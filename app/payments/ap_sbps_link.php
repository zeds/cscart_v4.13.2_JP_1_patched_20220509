<?php

use Tygh\Registry;
use Tygh\SbpsLink;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) {
    $order_id = $_REQUEST['order_id'];

    // SBPS側で決済処理を実行した場合
    if ($mode == 'process') {
        // SBPSの設定情報を取得
        $order_info = fn_get_order_info($order_id);
        $processor_data = fn_get_payment_method_data($order_info['payment_id']);

        if (($_REQUEST['merchant_id'] == $processor_data['processor_params']['merchant_id']) && ($_REQUEST['amount'] == round($order_info['total'])) && $_REQUEST['res_result'] == 'OK' && empty($_REQUEST['res_err_code'])) {
            // 正常終了した場合
            if (fn_check_payment_script('ap_sbps_link.php', $order_id)) {
                fn_order_placement_routines('route', $order_id, false);
            }

        } else {
            // エラーが発生した場合もしくはショップIDや決済金額が不正な場合
            if (fn_check_payment_script('ap_sbps_link.php', $order_id)) {
                fn_finish_payment($order_id, ['order_status' => 'F']);
                fn_order_placement_routines('route', $order_id);
            }
        }
    } elseif ($mode === 'cancelled' || $mode === 'error') {
        // 決済処理をキャンセルした場合
        if (fn_check_payment_script('ap_sbps_link.php', $order_id)) {
            fn_finish_payment($order_id, ['order_status' => 'F']);
            fn_order_placement_routines('route', $order_id);
        }
    }
} elseif ($mode == 'place_order' && AREA === 'A' && Registry::get('runtime.action') != 'save') {
    $pay_method = fn_ap_sbps_get_pay_method($order_id);
    $file_path = Registry::get('config.dir.payments') . "ap_sbps/edit/{$pay_method}.php";

    if (!empty($pay_method) && file_exists($file_path)) {
        include($file_path);
    } else {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('W', __('warning'), __('sbps_warning_payment_not_permit'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }
} else {
    // 初期化
    $sbps = new SbpsLink($order_id, $processor_data['processor_params']);

    // 購入リクエストデータ作成
    $attr = [
        'pay_type' => '0',
        'success_url' => html_entity_decode(fn_url("payment_notification.process&payment=ap_sbps_link&order_id={$order_id}", AREA, 'current'), ENT_QUOTES, 'UTF-8'),
        'cancel_url' => html_entity_decode(fn_url("payment_notification.cancelled&payment=ap_sbps_link&order_id={$order_id}", AREA, 'current'), ENT_QUOTES, 'UTF-8'),
        'error_url' => html_entity_decode(fn_url("payment_notification.error&payment=ap_sbps_link&order_id={$order_id}", AREA, 'current'), ENT_QUOTES, 'UTF-8'),
        'pagecon_url' => html_entity_decode(fn_lcjp_get_return_url('/jp_extras/ap_sbps/result.php'), ENT_QUOTES, 'UTF-8'),
    ];
    $purchase_request_data = $sbps->create_purchase_request_data($order_info, $attr);

    // この処理を入れないとSBPSで決済後表示されるリンクでCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
    // この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
    unset(Tygh::$app['session']['cart']['processed_order_id']);

    foreach ($_COOKIE as $key => $value){
        if (substr($key, 0, 13) == 'sid_customer_'){
            $domain = '.' . $_SERVER['HTTP_HOST'];
            header('Set-Cookie: ' . $key. '=' . $value . '; Domain=' . $domain . '; HttpOnly; SameSite=None; Secure');
            break;
        }
    }

    // 購入リクエスト
    echo <<<EOT
<html>
    <body onLoad="document.charset='Shift_JIS'; document.process.submit();">
    <form action="{$sbps->get_connection_url()}" method="POST" name="process" Accept-charset="Shift_JIS">
EOT;

    foreach ($purchase_request_data as $key => $val) {
        echo '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
    }

    $msg = __('text_cc_processor_connection');
    $msg = str_replace('[processor]', __('jp_sbps_company_name'), $msg);
    echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;

    exit;
}
