<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: products.pre.php by tommy from cs-cart.jp 2013

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] != 'POST'){

	// 当該商品のCS-Cartマルチ決済継続課金に関する情報を取得
	if ($mode == 'update' || $mode == 'add') {
		$smbc_rb_product = fn_smbcks_get_rb_product_info($_REQUEST['product_id']);
        Registry::get('view')->assign('smbc_rb_product', $smbc_rb_product);
	}
}
