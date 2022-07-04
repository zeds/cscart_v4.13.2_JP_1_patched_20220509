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

// $Id: oricopp_sw.php by tommy from cs-cart.jp 2016
// OricoPayment Plus（SimpleWeb）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

    // OricoPayment Plus側で決済処理を実行した場合
    if ($mode == 'process') {
        if (fn_check_payment_script('oricopp_sw.php', $_REQUEST['order_id'])) {
            $pp_response = array();
            $force_notification = array();

            // 現在の注文ステータスを取得
            $current_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);

            // 結果コードから割り当てる注文ステータスを決定
            if( $current_status == 'P' ){
                $status_to = 'P';
            }else{
                $status_to = fn_oppsw_get_status_to($_REQUEST['vrc']);
            }

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
        if (fn_check_payment_script('oricopp_sw.php', $_REQUEST['order_id'])) {
            fn_finish_payment($_REQUEST['order_id'], $pp_response);
            fn_order_placement_routines('route', $_REQUEST['order_id']);
        }
    }

} else {

    // OricoPayment Plusに送信するパラメータをセット
    $params = fn_oppsw_get_params($processor_data['processor_params']['type'], $order_info);
    list($merchant_key, $browser_key, $err_msg) = fn_oppsw_send_request($params);

    if( !empty($err_msg) ){
        fn_set_notification('E', __('jp_oppsw_error'), $err_msg);
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }elseif( !empty($merchant_key) && !empty($browser_key) )
        $connection_url = OPPSW_URL_PAYMENT;

        // この処理を入れないとOricoPayment Plus側で決済後表示されるリンクなどCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
        // この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
        unset(Tygh::$app['session']['cart']['processed_order_id']);

    echo <<<EOT
<html>
<body onLoad="document.process.submit();">
<form action="{$connection_url}" method="POST" name="process">
EOT;
    echo '<input type="hidden" name="MERCHANT_ID" value="' . $params['MERCHANT_ID'] . '" />';
    echo '<input type="hidden" name="ORDER_ID" value="' . $params['ORDER_ID'] . '" />';
    echo '<input type="hidden" name="BROWSER_ENCRYPTION_KEY" value="' . $browser_key . '" />';
    $msg = __('text_cc_processor_connection');
    $msg = str_replace('[processor]', __('jp_oppsw_service_name'), $msg);
    echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
}

exit;
