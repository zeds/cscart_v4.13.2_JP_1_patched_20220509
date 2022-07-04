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

// $Id: vendor_debt_payout_email_admin_notification_vendor_status_changed_to_disabled.php by takahashi from cs-cart.jp 2021
// 出品者債務管理アドオンで出品者のステータスが無効となった場合に有効なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
    // 出品者情報
    $tpl_company = $tpl_base_data['company']->value;

    // 理由
    $tpl_reason = " ";
    if($tpl_base_data['reason']->value) {
        $tpl_reason = __("reason").": ".$tpl_base_data['reason']->value;
    }
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと出品者の言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
    $mtpl_lang_code = $tpl_company['lang_code'];
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
        'VENDOR' =>
            array('desc' => 'vendor',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_company['company_name'], ENT_QUOTES, 'UTF-8') : ''),
        'REASON' =>
            array('desc' => 'reason',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_reason, ENT_QUOTES, 'UTF-8') : ''),
    );

if( empty($_edit_mail_tpl) ) {
    fn_set_hook('mail_tpl_var_vendor_debt_payout_email_admin_notification_vendor_status_changed_to_disabled', $mail_tpl_var, $tpl_company, $tpl_reason, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
