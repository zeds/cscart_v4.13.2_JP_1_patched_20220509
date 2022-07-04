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

// $Id: companies_payment_notification.php by tommy from cs-cart.jp 2013
// 出品者に対する支払い情報を追加した際に送信されるメールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// 出品者情報
$tpl_company_data = $tpl_base_data['company_data']->value;

// 支払情報
$tpl_payment_data = $tpl_base_data['payment']->value;
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと出品者の言語コードでメールテンプレートを抽出
$mtpl_lang_code = $tpl_company_data['lang_code'];
$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
$mail_tpl_var = 
	array(
		'COMPANY_NAME' => 
				array('desc' => 'company_name', 
						'value' => html_entity_decode($tpl_company_data['company_name'], ENT_QUOTES, 'UTF-8') ),
		'PAYMENT_START_DATE' => 
				array('desc' => 'from_date', 
						'value' => $tpl_payment_data['start_date'] ),
		'PAYMENT_END_DATE' => 
				array('desc' => 'to_date', 
						'value' => $tpl_payment_data['end_date'] ),
		'PAYMENT_AMOUNT' => 
				array('desc' => 'payment_amount', 
						'value' => fn_mtpl_get_display_price($tpl_payment_data['amount'], $tpl_base_data) ),
		'PAYMENT_METHOD' => 
				array('desc' => 'payment_method', 
						'value' => $tpl_payment_data['payment_method'] ),
		'PAYMENT_COMMENTS' => 
				array('desc' => 'comments', 
						'value' => $tpl_payment_data['comments'] ),
	);

fn_set_hook('mail_tpl_var_companies_payment_notification', $mail_tpl_var, $tpl_payment_data, $mail_template);
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
