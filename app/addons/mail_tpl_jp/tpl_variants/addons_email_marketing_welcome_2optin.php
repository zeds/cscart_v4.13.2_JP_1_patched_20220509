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

// $Id: addons_email_marketing_welcome_2optin.php by tommy from cs-cart.jp 2015
// ダブルオプトイン方式のメルマガ購読受付完了時に送信されるメールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// 購読解除用URL
	$tpl_url = $tpl_base_data['url']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーが使用中の言語コードでメールテンプレートを抽出
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
$mail_tpl_var = array(
	'UNSUBSCRIBE_URL' =>
		array('desc' => 'mtpl_email_marketing_unsubscribe_url',
			'value' => empty($_edit_mail_tpl) ? $tpl_url : ''),
);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_addons_email_marketing_welcome_2optin', $mail_tpl_var, $tpl_url);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
