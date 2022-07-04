<?php

use Tygh\Registry;
use Tygh\SbpsPaypay;

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
    $sbps = new SbpsPaypay($order_id, $processor_data['processor_params']);

    // 金額変更チェック
    $order_data = fn_ap_sbps_get_order_data($order_id);
    if (empty($order_data) || ((int)$order_info['total'] == round($order_data['total']))) {
        fn_set_notification('W', __('warning'), __('sbps_warning_price_no_change'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 実行チェック
    if ((round($order_data['total']) < (int)$order_info['total']) || !$sbps->valid_mode_exec($order_id, 'partial_refund', 'paypay')) {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('W', __('warning'), __('sbps_warning_payment_not_permit'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    $tracking_id = fn_ap_sbps_get_tracking_id($order_id);

    // 部分返金
    $sbps->cancel_request($tracking_id, ['pay_option_manage' => ['amount' => round($order_data['total']) - (int)$order_info['total']]]);
    if (!empty($sbps->errors)) {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('E', __('error'), __('sbps_error_change_amount_failed'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 決済結果参照要求
    $response = $sbps->reference_request($tracking_id);
    if (!empty($sbps->errors) || $response['res_status'] !== '0') {
        fn_set_notification('W', __('warning'), __('sbps_warning_reference_failed'));
        $info = [];
    } else {
        $info = $sbps->format_reference($response['res_pay_method_info']);
    }

    // 処理・注文情報保存
    fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'paypay');
    fn_ap_sbps_set_order_data($order_id, $info, 'paypay');
    fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

    // 終了処理
    fn_order_placement_routines('route', $order_id, $force_notification);
}