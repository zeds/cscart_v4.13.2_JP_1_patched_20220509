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

// $Id: sales_reports.post.php by tommy from cs-cart.jp 2015

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 開始年月日を指定しない場合に1970年1月1日から起算する不具合を修正
// ※ 販売商品の種類が多いと商品別売上高 (月次) などでメモリーエラーが発生することがある
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 販売レポートの期間指定が更新された場合
	if ($mode == 'set_report_view') {
		// 期間が「すべて」、もしくは期間が「カスタム」で起算日が指定されていない場合
		if( $_REQUEST['period'] == 'A' || $_REQUEST['period'] == 'C' && empty($_REQUEST['time_from']) ){
            // 起算日を一番古い注文データから取得
            $first_order_date = db_get_field("SELECT min(timestamp) FROM ?:orders");
            $_REQUEST['time_from'] = date('Y/m/d', $first_order_date);
            list($data['time_from'], $data['time_to']) = fn_create_periods($_REQUEST);
            db_query("UPDATE ?:sales_reports SET ?u WHERE report_id = ?i", $data, $_REQUEST['report_id']);
		}
	}
}
