<?php

use Tygh\Registry;
use Tygh\SbpsApplePay;

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
    $sbps = new SbpsApplePay($order_id, $processor_data['processor_params']);

    // 金額変更チェック
    $order_data = fn_ap_sbps_get_order_data($order_id);
    if (empty($order_data) || ((int)$order_info['total'] == round($order_data['total']))) {
        fn_set_notification('W', __('warning'), __('sbps_warning_price_no_change'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    $payment_info = fn_ap_sbps_get_payment_info($order_id, 'applepay');
    $exec_mode = $payment_info['payment_status'] !== SBPS_PAYMENT_STATUS_AUTH_OK && (round($order_data['total']) > (int)$order_info['total']) ? 'multiple_refund' : 're_auth';

    // 実行チェック
    if (!$sbps->valid_mode_exec($order_id, $mode, 'applepay')) {
        // 注文情報ロールバック
        fn_ap_sbps_rollback_order($order_id);

        fn_set_notification('W', __('warning'), __('sbps_warning_payment_not_permit'));
        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    $tracking_id = fn_ap_sbps_get_tracking_id($order_id);

    if ($exec_mode === 'multiple_refund') {
        // 複数回返金要求
        $add_data = [
            'pay_option_manage' => [
                'amount' => round($order_data['total']) - (int)$order_info['total'],
            ]
        ];

        $sbps->cancel_request($tracking_id, $add_data, 'refund');
        if (!empty($sbps->errors)) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('E', __('error'), __('sbps_error_refund_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 返金確定要求
        $this->refund_confirm_request($tracking_id);
        if (!empty($sbps->errors)) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('E', __('error'), __('sbps_error_refund_confirm_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }
    } else {
        // 再与信要求
        $re_auth_response = $sbps->re_auth_request($tracking_id, $order_info);
        if (!empty($sbps->errors)) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('E', __('error'), __('sbps_error_re_auth_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 処理情報保存
        fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $re_auth_response['res_tracking_id'], 'process' => 'applepay']);

        // 前処理を取消・返金
        $add_data = [];
        $cancel_mode = 'cancel';

        if (in_array($payment_info['payment_status'], [SBPS_PAYMENT_STATUS_SALES_CONFIRM, SBPS_PAYMENT_STATUS_PARTIAL_REFUNDED])) {
            $cancel_mode = 'refund';

            if ($payment_info['payment_status'] === SBPS_PAYMENT_STATUS_PARTIAL_REFUNDED) {
                $add_data = [
                    'pay_option_manage' => [
                        'amount' => round($order_data['total'])
                    ]
                ];
            }
        }

        $sbps->cancel_request($tracking_id, $add_data, $cancel_mode);
        if (!empty($sbps->errors)) {
            fn_set_notification('W', __('warning'), __('sbps_error_pre_process_cancel_failed'));
            $sbps->errors = [];
        } elseif ($cancel_mode === 'refund') {
            // 返金確定要求
            $this->refund_confirm_request($tracking_id);
            if (!empty($sbps->errors)) {
                fn_set_notification('W', __('warning'), __('sbps_error_pre_process_cancel_confirm_failed'));
                $sbps->errors = [];
            }
        }
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
    fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'applepay');
    fn_ap_sbps_set_order_data($order_id, $info, 'applepay');
    fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

    // 終了処理
    fn_order_placement_routines('route', $order_id, $force_notification);
}