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

// $Id: companies_accounting_new_withdrawal.php by takahashi from cs-cart.jp 2017
// 出品者入出金管理：引き出し要求メールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
    // 支払情報
    $tpl_payment_data = $tpl_base_data['payment']->value;

    // 出品者＞出品者入出金管理URL
    $tpl_account_url = $tpl_base_data['accounting_url']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとサプライヤー（出品者）の言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mtpl_lang_code = fn_mtpl_get_lang_from_company($tpl_payment_data['vendor']);
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
        'AMOUNT' =>
            array('desc' => 'amount',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_payment_data['amount'], ENT_QUOTES, 'UTF-8') : ''),
        'INITIATOR' =>
            array('desc' => 'requestor',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_payment_data['initiator'], ENT_QUOTES, 'UTF-8') : ''),
        'URL' =>
            array('desc' => 'accounting_url',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_account_url, ENT_QUOTES, 'UTF-8') : ''),
        'COMMENTS' =>
            array('desc' => 'comments',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_payment_data['comments'], ENT_QUOTES, 'UTF-8') : ''),
    );

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_companies_accounting_new_withdrawal', $mail_tpl_var, $tpl_payment_data, $tpl_account_url, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
