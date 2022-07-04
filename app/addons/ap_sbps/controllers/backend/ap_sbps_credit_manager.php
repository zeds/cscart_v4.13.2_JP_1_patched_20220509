<?php

use Tygh\Registry;
use Tygh\SbpsCredit;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_REQUEST['order_ids'])) {
        $valid_process = false;

        foreach ($_REQUEST['order_ids'] as $order_id) {
            $sbps = new SbpsCredit();

            if ($sbps->valid_mode_exec($order_id, $mode, 'credit')) {
                if (!$valid_process) {
                    $valid_process = true;
                }

                // 処理情報取得
                $processor_data = fn_ap_sbps_get_processor_data($order_id);
                if ($processor_data['processor_id'] === '9242') {
                    $user_id = fn_ap_sbps_get_order_user_id($order_id);
                    $processor_data = fn_ap_sbps_get_quickpay_payment_method_data($user_id);
                }

                // データ設定
                $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

                // 処理実行
                $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
                $payment_info = fn_ap_sbps_get_payment_info($order_id, 'credit');
                $sbps->exec_mode_request($mode, $tracking_id, $payment_info);
                if (!empty($sbps->errors)) {
                    fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
                    $sbps->errors = [];
                } elseif (in_array($payment_info['pay_method'], ['unionpay', 'recruitc'], true)) {
                    // 複数回返金の場合は枝番をインクリメント
                    if ($mode === 'cancel' && !empty($payment_info['refund_rowno'])) {
                        fn_ap_sbps_increment_refund_rowno($order_id, 'credit');
                    }

                    $payment_status = $sbps->get_exec_mode_payment_status($mode, $payment_info['payment_status']);
                    fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'credit');
                    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'credit'), 'credit');
                }

                // 決済結果参照要求
                if (in_array($payment_info['pay_method'], ['credit', 'credit3d'], true)) {
                    $response = $sbps->reference_request($tracking_id, $payment_info['pay_method']);
                    if (!empty($sbps->errors) || $response['res_status'] !== '0') {
                        fn_set_notification('W', __('warning'), __('sbps_warning_reference_failed'));
                    } else {
                        $info = $sbps->format_cc_reference($response['res_pay_method_info']);
                        fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'credit');
                        fn_ap_sbps_set_order_data($order_id, $info, 'credit');
                    }
                }
            }
        }

        if (!$valid_process) {
            fn_set_notification('W', __('warning'), __('sbps_error_exec_data_not_exists'));
        }
    }

    return [CONTROLLER_STATUS_OK];
}

$params = $_REQUEST;

if ($mode === 'manage' || empty($_REQUEST['order_id'])) {
    if (!empty($params['view_type']) && in_array($params['view_type'], ['A', 'R'], true)) {
        Registry::get('view')->assign('view_type', $params['view_type']);
    } else {
        fn_redirect(fn_url('ap_sbps_credit_manager.manage?view_type=A'), true);
    }

    if (!empty($params['status']) && $params['status'] === STATUS_INCOMPLETED_ORDER) {
        if (fn_allowed_for('MULTIVENDOR')) {
            Registry::get('view')->assign('incompleted_view', true);
        } else {
            unset($params['status']);
        }
    }

    $params['check_for_suppliers'] = true;
    $params['company_name'] = true;
    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

    Registry::get('view')->assign('orders', $orders);
    Registry::get('view')->assign('search', $search);
} else {
    $sbps = new SbpsCredit();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_mode_exec($order_id, $mode, 'credit')) {
        // 処理情報取得
        $processor_data = fn_ap_sbps_get_processor_data($order_id);
        if ($processor_data['processor_id'] === '9242') {
            $user_id = fn_ap_sbps_get_order_user_id($order_id);
            $processor_data = fn_ap_sbps_get_quickpay_payment_method_data($user_id);
        }

        // データ設定
        $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

        // 処理実行
        $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
        $payment_info = fn_ap_sbps_get_payment_info($order_id, 'credit');
        $sbps->exec_mode_request($mode, $tracking_id, $payment_info);
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
            $sbps->errors = [];
        } elseif (in_array($payment_info['pay_method'], ['unionpay', 'recruitc'], true)) {
            // 複数回返金の場合は枝番をインクリメント
            if ($mode === 'cancel' && !empty($payment_info['refund_rowno'])) {
                fn_ap_sbps_increment_refund_rowno($order_id, 'credit');
            }

            $payment_status = $sbps->get_exec_mode_payment_status($mode, $payment_info['payment_status']);
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'credit');
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'credit'), 'credit');
        }

        // 決済結果参照要求
        if (in_array($payment_info['pay_method'], ['credit', 'credit3d'], true)) {
            $response = $sbps->reference_request($tracking_id, $payment_info['pay_method']);
            if (!empty($sbps->errors) || $response['res_status'] !== '0') {
                fn_set_notification('W', __('warning'), __('sbps_warning_reference_failed'));
            } else {
                $info = $sbps->format_cc_reference($response['res_pay_method_info']);
                fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'credit');
                fn_ap_sbps_set_order_data($order_id, $info, 'credit');
            }
        }
    }

    return [CONTROLLER_STATUS_OK];
}