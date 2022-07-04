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

// $Id: paygent_cc.php by tommy from cs-cart.jp 2016
// ペイジェント（クレジットカード決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でペイジェントに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

	// ACSからの3D認証結果が戻された場合
	if( !empty($_REQUEST['order_id']) && isset($_REQUEST['result']) && $_REQUEST['process_type'] == '3dsecure_return' ){

        // 注文ID
        $order_id = $_REQUEST['order_id'];

        // 処理結果
        $resultStatus = $_REQUEST['result'];

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ) {
            // レスポンスコードを取得
            $responseCode = $_REQUEST['response_code'];

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($_REQUEST['response_detail'], 'UTF-8', 'Shift-JIS');

            // 注文手続きページにリダイレクトしてエラーメッセージを表示
            fn_pygnt_handle_process_error($responseCode, $responseDetail);

        // カード会社側画面の3Dセキュア処理が完了した場合
        }else{

            // 注文IDと利用した支払方法がマッチした場合
            if (fn_check_payment_script('paygent_cc.php', $order_id)) {
                // 決済IDを注文データに登録
                if( !empty($_REQUEST['payment_id']) ){
                    $res_array = array();
                    $res_array['payment_id'] = $_REQUEST['payment_id'];
                    // DBに保管する支払い情報を生成
                    fn_pygnt_format_payment_info('cc', $order_id, array(), $res_array);
                }

                // カード決済に関するステータス管理レコードを生成
                fn_pygnt_update_cc_status_code($order_id, 'AUTH_OK', $res_array['payment_id']);

                // 注文処理ページへリダイレクト
                $pp_response = array();
                $pp_response['order_status'] = 'P';
                fn_finish_payment($order_id, $pp_response);
                fn_order_placement_routines('route', $order_id);

            // 注文IDと利用した支払方法がマッチしなかった場合
            }else{
                // 表示するエラー内容を生成
                $responseinfo = __("jp_paygent_payment_unmatch_error");

                // 注文手続きページにリダイレクトしてエラーメッセージを表示
                fn_pygnt_handle_process_error($responseinfo);
            }
        }

    // その他の場合
    }else{

        // 処理タイプを初期化
        $type = 'cc';

        // 注文編集の場合
        if( Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {

            // 金額変更可否判定
            $is_cc_changeable = fn_pygnt_cc_is_changeable($order_id, $processor_data);

            // 金額変更の場合
            if ($is_cc_changeable) {
                fn_pygnt_cc_change_amount($order_id);
                return true;
            }
        }

        // ペイジェントに送信する取引登録用パラメータをセット
        $params = array();
        $params = fn_pygnt_get_params($type, $order_id, $order_info, $processor_data);
    }

    // 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    // ペイジェントに要求を送信
    $result = fn_pygnt_send_params($p, $params);

    // データ送信においてエラーが発生した場合
    if( !($result === true) ) {
        // 通信エラー処理
        fn_pygnt_handle_process_error($result);

    // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ) {
            // レスポンスコードを取得
            $responseCode = $p->getResponseCode();

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

            // 注文手続きページにリダイレクトしてエラーメッセージを表示
            fn_pygnt_handle_process_error($responseCode, $responseDetail);

        // 3Dセキュア認証を実施する場合
        }elseif( $resultStatus == 7 ){

            // CS-Cart側でのカード情報登録に必要なデータが存在する場合
            if( $p->hasResNext() ) {
                $res_array = $p->resNext();

                // 3Dセキュアを実施する場合
                if( !empty($res_array['out_acs_html']) ){

                    if( !empty($res_array) ){
                        foreach($res_array as $key => $val){
                            $res_array[$key] = mb_convert_encoding($val, 'UTF-8', 'Shift-JIS');
                        }
                    }

                    // クレジットカード情報お預かり
                    fn_pygnt_register_card($p, $type, $order_id, $order_info, $processor_data);

                    // DBに保管する支払い情報を生成
                    fn_pygnt_format_payment_info('cc', $order_id, $payment_info, $res_array);

                    // 3Dセキュア用HTMLを出力
                    echo $res_array['out_acs_html'];
                    exit;
                }
            }

            // 表示するエラー内容を生成
            $responseinfo = __("jp_paygent_3dsecure_error");

            // 注文手続きページにリダイレクトしてエラーメッセージを表示
            fn_pygnt_handle_process_error($responseinfo);

        // ペイジェントによるオーソリが完了した場合
        }else{

            // CS-Cart側でのカード情報登録に必要なデータが存在する場合
            if( $p->hasResNext() ) {
                $res_array = $p->resNext();

                if( !empty($res_array) ){
                    foreach($res_array as $key => $val){
                        $res_array[$key] = mb_convert_encoding($val, 'UTF-8', 'Shift-JIS');
                    }
                }

            // CS-Cart側でのカード情報登録に必要なデータが存在しない場合
            }else{
                $res_array = array();
            }

            // 注文IDと利用した支払方法がマッチした場合
            if( fn_check_payment_script('paygent_cc.php', $order_id) ){

                // DBに保管する支払い情報を生成
                fn_pygnt_format_payment_info('cc', $order_id, $payment_info, $res_array);

                // クレジットカード情報お預かり
                fn_pygnt_register_card($p, $type, $order_id, $order_info, $processor_data);

                // カード決済に関するステータス管理レコードを生成
                fn_pygnt_update_cc_status_code($order_id, 'AUTH_OK', $res_array['payment_id']);

                // 注文処理ページへリダイレクト
                $pp_response = array();
                $pp_response['order_status'] = 'P';
                fn_finish_payment($order_id, $pp_response);
                fn_order_placement_routines('route', $order_id);

            // 注文IDと利用した支払方法がマッチしなかった場合
            }else{
                // 表示するエラー内容を生成
                $responseinfo = __("jp_paygent_payment_unmatch_error");

                // 注文手続きページにリダイレクトしてエラーメッセージを表示
                fn_pygnt_handle_process_error($responseinfo);
            }
        }
    }
}
