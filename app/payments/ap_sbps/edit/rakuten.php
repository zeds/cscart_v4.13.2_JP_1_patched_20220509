<?php

use Tygh\Registry;
use Tygh\SbpsWallet;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode == 'place_order' && AREA == 'A' && Registry::get('runtime.action') != 'save') {
    // 支払い方法チェック
    if (!fn_check_payment_script('ap_sbps_link.php', $order_id)) {
        fn_set_notification('W', __('warning'), __('sbps_warning_different_payment'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 初期化
    $sbps = new SbpsWallet($order_id, $processor_data['processor_params']);

    // 金額変更チェック
    $order_data = fn_ap_sbps_get_order_data($order_id);
    if (empty($order_data) || ((int)$order_info['total'] == round($order_data['total']))) {
        fn_set_notification('W', __('warning'), __('sbps_warning_price_no_change'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 実行チェック
    if (!$sbps->valid_mode_exec($order_id, 'amount_change', 'wallet')) {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('W', __('warning'), __('sbps_warning_payment_not_permit'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    $tracking_id = fn_ap_sbps_get_tracking_id($order_id);

    // 金額変更
    $sbps->amount_change_request($tracking_id, 'rakuten', (int)$order_info['total']);
    if (!empty($sbps->errors)) {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('E', __('error'), __('sbps_error_change_amount_failed'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 処理・注文情報保存
    fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => SBPS_PAYMENT_STATUS_AMOUNT_CHANGE_ACCEPTED], 'wallet');
    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'wallet'), 'wallet');
    fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

    // 終了処理
    fn_order_placement_routines('route', $order_id, $force_notification);
}