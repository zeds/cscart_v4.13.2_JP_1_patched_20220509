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

// $Id: orders_order_notification.php by tommy from cs-cart.jp 2016
// 注文確認メールで使用可能なテンプレート変数

use Tygh\Registry;
use Tygh\Tools\SecurityHelper;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページの場合
if( $_edit_mail_tpl ){

	// 注文情報
	$tpl_order_info = array();

    // サイトIDを取得
    $mkm_company_id = Registry::get('runtime.company_id');

	// 注文が実行された際のお客様選択言語コード
	$mtpl_lang_code = DESCR_SL;

	// 登録済注文ステータス
	$order_statuses = fn_get_statuses(STATUSES_ORDER, array(),true, false, $mtpl_lang_code);

	// 当該注文のステータスに関する情報
	$tpl_order_status_info = array();
	$tpl_order_status = '';

	// 当該注文の配送に関する情報
	$tpl_shipping_info = '';

	// 運送会社、追跡番号、配達状況確認URL
    $shipment_carrier = __("unknown");
    $shipment_tracking_number = __("unknown");
    $shipment_tracking_url = __("unknown");

	// ユーザーフィールドに関する情報
	$invoice_profile_fields = fn_mtpl_get_invoice_profile_fields($tpl_order_info, $mtpl_lang_code);

	// 各注文ステータスに登録したEメールの冒頭文
	$email_header = '';

}else{

    // 注文情報
	$tpl_order_info = $tpl_base_data['order_info']->value;

	// 注文が実行された際のお客様選択言語コード
	$mtpl_lang_code = $tpl_order_info['lang_code'];

	// 登録済注文ステータス
	$order_statuses = fn_get_statuses(STATUSES_ORDER, array(),true, false, $mtpl_lang_code);

	// 当該注文のステータスに関する情報
	$tpl_order_status_info = $tpl_base_data['order_status']->value;

	// 当該注文のステータス名
	$tpl_order_status = $order_statuses[$tpl_order_status_info['status']]['description'];

    // 当該注文確認用URL
    $tpl_order_url = fn_url('orders.details&order_id=' . $tpl_order_info['order_id'], 'C', 'http');

	// 当該注文の配送に関する情報
	$tpl_shipping_info = fn_mtpl_get_shipping_info($tpl_order_info);

    // 運送会社、追跡番号、配達状況確認URL BOF
    $shipment_carrier = __("unknown");
    $shipment_tracking_number = __("unknown");
    $shipment_tracking_url = __("unknown");

    list($shipments) = fn_get_shipments_info(array('order_id' => $tpl_order_info['order_id'], 'advanced_info' => true));

    if( !empty($shipments) ){
        $shipment_info = array_values($shipments)[0];
        if( !empty($shipment_info['carrier_info']['name']) ) $shipment_carrier = $shipment_info['carrier_info']['name'];
        if( !empty($shipment_info['tracking_number']) ) $shipment_tracking_number = $shipment_info['tracking_number'];
        if( !empty($shipment_info['carrier_info']['tracking_url']) ) $shipment_tracking_url = $shipment_info['carrier_info']['tracking_url'];
    }
    // 運送会社、追跡番号、配達状況確認URL EOF

	// ユーザーフィールドに関する情報
	$invoice_profile_fields = fn_mtpl_get_invoice_profile_fields($tpl_order_info, $mtpl_lang_code);

	// 各注文ステータスに登録したEメールの冒頭文
	$email_header = mb_ereg_replace("/br/", "\n", SecurityHelper::escapeHtml($tpl_base_data['order_status']->value['email_header'], true));
	$email_header = mb_ereg_replace("&nbsp;", " ", $email_header);

    // 支払い方法名を言語に応じて取得
    $payment_method = db_get_field("SELECT payment FROM ?:payment_descriptions WHERE payment_id = ?i AND lang_code = ?s", $tpl_order_info['payment_method']['payment_id'], $mtpl_lang_code);

}


/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと注文が実行された際のお客様選択言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ){

    // 注文ステータスを取得
    $order_status_code = strtolower($tpl_order_info['status']);

    // 注文ステータスに応じたメールテンプレートの存在チェック
    $is_tpl_exists = db_get_field("SELECT tpl_code FROM ?:jp_mtpl WHERE tpl_code = ?s AND status = 'A'", $tpl_code . '_' . $order_status_code);

    // 注文ステータスに応じたメールテンプレートが存在する場合
    if( $is_tpl_exists ){
        // 注文ステータスに応じたメールテンプレートを抽出
        $mail_template = fn_mtpl_get_email_contents($tpl_code . '_' . $order_status_code, $mtpl_lang_code, '', $tpl_order_info['order_id']);

    // 注文ステータスに応じたメールテンプレートが存在しない場合
    }else{
        // デフォルトの注文ステータス用メールテンプレートを抽出
        $mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code, '', $tpl_order_info['order_id']);
    }
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
                'value' => $email_header),
        'ORDER_ID' =>
            array('desc' => 'order_id',
                'value' => empty($_edit_mail_tpl) ? $tpl_order_info['order_id'] : ''),
        'STATUS' =>
            array('desc' => 'order_status',
                'value' => $tpl_order_status),
        'DATE' =>
            array('desc' => 'date',
                'value' => empty($_edit_mail_tpl) ? date('Y/m/d H:i', $tpl_order_info['timestamp']) : ''),
        'PAYMENT' =>
            array('desc' => 'payment_method',
                'value' => empty($_edit_mail_tpl) ? $payment_method : ''),
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

// 注文商品や金額情報など
$mail_tpl_var_details =
    array(
        'P_BLK' =>
            array('desc' => 'products',
                'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_ordered_products($tpl_order_info['products'], $tpl_base_data) : ''),
        'O_SUBTOTAL' =>
            array('desc' => 'subtotal',
                'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_display_price($tpl_order_info['display_subtotal'], $tpl_base_data) : '' ),
        'O_MISC' =>
            array('desc' => 'mtpl_o_misc',
                'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_ot_misc($tpl_order_info, $tpl_base_data) : '' ),
        'O_TOTAL' =>
            array('desc' => 'total_cost',
                'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_display_price($tpl_order_info['total'], $tpl_base_data) : '' ),
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
    fn_set_hook('mail_tpl_var_orders_order_notification', $mail_tpl_var, $tpl_order_info, $order_statuses, $tpl_order_status_info, $tpl_order_status, $tpl_shipping_info, $invoice_profile_fields, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
