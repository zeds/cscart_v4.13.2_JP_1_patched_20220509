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

// $Id: products.pre.php by takahashi from cs-cart.jp 2018

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] != 'POST'){

	// 当該商品のソニーペイメントキャリア決済 - 継続課金に関する情報を取得
	if ($mode == 'update' || $mode == 'add') {

// 各キャリア毎にデータを更新
        for($carrier = 1; $carrier <= 3; $carrier++) {

            // キャリアコード
            // 01: docomo, 02: au, 03: softbank
            $carrier_cd = '0' . strval($carrier);

            $sonyc_rb_product = fn_sonyc_get_rb_product_info($_REQUEST['product_id'], $carrier_cd);

            Registry::get('view')->assign('sonypayment_carrier_rb_product_'.$carrier_cd, $sonyc_rb_product);
        }
	}
}
