<?php

use Tygh\Registry;
use Tygh\SbpsLink;
use Tygh\SbpsRpCareer;
use Tygh\SbpsRpCredit;
use Tygh\SbpsRpWallet;


if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) {
    $order_id = $_REQUEST['order_id'];

    // SBPS側で申請処理を実行した場合
    if ($mode === 'process') {
        // SBPSの設定情報を取得
        $order_info = fn_get_order_info($order_id);
        $processor_data = fn_get_payment_method_data($order_info['payment_id']);

        if (($_REQUEST['merchant_id'] == $processor_data['processor_params']['merchant_id']) && ($_REQUEST['amount'] == round($order_info['total'])) && $_REQUEST['res_result'] == 'OK' && empty($_REQUEST['res_err_code'])) {
            // 正常終了した場合
            if (fn_check_payment_script('ap_sbps_rp_link.php', $order_id)) {
                fn_order_placement_routines('route', $order_id, false);
            }
        } else {
            // エラーが発生した場合もしくはショップIDが不正な場合
            if (fn_check_payment_script('ap_sbps_rp_link.php', $order_id)) {
                fn_finish_payment($order_id, ['order_status' => 'F']);
                fn_order_placement_routines('route', $order_id);
            }
        }
    } elseif ($mode === 'cancelled' || $mode === 'error') {
        // 申請処理をキャンセルした場合
        if (fn_check_payment_script('ap_sbps_rp_link.php', $order_id)) {
            fn_finish_payment($order_id, ['order_status' => 'F']);
            fn_order_placement_routines('route', $order_id);
        }
    }
} elseif ($mode === 'place_order' && AREA === 'A' && Registry::get('runtime.action') != 'save') {
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
} elseif ($mode === 'cron_rp_order') {
    $sbps_obj = null;

    // 使用するクラスを判定
    $process_info = fn_ap_sbps_get_process_info($order_id);
    $process = $process_info['process'];

    switch ($process) {
        case 'rp_career':
            $sbps_obj = new SbpsRpCareer($order_id, $processor_data['processor_params']);
            break;
        case 'rp_credit':
            $sbps_obj = new SbpsRpCredit($order_id, $processor_data['processor_params']);
            break;
        case 'rp_wallet':
            $sbps_obj = new SbpsRpWallet($order_id, $processor_data['processor_params']);
            break;
        default:
    }

    if (!empty($sbps_obj)) {
        $master_order_id = !empty($process_info['master_order_id']) ? $process_info['master_order_id'] : $order_id;
        $master_tracking_id = fn_ap_sbps_get_master_tracking_id($master_order_id);

        // 購入要求
        $pay_method = fn_ap_sbps_get_pay_method($order_id, $process_info['process']);
        $response = $sbps_obj->purchase_request($master_tracking_id, $order_info, $pay_method);
        if (!empty($sbps_obj->errors)) {
            // 配送日を今日から定期購入間隔分の日時に設定
            db_query('UPDATE ?:orders SET ?u WHERE order_id = ?i', ['rp_shipping_at' => strtotime("+{$order_info['rp_interval']} day")], $order_id);

            // 失敗に変更
            fn_change_order_status($order_id, 'F');
        } else {
            // 処理情報保存
            fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $response['res_tracking_id']]);

            // 処理・決済・注文情報保存
            $payment_status = $pay_method === 'rp_docomo' ? SBPS_PAYMENT_STATUS_SALES_CONFIRM : SBPS_PAYMENT_STATUS_AUTH_OK;
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], $process);
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, $process), $process);
            fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

            // 支払確認済みに変更
            fn_change_order_status($order_id, 'P');
        }
    }
} else {
    // 初期化
    $sbps = new SbpsLink($order_id, $processor_data['processor_params']);

    // 購入リクエストデータ作成
    $attr = [
        'pay_type' => '2',
        'auto_charge_type' => '0',
        'div_settele' => '0',
        'success_url' => html_entity_decode(fn_url("payment_notification.process&payment=ap_sbps_rp_link&order_id={$order_id}", AREA, 'current'), ENT_QUOTES, 'UTF-8'),
        'cancel_url' => html_entity_decode(fn_url("payment_notification.cancelled&payment=ap_sbps_rp_link&order_id={$order_id}", AREA, 'current'), ENT_QUOTES, 'UTF-8'),
        'error_url' => html_entity_decode(fn_url("payment_notification.error&payment=ap_sbps_rp_link&order_id={$order_id}", AREA, 'current'), ENT_QUOTES, 'UTF-8'),
        'pagecon_url' => html_entity_decode(fn_lcjp_get_return_url('/jp_extras/ap_sbps/rp_result.php'), ENT_QUOTES, 'UTF-8'),
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
