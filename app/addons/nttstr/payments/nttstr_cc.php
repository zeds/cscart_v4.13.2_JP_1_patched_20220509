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

// $Id: nttstr_cc.php by takahashi from cs-cart.jp 2019
// omise（クレジットカード決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントでNTTスマートトレードゲートウェイに接続して決済手続きを実行する場合
if( $mode == 'place_order' && AREA == 'C' ) {

    // エラーコード、エラーメッセージ
    $errorCd = $order_info['payment_info']['errorCd'];
    $errorMsg = mb_convert_encoding($order_info['payment_info']['errorMsg'], 'SJIS');

    if ( empty($errorCd) ) {

        // エラー発生フラグ
        $is_payment_error = false;

        // 処理対象となる注文ID群を取得
        $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);
        $token_no = 0;

        // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
        foreach($order_ids_to_process as $order_id_to_process){
            $order_info = fn_get_order_info($order_id_to_process);

            // トークン情報を更新
            $order_info['payment_info']['token'] = $order_info['payment_info']['token'][$token_no];
            $token_no += 1;

            // NTTスマートトレードに送信するパラメータをセット
            $params = array();
            $params = fn_nttstr_get_params('exectran', $order_id_to_process, $order_info, $processor_data);

            // オーソリ依頼
            $result_params = fn_nttstr_send_request('exectran', $params, $processor_data['processor_params']['mode']);

            // NTTスマートトレードより処理結果が返された場合
            if (!empty($result_params['centerPayId'])) {

                // 処理でエラーが発生している場合
                if ($result_params['payStatus'] != 'C1000000') {

                    // エラー発生フラグ true
                    $is_payment_error = true;

                    // エラーメッセージを取得
                    $nttstr_err_msgs[] = [
                        'order_id' => $order_id_to_process,
                        'err_msg' => fn_nttstr_get_err_msg($result_params['payStatus'])
                    ];

                    // 処理を中断
                    break;

                    // オーソリが正常に完了した場合
                } else {
                    if (fn_check_payment_script('nttstr_cc.php', $order_id_to_process)) {

                        // 決済が正常終了した情報を保持
                        $success_payment_data[] = [
                            'order_id' => $order_id_to_process,
                            'status_code' => $processor_data['processor_params']['jobcd'],
                            'result_params' => $result_params
                        ];

                        // 後続処理のための aid をDBに保存
                        fn_nttstr_update_cc_status_code($order_id_to_process, $processor_data['processor_params']['jobcd'], strtotime($result_params['date']), $result_params['aid'], $result_params['linked_1']);

                        // DBに保管する支払い情報を生成
                        fn_nttstr_format_payment_info('cc', $order_id_to_process, $order_info['payment_info'], $result_params);

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
                if( $data['status_code'] == NTTSTR_AUTH_OK ){
                    $type = 'cc_auth_cancel';
                }
                elseif( $data['status_code'] == NTTSTR_CAPTURE_OK ){
                    $type = 'cc_sales_cancel';
                }

                // キャンセル処理
                fn_nttstr_send_cc_request($data['order_id'], $type);
            }

            foreach ($nttstr_err_msgs as $nttstr_err_msg) {
                if( !empty($nttstr_err_msg['err_msg']) ) {
                    $err_msg .= __("order_id") . '#' . $nttstr_err_msg['order_id'] . ':' . $nttstr_err_msg['err_msg'] . '<br />';
                }
            }

            fn_set_notification('E', __('jp_nttstr_cc_error'), __('jp_nttstr_cc_failed') . '<br />' . $err_msg);
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

        // メール送信設定
        if( empty($_REQUEST['notify_user']) ) {
            $force_notification['C'] = false;
        }
        if( empty($_REQUEST['notify_department']) ) {
            $force_notification['A'] = false;
        }
        if( empty($_REQUEST['notify_vendor']) ) {
            $force_notification['V'] = false;
        }

        // 注文処理ページへリダイレクト
        $pp_response = array();
        $pp_response['order_status'] = 'P';
        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id, $force_notification);

    }
    else {

        // エラーメッセージを表示
        fn_nttstr_set_err_msg($errorCd, $errorMsg);

        // 注文処理ページへリダイレクト
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }

}
