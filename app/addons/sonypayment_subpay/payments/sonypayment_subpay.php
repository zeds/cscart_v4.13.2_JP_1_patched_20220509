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

// $Id: sonypeyment_subpay.php by tommy from cs-cart.jp 2019

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でスマートリンクに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ) {

    // 金額変更処理判定
    list($is_cc_changeable, $unchangeable_reason) = fn_sonys_is_changeable($order_id, $order_info, $processor_data);

    // クレジトカード登録フラグ
    $result_regcc = true;

    // 注文編集の場合
    if (Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {
        // 金額変更処理の場合
        if ($is_cc_changeable) {
            $type = 'cc_change';
        }
        else{
            // エラーメッセージを表示して処理を終了
            fn_set_notification('E', __('jp_sonys_error'), $unchangeable_reason);
            return false;
        }
        // 通常の注文処理の場合
    } else {
        // クレジットカードが登録済みかチェック
        $is_card_registered = fn_sonys_is_registered_card($order_info['user_id']);

        // クレジットカードが登録されていない場合
        if (!$is_card_registered) {
            // クレジットカード情報を登録
            $result_regcc = fn_sonys_register_cc_info($order_info, $processor_data);
        }

        $type = 'ccreg_payment';
    }

    if ($result_regcc) {

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // マーケットプレイス版対応
        ///////////////////////////////////////////////
        // エラー発生フラグ
        $is_payment_error = false;

        // 処理対象となる注文ID群を取得
        $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);
        $cvv2 = $order_info['payment_info']['cvv2'];

        // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
        foreach($order_ids_to_process as $order_id_to_process) {
            $order_info = fn_get_order_info($order_id_to_process);

            $payment_info = $order_info['payment_info'];
            // 発送日の設定
            $payment_info['sonys_deliver_day'] = $payment_info['sonys_deliver_day_' . $payment_info['sonys_deliver_freq']];

            foreach($order_info['products'] as $product){
                $product_id = $product['product_id'];
            }
            // 初回無料商品かチェック
            $s_first_free_product = fn_sonys_is_first_free_product($product_id);

            // 初回無料の場合
            if($s_first_free_product){
                $result_params['TransactionId'] = true;
                $result_params['ResponseCd'] = 'OK';
            }
            // 初回無料でない場合
            else {
                // スマートリンクに送信するパラメータをセット
                $params = fn_sonys_get_params($type, $order_info, $processor_data);
                $action = 'checkout';

                // オーソリ依頼
                $result_params = fn_sonys_send_request($params, $processor_data, $action);
            }

            // スマートリンクより処理結果が返された場合
            if (!empty($result_params['TransactionId'])) {

                // 処理でエラーが発生している場合
                if ($result_params['ResponseCd'] != 'OK') {

                    // エラー発生フラグ true
                    $is_payment_error = true;

                    // エラーメッセージを取得
                    $sonys_err_msgs[] = [
                        'order_id' => $order_id_to_process,
                        'err_msg' => fn_sonys_get_err_msg($result_params['ResponseCd'])
                    ];

                    // 処理を中断
                    break;

                    // オーソリが正常に完了した場合
                } else {
                    if (fn_check_payment_script('sonypayment_subpay.php', $order_id_to_process)) {

                        // 決済が正常終了した情報を保持
                        $success_payment_data[] = [
                            'order_id' => $order_id_to_process,
                            'result_params' => $result_params
                        ];

                        // 初回無料でない場合
                        if(!$s_first_free_product) {
                            // 後続処理のための ProcessId と ProcessPass をDBに保存
                            fn_sonys_update_set_process_info($order_id_to_process, $result_params);

                            // DBに保管する支払い情報を生成
                            fn_sonys_format_payment_info('cc', $order_id_to_process, $payment_info, $result_params);

                            fn_sonys_update_cc_status_code($order_id_to_process, $result_params['OperateId']);
                        }

                        if( $type != 'cc_change' ) {
                            // 定期購入管理テーブルに追加
                            fn_sonys_update_subsc_data($order_id_to_process, $payment_info);
                        }
                    }
                }
                // リクエスト送信が異常終了した場合
            } else {
                // エラー発生フラグ true
                $is_payment_error = true;

                // 処理を中断
                break;
            }
        }

        // いずれかの決済でエラーが発生した場合
        if ($is_payment_error) {
            foreach ($success_payment_data as $data) {
                // OperateId に応じて type を設定
                if( $data['result_params']['OperateId'] == '1Auth' ){
                    $type = 'cc_auth_cancel';
                }
                elseif( $data['result_params']['OperateId'] == '1Gathering' ){
                    $type = 'cc_sales_cancel';
                }

                // キャンセル処理
                fn_sonys_send_cc_request($data['order_id'], $type);
            }

            foreach ($sonys_err_msgs as $sonys_err_msg) {
                $err_msg .= __("order_id") . '#' . $sonys_err_msg['order_id'] . ':' . $sonys_err_msg['err_msg'] . '<br />';
            }

            fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_cc_failed') . '<br />' . $err_msg);
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

        // メール送信設定
        if (empty($_REQUEST['notify_user'])) {
            $force_notification['C'] = false;
        }
        if (empty($_REQUEST['notify_department'])) {
            $force_notification['A'] = false;
        }
        if (empty($_REQUEST['notify_vendor'])) {
            $force_notification['V'] = false;
        }

        // 注文処理ページへリダイレクト
        $pp_response = array();
        $pp_response['order_status'] = 'P';
        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id, $force_notification);

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////
}
