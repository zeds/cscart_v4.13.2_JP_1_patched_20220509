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

// $Id: krnkwc_ccrtn.php by takahashi from cs-cart.jp 2019
// クロネコwebコレクト（API方式・登録済みクレジットカード決済・トークン方式）

// Modified by takahashi from cs-cart.jp 2020
// Chrome 80 以降対応

// Modified by takahashi from cs-cart.jp 2022
// Chrome 89 対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でクロネコwebコレクトに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    if(empty($order_info['payment_info']['errorCd'])) {
        // 3D認証結果が戻された場合
        if( !empty($_REQUEST['3D_TRAN_ID']) && !empty($_REQUEST['COMP_CD']) && $_REQUEST['pt'] == '3ds' ){

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 BOF
            // Chrome 80 以降対応
            ///////////////////////////////////////////////
            foreach($_COOKIE as $key=>$value){
                if(substr($key, 0, 13) == 'sid_customer_'){
                    setcookie($key, "", time()-60);
                }
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 EOF
            ///////////////////////////////////////////////

            // 注文情報を取得
            $order_id = (int)$_REQUEST['order_id'];

            // 支払方法に関する各種設定値が取得できていない場合
            if( empty($processor_data) ){
                // 支払方法に紐付けられた決済代行サービスの情報を取得
                $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);
            }

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2022 BOF
            // Chrome 89 以降対応
            ///////////////////////////////////////////////
            // テーブルから3Dトークンと受付番号を取得
            $krnkwc_ccrtn = db_get_array("select three_d_token, order_no from ?:jp_krnkwc_3dsecure WHERE order_id = ?i", $order_id);

            ///////////////////////////////////////////////////////////////////////////
            // クレジット決済登録（3Dセキュア後）に使用するパラメータをセット BOF
            ///////////////////////////////////////////////////////////////////////////
            $tds_params = array();
            $tds_params['order_no'] = $krnkwc_ccrtn[0]['order_no'];
            $tds_params['comp_cd'] = $_REQUEST['COMP_CD'];
            $tds_params['token'] = $_REQUEST['TOKEN'];
            $tds_params['card_no'] = $_REQUEST['CARD_NO'];
            $tds_params['card_exp'] = $_REQUEST['CARD_EXP'];
            $tds_params['item_price'] = $_REQUEST['ITEM_PRICE'];
            $tds_params['item_tax'] = $_REQUEST['ITEM_TAX'];
            $tds_params['cust_cd'] = $_REQUEST['CUST_CD'];
            $tds_params['shop_id'] = $_REQUEST['SHOP_ID'];
            $tds_params['term_cd'] = $_REQUEST['TERM_CD'];
            $tds_params['crd_res_cd'] = $_REQUEST['CRD_RES_CD'];
            $tds_params['res_ve'] = $_REQUEST['RES_VE'];
            $tds_params['res_pa'] = $_REQUEST['RES_PA'];
            $tds_params['res_code'] = $_REQUEST['RES_CODE'];
            $tds_params['three_d_inf'] = $_REQUEST['3D_INF'];
            $tds_params['three_d_tran_id'] = $_REQUEST['3D_TRAN_ID'];
            $tds_params['send_dt'] = $_REQUEST['SEND_DT'];
            $tds_params['hash_value'] = $_REQUEST['HASH_VALUE'];
            $tds_params['three_d_token'] = $krnkwc_ccrtn[0]['three_d_token'];
            ///////////////////////////////////////////////////////////////////////////
            // クレジット決済登録（3Dセキュア後）に使用するパラメータをセット EOF
            ///////////////////////////////////////////////////////////////////////////

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2022 EOF
            ///////////////////////////////////////////////

            // クロネコwebコレクトに送信するクレジット決済登録（3Dセキュア後）用パラメータをセット
            $params = array();
            $params = fn_krnkwc_get_params('credit_3ds', $order_id, $order_info, $processor_data);
            $params = array_merge($params, $tds_params);

            // クレジット決済登録（3Dセキュア後）を実行
            $tds_result = fn_krnkwc_send_request('credit3d', $params, $processor_data['processor_params']['mode'], $processor_data['processor_script']);

            // クレジット決済登録（3Dセキュア後）に関するリクエスト送信が正常終了した場合
            if (!empty($tds_result)) {

                // クレジット決済登録（3Dセキュア後）が正常に完了している場合
                if ( $tds_result['returnCode'] == 0 ) {
                    // 受付番号をセット
                    $tds_result['order_no'] = $tds_params['order_no'];

                    // 注文情報を取得
                    $order_info = fn_get_order_info($order_id);

                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2022 BOF
                    // Chrome 89 以降対応
                    ///////////////////////////////////////////////
                    // テーブルからお客様コメントを取得
                    $krnkwc_ccrtn_notes = db_get_field("select notes FROM ?:jp_krnkwc_notes WHERE user_id = ?i", $order_info['user_id']);
                    if(!empty($krnkwc_ccrtn_notes)){
                        // お客様コメントを更新
                        db_query("UPDATE ?:orders SET notes = ?s WHERE order_id = ?i", $krnkwc_ccrtn_notes, $order_id);
                    }
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2022 EOF
                    ///////////////////////////////////////////////

                    // 注文IDと利用した支払方法がマッチした場合
                    if (fn_check_payment_script('krnkwc_ccrtn.php', $order_id)) {
                        // 注文確定処理
                        $pp_response = array();
                        $pp_response['order_status'] = 'P';
                        fn_finish_payment($order_id, $pp_response);

                        // 請求ステータスを更新
                        fn_krnkwc_update_cc_status_code($order_id, 'CC_4', $tds_result['order_no']);

                        // DBに保管する支払い情報を生成
                        fn_krnkwc_format_payment_info('cc', $order_id, $order_info['payment_info'], $tds_result);

                        // 注文処理ページへリダイレクトして注文確定
                        fn_order_placement_routines('route', $order_id);
                    }
                // エラー処理
                } else {
                    // 注文手続きページへリダイレクトしてエラーメッセージを表示
                    fn_krnkwc_set_err_msg($tds_result);
                    $return_url = fn_lcjp_get_error_return_url();
                    fn_redirect($return_url, true);
                }

            // リクエスト送信が異常終了した場合
            }else{
                // 注文処理ページへリダイレクト
                fn_set_notification('E', __('jp_kuroneko_webcollect_cc_error'), __('jp_kuroneko_webcollect_cc_invalid'));
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }

        // その他の場合
        }else{

            // 注文編集の場合
            if( Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {

                // 金額変更・再オーソリ可否判定
                $is_cc_changeable = fn_krnkwc_cc_is_changeable($order_id, $processor_data);

                // 金額変更の場合
                if ($is_cc_changeable) {
                    // 金額変更
                    fn_krnkwc_send_cc_request($order_id, 'creditchangeprice');
                    return true;
                }
            }

            // クロネコwebコレクトに送信するクレジット決済登録（通常）用パラメータをセット
            $params = array();
            $params = fn_krnkwc_get_params('credit', $order_id, $order_info, $processor_data);

        }

        // クレジット決済登録（通常）
        $cc_result = fn_krnkwc_send_request('credit', $params, $processor_data['processor_params']['mode'], $processor_data['processor_script']);

        // クレジット決済登録（通常）に関するリクエスト送信が正常終了した場合
        if (!empty($cc_result)) {

            // 結果コード
            $return_code = $cc_result['returnCode'];

            // クレジット決済登録（通常）が正常に完了している場合
            if ( $return_code == 0 ) {
                // 3Dセキュア認証用ページへ遷移するためのHTMLが存在する場合
                if( !empty($cc_result['threeDAuthHtml']) ){

                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2020 BOF
                    // Chrome 80 以降対応
                    ///////////////////////////////////////////////
                    foreach($_COOKIE as $key=>$value){
                        if(substr($key, 0, 13) == 'sid_customer_'){
                            $domain = '.' . $_SERVER['HTTP_HOST'];
                            header('Set-Cookie: ' . $key. '=' . $value . '; Domain=' . $domain . '; HttpOnly; SameSite=None; Secure');
                            break;
                        }
                    }
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2020 EOF
                    ///////////////////////////////////////////////

                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2022 BOF
                    // Chrome 89 以降対応
                    ///////////////////////////////////////////////
                    // テーブルに3Dトークンと受付番号を保持
                    db_query("REPLACE INTO ?:jp_krnkwc_3dsecure(order_id, three_d_token, order_no) VALUES(?i, ?s, ?s)", $order_id, $cc_result['threeDToken'], $params['order_no']);
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2022 EOF
                    ///////////////////////////////////////////////

                    // 3Dセキュア認証用ページへ遷移
                    $tds_text = str_replace('<![CDATA[', '', $cc_result['threeDAuthHtml']);
                    $tds_text = str_replace(']]>', '', $tds_text);
                    echo $tds_text;
                    exit;
                }else{
                    $cc_result['order_no'] = $params['order_no'];
                }

                // 注文情報を取得
                $order_info = fn_get_order_info($order_id);

                // 注文IDと利用した支払方法がマッチした場合
                if (fn_check_payment_script('krnkwc_ccrtn.php', $order_id)) {
                    // 注文確定処理
                    $pp_response = array();
                    $pp_response['order_status'] = 'P';
                    fn_finish_payment($order_id, $pp_response);

                    // 請求ステータスを更新
                    fn_krnkwc_update_cc_status_code($order_id, 'CC_4', $params['order_no']);

                    // DBに保管する支払い情報を生成
                    fn_krnkwc_format_payment_info('cc', $order_id, $order_info['payment_info'], $cc_result);

                    // 注文処理ページへリダイレクトして注文確定
                    fn_order_placement_routines('route', $order_id);
                }
            // エラー処理
            } else {
                // 注文手続きページへリダイレクトしてエラーメッセージを表示
                fn_krnkwc_set_err_msg($cc_result);
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }

            // リクエスト送信が異常終了した場合
        }else{
            // 注文処理ページへリダイレクト
            fn_set_notification('E', __('jp_kuroneko_webcollect_cc_error'), __('jp_kuroneko_webcollect_cc_invalid'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }
    }
    // トークン取得時にエーラが発生した場合
    else {
        // 注文処理ページへリダイレクト
        fn_set_notification('E', __('jp_kuroneko_webcollect_cc_error'), ($order_info['payment_info']['errorMsg'].' ('.$order_info['payment_info']['errorCd'].')'));
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);

    }

}
