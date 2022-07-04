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

use Tygh\Registry;

// $Id: credix_cc.php by tommy from cs-cart.jp 2016
// CREDIX決済

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

	// CREDIX側で決済処理を実行した場合
    if ($mode == 'process') {
        if (fn_check_payment_script('credix_cc.php', $_REQUEST['order_id'])) {
            $pp_response = array();
            $force_notification = array();

            // 割り当てる注文ステータスは「P（処理中）」
            $status_to = 'P';

            // 現在の注文ステータスを取得
            $current_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);

            // 注文ステータスがすでに変更されている場合、メールによる通知は実施しない
            if($current_status == $status_to){
                $force_notification['C'] = false;
                $force_notification['A'] = false;
            }

            $pp_response['order_status'] = $status_to;
            fn_finish_payment($_REQUEST['order_id'], $pp_response, $force_notification);
            fn_order_placement_routines('route', $_REQUEST['order_id'], $force_notification);
        }

    // 決済処理でエラーが発生した、または決済がキャンセルされた場合
    } elseif ($mode == 'cancelled') {
        $pp_response["order_status"] = 'F';
        if (fn_check_payment_script('credix_cc.php', $_REQUEST['order_id'])) {
            fn_finish_payment($_REQUEST['order_id'], $pp_response);
            fn_order_placement_routines('route', $_REQUEST['order_id']);
        }
    }

} else {

	// 接続先URL
	$connection_url = 'https://secure.credix-web.co.jp/cgi-bin/credit/order.cgi';

    $params = array();

    $params['clientip'] = Registry::get('addons.credix.ip');

	// 注文合計金額は四捨五入して整数型にする
    $params['money'] = round($order_info['total']);

    // 電話番号
    if( !empty($order_info['phone']) ){
        $params['telno'] = substr(preg_replace("/-/", "", mb_convert_kana($order_info['phone'],"a")), 0, 11);
    }

	// e-mail
    $params['email'] = $order_info['email'];

    // Quick Chargeを利用する場合
    if( !empty($order_info['user_id']) &&  $processor_data['processor_params']['quick_charge'] == 'true' && $order_info['payment_info']['jp_credix_use_quick_charge'] == 'Y'){
        // sendidをセット
        $params['sendid'] = fn_crdx_generate_sendid();
    }

    // 注文IDと顧客ID
    $params['sendpoint'] = $order_id . CREDIX_SEPARATOR . (int)$order_info['user_id'];

    // CREDIXの決済完了ページに表示するリンクテキストを取得
    if( fn_allowed_for('ULTIMATE') ){
        $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
    } else {
        $company_id = Registry::get('runtime.company_id');
    }
    $str_back_to_store = __('jp_credix_back_to_store', array('[store_name]' => fn_get_company_name($company_id)));

    // ｢完了ページ｣に表示するリンクのリンク先URL
    $params['success_url'] = fn_url("payment_notification.process&payment=credix_cc&order_id=$order_id", AREA, 'current');

    // ｢完了ページ｣に表示するリンクを表示する際のテキスト
    $params['success_str'] = $str_back_to_store;

    // ｢失敗ページ｣に表示するリンクのリンク先URL
    $params['failure_url'] = fn_url("payment_notification.cancelled&payment=credix_cc&order_id=$order_id", AREA, 'current');

    // ｢失敗ページ｣に表示するリンクを表示する際のテキスト
    $params['failure_str'] = $str_back_to_store;

	// この処理を入れないとCREDIXで決済後表示されるリンクでCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
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
$msg = str_replace('[processor]', __('jp_credix_service_name'), $msg);
echo <<<EOT
</form>
<div align=center>{$msg}</div>
</body>
</html>
EOT;
}
exit;
