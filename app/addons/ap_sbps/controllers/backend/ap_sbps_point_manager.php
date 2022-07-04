<?php

use Tygh\Registry;
use Tygh\SbpsPoint;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_REQUEST['order_ids'])) {
        $valid_process = false;

        foreach ($_REQUEST['order_ids'] as $order_id) {
            $sbps = new SbpsPoint();

            if ($sbps->valid_mode_exec($order_id, $mode, 'point'))  {
                if (!$valid_process) {
                    $valid_process = true;
                }

                // 処理情報取得
                $processor_data = fn_ap_sbps_get_processor_data($order_id);

                // データ設定
                $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

                // 返金要求
                $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
                $payment_info = fn_ap_sbps_get_payment_info($order_id, 'point');
                $sbps->cancel_request($tracking_id, $payment_info['pay_method']);
                if (!empty($sbps->errors)) {
                    fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
                } else {
                    fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => SBPS_PAYMENT_STATUS_REFUNDED], 'point');
                    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'point'), 'point');
                }
            }
        }

        if (!$valid_process) {
            fn_set_notification('W', __('warning'), __('sbps_error_exec_data_not_exists'));
        }

        return [CONTROLLER_STATUS_REDIRECT];
    }

    return [CONTROLLER_STATUS_OK];
}

$params = $_REQUEST;

if ($mode === 'manage' || empty($_REQUEST['order_id'])) {
    $params['check_for_suppliers'] = true;
    $params['company_name'] = true;
    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

    Registry::get('view')->assign('orders', $orders);
    Registry::get('view')->assign('search', $search);
} else {
    $sbps = new SbpsPoint();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_mode_exec($order_id, $mode, 'point'))  {
        // 処理情報取得
        $processor_data = fn_ap_sbps_get_processor_data($order_id);

        // データ設定
        $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

        // 取消返金要求
        $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
        $payment_info = fn_ap_sbps_get_payment_info($order_id, 'point');
        $sbps->cancel_request($tracking_id, $payment_info['pay_method']);
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
        } else {
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => SBPS_PAYMENT_STATUS_REFUNDED], 'point');
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'point'), 'point');
        }
    }

    return [CONTROLLER_STATUS_OK];
}