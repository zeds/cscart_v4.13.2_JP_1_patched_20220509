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

// $Id: checkout.pre.php by takahashi from cs-cart.jp 2019
// マーケットプレイス版対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if( $mode == 'checkout' ) {

    // NTTスマートトレードトークン決済の場合
    if (fn_nttstr_get_payment_info("nttstr_cc.tpl")) {
        // 出品者の数を取得
        $vendor_cnt = count($_SESSION['cart']['product_groups']);

        Registry::get('view')->assign('vendor_cnt', $vendor_cnt);
    }

}
