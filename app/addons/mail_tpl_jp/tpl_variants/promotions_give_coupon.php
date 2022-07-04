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

// $Id: promotions_give_coupons.php by tommy from cs-cart.jp 2015
// クーポン発行通知メールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// 注文情報
	$tpl_order_info = $tpl_base_data['order_info']->value;

	// キャンペーン情報
	$tpl_promotion_data = $tpl_base_data['promotion_data']->value;

	// 特典情報
	$tpl_bonus_data = $tpl_base_data['bonus_data']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと注文が実行された際のお客様選択言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mtpl_lang_code = $tpl_order_info['lang_code'];
	$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
}
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
$mail_tpl_var = 
	array(
		'LASTNAME' => 
				array('desc' => 'first_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['firstname'] : ''),
		'FIRSTNAME' => 
				array('desc' => 'last_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['lastname'] : ''),
		'PROMO_NAME' => 
				array('desc' => 'mtpl_promotion_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_promotion_data['name'] : ''),
		'PROMO_NAME' => 
				array('desc' => 'mtpl_promotion_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_promotion_data['name'] : ''),
		'COUPON_CODE' => 
				array('desc' => 'coupon_code', 
						'value' => empty($_edit_mail_tpl) ? $tpl_bonus_data['coupon_code'] : ''),
		'COUPON_DESC' => 
				array('desc' => 'mtpl_coupon_desc', 
						'value' => empty($_edit_mail_tpl) ? $tpl_promotion_data['detailed_description'] : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_promotions_give_coupons', $mail_tpl_var, $tpl_order_info, $tpl_promotion_data, $tpl_bonus_data, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
