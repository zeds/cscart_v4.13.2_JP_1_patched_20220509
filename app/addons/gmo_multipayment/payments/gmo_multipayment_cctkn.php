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

// $Id: gmo_multipayment_cctkn.php by takahashi from cs-cart.jp 2017
// GMOマルチペイメントサービス（クレジットカード決済・トークン決済）

// Modified by takahashi from cs-cart.jp 2021
// Chrome 80 以降対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でGMOペイメントゲートウェイに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

	// ACSからの3D認証結果が戻された場合
	if( !empty($_REQUEST['PaRes']) && $_REQUEST['process_type'] == '3dsecure_return' ){

		// GMOペイメントゲートウェイに送信するパラメータをセット
		$params = array();
		$params['PaRes'] = $_REQUEST['PaRes'];
        $params['MD'] = $_REQUEST['MD'];

		// 支払方法に関する各種設定値が取得できていない場合
		if( empty($processor_data) ){
			// 支払方法に関するデータを取得
			$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = ?s AND ?:payments.status = 'A'", 'gmo_multipayment_cctkn.php');
			$processor_data = fn_get_processor_data($payment_id);
		}

        // 3Dセキュアによる決済実行
        $exec_result = fn_gmomp_send_request('securetran', $params, $processor_data['processor_params']['mode']);

        // 決済実行に関するリクエスト送信が正常終了した場合
        if (!empty($exec_result)) {

            // GMOペイメントゲートウェイから受信した決済実行情報を配列に格納
            $gmomp_exec_results = fn_gmomp_get_result_array($exec_result);

            // 決済実行が正常に完了している場合
            if (!empty($gmomp_exec_results['OrderID']) && !empty($_REQUEST['order_id']) && empty($gmomp_exec_results['ErrCode']) && empty($gmomp_exec_results['ErrInfo'])) {

                // 注文情報を取得
                $order_id = (int)$_REQUEST['order_id'];
                $order_info = fn_get_order_info($order_id);

                // クレジットカード情報お預かり機能を利用する場合（金額変更時は除く）
                if( $order_info['payment_info']['register_card_info'] == 'true' && !empty($order_info['user_id']) ){
                    // クレジットカード情報を登録
                    $order_info['gmo_order_id'] = $gmomp_exec_results['OrderID'];
                    fn_gmomp_register_cc_info($order_info, $processor_data, 'Y');
                }

                // 注文IDと利用した支払方法がマッチした場合
                if (fn_check_payment_script('gmo_multipayment_cctkn.php', $order_id)) {
                    // 注文確定処理
                    $pp_response = array();
                    $pp_response['order_status'] = 'P';
                    fn_finish_payment($order_id, $pp_response);

                    // 処理日時のタイムスタンプを取得
                    if( !empty($gmomp_exec_results['TranDate']) ){
                        $process_timestamp = strtotime($gmomp_exec_results['TranDate']);
                    }else{
                        $process_timestamp = time();
                    }

                    // 請求ステータスを更新
                    fn_gmomp_update_cc_status_code($order_id, $processor_data['processor_params']['jobcd'], $process_timestamp);

                    // DBに保管する支払い情報を生成
                    fn_gmomp_format_payment_info('cc', $order_id, $order_info['payment_info'], $gmomp_exec_results);

                    // 注文処理ページへリダイレクトして注文確定
                    fn_order_placement_routines('route', $order_id);
                }
            // エラー処理
            } else {
                // エラーメッセージを表示
                fn_gmomp_set_err_msg($gmomp_exec_results['ErrCode'], $gmomp_exec_results['ErrInfo']);

                // 注文手続きページへリダイレクト
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }
        // リクエスト送信が異常終了した場合
        }else{
            // 注文処理ページへリダイレクト
            fn_set_notification('E', __('jp_gmo_multipayment_cc_error'), __('jp_gmo_multipayment_cc_invalid'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    // その他の場合
    }else{

        // 処理タイプを初期化
        $type = 'cc';

        // 注文編集の場合
        if( Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {

            // 金額変更・再オーソリ可否判定
            list($is_cc_changeable, $change_type) = fn_gmomp_cc_is_changeable($order_id, $processor_data);

            // 金額変更・再オーソリの場合
            if ($is_cc_changeable) {
                fn_gmomp_send_cc_request($order_id, $change_type);
                return true;
            }
        }

        // GMOペイメントゲートウェイに送信する取引登録用パラメータをセット
        $params = array();
        $params = fn_gmomp_get_params('entrytran', $order_id, $order_info, $processor_data);
        $gmomp_order_id = $params['OrderID'];
    }

	// 取引登録
    $entry_result = fn_gmomp_send_request('entrytran', $params, $processor_data['processor_params']['mode']);

    // 取引登録に関するリクエスト送信が正常終了した場合
	if (!empty($entry_result)) {

        // GMOペイメントゲートウェイから受信した取引登録情報を配列に格納
        $gmomp_entry_results = fn_gmomp_get_result_array($entry_result);

        // 取引登録が正常に完了している場合
        if( !empty($gmomp_entry_results['AccessID']) && !empty($gmomp_entry_results['AccessPass']) && empty($gmomp_entry_results['ErrCode']) && empty($gmomp_entry_results['ErrInfo']) ){
            // GMOペイメントゲートウェイに送信する決済実行用パラメータをセット
            $params = array();
            $params = fn_gmomp_get_params('exectran', $order_id, $order_info, $processor_data);
            $params['AccessID'] = $gmomp_entry_results['AccessID'];
            $params['AccessPass'] = $gmomp_entry_results['AccessPass'];
            $params['OrderID'] = $gmomp_order_id;

            // 請求ステータスを更新（この時点で 'AccessID' と 'AccessPass' を記録しておく必要があるため）
            fn_gmomp_update_cc_status_code($order_id, 'IN_PROCESS', '', $gmomp_entry_results['AccessID'], $gmomp_entry_results['AccessPass']);

            // 決済実行
            $exec_result = fn_gmomp_send_request('exectran', $params, $processor_data['processor_params']['mode']);

            // 決済実行に関するリクエスト送信が正常終了した場合
            if (!empty($exec_result)) {

                // GMOペイメントゲートウェイから受信した決済実行情報を配列に格納
                $gmomp_exec_results = fn_gmomp_get_result_array($exec_result);

                // 3Dセキュア認証が必要な場合
                if( $gmomp_exec_results['ACS'] == 1 && $gmomp_exec_results['ACSUrl'] && $gmomp_exec_results['PaReq'] && $gmomp_exec_results['MD'] ){



                    // 3Dセキュア認証ページにリダイレクト
                    $acs_url = $gmomp_exec_results['ACSUrl'];
                    $pareq = $gmomp_exec_results['PaReq'];
                    $term_url = fn_url("payment_notification.process&payment=gmo_multipayment_cctkn&order_id=$order_id&process_type=3dsecure_return", AREA, 'current');
                    $md = $gmomp_exec_results['MD'];

                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2021 BOF
                    // Chrome 80 以降対応
                    ///////////////////////////////////////////////
                    /** @var \Tygh\Web\Session $session */
                    $session = Tygh::$app['session'];
                    $term_url = fn_link_attach($term_url, $session->getName() . '=' . $session->getID());
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2021 EOF
                    ///////////////////////////////////////////////
                    echo <<<EOT
<html>
<body onLoad="document.process.submit();">
<form action="{$acs_url}" method="POST" name="process">
	<input type="hidden" name="PaReq" value="{$pareq}" />
	<input type="hidden" name="TermUrl" value="{$term_url}" />
	<input type="hidden" name="MD" value="{$md}" />
EOT;

                    $msg = __('jp_gmo_multipayment_cc_redirect_acs');
                    echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
                    exit;

                // 決済実行が正常に完了している場合
                }elseif( !empty($gmomp_exec_results['OrderID']) && empty($gmomp_exec_results['ErrCode']) && empty($gmomp_exec_results['ErrInfo']) ){

                    // クレジットカード情報お預かり機能を利用する場合（金額変更時は除く）
                    if( $order_info['payment_info']['register_card_info'] == 'true' && !empty($order_info['user_id']) && $type != 'cc_change' ){
                        // クレジットカード情報を登録
                        $order_info['gmo_order_id'] = $gmomp_exec_results['OrderID'];
                        fn_gmomp_register_cc_info($order_info, $processor_data,'Y');
                    }

                    // 注文IDと利用した支払方法がマッチした場合
                    if (fn_check_payment_script('gmo_multipayment_cctkn.php', $order_id)) {
                        // 注文確定処理
                        $pp_response = array();
                        $pp_response['order_status'] = 'P';
                        fn_finish_payment($order_id, $pp_response);

                        // 処理日時のタイムスタンプを取得
                        if( !empty($gmomp_exec_results['TranDate']) ){
                            $process_timestamp = strtotime($gmomp_exec_results['TranDate']);
                        }else{
                            $process_timestamp = time();
                        }

                        // 請求ステータスを更新
                        fn_gmomp_update_cc_status_code($order_id, $processor_data['processor_params']['jobcd'], $process_timestamp);

                        // DBに保管する支払い情報を生成
                        fn_gmomp_format_payment_info('cc', $order_id, $order_info['payment_info'], $gmomp_exec_results);

                        // 注文処理ページへリダイレクトして注文確定
                        fn_order_placement_routines('route', $order_id);

                    }
                // エラーが発生している場合
                }else{
                    // エラーメッセージを表示
                    fn_gmomp_set_err_msg($gmomp_exec_results['ErrCode'], $gmomp_exec_results['ErrInfo']);

                    // 注文手続きページへリダイレクト
                    $return_url = fn_lcjp_get_error_return_url();
                    fn_redirect($return_url, true);
                }
            // リクエスト送信が異常終了した場合
            }else{
                // 注文データからカード情報を削除
                $_del_data = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
                if (!empty($_del_data)) {
                    db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
                }
                // 注文処理ページへリダイレクト
                fn_set_notification('E', __('jp_gmo_multipayment_cc_error'), __('jp_gmo_multipayment_cc_invalid'));
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }
        }

	// リクエスト送信が異常終了した場合
	}else{
        // 注文データからカード情報を削除
        $_del_data = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
        if (!empty($_del_data)) {
            db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
        }
		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('jp_gmo_multipayment_cc_error'), __('jp_gmo_multipayment_cc_invalid'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	}
}
