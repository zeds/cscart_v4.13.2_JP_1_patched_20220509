<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2004 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: products.pre.php by ari from cs-cart.jp 2015
// 商品検索において複数ワードの区切り文字に全角スペースを利用可能とする

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	if ($mode == 'search') {
		if (!empty($_REQUEST['q']) && ( (!empty($_REQUEST['match']) && $_REQUEST['match'] != 'exact') || empty($_REQUEST['match'])) ) {
			$_REQUEST['q'] = mb_convert_kana($_REQUEST['q'], "s", "UTF-8");
		}
	}
}

if ($mode == 'view') {

    //SQLインジェクション不正アクセス防止
    if (!empty($_REQUEST['product_id']) && !is_numeric($_REQUEST['product_id'])) {
        echo 'INVALID ACCESS';
        exit();
    }
}
