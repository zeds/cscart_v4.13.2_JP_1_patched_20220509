<?php

use Tygh\Registry;
use Tygh\SbpsCredit;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// ショップフロントもしくは注文の編集でソフトバンク・ペイメント・サービスに接続して決済手続きを再実行する場合
if ($mode == 'place_order' && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save'))) {
    // 支払い方法チェック
    if (!fn_check_payment_script('ap_sbps_cctkn.php', $order_id)) {
        if (AREA === 'C') {
            fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
        } else {
            fn_set_notification('W', __('warning'), __('sbps_warning_different_payment'));
        }

        fn_redirect(fn_lcjp_get_error_return_url(), true);
    }

    if (AREA === 'C') {
        $is_payment_error = false;
        $success_payment_data = [];

        if (fn_allowed_for('MULTIVENDOR') && $order_info['is_parent_order'] === 'Y') {
            $payment_order_ids = $child_order_ids = db_get_fields("SELECT order_id FROM ?:orders WHERE parent_order_id = ?i", $order_id);
        } else {
            $payment_order_ids = [$order_id];
        }

        foreach ($payment_order_ids as $payment_order_id) {
            // 同一の注文IDですでに処理されていないか、チェック
            $credit_payment_info = fn_ap_sbps_get_payment_info($payment_order_id, 'credit');
            if (!empty($credit_payment_info) && !in_array($credit_payment_info['payment_status'], [SBPS_PAYMENT_STATUS_AUTH_CANCEL, SBPS_PAYMENT_STATUS_REFUNDED, SBPS_PAYMENT_STATUS_UNKNOWN_CANCEL], true)) {
                continue;
            }

            $payment_order_info = fn_get_order_info($payment_order_id);

            // 初期化
            $sbps = new SbpsCredit($payment_order_id, $processor_data['processor_params']);

            // 決済要求
            $credit_response = $sbps->credit_request($payment_order_info, $payment_info, 'credit');
            if (!empty($sbps->errors)) {
                $is_payment_error = true;
                break;
            }

            // 確定要求
            $sbps->confirm_request($credit_response['res_tracking_id'], 'credit');
            if (!empty($sbps->errors)) {
                $is_payment_error = true;
                break;
            }

            // 決済結果参照要求
            $reference_response = $sbps->reference_request($credit_response['res_tracking_id'], 'credit');
            if (!empty($sbps->errors) ||  $reference_response['res_status'] !== '0') {
                $info = [];
                $sbps->errors = [];
            } else {
                $info = $sbps->format_cc_reference($reference_response['res_pay_method_info']);
            }

            // 決済が正常終了したオブジェクトとトラッキングIDを保持
            $success_payment_data[] = [
                'sbps_obj' => $sbps,
                'order_id' => $payment_order_id,
                'tracking_id' => $credit_response['res_tracking_id']
            ];

            // 処理・決済・注文情報保存
            fn_ap_sbps_set_sbps_process_info($payment_order_id, ['tracking_id' => $credit_response['res_tracking_id'], 'process' => 'credit']);
            fn_ap_sbps_set_sbps_payment_info($payment_order_id, array_merge($info, ['pay_method' => 'credit']), 'credit');
            fn_ap_sbps_set_order_data($payment_order_id, $info, 'credit');
            fn_ap_sbps_set_sbps_order_data($payment_order_id, $payment_order_info);
        }

        // いずれかの決済でエラーが発生した場合
        if ($is_payment_error) {
            foreach ($success_payment_data as $data) {
                $sbps_obj = $data['sbps_obj'];

                // 取消要求
                $sbps_obj->cancel_request($data['tracking_id'], 'credit');
                if (!empty($sbps_obj->errors)) {
                    continue;
                }

                // 決済結果参照要求
                $reference_response = $sbps->reference_request($data['tracking_id'], 'credit');
                if (!empty($sbps->errors) ||  $reference_response['res_status'] !== '0') {
                    $info = ['payment_status' => SBPS_PAYMENT_STATUS_UNKNOWN_CANCEL];
                } else {
                    $info = $sbps->format_cc_reference($reference_response['res_pay_method_info']);
                }

                // 決済・注文情報保存
                fn_ap_sbps_set_sbps_payment_info($data['order_id'], $info, 'credit');
                fn_ap_sbps_set_order_data($payment_order_id, $info, 'credit');
            }

            fn_set_notification('E', __('error'), __('sbps_error_cc_payment_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // カード預かり情報保存
        if (!empty($payment_info['cust_manage_flg']) && $payment_info['cust_manage_flg'] === '1') {
            fn_ap_sbps_set_quickpay_data($order_info['user_id'], $order_info['payment_method']['payment_id']);
        }
    } else {
        // 初期化
        $sbps = new SbpsCredit($order_id, $processor_data['processor_params']);

        // 金額変更チェック
        $order_data = fn_ap_sbps_get_order_data($order_id);
        if (empty($order_data) || ((int)$order_info['total'] == round($order_data['total']))) {
            fn_set_notification('W', __('warning'), __('sbps_warning_price_no_change'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 実行チェック
        if (!$sbps->valid_mode_exec($order_id, 're_auth', 'credit')) {
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
        $credit_response = $sbps->credit_request($order_info, $attr, 'credit', 're_auth');
        if (!empty($sbps->errors)) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('E', __('error'), __('sbps_error_re_auth_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 確定要求
        $sbps->confirm_request($credit_response['res_tracking_id'], 'credit');
        if (!empty($sbps->errors)) {
            // 注文情報ロールバック
            fn_ap_sbps_rollback_order($order_id);

            fn_set_notification('E', __('error'), __('sbps_error_re_auth_confirm_failed'));
            fn_redirect(fn_lcjp_get_error_return_url(), true);
        }

        // 決済結果参照要求
        $reference_response = $sbps->reference_request($credit_response['res_tracking_id'], 'credit');
        if (!empty($sbps->errors) ||  $reference_response['res_status'] !== '0') {
            $info = [];
            $sbps->errors = [];
        } else {
            $info = $sbps->format_cc_reference($reference_response['res_pay_method_info']);
        }

        // 処理・決済・注文情報保存
        fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $credit_response['res_tracking_id'], 'process' => 'credit']);
        fn_ap_sbps_set_sbps_payment_info($order_id, array_merge($info, ['pay_method' => 'credit']), 'credit');
        fn_ap_sbps_set_order_data($order_id, $info, 'credit');
        fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

        // 前処理を取消・返金
        $sbps->cancel_request($tracking_id, 'credit');
        if (!empty($sbps->errors)) {
            fn_set_notification('W', __('warning'), __('sbps_error_pre_process_cancel_failed'));
        }
    }

    // 終了処理
    fn_finish_payment($order_id, ['order_status' => 'P'], $force_notification);
    fn_order_placement_routines('route', $order_id, $force_notification);
}
