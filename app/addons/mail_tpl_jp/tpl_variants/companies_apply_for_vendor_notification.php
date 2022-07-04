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

// $Id: companies_apply_for_vendor_notification.php by tommy from cs-cart.jp 2013
// マーケットプレイスへの参加申請があった際にシステム管理者に送信されるメールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// 出品者情報
$tpl_company = $tpl_base_data['company']->value;
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと出品者の言語コードでメールテンプレートを抽出
$mtpl_lang_code = $tpl_company['lang_code'];
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
						'value' => html_entity_decode($tpl_company['company'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_DESCRIPTION' => 
				array('desc' => 'description', 
						'value' => html_entity_decode($tpl_company['company_description'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_URL' => 
				array('desc' => 'url', 
						'value' => html_entity_decode($tpl_company['url'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_EMAIL' =>
				array('desc' => 'email', 
						'value' => html_entity_decode($tpl_company['email'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_PHONE' => 
				array('desc' => 'phone', 
						'value' => html_entity_decode($tpl_company['phone'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_FAX' => 
				array('desc' => 'fax', 
						'value' => html_entity_decode($tpl_company['fax'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_COUNTRY' => 
				array('desc' => 'country', 
						'value' => html_entity_decode($tpl_company['country'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_ZIPCODE' => 
				array('desc' => 'zip_postal_code', 
						'value' => html_entity_decode($tpl_company['zipcode'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_STATE' => 
				array('desc' => 'state', 
						'value' => html_entity_decode($tpl_company['state'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_CITY' => 
				array('desc' => 'city', 
						'value' => html_entity_decode($tpl_company['city'], ENT_QUOTES, 'UTF-8') ),
		'COMPANY_ADDRESS' => 
				array('desc' => 'address', 
						'value' => html_entity_decode($tpl_company['address'], ENT_QUOTES, 'UTF-8') ),
	);

fn_set_hook('mail_tpl_var_companies_apply_for_vendor_notification', $mail_tpl_var, $tpl_company, $mail_template);
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
