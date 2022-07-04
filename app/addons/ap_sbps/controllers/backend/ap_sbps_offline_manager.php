<?php

use Tygh\Registry;
use Tygh\SbpsOffline;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_REQUEST['order_ids'])) {
        $valid_process = false;

        foreach ($_REQUEST['order_ids'] as $order_id) {
            $sbps = new SbpsOffline();

            if ($sbps->valid_mode_exec($order_id, $mode, 'offline'))  {
                if (!$valid_process) {
                    $valid_process = true;
                }

                // 処理情報取得
                $processor_data = fn_ap_sbps_get_processor_data($order_id);

                // データ設定
                $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

                // 決済結果参照要求
                $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
                $payment_info = fn_ap_sbps_get_payment_info($order_id, 'offline');
                $response = $sbps->reference_request($tracking_id, $payment_info['pay_method']);
                if (!empty($sbps->errors) || $response['res_status'] !== '0') {
                    fn_set_notification('W', __('warning'), __('sbps_warning_reference_failed'));
                } else {
                    $info = $sbps->format_reference($response['res_pay_method_info']);
                    fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'offline');
                    fn_ap_sbps_set_order_data($order_id, $info, 'offline');
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
        fn_redirect(fn_url('ap_sbps_offline_manager.manage?view_type=A'), true);
    }

    $params['check_for_suppliers'] = true;
    $params['company_name'] = true;
    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

    Registry::get('view')->assign('orders', $orders);
    Registry::get('view')->assign('search', $search);
} else {
    $sbps = new SbpsOffline();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_mode_exec($order_id, $mode, 'offline'))  {
        // 処理情報取得
        $processor_data = fn_ap_sbps_get_processor_data($order_id);

        // データ設定
        $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

        // 決済結果参照要求
        $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
        $payment_info = fn_ap_sbps_get_payment_info($order_id, 'offline');
        $response = $sbps->reference_request($tracking_id, $payment_info['pay_method']);
        if (!empty($sbps->errors) || $response['res_status'] !== '0') {
            fn_set_notification('W', __('warning'), __('sbps_warning_reference_failed'));
        } else {
            $info = $sbps->format_reference($response['res_pay_method_info']);
            fn_ap_sbps_set_sbps_payment_info($order_id, $info, 'offline');
            fn_ap_sbps_set_order_data($order_id, $info, 'offline');
        }
    }

    return [CONTROLLER_STATUS_OK];
}