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

// $Id: product_back_in_stock_notification.php by tommy from cs-cart.jp 2015
// 入荷通知メールで使用可能なテンプレート変数

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// 一般設定に関するデータ
	$tpl_general_settngs = Registry::get('settings.General');

	// CS-Cartの基本設定データ
	$tpl_config = $tpl_base_data['config']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーの言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mtpl_lang_code = CART_LANGUAGE;
	$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
}
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
$tpl_protocol = "";

if( Registry::get('settings.Security.secure_storefront') != 'none' ){
    $tpl_protocol = 'https';
}else{
    $tpl_protocol = 'http';
}

$mail_tpl_var = 
	array(
	    /*
		'C_NAME' => 
				array('desc' => 'mtpl_friend_name', 
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_customer_name_by_email($email_to) : ''),
	    */
		'P_NAME' => 
				array('desc' => 'product_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_base_data['product']->value['name'] : ''),
		'URL' => 
				array('desc' => 'mtpl_product_link', 
						'value' => empty($_edit_mail_tpl) ? fn_url('products.view?product_id=' . $tpl_base_data['product_id']->value, 'C', $tpl_protocol, "&") : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_products_back_in_stock_notification', $mail_tpl_var, $tpl_general_settngs, $tpl_config, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
