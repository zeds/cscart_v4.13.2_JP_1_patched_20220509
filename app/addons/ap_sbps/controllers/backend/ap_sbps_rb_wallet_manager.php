<?php

use Tygh\Registry;
use Tygh\SbpsRbWallet;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$params = $_REQUEST;

if ($mode === 'manage' || empty($_REQUEST['order_id'])) {
    $params['check_for_suppliers'] = true;
    $params['company_name'] = true;
    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

    Registry::get('view')->assign('orders', $orders);
    Registry::get('view')->assign('search', $search);
} else {
    $now = strtotime('now');

    $sbps = new SbpsRbWallet();
    $order_id = $_REQUEST['order_id'];

    if ($sbps->valid_rb_mode_exec($order_id, $mode, 'rb_wallet')) {
        // データ設定
        $processor_data = fn_ap_sbps_get_processor_data($order_id);
        $sbps->set_data(['order_id' => $order_id, 'processor' => $processor_data['processor_params']]);

        // 継続課金(簡易)解約要求
        $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
        $pay_method = fn_ap_sbps_get_pay_method($order_id);
        $sbps->cancel_contract_request($tracking_id, $pay_method);
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_exec_failed'));
        } else {
            fn_ap_sbps_update_rb_cancel_contract($order_id, 'rb_wallet');
        }
    }

    return [CONTROLLER_STATUS_OK];
}