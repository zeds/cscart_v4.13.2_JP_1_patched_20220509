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

// $Id: addons_vendor_data_premoderation_notification.php by tommy from cs-cart.jp 2013
// 出品者取扱商品のウェブサイトへの掲載を承認または拒否した際に送信されるメールで使用可能なテンプレート変数


/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// 商品情報
$tpl_products_data = $tpl_base_data['products']->value;
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////
// 商品の掲載ステータス BOF
////////////////////////////////////////////////////////////
$tpl_base_status = $tpl_base_data['status']->value;
$tpl_status = '';

if(!empty($tpl_base_status) ){
	if( $tpl_base_status == 'Y' ){
		$tpl_status = __('approved');
		$tpl_status_code = 'approved';
	}else{
		$tpl_status = __('disapproved');
		$tpl_status_code = 'disapproved';
	}
}
////////////////////////////////////////////////////////////
// 商品の掲載ステータス EOF
////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////
// 掲載ステータスが変更された商品の詳細 BOF
////////////////////////////////////////////////////////////
$tpl_supplier_products_blk = '';

if( !empty($tpl_products_data) ){
	$tpl_supplier_products_blk = __('products_approval_status_' . $tpl_status_code) . "\n\n";

	if( !empty($tpl_products_data) ){
		$cnt = 0;
		foreach( $tpl_products_data as $tpl_product_data ){
			$cnt++;
			$supplier_product_id = $tpl_product_data['product_id'];

			$tpl_supplier_products_blk .= '(' . $cnt . ') ' . $tpl_product_data['product'] . "\n";
			$tpl_supplier_products_blk .= $tpl_product_data['url'];

			if( count($tpl_products_data) > $cnt ){
				$tpl_supplier_products_blk .= "\n\n";
			}
		}
	}

	if( $tpl_base_status == 'Y' ){
		$tpl_supplier_products_blk .= "\n\n" . __('text_shoppers_can_order_products');
	}
}
////////////////////////////////////////////////////////////
// 掲載ステータスが変更された商品の詳細 EOF
////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////
// 商品の掲載ステータス変更理由 BOF
////////////////////////////////////////////////////////////
$tpl_base_reason = $tpl_base_data['reason']->value;
$tpl_reason = '';
if( !empty($tpl_base_reason) ){
	$tpl_reason = __('reason'). ':' . "\n" . $tpl_base_reason;
}
////////////////////////////////////////////////////////////
// 商品の掲載ステータス変更理由 EOF
////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーが使用中の言語コードでメールテンプレートを抽出
$mtpl_lang_code = CART_LANGUAGE;
$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code);
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////
$mail_tpl_var = 
	array(
		'COMPANY_PROD_STATUS' => 
				array('desc' => 'mtpl_products_status', 
						'value' => html_entity_decode($tpl_status, ENT_QUOTES, 'UTF-8') ),
		'COMPANY_P_BLK' => 
				array('desc' => 'mtpl_products_status_changed', 
						'value' => html_entity_decode($tpl_supplier_products_blk, ENT_QUOTES, 'UTF-8') ),
		'COMPANY_REASON' => 
				array('desc' => 'reason', 
						'value' => html_entity_decode($tpl_reason, ENT_QUOTES, 'UTF-8') ),
	);

fn_set_hook('mail_tpl_var_addons_vendor_data_premoderation_notification', $mail_tpl_var, $tpl_products_data, $tpl_base_status, $mail_template);
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
