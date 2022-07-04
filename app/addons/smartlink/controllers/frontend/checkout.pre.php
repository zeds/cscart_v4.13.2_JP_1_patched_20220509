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

// $Id: checkout.pre.php by tommy from cs-cart.jp 2014
// 登録済みカード情報の取得

// Modified by takahashi from cs-cart.jp 2018
// 登録済み決済支払方法がないとテストサイトがログに出る問題を修正

// Modified by takahashi from cs-cart.jp 2019
// マーケットプレイス版対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 登録済みカード情報を取得
if( $mode == 'checkout' ) {

    if( !empty($auth['user_id']) ){
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 BOF
    // 登録済み決済支払方法がないとテストサイトがログに出る問題を修正
    ///////////////////////////////////////////////
        if (fn_sln_get_payment_info("smartlink_ccreg.tpl")) {
            $sln_registered_card = fn_sln_get_registered_card_info($auth['user_id']);

            if (!empty($sln_registered_card)) {
                Registry::get('view')->assign('sln_registered_card', $sln_registered_card);
            }
        }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 EOF
    ///////////////////////////////////////////////
    }

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // マーケットプレイス版対応
    ///////////////////////////////////////////////
    // トークン決済の場合
    if (fn_sln_get_payment_info("smartlink_cctkn.tpl")) {
        // 出品者の数を取得
        $vendor_cnt = count($_SESSION['cart']['product_groups']);

        Registry::get('view')->assign('vendor_cnt', $vendor_cnt);
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////
}
