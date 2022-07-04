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

// $Id: sonypayment_carrier_ep.php by takahashi from cs-cart.jp 2018
// ソニーペイメントキャリア（都度決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でソニーペイメントに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process') && (AREA == 'C') ){

    // ソニーペイメントから申込結果が戻された場合
    if( !empty($_REQUEST['EncryptValue']) && !empty($_REQUEST['process_type']) ){
        $process_type = $_REQUEST['process_type'];
        // 戻り値の復号化
        $result_params = fn_sonyc_decrypt_params($_REQUEST['EncryptValue']);

        // ソニーペイメントより処理結果が返された場合
        if (!empty($result_params['TransactionId'])) {
            // ProcNoから注文番号を取得
            $order_id = fn_sonyc_get_order_id($result_params['ProcNo']);

            //申込完了の場合
            if( $process_type == 'order_complete' ) {
                // オーソリが正常に完了した場合
                if ($result_params['ResponseCd'] == 'OK') {

                    if (fn_check_payment_script('sonypayment_carrier_ep.php', $order_id)) {
                        // 後続処理のための ProcessId と ProcessPass をDBに保存
                        fn_sonyc_update_set_process_info($order_id, $result_params);
                        // DBに保管する支払い情報を生成
                        fn_sonyc_format_payment_info('ep', $order_id, $order_info['payment_info'], $result_params);

                        // 注文処理ページへリダイレクト
                        $pp_response = array();
                        $pp_response['order_status'] = 'P';
                        fn_finish_payment($order_id, $pp_response);
                        fn_sonyc_update_status_code($order_id, $result_params['OperateId']);
                        fn_order_placement_routines('route', $order_id);
                    }
                }
            }
            //申込キャンセルの場合
            elseif( $process_type == 'order_cancel' ) {
                // 処理でエラーが発生している場合
                if ($result_params['ResponseCd'] != 'OK') {
                    // DBに保管する支払い情報を生成（エラー情報などを保管）
                    fn_sonyc_format_payment_info('ep', $order_id, $order_info['payment_info'], $result_params);

                    // 同じ注文番号だとソニーから重複エラーが出るためカートを空にする
                    fn_clear_cart($_SESSION['cart']);

                    // 注文処理ページへリダイレクト
                    $sln_err_msg = fn_sonyc_get_err_msg($result_params['ResponseCd']);
                    fn_set_notification('E', __('jp_sonypayment_carrier_ep_error'), __('jp_sonypayment_carrier_ep_canceled') . '<br />' . $sln_err_msg . '<br />' . __('jp_sonypayment_carrier_clear_cart'));
                    $return_url = fn_lcjp_get_error_return_url();
                    fn_redirect($return_url, true);
                }
            }
            //申込エラーの場合
            elseif( $process_type == 'order_error' ) {
                // 処理でエラーが発生している場合
                if ($result_params['ResponseCd'] != 'OK') {
                    // DBに保管する支払い情報を生成（エラー情報などを保管）
                    fn_sonyc_format_payment_info('ep', $order_id, $order_info['payment_info'], $result_params);

                    // 同じ注文番号だとソニーから重複エラーが出るためカートを空にする
                    fn_clear_cart($_SESSION['cart']);

                    // 注文処理ページへリダイレクト
                    $sln_err_msg = fn_sonyc_get_err_msg($result_params['ResponseCd']);
                    fn_set_notification('E', __('jp_sonypayment_carrier_ep_error'), __('jp_sonypayment_carrier_ep_failed') . '<br />' . $sln_err_msg . '<br />' . __('jp_sonypayment_carrier_clear_cart'));
                    $return_url = fn_lcjp_get_error_return_url();
                    fn_redirect($return_url, true);
                }
            }
        } else {
            // 注文処理ページへリダイレクト
            fn_set_notification('E', __('jp_sonypayment_carrier_ep_error'), __('jp_sonypayment_carrier_ep_invalid'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    }
    else {
        // ソニーペイメントに送信するパラメータをセット
        $params = fn_sonyc_get_params('ep', $order_info, $processor_data);
        $action = 'checkout';

        // オーソリ依頼
        $result = fn_sonyc_send_request($params, $processor_data, $action);

        echo $result;
        exit;
    }
}
