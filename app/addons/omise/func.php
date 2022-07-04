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

// $Id: func.php by takahashi from cs-cart.jp 2017

use Tygh\Http;
use Tygh\Registry;

require_once dirname(__FILE__) . '/lib/Omise.php';

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * クレジットカード情報を登録済みの会員に対してのみ登録済みカード決済を表示
 *
 * @param $params
 * @param $payments
 */
function fn_omise_get_payments_post(&$params, &$payments)
{
    fn_lcjp_filter_payments($payments, 'omise_ccreg.tpl', 'omise_ccreg');
}




/**
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_omise_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
			case 'omise_cc.php':
			case 'omise_ccreg.php':
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
function fn_omise_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    // クレジット請求管理ページの場合
    if( Registry::get('runtime.controller') == 'omise_cc_manager' && Registry::get('runtime.mode') == 'manage'){
        // カード決済および登録済カードにより支払われた注文のみ抽出
        $pr_scripts = array("omise_cc.php", "omise_ccreg.php");
        $omise_cc_payments = db_get_fields("SELECT payment_id FROM ?:payments JOIN ?:payment_processors ON ?:payments.processor_id = ?:payment_processors.processor_id WHERE processor_script IN (?a)", $pr_scripts);
        $omise_cc_payments = implode(',', $omise_cc_payments);
        $condition .= " AND ?:orders.payment_id IN ($omise_cc_payments)";

        // 各注文にひもづけられたクレジット請求ステータスコードを抽出
        $fields[] = "?:jp_omise_cc_status.status_code as cc_status_code";
        $join .= " LEFT JOIN ?:jp_omise_cc_status ON ?:jp_omise_cc_status.order_id = ?:orders.order_id";
    }
}

/**
 * ユーザー削除時にomiseに登録されている会員情報も削除する
 *
 * @param $user_id
 * @param $user_data
 * @param $result
 * @return bool
 */
function fn_omise_post_delete_user(&$user_id, &$user_data, &$result)
{
    // CS-Cart側でのユーザー削除が完了した場合
    if($result){

        // omiseに顧客がとうろくされているか確認
        $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'omise_ccreg');

        $ccreg_delete_params[Customer] = $member_id;

        // 顧客情報が存在しない場合
        if( empty($member_id) ){
            // 削除済みクレジットカード情報が存在するかチェック
            $deleted_omise_customer = db_get_field("SELECT quickpay_id FROM ?:jp_omise_deleted_quickpay WHERE user_id = ?i", $user_id);

            // 削除済みクレジットカード情報が存在する場合
            if(!empty($deleted_omise_customer)){
                // 顧客IDをセット
                $ccreg_delete_params[Customer] = $deleted_omise_customer;
            }
        }

        // omiseの顧客登録済みユーザーの場合
        if(!empty($ccreg_delete_params[Customer])){

            // 顧客情報の削除
            $result_params = fn_omise_send_request('deletemember', $ccreg_delete_params);

            // omise側で正常に処理が終了した場合
            if ( !empty($result_params) ){
                db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'omise_ccreg');
                db_query("DELETE FROM ?:jp_omise_deleted_quickpay WHERE user_id = ?i", $user_id);
                fn_set_notification('N', __('information'), __('jp_omise_ccreg_delete_success'));
            // omise側でエラーが発生した場合
            }else{
                fn_set_notification('E', __('error'), __('jp_omise_ccreg_delete_failed'));
            }
        }
    }
}




/**
 * 注文情報削除時にクレジット決済の請求ステータスを削除
 *
 * @param $order_id
 */
function fn_omise_delete_order(&$order_id)
{
    $type = false;

    // 支払IDを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if( !empty($payment_id) && fn_omise_is_deletable($payment_id) ){
        $status_code = db_get_field("SELECT status_code FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_id);

        switch($status_code){
            // 与信状態の注文については与信を取消
            case 'AUTH_OK':
                $type = 'cc_auth_cancel';
                break;
            // 売上処理が完了している注文については売上を取消
            case 'CAPTURE_OK':
            case 'SALES_CONFIRMED':
                $type = 'cc_sales_cancel';
                break;
            default:
                // do nothing
        }

        // 取消・削除処理を実行
        if(!empty($type)) fn_omise_send_cc_request($order_id, $type);
    }

    db_query("DELETE FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_id);
}




/**
 * 注文データの削除時に売上取消（カード）またはデータ削除（収納代行）を実施
 *
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 * @return bool
 */
function fn_omise_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses, &$place_order)
{
    if( empty($order_info['payment_id']) ) return false;

    if($status_to == 'I' && fn_omise_is_deletable($order_info['payment_id']) ){

        $status_code = db_get_field("SELECT status_code FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_info['order_id']);

        switch($status_code){
            // 与信状態の注文については与信を取消
            case 'AUTH_OK':
                $type = 'cc_auth_cancel';
                break;
            // 売上処理が完了している注文については売上を取消
            case 'CAPTURE_OK':
            case 'SALES_CONFIRMED':
                $type = 'cc_sales_cancel';
                break;
            default:
                return false;
        }

        // 取消処理を実行
        fn_omise_send_cc_request($order_info['order_id'], $type);

        // 注文情報を取得
        $tmp_order_info = fn_get_order_info($order_info['order_id']);

        // 処理通番を更新
        if( !empty($tmp_order_info['payment_info']['jp_omise_transaction_id']) ){
            $order_info['payment_info']['jp_omise_transaction_id'] = $tmp_order_info['payment_info']['jp_omise_transaction_id'];
        }

        // 請求ステータスを更新
        if( !empty($tmp_order_info['payment_info']['jp_omise_cc_status']) ){
            $order_info['payment_info']['jp_omise_cc_status'] = $tmp_order_info['payment_info']['jp_omise_cc_status'];
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
function fn_omise_install()
{
    fn_lcjp_install('omise');
}




/**
 * アドオンのアンインストール時に支払関連のレコードを削除
 */
function fn_omise_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('omise_cc.php', 'omise_ccreg.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('omise_cc.php', 'omise_ccreg.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('omise_cc.php', 'omise_ccreg.php')");

}


##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################




##########################################################################################
// START その他の関数
##########################################################################################

/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * omiseゲートウェイに送信するパラメータをセット
 *
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_omise_get_params($type, $order_id, $order_info, $processor_data)
{
    // 送信パラメータを初期化
    $params = array();

    // 処理別に異なるパラメータをセット
    switch($type){

        // クレジットカード決済（決済実行）
        case 'exectran':

            // 処理区分
            $jobcd = $processor_data['processor_params']['jobcd'];

            if($jobcd == 'AUTH') {
                $params['Capture'] = 0;
            } else {
                $params['Capture'] = 1;
            }

            // 利用金額
            $params['Amount'] = round($order_info['total']);

            // 通貨コード
            $params['CurrencyCode'] = strtolower(db_get_field('SELECT currency_code FROM ?:currencies WHERE is_primary = ?s', 'Y'));

            // トークン
            $params['Token'] = $order_info['payment_info']['token'];

            // エラーコード、エラーメッセージ
            $params['ErrorCd'] = str_replace(' ', '_', $order_info['payment_info']['errorMsg']);
            $params['ErrorMsg'] = $order_info['payment_info']['errorMsg'];

            break;

        // 顧客登録・カード更新
        case 'savemember':
        case 'updatecard':

            // 顧客のメールアドレス
            $params['EmailAddress'] = $order_info['email'];

            // 顧客の説明文
            $params['Description'] = $order_info['firstname'] . ' ' . $order_info['lastname'];

            // トークン
            $params['Token'] = $order_info['payment_info']['token'];

            break;

        default:
            // do nothing
            break;
    }

    return $params;
}



/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $omise_exec_results
 * @param bool $flg_comments
 * @return bool
 */
function fn_omise_format_payment_info($type, $order_id, $payment_info, $omise_exec_results)
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
        $omise_cc_status = db_get_field("SELECT status_code FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_id);

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
                            case 'jp_omise_order_id':
                            case 'jp_omise_cc_status':
                            case 'jp_omise_approve':
                            case 'jp_omise_tran_id':
                            case 'jp_omise_tran_date':
                            case 'jp_omise_method':
                            case 'jp_omise_paytimes':
                            case 'jp_omise_card_company':
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

                if( !empty($omise_exec_results['id']) ){
                    // オーダーID
                    $info['jp_omise_order_id'] = $omise_exec_results['id'];
                }

                // 請求ステータス
                if(!empty($omise_cc_status)){
                    $info['jp_omise_cc_status'] = fn_omise_get_cc_status_name($omise_cc_status);
                }

                // トランザクションID
                $info['jp_omise_tran_id'] = $omise_exec_results['transaction'];

                // 決済日付
                $info['jp_omise_tran_date'] = fn_omise_format_date($omise_exec_results['created']);

                // カード会社
                $info['jp_omise_card_company'] = $omise_exec_results['card']['brand'];

                break;

            // クレジットカード売上確定/与信取消/売上取消/金額変更
            case 'cc_sales_confirm' :
            case 'cc_auth_cancel' :
            case 'cc_sales_cancel' :

                // 請求ステータス
                if(!empty($omise_cc_status)){
                    $info['jp_omise_cc_status'] = fn_omise_get_cc_status_name($omise_cc_status);
                }

                // トランザクションID
                $info['jp_omise_tran_id'] = $omise_exec_results['transaction'];

                // 決済日付
                $info['jp_omise_tran_date'] = fn_omise_format_date($omise_exec_results['created']);

                // カード会社
                $info['jp_omise_card_company'] = $omise_exec_results['card']['brand'];

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
 * omiseゲートウェイに各種データを送信
 *
 * @param $type
 * @param $params
 * @return mixed|string
 */
function fn_omise_send_request($type, $params)
{
    // API バージョン
    define('OMISE_API_VERSION', '2015-11-17');

    // パブリックキー
    define('OMISE_PUBLIC_KEY', Registry::get('addons.omise.public_key'));

    // シークレットキー
    define('OMISE_SECRET_KEY', Registry::get('addons.omise.secret_key'));

    // 結果パラメータを初期化
    $result = '';

    switch($type) {
        // カード決済（決済実行）
        case 'exectran':

            try {
                $result = OmiseCharge::create(array(
                    'amount' => $params[Amount],
                    'currency' => $params[CurrencyCode],
                    'card' => $params[Token],
                    'capture' => $params[Capture]
                ));
            } catch (Exception $e) {

            }

            break;

        // カード決済（登録済みカード決済実行）
        case 'exectranwc':

            try {
                $result = OmiseCharge::create(array(
                    'amount' => $params[Amount],
                    'currency' => $params[CurrencyCode],
                    'customer' => $params[Customer],
                    'card' => $params[CardId],
                    'capture' => $params[Capture]
                ));
            } catch (Exception $e) {

            }

            break;

        case 'searchcard':
            try {
                $result = OmiseCustomer::retrieve($params[Customer]);
            } catch (Exception $e) {

            }

            break;

        // トークンで顧客作成
        case 'savemember':

            try {
                $result = OmiseCustomer::create(array(
                    'email' => $params[EmailAddress],
                    'description' => $params[Description],
                    'card' => $params[Token]
                ));
            } catch (Exception $e) {

            }

            break;

        // トークンでカード更新
        case 'updatecard':

            try {
                $result = OmiseCustomer::retrieve($params[Customer]);
                $result->update(array(
                    'email' => $params[EmailAddress],
                    'description' => $params[Description],
                    'card' => $params[Token]
                ));
            } catch (Exception $e) {

            }

            break;

        // 顧客削除
        case 'deletemember':
            try {
                $result = OmiseCustomer::retrieve($params[Customer]);
                $result->destroy();
                $result->isDestroyed(); # => true
            } catch (Exception $e) {

            }

            break;

        // カード情報取得
        case 'getcard':
            try {
                $customer = OmiseCustomer::retrieve($params[Customer]);
                $result = $customer->getCards();
            } catch (Exceptoin $e) {

            }

            break;

        // カード削除
        case 'deletecard':
            try {
                $customer = OmiseCustomer::retrieve($params[Customer]);
                $result = $customer->getCards();

                $num_of_card = (int)$result[total];

                for ($i = 0; $i < $num_of_card; $i++) {
                    $card = $customer->getCards()->retrieve($result[data][$i][id]);
                    $card->destroy();
                    $card->isDestroyed(); # => true
                }
            } catch (Exception $e) {

            }

            break;

        // 実売上
        case 'sales':
            try {
                $result = OmiseCharge::retrieve($params[Id]);
                $result->capture();
            } catch (Exception $e) {

            }

            break;

        // 与信キャンセル
        case 'authcancel':
            try {
                $result = OmiseCharge::retrieve($params[Id]);
                $result->reverse();
            } catch (Exception $e) {

            }

            break;

        // 売上取消
        case 'salescancel':
            try {
                $charge = OmiseCharge::retrieve($params[Id]);
                $result = $charge->refunds()->create(array('amount' => (int)$params[Amount]));
            } catch (Exception $e) {

            }
            break;

        default:
            // do nothing
    }

    return $result;
}



/**
 * 日時データを読みやすくフォーマット
 *
 * @param $date
 * @param string $time
 * @return bool|string
 */
function fn_omise_format_date($datetime)
{
    $_year = substr($datetime, 0, 4);
    $_month = substr($datetime, 5, 2);
    $_date = substr($datetime, 8, 2);

    return $_year . '/' . $_month . '/' . $_date;
}





/**
 * エラーメッセージを表示
 *
 * @param $errcode
 * @param $errinfo
 */
function fn_omise_set_err_msg($errcode, $errinfo)
{
    // エラーコードおよびエラー詳細コードを配列化
    $errcode_array = explode('|', $errcode);
    $errinfo_array = explode('|', $errinfo);

    // エラーメッセージを表示
    if(is_array($errcode_array)){
        foreach($errcode_array as $key => $code){
            fn_set_notification('E', __('jp_omise_cc_error'), fn_omise_get_err_msg($errcode_array[$key]));
        }
    }
}




/**
 * エラーメッセージを取得
 *
 * @param $err_detail_code
 * @return string
 */
function fn_omise_get_err_msg($err_detail_code)
{
    $err_msg = __('jp_omise_errmsg_'.$err_detail_code);

    // エラーコードに対応する言語変数が存在しない場合、汎用メッセージをセット
    if( strpos($err_msg, 'jp_omise_errmsg_') === 0 || strpos($err_msg, 'jp_omise_errmsg_') > 0) {
        $err_msg = __('jp_omise_cc_failed');
    }

    // エラーメッセージを返す
    return $err_msg;
}

/**
 * 指定された決済方法に関するデータを取得
 *
 * @param $type
 * @return array|bool
 */
function fn_omise_get_processor_data($type)
{
    // 指定された決済方法で使用するスクリプトファイル名を取得
    switch($type){
        case 'cc':
            $script = 'omise_cc.php';
            break;
        case 'ccreg':
            $script = 'omise_ccreg.php';
            break;
        default;
            return false;
    }

    // 指定された決済方法に関するデータを取得
    $payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = ?s AND ?:payments.status = 'A'", $script);
    $processor_data = fn_get_processor_data($payment_id);

    // 決済方法に関するデータが存在する場合
    if( !empty($processor_data) ){
        // 取得したデータを返す
        return $processor_data;
        // 決済方法に関するデータが存在しない場合
    }else{
        // falseを返す
        return false;
    }
}

/**
 * omiseの取消およびデータ削除処理の対象となる注文であるかを判定
 *
 * @param $payment_id
 * @return bool
 */
function fn_omise_is_deletable($payment_id)
{
    // 注文で使用されている決済代行業者IDを取得
    $payment_method_data = fn_get_payment_method_data($payment_id);
    if( empty($payment_method_data) ) return false;
    $processor_id = $payment_method_data['processor_id'];
    if( empty($processor_id) ) return false;
    $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

    // 決済代行業者がomiseの場合はtrueを返す
    switch($pr_script){
        case 'omise_cc.php':    // omise（カード決済）
        case 'omise_ccreg.php':    // omise（登録済みカード決済）
            return true;
            break;
        default:
            return false;
    }
}
/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 EOF
/////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////
// カード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////


/**
 * ステータスコードをセット
 *
 * @param $order_id
 * @param $job_code
 * @param string $access_id
 * @param string $access_pass
 */
function fn_omise_update_cc_status_code($order_id, $job_code, $process_timestamp = '', $access_id = '', $access_pass = '')
{
    // 注文確定前の場合
    if($job_code == 'IN_PROCESS'){
        $_data = array (
            'order_id' => $order_id,
            'status_code' => fn_omise_get_status_code($job_code),
            'access_id' => $access_id,
            'access_pass' => $access_pass,
        );

    // 注文確定後の場合
    }else{
        $_data = array (
            'order_id' => $order_id,
            'status_code' => fn_omise_get_status_code($job_code),
        );

        // ステータスコードに応じてタイムスタンプをセット
        if( !empty($process_timestamp) ){
            fn_omise_set_timestamp($_data, $process_timestamp);
        }
    }

    // 当該注文に関するステータスコード関連レコードの存在チェック
    $is_exists = db_get_row("SELECT * FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文に関するステータスコード関連レコードが存在する場合
    if( !empty($is_exists) ){
        // レコードを更新
        db_query("UPDATE ?:jp_omise_cc_status SET ?u WHERE order_id = ?i", $_data, $order_id);
    // 当該注文に関するステータスコード関連レコードが存在しない場合
    }else{
        // レコードを新規追加
        db_query("REPLACE INTO ?:jp_omise_cc_status ?e", $_data);
    }
}




/**
 * 処理区分に応じてステータスコードを取得
 *
 * @param $job_code
 * @return string
 */
function fn_omise_get_status_code($job_code)
{
    switch($job_code){
        case 'AUTH':
            return 'AUTH_OK';
            break;
        case 'CAPTURE':
            return 'CAPTURE_OK';
            break;
        default:
            return strtoupper($job_code);
            // do nothing
    }
}




/**
 * ステータスコードに応じてタイムスタンプをセット
 *
 * @param $_data
 * @return bool
 */
function fn_omise_set_timestamp(&$_data, $process_timestamp)
{
    // ステータスコードが存在しない場合、処理を終了
    if( empty($_data['status_code']) ) return false;

    // ステータスコードに応じてタイムスタンプをセット
    switch($_data['status_code']){
        // 与信OKの場合
        case 'AUTH_OK':
            // 与信日時のタイムスタンプをセット
            $_data['auth_timestamp'] = $process_timestamp;
            break;

        // 即時売上または実売上の場合
        case 'CAPTURE_OK':
        case 'SALES_CONFIRMED':
            // 売上日時のタイムスタンプをセット
            $_data['capture_timestamp'] = $process_timestamp;
            break;

        // 中間処理の場合はタイムスタンプはセットしない
        case 'IN_PROCESS':
            // do nothing
            break;

        // その他のステータスコードの場合
        default:
            // 与信日時・売上日時のタイムスタンプをリセット
            $_data['auth_timestamp'] = 0;
            $_data['capture_timestamp'] = 0;
    }
}




/**
 * 注文データ内に格納されたクレジット請求ステータスを更新
 *
 * @param $order_id
 * @param string $type
 */
function fn_omise_update_cc_status($order_id, $type = 'cc_sales_confirm', $process_timestamp = '')
{
    // クレジット請求ステータスを初期化
    $status_code = '';

    // 処理内容に応じてセットする値を変更
    switch($type){
        // 実売上
        case 'cc_sales_confirm':
            $status_code = 'SALES_CONFIRMED';
            $msg = __('jp_omise_cc_sales_completed');
            break;
        // 与信取消
        case 'cc_auth_cancel':
            $status_code = 'AUTH_CANCELLED';
            $msg = __('jp_omise_cc_auth_cancelled');
            break;
        // 売上取消
        case 'cc_sales_cancel':
            $status_code = 'SALES_CANCELLED';
            $msg = __('jp_omise_cc_sales_cancelled');
            break;

        // その他
        default:
            // do nothing
    }

    // クレジット請求ステータスが設定されている場合
    if( !empty($status_code) ){
        // クレジット請求ステータスを更新
        fn_omise_update_cc_status_code($order_id, $status_code, $process_timestamp);
        // 処理完了メッセージを表示
        fn_set_notification('N', __('information'), $msg, 'K');
    }
}

/////////////////////////////////////////////////////////////////////////////////////
// カード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// 登録済みカード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * omiseゲートウェイに登録済みのカード情報を削除
 *
 * @param $user_id
 */
function fn_omise_delete_card_info($user_id)
{
    // omiseゲートウェイに会員登録済みかチェック
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'omise_ccreg');

    // omiseゲートウェイに会員登録済みの場合
    if(!empty($member_id)){
        // 登録済みカード決済用レコードを削除
        db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'omise_ccreg');

        // Omiseの削除済みカード情報を格納するテーブルにレコードをセット
        $_data['user_id'] = $user_id;
        $_data['quickpay_id'] = $member_id;
        db_query("REPLACE INTO ?:jp_omise_deleted_quickpay ?e", $_data);

        // omiseゲートウェイに登録された顧客カード情報を削除
        $deletecard_params[Customer] = $member_id;
        $success_delete = fn_omise_send_request('deletecard', $deletecard_params);

        // omiseゲートウェイに登録された顧客情報の削除に成功した場合
        if( $success_delete ){
            // 削除成功メッセージを表示
            fn_set_notification('N', __('notice'), __('jp_omise_ccreg_delete_success'));
        // omiseゲートウェイに登録された顧客情報の削除に失敗した場合
        }else{
            // 削除失敗メッセージを表示
            fn_set_notification('N', __('notice'), __('jp_omise_ccreg_delete_failed'));
        }
    }
}





/**
 * 注文に使用したカード番号をomiseゲートウェイに登録
 *
 * @param $order_info
 * @param $processor_data
 * @return bool
 */
function fn_omise_register_cc_info($order_info, $processor_data)
{
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $order_info['user_id'], 'omise_ccreg');

    // omiseゲートウェイに顧客登録されていない場合
    if( empty($member_id) ) {

        // 削除済みクレジットカード情報が存在するかチェック
        $deleted_customer = db_get_field("SELECT quickpay_id FROM ?:jp_omise_deleted_quickpay WHERE user_id = ?i", $order_info['user_id']);

        // 削除済みクレジットカード情報が存在する場合
        if (!empty($deleted_customer)) {
            // カード登録に必要なパラメータを取得して、顧客のカード情報を更新
            $savemember_params = fn_omise_get_params('updatecard', $order_info['order_id'], $order_info, $processor_data);
            $savemember_params[Customer] = $deleted_customer;
            $savemember_result = fn_omise_send_request('updatecard', $savemember_params);
        }
        // 削除済みクレジットカード情報が存在しない場合
        else {
            // 顧客登録に必要なパラメータを取得して、顧客登録を実行
            $savemember_params = fn_omise_get_params('savemember', $order_info['order_id'], $order_info, $processor_data);
            $savemember_result = fn_omise_send_request('savemember', $savemember_params);
        }

        // 顧客登録に関するリクエスト送信が正常終了した場合
        if (!empty($savemember_result)) {

            // 顧客登録が正常に終了している場合
            if (!empty($savemember_result['id'])) {
                $member_id = $savemember_result['id'];

                $_data = array('user_id' => $order_info['user_id'],
                    'payment_method' => 'omise_ccreg',
                    'quickpay_id' => $member_id,
                );
                db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);

                // 登録・更新完了メッセージを表示
                fn_set_notification('N', __('information'), __('jp_omise_ccreg_register_success'));

            // 顧客登録に失敗した場合
            }else{
                // エラーメッセージを表示して処理を終了
                fn_omise_set_err_msg(__('error'), __('jp_omise_ccreg_register_failed'));
                return false;
            }

        // 顧客登録に関するリクエスト送信に失敗した場合
        }else{
            // エラーメッセージを表示して処理を終了
            fn_set_notification('E', __('jp_omise_ccreg_error'),__('jp_omise_ccreg_register_failed'));
            return false;
        }
    }
    // 顧客登録済みの場合
    else
    {
        // 顧客に登録済みのカードを削除
        $deletecard_params[Customer] = $member_id;
        fn_omise_send_request('deletecard', $deletecard_params);

        // カード更新に必要なパラメータを取得して、顧客へのカード更新を実行
        $updatecard_params = fn_omise_get_params('updatecard', $order_info['order_id'], $order_info, $processor_data);
        $updatecard_params[Customer] = $member_id;
        $savemember_result = fn_omise_send_request('updatecard', $updatecard_params);

        // カード更新に関するリクエスト送信が正常終了した場合
        if (!empty($savemember_result)) {

            // カード更新が正常に終了している場合
            if (!empty($savemember_result['id'])) {
                $member_id = $savemember_result['id'];

                $_data = array('user_id' => $order_info['user_id'],
                    'payment_method' => 'omise_ccreg',
                    'quickpay_id' => $member_id,
                );
                db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);

                // 登録・更新完了メッセージを表示
                fn_set_notification('N', __('information'), __('jp_omise_ccreg_register_success'));

                // カード更新に失敗した場合
            } else {
                // エラーメッセージを表示して処理を終了
                fn_omise_set_err_msg(__('error'), __('jp_omise_ccreg_register_failed'));
                return false;
            }
        }
    }

    return $savemember_result;
}



/**
 * 登録済みカード情報の削除
 *
 * @param $order_info
 * @param $processor_data
 * @param $member_id
 * @return bool
 */
function fn_omise_delete_cc($member_id)
{


    // 登録済み顧客削除
    $deletecard_params[Customer] = $member_id;
    $deletecard_result = fn_omise_send_request('deletemember', $deletecard_params);

    // 登録済み顧客削除に関するリクエスト送信が正常終了した場合
    if (!empty($deletecard_result)) {

        // 登録済み顧客削除が正常に終了している場合
        if ( !is_null($deletecard_result['id']) ) {
            return true;
            // 登録済み顧客削除に失敗した場合
        }else{
            return false;
        }
        // 登録済み顧客削除に関するリクエスト送信に失敗した場合
    }else{
        return false;
    }
}




/**
 * omiseゲートウェイに登録されたカード情報を取得
 *
 * @param $user_id
 * @return array|bool
 */
function fn_omise_get_registered_card_info($user_id)
{
    // ユーザーIDが指定されていない場合は処理を終了
    if( empty($user_id) ) return false;

    // 登録済みカード情報を格納する変数を初期化
    $registered_card = false;

    // 支払方法に関するデータを取得
    $payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'omise_ccreg.php' AND ?:payments.status = 'A'");
    $processor_data = fn_get_processor_data($payment_id);

    // 会員IDを取得
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'omise_ccreg');

    // 会員IDが存在する場合
    if(!empty($member_id)){

        $searchcard_params = array();

        // Customer
        $searchcard_params[Customer] = $member_id;

        // 登録済みカード照会
        $searchcard_result = fn_omise_send_request('searchcard', $searchcard_params);

        // 登録済みカード照会に関するリクエスト送信が正常終了した場合
        if (!empty($searchcard_result)) {

            // 登録済みカード照会が正常に終了している場合
            if ( !is_null($searchcard_result['id'])) {

                // カードの登録数
                $no_of_card =  $searchcard_result['cards']['total'];

                // 最新のカードの位置
                $latest_card_pos = (string)((int)$no_of_card -1);

                $registered_card = array('card_number' => '**** **** **** '.$searchcard_result['cards']['data'][$latest_card_pos]['last_digits'], 'card_valid_term' => $searchcard_result['cards']['data'][$latest_card_pos]['expiration_year']. substr('0' . $searchcard_result['cards']['data'][$latest_card_pos]['expiration_month'], -2));

            // 登録済みカード照会に失敗した場合
            }else{
                // カード情報は表示しない
            }
        // 登録済みカード削除に関するリクエスト送信に失敗した場合
        }else{
            // カード情報は表示しない
        }
    }

    return $registered_card;
}
/////////////////////////////////////////////////////////////////////////////////////
//  登録済みカード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * クレジット請求ステータス名を取得
 *
 * @param $cc_status
 * @return string
 */
function fn_omise_get_cc_status_name($cc_status)
{
    // クレジット請求ステータス名を初期化
    $cc_status_name = '';

    // 請求ステータスコードに応じて請求ステータス名を取得
    switch($cc_status){
        // 仮売上
        case 'AUTH_OK':
            $cc_status_name = __('jp_omise_cc_auth_ok');
            break;
        // 与信NG
        case 'AUTH_NG':
            $cc_status_name = __('jp_omise_cc_auth_ng');
            break;
        // 与信取消
        case 'AUTH_CANCELLED':
            $cc_status_name = __('jp_omise_cc_auth_cancel');
            break;
        // 即時売上
        case 'CAPTURE_OK':
            $cc_status_name = __('jp_omise_cc_captured');
            break;
        // 実売上
        case 'SALES_CONFIRMED':
            $cc_status_name = __('jp_omise_cc_sales_confirm');
            break;
        // 売上取消
        case 'SALES_CANCELLED':
            $cc_status_name = __('jp_omise_cc_sales_cancel');
            break;
    }

    return $cc_status_name;
}




/**
 * 実売上・金額変更・与信取消・売上取消処理を実行
 *
 * @param $order_id
 * @param string $type
 * @param string $org_amount
 * @return bool
 */
function fn_omise_send_cc_request( $order_id, $type = 'cc_sales_confirm')
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_omise_check_process_validity($order_id, $type);

    // 指定した処理を行うのに適した注文でない場合
    if ( !$is_valid_order ){
        return false;
    }

    $params = array();

    // アクセスIDとアクセスパスワードを取得
    $omise_cc_access_info = db_get_row("SELECT * FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文の支払方法に関する情報を取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);


    // 課金IDの取得
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

    // 支払情報が存在する場合
    if (!empty($payment_info)) {
        // 支払情報が暗号化されている場合は復号化して変数にセット
        if (!is_array($payment_info)) {
            $info = @unserialize(fn_decrypt_text($payment_info));
        } else {
            // 支払情報を変数にセット
            $info = $payment_info;
        }
    }

    $params[Id] = $info['jp_omise_order_id'];

    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    switch($type){
        //  実売上
        case 'cc_sales_confirm':
            $transaction_type = 'sales';
            // 処理区分
            $params['JobCd'] = 'SALES';

            break;

        // 与信取消
        case 'cc_auth_cancel':
            $transaction_type = 'authcancel';
            // 処理区分
            $params['JobCd'] = fn_omise_get_cancel_jobcd($order_id, $type);

            break;

        // 売上取消
        case 'cc_sales_cancel':
            $transaction_type = 'salescancel';
            // 処理区分
            $params['JobCd'] = fn_omise_get_cancel_jobcd($order_id, $type);

            // 利用金額
            $params['Amount'] = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);

            break;


        default :
            // do nothing
    }
    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    // 処理実行
    $altertran_result = fn_omise_send_request($transaction_type, $params);

    // omiseゲートウェイに対するリクエスト送信が正常終了した場合
    if (!empty($altertran_result)) {

        // 決済実行が正常に完了している場合
        if (!empty($altertran_result)) {

            // 与信日時を取得
            if( !empty($altertran_result['created']) ){
                $process_timestamp = strtotime($altertran_result['created']);
            }else{
                $process_timestamp = time();
            }

            // 注文データ内に格納されたクレジット請求ステータスを更新
            fn_omise_update_cc_status($order_id, $type, $process_timestamp);

            // 注文情報を取得
            $order_info = fn_get_order_info($order_id);

            // DBに保管する支払い情報を生成
            fn_omise_format_payment_info($type, $order_id, $order_info['payment_info'], $altertran_result);

            return true;

        // エラー処理
        } else {
            // エラーメッセージを表示
            fn_omise_set_err_msg($altertran_result['failure_code'], $altertran_result['failure_message']);
        }

    // リクエスト送信が失敗した場合
    }else{
        // エラーメッセージを表示
        fn_set_notification('E', __('jp_omise_cc_error'), __('jp_omise_cc_status_change_failed'));
    }

    return false;
}





/**
 * 仮売上・即時売上・実売上のキャンセルを実行する際の処理区分を取得
 *（処理区分には処理タイミングにより VOID / RETURN / RETURNX の3種類がある）
 *
 * @param $order_id
 * @param $type
 * @return string
 */
function fn_omise_get_cancel_jobcd($order_id, $type)
{
    // 処理区分を初期化
    $jobcd = '';

    // 当該注文に関する請求関連レコードを取得
    $cc_status_data = db_get_row("SELECT * FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文に関する請求ステータス関連レコードが存在する場合
    if( !empty($cc_status_data) ){

        // 請求ステータスの種類に応じて処理を実施
        switch($cc_status_data['status_code']){

            // 請求ステータスが仮売上の場合
            case 'AUTH_OK':
                // 仮売上をキャンセルする場合
                if($type == 'cc_auth_cancel'){
                    // 仮売上日時のタイムスタンプを取得
                    $auth_timestamp = $cc_status_data['auth_timestamp'];

                    // 現在のタイムスタンプを取得
                    $current_timestamp = time();

                    // 仮売上日時が現在のタイムスタンプよりも古い場合（通常仮売上日時が現在時刻よりも新しいことはない）
                    if($current_timestamp > $auth_timestamp){
                        // YYMMDD形式の仮売上日時を取得
                        $auth_ymd = date("Ymd", $auth_timestamp);
                        // YYMMDD形式の現在日時を取得
                        $current_ymd = date("Ymd", $current_timestamp);

                        // 仮売上と同日のキャンセルの場合
                        if($auth_ymd == $current_ymd){
                            $jobcd = 'VOID';

                        // 仮売上の翌日以降のキャンセルの場合
                        }else{
                            $jobcd = 'RETURN';
                        }
                    }
                }
                break;

            // 請求ステータスが即時売上の場合
            case 'CAPTURE_OK':
                // 即時売上をキャンセルする場合
                if($type == 'cc_sales_cancel'){
                    // 即時売上日時のタイムスタンプを取得
                    $capture_timestamp = $cc_status_data['capture_timestamp'];

                    // 現在のタイムスタンプを取得
                    $current_timestamp = time();

                    // 即時売上日時が現在のタイムスタンプよりも古い場合（通常即時売上日時が現在時刻よりも新しいことはない）
                    if($current_timestamp > $capture_timestamp){
                        // YYMMDD形式で即時売上日時と現在日時を取得
                        $capture_ymd = date("Ymd", $capture_timestamp);
                        $current_ymd = date("Ymd", $current_timestamp);

                        // MM形式で即時売上月と現在月を取得
                        $capture_m = date("m", $capture_timestamp);
                        $current_m = date("m", $current_timestamp);

                        // 即時売上と同日のキャンセルの場合
                        if($capture_ymd == $current_ymd){
                            $jobcd = 'VOID';
                        // 即時売上の翌月以降のキャンセルの場合
                        }elseif( $capture_m != $current_m ){
                            $jobcd = 'RETURNX';
                        // その他の場合
                        }else{
                            $jobcd = 'RETURN';
                        }
                    }
                }
                break;

            // 請求ステータスが「実売上」の場合
            case 'SALES_CONFIRMED':
                // 実売上をキャンセルする場合
                if($type == 'cc_sales_cancel'){
                    // 仮売上日時、実売上日時、現在のタイムスタンプを取得
                    $auth_timestamp = $cc_status_data['auth_timestamp'];
                    $capture_timestamp = $cc_status_data['capture_timestamp'];
                    $current_timestamp = time();

                    // 仮売上日時が現在のタイムスタンプよりも古い場合（通常仮売上日時が現在時刻よりも新しいことはない）
                    if($current_timestamp > $auth_timestamp){
                        // YYMMDD形式で仮売上日時、現在日時を取得
                        $auth_ymd = date("Ymd", $capture_timestamp);
                        $current_ymd = date("Ymd", $current_timestamp);

                        // MM形式で実売上月、現在月を取得
                        $capture_m = date("m", $capture_timestamp);
                        $current_m = date("m", $current_timestamp);

                        // 仮売上と同日のキャンセルの場合
                        if($auth_ymd == $current_ymd){
                            $jobcd = 'VOID';
                        // 実売上の翌月以降のキャンセルの場合
                        }elseif( $capture_m != $current_m ){
                            $jobcd = 'RETURNX';
                        }else{
                            $jobcd = 'RETURN';
                        }
                    }
                }
                break;

            default:
                // do nothing
        }
    }

    return $jobcd;
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_omise_check_process_validity( $order_id, $type )
{
    // 注文データからクレジット請求ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_omise_cc_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 請求確定
        case 'cc_sales_confirm':
        // 与信取消
        case 'cc_auth_cancel':
            if( $cc_status == 'AUTH_OK' ) return true;
            break;
        // 売上取消
        case 'cc_sales_cancel':
            if( $cc_status == 'SALES_CONFIRMED' || $cc_status == 'CAPTURE_OK' ) return true;
            break;

        // その他
        default:
            // do nothing
    }

    return false;
}
/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 EOF
/////////////////////////////////////////////////////////////////////////////////////





##########################################################################################
// END その他の関数
##########################################################################################
