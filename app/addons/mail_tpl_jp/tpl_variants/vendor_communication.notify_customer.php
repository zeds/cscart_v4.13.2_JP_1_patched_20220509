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

// $Id: vendor_communication.notify_admin.php by takahashi from cs-cart.jp 2018
// 出品者がお客様へチャットメッセージを送信した際に送信されるメールで使用可能なテンプレート変数

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// お客様情報
$tpl_company_data = $tpl_base_data['message_from']->value;

// URL
$tpl_url_data = $tpl_base_data['thread_url']->value;

/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーが使用中の言語コードでメールテンプレートを抽出
$mtpl_lang_code = CART_LANGUAGE;
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
                'value' => html_entity_decode($tpl_company_data, ENT_QUOTES, 'UTF-8') ),
        'URL' =>
            array('desc' => 'url',
                'value' => html_entity_decode($tpl_url_data, ENT_QUOTES, 'UTF-8') ),
    );

fn_set_hook('mail_tpl_var_vendor_communication_notify_customer', $mail_tpl_var, $tpl_company_data, $tpl_url_data,  $mail_template);
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////