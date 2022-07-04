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

// $Id: shipments_shipment_products.php by tommy from cs-cart.jp 2015
// 商品の発送通知メールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////

// メールテンプレートの編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// 注文情報
	$tpl_order_info = $tpl_base_data['order_info']->value;

	// 発送情報
	$tpl_shipment = $tpl_base_data['shipment']->value;
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーが使用中の言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mtpl_lang_code = $tpl_order_info['lang_code'];
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
		'LASTNAME' => 
				array('desc' => 'first_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['firstname'] : ''),
		'FIRSTNAME' =>
				array('desc' => 'last_name',
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['lastname'] : ''),
		'ORDER_ID' =>
				array('desc' => 'order_id',
						'value' => empty($_edit_mail_tpl) ? $tpl_order_info['order_id'] : ''),
		'SHIPPING_METHOD' =>
				array('desc' => 'shipping_method',
						'value' => empty($_edit_mail_tpl) ? $tpl_shipment['shipping'] : ''),
		'DATE' =>
				array('desc' => 'shipment_date',
						'value' => empty($_edit_mail_tpl) ? date('Y/m/d H:i:s', $tpl_shipment['timestamp']) : '' ),
		'CARRIER' =>
				array('desc' => 'carrier',
						'value' => empty($_edit_mail_tpl) ? $tpl_shipment['carrier_info']['name'] : ''),
		'TRACK_NO' =>
				array('desc' => 'tracking_number',
						'value' => empty($_edit_mail_tpl) ? $tpl_shipment['tracking_number'] : ''),
		'TRACK_URL' =>
				array('desc' => 'jp_shipment_tracking_url',
						'value' => empty($_edit_mail_tpl) ? empty($tpl_shipment['tracking_number']) ? 'N/A' : $tpl_shipment['carrier_info']['tracking_url'] : ''),
		'P_BLK' =>
				array('desc' => 'products',
						'value' => empty($_edit_mail_tpl) ? fn_mtpl_get_shipped_products($tpl_order_info['products'], $tpl_shipment['products'], $tpl_base_data) : ''),
		'COMMENTS' =>
				array('desc' => 'comments',
						'value' => empty($_edit_mail_tpl) ? $tpl_shipment['comments'] : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_shipments_shipment_products', $mail_tpl_var, $tpl_order_info, $tpl_shipment, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
