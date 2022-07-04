<?php

use Tygh\Registry;
use Tygh\SbpsRpCredit;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// ショップフロントもしくは注文の編集でソフトバンク・ペイメント・サービスに接続して決済手続きを再実行する場合
if ($mode === 'place_order' && (AREA === 'C' || (AREA === 'A' && Registry::get('runtime.action') != 'save'))) {
    // 支払い方法チェック
    if (!fn_check_payment_script('ap_sbps_rp_cctkn.php', $order_id)) {
        if (AREA === 'C') {
            fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
        } else {
            fn_set_notification('W', __('warning'), __('sbps_warning_different_payment'));
        }

        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    if (AREA === 'C') {
        // 初期化
        $sbps = new SbpsRpCredit($order_id, $processor_data['processor_params']);
        $payment_info['cust_manage_flg'] = '1';

        // 決済要求
        $credit_response = $sbps->credit_request($order_info, $payment_info, 'rp_credit');
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 確定要求
        $sbps->confirm_request($credit_response['res_tracking_id'], 'rp_credit');
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 決済結果参照要求
        $reference_response = $sbps->reference_request($credit_response['res_tracking_id'], 'rp_credit');
        if (!empty($sbps->errors) || $reference_response['res_status'] !== '0') {
            $info = [];
            $sbps->errors = [];
        } else {
            $info = $sbps->format_cc_reference($reference_response['res_pay_method_info']);
        }

        // 処理・決済・注文情報保存
        fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $credit_response['res_tracking_id'], 'process' => 'rp_credit']);
        fn_ap_sbps_set_sbps_payment_info($order_id, array_merge($info, ['pay_method' => 'rp_credit']), 'rp_credit');
        fn_ap_sbps_set_order_data($order_id, $info, 'rp_credit');
        fn_ap_sbps_set_sbps_order_data($order_id, $order_info);
    } else {
        // 初期化
        $sbps = new SbpsRpCredit($order_id, $processor_data['processor_params']);

        // 金額変更チェック
        $order_data = fn_ap_sbps_get_order_data($order_id);
        if (empty($order_data) || ((int)$order_info['total'] == round($order_data['total']))) {
            fn_set_notification('W', __('warning'), __('sbps_warning_price_no_change'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 実行チェック
        if (!$sbps->valid_rp_mode_exec($order_id, 're_auth', 'rp_credit')) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('W', __('warning'), __('sbps_warning_payment_not_permit'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        $tracking_id = fn_ap_sbps_get_tracking_id($order_id);

        $attr = [
            'tracking_id' => $tracking_id,
            'pay_info_control_type' => 'B',
            'pay_info_detail_control_type' => 'B'
        ];

        // 再与信要求
        $credit_response = $sbps->credit_request($order_info, $attr, 'rp_credit', 're_auth');
        if (!empty($sbps->errors)) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('E', __('error'), __('sbps_error_re_auth_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 確定要求
        $sbps->confirm_request($credit_response['res_tracking_id'], 'rp_credit');
        if (!empty($sbps->errors)) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('E', __('error'), __('sbps_error_re_auth_confirm_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 決済結果参照要求
        $reference_response = $sbps->reference_request($credit_response['res_tracking_id'], 'rp_credit');
        if (!empty($sbps->errors) ||  $reference_response['res_status'] !== '0') {
            $info = [];
            $sbps->errors = [];
        } else {
            $info = $sbps->format_cc_reference($reference_response['res_pay_method_info']);
        }

        // 処理・決済・注文情報保存
        fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $credit_response['res_tracking_id'], 'process' => 'rp_credit']);
        fn_ap_sbps_set_sbps_payment_info($order_id, array_merge($info, ['pay_method' => 'rp_credit']), 'rp_credit');
        fn_ap_sbps_set_order_data($order_id, $info, 'rp_credit');
        fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

        // 前処理を取消・返金
        $sbps->cancel_request($tracking_id, 'rp_credit');
        if (!empty($sbps->errors)) {
            fn_set_notification('W', __('warning'), __('sbps_error_pre_process_cancel_failed'));
        }
    }

    // 終了処理
    fn_finish_payment($order_id, ['order_status' => 'P'], $force_notification);
    fn_order_placement_routines('route', $order_id, $force_notification);
} elseif ($mode === 'cron_rp_order') {
    // 初期化
    $sbps = new SbpsRpCredit($order_id, $processor_data['processor_params']);

    // 購入要求
    $credit_response = $sbps->credit_request($order_info, $payment_info, 'rp_credit');
    if (!empty($sbps->errors)) {
        // 配送日を今日から定期購入間隔分の日時に設定して、失敗に変更
        db_query('UPDATE ?:orders SET ?u WHERE order_id = ?i', ['rp_shipping_at' => strtotime("+{$order_info['rp_interval']} day")], $order_id);
        fn_change_order_status($order_id, 'F');
        return;
    }

    // 確定要求
    $sbps->confirm_request($credit_response['res_tracking_id'], 'rp_credit');
    if (!empty($sbps->errors)) {
        // 配送日を今日から定期購入間隔分の日時に設定して、失敗に変更
        db_query('UPDATE ?:orders SET ?u WHERE order_id = ?i', ['rp_shipping_at' => strtotime("+{$order_info['rp_interval']} day")], $order_id);
        fn_change_order_status($order_id, 'F');
        return;
    }

    // 決済結果参照要求
    $reference_response = $sbps->reference_request($credit_response['res_tracking_id'], 'rp_credit');
    if (!empty($sbps->errors) ||  $reference_response['res_status'] !== '0') {
        $info = [];
        $sbps->errors = [];
    } else {
        $info = $sbps->format_cc_reference($reference_response['res_pay_method_info']);
    }

    // 処理・決済・注文情報保存
    fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $credit_response['res_tracking_id'], 'process' => 'rp_credit']);
    fn_ap_sbps_set_sbps_payment_info($order_id, array_merge($info, ['pay_method' => 'rp_credit']), 'rp_credit');
    fn_ap_sbps_set_order_data($order_id, $info, 'rp_credit');
    fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

    // 支払確認済みに変更
    fn_change_order_status($order_id, 'P');
}
