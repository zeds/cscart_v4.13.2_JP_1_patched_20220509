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

// $Id: sonys_card_info.php by takahashi from cs-cart.jp 2019

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 登録済みカード情報の削除
if ($mode == 'delete') {
	fn_sonys_delete_card_info($auth['user_id']);
	return array(CONTROLLER_STATUS_REDIRECT, "sonys_card_info.view");

// 登録済みカード情報の確認
} elseif ($mode == 'view') {
	// パン屑リストを生成
	fn_add_breadcrumb(__('jp_sonys_registered_card'));

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 BOF
    // 登録済み決済支払方法がないとテストサイトがログに出る問題を修正
    ///////////////////////////////////////////////
    if (fn_sonys_get_payment_info("sonypayment_subpay.tpl")) {
        // 登録済みカード情報を取得
        $sonys_registered_card = fn_sonys_get_registered_card_info($auth['user_id']);
        Registry::get('view')->assign('sonys_registered_card', $sonys_registered_card);
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 EOF
    ///////////////////////////////////////////////
} elseif ($mode == 'update' ) {
    $order_info['user_id'] = $auth['user_id'];
    $order_info['payment_info']['token'][0] = $_REQUEST['payment_info']['token'][0];

    fn_sonys_register_cc_info($order_info, $processor_data);

    return array(CONTROLLER_STATUS_REDIRECT, "sonys_card_info.view");
}