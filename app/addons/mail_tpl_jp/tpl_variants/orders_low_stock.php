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

// $Id: orders_low_stock.php by tommy from cs-cart.jp 2017
// 商品の在庫数が設定値を下回った場合に送信されるメールで使用可能なテンプレート変数

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページの場合
if( $_edit_mail_tpl ) {
	$product_option = '';

// メールテンプレート編集ページ以外の場合
}else{
	// 商品オプションを取得
	if($tpl_base_data['product_options']->value){
		$product_options = array();

		foreach($tpl_base_data['product_options']->value as $v){
			$product_options[] = $v['option_name'] . ' : ' . $v['variant_name'];
		}

        $product_option = __('product_options') . ' :'."\n" . implode("\n", $product_options);
	}else{
		$product_option = '';
	}
}

/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
if( !empty($tpl_code) ){
	// メールテンプレートコードと管理者用デフォルト言語コードでメールテンプレートを抽出
	$mtpl_lang_code = Registry::get('settings.Appearance.backend_default_language');
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
		'P_NAME' => 
				array('desc' => 'product',
						'value' => !empty($tpl_code) ? fn_get_product_name($tpl_base_data['product_id']->value, $mtpl_lang_code) : ''),
		'P_ID' => 
				array('desc' => 'id', 
						'value' => !empty($tpl_code) ? $tpl_base_data['product_id']->value : ''),
		'P_CODE' => 
				array('desc' => 'product_code', 
						'value' => !empty($tpl_code) ? $tpl_base_data['product_code']->value : ''),
		'P_QTY' => 
				array('desc' => 'qty', 
						'value' => !empty($tpl_code) ? $tpl_base_data['new_qty']->value : ''),
		'P_OPTIONS' => 
				array('desc' => 'product_options', 
						'value' => $product_option),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_orders_low_stock', $mail_tpl_var, $tpl_base_data['product_options']->value, $product_option, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
