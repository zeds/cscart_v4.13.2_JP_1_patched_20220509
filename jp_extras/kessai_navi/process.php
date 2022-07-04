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

// $Id: process.php by tommy from cs-cart.jp 2015

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

    // 決済結果判定コードを初期化
    $result = 'ng';

    // 注文IDを取得
    $order_id = fn_knv_get_order_id($_REQUEST['stran']);

    // 注文IDから該当するcompany_idをセット
    fn_payments_set_company_id($order_id);

    // 決済が正常終了している場合
    if($_REQUEST['rsltcd'] == '0000000000000'){
        $params = $_REQUEST;

        // 決済が正常終了し、データの改竄が行われていないことを確認できた場合に決済結果判定コードを"ok"に変更
        $is_valid = fn_knv_is_valid_params($params);
        if($is_valid) $result = 'ok';

        // DBに保管する支払い情報を生成
        $order_info = fn_get_order_info($order_id);
        fn_knv_format_payment_info($order_id, $order_info['payment_info'], $params);
    }

    // 注文処理ページへリダイレクト
    $url = fn_url("payment_notification.process&amp;payment=kessai_navi&order_id=" . $order_id . "&amp;&bkcode=" . $_REQUEST['bkcode'] . "&amp;result=" . $result, AREA, 'current');
    fn_redirect($url);

}else{
    die('INVALID ACCESS!!');
}