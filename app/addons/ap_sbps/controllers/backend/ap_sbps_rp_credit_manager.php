<?php

use Tygh\Registry;
use Tygh\SbpsRpCredit;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_REQUEST['order_ids'])) {
        $valid_process = false;

        foreach ($_REQUEST['order_ids'] as $order_id) {
            $sbps = new SbpsRpCredit();

            if ($mode !== 'cancel_contract' && $sbps->valid_rp_mode_exec($order_id, $mode, 'rp_credit'))  {
                if (!$valid_process) {
                    $valid_process = true;
                }

                // 処理情報取得
                $processor_data = fn_ap_sbps_get_processor_data($order_id);

                // データ設定
                $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

                // 処理実行
                $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
                $payment_info = fn_ap_sbps_get_payment_info($order_id, 'rp_credit');
                $sbps->exec_mode_request($mode, $tracking_id, $payment_info);
                if (!empty($sbps->errors)) {
                    fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
                } elseif($payment_info['pay_method'] === 'rp_recruitc') {
                    // 複数回返金の場合は枝番をインクリメント
                    if ($mode === 'cancel' && !empty($payment_info['refund_rowno'])) {
                        fn_ap_sbps_increment_refund_rowno($order_id, 'credit');
                    }

                    $payment_status = $sbps->get_exec_mode_payment_status($mode, $payment_info['payment_status']);
                    fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'rp_credit');
                    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rp_credit'), 'rp_credit');
                }

                // 決済結果参照要求
                if (in_array($payment_info['pay_method'], ['rp_credit', 'rp_credit3d'], true)) {
                    $response = $sbps->reference_request($tracking_id, $payment_info['pay_method']);
                    if (!empty($sbps->errors) || $response['res_status'] !== '0') {
                        fn_set_notification('W', __('warning'), __('sbps_warning_reference_failed'));
                    } else {
                        $info = $sbps->format_cc_reference($response['res_pay_method_info']);
                        fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'rp_credit');
                        fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rp_credit'), 'rp_credit');
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
        fn_redirect(fn_url('ap_sbps_rp_credit_manager.manage?view_type=A'), true);
    }

    $params['check_for_suppliers'] = true;
    $params['company_name'] = true;
    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

    Registry::get('view')->assign('orders', $orders);
    Registry::get('view')->assign('search', $search);
} else {
    $sbps = new SbpsRpCredit();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_rp_mode_exec($order_id, $mode, 'rp_credit'))  {
        // 処理実行用のデータ取得
        $process_info = fn_ap_sbps_get_process_info($order_id);
        $payment_info = fn_ap_sbps_get_payment_info($order_id, 'rp_credit');

        // クレジットカード決済の場合、APIは呼び出さず定期購入の解約処理のみ行う
        if ($mode === 'cancel_contract' && in_array($payment_info['pay_method'], ['rp_credit', 'rp_credit3d'], true)) {
            fn_ap_sbps_update_rp_cancel_contract($order_id, $process_info);
            return [CONTROLLER_STATUS_OK];
        }

        // データ設定
        $processor_data = fn_ap_sbps_get_processor_data($order_id);
        $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

        $tracking_id = $process_info['tracking_id'];
        if ($mode === 'cancel_contract' && !empty($process_info['master_order_id'])) {
            $tracking_id = fn_ap_sbps_get_tracking_id($process_info['master_order_id']);
        }

        // 処理実行
        $sbps->exec_mode_request($mode, $tracking_id, $payment_info);
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
        } elseif ($payment_info['pay_method'] === 'rp_recruitc') {
            if ($mode === 'cancel_contract') {
                fn_ap_sbps_update_rp_cancel_contract($order_id, $process_info);
            } else {
                // 複数回返金の場合は枝番をインクリメント
                if ($mode === 'cancel' && !empty($payment_info['refund_rowno'])) {
                    fn_ap_sbps_increment_refund_rowno($order_id, 'rp_credit');
                }

                $payment_status = $sbps->get_exec_mode_payment_status($mode, $payment_info['payment_status']);
                fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'rp_credit');
                fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rp_credit'), 'rp_credit');
            }
        }

        // 決済結果参照要求
        if (in_array($payment_info['pay_method'], ['rp_credit', 'rp_credit3d'], true)) {
            $response = $sbps->reference_request($tracking_id, $payment_info['pay_method']);
            if (!empty($sbps->errors) || $response['res_status'] !== '0') {
                fn_set_notification('W', __('warning'), __('sbps_warning_reference_failed'));
            } else {
                $info = $sbps->format_cc_reference($response['res_pay_method_info']);
                fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'rp_credit');
                fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rp_credit'), 'rp_credit');
            }
        }
    }

    return [CONTROLLER_STATUS_OK];
}