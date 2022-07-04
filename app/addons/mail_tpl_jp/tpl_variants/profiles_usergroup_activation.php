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

// $Id: profiles_usergroup_activation.php by tommy from cs-cart.jp 2016
// ユーザーグループが有効化された際に送信されるメールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// ユーザーに関するデータ
	$tpl_user_data = $tpl_base_data['user_data']->value;

    // ユーザーグループに関するデータ
    $tpl_usergroup_data = $tpl_base_data['usergroups']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーの言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	// メールテンプレート編集ページ以外の場合
	if( empty($_edit_mail_tpl) && !empty($tpl_user_data['user_id']) ) {
		$mtpl_lang_code = $tpl_user_data['lang_code'];
		$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code, '', $tpl_user_data['user_id']);
	}else{
		$mtpl_lang_code = $tpl_user_data['lang_code'];
		$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
	}
}
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
$mail_tpl_var = 
	array(
		'U_ID' => 
				array('desc' => 'user_id', 
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['user_id'] : ''),
		'U_LOGIN' => 
				array('desc' => 'username', 
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['user_login'] : ''),
		'U_LASTNAME' => 
				array('desc' => 'first_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['firstname'] : ''),
		'U_FIRSTNAME' => 
				array('desc' => 'last_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['lastname'] : ''),
		'U_USERGROUPS' => 
				array('desc' => 'usergroup',
						'value' => empty($_edit_mail_tpl) ? $tpl_usergroup_data : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_profiles_usergroup_activation', $mail_tpl_var, $tpl_user_data, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
