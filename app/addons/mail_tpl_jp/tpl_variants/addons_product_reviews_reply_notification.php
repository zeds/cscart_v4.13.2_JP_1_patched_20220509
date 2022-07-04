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

// $Id: addons_product_reviews_reply_notification.notify_admin.php by takahashi from cs-cart.jp 2021
// お客様の「商品レビュー」に管理者が返信する際に送信されるメールで使用可能なテンプレート変数

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// 商品レビューデータ
$tpl_product_review_data = $tpl_base_data['product_review_data']->value;

// 商品URL
$tpl_product_url = $tpl_base_data['product_url']->value;

// ユーザーデータ
$tpl_user_data = $tpl_base_data['user_data']->value;

/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーが使用中の言語コードでメールテンプレートを抽出
$mtpl_lang_code = isset($tpl_user_data['lang_code']) ? $tpl_user_data['lang_code'] : CART_LANGUAGE;
$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////

$mail_tpl_var =
    [
        'U_NAME' =>
            ['desc' => 'user_name',
                'value' => html_entity_decode($tpl_user_data['firstname'], ENT_QUOTES, 'UTF-8') ],
        'PRODUCT_NAME' =>
            ['desc' => 'product_name',
                'value' => html_entity_decode(fn_get_product_name($tpl_product_review_data['product_id']), ENT_QUOTES, 'UTF-8') ],
        'PRODUCT_URL' =>
            ['desc' => 'product_url',
                'value' => html_entity_decode($tpl_product_url, ENT_QUOTES, 'UTF-8') ],
        'REPLY' =>
            ['desc' => 'product_reviews.reply',
                'value' => html_entity_decode($tpl_product_review_data['reply']['reply'], ENT_QUOTES, 'UTF-8') ],
    ];

fn_set_hook('mail_tpl_var_addons_product_reviews_reply_notification', $mail_tpl_var, $tpl_product_review_data, $tpl_product_url, $tpl_user_data, $mail_template);
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////