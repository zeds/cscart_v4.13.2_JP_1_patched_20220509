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

// $Id: zeus.php by tommy from cs-cart.jp 2016
// ゼウスカード決済

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {
	// ゼウス側で決済処理を実行した場合
	if ($mode == 'process') {
		if (fn_check_payment_script('zeus.php', $_REQUEST['order_id'])) {
            // 注文ステータスがすでに変更されている場合、メールによる通知は実施しない
            $zeus_order_info = fn_get_order_info($_REQUEST['order_id']);
            $force_notification = array();
            if($zeus_order_info['status'] != 'N'){
                $force_notification['C'] = false;
                $force_notification['A'] = false;
            }

			$pp_response = array();
			$pp_response['order_status'] = 'P';
			fn_finish_payment($_REQUEST['order_id'], $pp_response, $force_notification);
			fn_order_placement_routines('route', $_REQUEST['order_id'], $force_notification);
		}
	}

} else {

	// 接続先URL
	$connection_url = 'https://linkpt.cardservice.co.jp/cgi-bin/credit/order.cgi';

	// 注文合計金額は四捨五入して整数型にする
	$money = round($order_info['total']);

	// e-mail
	$email = $order_info['email'];

	// 注文IDと顧客ID
	$sendid = $order_id . ZEUS_SEPARATOR . (int)$order_info['user_id'];

	$sendpoint = '';

	// この処理を入れないとゼウスで決済後表示されるリンクでCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
	// この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
	unset(Tygh::$app['session']['cart']['processed_order_id']);

echo <<<EOT
<html>
<body onLoad="document.process.submit();">
<form action="{$connection_url}" method="POST" name="process">
	<input type="hidden" name="clientip" value="{$processor_data['processor_params']['clientip']}" />
	<input type="hidden" name="money" value="{$money}" />
	<input type="hidden" name="email" value="{$email}" />
	<input type="hidden" name="sendid" value="{$sendid}" />
	<input type="hidden" name="sendpoint" value="{$sendpoint}" />
EOT;


$msg = __('text_cc_processor_connection');
$msg = str_replace('[processor]', __('jp_zeus_company_name'), $msg);
echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
}
exit;
