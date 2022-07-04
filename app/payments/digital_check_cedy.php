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

// $Id: digital_check_cedy.php by tommy from cs-cart.jp 2016
// ペイデザイン（CyberEdy決済）

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

	// ペイデザイン側で決済処理を実行した場合
	if ($mode == 'process') {

		// 正常終了した場合
		if( fn_check_payment_script('digital_check_cedy.php', $_REQUEST['order_id']) ){
			$pp_response = array();
			$pp_response['order_status'] = 'P';
			fn_finish_payment($_REQUEST['order_id'], $pp_response);
			fn_order_placement_routines('route', $_REQUEST['order_id']);

		// エラーが発生した場合
		}else{
			$pp_response["order_status"] = 'F';
			if (fn_check_payment_script('digital_check_cedy.php', $_REQUEST['order_id'])) {
				fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
				fn_order_placement_routines('route', $_REQUEST['order_id']);
			}
		}

	// 決済処理をキャンセルした場合
	} elseif ($mode == 'cancelled') {
		$pp_response["order_status"] = 'F';
		if (fn_check_payment_script('digital_check_cedy.php', $_REQUEST['order_id'])) {
			$err_msg = fn_dgtlchck_encode_err_msg($_REQUEST['ERROR']);
			if( $err_msg ){
				fn_set_notification('E', __('error'), $err_msg);
			}
			fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
			fn_order_placement_routines('route', $_REQUEST['order_id']);
		}
	}

}else{

	// ペイデザインに送信するパラメータを取得
	$params = fn_dgtlchck_get_params('cedy', $order_id, $order_info, $processor_data);

	// 接続先URL
	$connection_url = PAYDESIGN_URL_CEDY;

	// この処理を入れないとペイデザイン側ページに遷移後に処理を中断した場合に再度同じ注文IDで決済が行われる
	// この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
	unset(Tygh::$app['session']['cart']['processed_order_id']);

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
exit;
