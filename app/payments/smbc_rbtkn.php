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

// $Id: smbc_rbtkn.php by tommy from cs-cart.jp 2016
// SMBCファイナンスサービス（クレジットカード継続課金）

use Tygh\Tools\SecurityHelper;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でSMBCに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    // トークン発行でエラーが発生している場合
    if( !empty($order_info['payment_info']['token_error_detail']) ){
        // エラーメッセージを表示して処理を終了
        fn_set_notification('E', __('jp_smbc_cc_error'), SecurityHelper::sanitizeHtml($order_info['payment_info']['token_error_detail']));
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }

	// SMBCファイナンスサービスに送信するパラメータをセット
	$params = array();
	$params = fn_smbcks_get_params('rb', $order_id, $order_info, $processor_data);
	$action = 'rb';

	// オーソリ依頼
	$return_val = fn_smbcks_send_request($params, $processor_data, $action);
	$response = $return_val['response'];
	$request = $return_val['request'];

	// リクエスト送信が正常終了した場合
	if( !PEAR::isError($response) ){

		// 応答内容の解析  
		$res_content = $request->getResponseBody();

		// SMBCファイナンスサービスから受信した請求情報を配列に格納
		$smbc_results = fn_smbcks_get_result_array($res_content);

		// オーソリでエラーが発生している場合
		if( strcmp($smbc_results['rescd'], '000000') !== 0 ){
			// カード決済エラー時に、注文データに格納されていた支払情報のうち、SMBCカード決済に関するデータのみ残す
			if( !empty($order_id) ) fn_smbcks_filter_payment_info($order_id);

			// 注文処理ページへリダイレクト
			fn_set_notification('E', __('jp_smbc_rb_error'), __('jp_smbc_rb_failed') . '<br />' . $smbc_results['rescd'] . ' : ' . $smbc_results['res']);
			$return_url = fn_lcjp_get_error_return_url();
			fn_redirect($return_url, true);

		// オーソリが正常に完了した場合
		}else{
			if( fn_check_payment_script('smbc_rbtkn.php', $order_id) ){

				// DBに保管する支払い情報を生成
				fn_smbcks_format_payment_info('rb', $order_id, $order_info['payment_info'], $smbc_results);

				// 注文処理ページへリダイレクト
				$pp_response = array();
				$pp_response['order_status'] = 'P';
				fn_finish_payment($order_id, $pp_response);
				fn_order_placement_routines('route', $order_id);
			}
		}

	// リクエスト送信が異常終了した場合
	}else{
		// カード決済エラー時に、注文データに格納されていた支払情報のうち、SMBCカード決済に関するデータのみ残す
		if( !empty($order_id) ) fn_smbcks_filter_payment_info($order_id);

		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('jp_smbc_rb_error'), __('jp_smbc_rb_invalid'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	}
}
