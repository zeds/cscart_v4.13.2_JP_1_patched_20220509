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

// $Id: addons_vendor_plans_companies_plan_changed.php by takahashi from cs-cart.jp 2018
// 出品者のプランを変更した時に送られるメールで使用可能なテンプレート変数

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// プラン情報
$tpl_plan_data = $tpl_base_data['plan']->value;

// URL
$tpl_url_data = $tpl_base_data['url']->value;

// コミッション
$tpl_commission = $tpl_plan_data['current_attributes']['commission'] . ' (%) + ' . number_format($tpl_plan_data['current_attributes']['fixed_commission']);

// 出品者名
$tpl_vendor_name = $tpl_base_data['vendor_name']->value;

/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーが使用中の言語コードでメールテンプレートを抽出
$mtpl_lang_code = $tpl_plan_data['params']['lang_code'];
$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
$mail_tpl_var =
    array(
        'VENDOR_NAME' =>
            array('desc' => 'vendor_name',
                'value' => html_entity_decode($tpl_vendor_name, ENT_QUOTES, 'UTF-8') ),
        'PLAN_NAME' =>
            array('desc' => 'plan_name',
                'value' => html_entity_decode($tpl_plan_data['current_attributes']['plan'], ENT_QUOTES, 'UTF-8') ),
        'AMOUNT' =>
            array('desc' => 'amount',
                'value' => html_entity_decode(fn_format_price($tpl_plan_data['current_attributes']['price']), ENT_QUOTES, 'UTF-8') ),
        'PERIOD' =>
            array('desc' => 'period',
                'value' => html_entity_decode(__("vendor_plans.periodicity_".$tpl_plan_data['current_attributes']['periodicity']), ENT_QUOTES, 'UTF-8') ),
        'COMISSION' =>
            array('desc' => 'commission',
                'value' => html_entity_decode($tpl_commission, ENT_QUOTES, 'UTF-8') ),
        'PRODUCTS_LIMIT' =>
            array('desc' => 'products_limit',
                'value' => html_entity_decode($tpl_plan_data['current_attributes']['products_limit'], ENT_QUOTES, 'UTF-8') ),
        'REVENUE_LIMIT' =>
            array('desc' => 'revenue_limit',
                'value' => html_entity_decode(fn_format_price($tpl_plan_data['current_attributes']['revenue_limit']), ENT_QUOTES, 'UTF-8') ),
        'IS_SHOP' =>
            array('desc' => 'is_shop',
                'value' => html_entity_decode($tpl_plan_data['current_attributes']['vendor_store'] == 1 ? __("Yes") : __("No"), ENT_QUOTES, 'UTF-8') ),
        'URL' =>
            array('desc' => 'url',
                'value' => html_entity_decode($tpl_url_data, ENT_QUOTES, 'UTF-8') ),
    );

fn_set_hook('mail_tpl_var_vendor_communication_notify_admin', $mail_tpl_var, $tpl_plan_data, $tpl_url_data,  $tpl_commission, $tpl_vendor_name, $mail_template);
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////