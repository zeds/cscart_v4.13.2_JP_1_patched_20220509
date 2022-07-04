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

// $Id: smartlink_cctkn.php by tommy from cs-cart.jp 2015
// スマートリンクネットワーク（クレジットカード決済）

// Modified by takahashi from cs-cart.jp 2017
// トークン決済に対応

// Modified by takahashi from cs-cart.jp 2019
// マーケットプレイス版対応

// Modified by takahashi from cs-cart.jp 2020
// 3Dセキュア認証対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でスマートリンクに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ) {

    $is_3d_check = false;
    $params_3ds = array();
    $type = 'cc';
    
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

                    if (fn_check_payment_script('smartlink_cctkn.php', $order_id)) {
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
            } // ３D認証会員登録完了の場合
            elseif ($process_type == '3d_reg_complete') {

                // 3D認証決済が正常に完了した場合
                // 3Dセキュアパスワード未設定（U01,2）あるいは 3Dセキュアカード発行会社未対応（U02,3）の場合
                if (($result_params['SecureResultCode'] == '0' || $result_params['SecureResultCode'] == '2' || $result_params['SecureResultCode'] == '3') && $result_params['ResponseCd'] == 'OK') {

                    if (fn_check_payment_script('smartlink_cctkn.php', $order_id)) {
                        $is_3d_reg = true;

                        $params_3ds['MessageVersionNo3D'] = $result_params['MessageVersionNo3D'];
                        $params_3ds['TransactionId3D'] = $result_params['TransactionId3D'];
                        $params_3ds['EncodeXId3D'] = $result_params['EncodeXId3D'];
                        $params_3ds['TransactionStatus3D'] = $result_params['TransactionStatus3D'];
                        $params_3ds['CAVVAlgorithm3D'] = $result_params['CAVVAlgorithm3D'];
                        $params_3ds['CAVV3D'] = $result_params['CAVV3D'];
                        $params_3ds['ECI3D'] = $result_params['ECI3D'];

                        $_data = array('user_id' => $order_info['user_id'],
                            'payment_method' => 'smartlink_ccreg',
                            'quickpay_id' => Tygh::$app['session']['KaiinPass'],
                        );
                        db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);

                        // 登録・更新完了メッセージを表示
                        fn_set_notification('N', __('information'), __('jp_sln_ccreg_register_success'));
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

    // 減額処理判定
    $is_cc_changeable = fn_sln_cc_is_changeable($order_id, $order_info, $processor_data);

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    // トークン決済に対応
    ///////////////////////////////////////////////
    // クレジトカード登録フラグ
    $result_regcc = true;
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////

    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

    // 注文編集の場合
    if (Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {
        // 減額処理の場合
        if ($is_cc_changeable) {
            $type = 'cc_change';
            // その他の場合
        } else {
            $type = 'cc';
        }
        // 通常の注文処理の場合
    } else {
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2017 BOF
        // トークン決済に対応
        ///////////////////////////////////////////////
        // クレジットカード情報お預かり機能を利用する場合（減額処理時は除く）
        if ($order_info['payment_info']['register_card_info'] == 'true' && !empty($order_info['user_id']) && $type != 'cc_change') {
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 BOF
            // 3Dセキュア認証対応
            ///////////////////////////////////////////////
            // 3dセキュアユーザー登録が完了していない場合
            if( !$is_3d_reg ) {
                // 本人認証サービス（3Dセキュア）を利用する場合(複数出品者の場合のみ)
                if ($processor_data['processor_params']['tdflag'] == 'true' && count($order_ids_to_process) > 1) {
                    $processor_data['tds'] = true;
                }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 EOF
            ///////////////////////////////////////////////
                // クレジットカード情報を登録
                $result_regcc = fn_sln_register_cc_info($order_info, $processor_data);
            }

            $type = 'ccreg_payment';
        } else {
            $type = 'cc';
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2017 EOF
        ///////////////////////////////////////////////
    }

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // 3Dセキュア認証対応
    ///////////////////////////////////////////////
    if($is_3d_reg){
        $type = 'ccreg_payment';
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 EOF
    ///////////////////////////////////////////////

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    // トークン決済に対応
    ///////////////////////////////////////////////
    if ($result_regcc) {
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // マーケットプレイス版対応
        ///////////////////////////////////////////////
        // エラー発生フラグ
        $is_payment_error = false;

        $token_no = 0;

        // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
        foreach($order_ids_to_process as $order_id_to_process){
            $order_info = fn_get_order_info($order_id_to_process);

            // トークン情報を更新
            $order_info['payment_info']['token'] = $order_info['payment_info']['token'][$token_no];
            $token_no += 1;

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 BOF
            // 3Dセキュア認証対応
            ///////////////////////////////////////////////
            // 本人認証サービス（3Dセキュア）を利用する場合（複数出品者の注文では無い場合）
            if ($processor_data['processor_params']['tdflag'] == 'true' && count($order_ids_to_process) == 1 && !$is_3d_reg) {
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
            if($is_3d_reg){
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
                    if (fn_check_payment_script('smartlink_cctkn.php', $order_id_to_process)) {

                        // 決済が正常終了した情報を保持
                        $success_payment_data[] = [
                            'order_id' => $order_id_to_process,
                            'result_params' => $result_params
                        ];

                        // 後続処理のための ProcessId と ProcessPass をDBに保存
                        fn_sln_update_set_process_info($order_id_to_process, $result_params);

                        ///////////////////////////////////////////////
                        // Modified by takahashi from cs-cart.jp 2017 BOF
                        // トークン決済に対応
                        ///////////////////////////////////////////////

                        $pinfo_type = $type;
                        if($type == 'cc_change'){
                            $pinfo_type = 'cc';
                        }

                        // DBに保管する支払い情報を生成
                        fn_sln_format_payment_info($pinfo_type, $order_id_to_process, $order_info['payment_info'], $result_params);

                        ///////////////////////////////////////////////
                        // Modified by takahashi from cs-cart.jp 2017 EOF
                        ///////////////////////////////////////////////

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

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    // トークン決済に対応
    ///////////////////////////////////////////////
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////
}
