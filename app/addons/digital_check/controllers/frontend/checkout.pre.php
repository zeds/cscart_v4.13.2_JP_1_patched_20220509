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

// $Id: checkout.pre.php by tommy from cs-cart.jp 2015
// 注文完了ページにコンビニ決済とMobileEdy決済の詳細を表示

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 注文完了ページにコンビニ決済とMobileEdy決済の詳細を表示
if( $mode == 'complete' && !empty($_REQUEST['order_id']) ){

	// 注文情報から支払方法IDとコメントを取得
	$digital_check_order = db_get_row("SELECT payment_id, notes FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);
	// 支払方法IDを取得
	$digital_check_processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $digital_check_order['payment_id']);

	// 支払方法の詳細を抽出するデリミタを初期化
	$delimiter_phrase = false;

	// 支払方法に応じてコメント欄から支払方法の詳細を抽出
	switch( $digital_check_processor_id ){
		// コンビニ決済
		case '9082':
			// 支払方法の詳細を抽出するデリミタをセット
			$delimiter_phrase = __('jp_digital_check_cvs_info');
			break;
		default:
			// do nothing
	}

	// 支払方法の詳細を抽出するデリミタが存在する場合
	if( $delimiter_phrase ){
		// コメント欄から支払方法の詳細を抽出
		$digital_check_payment_info = mb_strstr($digital_check_order['notes'], $delimiter_phrase, false, 'UTF-8');
        Registry::get('view')->assign('digital_check_payment_info', $digital_check_payment_info);
	}
}
