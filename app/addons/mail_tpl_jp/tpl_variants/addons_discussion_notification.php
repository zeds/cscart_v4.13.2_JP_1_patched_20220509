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

// $Id: addons_discussion_notification.php by tommy from cs-cart.jp 2016
// ユーザーからコメントが登録された際に送信されるメールで使用可能なテンプレート変数

use Tygh\Tools\SecurityHelper;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// CS-Cartの基本設定データ
	$tpl_config = $tpl_base_data['config']->value;

	// 投稿情報
	$tpl_post_data = $tpl_base_data['post_data']->value;

	// レビューの評価
	$tpl_rating = '';
	if ($tpl_post_data['rating_value']) {
		$tpl_rating = "\n" . __('rating') . " : " . fn_mtpl_get_review($tpl_post_data['rating_value'] . "\n");
	}
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
		'O_NAME' => 
				array('desc' => 'mtpl_comment_object_name', 
						'value' => empty($_edit_mail_tpl) ? __($tpl_base_data['object_name']->value) : ''),
		'O_DESC' => 
				array('desc' => 'mtpl_comment_object_desc', 
						'value' => empty($_edit_mail_tpl) ? $tpl_base_data['object_data']->value['description'] : ''),
		'P_NAME' => 
				array('desc' => 'author', 
						'value' => empty($_edit_mail_tpl) ? $tpl_post_data['name'] : ''),
		'P_RATING' => 
				array('desc' => 'rating', 
						'value' => empty($_edit_mail_tpl) ? $tpl_rating . "\n" : ''),
		'P_COMMENTS' => 
				array('desc' => 'comments', 
						'value' => empty($_edit_mail_tpl) ? ($tpl_post_data['message']) ? "\n" . __('comments') . " : \n" . $tpl_post_data['message'] . "\n" : '' : ''),
		'P_APPROVAL' => 
				array('desc' => 'mtpl_comments_approval', 
						'value' => empty($_edit_mail_tpl) ? ($tpl_post_data['status'] == 'N') ? "\n" . __('text_approval_notice') . "\n" : '' : ''),
		'P_URL' => 
				array('desc' => 'url', 
						'value' => empty($_edit_mail_tpl) ? SecurityHelper::escapeHtml($tpl_base_data['url']->value, true) : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_addons_discussion_notification', $mail_tpl_var, $tpl_config, $tpl_post_data, $tpl_rating, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
