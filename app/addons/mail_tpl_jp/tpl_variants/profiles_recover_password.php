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

// $Id: profiles_recover_password.php by tommy from cs-cart.jp 2016
// パスワードの再設定メールで使用可能なテンプレート変数
use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// パスワード再発行用URLをセット
	$tpl_link = $tpl_base_data['url']->value;

    if( Registry::get('settings.Security.secure_storefront') != 'none' ){
        $tpl_link = str_replace('http://', 'https://', $tpl_link);
    }

}else{
	$tpl_link = '';
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
$mail_tpl_var = 
	array(
		'LINK' => 
				array('desc' => 'mtpl_pass_recovery_link', 
						'value' => $tpl_link),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_profiles_recover_password', $mail_tpl_var, $tpl_link, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
