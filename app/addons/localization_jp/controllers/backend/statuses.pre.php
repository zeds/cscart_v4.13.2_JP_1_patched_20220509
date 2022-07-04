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

// $Id: statuses.pre.php by tommy from cs-cart.jp 2015

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	// 注文ステータスの登録・編集ページにソート順指定フィールドを表示する
	if ($mode == 'update') {
        // 登録されているソート順を取得
		$status_sort_id = db_get_field("SELECT sort_id FROM ?:jp_order_status_sort WHERE status = ?s", $_REQUEST['status']);

        // ソート順が登録されていない場合
        if( empty($status_sort_id) ){
            // 0をセット
			$status_sort_id = 0;
		}
        // Smarty変数にソート順をセット
        Registry::get('view')->assign('status_sort_id', $status_sort_id);
    }
}
