<?php

use Tygh\Registry;
use Tygh\SbpsCredit;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode === 'view' || $mode === 'delete') {
    $user_id = $auth['user_id'];

    // 初期化
    $processor_data = fn_ap_sbps_get_quickpay_payment_method_data($user_id);
    $sbps = new SbpsCredit($order_id, $processor_data['processor_params']);

    if ($mode === 'delete') {
        // 登録済みクレジットカード情報を削除
        $response = $sbps->cc_delete_request($user_id);
        if (!empty($sbps->errors)) {
            fn_set_notification('E', __('jp_smbc_ccreg_delete_error'), __('jp_smbc_ccreg_delete_failed'));
        } else {
            fn_set_notification('N', __('notice'), __('jp_smbc_ccreg_delete_success'));
            db_query('DELETE FROM ?:sbps_quickpay_data WHERE user_id = ?i', $user_id);
        }

        return [CONTROLLER_STATUS_REDIRECT, 'ap_sbps_card_info.view'];
    } else {
        // パン屑リストを生成
        fn_add_breadcrumb(__('sbps_registered_card'));

        // 登録済みクレジットカード情報を取得
        $response = $sbps->cc_reference_request($user_id);
        if (empty($sbps->errors)) {
            $registered_card = $sbps->format_reference($response['res_pay_method_info']);
            Registry::get('view')->assign('registered_card', $registered_card);
        }
    }
}
