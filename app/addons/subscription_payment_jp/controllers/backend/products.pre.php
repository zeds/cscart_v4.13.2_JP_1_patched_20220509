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

	// 当該商品の継続課金に関する情報を取得
	if ($mode == 'update' || $mode == 'add') {
		$subpay_jp_product = fn_subpay_jp_get_subscription_product_info($_REQUEST['product_id'], DESCR_SL);
        Registry::get('view')->assign('subpay_jp_product', $subpay_jp_product);
	}
}
