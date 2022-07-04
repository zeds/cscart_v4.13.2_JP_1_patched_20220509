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

// $Id: addons_gift_certificates_gift_certificate.php by tommy from cs-cart.jp 2016
// ギフト券の発行をお客様に通知するメールで使用可能なテンプレート変数

use Tygh\Tools\SecurityHelper;
use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// ギフト券に関するデータ
	$tpl_gift_cert_data = $tpl_base_data['gift_cert_data']->value;

    // CS-Cartの基本設定データ
    $tpl_config = Registry::get('config');
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと管理者が使用中の言語コードでメールテンプレートを抽出
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
$tpl_config_location = "";

if( Registry::get('settings.Security.secure_storefront') != 'none' ){
    $tpl_config_location = $tpl_config['https_location'];
}else{
    $tpl_config_location = $tpl_config['http_location'];
}

$mail_tpl_var = 
	array(
		'GIFT_TO' => 
				array('desc' => 'gift_cert_to', 
						'value' => empty($_edit_mail_tpl) ? $tpl_gift_cert_data['recipient'] : ''),
		'GIFT_FROM' => 
				array('desc' => 'gift_cert_from', 
						'value' => empty($_edit_mail_tpl) ? $tpl_gift_cert_data['sender'] : ''),
		'HEADER' => 
				array('desc' => 'email_header', 
						'value' => empty($_edit_mail_tpl) ? SecurityHelper::escapeHtml($tpl_base_data['certificate_status']->value['email_header'], true) : ''),
		'CODE' => 
				array('desc' => 'gift_cert_code', 
						'value' => empty($_edit_mail_tpl) ? $tpl_gift_cert_data['gift_cert_code'] : ''),
		'AMOUNT' => 
				array('desc' => 'amount', 
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_display_price($tpl_gift_cert_data['amount'], $tpl_base_data) : ''),
		'ADDRESS' => 
				array('desc' => 'ship_to', 
						'value' => empty($_edit_mail_tpl) ? ($tpl_gift_cert_data['send_via'] == 'P') ? fn_mtpl_get_gc_address($tpl_gift_cert_data) : '' : ''),
		'MESSAGE' => 
				array('desc' => 'message', 
						'value' => empty($_edit_mail_tpl) ? ($tpl_gift_cert_data['message']) ? SecurityHelper::escapeHtml($tpl_gift_cert_data['message'], true) : '' : ''),
		'PRODUCTS' => 
				array('desc' => 'free_products', 
						'value' => empty($_edit_mail_tpl) ? ( $tpl_gift_cert_data['products'] && $tpl_base_data['addons']->value['gift_certificates']['free_products_allow'] == 'Y') ? fn_mtpl_get_free_products($tpl_gift_cert_data['products'], $tpl_base_data) : '' : ''),
		'URL' => 
				array('desc' => 'url', 
						'value' => empty($_edit_mail_tpl) ? $tpl_config_location : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_addons_gift_certificates_gift_certificate', $mail_tpl_var, $tpl_gift_cert_data, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
