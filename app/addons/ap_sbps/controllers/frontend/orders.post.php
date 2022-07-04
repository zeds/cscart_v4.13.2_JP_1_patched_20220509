<?php

/* * *************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 * ***************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 * ************************************************************************** */

use Tygh\Registry;
use Tygh\SbpsRbCareer;
use Tygh\SbpsRbCredit;
use Tygh\SbpsRbWallet;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $mode === 'rb_cancel_contract') {
    $order_id = $_REQUEST['order_id'];
    $order = fn_get_order_info($order_id);

    // 操作可能ステータスかチェック
    if ($order['is_subscription'] !== '1') {
        fn_set_notification('N', __('notice'), __('rb_nothing_operation_orders'));
        return [CONTROLLER_STATUS_REDIRECT, "orders.details?order_id={$order_id}"];
    }

    $process_info = fn_ap_sbps_get_process_info($order_id);

    if (empty($process_info)) {
        fn_set_notification('E', __('error'), __('sbps_error_rb_cancel_contract'));
        fn_redirect("orders.details?order_id={$order_id}", true);
    }

    // 初期化
    $sbps_obj = null;
    $processor_data = fn_ap_sbps_get_processor_data($order_id);

    switch ($process_info['process']) {
        case 'rb_career':
            $sbps_obj = new SbpsRbCareer($order_id, $processor_data['processor_params']);
            break;
        case 'rb_credit':
            $sbps_obj = new SbpsRbCredit($order_id, $processor_data['processor_params']);
            break;
        case 'rb_wallet':
            $sbps_obj = new SbpsRbWallet($order_id, $processor_data['processor_params']);
            break;
        default:
            fn_set_notification('E', __('error'), __('sbps_error_rp_cancel_contract'));
            fn_redirect("orders.details?order_id={$order_id}", true);
    }

    // 解約処理
    $tracking_id = fn_ap_sbps_get_tracking_id($order_id);
    $pay_method = fn_ap_sbps_get_pay_method($order_id, $process_info['process']);
    $sbps_obj->cancel_contract_request($tracking_id, $pay_method);
    if (!empty($sbps_obj->errors)) {
        fn_set_notification('E', __('error'), __('sbps_error_rb_cancel_contract'));
        fn_redirect("orders.details?order_id={$order_id}", true);
    }

    // 注文情報等の更新
    fn_ap_sbps_update_rb_cancel_contract($order_id, $process_info['process']);

    // 注文ステータスをキャンセルに変更
    fn_change_order_status($order_id, 'I');

    fn_set_notification('N', __('notice'), __('sbps_rb_stopped_this_order'));
    return [CONTROLLER_STATUS_REDIRECT, "orders.details?order_id={$order_id}"];
}
