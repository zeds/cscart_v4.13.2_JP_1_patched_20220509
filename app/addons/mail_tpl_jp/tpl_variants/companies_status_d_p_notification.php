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

// $Id: companies_status_d_p_notification.php by takahashi from cs-cart.jp 2017
// サプライヤー（出品者）保留通知メールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
    // サプライヤー（出品者）情報
    $tpl_company_data = $tpl_base_data['company_data']->value;

    // 理由
    $tpl_reason = $tpl_base_data['reason']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとサプライヤー（出品者）の言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
    $mtpl_lang_code = CART_LANGUAGE;//$tpl_company_data['lang_code'];
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
        'COMPANY_NAME' =>
                array('desc' => 'company_name',
                        'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_company_data['company_name'], ENT_QUOTES, 'UTF-8') : ''),
        'REASON' =>
            array('desc' => 'reason',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_reason, ENT_QUOTES, 'UTF-8') : ''),
    );

if( empty($_edit_mail_tpl) ) {
    fn_set_hook('mail_tpl_var_companies_status_d_p_notification', $mail_tpl_var, $tpl_company_data, $tpl_reason, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
