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

// $Id: sln_card_info.php by tommy from cs-cart.jp 2014

// Modified by takahashi from cs-cart.jp 2018
// 登録済み決済支払方法がないとテストサイトがログに出る問題を修正

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 登録済みカード情報の削除
if ($mode == 'delete') {
	fn_sln_delete_card_info($auth['user_id']);
	return array(CONTROLLER_STATUS_REDIRECT, "sln_card_info.view");

// 登録済みカード情報の確認
} elseif ($mode == 'view') {
	// パン屑リストを生成
	fn_add_breadcrumb(__('jp_sln_ccreg_registered_card'));

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 BOF
    // 登録済み決済支払方法がないとテストサイトがログに出る問題を修正
    ///////////////////////////////////////////////
    if (fn_sln_get_payment_info("smartlink_ccreg.tpl")) {
        // 登録済みカード情報を取得
        $sln_registered_card = fn_sln_get_registered_card_info($auth['user_id']);
        Registry::get('view')->assign('sln_registered_card', $sln_registered_card);
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 EOF
    ///////////////////////////////////////////////
}
