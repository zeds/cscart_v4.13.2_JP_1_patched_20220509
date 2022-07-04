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

// $Id: smbc_cvs.php by tommy from cs-cart.jp 2016
// SMBCファイナンスサービス（コンビニ受付番号決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でSMBCに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

	// SMBCファイナンスサービスに送信するパラメータをセット
	$params = array();
	$params = fn_smbcks_get_params('cvs', $order_id, $order_info, $processor_data);

	// コンビニ収納依頼
	$return_val = fn_smbcks_send_request($params, $processor_data);
	$response = $return_val['response'];
	$request = $return_val['request'];

	// コンビニ収納依頼データ送信が正常終了した場合
	if (!PEAR::isError($response)) {

		// 応答内容の解析  
		$res_code = $request->getResponseCode();
		$res_content = $request->getResponseBody();

		// SMBCファイナンスサービスから受信した請求情報を配列に格納
		$smbc_results = fn_smbcks_get_result_array($res_content);

		// エラーが発生している場合
		if( strcmp($smbc_results['rescd'], '000000') !== 0 ){

			// 注文処理ページへリダイレクト
			fn_set_notification('E', __('jp_smbc_cvs_error'), __('jp_smbc_cvs_failed') . '<br />' . $smbc_results['rescd'] . ' : ' . $smbc_results['res']);
			$return_url = fn_lcjp_get_error_return_url();
			fn_redirect($return_url, true);

		// コンビニ収納依頼が正常終了した場合
		}else{
			if (fn_check_payment_script('smbc_cvs.php', $order_id)) {
				// DBに保管する支払い情報を生成
				fn_smbcks_format_payment_info('cvs', $order_id, $order_info['payment_info'], $smbc_results, true);

				// 注文処理ページへリダイレクト
				$pp_response = array();
				$pp_response['order_status'] = 'O';
				fn_finish_payment($order_id, $pp_response);
				fn_order_placement_routines('route', $order_id);
			}
		}

	// コンビニ収納依頼データ送信が異常終了した場合
	}else{
		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('jp_smbc_cvs_error'), __('jp_smbc_cvs_failed'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	}
}
