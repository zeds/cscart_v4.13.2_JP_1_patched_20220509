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

// $Id: orders_track.php by tommy from cs-cart.jp 2015
// ログインしていない状態で注文検索を実施したときに送信されるメールで使用可能なテンプレート変数

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////

// メールテンプレートの編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// CS-Cartの基本設定データ
	$tpl_config = Registry::get('config');

	// 検索する注文のID
	$tpl_order_id = $tpl_base_data['order_id']->value;

	// 注文検索結果
	$blk_order_track = '';

    $tpl_config_location = "";

    if( Registry::get('settings.Security.secure_storefront') != 'none' ){
        $tpl_config_location = $tpl_config['https_location'];
    }else{
        $tpl_config_location = $tpl_config['http_location'];
    }

    $tpl_config_location .= '/' . $tpl_config['customer_index'];

	if (!empty($tpl_config)) {
		if ($tpl_order_id) {
			$blk_order_track .= str_replace('[order]', $tpl_order_id, __('text_track_view_order')) . "\n";
			$blk_order_track .= $tpl_config_location . "?dispatch=orders.track&ekey=" . $tpl_base_data['access_key']->value . "&o_id=" . $tpl_order_id;
			$blk_order_track .= "\n\n";
		}

		$blk_order_track .= __('text_track_view_all_orders') . "\n";
		$blk_order_track .= $tpl_config_location . "?dispatch=orders.track&ekey=" . $tpl_base_data['access_key']->value;
	}
}else{
	$blk_order_track = '';
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードとユーザーが使用中の言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ) {
	$mtpl_lang_code = CART_LANGUAGE;
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
		'LINK_BLK' => 
				array('desc' => 'mtpl_order_track_link', 
						'value' => $blk_order_track),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_orders_track', $mail_tpl_var, $tpl_config, $blk_order_track, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
