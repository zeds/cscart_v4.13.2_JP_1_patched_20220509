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

// $Id: addons_rma_slip_notification.php by tommy from cs-cart.jp 2016
// 返品申請の受付、もしくは申請ステータスが変更された場合に送信されるメールで使用可能なテンプレート変数

use Tygh\Tools\SecurityHelper;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// 注文情報
	$tpl_order_info = $tpl_base_data['order_info']->value;

	// 注文が実行された際のお客様選択言語コード
	if ($tpl_order_info['lang_code']) {
		$mtpl_lang_code = $tpl_order_info['lang_code'];
	} else {
		$mtpl_lang_code = DESCR_SL;
	}

	// 返品申請情報
	$tpl_return_info = $tpl_base_data['return_info']->value;

	// 返品申請ステータス
	$return_statuses = fn_get_statuses(STATUSES_RETURN, array(), true, false, $mtpl_lang_code);

	// 当該返品申請のステータスに関する情報
	$tpl_return_status_info = $tpl_return_info['status'];

	// 当該返品申請のステータス名
	$tpl_return_status = $return_statuses[$tpl_return_status_info['status']]['description'];
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
		'HEADER' => 
				array('desc' => 'email_header', 
						'value' => empty($_edit_mail_tpl) ? mb_ereg_replace("/br/", "\n", SecurityHelper::escapeHtml($tpl_base_data['return_status']->value['email_header'], true)) : ''),
		'ORDER_ID' => 
				array('desc' => 'order_id', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['order_id'] : ''),
		'STATUS' => 
				array('desc' => 'order_status', 
						'value' => empty($_edit_mail_tpl) ? $tpl_return_status : ''),
		'RETURN_ID' => 
				array('desc' => 'mtpl_return_id', 
						'value' => empty($_edit_mail_tpl) ? $tpl_return_info['return_id'] : ''),
		'SUBJECT' => 
				array('desc' => 'email_subject', 
						'value' => empty($_edit_mail_tpl) ? $tpl_base_data['return_status']->value['email_subj'] : ''),
		'ACTION' => 
				array('desc' => 'what_you_would_like_to_do', 
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_rma_property($tpl_return_info['action'], $mtpl_lang_code) : ''),
		'P_BLK' => 
				array('desc' => 'products', 
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_return_products($tpl_order_info['products'], $tpl_return_info, $tpl_base_data) : ''),
		'COMMENT' => 
				array('desc' => 'notes', 
						'value' => empty($_edit_mail_tpl) ? ($tpl_return_info['comment']) ? fn_mtpl_wordwrap( SecurityHelper::escapeHtml($tpl_return_info['comment'], true), 40, "\n", true) : '' : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_addons_rma_slip_notification', $mail_tpl_var, $tpl_order_info, $tpl_return_info, $return_statuses, $tpl_return_status_info, $tpl_return_status, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
