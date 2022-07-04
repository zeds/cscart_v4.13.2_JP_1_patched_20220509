<?php

use Tygh\Registry;
use Tygh\SbpsWallet;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_REQUEST['order_ids'])) {
        $valid_process = false;

        foreach ($_REQUEST['order_ids'] as $order_id) {
            $sbps = new SbpsWallet();

            if ($sbps->valid_mode_exec($order_id, $mode, 'wallet'))  {
                $exec_mode = $mode;

                if (!$valid_process) {
                    $valid_process = true;
                }

                // 処理情報取得
                $processor_data = fn_ap_sbps_get_processor_data($order_id);

                // データ設定
                $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

                // 処理実行
                $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
                $payment_info = fn_ap_sbps_get_payment_info($order_id, 'wallet');

                // Yahoo!ウォレット決済にて売上確定でキャンセルされた場合、modeを返金用に変更
                if ($exec_mode === 'cancel' && $payment_info['pay_method'] === 'yahoowallet' && $payment_info['status'] === SBPS_PAYMENT_STATUS_SALES_CONFIRM) {
                    $exec_mode = 'refund';
                }

                $sbps->exec_mode_request($exec_mode, $tracking_id, $payment_info['pay_method']);

                // Yahoo!ウォレット決済にて与信済・売上済が判断できない状態で取消処理がエラーの場合、続けて返金処理を行う
                if ($exec_mode === 'cancel' && $payment_info['pay_method'] === 'yahoowallet' && !empty($sbps->errors)) {
                    $sbps->errors = [];

                    // 返金要求
                    $mode = 'refund';
                    $sbps->exec_mode_request($exec_mode, $tracking_id, $payment_info['pay_method']);
                }

                if (!empty($sbps->errors)) {
                    fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
                } else {
                    $payment_status = $sbps->get_exec_mode_payment_status($exec_mode, $payment_info['payment_status']);
                    fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'wallet');
                    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'wallet'), 'wallet');
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
    $sbps = new SbpsWallet();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_mode_exec($order_id, $mode, 'wallet'))  {
        $exec_mode = $mode;

        // 処理情報取得
        $processor_data = fn_ap_sbps_get_processor_data($order_id);

        // データ設定
        $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

        // 処理実行
        $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
        $payment_info = fn_ap_sbps_get_payment_info($order_id, 'wallet');
        $sbps->exec_mode_request($mode, $tracking_id, $payment_info['pay_method']);

        // Yahoo!ウォレット決済にて与信済・売上済が判断できない状態で取消処理がエラーの場合、続けて返金処理を行う
        if ($exec_mode === 'cancel' && $payment_info['pay_method'] === 'yahoowallet' && $payment_info['status'] === SBPS_PAYMENT_STATUS_UNKNOWN_OK && !empty($sbps->errors)) {
            $sbps->errors = [];

            // 返金要求
            $exec_mode = 'refund';
            $sbps->exec_mode_request($exec_mode, $tracking_id, $payment_info['pay_method']);
        }

        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
        } else {
            $payment_status = $sbps->get_exec_mode_payment_status($exec_mode, $payment_info['payment_status']);
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'wallet');
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'wallet'), 'wallet');
        }
    }

    return [CONTROLLER_STATUS_OK];
}