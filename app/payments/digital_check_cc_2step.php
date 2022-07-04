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

// $Id: digital_check_cc_2step.php by tommy from cs-cart.jp 2015
// ペイデザイン（クレジットカード[二段階方式]決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

	// ペイデザイン側で決済処理を実行した場合
	if ($mode == 'process') {

		// 正常終了した場合
		if( fn_check_payment_script('digital_check_cc_2step.php', $_REQUEST['order_id']) ){

			// ユーザーID決済で利用するデータを保存
			if( !empty($_REQUEST['FUKA']) ){
				$digital_check_user_id = db_get_field("SELECT user_id FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);

				if( !empty($digital_check_user_id) && ($digital_check_user_id == $_REQUEST['FUKA']) ){
					// ユーザーID決済に関する情報をセット
					fn_dgtlchck_register_cc_info($digital_check_user_id);
				}
			}

			$pp_response = array();
			$pp_response['order_status'] = 'P';
			fn_finish_payment($_REQUEST['order_id'], $pp_response);
			fn_order_placement_routines('route', $_REQUEST['order_id']);

		// エラーが発生した場合
		}else{
			$pp_response["order_status"] = 'F';
			if (fn_check_payment_script('digital_check_cc_2step.php', $_REQUEST['order_id'])) {
				fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
				fn_order_placement_routines('route', $_REQUEST['order_id']);
			}
		}

	// 決済処理をキャンセルした場合
	} elseif ($mode == 'cancelled') {
		$pp_response["order_status"] = 'F';
		if (fn_check_payment_script('digital_check_cc_2step.php', $_REQUEST['order_id'])) {
			$err_msg = fn_dgtlchck_encode_err_msg($_REQUEST['ERROR']);
			if( $err_msg ){
				fn_set_notification('E', __('jp_digital_check_cc_error'), $err_msg);
			}
			fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
			fn_order_placement_routines('route', $_REQUEST['order_id']);
		}
	}

}else{

	// ショップフロントもしくは注文の編集でペイデザインに接続して決済手続きを再実行する場合
	if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){
		//ペイデザインに送信するパラメータをセット
		$params = array();
		$params = fn_dgtlchck_get_params('cc_2step_auth', $order_id, $order_info, $processor_data);

		// オーソリ依頼
		$return_val = fn_dgtlchck_send_request('cc_2step_auth', $params);
		$response = $return_val['response'];
		$request = $return_val['request'];

		// リクエスト送信が正常終了した場合
		if (!PEAR::isError($response)) {

			// 応答内容の解析  
			$res_content = $request->getResponseBody();

			// ペイデザインから受信した請求情報を配列に格納
			$digital_check_results = fn_dgtlchck_get_result_array($res_content);

			// DBに保管する支払い情報を生成
			fn_dgtlchck_format_payment_info('cc', $order_id, $order_info['payment_info'], $digital_check_results);

			// 決済申込でエラーが発生している場合
			if( $digital_check_results[0] != 'OK' ){
				// 注文処理ページへリダイレクト
				$err_msg = fn_dgtlchck_encode_err_msg($digital_check_results[2]);
				if( $err_msg ){
					fn_set_notification('E', __('jp_digital_check_cc_error'), __('jp_digital_check_cc_failed') . '<br />' . $err_msg);
				}
				$return_url = fn_lcjp_get_error_return_url();
				fn_redirect($return_url, true);

			// 決済申込が正常に完了した場合
			}else{
				$params = fn_dgtlchck_get_params_cc_2step($order_id, $order_info, $processor_data, $digital_check_results);

				// 接続先URL
				$connection_url = PAYDESIGN_URL_CC_2STEP;

echo <<<EOT
<html>
<body onLoad="document.charset='Shift_JIS'; document.process.submit();">
<form action="{$connection_url}" method="POST" name="process" Accept-charset="Shift_JIS">
EOT;

foreach($params as $key => $val){
	echo '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
}

$msg = __('text_cc_processor_connection');
$msg = str_replace('[processor]', __('jp_digital_check_company_name'), $msg);
echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
			}
		// リクエスト送信が異常終了した場合
		}else{
			// 注文処理ページへリダイレクト
			fn_set_notification('E', __('jp_digital_check_cc_error'), __('jp_digital_check_cc_invalid'));
			$return_url = fn_lcjp_get_error_return_url();
			fn_redirect($return_url, true);
		}
	}
}
exit;
