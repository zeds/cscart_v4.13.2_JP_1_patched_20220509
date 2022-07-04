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

// $Id: addons_suppliers_notification.php by tommy from cs-cart.jp 2016
// サプライヤー取扱商品を受注した際にサプライヤーに送信されるメールで使用可能なテンプレート変数

use Tygh\Tools\SecurityHelper;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// 注文情報
	$tpl_order_info = $tpl_base_data['order_info']->value;

	// 注文が実行された際のお客様選択言語コード
	if ($tpl_order_info['lang_code']) {
		$mtpl_lang_code = $tpl_order_info['lang_code'];
	} else {
		$mtpl_lang_code = DESCR_SL;
	}

	// 登録済注文ステータス
	$order_statuses = fn_get_statuses(STATUSES_ORDER, array(), true, false, $mtpl_lang_code);

	// 当該注文のステータスに関する情報
	$tpl_order_status_info = $tpl_base_data['order_status']->value;

	// 当該注文のステータス名
    $tpl_order_status = $order_statuses[$tpl_order_status_info['status']]['description'];

	// 当該注文の配送に関する情報
	$tpl_shipping_info = fn_mtpl_get_shipping_info($tpl_order_info, $tpl_base_data['supplier_id']->value);

	// ユーザーフィールドに関する情報
	$invoice_profile_fields = fn_mtpl_get_invoice_profile_fields($tpl_order_info, $mtpl_lang_code);

}else{
	$tpl_order_info = array();

	// ユーザーフィールドに関する情報
	$invoice_profile_fields = fn_mtpl_get_invoice_profile_fields($tpl_order_info, DESCR_SL);
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと注文が実行された際のお客様選択言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
}
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////

// 全般
$mail_tpl_var_general = 
	array(
		'USER_ID' =>
				array('desc' => 'user_id',
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['user_id'] : ''),
		'EMAIL' =>
				array('desc' => 'email', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['email'] : ''),
		'LASTNAME' => 
				array('desc' => 'first_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['firstname'] : ''),
		'FIRSTNAME' => 
				array('desc' => 'last_name', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['lastname'] : ''),
		'SUBJECT' => 
				array('desc' => 'email_subject', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_status_info['email_subj'] : ''),
		'HEADER' => 
				array('desc' => 'email_header', 
						'value' => empty($_edit_mail_tpl) ? mb_ereg_replace("/br/", "\n", SecurityHelper::escapeHtml($tpl_base_data['order_status']->value['email_header'], true)) : ''),
		'ORDER_ID' => 
				array('desc' => 'order_id', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['order_id'] : ''),
		'STATUS' => 
				array('desc' => 'order_status', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_status : ''),
		'DATE' => 
				array('desc' => 'date', 
						'value' => empty($_edit_mail_tpl) ? date('Y/m/d H:i', $tpl_order_info['timestamp']) : ''),
		'PAYMENT' => 
				array('desc' => 'payment_method', 
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['payment_method']['payment'] : ''),
		'SHIPPING' => 
				array('desc' => 'shipping_method', 
						'value' => empty($_edit_mail_tpl) ? $tpl_shipping_info['shipping_method'] : ''),
        'DELIVERY_DATE' =>
                array('desc' => 'jp_delivery_date',
                    'value' => empty($_edit_mail_tpl) ? $tpl_shipping_info['delivery_date'] : ''),
        'DELIVERY_TIMING' =>
                array('desc' => 'jp_shipping_delivery_time',
                    'value' => empty($_edit_mail_tpl) ? $tpl_shipping_info['delivery_timing'] : ''),
		'TRACK_NO' => 
				array('desc' => 'tracking_number', 
						'value' => empty($_edit_mail_tpl) ? $tpl_shipping_info['tracking_number'] : ''),
		);

// 連絡先情報（購入手続きにおいて表示される連絡先情報のみテンプレート変数として利用可能）
$mail_tpl_var_contact = array();

// 姓
if( array_key_exists('c_firstname', $invoice_profile_fields) ){
	 $mail_tpl_var_contact['C_LASTNAME'] = array('desc' => 'mtpl_c_first_name', 'value' => $invoice_profile_fields['c_firstname']);
}
// 名
if( array_key_exists('c_lastname', $invoice_profile_fields) ){
	 $mail_tpl_var_contact['C_FIRSTNAME'] = array('desc' => 'mtpl_c_last_name', 'value' => $invoice_profile_fields['c_lastname']);
}
// 会社名
if( array_key_exists('c_company', $invoice_profile_fields) ){
	 $mail_tpl_var_contact['C_COMPANY'] = array('desc' => 'mtpl_c_company', 'value' => $invoice_profile_fields['c_company']);
}
// 電話
if( array_key_exists('c_phone', $invoice_profile_fields) ){
	 $mail_tpl_var_contact['C_PHONE'] = array('desc' => 'mtpl_c_phone', 'value' => $invoice_profile_fields['c_phone']);
}
// FAX
if( array_key_exists('c_fax', $invoice_profile_fields) ){
	 $mail_tpl_var_contact['C_FAX'] = array('desc' => 'fax', 'value' => $invoice_profile_fields['c_fax']);
}
// URL
if( array_key_exists('c_url', $invoice_profile_fields) ){
	 $mail_tpl_var_contact['C_URL'] = array('desc' => 'url', 'value' => $invoice_profile_fields['c_url']);
}

// ユーザーが追加した連絡先情報
$mail_tpl_var_contact_extra = array();
if( !empty($invoice_profile_fields['c_extra_fields']) ){
	foreach($invoice_profile_fields['c_extra_fields'] as $key => $val){
		$mail_tpl_var_contact_extra['C' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_c_info') . $val['field_desc'], 'value' => $val['field_val']);
	}
}


// 請求先情報（購入手続きにおいて表示される請求先情報のみテンプレート変数として利用可能）
$mail_tpl_var_billing = array();

// 姓
if( array_key_exists('b_firstname', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_LASTNAME'] = array('desc' => 'mtpl_b_first_name', 'value' => $invoice_profile_fields['b_firstname']);
}
// 名
if( array_key_exists('b_lastname', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_FIRSTNAME'] = array('desc' => 'mtpl_b_last_name', 'value' => $invoice_profile_fields['b_lastname']);
}
// 電話
if( array_key_exists('b_phone', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_PHONE'] = array('desc' => 'mtpl_b_phone', 'value' => $invoice_profile_fields['b_phone']);
}
// 国名
if( array_key_exists('b_country', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_COUNTRY'] = array('desc' => 'mtpl_b_country', 'value' => $invoice_profile_fields['b_country']);
}
// 郵便番号
if( array_key_exists('b_zipcode', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_ZIPCODE'] = array('desc' => 'mtpl_b_zipcodes', 'value' => $invoice_profile_fields['b_zipcode']);
}
// 都道府県
if( array_key_exists('b_state', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_STATE'] = array('desc' => 'mtpl_b_state', 'value' => $invoice_profile_fields['b_state']);
}
// 市区町村
if( array_key_exists('b_city', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_CITY'] = array('desc' => 'mtpl_b_city', 'value' => $invoice_profile_fields['b_city']);
}
// 番地
if( array_key_exists('b_address', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_ADDRESS'] = array('desc' => 'mtpl_b_address', 'value' => $invoice_profile_fields['b_address']);
}
// ビル・建物名
if( array_key_exists('b_address_2', $invoice_profile_fields) ){
	 $mail_tpl_var_billing['B_ADDRESS2'] = array('desc' => 'mtpl_b_address_2', 'value' => $invoice_profile_fields['b_address_2']);
}

// ユーザーが追加した請求先情報
$mail_tpl_var_billing_extra = array();
if($invoice_profile_fields['b_extra_fields']){
	foreach($invoice_profile_fields['b_extra_fields'] as $key => $val){
		$mail_tpl_var_billing_extra['B' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_b_info') . $val['field_desc'], 'value' => $val['field_val']);
	}
}

// 配送先情報（購入手続きにおいて表示される配送先情報のみテンプレート変数として利用可能）
$mail_tpl_var_shipping = array();

// 姓
if( array_key_exists('s_firstname', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_LASTNAME'] = array('desc' => 'mtpl_s_first_name', 'value' => $invoice_profile_fields['s_firstname']);
}
// 名
if( array_key_exists('s_lastname', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_FIRSTNAME'] = array('desc' => 'mtpl_s_last_name', 'value' => $invoice_profile_fields['s_lastname']);
}
// 電話
if( array_key_exists('s_phone', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_PHONE'] = array('desc' => 'mtpl_s_phone', 'value' => $invoice_profile_fields['s_phone']);
}
// 国名
if( array_key_exists('s_country', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_COUNTRY'] = array('desc' => 'mtpl_s_country', 'value' => $invoice_profile_fields['s_country']);
}
// 郵便番号
if( array_key_exists('s_zipcode', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_ZIPCODE'] = array('desc' => 'mtpl_s_zipcodes', 'value' => $invoice_profile_fields['s_zipcode']);
}
// 都道府県
if( array_key_exists('s_state', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_STATE'] = array('desc' => 'mtpl_s_state', 'value' => $invoice_profile_fields['s_state']);
}
// 市区町村
if( array_key_exists('s_city', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_CITY'] = array('desc' => 'mtpl_s_city', 'value' => $invoice_profile_fields['s_city']);
}
// 番地
if( array_key_exists('s_address', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_ADDRESS'] = array('desc' => 'mtpl_s_address', 'value' => $invoice_profile_fields['s_address']);
}
// ビル・建物名
if( array_key_exists('s_address_2', $invoice_profile_fields) ){
	 $mail_tpl_var_shipping['S_ADDRESS2'] = array('desc' => 'mtpl_s_address_2', 'value' => $invoice_profile_fields['s_address_2']);
}

// ユーザーが追加した配送先情報
$mail_tpl_var_shipping_extra = array();
if($invoice_profile_fields['s_extra_fields']){
	foreach($invoice_profile_fields['s_extra_fields'] as $key => $val){
		$mail_tpl_var_shipping_extra['S' . $key . '_' . $val['field_desc']] = array('desc' => __('mtpl_for_s_info') . $val['field_desc'], 'value' => $val['field_val']);
	}
}

// $tpl_order_info には商品の情報が含まれないため注文情報を再取得
if( empty($_edit_mail_tpl) ){
	$_buf_order_info = fn_get_order_info($tpl_order_info['order_id']);
}

// 注文商品や金額情報など
$mail_tpl_var_details =
	array(
		'P_BLK' =>
				array('desc' => 'products',
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_ordered_products($_buf_order_info['products'], $tpl_base_data, 'SUPPLIERS', $tpl_base_data['supplier_id']->value) : ''),
		'O_SUBTOTAL' =>
				array('desc' => 'subtotal',
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_display_price($tpl_order_info['display_subtotal'], $tpl_base_data) : ''),
		'O_MISC' =>
				array('desc' => 'mtpl_o_misc',
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_ot_misc($tpl_order_info, $tpl_base_data) : ''),
		'O_TOTAL' =>
				array('desc' => 'total_cost',
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_display_price($tpl_order_info['total'], $tpl_base_data) : ''),
		'COMMENT' =>
				array('desc' => 'notes',
						// v2.2.1-jp-1よりワードラップを解除 - Special Thanks to mmochi
						'value' => empty($_edit_mail_tpl) ? ($tpl_order_info['notes']) ? SecurityHelper::escapeHtml($tpl_order_info['notes'], true) : '' : ''),
		);


// テンプレート変数をマージ
$mail_tpl_var = array_merge(
						$mail_tpl_var_general,
						$mail_tpl_var_contact,
						$mail_tpl_var_contact_extra,
						$mail_tpl_var_billing,
						$mail_tpl_var_billing_extra,
						$mail_tpl_var_shipping,
						$mail_tpl_var_shipping_extra,
						$mail_tpl_var_details
						);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_orders_supplier_notification', $mail_tpl_var, $tpl_order_info, $order_statuses, $tpl_order_status_info, $tpl_order_status, $tpl_shipping_info, $invoice_profile_fields, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
