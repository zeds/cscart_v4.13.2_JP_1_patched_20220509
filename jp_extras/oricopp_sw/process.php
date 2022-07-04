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
// 【メモ:2015/01/08】
// 初期バージョンではセッションIDのバリデーションを実施していたが、
// 複数ショップ運営時にサブショップの決済がエラーとなるため廃止した。

use Tygh\Registry;

////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に BOF
////////////////////////////////////////////////
define('AREA', 'C');
require '../../init.php';
require_once(Registry::get('config.dir.addons') . 'oricopp_sw/func.php');
require_once(Registry::get('config.dir.addons') . 'localization_jp/func.php');
define('OPPSW_DATE_LENGTH', 12);
////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に EOF
////////////////////////////////////////////////

// 遷移先URLを初期化
$target_url = '';

// 注文IDが存在しない場合
if( empty($_REQUEST['orderId']) ){
    fn_set_notification('E', __('error'), __('jp_oppsw_error_oid') );

// その他の場合
}else{
    // 注文IDを取得
    $order_id = substr($_REQUEST['orderId'], 0, strlen($_REQUEST['orderId']) - OPPSW_DATE_LENGTH);

    // 注文IDから該当するcompany_idをセット
    fn_payments_set_company_id($order_id);

    // OricoPayment Plus側の処理が正常終了している場合
    if($_REQUEST['mStatus'] == 'success'){
        // 注文処理ページへリダイレクト
        $target_url = "payment_notification.process&payment=oricopp_sw&order_id=" . $order_id . "&" . $_REQUEST['vResultCode'];
    }else{
        $target_url = "payment_notification.cancelled&payment=oricopp_sw&order_id=" . $order_id . "&vrc=" . $_REQUEST['vResultCode'];
    }
}

fn_redirect($target_url, AREA, 'current');
