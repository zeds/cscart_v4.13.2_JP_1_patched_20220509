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

// $Id: products.pre.php by takahashi from cs-cart.jp 2019

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] != 'POST'){

	// 当該商品のソニーペイメントキャリア決済 - 継続課金に関する情報を取得
	if ($mode == 'update' || $mode == 'add') {

        $sonys_product = db_get_row("SELECT * FROM ?:jp_sonys_products WHERE product_id = ?i", $_REQUEST['product_id']);

	    Registry::get('view')->assign('sonys_product', $sonys_product);

	}
}
