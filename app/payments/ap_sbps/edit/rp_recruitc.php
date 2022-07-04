<?php

use Tygh\Registry;
use Tygh\SbpsRpCredit;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode == 'place_order' && AREA == 'A' && Registry::get('runtime.action') != 'save') {
    // 支払い方法チェック
    if (!fn_check_payment_script('ap_sbps_rp_link.php', $order_id)) {
        fn_set_notification('W', __('warning'), __('sbps_warning_different_payment'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 初期化
    $sbps = new SbpsRpCredit($order_id, $processor_data['processor_params']);

    // 金額変更チェック
    $order_data = fn_ap_sbps_get_order_data($order_id);
    if (empty($order_data) || ((int)$order_info['total'] == round($order_data['total']))) {
        fn_set_notification('W', __('warning'), __('sbps_warning_price_no_change'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 実行チェック
    if ((round($order_data['total']) < (int)$order_info['total']) || !$sbps->valid_rp_mode_exec($order_id, 'multiple_refund', 'rp_credit')) {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('W', __('warning'), __('sbps_warning_payment_not_permit'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
    $refund_rowno = fn_ap_sbps_get_refund_rowno($order_id, 'rp_credit');

    // 複数回返金
    $add_data = [
        'pay_option_manage' => [
            'amount' => round($order_data['total']) - (int)$order_info['total'],
            'refund_rowno' => $refund_rowno + 1
        ]
    ];

    $sbps->cancel_request($tracking_id, 'rp_recruitc', $add_data, 'multiple_refund');
    if (!empty($sbps->errors)) {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('E', __('error'), __('sbps_error_refund_failed'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    // 枝番のインクリメント
    fn_ap_sbps_increment_refund_rowno($order_id, 'rp_credit');

    // 注文情報保存
    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rp_credit'), 'rp_credit');
    fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

    // 終了処理
    fn_order_placement_routines('route', $order_id, $force_notification);
}