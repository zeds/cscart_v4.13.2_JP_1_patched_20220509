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

// $Id: omise_cc.php by takahashi from cs-cart.jp 2017
// omise（クレジットカード決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でOmiseゲートウェイに接続して決済手続きを再実行する場合
if( $mode == 'place_order' && AREA == 'C' ) {

    $params = array();
    $params = fn_omise_get_params('exectran', $order_id, $order_info, $processor_data);
    $omise_order_id = $params['OrderID'];

    if ($params['Token']) {
        // クレジットカード情報お預かり機能を利用する場合
        if( $order_info['payment_info']['register_card_info'] == 'true' && !empty($order_info['user_id']) ){

            // クレジットカード情報を登録
            $register_cc_result = fn_omise_register_cc_info($order_info, $processor_data);

            // 登録したCustomer IDをセット
            $params[Customer] = $register_cc_result['id'];

            // 決済実行
            $exec_result = fn_omise_send_request('exectranwc', $params);
        }
        else {
            // 決済実行
            $exec_result = fn_omise_send_request('exectran', $params);
        }

        // 決済実行に関するリクエスト送信が正常終了した場合
        if (!empty($exec_result)) {

            if ($exec_result['status'] == 'successful' || $exec_result['status'] == 'pending') {

                // 注文IDと利用した支払方法がマッチした場合
                if (fn_check_payment_script('omise_cc.php', $order_id)) {
                    // 注文確定処理
                    $pp_response = array();
                    $pp_response['order_status'] = 'P';
                    fn_finish_payment($order_id, $pp_response);

                    // 処理日時のタイムスタンプを取得
                    if (!empty($exec_result['created'])) {
                        $process_timestamp = strtotime($exec_result['created']);
                    } else {
                        $process_timestamp = time();
                    }

                    // 請求ステータスを更新
                    fn_omise_update_cc_status_code($order_id, $processor_data['processor_params']['jobcd'], $process_timestamp);

                    // DBに保管する支払い情報を生成
                    fn_omise_format_payment_info('cc', $order_id, $order_info['payment_info'], $exec_result);

                    // 注文処理ページへリダイレクトして注文確定
                    fn_order_placement_routines('route', $order_id);
                }
                // エラーが発生している場合
            } else {
                // エラーメッセージを表示
                fn_omise_set_err_msg($exec_result['failure_code'], $exec_result['failure_message']);

                // 注文手続きページへリダイレクト
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }
            // リクエスト送信が異常終了した場合
        } else {
            // 注文データからカード情報を削除
            $_del_data = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
            if (!empty($_del_data)) {
                db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
            }
            // 注文処理ページへリダイレクト
            fn_set_notification('E', __('jp_omise_cc_error'), __('jp_omise_cc_invalid'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    }
    else {

        // エラーメッセージを表示
        fn_omise_set_err_msg($params['ErrorCd'], $params['ErrorMsg']);

        // 注文処理ページへリダイレクト
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }

}
