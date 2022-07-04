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

// $Id: addons_reward_points_notification.php by tommy from cs-cart.jp 2015
// ポイントの加算・減算をお客様に通知するメールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// ユーザーに関するデータ
	$tpl_user_data = $tpl_base_data['user_data']->value;

	// ポイント付与・減算に関するデータ
	$tpl_reason = $tpl_base_data['reason']->value;
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
$mail_tpl_var = 
	array(
		'LASTNAME' => 
				array('desc' => 'first_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['firstname'] : ''),
		'MSG' => 
				array('desc' => 'message', 
						'value' => empty($_edit_mail_tpl) ? $tpl_reason['amount'] . __('points') . ( ($tpl_reason['action'] == 'A') ? __('reward_points_subj_added_to') : __('reward_points_subj_subtracted_from') ) : ''),
		'REASON' => 
				array('desc' => 'reason', 
						'value' => empty($_edit_mail_tpl) ? ($tpl_reason['reason']) ? $tpl_reason['reason'] : '' : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_addons_reward_points_notification', $mail_tpl_var, $tpl_user_data, $tpl_reason, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
