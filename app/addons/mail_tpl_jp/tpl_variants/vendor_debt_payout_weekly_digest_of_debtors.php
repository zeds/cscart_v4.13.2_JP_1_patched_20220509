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

// $Id: vendor_debt_payout_weekly_digest_of_debtors.php by takahashi from cs-cart.jp 2021
// 出品者債務管理アドオン週次レポート送信時に有効なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
    // 言語
    $tpl_lang_code = $tpl_base_data['lang_code']->value;

    // URL
    $tpl_url = $tpl_base_data['href']->value;

    // 一時停止となった出品者一覧
    $tpl_suspended_vendors = __("vendor_debt_payout.empty_list_of_vendors_got_suspended_label") . "\r\n";
    if($tpl_base_data['suspended_vendors']->value) {
        $tpl_suspended_vendors = __("vendor_debt_payout.list_of_vendors_got_suspended_label") . "\r\n\r\n";
        $tpl_suspended_vendors .= __("vendor") . ": " . __("vendor_debt_payout.debt_owned") . "\r\n";
        foreach($tpl_base_data['suspended_vendors']->value as $vendor){
            $tpl_suspended_vendors .= $vendor['company'] . ": " . $vendor['debt'] . "\r\n";
        }
    }

    // 有効になった出品者
    $tpl_active_vendors = __("vendor_debt_payout.empty_list_of_vendors_got_suspended_and_paid_label") . "\r\n";
    if($tpl_base_data['active_vendors']->value) {
        $tpl_active_vendors = __("vendor_debt_payout.list_of_vendors_got_suspended_and_paid_label") . "\r\n\r\n";
        $tpl_active_vendors .= __("vendor") . ": " . __("vendor_debt_payout.account_balance") . "\r\n";
        foreach($tpl_base_data['active_vendors']->value as $vendor){
            $tpl_active_vendors .= $vendor['company'] . ": " . $vendor['balance'] . "\r\n";
        }
    }

    // 負債合計
    $tpl_total_debt = $tpl_base_data['total_debt']->value;

}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと言語コードでメールテンプレートを抽出
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
        'URL' =>
            array('desc' => 'url',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_url, ENT_QUOTES, 'UTF-8') : ''),
        'SUSPENDED_VENDOR' =>
            array('desc' => 'suspended_vendor_list',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_suspended_vendors, ENT_QUOTES, 'UTF-8') : ''),
        'ACTIVE_VENDOR' =>
            array('desc' => 'activated_vendor_list',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_active_vendors, ENT_QUOTES, 'UTF-8') : ''),
        'TOTAL_DEBT' =>
            array('desc' => 'total_debt',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_total_debt, ENT_QUOTES, 'UTF-8') : ''),
    );

if( empty($_edit_mail_tpl) ) {
    fn_set_hook('mail_tpl_var_vendor_debt_payout_weekly_digest_of_debtors', $mail_tpl_var, $tpl_company, $tpl_reason, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
