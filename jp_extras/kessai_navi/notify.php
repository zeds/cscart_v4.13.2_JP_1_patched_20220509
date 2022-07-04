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

// $Id: notify.php by tommy from cs-cart.jp 2014

////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に BOF
////////////////////////////////////////////////
define('AREA', 'C');
require '../../init.php';
////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に EOF
////////////////////////////////////////////////

// 取引番号が存在する場合
if( !empty($_REQUEST['mbtran']) && !empty($_REQUEST['stran']) ){

    // 改竄チェック用フラグを初期化
    $is_valid = false;

    // セットする注文ステータスを初期化
    $status_to = 'F';

    // 注文IDを取得
    $order_id = fn_knv_get_order_id($_REQUEST['stran']);

    // 注文IDから該当するcompany_idをセット
    fn_payments_set_company_id($order_id);

    // 決済が正常終了している場合
    if($_REQUEST['rsltcd'] == '0000000000000'){
        $params = $_REQUEST;

        // 決済が正常終了し、データの改竄が行われていないことを確認
        $is_valid = fn_knv_is_valid_params($params);

        // 入金通知対象の決済手段であるかを判定
        $is_method_notify = fn_knv_method_notify($_REQUEST['bkcode']);

        // 決済が正常終了かつ入金通知対象の決済手段である場合
        if ($is_valid && $is_method_notify && fn_check_payment_script('kessai_navi.php', $order_id)) {

            // DBに保管する支払い情報を生成
            $order_info = fn_get_order_info($order_id);
            fn_knv_format_payment_info($order_id, $order_info['payment_info'], $params);

            // セットする注文ステータスを「P（入金完了状態）」に変更
            $status_to = 'P';
        }
    }

    // 注文ステータスが「受注処理未了」の場合
    if($order_info['status'] == 'N') {

        $pp_response = array();

        // 注文ステータスをセット
        $pp_response['order_status'] = $status_to;
        // 注文完了処理を実行
        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id);

    // 注文ステータスが「処理待ち」の場合
    }elseif($order_info['status'] == 'O'){
        // 注文ステータスを変更
        $force_notification = array();
        $force_notification['C'] = true;
        $force_notification['A'] = true;
        // 注文ステータスを変更
        $to_status = $status_to;
        fn_change_order_status($order_id, $to_status, '', $force_notification);
    }
}else{
    die('INVALID ACCESS!!');
}