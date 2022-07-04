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

// $Id: checkout.pre.php by tommy from cs-cart.jp 2016
// 登録済みカード情報の取得

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 登録済みカード情報を取得
if( $mode == 'checkout' && !empty($auth['user_id']) ){

	$krnkwc_registered_card = fn_krnkwc_get_registered_card_info($auth['user_id']);

	if( !empty($krnkwc_registered_card) ){
		Tygh::$app['view']->assign('krnkwc_registered_card', $krnkwc_registered_card);
	}
}
