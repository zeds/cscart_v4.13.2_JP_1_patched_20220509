<?php

use Tygh\Registry;
use Tygh\SbpsRbCareer;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_REQUEST['order_ids'])) {
        $now = strtotime('now');
        $valid_process = false;

        foreach ($_REQUEST['order_ids'] as $order_id) {
            $sbps = new SbpsRbCareer();

            if ($sbps->valid_rb_mode_exec($order_id, $mode, 'rb_career')) {
                if (!$valid_process) {
                    $valid_process = true;
                }

                // データ設定
                $processor_data = fn_ap_sbps_get_processor_data($order_id);
                $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

                // 処理実行
                $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
                $pay_method = fn_ap_sbps_get_pay_method($order_id);
                $sbps->exec_mode_request($mode, $tracking_id, $pay_method, $now);
                if (!empty($sbps->errors)) {
                    fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
                } else {
                    fn_ap_sbps_set_sbps_payment_info($order_id, ['refunded_at' => $now], 'rb_career');
                    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rb_career'), 'rb_career');
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
    $params['check_for_suppliers'] = true;
    $params['company_name'] = true;

    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);
    Registry::get('view')->assign('orders', $orders);
    Registry::get('view')->assign('search', $search);
} else {
    $now = strtotime('now');

    $sbps = new SbpsRbCareer();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_rb_mode_exec($order_id, $mode, 'rb_career')) {
        // データ設定
        $processor_data = fn_ap_sbps_get_processor_data($order_id);
        $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

        // 継続課金(簡易)解約要求
        $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
        $pay_method = fn_ap_sbps_get_pay_method($order_id);
        $sbps->exec_mode_request($mode, $tracking_id, $pay_method, $now);
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
        } else {
            if ($mode === 'cancel_contract') {
                fn_ap_sbps_update_rb_cancel_contract($order_id, 'rb_career');
            } else {
                fn_ap_sbps_set_sbps_payment_info($order_id, ['refunded_at' => $now], 'rb_career');
                fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rb_career'), 'rb_career');
            }
        }
    }

    return [CONTROLLER_STATUS_OK];
}