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

// $Id: companies_status_notification.php by takahashi from cs-cart.jp 2017
// サプライヤー（出品者）ステータスの変更通知メールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
    // サプライヤー（出品者）情報
    $tpl_company = $tpl_base_data['company']->value;

    // ステータスfrom
    $tpl_status_from = $tpl_base_data['status_from']->value;

    // ステータスto
    $tpl_status_to = $tpl_base_data['status_to']->value;

    // 理由
    $tpl_reason = " ";
    if($tpl_base_data['reason']->value) {
        $tpl_reason = __("reason").": ".$tpl_base_data['reason']->value;
    }

    // ステータス
    $tpl_status = $tpl_base_data['status']->value;

    // e_account
    $tpl_e_account = $tpl_base_data['e_account']->value;

    // ユーザー名
    $tpl_e_username = $tpl_base_data['e_username']->value;

    // URL
    $tpl_vendor_url = $tpl_base_data['vendor_url']->value;

    // パスワード
    $tpl_e_password = $tpl_base_data['e_password']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとサプライヤー（出品者）の言語コードでメールテンプレートを抽出
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
        'STATUS_FROM' =>
            array('desc' => 'status_from',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode(fn_get_status($tpl_status_from), ENT_QUOTES, 'UTF-8') : ''),
        'STATUS_TO' =>
            array('desc' => 'status_to',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode(fn_get_status($tpl_status_to), ENT_QUOTES, 'UTF-8') : ''),
        'REASON' =>
            array('desc' => 'reason',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_reason, ENT_QUOTES, 'UTF-8') : ''),
        'ADMIN_MESSAGE' =>
            array('desc' => 'admin_message',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode(fn_get_admin_message($tpl_e_account, $tpl_vendor_url, $tpl_e_username, $tpl_e_password), ENT_QUOTES, 'UTF-8') : ''),
        'URL' =>
            array('desc' => 'url',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_vendor_url, ENT_QUOTES, 'UTF-8') : ''),
        'EMAIL' =>
            array('desc' => 'email',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_e_username, ENT_QUOTES, 'UTF-8') : ''),
        'PASSWORD' =>
            array('desc' => 'password',
                'value' => empty($_edit_mail_tpl) ? html_entity_decode($tpl_e_password, ENT_QUOTES, 'UTF-8') : ''),
    );

if( empty($_edit_mail_tpl) ) {
    fn_set_hook('mail_tpl_var_companies_status_notification', $mail_tpl_var, $tpl_company, $tpl_status_from, $tpl_status_to, $tpl_reason,$tpl_e_account, $tpl_e_username, $tpl_vendor_url, $tpl_e_password, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
/**
 * ステータス名を取得
 *
 * @param $status_code
 * @return string
 */
function fn_get_status($status_code)
{
    $status = "";

    switch ($status_code){
        case "A":
            $status = __("company_status_A");
            break;
        case "D":
            $status = __("company_status_D");
            break;
        case "P":
            $status = __("company_status_P");
            break;
        case "S":
            $status = __("company_status_S");
            break;
        default:
            $status = __("company_status_N");
            break;

    }

    return $status;
}

/**
 * Eメールの管理者ステータス変更メッセージを取得
 *
 * @param $tpl_e_account
 * @param $tpl_e_username
 * @param $tpl_vendor_url
 * @param $tpl_e_password
 * @return string
 */
function fn_get_admin_message($tpl_e_account, $tpl_vendor_url, $tpl_e_username, $tpl_e_password){
    $message = "";
    if ($tpl_e_account == 'updated') {
        $message = __("company_admin_message_update").__("msg_access_admin_url").": ".$tpl_vendor_url."\n".__("user").": ".$tpl_e_username;;
    } elseif ($tpl_e_account == 'new') {
        $message = __("company_admin_message_new").__("msg_access_admin_url").": ".$tpl_vendor_url."\n".__("user").": ".$tpl_e_username."\n".__("password").": ".$tpl_e_password;;
    }
    return $message;
}
