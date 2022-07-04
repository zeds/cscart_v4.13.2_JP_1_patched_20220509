<?php

use Tygh\Registry;
use Tygh\SbpsRpCareer;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_REQUEST['order_ids'])) {
        $valid_process = false;

        foreach ($_REQUEST['order_ids'] as $order_id) {
            $sbps = new SbpsRpCareer();

            if ($mode !== 'cancel_contract' && $sbps->valid_rp_mode_exec($order_id, $mode, 'rp_career'))  {
                if (!$valid_process) {
                    $valid_process = true;
                }

                // 処理情報取得
                $processor_data = fn_ap_sbps_get_processor_data($order_id);

                // データ設定
                $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

                // 処理実行
                $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
                $payment_info = fn_ap_sbps_get_payment_info($order_id, 'rp_career');
                $sbps->exec_mode_request($mode, $tracking_id, $payment_info['pay_method']);
                if (!empty($sbps->errors)) {
                    fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
                } else {
                    $payment_status = $sbps->get_exec_mode_payment_status($mode, $payment_info['payment_status']);
                    fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'rp_career');
                    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rp_career'), 'rp_career');
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
    $sbps = new SbpsRpCareer();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_rp_mode_exec($order_id, $mode, 'rp_career'))  {
        // 処理実行用のデータ取得
        $process_info = fn_ap_sbps_get_process_info($order_id);
        $payment_info = fn_ap_sbps_get_payment_info($order_id, 'rp_career');

        // ソフトバンク決済の場合、APIは呼び出さず定期購入の解約処理のみ行う
        if ($mode === 'cancel_contract' && $payment_info['pay_method'] === 'rp_softbank2') {
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
        $sbps->exec_mode_request($mode, $tracking_id, $payment_info['pay_method']);
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
        } else {
            if ($mode === 'cancel_contract') {
                fn_ap_sbps_update_rp_cancel_contract($order_id, $process_info);
            } else {
                $payment_status = $sbps->get_exec_mode_payment_status($mode, $payment_info['payment_status']);
                fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'rp_career');
                fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'rp_career'), 'rp_career');
            }
        }
    }

    return [CONTROLLER_STATUS_OK];
}