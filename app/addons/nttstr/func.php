<?php
/***************************************************************************
 *                                                                          *
 *    Copyright (c) 2009 Simbirsk Technologies Ltd. All rights reserved.    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

// $Id: func.php by takahashi from cs-cart.jp 2019

use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * NTTスマートトレードでは注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためGMOマルチペイメントサービス
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_nttstr_finish_payment(&$order_id, &$pp_response, &$force_notification)
{
// 注文データ内の支払関連情報を取得
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

    // 注文データ内の支払関連情報が存在する場合
    if( !empty($payment_info) ){

        // 決済代行サービスのIDを取得
        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        if( empty($payment_id) ) return false;
        $payment_method_data = fn_get_payment_method_data($payment_id);
        if( empty($payment_method_data) ) return false;
        $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors JOIN ?:payments ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE payment_id = ?i", $payment_id);
        if( empty($pr_script) ) return false;

        switch($pr_script){
            case 'nttstr_cc.php':
                // 支払情報が暗号化されている場合は復号化して変数にセット
                if( !is_array($payment_info)) {
                    $info = @unserialize(fn_decrypt_text($payment_info));
                }else{
                    // 支払情報を変数にセット
                    $info = $payment_info;
                }

                // 支払情報から注文ステータスに関する記述を削除
                unset($info['order_status']);

                // カード情報への登録有無に関する記述を削除
                unset($info['use_uid']);

                // 支払情報を暗号化
                $_data = fn_encrypt_text(serialize($info));

                // 注文データ内の支払関連情報を上書き
                db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);
                break;
            default:
                // do nothing
        }
    }
}




/**
 * クレジット請求管理ページにおける注文情報の抽出・表示
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_nttstr_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    // クレジット請求管理ページの場合
    if( Registry::get('runtime.controller') == 'nttstr_cc_manager' && Registry::get('runtime.mode') == 'manage'){
        // カード決済および登録済カードにより支払われた注文のみ抽出
        $pr_scripts = array("nttstr_cc.php");
        $nttstr_cc_payments = db_get_fields("SELECT payment_id FROM ?:payments JOIN ?:payment_processors ON ?:payments.processor_id = ?:payment_processors.processor_id WHERE processor_script IN (?a)", $pr_scripts);
        $nttstr_cc_payments = implode(',', $nttstr_cc_payments);
        $condition .= " AND ?:orders.payment_id IN ($nttstr_cc_payments)";

        // 各注文にひもづけられたクレジット請求ステータスコードを抽出
        $fields[] = "?:jp_nttstr_cc_status.status_code as cc_status_code";
        $join .= " LEFT JOIN ?:jp_nttstr_cc_status ON ?:jp_nttstr_cc_status.order_id = ?:orders.order_id";
    }
}




/**
 * 注文情報削除時にクレジット決済の請求ステータスを削除
 *
 * @param $order_id
 */
function fn_nttstr_delete_order(&$order_id)
{
    $type = false;

    // 支払IDを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if( !empty($payment_id) && fn_nttstr_is_deletable($payment_id) ){
        $status_code = db_get_field("SELECT status_code FROM ?:jp_nttstr_cc_status WHERE order_id = ?i", $order_id);

        switch($status_code){
            // 与信状態の注文については与信を取消
            case NTTSTR_AUTH_OK:
                $type = 'cc_auth_cancel';
                break;
            // 売上処理が完了している注文については売上を取消
            case NTTSTR_CAPTURE_OK:
            case NTTSTR_SALES_CONFIRMED:
                $type = 'cc_sales_cancel';
                break;
            default:
                // do nothing
        }

        // 取消・削除処理を実行
        if(!empty($type)) fn_nttstr_send_cc_request($order_id, $type);
    }

    db_query("DELETE FROM ?:jp_nttstr_cc_status WHERE order_id = ?i", $order_id);
}




/**
 * ログにカード番号や有効期限、ShopIDなどが記録されることを回避
 *
 * @param $type
 * @param $action
 * @param $data
 * @param $user_id
 * @param $content
 * @param $event_type
 * @param $object_primary_keys
 */
function fn_nttstr_save_log(&$type, &$action, &$data, &$user_id, &$content, &$event_type, &$object_primary_keys)
{
    if($type == 'requests'){
        $url = $data['url'];
        switch($url){
            // 商用URL
            case NTTSTR_LIVE_URL_EXECTRAN:
            // 検証URL
            case NTTSTR_TEST_URL_EXECTRAN:
                $content['request'] = 'Hidden for Security Reason';
                $content['response'] = 'Hidden for Security Reason';
                break;

            default:
                // do nothing
        }
    }
}
##########################################################################################
// END フックポイントで動作する関数
##########################################################################################




##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################
/**
 * アドオンのインストール時の処理
 */
function fn_nttstr_install()
{
    fn_lcjp_install('nttstr');
}




/**
 * アドオンのアンインストール時に支払関連のレコードを削除
 */
function fn_nttstr_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('nttstr_cc.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('nttstr_cc.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('nttstr_cc.php')");
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################





##########################################################################################
// START アドオンの設定ページで動作する関数
##########################################################################################
##########################################################################################
//  END  アドオンの設定ページで動作する関数
##########################################################################################





##########################################################################################
// START その他の関数
##########################################################################################

/**
 * NTTスマートトレードの取消およびデータ削除処理の対象となる注文であるかを判定
 *
 * @param $payment_id
 * @return bool
 */
function fn_nttstr_is_deletable($payment_id)
{
    // 注文で使用されている決済代行業者IDを取得
    $payment_method_data = fn_get_payment_method_data($payment_id);
    if( empty($payment_method_data) ) return false;
    $processor_id = $payment_method_data['processor_id'];
    if( empty($processor_id) ) return false;
    $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

    // 決済代行業者がNTTスマートトレードの場合はtrueを返す
    switch($pr_script){
        case 'nttstr_cc.php':    // NTTスマートトレード（クレジットカード決済・トークン決済）
            return true;
            break;
        default:
            return false;
    }
}




/**
 * 実売上・金額変更・与信取消・売上取消処理を実行
 *
 * @param $order_id
 * @param string $type
 * @param string $org_amount
 * @return bool
 */
function fn_nttstr_send_cc_request( $order_id, $type = 'cc_sales_confirm')
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_nttstr_check_process_validity($order_id, $type);

    // 指定した処理を行うのに適した注文でない場合
    if ( !$is_valid_order ){
        return false;
    }

    $params = array();

    // 当該注文の支払方法に関する情報を取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);

    //////////////////////////////////////////////////////////////////////////
    // 共通パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    // 加盟店ID
    $params['shopId'] = Registry::get('addons.nttstr.shopid');

    // 加盟店注文番号
    $params['linked_1'] = db_get_field("SELECT linked_1 FROM ?:jp_nttstr_cc_status WHERE order_id = ?i", $order_id);

    // アクセスキー
    $params['accessKey'] = Registry::get('addons.nttstr.accesskey');

    // 利用者ID
    $params['aid'] = db_get_field("SELECT aid FROM ?:jp_nttstr_cc_status WHERE order_id = ?i", $order_id);

    // 利用金額
    $params['amount'] = round(db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id));

    // 代表商品番号
    $params['choComGoodsCode'] = "0990";
    //////////////////////////////////////////////////////////////////////////
    // 共通パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    switch($type){
        //  実売上
        case 'cc_sales_confirm':
            $transaction_type = 'sales';
            // 処理フラグ
            $params['flag'] = NTTSTR_SALES_CONFIRMED;

            break;

        // 与信取消
        case 'cc_auth_cancel':
            $transaction_type = 'authcancel';
            // 処理フラグ
            $params['flag'] = NTTSTR_AUTH_CANCEL;

            break;

        // 売上取消
        case 'cc_sales_cancel':
            $transaction_type = 'salescancel';
            // 処理フラグ
            $params['flag'] = NTTSTR_SALES_CANCEL;

            break;


        default :
            // do nothing
    }
    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    // 処理実行
    $altertran_result = fn_nttstr_send_request($transaction_type, $params, $processor_data['processor_params']['mode']);

    // NTTスマートトレードに対するリクエスト送信が正常終了した場合
    if (!empty($altertran_result)) {

        // 決済実行が正常に完了している場合
        if ( $altertran_result['payStatus'] == 'C1000000' ) {

            // 与信日時を取得
            if( !empty($altertran_result['date']) ){
                $process_timestamp = strtotime($altertran_result['date']);
            }else{
                $process_timestamp = time();
            }

            // 注文データ内に格納されたクレジット請求ステータスを更新
            fn_nttstr_update_cc_status($order_id, $type, $process_timestamp, $altertran_result['aid'], $altertran_result['linked_1']);

            // 注文情報を取得
            $order_info = fn_get_order_info($order_id);

            // DBに保管する支払い情報を生成
            fn_nttstr_format_payment_info($type, $order_id, $order_info['payment_info'], $altertran_result);

            return true;

            // エラー処理
        } else {
            $err_msg = __("order_id") . '#' . $order_id . ':' . fn_nttstr_get_err_msg($altertran_result['payStatus']);

            // エラーメッセージを表示
            fn_set_notification('E', __('jp_nttstr_cc_error'), $err_msg);
        }

        // リクエスト送信が失敗した場合
    }else{
        // エラーメッセージを表示
        fn_set_notification('E', __('jp_nttstr_cc_error'), __('jp_nttstr_cc_status_change_failed'));
    }

    return false;
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_nttstr_check_process_validity( $order_id, $type )
{
    // 注文データからクレジット請求ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_nttstr_cc_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 請求確定
        case 'cc_sales_confirm':
            // 与信取消
        case 'cc_auth_cancel':
            if( $cc_status == NTTSTR_AUTH_OK ) return true;
            break;
        // 売上取消
        case 'cc_sales_cancel':
            if( $cc_status == NTTSTR_SALES_CONFIRMED || $cc_status == NTTSTR_CAPTURE_OK ) return true;
            break;

        // その他
        default:
            // do nothing
    }

    return false;
}




/**
 * NTTスマートトレードに各種データを送信
 *
 * @param $type
 * @param $params
 * @return mixed|string
 */
function fn_nttstr_send_request($type, $params, $mode)
{
    // データ送信先URLと結果パラメータを初期化
    $target_url = '';

    if($mode == 'test'){
        $target_url = NTTSTR_TEST_URL_EXECTRAN;
    }else{
        $target_url = NTTSTR_LIVE_URL_EXECTRAN;
    }

    // 送信先URLが指定されている場合
    if( !empty($target_url) ){
        // NTTスマートトレードにデータを送信
        $_result_str = Http::post($target_url, $params);
    }

    // GETパラメータ形式（文字列）で結果データが返されるので配列化
    $_result_str = str_replace(PHP_EOL, '&', $_result_str);
    $_result_arr_1 = explode("&", $_result_str);
    $result = array();
    foreach($_result_arr_1 as $result_params_1){
        $result_arr_2 = explode('=', $result_params_1);
        if( count($result_arr_2) == 2 ) {
            $result[$result_arr_2[0]] = trim($result_arr_2[1]);
        }
    }

    return $result;
}




/**
 * 注文データ内に格納されたクレジット請求ステータスを更新
 *
 * @param $order_id
 * @param $type
 * @param $process_timestamp
 * @param $aid
 * @param $linked_1
 */
function fn_nttstr_update_cc_status($order_id, $type = 'cc_sales_confirm', $process_timestamp = '', $aid = '', $linked_1 = '')
{
    // クレジット請求ステータスを初期化
    $status_code = '';

    // 処理内容に応じてセットする値を変更
    switch($type){
        // 実売上
        case 'cc_sales_confirm':
            $status_code = NTTSTR_SALES_CONFIRMED;
            $msg = __('jp_nttstr_cc_sales_completed');
            break;
        // 与信取消
        case 'cc_auth_cancel':
            $status_code = NTTSTR_AUTH_CANCEL;
            $msg = __('jp_nttstr_cc_auth_cancelled');
            break;
        // 売上取消
        case 'cc_sales_cancel':
            $status_code = NTTSTR_SALES_CANCEL;
            $msg = __('jp_nttstr_cc_sales_cancelled');
            break;
        // その他
        default:
            // do nothing
    }

    // クレジット請求ステータスが設定されている場合
    if( !empty($status_code) ){
        // クレジット請求ステータスを更新
        fn_nttstr_update_cc_status_code($order_id, $status_code, $process_timestamp, $aid, $linked_1);
        // 処理完了メッセージを表示
        fn_set_notification('N', __('information'), $msg, 'K');
    }
}




/**
 * ステータスコードをセット
 *
 * @param $order_id
 * @param $status_code
 * @param $process_timestamp
 * @param $aid,
 * @param $linked_1
 */
function fn_nttstr_update_cc_status_code($order_id, $status_code, $process_timestamp = '', $aid = '', $linked_1 = '')
{
    // 注文確定前の場合
    $_data = array (
        'order_id' => $order_id,
        'linked_1' => $linked_1,
        'status_code' => $status_code,
        'aid' => $aid,
    );

    switch($status_code){
        case NTTSTR_AUTH_OK:
            $_data['auth_timestamp'] = $process_timestamp;

            break;
        case NTTSTR_SALES_CONFIRMED:
        case NTTSTR_CAPTURE_OK:
            $_data['capture_timestamp'] = $process_timestamp;
            break;

        default:
            // do nothing
            break;
    }

    // 当該注文に関するステータスコード関連レコードの存在チェック
    $is_exists = db_get_row("SELECT * FROM ?:jp_nttstr_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文に関するステータスコード関連レコードが存在する場合
    if( !empty($is_exists) ){
        // レコードを更新
        db_query("UPDATE ?:jp_nttstr_cc_status SET ?u WHERE order_id = ?i", $_data, $order_id);
        // 当該注文に関するステータスコード関連レコードが存在しない場合
    }else{
        // レコードを新規追加
        db_query("REPLACE INTO ?:jp_nttstr_cc_status ?e", $_data);
    }
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $nttstr_exec_results
 * @param bool $flg_comments
 * @return bool
 */
function fn_nttstr_format_payment_info($type, $order_id, $payment_info, $nttstr_exec_results)
{
    // 注文IDが存在しない場合は処理を終了
    if( empty($order_id) ) return false;

    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

    // 注文データ内の支払関連情報を取得（注文完了ページ表示前に決済完了通知が実行された場合に対応するため）
    if(empty($payment_info)){
        $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');
    }

    // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
    foreach($order_ids_to_process as $order_id){

        // 支払情報がすでに存在する場合
        if( !empty($payment_info) ){
            // 支払情報が暗号化されている場合は復号化して変数にセット
            if( !is_array($payment_info)) {
                $info = @unserialize(fn_decrypt_text($payment_info));
            }else{
                // 支払情報を変数にセット
                $info = $payment_info;
            }
        }

        // 請求ステータスを取得
        $nttstr_cc_status = db_get_field("SELECT status_code FROM ?:jp_nttstr_cc_status WHERE order_id = ?i", $order_id);

        // 支払情報がすでに存在する場合
        if( !empty($info) ){
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 BOF
            ////////////////////////////////////////////////////////////////////
            foreach($info as $key => $val){
                switch($type){
                    // クレジットカード決済に関する処理の場合
                    case 'cc' :
                    case 'cc_sales_confirm' :
                    case 'cc_auth_cancel' :
                    case 'cc_sales_cancel' :
                        switch($key){
                            // カード決済に関する情報のみ保持
                            case 'jp_nttstr_linked_1':
                            case 'jp_nttstr_date':
                            case 'jp_nttstr_centerpayid':
                            case 'jp_nttstr_cc_status':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // その他の場合
                    default:
                        // do noting
                }
            }
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 EOF
            ////////////////////////////////////////////////////////////////////
        }

        ////////////////////////////////////////////////////////////////////
        // 各支払方法固有の項目 BOF
        ////////////////////////////////////////////////////////////////////
        switch($type){
            // クレジットカード
            case 'cc' :

                if( !empty($nttstr_exec_results['linked_1']) ){
                    // オーダーID
                    $info['jp_nttstr_linked_1'] = $nttstr_exec_results['linked_1'];
                }

                // 請求ステータス
                if(!empty($nttstr_cc_status)){
                    $info['jp_nttstr_cc_status'] = fn_nttstr_get_cc_status_name($nttstr_cc_status);
                }

                // 決済日付
                $info['jp_nttstr_date'] = $nttstr_exec_results['date'];

                // 利用者ID
                $info['jp_nttstr_aid'] = $nttstr_exec_results['aid'];

                // .Com決済取引番号
                $info['jp_nttstr_centerpayid'] = $nttstr_exec_results['centerPayId'];

                break;

            // クレジットカード売上確定/与信取消/売上取消/金額変更
            case 'cc_sales_confirm' :
            case 'cc_auth_cancel' :
            case 'cc_sales_cancel' :

                // 請求ステータス
                if(!empty($nttstr_cc_status)){
                    $info['jp_nttstr_cc_status'] = fn_nttstr_get_cc_status_name($nttstr_cc_status);
                }

                // 決済日付
                $info['jp_nttstr_date'] = $nttstr_exec_results['date'];

                // 利用者ID
                $info['jp_nttstr_aid'] = $nttstr_exec_results['aid'];

                // .Com決済取引番号
                $info['jp_nttstr_centerpayid'] = $nttstr_exec_results['centerPayId'];

                break;

            // その他
            default:
                // do nothing

        }
        ////////////////////////////////////////////////////////////////////
        // 各支払方法固有の項目 EOF
        ////////////////////////////////////////////////////////////////////

        // 支払情報を暗号化
        $_data = fn_encrypt_text(serialize($info));

        // 注文データ内の支払関連情報の有無をチェック
        $tmp_order_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

        // 注文データ内の支払関連情報が存在する場合
        if( !empty($tmp_order_id) ){
            // 注文データ内の支払関連情報を上書き
            db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);

            // 注文データ内の支払関連情報が存在しない場合
        }else{
            // 注文データ内の支払関連情報を追加
            $insert_data = array (
                'order_id' => $order_id,
                'type' => 'P',
                'data' => $_data,
            );
            db_query("REPLACE INTO ?:order_data ?e", $insert_data);
        }
    }
}




/**
 * クレジット請求ステータス名を取得
 *
 * @param $cc_status
 * @return string
 */
function fn_nttstr_get_cc_status_name($cc_status)
{
    // クレジット請求ステータス名を初期化
    $cc_status_name = '';

    // 請求ステータスコードに応じて請求ステータス名を取得
    switch($cc_status){
        // 与信
        case NTTSTR_AUTH_OK:
            $cc_status_name = __('jp_nttstr_cc_auth_ok');
            break;
        // 与信取消
        case NTTSTR_AUTH_CANCEL:
            $cc_status_name = __('jp_nttstr_cc_auth_cancel');
            break;
        // 与信同時売上
        case NTTSTR_CAPTURE_OK:
            $cc_status_name = __('jp_nttstr_cc_captured');
            break;
        // 売上
        case NTTSTR_SALES_CONFIRMED:
            $cc_status_name = __('jp_nttstr_cc_sales_confirm');
            break;
        // 売上取消
        case NTTSTR_SALES_CANCEL:
            $cc_status_name = __('jp_nttstr_cc_sales_cancel');
            break;
    }

    return $cc_status_name;
}




/**
 * エラーメッセージを表示
 *
 * @param $errcode
 * @param $errinfo
 */
function fn_nttstr_set_err_msg($errcode, $errinfo = '')
{
    if( !empty($errinfo) ) {
        fn_set_notification('E', __('jp_nttstr_cc_error'), $errcode . ': ' . $errinfo);
    }
    else{
        fn_set_notification('E', __('jp_nttstr_cc_error'), fn_nttstr_get_err_msg($errcode));
    }
}




/**
 * NTTスマートトレードゲートウェイに送信するパラメータをセット
 *
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_nttstr_get_params($type, $order_id, $order_info, $processor_data)
{
    // 送信パラメータを初期化
    $params = array();

    // 処理別に異なるパラメータをセット
    switch ($type) {

        // クレジットカード決済（決済実行）
        case 'exectran':

            // 加盟店ID
            $params['shopId'] = Registry::get('addons.nttstr.shopid');

            // 加盟店注文番号
            $params['linked_1'] = $order_id . '_' . date('YmdHis');

            // アクセスキー
            $params['accessKey'] = Registry::get('addons.nttstr.accesskey');

            // トークン
            $params['token'] = $order_info['payment_info']['token'];

            // 利用金額
            $params['amount'] = round($order_info['total']);

            // 支払区分
            $params['payClass'] = $order_info['payment_info']['jp_cc_method'];

            // 支払区分が「分割」の場合
            if($order_info['payment_info']['jp_cc_method'] == "61"){
                // 支払回数
                $params['installCount'] = $order_info['payment_info']['jp_cc_installment_times'];
            }

            // 代表商品番号
            $params['choComGoodsCode'] = "0990";

            $products = $order_info[products];
            foreach($products as $product){
                $product_name = $product['product'];
                break;
            }

            // 代表商品名
            $params['choComTypicalGoodsName'] = mb_substr(mb_convert_encoding($product_name, 'SJIS', 'UTF-8'), 0, 255);

            // 代表商品名
            $params['amount'] = round($order_info['total']);

            // 処理フラグ
            $params['flag'] = $processor_data['processor_params']['jobcd'];

            break;

        default:
            // do nothing
            break;
    }

    return $params;
}




/**
 * 支払情報が存在するか確認
 *
 * @param $template
 * @return boolean
 */
function fn_nttstr_get_payment_info($template)
{
    $result = true;

    $path = "addons/nttstr/views/orders/components/payments/" . $template;

    $payment_info = db_get_field("SELECT payment_id FROM ?:payments WHERE template = ?s AND status=?s", $path, 'A');

    if(empty($payment_info)){
        $result = false;
    }

    return $result;

}




/**
 * エラーメッセージを取得する
 *
 * @param $response_cd
 * @return string
 */
function fn_nttstr_get_err_msg($response_cd)
{
    $err_msg = $response_cd . ' : ';

    $response_cd_lower = strtolower($response_cd);

    $err_msg .= __('jp_nttstr_errmsg_' . $response_cd_lower);

    // エラーコードに対応する言語変数が存在しない場合
    if( strpos($err_msg, 'jp_nttstr_errmsg_') === 0 || strpos($err_msg, 'jp_nttstr_errmsg_') > 0) {
        $err_msg = __("errorCode") . ": " . $response_cd;
    }

    return $err_msg;
}
##########################################################################################
// END その他の関数
##########################################################################################