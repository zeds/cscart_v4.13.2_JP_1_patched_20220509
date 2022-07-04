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

// $Id: profiles_create_profile.php by tommy from cs-cart.jp 2016
// 会員アカウントを登録した際に送信されるメールで使用可能なテンプレート変数

// Modified to fix bug by takahashi from cs-cart.jp 2017 BOF
// フィールドがラジオボタン・セレクトボックス・日付の場合に値を取得

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////

// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// ユーザーに関するデータ
	$tpl_user_data = $tpl_base_data['user_data']->value;

    // 一般設定に関するデータ
    $tpl_general_settngs = Registry::get('settings.General');

	// パスワード
	$tpl_password = $tpl_base_data['password']->value;

	// Amazon Pay経由での会員登録フラグ
	$_is_amazon_checkout = $tpl_base_data['amazon_checkout']->value;

	// Amazon Pay経由での会員登録以外はパスワードを非表示化
	if( !empty($_is_amazon_checkout) && $_is_amazon_checkout == 'Y' ){
		if( empty($tpl_password) ){
			$tpl_password = __("hidden");
		}
	}else{
		$tpl_password = __("hidden");
	}

}else{
	$tpl_user_data = array();
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーの言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mtpl_lang_code = $tpl_user_data['lang_code'];
	$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
}
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
// 連絡先情報
$mail_tpl_var_contact = 
	array(
		// ユーザーID
		'U_ID' =>
				array('desc' => 'user_id',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['user_id'] : ''),
		// 姓
		'U_LASTNAME' =>
				array('desc' => 'first_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['firstname'] : ''),
		// 名
		'U_FIRSTNAME' =>
				array('desc' => 'last_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['lastname'] : ''),
		// 生年月日
		'BIRTHDAY' =>
				array('desc' => 'date_of_birth',
						'value' => empty($_edit_mail_tpl) ? ( !empty($tpl_user_data['birthday']) ) ? date('Y/m/d', $tpl_user_data['birthday']) : ''  : ''),
		// 会社名
		'U_COMPANY' =>
				array('desc' => 'company',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['company'] : ''),
		// メールアドレス
		'U_EMAIL' =>
				array('desc' => 'emails',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['email'] : ''),
		// パスワード
		'U_PASSWORD' =>
			array('desc' => 'password',
				'value' => empty($_edit_mail_tpl) ? $tpl_password : ''),

		// 電話番号
		'U_PHONE' =>
				array('desc' => 'phone',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['phone'] : ''),
		// FAX番号
		'U_FAX' =>
				array('desc' => 'fax',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['fax'] : ''),
		// URL
		'U_URL' =>
				array('desc' => 'url',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['url'] : ''),
		// ユーザーグループ
		'U_USERGROUPS' =>
				array('desc' => 'usergroup',
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_usrgroups($tpl_user_data['usergroups'], $tpl_user_data['lang_code']) : ''),
		);

// ユーザーが追加した連絡先情報
$tpl_extra_user_data_c = fn_mtpl_get_profiles_extra_fields($tpl_user_data, 'C');
$mail_tpl_var_contact_extra = array();
if($tpl_extra_user_data_c){
	foreach($tpl_extra_user_data_c as $key => $val) {
        //////////////////////////////////////////////////////////////////////////////////////////
        // Modified to fix bug by takahashi from cs-cart.jp 2017 BOF
        // フィールドがラジオボタン・セレクトボックス・日付の場合に値を取得
        //////////////////////////////////////////////////////////////////////////////////////////
        $select_value = $val['field_val'];

        // フィールドがラジオボタン・セレクトボックス・日付の場合
        $profile_fields_selectbox_value = db_get_field("SELECT ?:profile_field_descriptions.description FROM ?:profile_field_descriptions INNER JOIN ?:profile_field_values ON ?:profile_field_descriptions.object_id = ?:profile_field_values.value_id INNER JOIN ?:profile_fields ON ?:profile_field_values.field_id = ?:profile_fields.field_id WHERE  ?:profile_field_descriptions.object_type = 'V' AND ?:profile_field_descriptions.lang_code = ?s AND ?:profile_fields.field_type IN ('R', 'S') AND ?:profile_field_values.value_id = ?i", $lang_code, $val['field_val']);

        if (!empty($profile_fields_selectbox_value)) {
            $select_value = $profile_fields_selectbox_value;
        }

        // フィールドが日付の場合
        $profile_fields_date_value = db_get_field("SELECT ?:profile_fields.field_id FROM ?:profile_fields WHERE ?:profile_fields.field_type = 'D' AND ?:profile_fields.field_id = ?i", $key);

         if (!empty($profile_fields_date_value) && $val['field_val'] != ''){
             $select_value = date("Y/m/d ", (int)$val['field_val']);
         }

        $mail_tpl_var_contact_extra['U' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_c_info') . $val['field_desc'], 'value' => $select_value);
        //$mail_tpl_var_contact_extra['U' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_c_info') . $val['field_desc'], 'value' => $val['field_val']);
        //////////////////////////////////////////////////////////////////////////////////////////
        // Modified to fix bug by takahashi from cs-cart.jp 2017 EOF
        //////////////////////////////////////////////////////////////////////////////////////////
	}
}

// 請求先情報
$mail_tpl_var_billing =
	array(
		// 姓
		'B_LASTNAME' =>
				array('desc' => 'mtpl_b_first_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_firstname'] : ''),
		// 名
		'B_FIRSTNAME' =>
				array('desc' => 'mtpl_b_last_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_lastname'] : ''),
		// 電話番号
		'B_PHONE' =>
				array('desc' => 'mtpl_b_phone',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_phone'] : ''),
		// 国名
		'B_COUNTRY' =>
				array('desc' => 'mtpl_b_country',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_country_descr'] : ''),
		// 郵便番号
		'B_ZIP' =>
				array('desc' => 'mtpl_b_zipcodes',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_zipcode'] : ''),
		// 都道府県
		'B_STATE' =>
				array('desc' => 'mtpl_b_state',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_state'] : ''),
		// 市区町村
		'B_CITY' => 
				array('desc' => 'mtpl_b_city',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_city'] : ''),
		// 番地
		'B_ADDRESS' =>
				array('desc' => 'mtpl_b_address',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_address'] : ''),
		// ビル・建物名
		'B_ADDRESS2' =>
				array('desc' => 'mtpl_b_address_2',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['b_address_2'] : ''),
		);

// ユーザーが追加した請求先情報
$tpl_extra_user_data_b = fn_mtpl_get_profiles_extra_fields($tpl_user_data, 'B');
$mail_tpl_var_billing_extra = array();
if($tpl_extra_user_data_b){
	foreach($tpl_extra_user_data_b as $key => $val){
        //////////////////////////////////////////////////////////////////////////////////////////
        // Modified to fix bug by takahashi from cs-cart.jp 2017 BOF
        // フィールドがラジオボタン・セレクトボックス・日付の場合に値を取得
        //////////////////////////////////////////////////////////////////////////////////////////
        $select_value = $val['field_val'];
        $profile_fields_selectbox_value = db_get_field("SELECT ?:profile_field_descriptions.description FROM ?:profile_field_descriptions INNER JOIN ?:profile_field_values ON ?:profile_field_descriptions.object_id = ?:profile_field_values.value_id INNER JOIN ?:profile_fields ON ?:profile_field_values.field_id = ?:profile_fields.field_id WHERE  ?:profile_field_descriptions.object_type = 'V' AND ?:profile_field_descriptions.lang_code = ?s AND ?:profile_fields.field_type IN ('R', 'S') AND ?:profile_field_values.value_id = ?i", $lang_code, $val['field_val']);

        if (!empty($profile_fields_selectbox_value)) {
            $select_value = $profile_fields_selectbox_value;
        }

        // フィールドが日付の場合
        $profile_fields_date_value = db_get_field("SELECT ?:profile_fields.field_id FROM ?:profile_fields WHERE ?:profile_fields.field_type = 'D' AND ?:profile_fields.field_id = ?i", $key);

        if (!empty($profile_fields_date_value) && $val['field_val'] != ''){
            $select_value = date("Y/m/d ", (int)$val['field_val']);
        }

        $mail_tpl_var_billing_extra['B' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_b_info') . $val['field_desc'], 'value' => $select_value);
		//$mail_tpl_var_billing_extra['B' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_b_info') . $val['field_desc'], 'value' => $val['field_val']);
        //////////////////////////////////////////////////////////////////////////////////////////
        // Modified to fix bug by takahashi from cs-cart.jp 2017 EOF
        //////////////////////////////////////////////////////////////////////////////////////////
	}
}

// 配送先情報
$mail_tpl_var_shipping = 
	array(
		// 姓
		'S_LASTNAME' =>
				array('desc' => 'mtpl_s_first_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_firstname'] : ''),
		// 名
		'S_FIRSTNAME' =>
				array('desc' => 'mtpl_s_last_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_lastname'] : ''),
		// 電話番号
		'S_PHONE' =>
				array('desc' => 'mtpl_s_phone',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_phone'] : ''),
		// 国名
		'S_COUNTRY' =>
				array('desc' => 'mtpl_s_country',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_country_descr'] : ''),
		// 郵便番号
		'S_ZIP' =>
				array('desc' => 'mtpl_s_zipcodes',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_zipcode'] : ''),
		// 都道府県
		'S_STATE' =>
				array('desc' => 'mtpl_s_state',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_state'] : ''),
		// 市区町村
		'S_CITY' =>
				array('desc' => 'mtpl_s_city',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_city'] : ''),
		// 番地
		'S_ADDRESS' =>
				array('desc' => 'mtpl_s_address',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_address'] : ''),
		// ビル・建物名
		'S_ADDRESS2' =>
				array('desc' => 'mtpl_s_address_2',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['s_address_2'] : ''),
		// プロフィール名
		'PROFILE_NAME' =>
				array('desc' => 'profile_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_user_data['profile_name'] : ''),
		);

// ユーザーが追加した配送先情報
$tpl_extra_user_data_s = fn_mtpl_get_profiles_extra_fields($tpl_user_data, 'S');
$mail_tpl_var_shipping_extra = array();
if($tpl_extra_user_data_s){
	foreach($tpl_extra_user_data_s as $key => $val){
        //////////////////////////////////////////////////////////////////////////////////////////
        // Modified to fix bug by takahashi from cs-cart.jp 2017 BOF
        // フィールドがラジオボタン・セレクトボックス・日付の場合に値を取得
        //////////////////////////////////////////////////////////////////////////////////////////
        $select_value = $val['field_val'];
        $profile_fields_selectbox_value = db_get_field("SELECT ?:profile_field_descriptions.description FROM ?:profile_field_descriptions INNER JOIN ?:profile_field_values ON ?:profile_field_descriptions.object_id = ?:profile_field_values.value_id INNER JOIN ?:profile_fields ON ?:profile_field_values.field_id = ?:profile_fields.field_id WHERE  ?:profile_field_descriptions.object_type = 'V' AND ?:profile_field_descriptions.lang_code = ?s AND ?:profile_fields.field_type IN ('R', 'S') AND ?:profile_field_values.value_id = ?i", $lang_code, $val['field_val']);

        if (!empty($profile_fields_selectbox_value)) {
            $select_value = $profile_fields_selectbox_value;
        }

        // フィールドが日付の場合
        $profile_fields_date_value = db_get_field("SELECT ?:profile_fields.field_id FROM ?:profile_fields WHERE ?:profile_fields.field_type = 'D' AND ?:profile_fields.field_id = ?i", $key);

        if (!empty($profile_fields_date_value) && $val['field_val'] != ''){
            $select_value = date("Y/m/d ", (int)$val['field_val']);
        }

        $mail_tpl_var_shipping_extra['S' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_s_info') . $val['field_desc'], 'value' => $select_value);
		//$mail_tpl_var_shipping_extra['S' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_s_info') . $val['field_desc'], 'value' => $val['field_val']);
        //////////////////////////////////////////////////////////////////////////////////////////
        // Modified to fix bug by takahashi from cs-cart.jp 2017 EOF
        //////////////////////////////////////////////////////////////////////////////////////////
	}
}

// テンプレート変数をマージ
$mail_tpl_var = array_merge(
						$mail_tpl_var_contact,
						$mail_tpl_var_contact_extra,
						$mail_tpl_var_billing,
						$mail_tpl_var_billing_extra,
						$mail_tpl_var_shipping,
						$mail_tpl_var_shipping_extra
						);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_profiles_create_profile', $mail_tpl_var, $tpl_user_data, $tpl_general_settngs, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
