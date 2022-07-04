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

// $Id: smartlink_ccreg.php by tommy from cs-cart.jp 2014
// スマートリンクネットワーク（登録済みクレジットカード決済）

// Modified by takahashi from cs-cart.jp 2019
// マーケットプレイス版対応


// Modified by takahashi from cs-cart.jp 2020
// 3Dセキュア認証対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でスマートリンクに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    $is_3d_check = false;
    $params_3ds = array();

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // 3Dセキュア認証対応
    ///////////////////////////////////////////////
    // ソニーペイメントから3D結果が戻された場合
    if( !empty($_REQUEST['EncryptValue']) && !empty($_REQUEST['process_type']) ){
        $process_type = $_REQUEST['process_type'];

        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);
        // 支払方法IDに紐付けられた決済代行サービスの情報を取得
        $processor_data = fn_get_processor_data($payment_id);

        // 戻り値の復号化
        $result_params = fn_sln_decrypt_params($_REQUEST['EncryptValue'], $processor_data);

        // ソニーペイメントより処理結果が返された場合
        if (!empty($result_params['TransactionId'])) {
            // ProcNoから注文番号を取得
            $order_id = fn_sln_get_order_id_3d($result_params['ProcNo']);
            $order_info = fn_get_order_info($order_id);

            $force_notification = Tygh::$app['session']['force_notification'];

            //3D認証決済完了の場合
            if ($process_type == '3d_complete') {
                // 3D認証決済が正常に完了した場合
                // 3Dセキュアパスワード未設定（U01,2）あるいは 3Dセキュアカード発行会社未対応（U02,3）の場合
                if (($result_params['SecureResultCode'] == '0' || $result_params['SecureResultCode'] == '2' || $result_params['SecureResultCode'] == '3') && $result_params['ResponseCd'] == 'OK') {

                    if (fn_check_payment_script('smartlink_ccreg.php', $order_id)) {
                        // 後続処理のための ProcessId と ProcessPass をDBに保存
                        fn_sln_update_set_process_info($order_id, $result_params);

                        // DBに保管する支払い情報を生成
                        fn_sln_format_payment_info('cc', $order_id, $order_info['payment_info'], $result_params);
                        fn_sln_update_cc_status_code($order_id, $result_params['OperateId']);
                        if(!is_array($force_notification)){
                            $force_notification = [];
                        }
                        // 注文処理ページへリダイレクト
                        $pp_response = array();
                        $pp_response['order_status'] = 'P';
                        fn_finish_payment($order_id, $pp_response, $force_notification);
                        fn_order_placement_routines('route', $order_id, $force_notification);
                    }
                }
                else {
                    $err_cd = $result_params['SecureResultCode'];
                    fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_3d_err_' . $err_cd));
                    $return_url = fn_lcjp_get_error_return_url();
                    fn_redirect($return_url, true);
                }
            } // ３D認証カードチェック完了の場合
            elseif ($process_type == '3d_check_complete') {
                // 3D認証決済が正常に完了した場合
                // 3Dセキュアパスワード未設定（U01,2）あるいは 3Dセキュアカード発行会社未対応（U02,3）の場合
                if (($result_params['SecureResultCode'] == '0' || $result_params['SecureResultCode'] == '2' || $result_params['SecureResultCode'] == '3') && $result_params['ResponseCd'] == 'OK') {

                    if (fn_check_payment_script('smartlink_ccreg.php', $order_id)) {
                        $is_3d_check = true;

                        $params_3ds['MessageVersionNo3D'] = $result_params['MessageVersionNo3D'];
                        $params_3ds['TransactionId3D'] = $result_params['TransactionId3D'];
                        $params_3ds['EncodeXId3D'] = $result_params['EncodeXId3D'];
                        $params_3ds['TransactionStatus3D'] = $result_params['TransactionStatus3D'];
                        $params_3ds['CAVVAlgorithm3D'] = $result_params['CAVVAlgorithm3D'];
                        $params_3ds['CAVV3D'] = $result_params['CAVV3D'];
                        $params_3ds['ECI3D'] = $result_params['ECI3D'];
                    }
                }
                else {
                    $err_cd = $result_params['SecureResultCode'];
                    fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_3d_err_' . $err_cd));
                    $return_url = fn_lcjp_get_error_return_url();
                    fn_redirect($return_url, true);
                }
            } else {
                // 注文処理ページへリダイレクト
                fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_cc_failed'));
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }
        }
        else {
            // 注文処理ページへリダイレクト
            fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_cc_failed'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 EOF
    ///////////////////////////////////////////////

    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // 3Dセキュア認証対応
    ///////////////////////////////////////////////
    if ($processor_data['processor_params']['tdflag'] == 'true' && count($order_ids_to_process) > 1 && !$is_3d_check) {
        $type = 'check';
        $params = fn_sln_get_params($type, $order_info, $processor_data);
        $params = fn_sln_encrypt_params($params, $processor_data);

        // 本番環境の場合
        if( $processor_data['processor_params']['mode'] == 'live' ){
            $target_url = 'https://www.e-scott.jp/online/tds/OTDS010.do';
            //  テスト環境の場合
        }else{
            $target_url = 'https://www.test.e-scott.jp/online/tds/OTDS010.do';
        }

        Tygh::$app['session']['force_notification'] = $force_notification;

        echo <<<EOT
<!DOCTYPE html PUBLIC "--//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title></title>
</head>
<body onload="javascript:document.forms['redirectForm'].submit();">
<form action="{$target_url}" method="post" id="redirectForm">
<input type="hidden" name="MerchantId" value="{$params["MerchantId"]}"/>
<input type="hidden" name="EncryptValue" value="{$params["EncryptValue"]}"/>
</
</body>
</html>
EOT;
        exit;
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 EOF
    ///////////////////////////////////////////////

    // 減額処理判定
    $is_cc_changeable = fn_sln_cc_is_changeable($order_id, $order_info, $processor_data);

    // セキュリティコードの要否を初期化
    $cvv_required = false;

    // 注文編集の場合
    if( Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {
        // 減額処理の場合
        if( $is_cc_changeable ){
            $type = 'cc_change';
        // 減額処理以外でセキュリティコードが入力されていない場合
        }elseif( $processor_data['processor_params']['use_cvv'] == 'true' && empty($order_info['payment_info']['cvv2']) ){
            // エラーメッセージを表示して処理を中断
            fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_cc_failed') . '<br />' . __('jp_sln_security_code_is_empty'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        // その他の場合
        }else{
            $type = 'ccreg_payment';
        }
    // 通常の注文処理の場合
    }else{
        $type = 'ccreg_payment';
    }

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // マーケットプレイス版対応
    ///////////////////////////////////////////////
    // エラー発生フラグ
    $is_payment_error = false;

    Tygh::$app['session']['payment_info']['cvv2'] = $order_info['payment_info']['cvv2'];

    // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
    foreach($order_ids_to_process as $order_id_to_process) {
        $order_info = fn_get_order_info($order_id_to_process);
        $order_info['payment_info']['cvv2'] = Tygh::$app['session']['payment_info']['cvv2'];

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // 3Dセキュア認証対応
        ///////////////////////////////////////////////
        // 本人認証サービス（3Dセキュア）を利用する場合（複数出品者の注文では無い場合）
        if ($processor_data['processor_params']['tdflag'] == 'true' && count($order_ids_to_process) == 1 && !$is_3d_check) {
            $processor_data['tds'] = true;

            // スマートリンクに送信するパラメータをセット
            $params = fn_sln_get_params($type, $order_info, $processor_data);
            $params = fn_sln_encrypt_params($params, $processor_data);

            // 本番環境の場合
            if( $processor_data['processor_params']['mode'] == 'live' ){
                $target_url = 'https://www.e-scott.jp/online/tds/OTDS010.do';
                //  テスト環境の場合
            }else{
                $target_url = 'https://www.test.e-scott.jp/online/tds/OTDS010.do';
            }

            Tygh::$app['session']['force_notification'] = $force_notification;

            echo <<<EOT
<!DOCTYPE html PUBLIC "--//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title></title>
</head>
<body onload="javascript:document.forms['redirectForm'].submit();">
<form action="{$target_url}" method="post" id="redirectForm">
<input type="hidden" name="MerchantId" value="{$params["MerchantId"]}"/>
<input type="hidden" name="EncryptValue" value="{$params["EncryptValue"]}"/>
</
</body>
</html>
EOT;
            exit;
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////

        // スマートリンクに送信するパラメータをセット
        $params = fn_sln_get_params($type, $order_info, $processor_data);

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // 3Dセキュア認証対応
        ///////////////////////////////////////////////
        if($is_3d_check){
            $params = array_merge($params, $params_3ds);
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////

        $action = 'checkout';

        // オーソリ依頼
        $result_params = fn_sln_send_request($params, $processor_data, $action);

        // スマートリンクより処理結果が返された場合
        if (!empty($result_params['TransactionId'])) {

            // 処理でエラーが発生している場合
            if ($result_params['ResponseCd'] != 'OK') {

                // エラー発生フラグ true
                $is_payment_error = true;

                // エラーメッセージを取得
                $sln_err_msgs[] = [
                    'order_id' => $order_id_to_process,
                    'err_msg' => fn_sln_get_err_msg($result_params['ResponseCd'])
                ];

                // 処理を中断
                break;

                // オーソリが正常に完了した場合
            } else {
                if (fn_check_payment_script('smartlink_ccreg.php', $order_id_to_process)) {

                    // 決済が正常終了した情報を保持
                    $success_payment_data[] = [
                        'order_id' => $order_id_to_process,
                        'result_params' => $result_params
                    ];

                    // 後続処理のための ProcessId と ProcessPass をDBに保存
                    fn_sln_update_set_process_info($order_id_to_process, $result_params);

                    // DBに保管する支払い情報を生成
                    fn_sln_format_payment_info('cc', $order_id_to_process, $order_info['payment_info'], $result_params);

                    fn_sln_update_cc_status_code($order_id_to_process, $result_params['OperateId']);
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
            fn_sln_send_cc_request($data['order_id'], $type);
        }

        foreach ($sln_err_msgs as $sln_err_msg) {
            $err_msg .= __("order_id") . '#' . $sln_err_msg['order_id'] . ':' . $sln_err_msg['err_msg'] . '<br />';
        }

        fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_cc_failed') . '<br />' . $err_msg);
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }

    // 注文処理ページへリダイレクト
    $pp_response = array();
    $pp_response['order_status'] = 'P';
    fn_finish_payment($order_id, $pp_response, $force_notification);
    fn_order_placement_routines('route', $order_id, $force_notification);

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////
}
