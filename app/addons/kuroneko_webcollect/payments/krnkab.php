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

// $Id: krnkab.php by tommy from cs-cart.jp 2016
// クロネコ代金後払いサービス

// Modified by takahashi from cs-cart.jp 2020
// 後払い請求金額変更対応

// Modified by takahashi from cs-cart.jp 2021
// スマホタイプ対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でクロネコ代金後払いサービスに接続して決済手続きを再実行する場合
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 BOF
// 後払い請求金額変更対応
///////////////////////////////////////////////
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2021 BOF
    // スマホタイプ対応
    ///////////////////////////////////////////////
    $nincode = $_REQUEST['smsauth']['nincode'];
    $orderno = $_REQUEST['smsauth']['krnkab_orderno'];
    $order_info['nincode'] = $nincode;
    $order_info['krnkab_orderno'] = $orderno;
    //　認証コードを受信した場合
    if($nincode > 0){
        $params = array();
        $params = fn_krnkwc_get_params('absms', $order_id, $order_info, $processor_data);
        // クロネコ代金後払いサービスにリクエストを送信
        $ab_result = fn_krnkwc_send_request('absms', $params, $processor_data['processor_params']['mode']);
        if (!empty($ab_result)) {
            // 結果コード
            $return_code = $ab_result['returnCode'];

            // 審査結果
            if($return_code == 0){
                $auth_result = $ab_result['result'];
            }else{
                $auth_result = '';
            }

            // クロネコ代金後払いサービスの決済依頼(SMS認証あり)が正常に完了している場合
            if ( $return_code == 0 && $auth_result == 1 /* OK */ ) {

                // 注文IDと利用した支払方法がマッチした場合
                if (fn_check_payment_script('krnkab.php', $order_id)) {
                    // 注文確定処理
                    $pp_response = array();
                    $pp_response['order_status'] = 'P';
                    fn_finish_payment($order_id, $pp_response, $force_notification);

                    // 請求ステータスを更新
                    fn_krnkwc_update_cc_status_code($order_id, 'AB_1', $params['orderNo']);

                    // DBに保管する支払い情報を生成
                    fn_krnkwc_format_payment_info('ab', $order_id, $order_info['payment_info'], $ab_result);

                    // 決済に関するメッセージを表示
                    fn_set_notification('I', __('jp_kuroneko_webcollect_ab_notification_title'),  __('jp_kuroneko_webcollect_ab_notification_message', array('[email]' => $params['buyer_email'])));

                    // 注文処理ページへリダイレクトして注文確定
                    fn_order_placement_routines('route', $order_id, $force_notification);
                }
                // エラー処理
            } else {
                // 決済依頼は正常終了したが審査結果が（2:コード不一致 / 3:有効期限切れ(3分) / 4:認証SMS送信NG / 5:認証結果の不正）の場合
                if(!empty($auth_result)){
                    fn_set_notification('E', __('jp_kuroneko_webcollect_ab_error'), __('jp_kuroneko_webcollect_ab_error_msg'));
                // 決済依頼が異常終了した場合
                }else{
                    fn_krnkwc_set_err_msg($ab_result, __('jp_kuroneko_webcollect_ab_error'));
                }

                // 注文手続きページへリダイレクトしてエラーメッセージを表示
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }
            // リクエスト送信が異常終了した場合
        }else{
            // 注文処理ページへリダイレクト
            fn_set_notification('E', __('jp_kuroneko_webcollect_ab_error'), __('jp_kuroneko_webcollect_ab_error_msg'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2021 EOF
    ///////////////////////////////////////////////

    // 注文編集の場合
    if (Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {

        // 金額変更
        fn_krnkwc_send_cc_request($order_id, 'abchangeprice');
        return true;
    }
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 EOF
///////////////////////////////////////////////

    // クロネコ代金後払いサービスに送信するパラメータをセット
    $params = array();
    $params = fn_krnkwc_get_params('ab', $order_id, $order_info, $processor_data);

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2021 BOF
    // スマホタイプ対応
    ///////////////////////////////////////////////
    $sms_invoice = $_REQUEST['payment_info']['sms_invoice'];
    $smartphone  = $_REQUEST['payment_info']['smartphone'];

    if($sms_invoice == 'true'){
        $params['sendDiv'] = '3';
        $params['telNum'] = $smartphone;
    }

    // クロネコ代金後払いサービスにリクエストを送信
    $ab_result = fn_krnkwc_send_request('KAARA0010', $params, $processor_data['processor_params']['mode']);

    if($sms_invoice == 'true'){
        if (!empty($ab_result)) {
            // 結果コード
            $return_code = $ab_result['returnCode'];

            // 審査結果
            if($return_code == 0){
                $auth_result = $ab_result['result'];
            }else{
                $auth_result = '';
            }

            // クロネコ代金後払いサービスの決済依頼(SMS認証あり)が正常に完了している場合
            if ( $return_code == 0 && $auth_result == 3 /* 審査中 */ ) {
                $html  = '<form method="POST" action="' . fn_url('checkout.place_order') . '">';
                $html .= '<div class="clearfix">';
                $html .= '<div class="ty-control-group" style="margin-left: 20px;">';
                $html .= '    <label for="nincode" class="ty-control-group__title cm-required">' . __("jp_kuroneko_webcollect_ab_nincode") . '</label>';
                $html .= '    <input type="tel" name="smsauth[nincode]" id="nincode"/>';
                $html .= '    <input type="hidden" name="smsauth[krnkab_orderno]" value="' . $ab_result['orderNo'] . '"/>';
                $html .= '&nbsp;&nbsp;<button type="submit">' . __("send") . '</button>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</form>';

                fn_set_notification('I', __('jp_kuroneko_webcollect_ab_sms_auth_nincode'),  $html);

                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            // エラー処理
            } else {
            // 決済依頼は正常終了したが審査結果が（1:ご利用不可 / 2:限度額超過）の場合
                if(!empty($auth_result)){
                    fn_set_notification('E', __('jp_kuroneko_webcollect_ab_error'), __('jp_kuroneko_webcollect_ab_error_msg'));
                    // 決済依頼が異常終了した場合
                }else{
                    fn_krnkwc_set_err_msg($ab_result, __('jp_kuroneko_webcollect_ab_error'));
                }

                // 注文手続きページへリダイレクトしてエラーメッセージを表示
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }
            // リクエスト送信が異常終了した場合
        }else{
            // 注文処理ページへリダイレクト
            fn_set_notification('E', __('jp_kuroneko_webcollect_ab_error'), __('jp_kuroneko_webcollect_ab_error_msg'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2021 EOF
    ///////////////////////////////////////////////

    // クロネコ代金後払いサービスに関するリクエスト送信が正常終了した場合
    if (!empty($ab_result)) {

        // 結果コード
        $return_code = $ab_result['returnCode'];

        // 審査結果
        if($return_code == 0){
            $auth_result = $ab_result['result'];
        }else{
            $auth_result = '';
        }

        // クロネコ代金後払いサービスの決済依頼が正常に完了している場合
        if ( $return_code == 0 && $auth_result == 0 ) {

            // 注文情報を取得
            $order_info = fn_get_order_info($order_id);

            // 注文IDと利用した支払方法がマッチした場合
            if (fn_check_payment_script('krnkab.php', $order_id)) {
                // 注文確定処理
                $pp_response = array();
                $pp_response['order_status'] = 'P';
                fn_finish_payment($order_id, $pp_response);

                // 請求ステータスを更新
                fn_krnkwc_update_cc_status_code($order_id, 'AB_1', $params['orderNo']);

                // DBに保管する支払い情報を生成
                fn_krnkwc_format_payment_info('ab', $order_id, $order_info['payment_info'], $ab_result);

                // コンビニ決済に関するメッセージを表示
                fn_set_notification('I', __('jp_kuroneko_webcollect_ab_notification_title'),  __('jp_kuroneko_webcollect_ab_notification_message', array('[email]' => $params['buyer_email'])));

                // 注文処理ページへリダイレクトして注文確定
                fn_order_placement_routines('route', $order_id);
            }
        // エラー処理
        } else {
            // 決済依頼は正常終了したが審査結果が（1:ご利用不可 / 2:限度額超過 / 3:審査中）の場合
            if(!empty($auth_result)){
                fn_set_notification('E', __('jp_kuroneko_webcollect_ab_error'), __('jp_kuroneko_webcollect_ab_error_msg'));
            // 決済依頼が異常終了した場合
            }else{
                fn_krnkwc_set_err_msg($ab_result, __('jp_kuroneko_webcollect_ab_error'));
            }

            // 注文手続きページへリダイレクトしてエラーメッセージを表示
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    // リクエスト送信が異常終了した場合
    }else{
        // 注文処理ページへリダイレクト
        fn_set_notification('E', __('jp_kuroneko_webcollect_ab_error'), __('jp_kuroneko_webcollect_ab_error_msg'));
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }
}
