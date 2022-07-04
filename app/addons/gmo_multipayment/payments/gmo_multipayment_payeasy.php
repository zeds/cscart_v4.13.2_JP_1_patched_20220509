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

// $Id: gmo_multipayment_payeasy.php by tommy from cs-cart.jp 2015
// GMOマルチペイメントサービス（ペイジー決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でGMOペイメントゲートウェイに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    // GMOペイメントゲートウェイに送信する取引登録用パラメータをセット
    $params = array();
    $params = fn_gmomp_get_params('entrytranpayeasy', $order_id, $order_info, $processor_data);
    $gmomp_order_id = $params['OrderID'];

	// 取引登録
    $entry_result = fn_gmomp_send_request('entrytranpayeasy', $params, $processor_data['processor_params']['mode']);

    // 取引登録に関するリクエスト送信が正常終了した場合
	if (!empty($entry_result)) {

        // GMOペイメントゲートウェイから受信した取引登録情報を配列に格納
        $gmomp_entry_results = fn_gmomp_get_result_array($entry_result);

        // 取引登録が正常に完了している場合
        if( !empty($gmomp_entry_results['AccessID']) && !empty($gmomp_entry_results['AccessPass']) && empty($gmomp_entry_results['ErrCode']) && empty($gmomp_entry_results['ErrInfo']) ){

            // GMOペイメントゲートウェイに送信する決済実行用パラメータをセット
            $params = array();
            $params = fn_gmomp_get_params('exectranpayeasy', $order_id, $order_info, $processor_data);
            $params['AccessID'] = $gmomp_entry_results['AccessID'];
            $params['AccessPass'] = $gmomp_entry_results['AccessPass'];
            $params['OrderID'] = $gmomp_order_id;

            // 決済実行
            $exec_result = fn_gmomp_send_request('exectranpayeasy', $params, $processor_data['processor_params']['mode']);

            // 決済実行に関するリクエスト送信が正常終了した場合
            if (!empty($exec_result)) {

                // GMOペイメントゲートウェイから受信した決済実行情報を配列に格納
                $gmomp_exec_results = fn_gmomp_get_result_array($exec_result);

                // 決済実行が正常に完了している場合
                if( !empty($gmomp_exec_results['OrderID']) && empty($gmomp_exec_results['ErrCode']) && empty($gmomp_exec_results['ErrInfo']) ){

                    // 注文IDと利用した支払方法がマッチした場合
                    if (fn_check_payment_script('gmo_multipayment_payeasy.php', $order_id)) {
                        // 注文確定処理
                        $pp_response = array();
                        $pp_response['order_status'] = 'O';
                        fn_finish_payment($order_id, $pp_response);

                        // DBに保管する支払い情報を生成
                        fn_gmomp_format_payment_info('payeasy', $order_id, $order_info['payment_info'], $gmomp_exec_results);

                        // コンビニ決済に関するメッセージを表示
                        fn_set_notification('I', __('jp_gmo_multipayment_payeasy_notification_title'),  __('jp_gmo_multipayment_payeasy_notification_message', array('[email]' => $params['MailAddress'])));

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
            }
        }

	// リクエスト送信が異常終了した場合
	}else{
		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('jp_gmo_multipayment_payeasy_error'), __('jp_gmo_multipayment_payeasy_failed'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	}
}
