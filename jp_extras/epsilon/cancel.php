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

// $Id: cancel.php by tommy from cs-cart.jp 2015

use Tygh\Registry;

////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に BOF
////////////////////////////////////////////////
define('AREA', 'C');
define('EPSILON_DATE_LENGTH', 12);
require '../../init.php';
require_once(Registry::get('config.dir.addons') . 'localization_jp/func.php');
////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に EOF
////////////////////////////////////////////////

// パラメータに注文番号が存在する場合
if( $_REQUEST['order_number'] ){

	// 注文IDを取得
	$order_id = substr($_REQUEST['order_number'], 0, strlen($_REQUEST['order_number']) - EPSILON_DATE_LENGTH);

    // 注文IDから該当するcompany_idをセット
    fn_payments_set_company_id($order_id);

    // fn_urlのリダイレクトパラメータに追加する"company_id"を取得
    $company_query = fn_lcjp_get_company_query_from_order($order_id);

	$valid_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);

	// 正常なフローでの処理の場合
	if( !empty($valid_id) ){
		// 注文処理ページへリダイレクト
        $url = fn_url("payment_notification.cancelled&amp;payment=epsilon&amp;order_id=$order_id" . $company_query, AREA, 'current');
        fn_redirect($url);
	}else{
		die('INVALID ACCESS!!');
	}

}else{
	die('INVALID ACCESS!!');
}
