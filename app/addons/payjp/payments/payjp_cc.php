<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2009 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: payjp_cc.php by takahashi from cs-cart.jp 2020
// payjp（クレジットカード決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でpayjpゲートウェイに接続して決済手続きを再実行する場合
if( $mode == 'place_order' && AREA == 'C' ) {
    $params = array();
    $params = fn_payjp_get_params('exectran', $order_info, $processor_data);

    if (empty($params['ErrorMsg'])) {
        // クレジットカード情報を登録
        if($order_info['payment_info']['is_regcard'] != 'Y') {
            $register_cc_result = fn_payjp_register_cc_info($order_info, $processor_data);

            if($register_cc_result) {
                // 登録・更新完了メッセージを表示
                fn_set_notification('N', __('information'), __('jp_payjp_ccreg_register_success'));
            }
            else {
                // エラーメッセージを表示して処理を終了
                fn_set_notification('E', __('jp_payjp_ccreg_error'),__('jp_payjp_ccreg_register_failed'));
                return false;
            }
        }

        // 登録済み Customer IDをセット
        $params['customer'] = fn_payjp_get_customer_id($order_info['user_id']);
        $params['order_id'] = $order_id;

        // 決済実行
        $exec_result = fn_payjp_send_request('exectranwc', $params);

        // 決済実行に関するリクエスト送信が正常終了した場合
        if (!is_array($exec_result['error'])) {

            // 注文IDと利用した支払方法がマッチした場合
            if (fn_check_payment_script('payjp_cc.php', $order_id)) {
                // 注文確定処理
                $pp_response = array();
                $pp_response['order_status'] = 'P';
                fn_finish_payment($order_id, $pp_response, $force_notification);

                // 請求ステータスを更新
                fn_payjp_update_cc_status_code($order_id, $processor_data['processor_params']['jobcd']);

                // DBに保管する支払い情報を生成
                fn_payjp_format_payment_info('cc', $order_id, $order_info['payment_info'], $exec_result);

                // 注文処理ページへリダイレクトして注文確定
                fn_order_placement_routines('route', $order_id, $force_notification);
            }

            // リクエスト送信が異常終了した場合
        } else {
            // 注文データから情報を削除
            $_del_data = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
            if (!empty($_del_data)) {
                db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
            }
            // 注文処理ページへリダイレクト
            fn_payjp_set_err_msg($exec_result['error']['code'], $exec_result['error']['message'], 'cc');
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    }
    else {

        // エラーメッセージを表示
        fn_payjp_set_err_msg($params['ErrorCd'], $params['ErrorMsg'], 'cc');

        // 注文処理ページへリダイレクト
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }

}
