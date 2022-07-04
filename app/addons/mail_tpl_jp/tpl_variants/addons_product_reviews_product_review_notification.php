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

// $Id: addons_product_reviews_product_review_notification.notify_admin.php by takahashi from cs-cart.jp 2021
// お客様が「商品レビュー」を投稿した際に送信されるメールで使用可能なテンプレート変数

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// 商品レビューデータ
$tpl_product_review_data = $tpl_base_data['product_review_data']->value;

// 商品レビューURL
$tpl_product_review_url = $tpl_base_data['product_review_url']->value;

// 商品URL
$tpl_product_url = $tpl_base_data['product_url']->value;
$tpl_product_url = str_replace('product_products', 'products', $tpl_product_url);

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
$product_rating_text = '';
$product_rating_icon = '';
switch($tpl_product_review_data['rating_value']){
    case 5:
        $product_rating_text = __("product_reviews.excellent");
        $product_rating_icon = __("product_reviews.five_star_icon");
        break;
    case 4:
        $product_rating_text = __("product_reviews.very_good");
        $product_rating_icon = __("product_reviews.four_star_icon");
        break;
    case 3:
        $product_rating_text = __("product_reviews.average");
        $product_rating_icon = __("product_reviews.three_star_icon");
        break;
    case 2:
        $product_rating_text = __("product_reviews.fair");
        $product_rating_icon = __("product_reviews.two_star_icon");
        break;
    case 1:
        $product_rating_text = __("product_reviews.poor");
        $product_rating_icon = __("product_reviews.one_star_icon");
        break;
}

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
        'PERSON_NAME' =>
            ['desc' => 'person_name',
                'value' => html_entity_decode($tpl_product_review_data['user_data']['name'], ENT_QUOTES, 'UTF-8') ],
        'RATING_TEXT' =>
            ['desc' => 'rating_text',
                'value' => html_entity_decode($product_rating_text, ENT_QUOTES, 'UTF-8') ],
        'RATING_ICON' =>
            ['desc' => 'rating_icon',
                'value' => html_entity_decode($product_rating_icon, ENT_QUOTES, 'UTF-8') ],
        'ADVANTAGE' =>
            ['desc' => 'product_reviews.advantages',
                'value' => html_entity_decode(isset($tpl_product_review_data['message']['advantages']) ? $tpl_product_review_data['message']['advantages'] : '', ENT_QUOTES, 'UTF-8') ],
        'DISADVANTAGE' =>
            ['desc' => 'product_reviews.disadvantages',
                'value' => html_entity_decode(isset($tpl_product_review_data['message']['disadvantages']) ? $tpl_product_review_data['message']['disadvantages'] : '', ENT_QUOTES, 'UTF-8') ],
        'COMMENT' =>
            ['desc' => 'product_reviews.comment',
                'value' => html_entity_decode($tpl_product_review_data['message']['comment'], ENT_QUOTES, 'UTF-8') ],
        'PRODUCT_REVIEW_URL' =>
            ['desc' => 'product_reviews_url',
                'value' => html_entity_decode($tpl_product_review_url, ENT_QUOTES, 'UTF-8') ],
    ];

fn_set_hook('mail_tpl_var_addons_product_reviews_product_review_notification', $mail_tpl_var, $tpl_product_review_data, $tpl_product_review_url, $tpl_product_url, $tpl_user_data, $mail_template);
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////