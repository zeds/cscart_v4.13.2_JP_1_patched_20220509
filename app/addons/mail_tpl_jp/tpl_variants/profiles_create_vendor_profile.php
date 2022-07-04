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

// $Id: profiles_create_vendor_profile.php by tommy from cs-cart.jp 2015
// 管理画面から出品者アカウントを登録した際に自動で作成された出品者データ管理者
// の情報を送信するメールで使用可能なテンプレート変数

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// ユーザーに関するデータ
	$tpl_user_data = $tpl_base_data['user_data']->value;

	// 一般設定に関するデータ
	$tpl_general_settngs = Registry::get('settings.General');
}else{
	$tpl_user_data = array();
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーの言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mtpl_lang_code = $tpl_user_data['lang_code'];
	$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
}
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
// 連絡先情報
$mail_tpl_var =
	array(
		// 出品者名
		'COMPANY_NAME' =>
			array('desc' => 'vendor_name',
				'value' => empty($_edit_mail_tpl) ? $tpl_user_data['company'] : ''),
		// メールアドレス
		'U_EMAIL' =>
			array('desc' => 'emails',
				'value' => empty($_edit_mail_tpl) ? $tpl_user_data['email'] : ''),
		// メールアドレス
		'PASSWORD' =>
			array('desc' => 'password',
				'value' => empty($_edit_mail_tpl) ? $tpl_base_data['password']->value : ''),
		// ログインURL
		'LOGIN_URL' =>
			array('desc' => 'mtpl_login_url',
				'value' => empty($_edit_mail_tpl) ? fn_url('', 'V') : ''),
		);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_profiles_create_vendor_profile', $mail_tpl_var, $tpl_user_data, $tpl_general_settngs, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
