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

// $Id: telecomcredit.php by tommy from cs-cart.jp 2016
// テレコムクレジット決済

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

	// テレコムクレジット側で決済処理を実行した場合
	if ($mode == 'process') {
		if (fn_check_payment_script('telecomcredit.php', $_REQUEST['order_id'])) {
			$pp_response = array();
			$pp_response['order_status'] = 'P';
			fn_finish_payment($_REQUEST['order_id'], $pp_response);
			fn_order_placement_routines('route', $_REQUEST['order_id']);
		}
	}

} else {

	// 接続先URL
	$connection_url = 'https://secure.telecomcredit.co.jp/inetcredit/secure/order.pl';

	// 戻りURL（決済処理実行時）
    $redirect_url = fn_url("payment_notification.process&payment=telecomcredit&order_id=$order_id", AREA, 'current');

	// 注文合計金額は四捨五入して整数型にする
	$money = round($order_info['total']);

	// 顧客ID
	$sendid = (int)$order_info['user_id'];

	// この処理を入れないとテレコムクレジットで決済後表示されるリンクでCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
	// この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
	unset(Tygh::$app['session']['cart']['processed_order_id']);

echo <<<EOT
<html>
<body onLoad="document.process.submit();">
<form action="{$connection_url}" method="POST" name="process">
	<input type="hidden" name="clientip" value="{$processor_data['processor_params']['clientip']}" />
	<input type="hidden" name="money" value="{$money}" />
	<input type="hidden" name="sendid" value="{$sendid}" />
	<input type="hidden" name="redirect_url" value="{$redirect_url}" />
EOT;


$msg = __('text_cc_processor_connection');
$msg = str_replace('[processor]', __('jp_telecomcredit_company_name'), $msg);
echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
}
exit;
