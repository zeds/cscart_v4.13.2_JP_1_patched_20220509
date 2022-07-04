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

// $Id: paygent_card_info.php by tommy from cs-cart.jp 2016

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 登録済みカード情報の削除
if( $mode == 'delete' ){
	fn_pygnt_delete_card_info($auth['user_id']);
	return array(CONTROLLER_STATUS_REDIRECT, "paygent_card_info.view");

// 登録済みカード情報の確認
}elseif( $mode == 'view' ){
	// パン屑リストを生成
	fn_add_breadcrumb(__('jp_paygent_ccreg_registered_card'));

	// 登録済みカード情報を取得
	$registered_card = fn_pygnt_get_registered_card_info($auth['user_id']);

	Registry::get('view')->assign('registered_card', $registered_card);
}
