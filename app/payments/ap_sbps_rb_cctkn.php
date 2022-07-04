<?php

use Tygh\Registry;
use Tygh\SbpsRbCredit;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// ショップフロントもしくは注文の編集でソフトバンク・ペイメント・サービスに接続して決済手続きを再実行する場合
if ($mode === 'place_order' && (AREA === 'C' || (AREA === 'A' && Registry::get('runtime.action') != 'save'))) {
    // 支払い方法チェック
    if (!fn_check_payment_script('ap_sbps_rb_cctkn.php', $order_id)) {
        if (AREA === 'C') {
            fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
        } else {
            fn_set_notification('W', __('warning'), __('sbps_warning_different_payment'));
        }

        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    if (AREA === 'A') {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('W', __('warning'), __('sbps_warning_payment_not_permit'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 初期化
    $sbps = new SbpsRbCredit($order_id, $processor_data['processor_params']);

    // 継続課金(簡易)購入要求
    $credit_response = $sbps->credit_request($order_info, $payment_info, 'rb_credit');
    if (!empty($sbps->errors)) {
        fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 確定要求
    $sbps->confirm_request($credit_response['res_tracking_id'], 'rb_credit');
    if (!empty($sbps->errors)) {
        fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 処理情報保存
    fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $credit_response['res_tracking_id'], 'process' => 'rb_credit']);
    fn_ap_sbps_set_sbps_payment_info($order_id, ['is_charge' => true, 'pay_method' => 'rb_credit'], 'rb_credit');
    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rb_credit'), 'rb_credit');
    fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

    // 終了処理
    fn_finish_payment($order_id, ['order_status' => 'P'], $force_notification);
    fn_order_placement_routines('route', $order_id, $force_notification);
}
