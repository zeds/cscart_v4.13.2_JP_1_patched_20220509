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

use Tygh\Registry;

////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に BOF
////////////////////////////////////////////////
define('AREA', 'C');
require '../../init.php';
require_once(Registry::get('config.dir.addons') . 'localization_jp/config.php');
////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に EOF
////////////////////////////////////////////////

// ゼウスからの戻りパラメータに注文IDと顧客IDを組み合わせた文字列が含まれる場合
if( $_REQUEST['sendid'] ){

	// 注文IDと顧客IDを取得
	$zeus_info = explode(ZEUS_SEPARATOR, $_REQUEST['sendid']);

	if( sizeof($zeus_info) != 2 ){
		// エラーメッセージを返す
		echo 'NG, INVALID SENDID';
		exit;
	}

	$zeus_order_id = (int)$zeus_info[0];

	// 注文IDから該当するcompany_idをセット
	fn_payments_set_company_id($zeus_order_id);

	// 注文処理ページへリダイレクト
    $url = fn_url("payment_notification.process&payment=zeus&order_id=" . $zeus_order_id, AREA, 'current');
	fn_redirect($url);

}else{
	die('INVALID ACCESS!!');
}
