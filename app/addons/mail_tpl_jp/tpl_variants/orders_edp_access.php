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

// $Id: orders_edp_access.php by tommy from cs-cart.jp 2017
// ダウンロード商品がダウンロード可能なった場合に送信されるメールで使用可能なテンプレート変数

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////

// メールテンプレートの編集ページ以外の場合
if( empty($_edit_mail_tpl) ){
	// 注文情報
	$tpl_order_info = $tpl_base_data['order_info']->value;
	// ダウンロード商品情報
	$tpl_edp = $tpl_base_data['edp_data']->value;
	// CS-Cartの基本設定データ
	$tpl_config = Registry::get('config');
}

// ダウンロード用リンク
$blk_edp = '';

if( !empty($tpl_order_info) ){

	$cnt = 0;

    $tpl_config_location = "";

    if( Registry::get('settings.Security.secure_storefront') != 'none' ){
        $tpl_config_location = $tpl_config['https_location'];
    }else{
        $tpl_config_location = $tpl_config['http_location'];
    }

    $tpl_config_location .= '/' . $tpl_config['customer_index'];

	foreach($tpl_order_info['products'] as $oi){
		$cnt++;

		if( $oi['extra']['is_edp'] == 'Y' && $tpl_edp[$oi['product_id']]['files'] ){
            // ダウンロードファイルの数をカウント
            $edp_cnt = count($tpl_edp[$oi['product_id']]['files']);

			$first_file = reset($tpl_edp[$oi['product_id']]['files']);

			$blk_edp .= $oi['product'] . "\n";
			$blk_edp .= $tpl_config_location . "?dispatch=orders.downloads&product_id=" . $oi['product_id'] . "&ekey=" . $first_file['ekey'];
			$blk_edp .= "\n\n";

            // ダウンロードファイル数が2つ以上存在する場合、改行を追加
            if($edp_cnt > 1) $blk_edp .= "\n";

			foreach( $tpl_edp[$oi['product_id']]['files'] as $file_id => $file ){
				$blk_edp .= $file['file_name'] . " (" . number_format($file['file_size'],0,'.',',') . __('bytes') . ")\n";
				$blk_edp .= $tpl_config_location . "?dispatch=orders.get_file&file_id=" . $file['file_id'] . "&product_id=" . $oi['product_id'] . "&ekey=" . $file['ekey'];
				// v2.2.1-jp-1より改行を追加 - Special Thanks to mmochi 
				$blk_edp .= "\n";

                // ダウンロードファイル数が2つ以上存在する場合、改行を追加
                if($edp_cnt > 1) $blk_edp .= "\n";
			}

			if( $cnt < count($tpl_order_info['products']) ){
				$blk_edp .= "\n\n\n";
			}
		}
	}
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと注文が実行された際のお客様選択言語コードでメールテンプレートを抽出
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
		'LINK_BLK' => 
				array('desc' => 'mtpl_edp_link', 
						'value' => $blk_edp),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_orders_edp_access', $mail_tpl_var, $tpl_order_info, $tpl_edp, $tpl_config, $blk_edp, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
