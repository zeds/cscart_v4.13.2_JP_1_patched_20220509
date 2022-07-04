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

// $Id: func_installer.php by tommy from cs-cart.jp 2016

use Tygh\Registry;
use Tygh\Settings;

##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################

//////////////////////////////////////////////////////////////
// 各種関数を利用するために必要なコアファイルを読み込み BOF
//////////////////////////////////////////////////////////////
require_once (Registry::get('config.dir.functions') . 'fn.cart.php');
require (Registry::get('config.dir.root') . '/app/controllers/backend/destinations.php');
//////////////////////////////////////////////////////////////
// 各種関数を利用するために必要なコアファイルを読み込み EOF
//////////////////////////////////////////////////////////////


// アドオンのインストール時の動作
function fn_lcjp_configure_japanese_version()
{
    // インストール済みの言語を取得
    $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

    /////////////////////////////////////////////////////////////////////////
    // 日本語以外に追加言語が設定されていない場合は、英語をオフにする BOF
    /////////////////////////////////////////////////////////////////////////
    $other_langs = db_get_field("SELECT COUNT(*) FROM ?:languages WHERE lang_code != ?s AND lang_code != ?s", 'en', 'ja');
    if( empty($other_langs) ){
        db_query("UPDATE ?:languages SET status = 'D' WHERE lang_code != 'ja'");
    }
    /////////////////////////////////////////////////////////////////////////
    // 日本語以外に追加言語が設定されていない場合は、英語をオフにする EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // 基本設定の設定値を変更 BOF
    /////////////////////////////////////////////////////////////////////////
    // 基本設定 -> 全般 の設定内容を変更
    Settings::instance()->updateValue('weight_symbol', 'kg', 'General');
    Settings::instance()->updateValue('weight_symbol_grams', 1000, 'General');
    Settings::instance()->updateValue('use_shipments', 'N', 'General');
    Settings::instance()->updateValue('default_country', 'JP', 'General');
    Settings::instance()->updateValue('default_zipcode', '107-0052', 'General');
    Settings::instance()->updateValue('default_state', '東京都', 'General');
    Settings::instance()->updateValue('default_city', '港区', 'General');
    Settings::instance()->updateValue('default_address', '赤坂1-2-34 CSビル5F', 'General');
    Settings::instance()->updateValue('default_phone', '01-2345-6789', 'General');
    Settings::instance()->updateValue('allow_usergroup_signup', 'N', 'General');
    Settings::instance()->updateValue('min_order_amount_type', 'only_products', 'General');
    Settings::instance()->updateValue('checkout_redirect', 'Y', 'General');
    Settings::instance()->updateValue('repay', 'N', 'General');
    Settings::instance()->updateValue('estimate_shipping_cost', 'N', 'General');
    Settings::instance()->updateValue('monitor_core_changes', 'N', 'General');

    // 基本設定 -> 全般 の住所の並び順を変更
    $_obj_id = Settings::instance()->getId('default_country', 'General');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 70) );
    $_obj_id = Settings::instance()->getId('default_zipcode', 'General');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 80) );
    $_obj_id = Settings::instance()->getId('default_state', 'General');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 90) );
    $_obj_id = Settings::instance()->getId('default_city', 'General');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 100) );
    $_obj_id = Settings::instance()->getId('default_address', 'General');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 110) );

    // 基本設定 -> 表示設定 の設定内容を変更
    Settings::instance()->updateValue('frontend_default_language', 'ja', 'Appearance');
    Settings::instance()->updateValue('backend_default_language', 'ja', 'Appearance');
    Settings::instance()->updateValue('date_format', '%Y/%m/%d', 'Appearance');
    Settings::instance()->updateValue('timezone', 'Asia/Tokyo', 'Appearance');
    Settings::instance()->updateValue('changes_warning', 'N', 'Appearance');
    Settings::instance()->updateValue('phone_validation_mode', 'any_digits', 'Appearance');

    // 基本設定 -> 会社概要 の設定内容を変更
    Settings::instance()->updateValue('company_name', 'CS-Cart.jp', 'Company');
    Settings::instance()->updateValue('company_country', 'JP', 'Company');
    Settings::instance()->updateValue('company_zipcode', '107-0052', 'Company');
    Settings::instance()->updateValue('company_state', '東京都', 'Company');
    Settings::instance()->updateValue('company_city', '港区', 'Company');
    Settings::instance()->updateValue('company_address', '赤坂1-2-34 CSビル5F', 'Company');
    Settings::instance()->updateValue('company_phone', '01-2345-6789', 'Company');
    Settings::instance()->updateValue('company_website', 'https://cs-cart.jp/', 'Company');
    Settings::instance()->updateValue('company_start_year', '2013', 'Company');

    // 基本設定 -> 会社概要 の住所の並び順を変更
    $_obj_id = Settings::instance()->getId('company_country', 'Company');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 10) );
    $_obj_id = Settings::instance()->getId('company_zipcode', 'Company');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 20) );
    $_obj_id = Settings::instance()->getId('company_state', 'Company');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 30) );
    $_obj_id = Settings::instance()->getId('company_city', 'Company');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 40) );
    $_obj_id = Settings::instance()->getId('company_address', 'Company');
    Settings::instance()->update( array('object_id' => $_obj_id, 'position' => 50) );

    // 基本設定 -> 画像認証 の設定内容を変更
    Settings::instance()->updateValue('use_for_login', 'N', 'Image_verification');
    Settings::instance()->updateValue('use_for_register', 'N', 'Image_verification');
    Settings::instance()->updateValue('use_for_checkout', 'N', 'Image_verification');
    Settings::instance()->updateValue('use_for_polls', 'N', 'Image_verification');
    /////////////////////////////////////////////////////////////////////////
    // 基本設定の設定値を変更 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // 一般設定メニューを変更 BOF
    /////////////////////////////////////////////////////////////////////////
    db_query("DELETE FROM ?:privileges WHERE privilege = ?s AND section_id = ?s AND group_id = ?s", 'view_file_changes', 'administration', 'file_changes');
    /////////////////////////////////////////////////////////////////////////
    // 一般設定メニューを変更 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // デフォルトの顧客情報を変更 BOF
    /////////////////////////////////////////////////////////////////////////
    db_query("UPDATE ?:users SET firstname = '鈴木', lastname = '一郎', lang_code = 'ja' WHERE user_login = 'customer'");
    db_query("UPDATE ?:user_profiles SET b_firstname = '鈴木', b_lastname = '一郎', b_country = 'JP', b_zipcode = '107-0052', b_state = '東京都', b_city = '港区', b_address = '赤坂5-6-78', b_address_2 = 'CS第二ビル7F', s_firstname = '鈴木', s_lastname = '一郎', s_country = 'JP', s_zipcode = '107-0052', s_state = '東京都', s_city = '港区', s_address = '赤坂5-6-78', s_address_2 = 'CSビル7F' WHERE user_id = 3 AND profile_id = 2");
    /////////////////////////////////////////////////////////////////////////
    // デフォルトの顧客情報を変更 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // デフォルトのショップ情報を変更 BOF
    /////////////////////////////////////////////////////////////////////////
    $_company_data = array (
        'company' => 'CS-Cart.jp',
        'lang_code' => 'ja',
        'address' => '赤坂1-2-34 CSビル5F',
        'city' => '港区',
        'state' => '東京都',
        'country' => 'JP',
        'zipcode' => '107-0052',
        'email' => 'cscartjp@example.com',
        'phone' => '01-2345-6789',
        'fax' => '',
        'url' => 'https://cs-cart.jp'
    );

    db_query("UPDATE ?:companies SET ?u WHERE company_id = ?i", $_company_data, 1);

    db_query("UPDATE ?:storefronts SET name = ?s WHERE storefront_id = ?i", 'CS-Cart.jp', 1);
    /////////////////////////////////////////////////////////////////////////
    // デフォルトのショップ情報を変更 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // デフォルトの支払方法に関する設定を変更 BOF
    /////////////////////////////////////////////////////////////////////////
    db_query("DELETE FROM ?:payments WHERE payment_id NOT IN (1,6,12)");
    db_query("UPDATE ?:payments SET status = 'A' WHERE payment_id IN (1,6,12)");
    /////////////////////////////////////////////////////////////////////////
    // デフォルトの支払方法に関する設定を変更 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // すべての注文ステータスについて注文管理担当者へ通知 BOF
    // マーケットプレイス版については出品者データ管理者にも通知
    /////////////////////////////////////////////////////////////////////////
    // 注文ステータスに関するステータスIDを取得
    $status_ids = db_get_fields("SELECT status_id FROM ?:statuses WHERE type = ?s", 'O');

    // マーケットプレイス版の場合
    if( fn_allowed_for('MULTIVENDOR') ){
        foreach ($status_ids as $status_id) {
            $_status_data = array(
                'status_id' => $status_id,
                'param' => 'notify_department',
                'value' => 'Y'
            );
            db_query("REPLACE INTO ?:status_data ?e", $_status_data);
            $_status_data = array(
                'status_id' => $status_id,
                'param' => 'notify_vendor',
                'value' => 'Y'
            );
            db_query("REPLACE INTO ?:status_data ?e", $_status_data);
        }

    // その他のエディションの場合
    }else{
        foreach ($status_ids as $status_id) {
            $_status_data = array(
                'status_id' => $status_id,
                'param' => 'notify_department',
                'value' => 'Y'
            );
            db_query("REPLACE INTO ?:status_data ?e", $_status_data);
        }
    }
    /////////////////////////////////////////////////////////////////////////
    // すべての注文ステータスについて注文管理担当者へ通知 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // 日本円を追加し、ベース通貨に設定 BOF
    /////////////////////////////////////////////////////////////////////////
    // 日本円が登録されているかチェック
    $is_exists = db_get_field("SELECT COUNT(*) FROM ?:currencies WHERE currency_code = ?s", 'JPY');

    // 日本円が登録されていない場合、登録してベース通貨に設定する
    if (empty($is_exists)) {
        // 通貨
        $currency = array(
            'currency_code' => 'JPY',
            'coefficient' => 1,
            'symbol'=> '円',
            'after' => 'Y',
            'status' => 'A',
            'thousands_separator'=> ',',
            'decimal_separator' => '.',
            'decimals' => 0,
            'is_primary' => 'Y'
        );

        // 通貨の説明
        $currency_desc = array(
            'currency_code' => $currency['currency_code']
        );

        // 既存通貨のステータスをすべてオフにする
        db_query("UPDATE ?:currencies SET status = 'D'");

        // 既存通貨のベース通貨指定を解除
        $update_data = array('is_primary' => 'N');
        db_query("UPDATE ?:currencies SET ?u WHERE is_primary = ?s", $update_data, 'Y');

        // 日本円をベース通貨として登録
        db_query("REPLACE INTO ?:currencies ?e", $currency);

        foreach ($languages as $currency_desc['lang_code'] => $v) {
            if ($currency_desc['lang_code'] == 'ja' ) {
                $currency_desc['description'] = '日本円';
            }else{
                $currency_desc['description'] = 'Japanese Yen';
            }
            db_query("REPLACE INTO ?:currency_descriptions ?e", $currency_desc);

            // 不要なデータを削除
            db_query("DELETE FROM ?:currency_descriptions WHERE currency_id = ?i", 0);
        }

        // 追加された日本円の currency_id を取得
        $jpy_currency_id = db_get_field("SELECT currency_id FROM ?:currencies WHERE currency_code =?s", 'JPY');

        // 登録済みのすべてのショップにおいて有効にする
        if (fn_allowed_for('ULTIMATE')){
            fn_share_object_to_all('currencies', $jpy_currency_id);
        }

        // 登録済み通貨を取得
        $registered_currencies = db_get_array("SELECT * FROM ?:currencies");

        // USD / EUR / GBP について対円の為替レートをセット
        foreach($registered_currencies as $registered_currency){
            // 為替レートを初期化
            $ex_rate = '';
            switch($registered_currency['currency_code']){
                case 'USD':
                    $ex_rate = 100;
                    break;
                case 'EUR':
                    $ex_rate = 130;
                    break;
                case 'GBP':
                    $ex_rate = 160;
                    break;
                default:
                    // do nothing
            }

            if(!empty($ex_rate)){
                db_query("UPDATE ?:currencies SET coefficient = ?i WHERE currency_code = ?s", $ex_rate, $registered_currency['currency_code']);
            }
        }
    }
    /////////////////////////////////////////////////////////////////////////
    // 日本円を追加し、ベース通貨に設定 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // 会員情報フィールドに姓・名フリガナを追加したうえで表示順を変更 BOF
    /////////////////////////////////////////////////////////////////////////
    // 姓名フリガナ
    $profile_kana = array(
        // 姓フリガナ（連絡先情報）
        'firstname_c' => array(
            'description' => '姓フリガナ',
            'position' => '0',
            'field_name' => 'firstname_kana',
            'field_type' => 'I',
            'section' => 'C',
            'profile_show' => 'N',
            'profile_required' => 'N',
            'checkout_show' => 'N',
            'checkout_required' => 'N',
            'class' => 'first-name-kana'
        ),
        // 名フリガナ（連絡先情報）
        'lastname_c' => array(
            'description' => '名フリガナ',
            'position' => '0',
            'field_name' => 'lastname_kana',
            'field_type' => 'I',
            'section' => 'C',
            'profile_show' => 'N',
            'profile_required' => 'N',
            'checkout_show' => 'N',
            'checkout_required' => 'N',
            'class' => 'last-name-kana'
        ),
        // 姓フリガナ（請求先/配送先住所）
        'firstname_bs' => array(
            'description' => '姓フリガナ',
            'position' => '0',
            'field_name' => 'firstname_kana',
            'field_type' => 'I',
            'section' => 'BS',
            'profile_show' => 'Y',
            'profile_required' => 'Y',
            'checkout_show' => 'Y',
            'checkout_required' => 'Y',
            'class' => 'first-name-kana'
        ),
        // 名フリガナ（請求先/配送先住所）
        'lastname_bs' => array(
            'description' => '名フリガナ',
            'position' => '0',
            'field_name' => 'lastname_kana',
            'field_type' => 'I',
            'section' => 'BS',
            'profile_show' => 'Y',
            'profile_required' => 'Y',
            'checkout_show' => 'Y',
            'checkout_required' => 'Y',
            'class' => 'last-name-kana'
        )
    );

    // 姓名フリガナフィールドを追加し、登録済みのすべてのショップにおいて有効にする
    foreach ($profile_kana as $profile_kana_data){
        $field_id = fn_update_profile_field($profile_kana_data, '');
        // フィールドを登録済みのすべてのショップにおいて有効にする
        if (fn_allowed_for('ULTIMATE')){
            fn_share_object_to_all('profile_fields', $field_id);
        }

        // 配送先/請求先住所欄のフィールドについては、請求先用フィールドに対応する配送先用フィールドも
        // 登録済みのすべてのショップにおいて有効にする
        if (fn_allowed_for('ULTIMATE')){
            $matching_id = db_get_field("SELECT matching_id FROM ?:profile_fields WHERE field_id = ?i", $field_id);
            if (!empty($matching_id)) fn_share_object_to_all('profile_fields', $matching_id);
        }
    }

    // 会員情報フィールドソート順
    $profile_sort_order = array(
        'firstname' => array('position' => 10, 'section' => 'C', 'profile_show' => 'N', 'checkout_show' => 'N'),
        'lastname' => array('position' => 20, 'section' => 'C', 'profile_show' => 'N', 'checkout_show' => 'N'),
        'firstname_kana' => array('field_name' => 'firstname_kana', 'position' => 30, 'section' => 'C', 'profile_show' => 'N', 'checkout_show' => 'N'),
        'lastname_kana' => array('field_name' => 'lastname_kana', 'position' => 40, 'section' => 'C', 'profile_show' => 'N', 'checkout_show' => 'N'),
        'company' => array('position' => 50, 'section' => 'C', 'profile_show' => 'N', 'checkout_show' => 'N'),
        'phone' => array('position' => 60, 'section' => 'C', 'profile_show' => 'N', 'checkout_show' => 'N'),

        'b_firstname' => array('position' => 90, 'profile_required' => 'Y', 'checkout_required' => 'Y', 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        'b_lastname' => array('position' => 100, 'profile_required' => 'Y', 'checkout_required' => 'Y', 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        'b_firstname_kana' => array('field_name' => 'b_firstname_kana', 'position' => 110, 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        'b_lastname_kana' => array('field_name' => 'b_lastname_kana', 'position' => 120, 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        'b_phone' => array('position' => 140, 'profile_required' => 'Y', 'checkout_required' => 'Y', 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--xsmall'),
        'b_country' => array('position' => 150, 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'checkout_required' => 'Y'),
        'b_zipcode' => array('position' => 160, 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'checkout_required' => 'Y', 'wrapper_class' => 'litecheckout__field--xsmall'),
        'b_state' => array('position' => 170, 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'checkout_required' => 'Y'),
        'b_city' => array('position' => 180, 'section' => 'B', 'profile_show' => 'Y', 'profile_required' => 'N', 'checkout_show' => 'Y', 'checkout_required' => 'Y'),
        'b_address' => array('position' => 190, 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'checkout_required' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        'b_address_2' => array('position' => 200, 'section' => 'B', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--xlarge'),

        's_firstname' => array('position' => 210, 'profile_required' => 'Y', 'checkout_required' => 'Y', 'section' => 'S', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        's_lastname' => array('position' => 220, 'profile_required' => 'Y', 'checkout_required' => 'Y', 'section' => 'S', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        's_firstname_kana' => array('field_name' => 's_firstname_kana', 'position' => 230, 'section' => 'S', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        's_lastname_kana' => array('field_name' => 's_lastname_kana', 'position' => 240, 'section' => 'S', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        's_phone' => array('position' => 260, 'profile_required' => 'Y', 'checkout_required' => 'Y', 'section' => 'S', 'profile_show' => 'Y', 'checkout_show' => 'Y', 'wrapper_class' => 'litecheckout__field--xsmall'),
        's_country' => array('position' => 270, 'section' => 'S', 'profile_show' => 'Y', 'wrapper_class' => 'litecheckout__field--auto'),
        's_zipcode' => array('position' => 280, 'section' => 'S', 'profile_show' => 'Y', 'wrapper_class' => 'litecheckout__field--xsmall'),
        's_state' => array('position' => 290, 'section' => 'S', 'profile_show' => 'Y', 'wrapper_class' => 'litecheckout__field--xsmall'),
        's_city' => array('position' => 300, 'section' => 'S', 'profile_show' => 'Y', 'profile_required' => 'N', 'wrapper_class' => 'litecheckout__field--fill'),
        's_address' => array('position' => 310, 'section' => 'S', 'profile_show' => 'Y', 'wrapper_class' => 'litecheckout__field--medium'),
        's_address_2' => array('position' => 320, 'section' => 'S', 'profile_show' => 'Y', 'wrapper_class' => 'litecheckout__field--xlarge'),
        's_address_type' => array('position' => 330, 'section' => 'S', 'profile_show' => 'N'),
    );

    // 各フィールドのソート順を変更
    foreach ($profile_sort_order as $k => $v) {

        if( $k == 'email_b' || $k == 'email_s' ){
            $field_name = 'email';
        }else{
            $field_name = $k;
        }
        db_query("UPDATE ?:profile_fields SET ?u WHERE field_name = ?s AND section = ?s AND profile_type = 'U'", $v, $field_name, $v['section']);
    }

    // 追加フィールドにフィールド名があると内容の更新ができなくなるのでここで削除
    // field_name は必須
    //db_query("UPDATE ?:profile_fields SET field_name = '' WHERE profile_type = 'U' AND field_name LIKE '%_kana'");
    /////////////////////////////////////////////////////////////////////////
    // 会員情報フィールドに姓・名フリガナを追加したうえで表示順を変更 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // 出品者会員情報フィールドの表示順を変更 BOF
    /////////////////////////////////////////////////////////////////////////
    // 出品者会員情報フィールドソート順
    $vendor_profile_sort_order = array(
        'company' => array('position' => 10, 'section' => 'C'),
        'plan_id' => array('position' => 15, 'section' => 'C'),
        'company_description' => array('position' => 20, 'section' => 'C'),
        'admin_lastname' => array('position' => 30, 'section' => 'C'),
        'admin_firstname' => array('position' => 40, 'section' => 'C'),
        'email' => array('position' => 50, 'section' => 'C'),
        'phone' => array('position' => 60, 'section' => 'C', 'profile_show' => 'Y', 'profile_required' => 'Y'),
        'url' => array('position' => 70, 'section' => 'C', 'profile_show' => 'Y', 'profile_required' => 'N'),
        'country' => array('position' => 80, 'section' => 'C', 'profile_show' => 'Y', 'profile_required' => 'Y'),
        'zipcode' => array('position' => 90, 'section' => 'C', 'profile_show' => 'Y', 'profile_required' => 'Y'),
        'state' => array('position' => 100, 'section' => 'C', 'profile_show' => 'Y', 'profile_required' => 'Y'),
        'city' => array('position' => 110, 'section' => 'C', 'profile_show' => 'Y', 'profile_required' => 'Y'),
        'address' => array('position' => 120, 'section' => 'C', 'profile_show' => 'Y', 'profile_required' => 'Y'),
        'tax_number' => array('position' => 180, 'section' => 'C', 'profile_show' => 'N'),
        'accept_terms' => array('position' => 200, 'section' => 'C'),
    );

    // 各フィールドのソート順を変更
    foreach ($vendor_profile_sort_order as $k => $v) {
        db_query("UPDATE ?:profile_fields SET ?u WHERE field_name = ?s AND section = ?s AND profile_type = 'S'", $v, $k, $v['section']);
    }

    // 出品者 company フィールドの名称を "出品者名" に変更
    db_query("UPDATE ?:profile_field_descriptions SET description = ?s WHERE object_id = (SELECT field_id FROM ?:profile_fields WHERE profile_type = 'S' AND field_name = 'company') AND lang_code = ?s", __("vendor_name", null, 'ja'), "ja");

    // 出品者 email フィールドの名称を "Eメール" に変更
    db_query("UPDATE ?:profile_field_descriptions SET description = ?s WHERE object_id = (SELECT field_id FROM ?:profile_fields WHERE profile_type = 'S' AND field_name = 'email') AND lang_code = ?s", __("email", null, 'ja'), "ja");

    // 出品者 address フィールドの名称を "番地・ビル建物名" に変更
    db_query("UPDATE ?:profile_field_descriptions SET description = ?s WHERE object_id = (SELECT field_id FROM ?:profile_fields WHERE profile_type = 'S' AND field_name = 'address') AND lang_code = ?s", "番地・ビル建物名", "ja");
    /////////////////////////////////////////////////////////////////////////
    // 出品者会員情報フィールドの表示順を変更 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // 都道府県を追加 BOF
    /////////////////////////////////////////////////////////////////////////
    // 都道府県
    $states_data = array(
        'Hokkaido' => array(
            'state' => '北海道',
        ),
        'Aomori' => array(
            'state' => '青森県',
        ),
        'Iwate' => array(
            'state' => '岩手県',
        ),
        'Miyagi' => array(
            'state' => '宮城県',
        ),
        'Akita' => array(
            'state' => '秋田県',
        ),
        'Yamagata' => array(
            'state' => '山形県',
        ),
        'Fukushima' => array(
            'state' => '福島県',
        ),
        'Ibaraki' => array(
            'state' => '茨城県',
        ),
        'Tochigi' => array(
            'state' => '栃木県',
        ),
        'Gunma' => array(
            'state' => '群馬県',
        ),
        'Saitama' => array(
            'state' => '埼玉県',
        ),
        'Chiba' => array(
            'state' => '千葉県',
        ),
        'Tokyo' => array(
            'state' => '東京都',
        ),
        'Kanagawa' => array(
            'state' => '神奈川県',
        ),
        'Niigata' => array(
            'state' => '新潟県',
        ),
        'Toyama' => array(
            'state' => '富山県',
        ),
        'Ishikawa' => array(
            'state' => '石川県',
        ),
        'Fukui' => array(
            'state' => '福井県',
        ),
        'Yamanashi' => array(
            'state' => '山梨県',
        ),
        'Nagano' => array(
            'state' => '長野県',
        ),
        'Gifu' => array(
            'state' => '岐阜県',
        ),
        'Shizuoka' => array(
            'state' => '静岡県',
        ),
        'Aichi' => array(
            'state' => '愛知県',
        ),
        'Mie' => array(
            'state' => '三重県',
        ),
        'Shiga' => array(
            'state' => '滋賀県',
        ),
        'Kyoto' => array(
            'state' => '京都府',
        ),
        'Osaka' => array(
            'state' => '大阪府',
        ),
        'Hyogo' => array(
            'state' => '兵庫県',
        ),
        'Nara' => array(
            'state' => '奈良県',
        ),
        'Wakayama' => array(
            'state' => '和歌山県',
        ),
        'Tottori' => array(
            'state' => '鳥取県',
        ),
        'Shimane' => array(
            'state' => '島根県',
        ),
        'Okayama' => array(
            'state' => '岡山県',
        ),
        'Hiroshima' => array(
            'state' => '広島県',
        ),
        'Yamaguchi' => array(
            'state' => '山口県',
        ),
        'Tokushima' => array(
            'state' => '徳島県',
        ),
        'Kagawa' => array(
            'state' => '香川県',
        ),
        'Ehime' => array(
            'state' => '愛媛県',
        ),
        'Kouchi' => array(
            'state' => '高知県',
        ),
        'Fukuoka' => array(
            'state' => '福岡県',
        ),
        'Saga' => array(
            'state' => '佐賀県',
        ),
        'Nagasaki' => array(
            'state' => '長崎県',
        ),
        'Kumamoto' => array(
            'state' => '熊本県',
        ),
        'Oita' => array(
            'state' => '大分県',
        ),
        'Miyazaki' => array(
            'state' => '宮崎県',
        ),
        'Kagoshima' => array(
            'state' => '鹿児島県',
        ),
        'Okinawa' => array(
            'state' => '沖縄県',
        ),
    );

    $cnt_state = 9000;
    foreach ($states_data as $key => $value) {

        if ( !empty($value['state']) ) {

            $value['country_code'] = 'JP';
            $value['code'] = $value['state'];
            $value['status'] = 'A';
            $value['state_id'] = $cnt_state;

            db_query("REPLACE INTO ?:states ?e", $value);

            foreach ($languages as $value['lang_code'] => $_v) {
                db_query('REPLACE INTO ?:state_descriptions ?e', $value);
            }

            $cnt_state++;
        }
    }
    /////////////////////////////////////////////////////////////////////////
    // 都道府県を追加 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // ロケーション "アメリカ" "カナダ" を削除 BOF
    /////////////////////////////////////////////////////////////////////////
    $destination_ids = db_get_fields("SELECT destination_id FROM ?:destination_descriptions WHERE destination IN ('アメリカ', 'カナダ') AND lang_code = 'ja'");
    foreach ($destination_ids as $dest_id) {
        if (!empty($dest_id) && $dest_id != 1) {
            db_query("DELETE FROM ?:destinations WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:destination_descriptions WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:destination_elements WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:shipping_rates WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:tax_rates WHERE destination_id = ?i", $dest_id);
        }
    }
    /////////////////////////////////////////////////////////////////////////
    // ロケーション "USA" "Canada" を削除 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // US用の配送方法を削除 BOF
    /////////////////////////////////////////////////////////////////////////
    $shipping_ids = db_get_fields("SELECT s.shipping_id FROM ?:shippings s LEFT JOIN ?:shipping_descriptions sd ON s.shipping_id = sd.shipping_id WHERE sd.shipping_id IS NULL");

    foreach ($shipping_ids as $id) {
        fn_delete_shipping($id);
        if (fn_allowed_for('ULTIMATE')){
            db_query('DELETE FROM ?:ult_objects_sharing WHERE share_object_id = ?s AND share_object_type = ?s', $id, 'shippings');
        }
    }
    /////////////////////////////////////////////////////////////////////////
    // US用の配送方法を削除 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // カスタム配送のレートを更新 BOF
    /////////////////////////////////////////////////////////////////////////
    db_query("UPDATE ?:shipping_rates SET rate_value = 'a:1:{s:1:\"C\";a:2:{i:0;a:3:{s:16:\"range_from_value\";i:0;s:14:\"range_to_value\";i:100;s:5:\"value\";s:4:\"0.00\";}i:100;a:3:{s:16:\"range_from_value\";i:100;s:14:\"range_to_value\";s:0:\"\";s:5:\"value\";s:5:\"25.00\";}}}' WHERE rate_id = ?i", 45);
    /////////////////////////////////////////////////////////////////////////
    // カスタム配送のレートを更新 EOF
    /////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////
    // VATを削除し、消費税を追加 BOF
    /////////////////////////////////////////////////////////////////////////
    // 税金
    $tax = array(
        'tax' => '消費税',
        'regnumber' => '',
        'priority' => 0,
        'address_type' => 'S',
        'status' => 'D',
        'price_includes_tax' => 'Y',
        'rates' => array(
            1 => array(
                'rate_id' => '',
                'rate_value' => 10,
                'rate_type' => 'P'
            )
        ),
    );

    $tax_ids = db_get_fields("SELECT tax_id FROM ?:tax_descriptions WHERE tax IN ('VAT') AND lang_code = 'ja'");
    fn_delete_taxes($tax_ids);

    fn_update_tax($tax, '', $languages);
    /////////////////////////////////////////////////////////////////////////
    // VATを削除し、消費税を追加 EOF
    /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 言語変数を追加 BOF
        /////////////////////////////////////////////////////////////////////////
        // 言語変数の追加
        $lang_variables = array(
            array('name' => 'carrier_fukutsu', 'value' => '福山通運'),
            array('name' => 'carrier_jpost', 'value' => '日本郵便'),
            array('name' => 'carrier_jpems', 'value' => '日本郵便 (海外向け)'),
            array('name' => 'carrier_sagawa', 'value' => '佐川急便'),
            array('name' => 'carrier_yamato', 'value' => 'ヤマト運輸'),
            array('name' => 'jp_addons_unable_to_uninstall', 'value' => 'このアドオンはアンインストールできません。'),
            array('name' => 'jp_address_vendors', 'value' => '番地・ビル建物名'),
            array('name' => 'jp_after_replace', 'value' => '置換後'),
            array('name' => 'jp_apply_for_marketplace', 'value' => 'マーケットプレイスへの参加申請'),
            array('name' => 'jp_availability', 'value' => '在庫状況'),
            array('name' => 'jp_back_in_stock_add_email', 'value' => '入荷通知メールの追加'),
            array('name' => 'jp_cc_bonus_month', 'value' => '支払月'),
            array('name' => 'jp_cc_bonus_onetime', 'value' => 'ボーナス一括払い'),
            array('name' => 'jp_cc_installment', 'value' => '分割払い'),
            array('name' => 'jp_cc_installment_times', 'value' => '支払回数'),
            array('name' => 'jp_cc_method', 'value' => '支払方法'),
            array('name' => 'jp_cc_onetime', 'value' => '一括払い'),
            array('name' => 'jp_cc_revo', 'value' => 'リボ払い'),
            array('name' => 'jp_company_address', 'value' => '会社所在地'),
            array('name' => 'jp_company_description', 'value' => 'マーケットプレイスに表示する会社の紹介文（後から変更できます）'),
            array('name' => 'jp_company_info', 'value' => '会社情報'),
            array('name' => 'jp_consumption_tax', 'value' => '消費税'),
            array('name' => 'jp_cvs_ck', 'value' => 'サークルK'),
            array('name' => 'jp_cvs_company_code', 'value' => '企業コード'),
            array('name' => 'jp_cvs_dy', 'value' => 'デイリーヤマザキ'),
            array('name' => 'jp_cvs_fm', 'value' => 'ファミリーマート'),
            array('name' => 'jp_cvs_limit', 'value' => '支払期限'),
            array('name' => 'jp_cvs_ls', 'value' => 'ローソン'),
            array('name' => 'jp_cvs_ms', 'value' => 'ミニストップ'),
            array('name' => 'jp_cvs_name', 'value' => 'コンビ二名'),
            array('name' => 'jp_cvs_payment_barcode', 'value' => 'バーコード情報'),
            array('name' => 'jp_cvs_payment_date', 'value' => '入金日時'),
            array('name' => 'jp_cvs_payment_header', 'value' => '【[cvs_name]決済入金情報】'),
            array('name' => 'jp_cvs_payment_instruction_url', 'value' => '支払方法案内URL'),
            array('name' => 'jp_cvs_payment_number', 'value' => '払込番号'),
            array('name' => 'jp_cvs_payment_online_payment_number', 'value' => 'オンライン決済番号'),
            array('name' => 'jp_cvs_payment_slip', 'value' => 'コンビニ払込票'),
            array('name' => 'jp_cvs_payment_slip_jpost', 'value' => 'コンビニ払込票（郵便振替対応）'),
            array('name' => 'jp_cvs_receipt_no', 'value' => '受付番号'),
            array('name' => 'jp_cvs_se', 'value' => 'セブンイレブン'),
            array('name' => 'jp_cvs_sm', 'value' => 'セイコーマート'),
            array('name' => 'jp_cvs_ts', 'value' => 'サンクス'),
            array('name' => 'jp_cvs_url', 'value' => '払込票URL'),
            array('name' => 'jp_cvs_yd', 'value' => 'ヤマザキデイリーストア'),
            array('name' => 'jp_dear_casual', 'value' => 'さん'),
            array('name' => 'jp_dear_supplier', 'value' => 'ご担当者様'),
            array('name' => 'jp_delivery_date', 'value' => 'お届け希望日'),
            array('name' => 'jp_delivery_date_desc01', 'value' => '注文日の'),
            array('name' => 'jp_delivery_date_desc02', 'value' => '日後（'),
            array('name' => 'jp_delivery_date_desc03', 'value' => 'から'),
            array('name' => 'jp_delivery_date_desc04', 'value' => '日間'),
            array('name' => 'jp_delivery_date_display_days', 'value' => 'お届け日の表示日数'),
            array('name' => 'jp_delivery_date_enable', 'value' => 'お届け日を指定可能にする'),
            array('name' => 'jp_delivery_date_include_holidays', 'value' => '休業日を計算に含める'),
            array('name' => 'jp_delivery_date_not_specified', 'value' => 'お届け日を指定可能にする場合は表示日数を入力してください。'),
            array('name' => 'jp_delivery_date_specify', 'value' => 'お届け日指定'),
            array('name' => 'jp_editing_location', 'value' => 'ロケーションの編集'),
            array('name' => 'jp_edition_standard', 'value' => 'スタンダード版'),
            array('name' => 'jp_edition_marketplace', 'value' => 'マーケットプレイス版'),
            array('name' => 'jp_epsilon_company_name', 'value' => 'イプシロン株式会社'),
            array('name' => 'jp_epsilon_contract_code', 'value' => '契約コード'),
            array('name' => 'jp_epsilon_general_error', 'value' => '決済処理中にエラーが発生しました。<br />お手数ですがショップ管理者までお問い合わせください。'),
            array('name' => 'jp_epsilon_item_name', 'value' => 'お買い上げ商品'),
            array('name' => 'jp_epsilon_notes_header', 'value' => '【イプシロン決済情報】'),
            array('name' => 'jp_epsilon_order_url_production', 'value' => 'オーダー情報確認URL（本番）'),
            array('name' => 'jp_epsilon_order_url_test', 'value' => 'オーダー情報確認URL（テスト）'),
            array('name' => 'jp_epsilon_send_error', 'value' => 'データ送信エラー'),
            array('name' => 'jp_epsilon_trans_method', 'value' => '決済方法'),
            array('name' => 'jp_epsilon_url_production', 'value' => '本番環境接続先URL'),
            array('name' => 'jp_epsilon_url_test', 'value' => 'テスト環境接続先URL'),
            array('name' => 'jp_epsilon_xml_parse_error', 'value' => 'XMLパースエラー'),
            array('name' => 'jp_etc', 'value' => 'など'),
            array('name' => 'jp_exc_tax', 'value' => '税抜'),
            array('name' => 'jp_excluding_tax', 'value' => '税抜'),
            array('name' => 'jp_free', 'value' => 'フリー'),
            array('name' => 'jp_free_license_request_sent', 'value' => 'ライセンス番号発行のため「管理者用Eメールアドレス」および「CS-CartをインストールしたURL」が cs-cart.jp に送信されました。<br />3～5営業日程度で管理者用メールアドレスにライセンス番号をお知らせするメールが届きます。'),
            array('name' => 'jp_goto_namager', 'value' => '管理ページに移動'),
            array('name' => 'jp_holidays', 'value' => '休業日'),
            array('name' => 'jp_inventory_count', 'value' => '在庫数'),
            array('name' => 'jp_japanese_yen', 'value' => '円'),
            array('name' => 'jp_login_or_register', 'value' => 'ログインまたは会員登録'),
            array('name' => 'jp_make_a_fresh_snapshot', 'value' => 'スナップショットの取得'),
            array('name' => 'jp_marketplace_admin_mode', 'value' => 'マーケットプレイス管理モード'),
            array('name' => 'jp_mtpl_reward_points_aquired', 'value' => '獲得ポイント数'),
            array('name' => 'jp_not_specified', 'value' => '指定なし'),
            array('name' => 'jp_payment_alipay', 'value' => 'Alipay国際決済'),
            array('name' => 'jp_payment_auone', 'value' => 'auかんたん決済'),
            array('name' => 'jp_payment_banktransfer', 'value' => '銀行振込'),
            array('name' => 'jp_payment_bitcash', 'value' => 'ビットキャッシュ'),
            array('name' => 'jp_payment_cc', 'value' => 'クレジットカード決済'),
            array('name' => 'jp_payment_chocom', 'value' => 'ちょコム決済'),
            array('name' => 'jp_payment_cvs', 'value' => 'コンビニ決済'),
            array('name' => 'jp_payment_cyberedy', 'value' => 'CyberEdy'),
            array('name' => 'jp_payment_docomo', 'value' => 'ドコモケータイ払い'),
            array('name' => 'jp_payment_gmoney', 'value' => 'G-MONEY'),
            array('name' => 'jp_payment_installment', 'value' => '分割払い'),
            array('name' => 'jp_payment_jcb_premo', 'value' => 'JCB PREMO'),
            array('name' => 'jp_payment_jnb', 'value' => 'ジャパンネット銀行'),
            array('name' => 'jp_payment_mobileedy', 'value' => 'Mobile Edy'),
            array('name' => 'jp_payment_netcash', 'value' => 'NET CASH'),
            array('name' => 'jp_payment_netmile', 'value' => 'ネットマイル'),
            array('name' => 'jp_payment_oempin', 'value' => 'PIN決済'),
            array('name' => 'jp_payment_paypal', 'value' => 'Paypal決済'),
            array('name' => 'jp_payment_pez', 'value' => 'ペイジー'),
            array('name' => 'jp_payment_rakuten', 'value' => '楽天ID決済'),
            array('name' => 'jp_payment_rakutenbank', 'value' => '楽天銀行'),
            array('name' => 'jp_payment_sbmoney', 'value' => 'SoftBankマネー'),
            array('name' => 'jp_payment_softbank', 'value' => 'S!まとめて支払い'),
            array('name' => 'jp_payment_suica', 'value' => 'モバイルSuica'),
            array('name' => 'jp_payment_unionpay', 'value' => '銀聯ネット決済'),
            array('name' => 'jp_payment_webmoney', 'value' => 'ウェブマネー'),
            array('name' => 'jp_payment_yahoowallet', 'value' => 'Yahoo!ウォレット'),
            array('name' => 'jp_paypal_email', 'value' => '支払いを受けるPaypalアカウントのEメールアドレス'),
            array('name' => 'jp_paypal_pending', 'value' => '未決済'),
            array('name' => 'jp_paytimes', 'value' => '支払回数'),
            array('name' => 'jp_paytimes_unit', 'value' => '回'),
            array('name' => 'jp_pdfinv_customise_product', 'value' => '【カスタマイズ商品】'),
            array('name' => 'jp_pdfinv_full_colon', 'value' => '：'),
            array('name' => 'jp_pdfinv_invoice', 'value' => '納 品 書'),
            array('name' => 'jp_pdfinv_not_assigned', 'value' => '設定なし'),
            array('name' => 'jp_pdfinv_not_required', 'value' => '指定不要'),
            array('name' => 'jp_pdfinv_other_option', 'value' => 'その他..'),
            array('name' => 'jp_pdfinv_page_subtotal', 'value' => 'ページ小計'),
            array('name' => 'jp_pdfinv_person_in_charge', 'value' => '担当'),
            array('name' => 'jp_pdfinv_tab_name', 'value' => 'PDF納品書'),
            array('name' => 'jp_pdfinv_zip_title', 'value' => '〒'),
            array('name' => 'jp_pez_company_code', 'value' => '収納機関番号'),
            array('name' => 'jp_pez_limit', 'value' => '支払期限'),
            array('name' => 'jp_pez_receipt_no', 'value' => '確認番号'),
            array('name' => 'jp_product_no_track', 'value' => '在庫あり'),
            array('name' => 'jp_products_in_wishlist', 'value' => 'ほしい物リスト内商品数'),
            array('name' => 'jp_remise_company_name', 'value' => 'ルミーズ株式会社'),
            array('name' => 'jp_remise_csp_notify_url', 'value' => '収納情報通知URL'),
            array('name' => 'jp_remise_csp_notify_url_notice', 'value' => '加盟店バックヤードシステムの「収納情報通知URL」には、<br /><strong>[notify_url]</strong><br />を登録してください。'),
            array('name' => 'jp_remise_cvs_info', 'value' => '[コンビニ決済情報]'),
            array('name' => 'jp_remise_cvs_name', 'value' => 'コンビ二名'),
            array('name' => 'jp_remise_goods_name', 'value' => '商品一式'),
            array('name' => 'jp_remise_host_id', 'value' => 'ホスト番号'),
            array('name' => 'jp_remise_payment_method', 'value' => '支払い方法'),
            array('name' => 'jp_remise_payquick', 'value' => 'ペイクイック機能'),
            array('name' => 'jp_remise_payquick_click_to_delete', 'value' => '登録済みのクレジットカード情報を削除するにはボタンをクリックしてください。'),
            array('name' => 'jp_remise_payquick_delete_card_info', 'value' => '登録済みカード情報の削除'),
            array('name' => 'jp_remise_payquick_delete_success', 'value' => 'クレジットカード情報を削除しました。'),
            array('name' => 'jp_remise_payquick_desc', 'value' => 'ペイクイック機能を使うと２回目以降のお買い物でクレジットカード情報の入力が不要になります。'),
            array('name' => 'jp_remise_payquick_no_card_info', 'value' => 'カード情報は登録されていません'),
            array('name' => 'jp_remise_payquick_registered_card', 'value' => '登録済みカード情報'),
            array('name' => 'jp_remise_plan', 'value' => 'ご契約プラン'),
            array('name' => 'jp_remise_result_url_notice', 'value' => '加盟店バックヤードシステムの「結果通知URL」には、<br /><strong>[result_url]</strong><br />を登録してください。'),
            array('name' => 'jp_remise_s_paydate', 'value' => '支払い期限（日）'),
            array('name' => 'jp_remise_shop_code', 'value' => '加盟店コード'),
            array('name' => 'jp_remise_url_production', 'value' => '本番環境接続先URL'),
            array('name' => 'jp_remise_url_test', 'value' => 'テスト環境接続先URL'),
            array('name' => 'jp_remise_use_payquick', 'value' => 'ペイクイック機能を使う'),
            array('name' => 'jp_sbps_company_name', 'value' => 'SBペイメントサービス株式会社'),
            array('name' => 'jp_sbps_connection_support', 'value' => '接続支援サイト'),
            array('name' => 'jp_sbps_etc', 'value' => 'etc'),
            array('name' => 'jp_sbps_hashkey', 'value' => 'ハッシュキー'),
            array('name' => 'jp_sbps_item_name', 'value' => 'お買い上げ商品'),
            array('name' => 'jp_sbps_merchant_id', 'value' => 'マーチャントID'),
            array('name' => 'jp_sbps_notes_header', 'value' => '【SBPS決済情報】'),
            array('name' => 'jp_sbps_notice', 'value' => '※ <a href="https://cs-cart.jp/gateway/sbps/" target="_blank">SBペイメントサービスとの契約</a>が必要です。お申し込みは <a href="https://cs-cart.jp/gateway/sbps/" target="_blank"><b>こちら</b></a>'),
            array('name' => 'jp_sbps_service_id', 'value' => 'サービスID'),
            array('name' => 'jp_sbps_settings_connection', 'value' => '接続設定'),
            array('name' => 'jp_sbps_tracking_id', 'value' => 'トラッキングID'),
            array('name' => 'jp_sbps_trans_method', 'value' => '決済方法'),
            array('name' => 'jp_sbps_url_connection_support', 'value' => '接続支援サイトURL'),
            array('name' => 'jp_sbps_url_production', 'value' => '本番環境接続先URL'),
            array('name' => 'jp_sbps_url_test', 'value' => 'テスト環境接続先URL'),
            array('name' => 'jp_share_link_on_facebook', 'value' => 'Facebookでリンクをシェア'),
            array('name' => 'jp_shipment_tracking_url', 'value' => '配達状況確認URL'),
            array('name' => 'jp_shipping_carrier', 'value' => '運送会社'),
            array('name' => 'jp_shipping_delivery_time', 'value' => 'お届け時間帯'),
            array('name' => 'jp_shipping_no_config_params', 'value' => '選択した配送サービスに関する設定項目はありません。'),
            array('name' => 'jp_shipping_origination', 'value' => '出発地点'),
            array('name' => 'jp_shipping_rates_setting', 'value' => '送料設定'),
            array('name' => 'jp_shipping_rates_setting_menu_description', 'value' => '各種配送サービスにおける料金テーブルの管理を行います。'),
            array('name' => 'jp_shipping_rates_updated', 'value' => '配送料金を更新しました。'),
            array('name' => 'jp_shipping_select_service_and_origin', 'value' => '配送サービスと出発地点を指定してください'),
            array('name' => 'jp_shipping_service_name', 'value' => '配送サービス名'),
            array('name' => 'jp_shipping_service_name_short', 'value' => 'サービス名'),
            array('name' => 'jp_shipping_size', 'value' => 'サイズ'),
            array('name' => 'jp_shipping_weight', 'value' => '重量'),
            array('name' => 'jp_supplier_arrange_delivery', 'value' => '商品発送依頼'),
            array('name' => 'jp_telecomcredit_clientip', 'value' => 'クライアントIP'),
            array('name' => 'jp_telecomcredit_company_name', 'value' => 'テレコムクレジット株式会社'),
            array('name' => 'jp_telecomcredit_error_title', 'value' => '決済エラー'),
            array('name' => 'jp_text_epsilon_error', 'value' => 'イプシロン決済エラー'),
            array('name' => 'jp_text_epsilon_notice', 'value' => 'イプシロン決済に関する設定情報を入力してください。'),
            array('name' => 'jp_text_images_export_directory', 'value' => 'エクスポート用画像の出力ディレクトリを絶対パスで指定してください。'),
            array('name' => 'jp_text_no_updates_available', 'value' => '利用可能なアップデートはありません'),
            array('name' => 'jp_text_not_u2d', 'value' => '<p>CS-Cart本体のバージョン <b>[pkg_ver]</b> と日本語版のバージョン <b>[jp_ver]</b> が一致しません。<br />すぐに日本語版をアップデートしてください。<br />アップデートファイルは<br /><a href="[url]" target="_blank" class="underlined"><b>[url]</b></a><br />からダウンロードできます。<br />また、日本語版のアップデートファイルをサーバーにアップロード済みの場合は、<a href="[url_ud]" class="underlined"><b>こちら</b></a> からアップデートできます。</p>'),
            array('name' => 'jp_text_remise_cc_notice', 'value' => 'ルミーズクレジットカード決済に関する設定情報を入力してください。'),
            array('name' => 'jp_text_remise_csp_notice', 'value' => 'ルミーズマルチ決済に関する設定情報を入力してください。'),
            array('name' => 'jp_text_telecomcredit_notice', 'value' => 'テレコムクレジット決済に関する設定情報を入力してください。'),
            array('name' => 'jp_text_update_completed', 'value' => 'お使いのCS-Cart日本語版は最新バージョンにアップデートされました。'),
            array('name' => 'jp_text_update_failed', 'value' => 'CS-Cart日本語版のアップデートに失敗しました。'),
            array('name' => 'jp_text_version_file_not_exists', 'value' => '日本語版バージョン管理ファイル<br /><b>[file_path]</b><br />が存在しません。<br />ファイルをサーバーにアップロードしてください。'),
            array('name' => 'jp_text_version_file_not_writable', 'value' => '日本語版バージョン管理ファイル<br /><b>[file_path]</b><br />に書き込みできません。<br />ファイルのパーミッションを 666 に変更してください。'),
            array('name' => 'jp_trial_expired', 'value' => '試用期間が終了しました'),
            array('name' => 'jp_trial_expired_and_closed', 'value' => '30日間の試用期間が終了し、お使いのCS-Cartマーケットプレイス版で構築したショップは一時クローズされました。<br />ショップを再びオープンするには、 <a href="http://store.cs-cart.jp/cs-cart-marketplace.html" target="_blank">こちら</a> よりライセンスを購入いただき、<a class="cm-dialog-opener cm-dialog-auto-size" data-ca-target-id="store_mode_dialog">ショップモード</a> ページからライセンス番号を登録してください。<br />ライセンスが正しく認証され、フルモードが有効化された後に 基本設定 -> 全般 -> ショップを一時クローズ のチェックを外すとショップを再オープンできます。'),
            array('name' => 'jp_update', 'value' => 'アップデート'),
            array('name' => 'jp_update_center', 'value' => '日本語版アップデート'),
            array('name' => 'jp_update_contents', 'value' => 'アップデート内容'),
            array('name' => 'jp_welcome', 'value' => 'ようこそ、'),
            array('name' => 'jp_zeus_clientip', 'value' => 'IPコード'),
            array('name' => 'jp_zeus_company_name', 'value' => '株式会社ゼウス'),
            array('name' => 'jp_zeus_notes_header', 'value' => '【ゼウスカード決済情報】'),
            array('name' => 'jp_zeus_notice', 'value' => 'ゼウスカード決済に関する設定情報を入力してください。'),
            array('name' => 'jp_zeus_ordd', 'value' => 'オーダーID'),
        );

        foreach ($languages as $lc => $_v) {
            foreach ($lang_variables as $k1 => $v1) {
                if (!empty($v1['name'])) {
                    preg_match("/(^[a-zA-z0-9][a-zA-Z0-9_]*)/", $v1['name'], $matches);
                    if (strlen($matches[0]) == strlen($v1['name'])) {
                        $v1['lang_code'] = $lc;
                        db_query("REPLACE INTO ?:language_values ?e", $v1);
                    }
                }
            }
        }
        /////////////////////////////////////////////////////////////////////////
        // 言語変数を追加 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 日本語版オリジナルの言語変数で英語の値をセットすべきものに対応 BOF
        /////////////////////////////////////////////////////////////////////////
        db_query("UPDATE ?:language_values SET value = 'Availability' WHERE name = 'jp_availability' AND lang_code = 'en'");
        db_query("UPDATE ?:language_values SET value = 'exc tax' WHERE name = 'jp_exc_tax' AND lang_code = 'en'");
        db_query("UPDATE ?:language_values SET value = 'Make a fresh snapshot' WHERE name = 'jp_make_a_fresh_snapshot' AND lang_code = 'en'");
        db_query("UPDATE ?:language_values SET value = 'Excluding tax' WHERE name = 'jp_excluding_tax' AND lang_code = 'en'");
        db_query("UPDATE ?:language_values SET value = ' (Advanced Booking Available)' WHERE name = 'jp_order_allowed' AND lang_code = 'en'");
        /////////////////////////////////////////////////////////////////////////
        // 日本語版オリジナルの言語変数で英語の値をセットすべきものに対応 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 既存の運送会社のソート順を変更 BOF
        /////////////////////////////////////////////////////////////////////////
        // 既存の運送会社ソート順
        $existing_carriers_sort = array(
            'fedex_enabled' => 100,
            'ups_enabled' => 110,
            'usps_enabled' => 120,
            'dhl_enabled' => 130,
            'aup_enabled' => 140,
            'can_enabled' => 150,
            'swisspost_enabled' => 160,
            'temando_enabled' => 170,
        );

        foreach ($existing_carriers_sort as $key => $value) {
            $_obj_id = Settings::instance()->getId($key, 'Shippings');
            Settings::instance()->update( array('object_id' => $_obj_id, 'position' => $value) );
        }
        /////////////////////////////////////////////////////////////////////////
        // 既存の運送会社のソート順を変更 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 日本国内配送用テーブルを作成しデータをセット BOF
        /////////////////////////////////////////////////////////////////////////
        // 運送会社
        $jp_carriers = array(
            'sagawa' => '佐川急便',
            'yamato' => 'ヤマト運輸',
            'fukutsu' => '福山通運',
            'jpems' => '日本郵便（海外向け）',
            'jpost' => '日本郵便',
        );

        // 配送サービス
        $jp_carrier_services = array(
            array('service_id' => 9000, 'carrier_code' => 'sagawa', 'service_code' => 'standard', 'service_name' => '飛脚宅配便', 'sort_order' => 1),
            array('service_id' => 9001, 'carrier_code' => 'sagawa', 'service_code' => 'cool', 'service_name' => '飛脚クール便', 'sort_order' => 2),
            array('service_id' => 9002, 'carrier_code' => 'yamato', 'service_code' => 'standard', 'service_name' => '宅急便', 'sort_order' => 1),
            array('service_id' => 9003, 'carrier_code' => 'yamato', 'service_code' => 'cool', 'service_name' => 'クール宅急便', 'sort_order' => 2),
            array('service_id' => 9006, 'carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'service_name' => 'フクツー宅配便', 'sort_order' => 1),
            array('service_id' => 9007, 'carrier_code' => 'jpems', 'service_code' => 'ems', 'service_name' => 'EMS', 'sort_order' => 1),
            array('service_id' => 9008, 'carrier_code' => 'jpost', 'service_code' => 'standard', 'service_name' => 'ゆうパック', 'sort_order' => 1)
        );

        // 配送地域
        $jp_carrier_zones = array(
            array('zone_id' => 1, 'carrier_code' => 'sagawa', 'zone_code' => 'L', 'zone_name' => '南九州', 'sort_order' => 1),
            array('zone_id' => 2, 'carrier_code' => 'sagawa', 'zone_code' => 'K', 'zone_name' => '北九州', 'sort_order' => 2),
            array('zone_id' => 3, 'carrier_code' => 'sagawa', 'zone_code' => 'J', 'zone_name' => '四国', 'sort_order' => 3),
            array('zone_id' => 4, 'carrier_code' => 'sagawa', 'zone_code' => 'I', 'zone_name' => '中国', 'sort_order' => 4),
            array('zone_id' => 5, 'carrier_code' => 'sagawa', 'zone_code' => 'H', 'zone_name' => '関西', 'sort_order' => 5),
            array('zone_id' => 6, 'carrier_code' => 'sagawa', 'zone_code' => 'G', 'zone_name' => '北陸', 'sort_order' => 6),
            array('zone_id' => 7, 'carrier_code' => 'sagawa', 'zone_code' => 'F', 'zone_name' => '東海', 'sort_order' => 7),
            array('zone_id' => 8, 'carrier_code' => 'sagawa', 'zone_code' => 'E', 'zone_name' => '信越', 'sort_order' => 8),
            array('zone_id' => 9, 'carrier_code' => 'sagawa', 'zone_code' => 'D', 'zone_name' => '関東', 'sort_order' => 9),
            array('zone_id' => 10, 'carrier_code' => 'sagawa', 'zone_code' => 'C', 'zone_name' => '南東北', 'sort_order' => 10),
            array('zone_id' => 11, 'carrier_code' => 'sagawa', 'zone_code' => 'B', 'zone_name' => '北東北', 'sort_order' => 11),
            array('zone_id' => 12, 'carrier_code' => 'sagawa', 'zone_code' => 'A', 'zone_name' => '北海道', 'sort_order' => 12),
            array('zone_id' => 13, 'carrier_code' => 'sagawa', 'zone_code' => 'M', 'zone_name' => '沖縄', 'sort_order' => 13),

            array('zone_id' => 14, 'carrier_code' => 'yamato', 'zone_code' => 'A', 'zone_name' => '北海道', 'sort_order' => 1),
            array('zone_id' => 15, 'carrier_code' => 'yamato', 'zone_code' => 'B', 'zone_name' => '北東北', 'sort_order' => 2),
            array('zone_id' => 16, 'carrier_code' => 'yamato', 'zone_code' => 'C', 'zone_name' => '南東北', 'sort_order' => 3),
            array('zone_id' => 17, 'carrier_code' => 'yamato', 'zone_code' => 'D', 'zone_name' => '関東', 'sort_order' => 4),
            array('zone_id' => 18, 'carrier_code' => 'yamato', 'zone_code' => 'E', 'zone_name' => '信越', 'sort_order' => 5),
            array('zone_id' => 19, 'carrier_code' => 'yamato', 'zone_code' => 'F', 'zone_name' => '中部', 'sort_order' => 6),
            array('zone_id' => 20, 'carrier_code' => 'yamato', 'zone_code' => 'G', 'zone_name' => '北陸', 'sort_order' => 7),
            array('zone_id' => 21, 'carrier_code' => 'yamato', 'zone_code' => 'H', 'zone_name' => '関西', 'sort_order' => 8),
            array('zone_id' => 22, 'carrier_code' => 'yamato', 'zone_code' => 'I', 'zone_name' => '中国', 'sort_order' => 9),
            array('zone_id' => 23, 'carrier_code' => 'yamato', 'zone_code' => 'J', 'zone_name' => '四国', 'sort_order' => 10),
            array('zone_id' => 24, 'carrier_code' => 'yamato', 'zone_code' => 'K', 'zone_name' => '九州', 'sort_order' => 11),
            array('zone_id' => 25, 'carrier_code' => 'yamato', 'zone_code' => 'L', 'zone_name' => '沖縄', 'sort_order' => 12),

            array('zone_id' => 37, 'carrier_code' => 'fukutsu', 'zone_code' => 'A', 'zone_name' => '北海道', 'sort_order' => 1),
            array('zone_id' => 38, 'carrier_code' => 'fukutsu', 'zone_code' => 'B', 'zone_name' => '北東北', 'sort_order' => 2),
            array('zone_id' => 39, 'carrier_code' => 'fukutsu', 'zone_code' => 'C', 'zone_name' => '南東北', 'sort_order' => 3),
            array('zone_id' => 40, 'carrier_code' => 'fukutsu', 'zone_code' => 'D', 'zone_name' => '関東', 'sort_order' => 4),
            array('zone_id' => 41, 'carrier_code' => 'fukutsu', 'zone_code' => 'E', 'zone_name' => '信越', 'sort_order' => 5),
            array('zone_id' => 42, 'carrier_code' => 'fukutsu', 'zone_code' => 'F', 'zone_name' => '北陸', 'sort_order' => 6),
            array('zone_id' => 43, 'carrier_code' => 'fukutsu', 'zone_code' => 'G', 'zone_name' => '中部', 'sort_order' => 7),
            array('zone_id' => 44, 'carrier_code' => 'fukutsu', 'zone_code' => 'H', 'zone_name' => '関西', 'sort_order' => 8),
            array('zone_id' => 45, 'carrier_code' => 'fukutsu', 'zone_code' => 'I', 'zone_name' => '中国', 'sort_order' => 9),
            array('zone_id' => 46, 'carrier_code' => 'fukutsu', 'zone_code' => 'J', 'zone_name' => '四国', 'sort_order' => 10),
            array('zone_id' => 47, 'carrier_code' => 'fukutsu', 'zone_code' => 'K', 'zone_name' => '北九州', 'sort_order' => 11),
            array('zone_id' => 65, 'carrier_code' => 'fukutsu', 'zone_code' => 'L', 'zone_name' => '南九州', 'sort_order' => 12),

            array('zone_id' => 48, 'carrier_code' => 'jpems', 'zone_code' => 'A', 'zone_name' => '第1地帯', 'sort_order' => 1),
            array('zone_id' => 49, 'carrier_code' => 'jpems', 'zone_code' => 'B', 'zone_name' => '第2-1地帯', 'sort_order' => 2),
            array('zone_id' => 50, 'carrier_code' => 'jpems', 'zone_code' => 'C', 'zone_name' => '第2-2地帯', 'sort_order' => 3),
            array('zone_id' => 51, 'carrier_code' => 'jpems', 'zone_code' => 'D', 'zone_name' => '第3地帯', 'sort_order' => 4),
            array('zone_id' => 52, 'carrier_code' => 'jpems', 'zone_code' => 'Z', 'zone_name' => '日本国内', 'sort_order' => 99),

            array('zone_id' => 53, 'carrier_code' => 'jpost', 'zone_code' => 'A', 'zone_name' => '北海道', 'sort_order' => 1),
            array('zone_id' => 54, 'carrier_code' => 'jpost', 'zone_code' => 'B', 'zone_name' => '東北', 'sort_order' => 2),
            array('zone_id' => 55, 'carrier_code' => 'jpost', 'zone_code' => 'C', 'zone_name' => '関東', 'sort_order' => 3),
            array('zone_id' => 56, 'carrier_code' => 'jpost', 'zone_code' => 'D', 'zone_name' => '信越', 'sort_order' => 4),
            array('zone_id' => 57, 'carrier_code' => 'jpost', 'zone_code' => 'E', 'zone_name' => '北陸', 'sort_order' => 5),
            array('zone_id' => 58, 'carrier_code' => 'jpost', 'zone_code' => 'F', 'zone_name' => '東海', 'sort_order' => 6),
            array('zone_id' => 59, 'carrier_code' => 'jpost', 'zone_code' => 'G', 'zone_name' => '近畿', 'sort_order' => 7),
            array('zone_id' => 60, 'carrier_code' => 'jpost', 'zone_code' => 'H', 'zone_name' => '中国', 'sort_order' => 8),
            array('zone_id' => 61, 'carrier_code' => 'jpost', 'zone_code' => 'I', 'zone_name' => '四国', 'sort_order' => 9),
            array('zone_id' => 62, 'carrier_code' => 'jpost', 'zone_code' => 'J', 'zone_name' => '九州', 'sort_order' => 10),
            array('zone_id' => 63, 'carrier_code' => 'jpost', 'zone_code' => 'K', 'zone_name' => '沖縄', 'sort_order' => 11),
            array('zone_id' => 64, 'carrier_code' => 'jpost', 'zone_code' => 'X', 'zone_name' => '同一県', 'sort_order' => 12),
        );

        // 送料
        $jp_shipping_rates = fn_lcjp_get_jp_shipping_rates_table();

        // プリセットする配送方法
        $jp_shipping_methods = array(
            array('shipping' => '飛脚宅配便', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9000, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 10),
            array('shipping' => '飛脚クール便', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9001, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 20),
            array('shipping' => '宅急便', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9002, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 30),
            array('shipping' => 'クール宅急便', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9003, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 40),
            array('shipping' => 'フクツー宅配便', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9006, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 50),
            array('shipping' => 'EMS', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9007, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 60),
            array('shipping' => 'ゆうパック', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9008, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 70),
            array('shipping' => 'チルドゆうパック', 'min_weight' => '0.00', 'rate_calculation' => 'R', 'service_id' => 9008, 'service_params' => array('jp_shipping' => 'Y'), 'position' => 80)
        );

        $_data = array();
        $cnt = 0;
        db_query("DROP TABLE IF EXISTS ?:jp_carriers");
        db_query("CREATE TABLE ?:jp_carriers (carrier_id mediumint(8) unsigned NOT NULL auto_increment, carrier_code varchar(32) NOT NULL, carrier_name varchar(32) NOT NULL, lang_code varchar(4) NOT NULL, PRIMARY KEY  (carrier_id, lang_code)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        foreach ($jp_carriers as $key => $value) {
            $cnt++;
            // 運送会社に関する情報を配列にセット
            $_data['carrier_id'] = $cnt;
            $_data['carrier_code'] = $key;
            $_data['carrier_name'] = $value;

            // jp_carriersテーブルに運送会社のレコードを追加
            foreach ($languages as $_data['lang_code'] => $_v) {
                db_query("INSERT INTO ?:jp_carriers ?e", $_data);
            }
        }

        // 配送サービス
        $_data = array();
        $cnt = 0;
        db_query("DROP TABLE IF EXISTS ?:jp_carrier_services");
        db_query("CREATE TABLE ?:jp_carrier_services (service_id mediumint(8) unsigned NOT NULL auto_increment, carrier_code varchar(32) NOT NULL, service_code varchar(32) NOT NULL, service_name varchar(32) NOT NULL, lang_code varchar(4) NOT NULL, sort_order tinyint(3) unsigned NOT NULL, PRIMARY KEY  (service_id, lang_code)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        foreach ($jp_carrier_services as $_data) {
            $cnt++;

            // shipping_servicesテーブルに運送サービスに関するレコードを追加
            $_data_shipping_services = array();
            $_data_shipping_service_desc = array();

            $_data_shipping_services['service_id'] = $_data['service_id'];
            $_data_shipping_services['status'] = 'A';
            $_data_shipping_services['module'] = $_data['carrier_code'];
            $_data_shipping_services['code'] = strtoupper($_data['service_code']);
            $_data_shipping_service_desc['service_id'] = $_data['service_id'];

            db_query("INSERT INTO ?:shipping_services ?e", $_data_shipping_services);

            $_data['service_id'] = $cnt;

            foreach ($languages as $_data['lang_code'] => $_v) {
                // jp_carrier_servicesテーブルに運送サービスに関するレコードを追加
                db_query("INSERT INTO ?:jp_carrier_services ?e", $_data);

                // shipping_service_descriptionsテーブルに運送サービスに関するレコードを追加
                $_data_shipping_service_desc['lang_code'] = $_data['lang_code'];
                $_data_shipping_service_desc['description'] = $_data['service_name'];
                db_query("INSERT INTO ?:shipping_service_descriptions ?e", $_data_shipping_service_desc);
            }
        }

        // 配送地域
        $_data = array();
        $cnt = 0;
        db_query("DROP TABLE IF EXISTS ?:jp_carrier_zones");
        db_query("CREATE TABLE ?:jp_carrier_zones (zone_id mediumint(8) unsigned NOT NULL auto_increment, carrier_code varchar(32) NOT NULL, zone_code varchar(2) NOT NULL, zone_name varchar(12) NOT NULL, lang_code varchar(4) NOT NULL, sort_order tinyint(3) unsigned NOT NULL, PRIMARY KEY  (zone_id, lang_code)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        foreach ($jp_carrier_zones as $_data) {
            $cnt++;

            foreach ($languages as $_data['lang_code'] => $_v) {
                db_query("INSERT INTO ?:jp_carrier_zones ?e", $_data);
            }
        }

        // 送料
        $_data = array();
        db_query("DROP TABLE IF EXISTS ?:jp_shipping_rates");
        db_query("CREATE TABLE ?:jp_shipping_rates (rate_id mediumint(8) unsigned NOT NULL auto_increment, company_id mediumint(8) NOT NULL DEFAULT '0', carrier_code varchar(32) NOT NULL, service_code varchar(12) NOT NULL, zone_id mediumint(8) unsigned NOT NULL, shipping_rates text, PRIMARY KEY (rate_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        foreach ($jp_shipping_rates as $_data) {
            $_data['shipping_rates'] = serialize($_data['shipping_rates']);
            db_query("INSERT INTO ?:jp_shipping_rates ?e", $_data);
        }

        // 登録済みのショップ（出品者）のIDを取得
        $company_ids = db_get_fields("SELECT company_id FROM ?:companies");

        // 登録済みのショップ（出品者）が存在する場合
        if( !empty($company_ids) ){
            // デフォルトの送料情報を取得
            $default_shipping_rates = db_get_array("SELECT * FROM ?:jp_shipping_rates WHERE company_id = ?i", 0);

            // デフォルトの送料情報が存在する場合
            if( !empty($default_shipping_rates) && is_array($default_shipping_rates) ){
                // デフォルトの送料情報を新しいショップ（出品者）向けにコピー
                foreach($default_shipping_rates as $rate_info){
                    $_data = $rate_info;
                    unset($_data['rate_id']);
                    foreach( $company_ids as $company_id){
                        $_data['company_id'] = $company_id;
                        db_query("REPLACE INTO ?:jp_shipping_rates ?e", $_data);
                    }
                }
            }
        }

        // 日本向け配送方法を追加
        $_chilled_id = 0;
        foreach ($jp_shipping_methods as $_data) {
            if (fn_allowed_for('ULTIMATE')){
                $_data['company_id'] = 1;
            }
            $sid = fn_update_shipping($_data, '', $languages);

            // 登録済みのすべてのショップにおいて有効にする
            if (fn_allowed_for('ULTIMATE')){
                fn_share_object_to_all('shippings', $sid);
            }

            if($_data['shipping'] == 'チルドゆうパック') {
                $_chilled_id = $sid;
            }
        }
        // 日本郵便（チルドゆうパック）に送料データを追加する
        if($_chilled_id != 0) {
            $_rate_value = 'a:1:{s:1:"W";a:3:{i:0;a:3:{s:5:"value";d:190;s:4:"type";s:1:"F";s:8:"per_unit";s:1:"N";}i:4;a:3:{s:5:"value";d:340;s:4:"type";s:1:"F";s:8:"per_unit";s:1:"N";}i:8;a:3:{s:5:"value";d:640;s:4:"type";s:1:"F";s:8:"per_unit";s:1:"N";}}}';
            db_query("REPLACE INTO ?:shipping_rates (rate_value, destination_id, shipping_id) VALUES(?s, ?i, ?i)", $_rate_value, 0, $_chilled_id);
        }

        /////////////////////////////////////////////////////////////////////////
        // 日本国内配送用テーブルを作成しデータをセット EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 各配送方法のお届け希望日の表示内容登を録するテーブルを作成 BOF
        /////////////////////////////////////////////////////////////////////////
        db_query("DROP TABLE IF EXISTS ?:jp_delivery_date");
        db_query("CREATE TABLE ?:jp_delivery_date (delivery_id mediumint(8) unsigned NOT NULL auto_increment, shipping_id mediumint(8) unsigned NOT NULL, delivery_status varchar(1) NOT NULL, delivery_from smallint(6) NOT NULL, delivery_to smallint(6) NOT NULL, include_holidays varchar(1) NOT NULL, PRIMARY KEY (`delivery_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        /////////////////////////////////////////////////////////////////////////
        // 各配送方法のお届け希望日の表示内容を登録するテーブルを作成 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 注文における各配送方法のお届け時間帯・希望日を登録するテーブルを作成 BOF
        /////////////////////////////////////////////////////////////////////////
        db_query("DROP TABLE IF EXISTS ?:jp_order_delivery_info");
        db_query("CREATE TABLE ?:jp_order_delivery_info (order_id mediumint(8) unsigned NOT NULL, shipping_id mediumint(8) unsigned NOT NULL, group_key mediumint(8) unsigned NOT NULL,delivery_date varchar(64) NOT NULL default '', delivery_timing varchar(64) NOT NULL default '', PRIMARY KEY (`order_id`, `shipping_id`, `group_key`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        /////////////////////////////////////////////////////////////////////////
        // 注文における各配送方法のお届け時間帯・希望日を登録するテーブルを作成 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 日本では使用しない支払方法を削除 BOF
        /////////////////////////////////////////////////////////////////////////
        $payment_ids = db_get_fields("SELECT payment_id FROM ?:payment_descriptions WHERE payment IN ('Check', 'Money Order', 'Purchase Order', 'Personal Check', 'Business Check', 'Government Check', 'Traveller\'s Check') AND lang_code = 'en'");

        foreach($payment_ids as $payment_id){
            fn_delete_payment($payment_id);
            if (fn_allowed_for('ULTIMATE')){
                db_query('DELETE FROM ?:ult_objects_sharing WHERE share_object_id = ?s AND share_object_type = ?s', $payment_id, 'payments');
            }
        }
        /////////////////////////////////////////////////////////////////////////
        // 日本では使用しない支払方法を削除 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 支払方法の設定 BOF
        /////////////////////////////////////////////////////////////////////////
        // 追加する支払い方法
        $payment_methods = array(
            array('processor_id' => 9000,
                'processor' => 'ルミーズマルチ決済',
                'processor_script' => 'remise_csp.php',
                'processor_template' => 'views/orders/components/payments/cc_outside.tpl',
                'admin_template' => 'remise_csp.tpl',
                'callback' => 'N',
                'type' => 'P',
            ),
            array('processor_id' => 9001,
                'processor' => 'ルミーズクレジットカード決済',
                'processor_script' => 'remise_cc.php',
                'processor_template' => 'views/orders/components/payments/remise_cc.tpl',
                'admin_template' => 'remise_cc.tpl',
                'callback' => 'N',
                'type' => 'P',
            ),
            array('processor_id' => 9004,
                'processor' => 'テレコムクレジット',
                'processor_script' => 'telecomcredit.php',
                'processor_template' => 'views/orders/components/payments/cc_outside.tpl',
                'admin_template' => 'telecomcredit.tpl',
                'callback' => 'N',
                'type' => 'P',
            ),
            array('processor_id' => 9005,
                'processor' => 'イプシロン',
                'processor_script' => 'epsilon.php',
                'processor_template' => 'views/orders/components/payments/cc_outside.tpl',
                'admin_template' => 'epsilon.tpl',
                'callback' => 'N',
                'type' => 'P',
            ),
            array('processor_id' => 9050,
                'processor' => 'ゼウスカード決済（リンク型）',
                'processor_script' => 'zeus.php',
                'processor_template' => 'views/orders/components/payments/cc_outside.tpl',
                'admin_template' => 'zeus.tpl',
                'callback' => 'N',
                'type' => 'P',
            ),
        );

        // プリセットする支払方法
        $payment_preset = array(
            array('usergroup_ids' => '0',
                'position' => 20,
                'status' => 'A',
                'template' => 'views/orders/components/payments/cc_outside.tpl',
                'params' => '',
                'a_surcharge' => '',
                'p_surcharge' => '',
                'localization' => '',
                'payment' => '銀行振込',
                'description' => '',
                'processor_script' => '',
                'company_id' => 1,
            ),
        );

        $_data = array();
        foreach ( $payment_methods as $_data) {
            db_query("REPLACE INTO ?:payment_processors ?e", $_data);
        }

        $_data = array();
        foreach ($payment_preset as $_data) {
            $payment_id = fn_update_payment($_data, '');
            // 登録済みのすべてのショップにおいて有効にする
            if (fn_allowed_for('ULTIMATE')){
                fn_share_object_to_all('payments', $payment_id);
            }
        }

        // 各支払方法の支払カテゴリーは一律「tab3（その他の支払方法）に変更する
        db_query('UPDATE ?:payments SET payment_category = ?s', 'tab3');

        // クレジットカード決済において２回目以降のカード情報入力を省略するためのテーブルを作成
        db_query("CREATE TABLE ?:jp_cc_quickpay (user_id mediumint(8) NOT NULL, payment_method varchar(64) NOT NULL, quickpay_id varchar(64) NOT NULL, PRIMARY KEY (user_id, payment_method)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        /////////////////////////////////////////////////////////////////////////
        // 支払方法の設定 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 注文ステータスのソート順を管理するテーブルを作成 BOF
        /////////////////////////////////////////////////////////////////////////
        db_query("DROP TABLE IF EXISTS ?:jp_order_status_sort");
        db_query("CREATE TABLE ?:jp_order_status_sort (status char(1) NOT NULL default '', sort_id mediumint(8) unsigned NOT NULL, PRIMARY KEY (`status`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        /////////////////////////////////////////////////////////////////////////
        // 注文ステータスのソート順を管理するテーブルを作成 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // CS-Cartのインストール日時を更新 BOF
        /////////////////////////////////////////////////////////////////////////
        $_data_install_date = array();
        $_data_install_date['object_id'] = 70024;
        $_data_install_date['value'] = time();
        $_data_install_date['object_type'] = 'O';

        foreach ($languages as $_data_install_date['lang_code'] => $_v) {
            Settings::instance()->updateDescription( $_data_install_date );
        }
        /////////////////////////////////////////////////////////////////////////
        // CS-Cartのインストール日時を更新 BOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 注文一覧とアドオン一覧に設定されたビューの名称を日本語化 BOF
        /////////////////////////////////////////////////////////////////////////
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '配送済み', 1, 'orders');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '入金済み', 2, 'orders');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '未完了', 3, 'orders');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '未了または失敗', 4, 'orders');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '中止', 17, 'orders');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '管理者未割当', 6, 'orders');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '最近更新分', 5, 'products');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '最近インストール', 13, 'addons');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', 'お気に入り', 14, 'addons');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', 'アップグレードあり', 15, 'addons');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '有効', 16, 'addons');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '商品タイプ', 18, 'blocks');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '最近更新分', 7, 'product_features');
        db_query('UPDATE ?:views SET name = ?s WHERE view_id =?i AND object =?s', '出品者作成', 8, 'product_features');
        /////////////////////////////////////////////////////////////////////////
        // 注文一覧とアドオン一覧に設定されたビューの名称を日本語化 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // Amazon Payアドオンをフルモードでのみ利用可能に BOF
        /////////////////////////////////////////////////////////////////////////
        //db_query("REPLACE INTO ?:storage_data (data_key, data) VALUES(?s, ?s)", 'addons_snapshots', '51371ebab8cb424c75d867fe73e50bed,6775b3701963b392791fa8c687bac742,d0091c6ed89f0aedd76a40863775277a,da2fd5324f611f2b1d8b4fef9ae3179e,1c80f2768de5b4fb4d2b3944d370cc7a,c69b98dafc2dfacb204cfd71400f3ca8,03121c8182a3b49ee95c327b6d3940b2,235acb56aad0eac7acf7ce56c756115c,12f8524c45544e9bb9448c45bd191081,449a22bd4fd9e552309e9175dec5745e,bd8bc36eb41bc90c585ae7e902e9e284,4c3b118e2c4d898d99f7ed6756f239f0,9beedfe36624c1c064be3382b97f2eb7,bcafeedd7dd058cb267db6bfb7086f27,68249180d0f8ced902a75a5444104dd4,3b8c35e3f8f78f15c6e98f33345ad991,b50e298ae54c7c326d21425c9bc59a39,90b93be7713dbd6bad07926f7d6eb55f,c06cd01ce149aa26966db5feaccfef6c,eaf45716a98a4bafe872c75c4d245b32,9292d36f62272ba6fc7cd9f3b04f79f9,879494ec811609b65a1d03fdba267b21,952e8cf2c863b8ddc656bac6ad0b729b,5a8b93606dea3db356b524f621e7a8bb,e9741eb2a4ec7d4bc13ce20d13627fc6,7bc397e032bdaae9dca38e5f5452f9a6,a1eff01a6862aea0d5237eb513a378d3,d590327cacc0208d3dcb54fe719e5831,32dc190b81f0b4dd9911972550576baa,281211c4c174214495bd2deb623e9b9e,bf9ad0cf4d2ffc6e54348937e904b667,694779637169a7bc5536f826faa0a05f,da2b534385b751f3fb550c43198dc87c,d9ddf16079b7ba158c82e819d2c363d1,d2e43e8c7123cdf91e4edd3380281d75,aadb0c6e3f30f8db66b89578b82a8a35,c8e43e20a7128fc60f2425a93a0f82c2,b3230f212f048d3087bf992923735b84,0642f2352e66f384142539f5cdd39491,2506ead1700ca25630c8123e5d2a205d,ecbc903855420b66f9132051f282d08d,6831915d94c2407bba96774a64b92dd5,126ac9f6149081eb0e97c2e939eaad52,9b1506af19d73a7a113458414544c6df,6a96538f14b69b31f469028c921b05c7,509f8c419805dc16e7bd457e29155ef3,a8d2d1bc25ab0c4691aa6940d405f091,d57ac45256849d9b13e2422d91580fb9,9f71cf66aabc45aca700ccd19d277437,135ec079d5684f3b4a5ee738fe5932b8');
        /////////////////////////////////////////////////////////////////////////
        // Amazon Payアドオンをフルモードでのみ利用可能に EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 試用期間を「パッケージダウンロードから30日間」から
        // 「インストールから30日間」に変更 BOF
        /////////////////////////////////////////////////////////////////////////
        $installation_timestamp = TIME;
        db_query("UPDATE ?:settings_objects SET value = $installation_timestamp WHERE name = 'current_timestamp'");
        /////////////////////////////////////////////////////////////////////////
        // 試用期間を「パッケージダウンロードから30日間」から
        // 「インストールから30日間」に変更 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // 基本設定: 注文手続きのデフォルト住所を変更 BOF
        /////////////////////////////////////////////////////////////////////////
        db_query("REPLACE INTO ?:settings_objects VALUES (20,'ROOT,ULT:VENDOR','default_country',6,0,'X','JP',410,'Y','',0)");
        db_query("REPLACE INTO ?:settings_objects VALUES (18,'ROOT,ULT:VENDOR','default_zipcode',6,0,'I','107-0052',420,'Y','',0)");
        db_query("REPLACE INTO ?:settings_objects VALUES (21,'ROOT,ULT:VENDOR','default_state',6,0,'W','東京都',430,'Y','',0)");
        db_query("REPLACE INTO ?:settings_objects VALUES (19,'ROOT,ULT:VENDOR','default_city',6,0,'I','港区',440,'Y','',0)");
        db_query("REPLACE INTO ?:settings_objects VALUES (17,'ROOT,ULT:VENDOR','default_address',6,0,'I','赤坂1-2-34 CSビル5F',450,'Y','',0)");
        db_query("REPLACE INTO ?:settings_objects VALUES (41,'ROOT,ULT:VENDOR','default_phone',6,0,'L','01-2345-6789',460,'Y','',0)");
        /////////////////////////////////////////////////////////////////////////
        // 基本設定: 注文手続きのデフォルト住所を変更 EOF
        /////////////////////////////////////////////////////////////////////////

        /////////////////////////////////////////////////////////////////////////
        // アドオン → 日本語版アドオン 設定用 BOF
        /////////////////////////////////////////////////////////////////////////
        db_query("CREATE TABLE ?:jp_settings (name varchar(100) NOT NULL, value varchar(100) NOT NULL, PRIMARY KEY (name)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        // 注文確定の姓名を分割する
        db_query("INSERT INTO ?:jp_settings(name, value) values('jp_checkout_fullname_mode', 'jp_checkout_fullname_no');");
        /////////////////////////////////////////////////////////////////////////
        // アドオン → 日本語版アドオン 設定用 EOF
        /////////////////////////////////////////////////////////////////////////
}




function fn_lcjp_get_jp_shipping_rates_table()
{
    return array(
        // 佐川急便 - 飛脚宅配便
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 1, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1870, 'B' => 1430, 'C' => 1430, 'D' => 1210, 'E' => 1210, 'F' => 990, 'G' => 990, 'H' => 880, 'I' => 770, 'J' => 880, 'K' => 770, 'L' => 770, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2145, 'B' => 1705, 'C' => 1705, 'D' => 1485, 'E' => 1485, 'F' => 1265, 'G' => 1265, 'H' => 1155, 'I' => 1045, 'J' => 1155, 'K' => 1045, 'L' => 1045, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2486, 'B' => 2046, 'C' => 2046, 'D' => 1826, 'E' => 1826, 'F' => 1606, 'G' => 1606, 'H' => 1496, 'I' => 1386, 'J' => 1496, 'K' => 1386, 'L' => 1386, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2948, 'B' => 2508, 'C' => 2508, 'D' => 2288, 'E' => 2288, 'F' => 2068, 'G' => 2068, 'H' => 1958, 'I' => 1848, 'J' => 1958, 'K' => 1848, 'L' => 1848, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3168, 'B' => 2728, 'C' => 2728, 'D' => 2508, 'E' => 2508, 'F' => 2288, 'G' => 2288, 'H' => 2178, 'I' => 2068, 'J' => 2178, 'K' => 2068, 'L' => 2068, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 5555, 'B' => 4785, 'C' => 4510, 'D' => 3960, 'E' => 3740, 'F' => 3465, 'G' => 3465, 'H' => 3245, 'I' => 3025, 'J' => 3245, 'K' => 2530, 'L' => 2420, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 6325, 'B' => 5390, 'C' => 5115, 'D' => 4400, 'E' => 4180, 'F' => 3850, 'G' => 3850, 'H' => 3575, 'I' => 3300, 'J' => 3520, 'K' => 2695, 'L' => 2695, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 8030, 'B' => 7260, 'C' => 6380, 'D' => 5500, 'E' => 5170, 'F' => 4730, 'G' => 4730, 'H' => 4400, 'I' => 4015, 'J' => 4290, 'K' => 3245, 'L' => 3245, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 9735, 'B' => 8195, 'C' => 7700, 'D' => 6545, 'E' => 6160, 'F' => 5555, 'G' => 5610, 'H' => 5170, 'I' => 4730, 'J' => 5060, 'K' => 3795, 'L' => 3795, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 13145, 'B' => 11000, 'C' => 10285, 'D' => 8690, 'E' => 8140, 'F' => 7315, 'G' => 7370, 'H' => 6765, 'I' => 6105, 'J' => 6600, 'K' => 4895, 'L' => 4895, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 16555, 'B' => 14355, 'C' => 12870, 'D' => 10835, 'E' => 10065, 'F' => 9075, 'G' => 9130, 'H' => 8360, 'I' => 7535, 'J' => 8195, 'K' => 5995, 'L' => 5995, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 2, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1870, 'B' => 1430, 'C' => 1430, 'D' => 1210, 'E' => 1210, 'F' => 990, 'G' => 990, 'H' => 880, 'I' => 770, 'J' => 880, 'K' => 770, 'L' => 770, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2145, 'B' => 1705, 'C' => 1705, 'D' => 1485, 'E' => 1485, 'F' => 1265, 'G' => 1265, 'H' => 1155, 'I' => 1045, 'J' => 1155, 'K' => 1045, 'L' => 1045, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2486, 'B' => 2046, 'C' => 2046, 'D' => 1826, 'E' => 1826, 'F' => 1606, 'G' => 1606, 'H' => 1496, 'I' => 1386, 'J' => 1496, 'K' => 1386, 'L' => 1386, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2948, 'B' => 2508, 'C' => 2508, 'D' => 2288, 'E' => 2288, 'F' => 2068, 'G' => 2068, 'H' => 1958, 'I' => 1848, 'J' => 1958, 'K' => 1848, 'L' => 1848, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3168, 'B' => 2728, 'C' => 2728, 'D' => 2508, 'E' => 2508, 'F' => 2288, 'G' => 2288, 'H' => 2178, 'I' => 2068, 'J' => 2178, 'K' => 2068, 'L' => 2068, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 5335, 'B' => 4510, 'C' => 4015, 'D' => 3630, 'E' => 3520, 'F' => 3135, 'G' => 3190, 'H' => 2915, 'I' => 2640, 'J' => 2805, 'K' => 2420, 'L' => 2530, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 6050, 'B' => 5060, 'C' => 4510, 'D' => 4015, 'E' => 3905, 'F' => 3410, 'G' => 3520, 'H' => 3135, 'I' => 2860, 'J' => 3025, 'K' => 2695, 'L' => 2695, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 7645, 'B' => 6325, 'C' => 5610, 'D' => 5005, 'E' => 4840, 'F' => 4125, 'G' => 4235, 'H' => 3795, 'I' => 3355, 'J' => 3630, 'K' => 3245, 'L' => 3245, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 9240, 'B' => 7590, 'C' => 6655, 'D' => 5940, 'E' => 5720, 'F' => 4895, 'G' => 5005, 'H' => 4400, 'I' => 3905, 'J' => 4235, 'K' => 3795, 'L' => 3795, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 12485, 'B' => 10340, 'C' => 8855, 'D' => 7810, 'E' => 7535, 'F' => 6325, 'G' => 6545, 'H' => 5720, 'I' => 5005, 'J' => 5390, 'K' => 4895, 'L' => 4895, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 15675, 'B' => 12815, 'C' => 11055, 'D' => 9680, 'E' => 9350, 'F' => 7810, 'G' => 8085, 'H' => 6985, 'I' => 6105, 'J' => 6600, 'K' => 5995, 'L' => 5995, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 3, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1760, 'B' => 1320, 'C' => 1320, 'D' => 1100, 'E' => 1100, 'F' => 990, 'G' => 990, 'H' => 880, 'I' => 880, 'J' => 770, 'K' => 880, 'L' => 880, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2035, 'B' => 1595, 'C' => 1595, 'D' => 1375, 'E' => 1375, 'F' => 1265, 'G' => 1265, 'H' => 1155, 'I' => 1155, 'J' => 1045, 'K' => 1155, 'L' => 1155, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2376, 'B' => 1936, 'C' => 1936, 'D' => 1716, 'E' => 1716, 'F' => 1606, 'G' => 1606, 'H' => 1496, 'I' => 1496, 'J' => 1386, 'K' => 1496, 'L' => 1496, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2838, 'B' => 2398, 'C' => 2398, 'D' => 2178, 'E' => 2178, 'F' => 2068, 'G' => 2068, 'H' => 1958, 'I' => 1958, 'J' => 1848, 'K' => 1958, 'L' => 1958, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3058, 'B' => 2618, 'C' => 2618, 'D' => 2398, 'E' => 2398, 'F' => 2288, 'G' => 2288, 'H' => 2178, 'I' => 2178, 'J' => 2068, 'K' => 2178, 'L' => 2178, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 5060, 'B' => 4070, 'C' => 3685, 'D' => 3410, 'E' => 3245, 'F' => 2915, 'G' => 3025, 'H' => 2640, 'I' => 2530, 'J' => 2420, 'K' => 2805, 'L' => 3245, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 5720, 'B' => 4510, 'C' => 4070, 'D' => 3740, 'E' => 3575, 'F' => 3190, 'G' => 3300, 'H' => 2805, 'I' => 2805, 'J' => 2695, 'K' => 3025, 'L' => 3520, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 7260, 'B' => 5665, 'C' => 5060, 'D' => 4565, 'E' => 4345, 'F' => 3850, 'G' => 3960, 'H' => 3355, 'I' => 3355, 'J' => 3245, 'K' => 3630, 'L' => 4290, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 8745, 'B' => 6765, 'C' => 5995, 'D' => 5445, 'E' => 5115, 'F' => 4510, 'G' => 4675, 'H' => 3905, 'I' => 3905, 'J' => 3795, 'K' => 4235, 'L' => 5060, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 11770, 'B' => 8965, 'C' => 7920, 'D' => 7095, 'E' => 6710, 'F' => 5830, 'G' => 6050, 'H' => 5005, 'I' => 5005, 'J' => 4895, 'K' => 5390, 'L' => 6600, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 14795, 'B' => 11220, 'C' => 9845, 'D' => 8800, 'E' => 8250, 'F' => 7150, 'G' => 7425, 'H' => 6105, 'I' => 6105, 'J' => 5995, 'K' => 6600, 'L' => 8195, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 4, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1650, 'B' => 1210, 'C' => 1210, 'D' => 990, 'E' => 990, 'F' => 880, 'G' => 880, 'H' => 770, 'I' => 770, 'J' => 880, 'K' => 770, 'L' => 770, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1925, 'B' => 1485, 'C' => 1485, 'D' => 1265, 'E' => 1265, 'F' => 1155, 'G' => 1155, 'H' => 1045, 'I' => 1045, 'J' => 1155, 'K' => 1045, 'L' => 1045, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2266, 'B' => 1826, 'C' => 1826, 'D' => 1606, 'E' => 1606, 'F' => 1496, 'G' => 1496, 'H' => 1386, 'I' => 1386, 'J' => 1496, 'K' => 1386, 'L' => 1386, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2728, 'B' => 2288, 'C' => 2288, 'D' => 2068, 'E' => 2068, 'F' => 1958, 'G' => 1958, 'H' => 1848, 'I' => 1848, 'J' => 1958, 'K' => 1848, 'L' => 1848, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2948, 'B' => 2508, 'C' => 2508, 'D' => 2288, 'E' => 2288, 'F' => 2178, 'G' => 2178, 'H' => 2068, 'I' => 2068, 'J' => 2178, 'K' => 2068, 'L' => 2068, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 5060, 'B' => 4070, 'C' => 3685, 'D' => 3410, 'E' => 3245, 'F' => 2915, 'G' => 3025, 'H' => 2640, 'I' => 2420, 'J' => 2530, 'K' => 2640, 'L' => 3025, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 5720, 'B' => 4510, 'C' => 4070, 'D' => 3740, 'E' => 3575, 'F' => 3190, 'G' => 3300, 'H' => 2805, 'I' => 2695, 'J' => 2805, 'K' => 2860, 'L' => 3300, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 7205, 'B' => 5665, 'C' => 5060, 'D' => 4565, 'E' => 4345, 'F' => 3850, 'G' => 3960, 'H' => 3355, 'I' => 3245, 'J' => 3355, 'K' => 3355, 'L' => 4015, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 8690, 'B' => 6765, 'C' => 5995, 'D' => 5445, 'E' => 5115, 'F' => 4510, 'G' => 4675, 'H' => 3850, 'I' => 3795, 'J' => 3905, 'K' => 3905, 'L' => 4730, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 11715, 'B' => 8965, 'C' => 7920, 'D' => 7095, 'E' => 6710, 'F' => 5830, 'G' => 6050, 'H' => 4950, 'I' => 4895, 'J' => 5005, 'K' => 5005, 'L' => 6105, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 14740, 'B' => 11220, 'C' => 9845, 'D' => 8800, 'E' => 8250, 'F' => 7150, 'G' => 7425, 'H' => 5995, 'I' => 5995, 'J' => 6105, 'K' => 6105, 'L' => 7535, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 5, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1540, 'B' => 1100, 'C' => 990, 'D' => 880, 'E' => 880, 'F' => 770, 'G' => 770, 'H' => 770, 'I' => 770, 'J' => 880, 'K' => 880, 'L' => 880, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1815, 'B' => 1375, 'C' => 1265, 'D' => 1155, 'E' => 1155, 'F' => 1045, 'G' => 1045, 'H' => 1045, 'I' => 1045, 'J' => 1155, 'K' => 1155, 'L' => 1155, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2156, 'B' => 1716, 'C' => 1606, 'D' => 1496, 'E' => 1496, 'F' => 1386, 'G' => 1386, 'H' => 1386, 'I' => 1386, 'J' => 1496, 'K' => 1496, 'L' => 1496, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2618, 'B' => 2178, 'C' => 2068, 'D' => 1958, 'E' => 1958, 'F' => 1848, 'G' => 1848, 'H' => 1848, 'I' => 1848, 'J' => 1958, 'K' => 1958, 'L' => 1958, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2838, 'B' => 2398, 'C' => 2288, 'D' => 2178, 'E' => 2178, 'F' => 2068, 'G' => 2068, 'H' => 2068, 'I' => 2068, 'J' => 2178, 'K' => 2178, 'L' => 2178, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 4565, 'B' => 3850, 'C' => 3465, 'D' => 3135, 'E' => 2750, 'F' => 2585, 'G' => 2530, 'H' => 2420, 'I' => 2640, 'J' => 2640, 'K' => 2915, 'L' => 3245, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 5115, 'B' => 4290, 'C' => 3850, 'D' => 3410, 'E' => 2970, 'F' => 2750, 'G' => 2695, 'H' => 2695, 'I' => 2805, 'J' => 2805, 'K' => 3135, 'L' => 3575, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 6435, 'B' => 5335, 'C' => 4730, 'D' => 4180, 'E' => 3575, 'F' => 3245, 'G' => 3245, 'H' => 3245, 'I' => 3355, 'J' => 3355, 'K' => 3795, 'L' => 4400, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 7755, 'B' => 6380, 'C' => 5555, 'D' => 4895, 'E' => 4180, 'F' => 3795, 'G' => 3795, 'H' => 3795, 'I' => 3850, 'J' => 3905, 'K' => 4400, 'L' => 5170, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 10340, 'B' => 8470, 'C' => 7315, 'D' => 6380, 'E' => 5390, 'F' => 4895, 'G' => 4895, 'H' => 4895, 'I' => 4950, 'J' => 5005, 'K' => 5720, 'L' => 6765, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 12980, 'B' => 10505, 'C' => 9075, 'D' => 7865, 'E' => 6545, 'F' => 5995, 'G' => 5995, 'H' => 5995, 'I' => 5995, 'J' => 6105, 'K' => 6985, 'L' => 8360, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 6, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1320, 'B' => 990, 'C' => 880, 'D' => 770, 'E' => 770, 'F' => 770, 'G' => 770, 'H' => 770, 'I' => 880, 'J' => 990, 'K' => 990, 'L' => 990, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1595, 'B' => 1265, 'C' => 1155, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1045, 'I' => 1155, 'J' => 1265, 'K' => 1265, 'L' => 1265, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1936, 'B' => 1606, 'C' => 1496, 'D' => 1386, 'E' => 1386, 'F' => 1386, 'G' => 1386, 'H' => 1386, 'I' => 1496, 'J' => 1606, 'K' => 1606, 'L' => 1606, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2398, 'B' => 2068, 'C' => 1958, 'D' => 1848, 'E' => 1848, 'F' => 1848, 'G' => 1848, 'H' => 1848, 'I' => 1958, 'J' => 2068, 'K' => 2068, 'L' => 2068, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2618, 'B' => 2288, 'C' => 2178, 'D' => 2068, 'E' => 2068, 'F' => 2068, 'G' => 2068, 'H' => 2068, 'I' => 2178, 'J' => 2288, 'K' => 2288, 'L' => 2288, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 4455, 'B' => 3520, 'C' => 3190, 'D' => 3135, 'E' => 2695, 'F' => 2585, 'G' => 2420, 'H' => 2530, 'I' => 3025, 'J' => 3025, 'K' => 3190, 'L' => 3465, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 5005, 'B' => 3850, 'C' => 3520, 'D' => 3410, 'E' => 2915, 'F' => 2750, 'G' => 2695, 'H' => 2695, 'I' => 3300, 'J' => 3300, 'K' => 3520, 'L' => 3850, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 6270, 'B' => 4785, 'C' => 4290, 'D' => 4180, 'E' => 3465, 'F' => 3245, 'G' => 3245, 'H' => 3245, 'I' => 3960, 'J' => 3960, 'K' => 4235, 'L' => 4730, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 7535, 'B' => 5665, 'C' => 5060, 'D' => 4895, 'E' => 4070, 'F' => 3795, 'G' => 3795, 'H' => 3795, 'I' => 4675, 'J' => 4675, 'K' => 5005, 'L' => 5610, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 10065, 'B' => 7425, 'C' => 6600, 'D' => 6380, 'E' => 5170, 'F' => 4895, 'G' => 4895, 'H' => 4895, 'I' => 6050, 'J' => 6050, 'K' => 6545, 'L' => 7370, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 12595, 'B' => 9185, 'C' => 8140, 'D' => 7865, 'E' => 6325, 'F' => 5995, 'G' => 5995, 'H' => 5995, 'I' => 7425, 'J' => 7425, 'K' => 8085, 'L' => 9130, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 7, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1320, 'B' => 990, 'C' => 880, 'D' => 770, 'E' => 770, 'F' => 770, 'G' => 770, 'H' => 770, 'I' => 880, 'J' => 990, 'K' => 990, 'L' => 990, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1595, 'B' => 1265, 'C' => 1155, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1045, 'I' => 1155, 'J' => 1265, 'K' => 1265, 'L' => 1265, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1936, 'B' => 1606, 'C' => 1496, 'D' => 1386, 'E' => 1386, 'F' => 1386, 'G' => 1386, 'H' => 1386, 'I' => 1496, 'J' => 1606, 'K' => 1606, 'L' => 1606, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2398, 'B' => 2068, 'C' => 1958, 'D' => 1848, 'E' => 1848, 'F' => 1848, 'G' => 1848, 'H' => 1848, 'I' => 1958, 'J' => 2068, 'K' => 2068, 'L' => 2068, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2618, 'B' => 2288, 'C' => 2178, 'D' => 2068, 'E' => 2068, 'F' => 2068, 'G' => 2068, 'H' => 2068, 'I' => 2178, 'J' => 2288, 'K' => 2288, 'L' => 2288, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 4455, 'B' => 3520, 'C' => 3190, 'D' => 3135, 'E' => 2695, 'F' => 2420, 'G' => 2585, 'H' => 2585, 'I' => 2915, 'J' => 2915, 'K' => 3135, 'L' => 3465, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 5005, 'B' => 3850, 'C' => 3520, 'D' => 3410, 'E' => 2915, 'F' => 2695, 'G' => 2750, 'H' => 2750, 'I' => 3190, 'J' => 3190, 'K' => 3410, 'L' => 3850, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 6270, 'B' => 4785, 'C' => 4290, 'D' => 4180, 'E' => 3465, 'F' => 3245, 'G' => 3245, 'H' => 3245, 'I' => 3850, 'J' => 3850, 'K' => 4125, 'L' => 4730, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 7535, 'B' => 5665, 'C' => 5060, 'D' => 4895, 'E' => 4070, 'F' => 3795, 'G' => 3795, 'H' => 3795, 'I' => 4510, 'J' => 4510, 'K' => 4895, 'L' => 5555, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 10065, 'B' => 7425, 'C' => 6600, 'D' => 6380, 'E' => 5170, 'F' => 4895, 'G' => 4895, 'H' => 4895, 'I' => 5830, 'J' => 5830, 'K' => 6325, 'L' => 7315, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 12595, 'B' => 9185, 'C' => 8140, 'D' => 7865, 'E' => 6325, 'F' => 5995, 'G' => 5995, 'H' => 5995, 'I' => 7150, 'J' => 7150, 'K' => 7810, 'L' => 9075, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 8, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1210, 'B' => 880, 'C' => 770, 'D' => 770, 'E' => 770, 'F' => 770, 'G' => 770, 'H' => 880, 'I' => 990, 'J' => 1100, 'K' => 1210, 'L' => 1210, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1485, 'B' => 1155, 'C' => 1045, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1155, 'I' => 1265, 'J' => 1375, 'K' => 1485, 'L' => 1485, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1826, 'B' => 1496, 'C' => 1386, 'D' => 1386, 'E' => 1386, 'F' => 1386, 'G' => 1386, 'H' => 1496, 'I' => 1606, 'J' => 1716, 'K' => 1826, 'L' => 1826, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2288, 'B' => 1958, 'C' => 1848, 'D' => 1848, 'E' => 1848, 'F' => 1848, 'G' => 1848, 'H' => 1958, 'I' => 2068, 'J' => 2178, 'K' => 2288, 'L' => 2288, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2508, 'B' => 2178, 'C' => 2068, 'D' => 2068, 'E' => 2068, 'F' => 2068, 'G' => 2068, 'H' => 2178, 'I' => 2288, 'J' => 2398, 'K' => 2508, 'L' => 2508, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 4290, 'B' => 3410, 'C' => 3190, 'D' => 3135, 'E' => 2420, 'F' => 2695, 'G' => 2695, 'H' => 2750, 'I' => 3245, 'J' => 3245, 'K' => 3520, 'L' => 3740, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 4785, 'B' => 3740, 'C' => 3520, 'D' => 3410, 'E' => 2695, 'F' => 2915, 'G' => 2915, 'H' => 2970, 'I' => 3575, 'J' => 3575, 'K' => 3905, 'L' => 4180, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 5995, 'B' => 4565, 'C' => 4290, 'D' => 4180, 'E' => 3245, 'F' => 3465, 'G' => 3465, 'H' => 3575, 'I' => 4345, 'J' => 4345, 'K' => 4840, 'L' => 5170, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 7205, 'B' => 5390, 'C' => 5060, 'D' => 4895, 'E' => 3795, 'F' => 4070, 'G' => 4070, 'H' => 4180, 'I' => 5115, 'J' => 5115, 'K' => 5720, 'L' => 6160, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 9625, 'B' => 7095, 'C' => 6600, 'D' => 6380, 'E' => 4895, 'F' => 5170, 'G' => 5170, 'H' => 5390, 'I' => 6710, 'J' => 6710, 'K' => 7535, 'L' => 8140, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 12045, 'B' => 8800, 'C' => 8140, 'D' => 7865, 'E' => 5995, 'F' => 6325, 'G' => 6325, 'H' => 6545, 'I' => 8250, 'J' => 8250, 'K' => 9350, 'L' => 10065, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 9, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1210, 'B' => 880, 'C' => 770, 'D' => 770, 'E' => 770, 'F' => 770, 'G' => 770, 'H' => 880, 'I' => 990, 'J' => 1100, 'K' => 1210, 'L' => 1210, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1485, 'B' => 1155, 'C' => 1045, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1155, 'I' => 1265, 'J' => 1375, 'K' => 1485, 'L' => 1485, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1826, 'B' => 1496, 'C' => 1386, 'D' => 1386, 'E' => 1386, 'F' => 1386, 'G' => 1386, 'H' => 1496, 'I' => 1606, 'J' => 1716, 'K' => 1826, 'L' => 1826, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2288, 'B' => 1958, 'C' => 1848, 'D' => 1848, 'E' => 1848, 'F' => 1848, 'G' => 1848, 'H' => 1958, 'I' => 2068, 'J' => 2178, 'K' => 2288, 'L' => 2288, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2508, 'B' => 2178, 'C' => 2068, 'D' => 2068, 'E' => 2068, 'F' => 2068, 'G' => 2068, 'H' => 2178, 'I' => 2288, 'J' => 2398, 'K' => 2508, 'L' => 2508, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 3960, 'B' => 3410, 'C' => 3190, 'D' => 2420, 'E' => 3135, 'F' => 3135, 'G' => 3135, 'H' => 3135, 'I' => 3410, 'J' => 3410, 'K' => 3630, 'L' => 3960, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 4455, 'B' => 3740, 'C' => 3520, 'D' => 2695, 'E' => 3410, 'F' => 3410, 'G' => 3410, 'H' => 3410, 'I' => 3740, 'J' => 3740, 'K' => 4015, 'L' => 4400, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 5500, 'B' => 4565, 'C' => 4290, 'D' => 3245, 'E' => 4180, 'F' => 4180, 'G' => 4180, 'H' => 4180, 'I' => 4565, 'J' => 4565, 'K' => 5005, 'L' => 5500, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 6985, 'B' => 5390, 'C' => 5060, 'D' => 3795, 'E' => 4895, 'F' => 4895, 'G' => 4895, 'H' => 4895, 'I' => 5445, 'J' => 5445, 'K' => 5940, 'L' => 6545, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 8745, 'B' => 7095, 'C' => 6600, 'D' => 4895, 'E' => 6380, 'F' => 6380, 'G' => 6380, 'H' => 6380, 'I' => 7095, 'J' => 7095, 'K' => 7810, 'L' => 8690, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 10945, 'B' => 8800, 'C' => 8140, 'D' => 5995, 'E' => 7865, 'F' => 7865, 'G' => 7865, 'H' => 7865, 'I' => 8800, 'J' => 8800, 'K' => 9680, 'L' => 10835, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 10, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1100, 'B' => 770, 'C' => 770, 'D' => 770, 'E' => 770, 'F' => 880, 'G' => 880, 'H' => 990, 'I' => 1210, 'J' => 1320, 'K' => 1430, 'L' => 1430, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1375, 'B' => 1045, 'C' => 1045, 'D' => 1045, 'E' => 1045, 'F' => 1155, 'G' => 1155, 'H' => 1265, 'I' => 1485, 'J' => 1595, 'K' => 1705, 'L' => 1705, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1716, 'B' => 1386, 'C' => 1386, 'D' => 1386, 'E' => 1386, 'F' => 1496, 'G' => 1496, 'H' => 1606, 'I' => 1826, 'J' => 1936, 'K' => 2046, 'L' => 2046, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2178, 'B' => 1848, 'C' => 1848, 'D' => 1848, 'E' => 1848, 'F' => 1958, 'G' => 1958, 'H' => 2068, 'I' => 2288, 'J' => 2398, 'K' => 2508, 'L' => 2508, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2398, 'B' => 2068, 'C' => 2068, 'D' => 2068, 'E' => 2068, 'F' => 2178, 'G' => 2178, 'H' => 2288, 'I' => 2508, 'J' => 2618, 'K' => 2728, 'L' => 2728, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 3850, 'B' => 2420, 'C' => 2420, 'D' => 3190, 'E' => 3190, 'F' => 3190, 'G' => 3190, 'H' => 3465, 'I' => 3685, 'J' => 3685, 'K' => 4015, 'L' => 4510, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 4290, 'B' => 2695, 'C' => 2695, 'D' => 3520, 'E' => 3520, 'F' => 3520, 'G' => 3520, 'H' => 3850, 'I' => 4070, 'J' => 4070, 'K' => 4510, 'L' => 5115, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 5335, 'B' => 3245, 'C' => 3245, 'D' => 4290, 'E' => 4290, 'F' => 4290, 'G' => 4290, 'H' => 4730, 'I' => 5060, 'J' => 5060, 'K' => 5610, 'L' => 6380, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 6875, 'B' => 3795, 'C' => 3795, 'D' => 5060, 'E' => 5060, 'F' => 5060, 'G' => 5060, 'H' => 5555, 'I' => 5995, 'J' => 5995, 'K' => 6655, 'L' => 7700, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 8470, 'B' => 4895, 'C' => 4895, 'D' => 6600, 'E' => 6600, 'F' => 6600, 'G' => 6600, 'H' => 7315, 'I' => 7920, 'J' => 7920, 'K' => 8855, 'L' => 10285, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 10560, 'B' => 5995, 'C' => 5995, 'D' => 8140, 'E' => 8140, 'F' => 8140, 'G' => 8140, 'H' => 9075, 'I' => 9845, 'J' => 9845, 'K' => 11055, 'L' => 12870, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 11, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 990, 'B' => 770, 'C' => 770, 'D' => 880, 'E' => 880, 'F' => 990, 'G' => 990, 'H' => 1100, 'I' => 1210, 'J' => 1320, 'K' => 1430, 'L' => 1430, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1265, 'B' => 1045, 'C' => 1045, 'D' => 1155, 'E' => 1155, 'F' => 1265, 'G' => 1265, 'H' => 1375, 'I' => 1485, 'J' => 1595, 'K' => 1705, 'L' => 1705, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1606, 'B' => 1386, 'C' => 1386, 'D' => 1496, 'E' => 1496, 'F' => 1606, 'G' => 1606, 'H' => 1716, 'I' => 1826, 'J' => 1936, 'K' => 2046, 'L' => 2046, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2068, 'B' => 1848, 'C' => 1848, 'D' => 1958, 'E' => 1958, 'F' => 2068, 'G' => 2068, 'H' => 2178, 'I' => 2288, 'J' => 2398, 'K' => 2508, 'L' => 2508, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2288, 'B' => 2068, 'C' => 2068, 'D' => 2178, 'E' => 2178, 'F' => 2288, 'G' => 2288, 'H' => 2398, 'I' => 2508, 'J' => 2618, 'K' => 2728, 'L' => 2728, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 3795, 'B' => 2420, 'C' => 2420, 'D' => 3410, 'E' => 3410, 'F' => 3520, 'G' => 3520, 'H' => 3850, 'I' => 4070, 'J' => 4070, 'K' => 4510, 'L' => 4785, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 4180, 'B' => 2695, 'C' => 2695, 'D' => 3740, 'E' => 3740, 'F' => 3850, 'G' => 3850, 'H' => 4290, 'I' => 4510, 'J' => 4510, 'K' => 5060, 'L' => 5390, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 5170, 'B' => 3245, 'C' => 3245, 'D' => 4565, 'E' => 4565, 'F' => 4785, 'G' => 4785, 'H' => 5335, 'I' => 5665, 'J' => 5665, 'K' => 6325, 'L' => 7260, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 6765, 'B' => 3795, 'C' => 3795, 'D' => 5390, 'E' => 5390, 'F' => 5665, 'G' => 5665, 'H' => 6380, 'I' => 6765, 'J' => 6765, 'K' => 7590, 'L' => 8195, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 8360, 'B' => 4895, 'C' => 4895, 'D' => 7095, 'E' => 7095, 'F' => 7425, 'G' => 7425, 'H' => 8470, 'I' => 8965, 'J' => 8965, 'K' => 10340, 'L' => 11000, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 10175, 'B' => 5995, 'C' => 5995, 'D' => 8800, 'E' => 8800, 'F' => 9185, 'G' => 9185, 'H' => 10505, 'I' => 11220, 'J' => 11220, 'K' => 12815, 'L' => 14355, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 12, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 770, 'B' => 990, 'C' => 1100, 'D' => 1210, 'E' => 1210, 'F' => 1320, 'G' => 1320, 'H' => 1540, 'I' => 1650, 'J' => 1760, 'K' => 1870, 'L' => 1870, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1045, 'B' => 1265, 'C' => 1375, 'D' => 1485, 'E' => 1485, 'F' => 1595, 'G' => 1595, 'H' => 1815, 'I' => 1925, 'J' => 2035, 'K' => 2145, 'L' => 2145, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1386, 'B' => 1606, 'C' => 1716, 'D' => 1826, 'E' => 1826, 'F' => 1936, 'G' => 1936, 'H' => 2156, 'I' => 2266, 'J' => 2376, 'K' => 2486, 'L' => 2486, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 1848, 'B' => 2068, 'C' => 2178, 'D' => 2288, 'E' => 2288, 'F' => 2398, 'G' => 2398, 'H' => 2618, 'I' => 2728, 'J' => 2838, 'K' => 2948, 'L' => 2948, 'M' => 0, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2068, 'B' => 2288, 'C' => 2398, 'D' => 2508, 'E' => 2508, 'F' => 2618, 'G' => 2618, 'H' => 2838, 'I' => 2948, 'J' => 3058, 'K' => 3168, 'L' => 3168, 'M' => 0, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 2420, 'B' => 3795, 'C' => 3850, 'D' => 3960, 'E' => 4290, 'F' => 4455, 'G' => 4455, 'H' => 4565, 'I' => 5060, 'J' => 5060, 'K' => 5335, 'L' => 5555, 'M' => 0, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 2695, 'B' => 4180, 'C' => 4290, 'D' => 4455, 'E' => 4785, 'F' => 5005, 'G' => 5005, 'H' => 5115, 'I' => 5720, 'J' => 5720, 'K' => 6050, 'L' => 6325, 'M' => 0, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 3245, 'B' => 5170, 'C' => 5335, 'D' => 5500, 'E' => 5995, 'F' => 6270, 'G' => 6270, 'H' => 6435, 'I' => 7205, 'J' => 7260, 'K' => 7645, 'L' => 8030, 'M' => 0, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 3795, 'B' => 6765, 'C' => 6875, 'D' => 6985, 'E' => 7205, 'F' => 7535, 'G' => 7535, 'H' => 7755, 'I' => 8690, 'J' => 8745, 'K' => 9240, 'L' => 9735, 'M' => 0, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 4895, 'B' => 8360, 'C' => 8470, 'D' => 8745, 'E' => 9625, 'F' => 10065, 'G' => 10065, 'H' => 10340, 'I' => 11715, 'J' => 11770, 'K' => 12485, 'L' => 13145, 'M' => 0, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 5995, 'B' => 10175, 'C' => 10560, 'D' => 10945, 'E' => 12045, 'F' => 12595, 'G' => 12595, 'H' => 12980, 'I' => 14740, 'J' => 14795, 'K' => 15675, 'L' => 16555, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'standard', 'zone_id' => 13, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 770, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 1045, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 1386, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 1848, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 2068, ), ),
            170 => array ( 'size' => '170', 'weight' => '50', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 2420, ), ),
            180 => array ( 'size' => '180', 'weight' => '50', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 2695, ), ),
            200 => array ( 'size' => '200', 'weight' => '50', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 3245, ), ),
            220 => array ( 'size' => '220', 'weight' => '50', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 3795, ), ),
            240 => array ( 'size' => '240', 'weight' => '50', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 4895, ), ),
            260 => array ( 'size' => '260', 'weight' => '50', 'rates' => array ('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 5995, ), ),
            )
        ),
        // 佐川急便 - 飛脚クール便
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 1, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 2145, 'B' => 1705, 'C' => 1705, 'D' => 1485, 'E' => 1485, 'F' => 1265, 'G' => 1265, 'H' => 1155, 'I' => 1045, 'J' => 1155, 'K' => 1045, 'L' => 1045, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2475, 'B' => 2035, 'C' => 2035, 'D' => 1815, 'E' => 1815, 'F' => 1595, 'G' => 1595, 'H' => 1485, 'I' => 1375, 'J' => 1485, 'K' => 1375, 'L' => 1375, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2926, 'B' => 2486, 'C' => 2486, 'D' => 2266, 'E' => 2266, 'F' => 2046, 'G' => 2046, 'H' => 1936, 'I' => 1826, 'J' => 1936, 'K' => 1826, 'L' => 1826, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 3663, 'B' => 3223, 'C' => 3223, 'D' => 3003, 'E' => 3003, 'F' => 2783, 'G' => 2783, 'H' => 2673, 'I' => 2563, 'J' => 2673, 'K' => 2563, 'L' => 2563, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 2, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>2145, 'B' => 1705, 'C' => 1705, 'D' => 1485, 'E' => 1485, 'F' => 1265, 'G' => 1265, 'H' => 1155, 'I' => 1045, 'J' => 1155, 'K' => 1045, 'L' => 1045, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2475, 'B' => 2035, 'C' => 2035, 'D' => 1815, 'E' => 1815, 'F' => 1595, 'G' => 1595, 'H' => 1485, 'I' => 1375, 'J' => 1485, 'K' => 1375, 'L' => 1375, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2926, 'B' => 2486, 'C' => 2486, 'D' => 2266, 'E' => 2266, 'F' => 2046, 'G' => 2046, 'H' => 1936, 'I' => 1826, 'J' => 1936, 'K' => 1826, 'L' => 1826, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3663, 'B' => 3223, 'C' => 3223, 'D' => 3003, 'E' => 3003, 'F' => 2783, 'G' => 2783, 'H' => 2673, 'I' => 2563, 'J' => 2673, 'K' => 2563, 'L' => 2563, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 3, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>2035, 'B' => 1595, 'C' => 1595, 'D' => 1375, 'E' => 1375, 'F' => 1265, 'G' => 1265, 'H' => 1155, 'I' => 1155, 'J' => 1045, 'K' => 1155, 'L' => 1155, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2365, 'B' => 1925, 'C' => 1925, 'D' => 1705, 'E' => 1705, 'F' => 1595, 'G' => 1595, 'H' => 1485, 'I' => 1485, 'J' => 1375, 'K' => 1485, 'L' => 1485, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2816, 'B' => 2376, 'C' => 2376, 'D' => 2156, 'E' => 2156, 'F' => 2046, 'G' => 2046, 'H' => 1936, 'I' => 1936, 'J' => 1826, 'K' => 1936, 'L' => 1936, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3553, 'B' => 3113, 'C' => 3113, 'D' => 2893, 'E' => 2893, 'F' => 2783, 'G' => 2783, 'H' => 2673, 'I' => 2673, 'J' => 2563, 'K' => 2673, 'L' => 2673, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 4, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1925, 'B' => 1485, 'C' => 1485, 'D' => 1265, 'E' => 1265, 'F' => 1155, 'G' => 1155, 'H' => 1045, 'I' => 1045, 'J' => 1155, 'K' => 1045, 'L' => 1045, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2255, 'B' => 1815, 'C' => 1815, 'D' => 1595, 'E' => 1595, 'F' => 1485, 'G' => 1485, 'H' => 1375, 'I' => 1375, 'J' => 1485, 'K' => 1375, 'L' => 1375, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2706, 'B' => 2266, 'C' => 2266, 'D' => 2046, 'E' => 2046, 'F' => 1936, 'G' => 1936, 'H' => 1826, 'I' => 1826, 'J' => 1936, 'K' => 1826, 'L' => 1826, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3443, 'B' => 3003, 'C' => 3003, 'D' => 2783, 'E' => 2783, 'F' => 2673, 'G' => 2673, 'H' => 2563, 'I' => 2563, 'J' => 2673, 'K' => 2563, 'L' => 2563, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 5, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1815, 'B' => 1375, 'C' => 1265, 'D' => 1155, 'E' => 1155, 'F' => 1045, 'G' => 1045, 'H' => 1045, 'I' => 1045, 'J' => 1155, 'K' => 1155, 'L' => 1155, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2145, 'B' => 1705, 'C' => 1595, 'D' => 1485, 'E' => 1485, 'F' => 1375, 'G' => 1375, 'H' => 1375, 'I' => 1375, 'J' => 1485, 'K' => 1485, 'L' => 1485, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2596, 'B' => 2156, 'C' => 2046, 'D' => 1936, 'E' => 1936, 'F' => 1826, 'G' => 1826, 'H' => 1826, 'I' => 1826, 'J' => 1936, 'K' => 1936, 'L' => 1936, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3333, 'B' => 2893, 'C' => 2783, 'D' => 2673, 'E' => 2673, 'F' => 2563, 'G' => 2563, 'H' => 2563, 'I' => 2563, 'J' => 2673, 'K' => 2673, 'L' => 2673, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 6, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1595, 'B' => 1265, 'C' => 1155, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1045, 'I' => 1155, 'J' => 1265, 'K' => 1265, 'L' => 1265, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1925, 'B' => 1595, 'C' => 1485, 'D' => 1375, 'E' => 1375, 'F' => 1375, 'G' => 1375, 'H' => 1375, 'I' => 1485, 'J' => 1595, 'K' => 1595, 'L' => 1595, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2376, 'B' => 2046, 'C' => 1936, 'D' => 1826, 'E' => 1826, 'F' => 1826, 'G' => 1826, 'H' => 1826, 'I' => 1936, 'J' => 2046, 'K' => 2046, 'L' => 2046, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3113, 'B' => 2783, 'C' => 2673, 'D' => 2563, 'E' => 2563, 'F' => 2563, 'G' => 2563, 'H' => 2563, 'I' => 2673, 'J' => 2783, 'K' => 2783, 'L' => 2783, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 7, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1595, 'B' => 1265, 'C' => 1155, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1045, 'I' => 1155, 'J' => 1265, 'K' => 1265, 'L' => 1265, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1925, 'B' => 1595, 'C' => 1485, 'D' => 1375, 'E' => 1375, 'F' => 1375, 'G' => 1375, 'H' => 1375, 'I' => 1485, 'J' => 1595, 'K' => 1595, 'L' => 1595, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2376, 'B' => 2046, 'C' => 1936, 'D' => 1826, 'E' => 1826, 'F' => 1826, 'G' => 1826, 'H' => 1826, 'I' => 1936, 'J' => 2046, 'K' => 2046, 'L' => 2046, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3113, 'B' => 2783, 'C' => 2673, 'D' => 2563, 'E' => 2563, 'F' => 2563, 'G' => 2563, 'H' => 2563, 'I' => 2673, 'J' => 2783, 'K' => 2783, 'L' => 2783, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 8, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1485, 'B' => 1155, 'C' => 1045, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1155, 'I' => 1265, 'J' => 1375, 'K' => 1485, 'L' => 1485, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1815, 'B' => 1485, 'C' => 1375, 'D' => 1375, 'E' => 1375, 'F' => 1375, 'G' => 1375, 'H' => 1485, 'I' => 1595, 'J' => 1705, 'K' => 1815, 'L' => 1815, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2266, 'B' => 1936, 'C' => 1826, 'D' => 1826, 'E' => 1826, 'F' => 1826, 'G' => 1826, 'H' => 1936, 'I' => 2046, 'J' => 2156, 'K' => 2266, 'L' => 2266, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3003, 'B' => 2673, 'C' => 2563, 'D' => 2563, 'E' => 2563, 'F' => 2563, 'G' => 2563, 'H' => 2673, 'I' => 2783, 'J' => 2893, 'K' => 3003, 'L' => 3003, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 9, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1485, 'B' => 1155, 'C' => 1045, 'D' => 1045, 'E' => 1045, 'F' => 1045, 'G' => 1045, 'H' => 1155, 'I' => 1265, 'J' => 1375, 'K' => 1485, 'L' => 1485, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1815, 'B' => 1485, 'C' => 1375, 'D' => 1375, 'E' => 1375, 'F' => 1375, 'G' => 1375, 'H' => 1485, 'I' => 1595, 'J' => 1705, 'K' => 1815, 'L' => 1815, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2266, 'B' => 1936, 'C' => 1826, 'D' => 1826, 'E' => 1826, 'F' => 1826, 'G' => 1826, 'H' => 1936, 'I' => 2046, 'J' => 2156, 'K' => 2266, 'L' => 2266, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>3003, 'B' => 2673, 'C' => 2563, 'D' => 2563, 'E' => 2563, 'F' => 2563, 'G' => 2563, 'H' => 2673, 'I' => 2783, 'J' => 2893, 'K' => 3003, 'L' => 3003, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 10, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1375, 'B' => 1045, 'C' => 1045, 'D' => 1045, 'E' => 1045, 'F' => 1155, 'G' => 1155, 'H' => 1265, 'I' => 1485, 'J' => 1595, 'K' => 1705, 'L' => 1705, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1705, 'B' => 1375, 'C' => 1375, 'D' => 1375, 'E' => 1375, 'F' => 1485, 'G' => 1485, 'H' => 1595, 'I' => 1815, 'J' => 1925, 'K' => 2035, 'L' => 2035, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2156, 'B' => 1826, 'C' => 1826, 'D' => 1826, 'E' => 1826, 'F' => 1936, 'G' => 1936, 'H' => 2046, 'I' => 2266, 'J' => 2376, 'K' => 2486, 'L' => 2486, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>2893, 'B' => 2563, 'C' => 2563, 'D' => 2563, 'E' => 2563, 'F' => 2673, 'G' => 2673, 'H' => 2783, 'I' => 3003, 'J' => 3113, 'K' => 3223, 'L' => 3223, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 11, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1265, 'B' => 1045, 'C' => 1045, 'D' => 1155, 'E' => 1155, 'F' => 1265, 'G' => 1265, 'H' => 1375, 'I' => 1485, 'J' => 1595, 'K' => 1705, 'L' => 1705, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1595, 'B' => 1375, 'C' => 1375, 'D' => 1485, 'E' => 1485, 'F' => 1595, 'G' => 1595, 'H' => 1705, 'I' => 1815, 'J' => 1925, 'K' => 2035, 'L' => 2035, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2046, 'B' => 1826, 'C' => 1826, 'D' => 1936, 'E' => 1936, 'F' => 2046, 'G' => 2046, 'H' => 2156, 'I' => 2266, 'J' => 2376, 'K' => 2486, 'L' => 2486, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>2783, 'B' => 2563, 'C' => 2563, 'D' => 2673, 'E' => 2673, 'F' => 2783, 'G' => 2783, 'H' => 2893, 'I' => 3003, 'J' => 3113, 'K' => 3223, 'L' => 3223, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 12, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1045, 'B' => 1265, 'C' => 1375, 'D' => 1485, 'E' => 1485, 'F' => 1595, 'G' => 1595, 'H' => 1815, 'I' => 1925, 'J' => 2035, 'K' => 2145, 'L' => 2145, 'M' => 0, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1375, 'B' => 1595, 'C' => 1705, 'D' => 1815, 'E' => 1815, 'F' => 1925, 'G' => 1925, 'H' => 2145, 'I' => 2255, 'J' => 2365, 'K' => 2475, 'L' => 2475, 'M' => 0, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>1826, 'B' => 2046, 'C' => 2156, 'D' => 2266, 'E' => 2266, 'F' => 2376, 'G' => 2376, 'H' => 2596, 'I' => 2706, 'J' => 2816, 'K' => 2926, 'L' => 2926, 'M' => 0, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>2563, 'B' => 2783, 'C' => 2893, 'D' => 3003, 'E' => 3003, 'F' => 3113, 'G' => 3113, 'H' => 3333, 'I' => 3443, 'J' => 3553, 'K' => 3663, 'L' => 3663, 'M' => 0, ), ),
            )
        ),
        array('carrier_code' => 'sagawa', 'service_code' => 'cool', 'zone_id' => 13, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 1045, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 1375, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 1826, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' =>0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0, 'K' => 0, 'L' => 0, 'M' => 2563, ), ),
            )
        ),
        // ヤマト運輸 - 宅急便
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 14, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 930, 'B' => 1150, 'C' => 1260, 'D' => 1370, 'E' => 1370, 'F' => 1480, 'G' => 1480, 'H' => 1700, 'I' => 1810, 'J' => 1810, 'K' => 2030, 'L' => 2030, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1150, 'B' => 1370, 'C' => 1480, 'D' => 1590, 'E' => 1590, 'F' => 1700, 'G' => 1700, 'H' => 1920, 'I' => 2030, 'J' => 2030, 'K' => 2250, 'L' => 2580, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1390, 'B' => 1610, 'C' => 1720, 'D' => 1830, 'E' => 1830, 'F' => 1940, 'G' => 1940, 'H' => 2160, 'I' => 2270, 'J' => 2270, 'K' => 2490, 'L' => 3150, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 1610, 'B' => 1830, 'C' => 1940, 'D' => 2050, 'E' => 2050, 'F' => 2160, 'G' => 2160, 'H' => 2380, 'I' => 2490, 'J' => 2490, 'K' => 2710, 'L' => 3700, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 1850, 'B' => 2070, 'C' => 2180, 'D' => 2290, 'E' => 2290, 'F' => 2400, 'G' => 2400, 'H' => 2620, 'I' => 2730, 'J' => 2730, 'K' => 2950, 'L' => 4270, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2070, 'B' => 2290, 'C' => 2400, 'D' => 2510, 'E' => 2510, 'F' => 2620, 'G' => 2620, 'H' => 2840, 'I' => 2950, 'J' => 2950, 'K' => 3170, 'L' => 4820, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 15, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1150, 'B' => 930, 'C' => 930, 'D' => 1040, 'E' => 1040, 'F' => 1150, 'G' => 1150, 'H' => 1260, 'I' => 1370, 'J' => 1370, 'K' => 1590, 'L' => 1700, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1370, 'B' => 1150, 'C' => 1150, 'D' => 1260, 'E' => 1260, 'F' => 1370, 'G' => 1370, 'H' => 1480, 'I' => 1590, 'J' => 1590, 'K' => 1810, 'L' => 2250, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1610, 'B' => 1390, 'C' => 1390, 'D' => 1500, 'E' => 1500, 'F' => 1610, 'G' => 1610, 'H' => 1720, 'I' => 1830, 'J' => 1830, 'K' => 2050, 'L' => 2820, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 1830, 'B' => 1610, 'C' => 1610, 'D' => 1720, 'E' => 1720, 'F' => 1830, 'G' => 1830, 'H' => 1940, 'I' => 2050, 'J' => 2050, 'K' => 2270, 'L' => 3370, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2070, 'B' => 1850, 'C' => 1850, 'D' => 1960, 'E' => 1960, 'F' => 2070, 'G' => 2070, 'H' => 2180, 'I' => 2290, 'J' => 2290, 'K' => 2510, 'L' => 3940, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2290, 'B' => 2070, 'C' => 2070, 'D' => 2180, 'E' => 2180, 'F' => 2290, 'G' => 2290, 'H' => 2400, 'I' => 2510, 'J' => 2510, 'K' => 2730, 'L' => 4490, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 16, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1260, 'B' => 930, 'C' => 930, 'D' => 930, 'E' => 930, 'F' => 1040, 'G' => 1040, 'H' => 1150, 'I' => 1370, 'J' => 1370, 'K' => 1590, 'L' => 1590, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1480, 'B' => 1150, 'C' => 1150, 'D' => 1150, 'E' => 1150, 'F' => 1260, 'G' => 1260, 'H' => 1370, 'I' => 1590, 'J' => 1590, 'K' => 1810, 'L' => 2140, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1720, 'B' => 1390, 'C' => 1390, 'D' => 1390, 'E' => 1390, 'F' => 1500, 'G' => 1500, 'H' => 1610, 'I' => 1830, 'J' => 1830, 'K' => 2050, 'L' => 2710, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 1940, 'B' => 1610, 'C' => 1610, 'D' => 1610, 'E' => 1610, 'F' => 1720, 'G' => 1720, 'H' => 1830, 'I' => 2050, 'J' => 2050, 'K' => 2270, 'L' => 3260, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2180, 'B' => 1850, 'C' => 1850, 'D' => 1850, 'E' => 1850, 'F' => 1960, 'G' => 1960, 'H' => 2070, 'I' => 2290, 'J' => 2290, 'K' => 2510, 'L' => 3830, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2400, 'B' => 2070, 'C' => 2070, 'D' => 2070, 'E' => 2070, 'F' => 2180, 'G' => 2180, 'H' => 2290, 'I' => 2510, 'J' => 2510, 'K' => 2730, 'L' => 4380, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 17, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1370, 'B' => 1040, 'C' => 930, 'D' => 930, 'E' => 930, 'F' => 930, 'G' => 930, 'H' => 1040, 'I' => 1150, 'J' => 1150, 'K' => 1370, 'L' => 1370, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1590, 'B' => 1260, 'C' => 1150, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1260, 'I' => 1370, 'J' => 1370, 'K' => 1590, 'L' => 1920, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1830, 'B' => 1500, 'C' => 1390, 'D' => 1390, 'E' => 1390, 'F' => 1390, 'G' => 1390, 'H' => 1500, 'I' => 1610, 'J' => 1610, 'K' => 1830, 'L' => 2490, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2050, 'B' => 1720, 'C' => 1610, 'D' => 1610, 'E' => 1610, 'F' => 1610, 'G' => 1610, 'H' => 1720, 'I' => 1830, 'J' => 1830, 'K' => 2050, 'L' => 3040, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2290, 'B' => 1960, 'C' => 1850, 'D' => 1850, 'E' => 1850, 'F' => 1850, 'G' => 1850, 'H' => 1960, 'I' => 2070, 'J' => 2070, 'K' => 2290, 'L' => 3610, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2510, 'B' => 2180, 'C' => 2070, 'D' => 2070, 'E' => 2070, 'F' => 2070, 'G' => 2070, 'H' => 2180, 'I' => 2290, 'J' => 2290, 'K' => 2510, 'L' => 4160, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 18, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1370, 'B' => 1040, 'C' => 930, 'D' => 930, 'E' => 930, 'F' => 930, 'G' => 930, 'H' => 1040, 'I' => 1150, 'J' => 1150, 'K' => 1370, 'L' => 1480, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1590, 'B' => 1260, 'C' => 1150, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1260, 'I' => 1370, 'J' => 1370, 'K' => 1590, 'L' => 2030, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1830, 'B' => 1500, 'C' => 1390, 'D' => 1390, 'E' => 1390, 'F' => 1390, 'G' => 1390, 'H' => 1500, 'I' => 1610, 'J' => 1610, 'K' => 1830, 'L' => 2600, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2050, 'B' => 1720, 'C' => 1610, 'D' => 1610, 'E' => 1610, 'F' => 1610, 'G' => 1610, 'H' => 1720, 'I' => 1830, 'J' => 1830, 'K' => 2050, 'L' => 3150, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2290, 'B' => 1960, 'C' => 1850, 'D' => 1850, 'E' => 1850, 'F' => 1850, 'G' => 1850, 'H' => 1960, 'I' => 2070, 'J' => 2070, 'K' => 2290, 'L' => 3720, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2510, 'B' => 2180, 'C' => 2070, 'D' => 2070, 'E' => 2070, 'F' => 2070, 'G' => 2070, 'H' => 2180, 'I' => 2290, 'J' => 2290, 'K' => 2510, 'L' => 4270, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 19, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1480, 'B' => 1150, 'C' => 1040, 'D' => 930, 'E' => 930, 'F' => 930, 'G' => 930, 'H' => 930, 'I' => 1040, 'J' => 1040, 'K' => 1150, 'L' => 1370, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1700, 'B' => 1370, 'C' => 1260, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1150, 'I' => 1260, 'J' => 1260, 'K' => 1370, 'L' => 1920, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1940, 'B' => 1610, 'C' => 1500, 'D' => 1390, 'E' => 1390, 'F' => 1390, 'G' => 1390, 'H' => 1390, 'I' => 1500, 'J' => 1500, 'K' => 1610, 'L' => 2490, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2160, 'B' => 1830, 'C' => 1720, 'D' => 1610, 'E' => 1610, 'F' => 1610, 'G' => 1610, 'H' => 1610, 'I' => 1720, 'J' => 1720, 'K' => 1830, 'L' => 3040, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2400, 'B' => 2070, 'C' => 1960, 'D' => 1850, 'E' => 1850, 'F' => 1850, 'G' => 1850, 'H' => 1850, 'I' => 1960, 'J' => 1960, 'K' => 2070, 'L' => 3610, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2620, 'B' => 2290, 'C' => 2180, 'D' => 2070, 'E' => 2070, 'F' => 2070, 'G' => 2070, 'H' => 2070, 'I' => 2180, 'J' => 2180, 'K' => 2290, 'L' => 4160, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 20, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1480, 'B' => 1150, 'C' => 1040, 'D' => 930, 'E' => 930, 'F' => 930, 'G' => 930, 'H' => 930, 'I' => 1040, 'J' => 1040, 'K' => 1150, 'L' => 1480, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1700, 'B' => 1370, 'C' => 1260, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1150, 'I' => 1260, 'J' => 1260, 'K' => 1370, 'L' => 2030, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1940, 'B' => 1610, 'C' => 1500, 'D' => 1390, 'E' => 1390, 'F' => 1390, 'G' => 1390, 'H' => 1390, 'I' => 1500, 'J' => 1500, 'K' => 1610, 'L' => 2600, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2160, 'B' => 1830, 'C' => 1720, 'D' => 1610, 'E' => 1610, 'F' => 1610, 'G' => 1610, 'H' => 1610, 'I' => 1720, 'J' => 1720, 'K' => 1830, 'L' => 3150, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2400, 'B' => 2070, 'C' => 1960, 'D' => 1850, 'E' => 1850, 'F' => 1850, 'G' => 1850, 'H' => 1850, 'I' => 1960, 'J' => 1960, 'K' => 2070, 'L' => 3720, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2620, 'B' => 2290, 'C' => 2180, 'D' => 2070, 'E' => 2070, 'F' => 2070, 'G' => 2070, 'H' => 2070, 'I' => 2180, 'J' => 2180, 'K' => 2290, 'L' => 4270, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 21, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1700, 'B' => 1260, 'C' => 1150, 'D' => 1040, 'E' => 1040, 'F' => 930, 'G' => 930, 'H' => 930, 'I' => 930, 'J' => 930, 'K' => 1040, 'L' => 1370, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 1920, 'B' => 1480, 'C' => 1370, 'D' => 1260, 'E' => 1260, 'F' => 1150, 'G' => 1150, 'H' => 1150, 'I' => 1150, 'J' => 1150, 'K' => 1260, 'L' => 1920, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2160, 'B' => 1720, 'C' => 1610, 'D' => 1500, 'E' => 1500, 'F' => 1390, 'G' => 1390, 'H' => 1390, 'I' => 1390, 'J' => 1390, 'K' => 1500, 'L' => 2490, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2380, 'B' => 1940, 'C' => 1830, 'D' => 1720, 'E' => 1720, 'F' => 1610, 'G' => 1610, 'H' => 1610, 'I' => 1610, 'J' => 1610, 'K' => 1720, 'L' => 3040, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2620, 'B' => 2180, 'C' => 2070, 'D' => 1960, 'E' => 1960, 'F' => 1850, 'G' => 1850, 'H' => 1850, 'I' => 1850, 'J' => 1850, 'K' => 1960, 'L' => 3610, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2840, 'B' => 2400, 'C' => 2290, 'D' => 2180, 'E' => 2180, 'F' => 2070, 'G' => 2070, 'H' => 2070, 'I' => 2070, 'J' => 2070, 'K' => 2180, 'L' => 4160, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 22, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1810, 'B' => 1370, 'C' => 1370, 'D' => 1150, 'E' => 1150, 'F' => 1040, 'G' => 1040, 'H' => 930, 'I' => 930, 'J' => 930, 'K' => 930, 'L' => 1370, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2030, 'B' => 1590, 'C' => 1590, 'D' => 1370, 'E' => 1370, 'F' => 1260, 'G' => 1260, 'H' => 1150, 'I' => 1150, 'J' => 1150, 'K' => 1150, 'L' => 1920, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2270, 'B' => 1830, 'C' => 1830, 'D' => 1610, 'E' => 1610, 'F' => 1500, 'G' => 1500, 'H' => 1390, 'I' => 1390, 'J' => 1390, 'K' => 1390, 'L' => 2490, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2490, 'B' => 2050, 'C' => 2050, 'D' => 1830, 'E' => 1830, 'F' => 1720, 'G' => 1720, 'H' => 1610, 'I' => 1610, 'J' => 1610, 'K' => 1610, 'L' => 3040, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2730, 'B' => 2290, 'C' => 2290, 'D' => 2070, 'E' => 2070, 'F' => 1960, 'G' => 1960, 'H' => 1850, 'I' => 1850, 'J' => 1850, 'K' => 1850, 'L' => 3610, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2950, 'B' => 2510, 'C' => 2510, 'D' => 2290, 'E' => 2290, 'F' => 2180, 'G' => 2180, 'H' => 2070, 'I' => 2070, 'J' => 2070, 'K' => 2070, 'L' => 4160, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 23, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1810, 'B' => 1370, 'C' => 1370, 'D' => 1150, 'E' => 1150, 'F' => 1040, 'G' => 1040, 'H' => 930, 'I' => 930, 'J' => 930, 'K' => 1040, 'L' => 1370, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2030, 'B' => 1590, 'C' => 1590, 'D' => 1370, 'E' => 1370, 'F' => 1260, 'G' => 1260, 'H' => 1150, 'I' => 1150, 'J' => 1150, 'K' => 1260, 'L' => 1920, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2270, 'B' => 1830, 'C' => 1830, 'D' => 1610, 'E' => 1610, 'F' => 1500, 'G' => 1500, 'H' => 1390, 'I' => 1390, 'J' => 1390, 'K' => 1500, 'L' => 2490, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2490, 'B' => 2050, 'C' => 2050, 'D' => 1830, 'E' => 1830, 'F' => 1720, 'G' => 1720, 'H' => 1610, 'I' => 1610, 'J' => 1610, 'K' => 1720, 'L' => 3040, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2730, 'B' => 2290, 'C' => 2290, 'D' => 2070, 'E' => 2070, 'F' => 1960, 'G' => 1960, 'H' => 1850, 'I' => 1850, 'J' => 1850, 'K' => 1960, 'L' => 3610, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 2950, 'B' => 2510, 'C' => 2510, 'D' => 2290, 'E' => 2290, 'F' => 2180, 'G' => 2180, 'H' => 2070, 'I' => 2070, 'J' => 2070, 'K' => 2180, 'L' => 4160, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 24, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 2030, 'B' => 1590, 'C' => 1590, 'D' => 1370, 'E' => 1370, 'F' => 1150, 'G' => 1150, 'H' => 1040, 'I' => 930, 'J' => 1040, 'K' => 930, 'L' => 1260, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2250, 'B' => 1810, 'C' => 1810, 'D' => 1590, 'E' => 1590, 'F' => 1370, 'G' => 1370, 'H' => 1260, 'I' => 1150, 'J' => 1260, 'K' => 1150, 'L' => 1810, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2490, 'B' => 2050, 'C' => 2050, 'D' => 1830, 'E' => 1830, 'F' => 1610, 'G' => 1610, 'H' => 1500, 'I' => 1390, 'J' => 1500, 'K' => 1390, 'L' => 2380, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2710, 'B' => 2270, 'C' => 2270, 'D' => 2050, 'E' => 2050, 'F' => 1830, 'G' => 1830, 'H' => 1720, 'I' => 1610, 'J' => 1720, 'K' => 1610, 'L' => 2930, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2950, 'B' => 2510, 'C' => 2510, 'D' => 2290, 'E' => 2290, 'F' => 2070, 'G' => 2070, 'H' => 1960, 'I' => 1850, 'J' => 1960, 'K' => 1850, 'L' => 3500, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 3170, 'B' => 2730, 'C' => 2730, 'D' => 2510, 'E' => 2510, 'F' => 2290, 'G' => 2290, 'H' => 2180, 'I' => 2070, 'J' => 2180, 'K' => 2070, 'L' => 4050, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'standard', 'zone_id' => 25, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 2030, 'B' => 1700, 'C' => 1590, 'D' => 1370, 'E' => 1480, 'F' => 1370, 'G' => 1480, 'H' => 1370, 'I' => 1370, 'J' => 1370, 'K' => 1260, 'L' => 930, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' => 2580, 'B' => 2250, 'C' => 2140, 'D' => 1920, 'E' => 2030, 'F' => 1920, 'G' => 2030, 'H' => 1920, 'I' => 1920, 'J' => 1920, 'K' => 1810, 'L' => 1150, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 3150, 'B' => 2820, 'C' => 2710, 'D' => 2490, 'E' => 2600, 'F' => 2490, 'G' => 2600, 'H' => 2490, 'I' => 2490, 'J' => 2490, 'K' => 2380, 'L' => 1390, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 3700, 'B' => 3370, 'C' => 3260, 'D' => 3040, 'E' => 3150, 'F' => 3040, 'G' => 3150, 'H' => 3040, 'I' => 3040, 'J' => 3040, 'K' => 2930, 'L' => 1610, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 4270, 'B' => 3940, 'C' => 3830, 'D' => 3610, 'E' => 3720, 'F' => 3610, 'G' => 3720, 'H' => 3610, 'I' => 3610, 'J' => 3610, 'K' => 3500, 'L' => 1850, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' => 4820, 'B' => 4490, 'C' => 4380, 'D' => 4160, 'E' => 4270, 'F' => 4160, 'G' => 4270, 'H' => 4160, 'I' => 4160, 'J' => 4160, 'K' => 4050, 'L' => 2070, ), ),
            )
        ),
        // ヤマト運輸 - クール宅急便
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 14, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1150, 'B' => 1370, 'C' => 1480, 'D' => 1590, 'E' => 1590, 'F' => 1700, 'G' => 1700, 'H' => 1920, 'I' => 2030, 'J' => 2030, 'K' => 2250, 'L' => 2250, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1370, 'B' => 1590, 'C' => 1700, 'D' => 1810, 'E' => 1810, 'F' => 1920, 'G' => 1920, 'H' => 2140, 'I' => 2250, 'J' => 2250, 'K' => 2470, 'L' => 2800, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>1720, 'B' => 1940, 'C' => 2050, 'D' => 2160, 'E' => 2160, 'F' => 2270, 'G' => 2270, 'H' => 2490, 'I' => 2600, 'J' => 2600, 'K' => 2820, 'L' => 3480, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>2270, 'B' => 2490, 'C' => 2600, 'D' => 2710, 'E' => 2710, 'F' => 2820, 'G' => 2820, 'H' => 3040, 'I' => 3150, 'J' => 3150, 'K' => 3370, 'L' => 4360, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 15, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1370, 'B' => 1150, 'C' => 1150, 'D' => 1260, 'E' => 1260, 'F' => 1370, 'G' => 1370, 'H' => 1480, 'I' => 1590, 'J' => 1590, 'K' => 1810, 'L' => 1920, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1590, 'B' => 1370, 'C' => 1370, 'D' => 1480, 'E' => 1480, 'F' => 1590, 'G' => 1590, 'H' => 1700, 'I' => 1810, 'J' => 1810, 'K' => 2030, 'L' => 2470, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>1940, 'B' => 1720, 'C' => 1720, 'D' => 1830, 'E' => 1830, 'F' => 1940, 'G' => 1940, 'H' => 2050, 'I' => 2160, 'J' => 2160, 'K' => 2380, 'L' => 3150, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>2490, 'B' => 2270, 'C' => 2270, 'D' => 2380, 'E' => 2380, 'F' => 2490, 'G' => 2490, 'H' => 2600, 'I' => 2710, 'J' => 2710, 'K' => 2930, 'L' => 4030, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 16, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1480, 'B' => 1150, 'C' => 1150, 'D' => 1150, 'E' => 1150, 'F' => 1260, 'G' => 1260, 'H' => 1370, 'I' => 1590, 'J' => 1590, 'K' => 1810, 'L' => 1810, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1700, 'B' => 1370, 'C' => 1370, 'D' => 1370, 'E' => 1370, 'F' => 1480, 'G' => 1480, 'H' => 1590, 'I' => 1810, 'J' => 1810, 'K' => 2030, 'L' => 2360, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2050, 'B' => 1720, 'C' => 1720, 'D' => 1720, 'E' => 1720, 'F' => 1830, 'G' => 1830, 'H' => 1940, 'I' => 2160, 'J' => 2160, 'K' => 2380, 'L' => 3040, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>2600, 'B' => 2270, 'C' => 2270, 'D' => 2270, 'E' => 2270, 'F' => 2380, 'G' => 2380, 'H' => 2490, 'I' => 2710, 'J' => 2710, 'K' => 2930, 'L' => 3920, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 17, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1590, 'B' => 1260, 'C' => 1150, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1260, 'I' => 1370, 'J' => 1370, 'K' => 1590, 'L' => 1590, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1810, 'B' => 1480, 'C' => 1370, 'D' => 1370, 'E' => 1370, 'F' => 1370, 'G' => 1370, 'H' => 1480, 'I' => 1590, 'J' => 1590, 'K' => 1810, 'L' => 2140, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2160, 'B' => 1830, 'C' => 1720, 'D' => 1720, 'E' => 1720, 'F' => 1720, 'G' => 1720, 'H' => 1830, 'I' => 1940, 'J' => 1940, 'K' => 2160, 'L' => 2820, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>2710, 'B' => 2380, 'C' => 2270, 'D' => 2270, 'E' => 2270, 'F' => 2270, 'G' => 2270, 'H' => 2380, 'I' => 2490, 'J' => 2490, 'K' => 2710, 'L' => 3700, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 18, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1590, 'B' => 1260, 'C' => 1150, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1260, 'I' => 1370, 'J' => 1370, 'K' => 1590, 'L' => 1700, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1810, 'B' => 1480, 'C' => 1370, 'D' => 1370, 'E' => 1370, 'F' => 1370, 'G' => 1370, 'H' => 1480, 'I' => 1590, 'J' => 1590, 'K' => 1810, 'L' => 2250, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2160, 'B' => 1830, 'C' => 1720, 'D' => 1720, 'E' => 1720, 'F' => 1720, 'G' => 1720, 'H' => 1830, 'I' => 1940, 'J' => 1940, 'K' => 2160, 'L' => 2930, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>2710, 'B' => 2380, 'C' => 2270, 'D' => 2270, 'E' => 2270, 'F' => 2270, 'G' => 2270, 'H' => 2380, 'I' => 2490, 'J' => 2490, 'K' => 2710, 'L' => 3810, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 19, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1700, 'B' => 1370, 'C' => 1260, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1150, 'I' => 1260, 'J' => 1260, 'K' => 1370, 'L' => 1590, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1920, 'B' => 1590, 'C' => 1480, 'D' => 1370, 'E' => 1370, 'F' => 1370, 'G' => 1370, 'H' => 1370, 'I' => 1480, 'J' => 1480, 'K' => 1590, 'L' => 2140, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2270, 'B' => 1940, 'C' => 1830, 'D' => 1720, 'E' => 1720, 'F' => 1720, 'G' => 1720, 'H' => 1720, 'I' => 1830, 'J' => 1830, 'K' => 1940, 'L' => 2820, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>2820, 'B' => 2490, 'C' => 2380, 'D' => 2270, 'E' => 2270, 'F' => 2270, 'G' => 2270, 'H' => 2270, 'I' => 2380, 'J' => 2380, 'K' => 2490, 'L' => 3700, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 20, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1700, 'B' => 1370, 'C' => 1260, 'D' => 1150, 'E' => 1150, 'F' => 1150, 'G' => 1150, 'H' => 1150, 'I' => 1260, 'J' => 1260, 'K' => 1370, 'L' => 1700, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>1920, 'B' => 1590, 'C' => 1480, 'D' => 1370, 'E' => 1370, 'F' => 1370, 'G' => 1370, 'H' => 1370, 'I' => 1480, 'J' => 1480, 'K' => 1590, 'L' => 2250, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2270, 'B' => 1940, 'C' => 1830, 'D' => 1720, 'E' => 1720, 'F' => 1720, 'G' => 1720, 'H' => 1720, 'I' => 1830, 'J' => 1830, 'K' => 1940, 'L' => 2930, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>2820, 'B' => 2490, 'C' => 2380, 'D' => 2270, 'E' => 2270, 'F' => 2270, 'G' => 2270, 'H' => 2270, 'I' => 2380, 'J' => 2380, 'K' => 2490, 'L' => 3810, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 21, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>1920, 'B' => 1480, 'C' => 1370, 'D' => 1260, 'E' => 1260, 'F' => 1150, 'G' => 1150, 'H' => 1150, 'I' => 1150, 'J' => 1150, 'K' => 1260, 'L' => 1590, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2140, 'B' => 1700, 'C' => 1590, 'D' => 1480, 'E' => 1480, 'F' => 1370, 'G' => 1370, 'H' => 1370, 'I' => 1370, 'J' => 1370, 'K' => 1480, 'L' => 2140, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2490, 'B' => 2050, 'C' => 1940, 'D' => 1830, 'E' => 1830, 'F' => 1720, 'G' => 1720, 'H' => 1720, 'I' => 1720, 'J' => 1720, 'K' => 1830, 'L' => 2820, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>3040, 'B' => 2600, 'C' => 2490, 'D' => 2380, 'E' => 2380, 'F' => 2270, 'G' => 2270, 'H' => 2270, 'I' => 2270, 'J' => 2270, 'K' => 2380, 'L' => 3700, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 22, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>2030, 'B' => 1590, 'C' => 1590, 'D' => 1370, 'E' => 1370, 'F' => 1260, 'G' => 1260, 'H' => 1150, 'I' => 1150, 'J' => 1150, 'K' => 1150, 'L' => 1590, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2250, 'B' => 1810, 'C' => 1810, 'D' => 1590, 'E' => 1590, 'F' => 1480, 'G' => 1480, 'H' => 1370, 'I' => 1370, 'J' => 1370, 'K' => 1370, 'L' => 2140, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2600, 'B' => 2160, 'C' => 2160, 'D' => 1940, 'E' => 1940, 'F' => 1830, 'G' => 1830, 'H' => 1720, 'I' => 1720, 'J' => 1720, 'K' => 1720, 'L' => 2820, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>3150, 'B' => 2710, 'C' => 2710, 'D' => 2490, 'E' => 2490, 'F' => 2380, 'G' => 2380, 'H' => 2270, 'I' => 2270, 'J' => 2270, 'K' => 2270, 'L' => 3700, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 23, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>2030, 'B' => 1590, 'C' => 1590, 'D' => 1370, 'E' => 1370, 'F' => 1260, 'G' => 1260, 'H' => 1150, 'I' => 1150, 'J' => 1150, 'K' => 1260, 'L' => 1590, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2250, 'B' => 1810, 'C' => 1810, 'D' => 1590, 'E' => 1590, 'F' => 1480, 'G' => 1480, 'H' => 1370, 'I' => 1370, 'J' => 1370, 'K' => 1480, 'L' => 2140, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2600, 'B' => 2160, 'C' => 2160, 'D' => 1940, 'E' => 1940, 'F' => 1830, 'G' => 1830, 'H' => 1720, 'I' => 1720, 'J' => 1720, 'K' => 1830, 'L' => 2820, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>3150, 'B' => 2710, 'C' => 2710, 'D' => 2490, 'E' => 2490, 'F' => 2380, 'G' => 2380, 'H' => 2270, 'I' => 2270, 'J' => 2270, 'K' => 2380, 'L' => 3700, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 24, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>2250, 'B' => 1810, 'C' => 1810, 'D' => 1590, 'E' => 1590, 'F' => 1370, 'G' => 1370, 'H' => 1260, 'I' => 1150, 'J' => 1260, 'K' => 1150, 'L' => 1480, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2470, 'B' => 2030, 'C' => 2030, 'D' => 1810, 'E' => 1810, 'F' => 1590, 'G' => 1590, 'H' => 1480, 'I' => 1370, 'J' => 1480, 'K' => 1370, 'L' => 2030, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>2820, 'B' => 2380, 'C' => 2380, 'D' => 2160, 'E' => 2160, 'F' => 1940, 'G' => 1940, 'H' => 1830, 'I' => 1720, 'J' => 1830, 'K' => 1720, 'L' => 2710, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>3370, 'B' => 2930, 'C' => 2930, 'D' => 2710, 'E' => 2710, 'F' => 2490, 'G' => 2490, 'H' => 2380, 'I' => 2270, 'J' => 2380, 'K' => 2270, 'L' => 3590, ), ),
            )
        ),
        array('carrier_code' => 'yamato', 'service_code' => 'cool', 'zone_id' => 25, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' =>2250, 'B' => 1920, 'C' => 1810, 'D' => 1590, 'E' => 1700, 'F' => 1590, 'G' => 1700, 'H' => 1590, 'I' => 1590, 'J' => 1590, 'K' => 1480, 'L' => 1150, ), ),
            80 => array ( 'size' => '80', 'weight' => '5', 'rates' => array ('A' =>2800, 'B' => 2470, 'C' => 2360, 'D' => 2140, 'E' => 2250, 'F' => 2140, 'G' => 2250, 'H' => 2140, 'I' => 2140, 'J' => 2140, 'K' => 2030, 'L' => 1370, ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' =>3480, 'B' => 3150, 'C' => 3040, 'D' => 2820, 'E' => 2930, 'F' => 2820, 'G' => 2930, 'H' => 2820, 'I' => 2820, 'J' => 2820, 'K' => 2710, 'L' => 1720, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' =>4360, 'B' => 4030, 'C' => 3920, 'D' => 3700, 'E' => 3810, 'F' => 3700, 'G' => 3810, 'H' => 3700, 'I' => 3700, 'J' => 3700, 'K' => 3590, 'L' => 2270, ), ),
            )
        ),
        // 福山通運 - フクツー宅配便
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 37, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 990, 'B' => 1220, 'C' => 1330, 'D' => 1430, 'E' => 1430, 'F' => 1540, 'G' => 1540, 'H' => 1770, 'I' => 1880, 'J' => 1980, 'K' => 2090, 'L' => 2100, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1220, 'B' => 1430, 'C' => 1540, 'D' => 1650, 'E' => 1650, 'F' => 1770, 'G' => 1770, 'H' => 1980, 'I' => 2090, 'J' => 2200, 'K' => 2320, 'L' => 2330,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1430, 'B' => 1650, 'C' => 1770, 'D' => 1880, 'E' => 1880, 'F' => 1980, 'G' => 1980, 'H' => 2200, 'I' => 2320, 'J' => 2430, 'K' => 2530,  'L' => 2540, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 1650, 'B' => 1880, 'C' => 1980, 'D' => 2090, 'E' => 2090, 'F' => 2200, 'G' => 2200, 'H' => 2430, 'I' => 2530, 'J' => 2640, 'K' => 2750,  'L' => 2770, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 1880, 'B' => 2090, 'C' => 2200, 'D' => 2320, 'E' => 2320, 'F' => 2430, 'G' => 2430, 'H' => 2640, 'I' => 2750, 'J' => 2870, 'K' => 2980,  'L' => 2990, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2320, 'B' => 2530, 'C' => 2640, 'D' => 2750, 'E' => 2750, 'F' => 2870, 'G' => 2870, 'H' => 3080, 'I' => 3190, 'J' => 3300, 'K' => 3420,  'L' => 3430, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 38, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1220, 'B' => 990, 'C' => 1000, 'D' => 1100, 'E' => 1100, 'F' => 1220, 'G' => 1220, 'H' => 1330, 'I' => 1430, 'J' => 1540, 'K' => 1650, 'L' => 1670, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1430, 'B' => 1220, 'C' => 1230, 'D' => 1330, 'E' => 1330, 'F' => 1430, 'G' => 1430, 'H' => 1540, 'I' => 1650, 'J' => 1770, 'K' => 1880, 'L' => 1890,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1650, 'B' => 1430, 'C' => 1440, 'D' => 1540, 'E' => 1540, 'F' => 1650, 'G' => 1650, 'H' => 1770, 'I' => 1880, 'J' => 1980, 'K' => 2090,  'L' => 2100, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 1880, 'B' => 1650, 'C' => 1670, 'D' => 1770, 'E' => 1770, 'F' => 1880, 'G' => 1880, 'H' => 1980, 'I' => 2090, 'J' => 2200, 'K' => 2320,  'L' => 2330, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2090, 'B' => 1880, 'C' => 1890, 'D' => 1980, 'E' => 1980, 'F' => 2090, 'G' => 2090, 'H' => 2200, 'I' => 2320, 'J' => 2430, 'K' => 2530,  'L' => 2540, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2530, 'B' => 2320, 'C' => 2330, 'D' => 2430, 'E' => 2430, 'F' => 2530, 'G' => 2530, 'H' => 2640, 'I' => 2750, 'J' => 2870, 'K' => 2980,  'L' => 2990, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 39, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1330, 'B' => 1, 'C' => 990, 'D' => 1000, 'E' => 1000, 'F' => 1100, 'G' => 1100, 'H' => 1220, 'I' => 1430, 'J' => 1540, 'K' => 1650, 'L' => 1670, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1540, 'B' => 1230, 'C' => 1220, 'D' => 1230, 'E' => 1230, 'F' => 1330, 'G' => 1330, 'H' => 1430, 'I' => 1650, 'J' => 1770, 'K' => 1880, 'L' => 1890,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1770, 'B' => 1440, 'C' => 1430, 'D' => 1440, 'E' => 1440, 'F' => 1540, 'G' => 1540, 'H' => 1650, 'I' => 1880, 'J' => 1980, 'K' => 2090,  'L' => 2100, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 1980, 'B' => 1670, 'C' => 1650, 'D' => 1670, 'E' => 1670, 'F' => 1770, 'G' => 1770, 'H' => 1880, 'I' => 2090, 'J' => 2200, 'K' => 2320,  'L' => 2330, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2200, 'B' => 1890, 'C' => 1880, 'D' => 1890, 'E' => 1890, 'F' => 1980, 'G' => 1980, 'H' => 2090, 'I' => 2320, 'J' => 2430, 'K' => 2530,  'L' => 2540, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2640, 'B' => 2330, 'C' => 2320, 'D' => 2330, 'E' => 2330, 'F' => 2430, 'G' => 2430, 'H' => 2530, 'I' => 2750, 'J' => 2870, 'K' => 2980,  'L' => 2990, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 40, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1430, 'B' => 1100, 'C' => 1000, 'D' => 990, 'E' => 1000, 'F' => 1000, 'G' => 1000, 'H' => 1100, 'I' => 1220, 'J' => 1330, 'K' => 1430, 'L' => 1440, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1650, 'B' => 1330, 'C' => 1230, 'D' => 1220, 'E' => 1230, 'F' => 1230, 'G' => 1230, 'H' => 1330, 'I' => 1430, 'J' => 1540, 'K' => 1650, 'L' => 1670,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1880, 'B' => 1540, 'C' => 1440, 'D' => 1430, 'E' => 1440, 'F' => 1440, 'G' => 1440, 'H' => 1540, 'I' => 1650, 'J' => 1770, 'K' => 1880,  'L' => 1890, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2090, 'B' => 1770, 'C' => 1670, 'D' => 1650, 'E' => 1670, 'F' => 1670, 'G' => 1670, 'H' => 1770, 'I' => 1880, 'J' => 1980, 'K' => 2090,  'L' => 2100, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2320, 'B' => 1980, 'C' => 1890, 'D' => 1880, 'E' => 1890, 'F' => 1890, 'G' => 1890, 'H' => 1980, 'I' => 2090, 'J' => 2200, 'K' => 2320,  'L' => 2330, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2750, 'B' => 2430, 'C' => 2330, 'D' => 2320, 'E' => 2330, 'F' => 2330, 'G' => 2330, 'H' => 2430, 'I' => 2530, 'J' => 2640, 'K' => 2750,  'L' => 2770, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 41, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1430, 'B' => 1100, 'C' => 1000, 'D' => 1000, 'E' => 990, 'F' => 1000, 'G' => 1000, 'H' => 1100, 'I' => 1220, 'J' => 1330, 'K' => 1430, 'L' => 1440, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1650, 'B' => 1330, 'C' => 1230, 'D' => 1230, 'E' => 1220, 'F' => 1230, 'G' => 1230, 'H' => 1330, 'I' => 1430, 'J' => 1540, 'K' => 1650, 'L' => 1670,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1880, 'B' => 1540, 'C' => 1440, 'D' => 1440, 'E' => 1430, 'F' => 1440, 'G' => 1440, 'H' => 1540, 'I' => 1650, 'J' => 1770, 'K' => 1880,  'L' => 1890, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2090, 'B' => 1770, 'C' => 1670, 'D' => 1670, 'E' => 1650, 'F' => 1670, 'G' => 1670, 'H' => 1770, 'I' => 1880, 'J' => 1980, 'K' => 2090,  'L' => 2100, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2320, 'B' => 1980, 'C' => 1890, 'D' => 1890, 'E' => 1880, 'F' => 1890, 'G' => 1890, 'H' => 1980, 'I' => 2090, 'J' => 2200, 'K' => 2320,  'L' => 2330, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2750, 'B' => 2430, 'C' => 2330, 'D' => 2330, 'E' => 2320, 'F' => 2330, 'G' => 2330, 'H' => 2430, 'I' => 2530, 'J' => 2640, 'K' => 2750,  'L' => 2770, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 42, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1540, 'B' => 1220, 'C' => 1100, 'D' => 1000, 'E' => 1000, 'F' => 990, 'G' => 1000, 'H' => 1000, 'I' => 1100, 'J' => 1220, 'K' => 1220, 'L' => 1230, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1770, 'B' => 1430, 'C' => 1330, 'D' => 1230, 'E' => 1230, 'F' => 1220, 'G' => 1230, 'H' => 1230, 'I' => 1330, 'J' => 1430, 'K' => 1430, 'L' => 1440,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1980, 'B' => 1650, 'C' => 1540, 'D' => 1440, 'E' => 1440, 'F' => 1430, 'G' => 1440, 'H' => 1440, 'I' => 1540, 'J' => 1650, 'K' => 1650,  'L' => 1670, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2200, 'B' => 1880, 'C' => 1770, 'D' => 1670, 'E' => 1670, 'F' => 1650, 'G' => 1670, 'H' => 1670, 'I' => 1770, 'J' => 1880, 'K' => 1880,  'L' => 1890, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2430, 'B' => 2090, 'C' => 1980, 'D' => 1890, 'E' => 1890, 'F' => 1880, 'G' => 1890, 'H' => 1890, 'I' => 1980, 'J' => 2090, 'K' => 2090,  'L' => 2100, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2870, 'B' => 2530, 'C' => 2430, 'D' => 2330, 'E' => 2330, 'F' => 2320, 'G' => 2330, 'H' => 2330, 'I' => 2430, 'J' => 2530, 'K' => 2530,  'L' => 2540, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 43, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1540, 'B' => 1220, 'C' => 1100, 'D' => 1000, 'E' => 1000, 'F' => 1000, 'G' => 990, 'H' => 1000, 'I' => 1100, 'J' => 1220, 'K' => 1220, 'L' => 1230, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1770, 'B' => 1430, 'C' => 1330, 'D' => 1230, 'E' => 1230, 'F' => 1230, 'G' => 1220, 'H' => 1230, 'I' => 1330, 'J' => 1430, 'K' => 1430, 'L' => 1440,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 1980, 'B' => 1650, 'C' => 1540, 'D' => 1440, 'E' => 1440, 'F' => 1440, 'G' => 1430, 'H' => 1440, 'I' => 1540, 'J' => 1650, 'K' => 1650,  'L' => 1670, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2200, 'B' => 1880, 'C' => 1770, 'D' => 1670, 'E' => 1670, 'F' => 1670, 'G' => 1650, 'H' => 1670, 'I' => 1770, 'J' => 1880, 'K' => 1880,  'L' => 1890, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2430, 'B' => 2090, 'C' => 1980, 'D' => 1890, 'E' => 1890, 'F' => 1890, 'G' => 1880, 'H' => 1890, 'I' => 1980, 'J' => 2090, 'K' => 2090,  'L' => 2100, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 2870, 'B' => 2530, 'C' => 2430, 'D' => 2330, 'E' => 2330, 'F' => 2330, 'G' => 2320, 'H' => 2330, 'I' => 2430, 'J' => 2530, 'K' => 2530,  'L' => 2540, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 44, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1770, 'B' => 1330, 'C' => 1220, 'D' => 1100, 'E' => 1100, 'F' => 1000, 'G' => 1000, 'H' => 990, 'I' => 1000, 'J' => 1100, 'K' => 1100, 'L' => 1120, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 1980, 'B' => 1540, 'C' => 1430, 'D' => 1330, 'E' => 1330, 'F' => 1230, 'G' => 1230, 'H' => 1220, 'I' => 1230, 'J' => 1330, 'K' => 1330, 'L' => 1340,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2200, 'B' => 1770, 'C' => 1650, 'D' => 1540, 'E' => 1540, 'F' => 1440, 'G' => 1440, 'H' => 1430, 'I' => 1440, 'J' => 1540, 'K' => 1540,  'L' => 1550, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2430, 'B' => 1980, 'C' => 1880, 'D' => 1770, 'E' => 1770, 'F' => 1670, 'G' => 1670, 'H' => 1650, 'I' => 1670, 'J' => 1770, 'K' => 1770,  'L' => 1780, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2640, 'B' => 2200, 'C' => 2090, 'D' => 1980, 'E' => 1980, 'F' => 1890, 'G' => 1890, 'H' => 1880, 'I' => 1890, 'J' => 1980, 'K' => 1980,  'L' => 1990, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3080, 'B' => 2640, 'C' => 2530, 'D' => 2430, 'E' => 2430, 'F' => 2330, 'G' => 2330, 'H' => 2320, 'I' => 2330, 'J' => 2430, 'K' => 2430,  'L' => 2440, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 45, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1880, 'B' => 1430, 'C' => 1430, 'D' => 1220, 'E' => 1220, 'F' => 1100, 'G' => 1100, 'H' => 1000, 'I' => 990, 'J' => 1100, 'K' => 1000, 'L' => 1010, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 2090, 'B' => 1650, 'C' => 1650, 'D' => 1430, 'E' => 1430, 'F' => 1330, 'G' => 1330, 'H' => 1230, 'I' => 1220, 'J' => 1330, 'K' => 1230, 'L' => 1240,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2320, 'B' => 1880, 'C' => 1880, 'D' => 1650, 'E' => 1650, 'F' => 1540, 'G' => 1540, 'H' => 1440, 'I' => 1430, 'J' => 1540, 'K' => 1440,  'L' => 1450, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2530, 'B' => 2090, 'C' => 2090, 'D' => 1880, 'E' => 1880, 'F' => 1770, 'G' => 1770, 'H' => 1670, 'I' => 1650, 'J' => 1770, 'K' => 1670,  'L' => 1680, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2750, 'B' => 2320, 'C' => 2320, 'D' => 2090, 'E' => 2090, 'F' => 1980, 'G' => 1980, 'H' => 1890, 'I' => 1880, 'J' => 1980, 'K' => 1890,  'L' => 1900, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3190, 'B' => 2750, 'C' => 2750, 'D' => 2530, 'E' => 2530, 'F' => 2430, 'G' => 2430, 'H' => 2330, 'I' => 2320, 'J' => 2430, 'K' => 2330,  'L' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 46, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 1980, 'B' => 1540, 'C' => 1540, 'D' => 1330, 'E' => 1330, 'F' => 1220, 'G' => 1220, 'H' => 1100, 'I' => 1100, 'J' => 990, 'K' => 1100, 'L' => 1120, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 2200, 'B' => 1770, 'C' => 1770, 'D' => 1540, 'E' => 1540, 'F' => 1430, 'G' => 1430, 'H' => 1330, 'I' => 1330, 'J' => 1220, 'K' => 1330, 'L' => 1340,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2430, 'B' => 1980, 'C' => 1980, 'D' => 1770, 'E' => 1770, 'F' => 1650, 'G' => 1650, 'H' => 1540, 'I' => 1540, 'J' => 1430, 'K' => 1540,  'L' => 1550, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2640, 'B' => 2200, 'C' => 2200, 'D' => 1980, 'E' => 1980, 'F' => 1880, 'G' => 1880, 'H' => 1770, 'I' => 1770, 'J' => 1650, 'K' => 1770,  'L' => 1780, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2870, 'B' => 2430, 'C' => 2430, 'D' => 2200, 'E' => 2200, 'F' => 2090, 'G' => 2090, 'H' => 1980, 'I' => 1980, 'J' => 1880, 'K' => 1980,  'L' => 1990, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3300, 'B' => 2870, 'C' => 2870, 'D' => 2640, 'E' => 2640, 'F' => 2530, 'G' => 2530, 'H' => 2430, 'I' => 2430, 'J' => 2320, 'K' => 2430,  'L' => 2440, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 47, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 2090, 'B' => 1650, 'C' => 1650, 'D' => 1430, 'E' => 1430, 'F' => 1220, 'G' => 1220, 'H' => 1100, 'I' => 1000, 'J' => 1100, 'K' => 990, 'L' => 1000, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 2320, 'B' => 1880, 'C' => 1880, 'D' => 1650, 'E' => 1650, 'F' => 1430, 'G' => 1430, 'H' => 1330, 'I' => 1230, 'J' => 1330, 'K' => 1220, 'L' => 1230,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2530, 'B' => 2090, 'C' => 2090, 'D' => 1880, 'E' => 1880, 'F' => 1650, 'G' => 1650, 'H' => 1540, 'I' => 1440, 'J' => 1540, 'K' => 1430,  'L' => 1440, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2750, 'B' => 2320, 'C' => 2320, 'D' => 2090, 'E' => 2090, 'F' => 1880, 'G' => 1880, 'H' => 1770, 'I' => 1670, 'J' => 1770, 'K' => 1650,  'L' => 1670, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2980, 'B' => 2530, 'C' => 2530, 'D' => 2320, 'E' => 2320, 'F' => 2090, 'G' => 2090, 'H' => 1980, 'I' => 1890, 'J' => 1980, 'K' => 1880,  'L' => 1890, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3420, 'B' => 2980, 'C' => 2980, 'D' => 2750, 'E' => 2750, 'F' => 2530, 'G' => 2530, 'H' => 2430, 'I' => 2330, 'J' => 2430, 'K' => 2320,  'L' => 2330, ), ),
            )
        ),
        array('carrier_code' => 'fukutsu', 'service_code' => 'parcel1', 'zone_id' => 65, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '2', 'rates' => array ('A' => 2100, 'B' => 1670, 'C' => 1670, 'D' => 1440, 'E' => 1440, 'F' => 1230, 'G' => 1230, 'H' => 1120, 'I' => 1010, 'J' => 1120, 'K' => 1000, 'L' => 990, ), ),
            90 => array ( 'size' => '90', 'weight' => '5', 'rates' => array ('A' => 2330, 'B' => 1890, 'C' => 1890, 'D' => 1670, 'E' => 1670, 'F' => 1440, 'G' => 1440, 'H' => 1340, 'I' => 1240, 'J' => 1340, 'K' => 1230, 'L' => 1220,  ), ),
            100 => array ( 'size' => '100', 'weight' => '10', 'rates' => array ('A' => 2540, 'B' => 2100, 'C' => 2100, 'D' => 1890, 'E' => 1890, 'F' => 1670, 'G' => 1670, 'H' => 1550, 'I' => 1450, 'J' => 1550, 'K' => 1440,  'L' => 1430, ), ),
            120 => array ( 'size' => '120', 'weight' => '15', 'rates' => array ('A' => 2770, 'B' => 2330, 'C' => 2330, 'D' => 2100, 'E' => 2100, 'F' => 1890, 'G' => 1890, 'H' => 1780, 'I' => 1680, 'J' => 1780, 'K' => 1670,  'L' => 1650, ), ),
            140 => array ( 'size' => '140', 'weight' => '20', 'rates' => array ('A' => 2990, 'B' => 2540, 'C' => 2540, 'D' => 2330, 'E' => 2330, 'F' => 2100, 'G' => 2100, 'H' => 1990, 'I' => 1900, 'J' => 1990, 'K' => 1890,  'L' => 1880, ), ),
            160 => array ( 'size' => '160', 'weight' => '30', 'rates' => array ('A' => 3430, 'B' => 2990, 'C' => 2990, 'D' => 2770, 'E' => 2770, 'F' => 2540, 'G' => 2540, 'H' => 2440, 'I' => 2340, 'J' => 2440, 'K' => 2330,  'L' => 2320, ), ),
            )
        ),
        // 日本郵便 - EMS
        array('carrier_code' => 'jpems', 'service_code' => 'ems', 'zone_id' => 52, 'shipping_rates' => array (
            10 => array ( 'size' => '10', 'weight' => '0.5', 'rates' => array ('A' =>1400, 'B' => 2000, 'C' => 2200, 'D' => 2400, ), ),
            20 => array ( 'size' => '20', 'weight' => '0.6', 'rates' => array ('A' =>1540, 'B' => 2180, 'C' => 2400, 'D' => 2740, ), ),
            30 => array ( 'size' => '30', 'weight' => '0.7', 'rates' => array ('A' =>1680, 'B' => 2360, 'C' => 2600, 'D' => 3080, ), ),
            40 => array ( 'size' => '40', 'weight' => '0.8', 'rates' => array ('A' =>1820, 'B' => 2540, 'C' => 2800, 'D' => 3420, ), ),
            50 => array ( 'size' => '50', 'weight' => '0.9', 'rates' => array ('A' =>1960, 'B' => 2720, 'C' => 3000, 'D' => 3760, ), ),
            60 => array ( 'size' => '60', 'weight' => '1', 'rates' => array ('A' =>2100, 'B' => 2900, 'C' => 3200, 'D' => 4100, ), ),
            70 => array ( 'size' => '70', 'weight' => '1.25', 'rates' => array ('A' =>2400, 'B' => 3300, 'C' => 3650, 'D' => 4900, ), ),
            80 => array ( 'size' => '80', 'weight' => '1.5', 'rates' => array ('A' =>2700, 'B' => 3700, 'C' => 4100, 'D' => 5700, ), ),
            90 => array ( 'size' => '90', 'weight' => '1.75', 'rates' => array ('A' =>3000, 'B' => 4100, 'C' => 4550, 'D' => 6500, ), ),
            100 => array ( 'size' => '100', 'weight' => '2', 'rates' => array ('A' =>3300, 'B' => 4500, 'C' => 5000, 'D' => 7300, ), ),
            110 => array ( 'size' => '110', 'weight' => '2.5', 'rates' => array ('A' =>3800, 'B' => 5200, 'C' => 5800, 'D' => 8800, ), ),
            120 => array ( 'size' => '120', 'weight' => '3', 'rates' => array ('A' =>4300, 'B' => 5900, 'C' => 6600, 'D' => 10300, ), ),
            130 => array ( 'size' => '130', 'weight' => '3.5', 'rates' => array ('A' =>4800, 'B' => 6600, 'C' => 7400, 'D' => 11800, ), ),
            140 => array ( 'size' => '140', 'weight' => '4', 'rates' => array ('A' =>5300, 'B' => 7300, 'C' => 8200, 'D' => 13300, ), ),
            150 => array ( 'size' => '150', 'weight' => '4.5', 'rates' => array ('A' =>5800, 'B' => 8000, 'C' => 9000, 'D' => 14800, ), ),
            160 => array ( 'size' => '160', 'weight' => '5', 'rates' => array ('A' =>6300, 'B' => 8700, 'C' => 9800, 'D' => 16300, ), ),
            170 => array ( 'size' => '170', 'weight' => '5.5', 'rates' => array ('A' =>6800, 'B' => 9400, 'C' => 10600, 'D' => 17800, ), ),
            180 => array ( 'size' => '180', 'weight' => '6', 'rates' => array ('A' =>7300, 'B' => 10100, 'C' => 11400, 'D' => 19300, ), ),
            190 => array ( 'size' => '190', 'weight' => '7', 'rates' => array ('A' =>8100, 'B' => 11200, 'C' => 12700, 'D' => 21400, ), ),
            200 => array ( 'size' => '200', 'weight' => '8', 'rates' => array ('A' =>8900, 'B' => 12300, 'C' => 14000, 'D' => 23500, ), ),
            210 => array ( 'size' => '210', 'weight' => '9', 'rates' => array ('A' =>9700, 'B' => 13400, 'C' => 15300, 'D' => 25600, ), ),
            220 => array ( 'size' => '220', 'weight' => '10', 'rates' => array ('A' =>10500, 'B' => 14500, 'C' => 16600, 'D' => 27700, ), ),
            230 => array ( 'size' => '230', 'weight' => '11', 'rates' => array ('A' =>11300, 'B' => 15600, 'C' => 17900, 'D' => 29800, ), ),
            240 => array ( 'size' => '240', 'weight' => '12', 'rates' => array ('A' =>12100, 'B' => 16700, 'C' => 19200, 'D' => 31900, ), ),
            250 => array ( 'size' => '250', 'weight' => '13', 'rates' => array ('A' =>12900, 'B' => 17800, 'C' => 20500, 'D' => 34000, ), ),
            260 => array ( 'size' => '260', 'weight' => '14', 'rates' => array ('A' =>13700, 'B' => 18900, 'C' => 21800, 'D' => 36100, ), ),
            270 => array ( 'size' => '270', 'weight' => '15', 'rates' => array ('A' =>14500, 'B' => 20000, 'C' => 23100, 'D' => 38200, ), ),
            280 => array ( 'size' => '280', 'weight' => '16', 'rates' => array ('A' =>15300, 'B' => 21100, 'C' => 24400, 'D' => 40300, ), ),
            290 => array ( 'size' => '290', 'weight' => '17', 'rates' => array ('A' =>16100, 'B' => 22200, 'C' => 25700, 'D' => 42400, ), ),
            300 => array ( 'size' => '300', 'weight' => '18', 'rates' => array ('A' =>16900, 'B' => 23300, 'C' => 27000, 'D' => 44500, ), ),
            310 => array ( 'size' => '310', 'weight' => '19', 'rates' => array ('A' =>17700, 'B' => 24400, 'C' => 28300, 'D' => 46600, ), ),
            320 => array ( 'size' => '320', 'weight' => '20', 'rates' => array ('A' =>18500, 'B' => 25500, 'C' => 29600, 'D' => 48700, ), ),
            330 => array ( 'size' => '330', 'weight' => '21', 'rates' => array ('A' =>19300, 'B' => 26600, 'C' => 30900, 'D' => 50800, ), ),
            340 => array ( 'size' => '340', 'weight' => '22', 'rates' => array ('A' =>20100, 'B' => 27700, 'C' => 32200, 'D' => 52900, ), ),
            350 => array ( 'size' => '350', 'weight' => '23', 'rates' => array ('A' =>20900, 'B' => 28800, 'C' => 33500, 'D' => 55000, ), ),
            360 => array ( 'size' => '360', 'weight' => '24', 'rates' => array ('A' =>21700, 'B' => 29900, 'C' => 34800, 'D' => 57100, ), ),
            370 => array ( 'size' => '370', 'weight' => '25', 'rates' => array ('A' =>22500, 'B' => 31000, 'C' => 36100, 'D' => 59200, ), ),
            380 => array ( 'size' => '380', 'weight' => '26', 'rates' => array ('A' =>23300, 'B' => 32100, 'C' => 37400, 'D' => 61300, ), ),
            390 => array ( 'size' => '390', 'weight' => '27', 'rates' => array ('A' =>24100, 'B' => 33200, 'C' => 38700, 'D' => 63400, ), ),
            400 => array ( 'size' => '400', 'weight' => '28', 'rates' => array ('A' =>24900, 'B' => 34300, 'C' => 40000, 'D' => 65500, ), ),
            410 => array ( 'size' => '410', 'weight' => '29', 'rates' => array ('A' =>25700, 'B' => 35400, 'C' => 41300, 'D' => 67600, ), ),
            420 => array ( 'size' => '420', 'weight' => '30', 'rates' => array ('A' =>26500, 'B' => 36500, 'C' => 42600, 'D' => 69700, ), ),
            )
        ),
        // 日本郵便 - ゆうパック
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 53, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>810, 'B' => 1100, 'C' => 1300, 'D' => 1300, 'E' => 1430, 'F' => 1430, 'G' => 1540, 'H' => 1540, 'I' => 1540, 'J' => 1540, 'K' => 1550, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1030, 'B' => 1310, 'C' => 1530, 'D' => 1530, 'E' => 1650, 'F' => 1650, 'G' => 1750, 'H' => 1750, 'I' => 1750, 'J' => 1750, 'K' => 1760, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>1280, 'B' => 1560, 'C' => 1760, 'D' => 1760, 'E' => 1890, 'F' => 1890, 'G' => 2000, 'H' => 2000, 'I' => 2000, 'J' => 2000, 'K' => 2010, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>1530, 'B' => 1800, 'C' => 2020, 'D' => 2020, 'E' => 2140, 'F' => 2140, 'G' => 2240, 'H' => 2240, 'I' => 2240, 'J' => 2240, 'K' => 2270, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>1780, 'B' => 2060, 'C' => 2260, 'D' => 2260, 'E' => 2390, 'F' => 2390, 'G' => 2500, 'H' => 2500, 'I' => 2500, 'J' => 2500, 'K' => 2550, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2010, 'B' => 2270, 'C' => 2490, 'D' => 2490, 'E' => 2610, 'F' => 2610, 'G' => 2720, 'H' => 2720, 'I' => 2720, 'J' => 2720, 'K' => 2770, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>2340, 'B' => 2640, 'C' => 2850, 'D' => 2850, 'E' => 2980, 'F' => 2980, 'G' => 3100, 'H' => 3100, 'I' => 3100, 'J' => 3100, 'K' => 3160, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 54, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1100, 'B' => 870, 'C' => 870, 'D' => 870, 'E' => 970, 'F' => 970, 'G' => 1100, 'H' => 1300, 'I' => 1300, 'J' => 1540, 'K' => 1550, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1310, 'B' => 1100, 'C' => 1100, 'D' => 1100, 'E' => 1200, 'F' => 1200, 'G' => 1310, 'H' => 1530, 'I' => 1530, 'J' => 1750, 'K' => 1760, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>1560, 'B' => 1330, 'C' => 1330, 'D' => 1330, 'E' => 1440, 'F' => 1440, 'G' => 1560, 'H' => 1760, 'I' => 1760, 'J' => 2000, 'K' => 2010, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>1800, 'B' => 1590, 'C' => 1590, 'D' => 1590, 'E' => 1690, 'F' => 1690, 'G' => 1800, 'H' => 2020, 'I' => 2020, 'J' => 2240, 'K' => 2270, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2060, 'B' => 1830, 'C' => 1830, 'D' => 1830, 'E' => 1950, 'F' => 1950, 'G' => 2060, 'H' => 2260, 'I' => 2260, 'J' => 2500, 'K' => 2550, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2270, 'B' => 2060, 'C' => 2060, 'D' => 2060, 'E' => 2160, 'F' => 2160, 'G' => 2270, 'H' => 2490, 'I' => 2490, 'J' => 2720, 'K' => 2770, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>2640, 'B' => 2410, 'C' => 2410, 'D' => 2410, 'E' => 2530, 'F' => 2530, 'G' => 2640, 'H' => 2850, 'I' => 2850, 'J' => 3100, 'K' => 3160, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 55, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1300, 'B' => 870, 'C' => 870, 'D' => 870, 'E' => 870, 'F' => 870, 'G' => 970, 'H' => 1100, 'I' => 1100, 'J' => 1300, 'K' => 1350, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1530, 'B' => 1100, 'C' => 1100, 'D' => 1100, 'E' => 1100, 'F' => 1100, 'G' => 1200, 'H' => 1310, 'I' => 1310, 'J' => 1530, 'K' => 1630, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>1760, 'B' => 1330, 'C' => 1330, 'D' => 1330, 'E' => 1330, 'F' => 1330, 'G' => 1440, 'H' => 1560, 'I' => 1560, 'J' => 1760, 'K' => 1900, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2020, 'B' => 1590, 'C' => 1590, 'D' => 1590, 'E' => 1590, 'F' => 1590, 'G' => 1690, 'H' => 1800, 'I' => 1800, 'J' => 2020, 'K' => 2170, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2260, 'B' => 1830, 'C' => 1830, 'D' => 1830, 'E' => 1830, 'F' => 1830, 'G' => 1950, 'H' => 2060, 'I' => 2060, 'J' => 2260, 'K' => 2440, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2490, 'B' => 2060, 'C' => 2060, 'D' => 2060, 'E' => 2060, 'F' => 2060, 'G' => 2160, 'H' => 2270, 'I' => 2270, 'J' => 2490, 'K' => 2660, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>2850, 'B' => 2410, 'C' => 2410, 'D' => 2410, 'E' => 2410, 'F' => 2410, 'G' => 2530, 'H' => 2640, 'I' => 2640, 'J' => 2850, 'K' => 3060, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 56, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1300, 'B' => 870, 'C' => 870, 'D' => 870, 'E' => 870, 'F' => 870, 'G' => 970, 'H' => 1100, 'I' => 1100, 'J' => 1300, 'K' => 1470, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1530, 'B' => 1100, 'C' => 1100, 'D' => 1100, 'E' => 1100, 'F' => 1100, 'G' => 1200, 'H' => 1310, 'I' => 1310, 'J' => 1530, 'K' => 1730, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>1760, 'B' => 1330, 'C' => 1330, 'D' => 1330, 'E' => 1330, 'F' => 1330, 'G' => 1440, 'H' => 1560, 'I' => 1560, 'J' => 1760, 'K' => 2010, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2020, 'B' => 1590, 'C' => 1590, 'D' => 1590, 'E' => 1590, 'F' => 1590, 'G' => 1690, 'H' => 1800, 'I' => 1800, 'J' => 2020, 'K' => 2270, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2260, 'B' => 1830, 'C' => 1830, 'D' => 1830, 'E' => 1830, 'F' => 1830, 'G' => 1950, 'H' => 2060, 'I' => 2060, 'J' => 2260, 'K' => 2550, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2490, 'B' => 2060, 'C' => 2060, 'D' => 2060, 'E' => 2060, 'F' => 2060, 'G' => 2160, 'H' => 2270, 'I' => 2270, 'J' => 2490, 'K' => 2770, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>2850, 'B' => 2410, 'C' => 2410, 'D' => 2410, 'E' => 2410, 'F' => 2410, 'G' => 2530, 'H' => 2640, 'I' => 2640, 'J' => 2850, 'K' => 3160, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 57, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1430, 'B' => 970, 'C' => 870, 'D' => 870, 'E' => 870, 'F' => 870, 'G' => 870, 'H' => 970, 'I' => 970, 'J' => 1100, 'K' => 1470, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1650, 'B' => 1200, 'C' => 1100, 'D' => 1100, 'E' => 1100, 'F' => 1100, 'G' => 1100, 'H' => 1200, 'I' => 1200, 'J' => 1310, 'K' => 1730, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>1890, 'B' => 1440, 'C' => 1330, 'D' => 1330, 'E' => 1330, 'F' => 1330, 'G' => 1330, 'H' => 1440, 'I' => 1440, 'J' => 1560, 'K' => 2010, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2140, 'B' => 1690, 'C' => 1590, 'D' => 1590, 'E' => 1590, 'F' => 1590, 'G' => 1590, 'H' => 1690, 'I' => 1690, 'J' => 1800, 'K' => 2270, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2390, 'B' => 1950, 'C' => 1830, 'D' => 1830, 'E' => 1830, 'F' => 1830, 'G' => 1830, 'H' => 1950, 'I' => 1950, 'J' => 2060, 'K' => 2550, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2610, 'B' => 2160, 'C' => 2060, 'D' => 2060, 'E' => 2060, 'F' => 2060, 'G' => 2060, 'H' => 2160, 'I' => 2160, 'J' => 2270, 'K' => 2770, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>2980, 'B' => 2530, 'C' => 2410, 'D' => 2410, 'E' => 2410, 'F' => 2410, 'G' => 2410, 'H' => 2530, 'I' => 2530, 'J' => 2640, 'K' => 3160, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 58, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1430, 'B' => 970, 'C' => 870, 'D' => 870, 'E' => 870, 'F' => 870, 'G' => 870, 'H' => 970, 'I' => 970, 'J' => 1100, 'K' => 1350, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1650, 'B' => 1200, 'C' => 1100, 'D' => 1100, 'E' => 1100, 'F' => 1100, 'G' => 1100, 'H' => 1200, 'I' => 1200, 'J' => 1310, 'K' => 1630, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>1890, 'B' => 1440, 'C' => 1330, 'D' => 1330, 'E' => 1330, 'F' => 1330, 'G' => 1330, 'H' => 1440, 'I' => 1440, 'J' => 1560, 'K' => 1900, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2140, 'B' => 1690, 'C' => 1590, 'D' => 1590, 'E' => 1590, 'F' => 1590, 'G' => 1590, 'H' => 1690, 'I' => 1690, 'J' => 1800, 'K' => 2170, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2390, 'B' => 1950, 'C' => 1830, 'D' => 1830, 'E' => 1830, 'F' => 1830, 'G' => 1830, 'H' => 1950, 'I' => 1950, 'J' => 2060, 'K' => 2440, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2610, 'B' => 2160, 'C' => 2060, 'D' => 2060, 'E' => 2060, 'F' => 2060, 'G' => 2060, 'H' => 2160, 'I' => 2160, 'J' => 2270, 'K' => 2660, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>2980, 'B' => 2530, 'C' => 2410, 'D' => 2410, 'E' => 2410, 'F' => 2410, 'G' => 2410, 'H' => 2530, 'I' => 2530, 'J' => 2640, 'K' => 3060, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 59, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1540, 'B' => 1100, 'C' => 970, 'D' => 970, 'E' => 870, 'F' => 870, 'G' => 870, 'H' => 870, 'I' => 870, 'J' => 970, 'K' => 1350, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1750, 'B' => 1310, 'C' => 1200, 'D' => 1200, 'E' => 1100, 'F' => 1100, 'G' => 1100, 'H' => 1100, 'I' => 1100, 'J' => 1200, 'K' => 1630, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>2000, 'B' => 1560, 'C' => 1440, 'D' => 1440, 'E' => 1330, 'F' => 1330, 'G' => 1330, 'H' => 1330, 'I' => 1330, 'J' => 1440, 'K' => 1900, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2240, 'B' => 1800, 'C' => 1690, 'D' => 1690, 'E' => 1590, 'F' => 1590, 'G' => 1590, 'H' => 1590, 'I' => 1590, 'J' => 1690, 'K' => 2170, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2500, 'B' => 2060, 'C' => 1950, 'D' => 1950, 'E' => 1830, 'F' => 1830, 'G' => 1830, 'H' => 1830, 'I' => 1830, 'J' => 1950, 'K' => 2440, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2720, 'B' => 2270, 'C' => 2160, 'D' => 2160, 'E' => 2060, 'F' => 2060, 'G' => 2060, 'H' => 2060, 'I' => 2060, 'J' => 2160, 'K' => 2660, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>3100, 'B' => 2640, 'C' => 2530, 'D' => 2530, 'E' => 2410, 'F' => 2410, 'G' => 2410, 'H' => 2410, 'I' => 2410, 'J' => 2530, 'K' => 3060, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 60, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1540, 'B' => 1300, 'C' => 1100, 'D' => 1100, 'E' => 970, 'F' => 970, 'G' => 870, 'H' => 870, 'I' => 870, 'J' => 870, 'K' => 1230, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1750, 'B' => 1530, 'C' => 1310, 'D' => 1310, 'E' => 1200, 'F' => 1200, 'G' => 1100, 'H' => 1100, 'I' => 1100, 'J' => 1100, 'K' => 1510, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>2000, 'B' => 1760, 'C' => 1560, 'D' => 1560, 'E' => 1440, 'F' => 1440, 'G' => 1330, 'H' => 1330, 'I' => 1330, 'J' => 1330, 'K' => 1770, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2240, 'B' => 2020, 'C' => 1800, 'D' => 1800, 'E' => 1690, 'F' => 1690, 'G' => 1590, 'H' => 1590, 'I' => 1590, 'J' => 1590, 'K' => 2050, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2500, 'B' => 2260, 'C' => 2060, 'D' => 2060, 'E' => 1950, 'F' => 1950, 'G' => 1830, 'H' => 1830, 'I' => 1830, 'J' => 1830, 'K' => 2310, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2720, 'B' => 2490, 'C' => 2270, 'D' => 2270, 'E' => 2160, 'F' => 2160, 'G' => 2060, 'H' => 2060, 'I' => 2060, 'J' => 2060, 'K' => 2540, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>3100, 'B' => 2850, 'C' => 2640, 'D' => 2640, 'E' => 2530, 'F' => 2530, 'G' => 2410, 'H' => 2410, 'I' => 2410, 'J' => 2410, 'K' => 2910, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 61, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1540, 'B' => 1300, 'C' => 1100, 'D' => 1100, 'E' => 970, 'F' => 970, 'G' => 870, 'H' => 870, 'I' => 870, 'J' => 970, 'K' => 1350, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1750, 'B' => 1530, 'C' => 1310, 'D' => 1310, 'E' => 1200, 'F' => 1200, 'G' => 1100, 'H' => 1100, 'I' => 1100, 'J' => 1200, 'K' => 1630, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>2000, 'B' => 1760, 'C' => 1560, 'D' => 1560, 'E' => 1440, 'F' => 1440, 'G' => 1330, 'H' => 1330, 'I' => 1330, 'J' => 1440, 'K' => 1900, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2240, 'B' => 2020, 'C' => 1800, 'D' => 1800, 'E' => 1690, 'F' => 1690, 'G' => 1590, 'H' => 1590, 'I' => 1590, 'J' => 1690, 'K' => 2170, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2500, 'B' => 2260, 'C' => 2060, 'D' => 2060, 'E' => 1950, 'F' => 1950, 'G' => 1830, 'H' => 1830, 'I' => 1830, 'J' => 1950, 'K' => 2440, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2720, 'B' => 2490, 'C' => 2270, 'D' => 2270, 'E' => 2160, 'F' => 2160, 'G' => 2060, 'H' => 2060, 'I' => 2060, 'J' => 2160, 'K' => 2660, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>3100, 'B' => 2850, 'C' => 2640, 'D' => 2640, 'E' => 2530, 'F' => 2530, 'G' => 2410, 'H' => 2410, 'I' => 2410, 'J' => 2530, 'K' => 3060, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 62, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1540, 'B' => 1540, 'C' => 1300, 'D' => 1300, 'E' => 1100, 'F' => 1100, 'G' => 970, 'H' => 870, 'I' => 970, 'J' => 870, 'K' => 1030, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1750, 'B' => 1750, 'C' => 1530, 'D' => 1530, 'E' => 1310, 'F' => 1310, 'G' => 1200, 'H' => 1100, 'I' => 1200, 'J' => 1100, 'K' => 1290, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>2000, 'B' => 2000, 'C' => 1760, 'D' => 1760, 'E' => 1560, 'F' => 1560, 'G' => 1440, 'H' => 1330, 'I' => 1440, 'J' => 1330, 'K' => 1570, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2240, 'B' => 2240, 'C' => 2020, 'D' => 2020, 'E' => 1800, 'F' => 1800, 'G' => 1690, 'H' => 1590, 'I' => 1690, 'J' => 1590, 'K' => 1830, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2500, 'B' => 2500, 'C' => 2260, 'D' => 2260, 'E' => 2060, 'F' => 2060, 'G' => 1950, 'H' => 1830, 'I' => 1950, 'J' => 1830, 'K' => 2110, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2720, 'B' => 2720, 'C' => 2490, 'D' => 2490, 'E' => 2270, 'F' => 2270, 'G' => 2160, 'H' => 2060, 'I' => 2160, 'J' => 2060, 'K' => 2320, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>3100, 'B' => 3100, 'C' => 2850, 'D' => 2850, 'E' => 2640, 'F' => 2640, 'G' => 2530, 'H' => 2410, 'I' => 2530, 'J' => 2410, 'K' => 2700, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 63, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>1550, 'B' => 1550, 'C' => 1350, 'D' => 1470, 'E' => 1470, 'F' => 1350, 'G' => 1350, 'H' => 1230, 'I' => 1350, 'J' => 1030, 'K' => 810, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1760, 'B' => 1760, 'C' => 1630, 'D' => 1730, 'E' => 1730, 'F' => 1630, 'G' => 1630, 'H' => 1510, 'I' => 1630, 'J' => 1290, 'K' => 1030, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>2010, 'B' => 2010, 'C' => 1900, 'D' => 2010, 'E' => 2010, 'F' => 1900, 'G' => 1900, 'H' => 1770, 'I' => 1900, 'J' => 1570, 'K' => 1280, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>2270, 'B' => 2270, 'C' => 2170, 'D' => 2270, 'E' => 2270, 'F' => 2170, 'G' => 2170, 'H' => 2050, 'I' => 2170, 'J' => 1830, 'K' => 1530, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>2550, 'B' => 2550, 'C' => 2440, 'D' => 2550, 'E' => 2550, 'F' => 2440, 'G' => 2440, 'H' => 2310, 'I' => 2440, 'J' => 2110, 'K' => 1780, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2770, 'B' => 2770, 'C' => 2660, 'D' => 2770, 'E' => 2770, 'F' => 2660, 'G' => 2660, 'H' => 2540, 'I' => 2660, 'J' => 2320, 'K' => 2010, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>3160, 'B' => 3160, 'C' => 3060, 'D' => 3160, 'E' => 3160, 'F' => 3060, 'G' => 3060, 'H' => 2910, 'I' => 3060, 'J' => 2700, 'K' => 2340, 'X' => 2340, ), ),
            )
        ),
        array('carrier_code' => 'jpost', 'service_code' => 'standard', 'zone_id' => 64, 'shipping_rates' => array (
            60 => array ( 'size' => '60', 'weight' => '25', 'rates' => array ('A' =>810, 'B' => 810, 'C' => 810, 'D' => 810, 'E' => 810, 'F' => 810, 'G' => 810, 'H' => 810, 'I' => 810, 'J' => 810, 'K' => 810, 'X' => 810, ), ),
            80 => array ( 'size' => '80', 'weight' => '25', 'rates' => array ('A' =>1030, 'B' => 1030, 'C' => 1030, 'D' => 1030, 'E' => 1030, 'F' => 1030, 'G' => 1030, 'H' => 1030, 'I' => 1030, 'J' => 1030, 'K' => 1030, 'X' => 1030, ), ),
            100 => array ( 'size' => '100', 'weight' => '25', 'rates' => array ('A' =>1280, 'B' => 1280, 'C' => 1280, 'D' => 1280, 'E' => 1280, 'F' => 1280, 'G' => 1280, 'H' => 1280, 'I' => 1280, 'J' => 1280, 'K' => 1280, 'X' => 1280, ), ),
            120 => array ( 'size' => '120', 'weight' => '25', 'rates' => array ('A' =>1530, 'B' => 1530, 'C' => 1530, 'D' => 1530, 'E' => 1530, 'F' => 1530, 'G' => 1530, 'H' => 1530, 'I' => 1530, 'J' => 1530, 'K' => 1530, 'X' => 1530, ), ),
            140 => array ( 'size' => '140', 'weight' => '25', 'rates' => array ('A' =>1780, 'B' => 1780, 'C' => 1780, 'D' => 1780, 'E' => 1780, 'F' => 1780, 'G' => 1780, 'H' => 1780, 'I' => 1780, 'J' => 1780, 'K' => 1780, 'X' => 1780, ), ),
            160 => array ( 'size' => '160', 'weight' => '25', 'rates' => array ('A' =>2010, 'B' => 2010, 'C' => 2010, 'D' => 2010, 'E' => 2010, 'F' => 2010, 'G' => 2010, 'H' => 2010, 'I' => 2010, 'J' => 2010, 'K' => 2010, 'X' => 2010, ), ),
            170 => array ( 'size' => '170', 'weight' => '25', 'rates' => array ('A' =>2340, 'B' => 2340, 'C' => 2340, 'D' => 2340, 'E' => 2340, 'F' => 2340, 'G' => 2340, 'H' => 2340, 'I' => 2340, 'J' => 2340, 'K' => 2340, 'X' => 2340, ), ),
            )
        ),
    );

}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################
