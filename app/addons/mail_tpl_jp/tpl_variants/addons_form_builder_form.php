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

// $Id: addons_form_builder_form_body.php by tommy from cs-cart.jp 2015
// フォームビルダーから送信されるメールで使用可能なテンプレート変数

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// 問い合わせ内容を格納する変数を初期化
$tpl_form_block = '';

// メールテンプレート編集ページ以外の場合
if( empty($_edit_mail_tpl) ) {
	// フォームビルダーで作成したエレメント
	$tpl_elements_data = $tpl_base_data['elements']->value;

	// フォームの入力内容
	$tpl_form_values = $tpl_base_data['form_values']->value;
}


// フォームビルダーで作成したエレメントが存在する場合
if( !empty($tpl_elements_data) ){

	foreach ($tpl_elements_data as $k => $v){

		// フォームの値が存在する場合
		if(!empty($tpl_form_values[$k])){
			// 項目名
			$tpl_form_block .= $v['description'];

			// フォームの値
			$tpl_form_block .= ' ： '; 

			// エレメントがテキストエリアの場合、項目名の後に改行を挿入
			if( $v['element_type'] == FORM_TEXTAREA ){
				$tpl_form_block .= "\n";
			}

			$tpl_form_block .= fn_mtpl_get_form_value($tpl_form_values[$k], $v['element_type'])  . "\n\n";

		// フォームの値が存在しない場合、見出し行として処理
		}else{

			$tpl_form_block .= "\n" . '----------------------------------------' . "\n";

			// 見出し名
			$tpl_form_block .= $v['description'];

			$tpl_form_block .= "\n" . '----------------------------------------' . "\n";
		}
	}
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
if( !empty($tpl_code) ) {
	// メールテンプレートコードと管理者用パネルのデフォルト言語コードでメールテンプレートを抽出
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
		'F_TITLE' => 
				array('desc' => 'mtpl_form_title', 
						'value' => empty($_edit_mail_tpl) ? $tpl_base_data['form_title']->value : ''),
		'F_BLK' => 
				array('desc' => 'mtpl_form_contents',
						'value' => empty($_edit_mail_tpl) ? $tpl_form_block : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_addons_form_builder_form_body', $mail_tpl_var, $tpl_form_block, $tpl_elements_data, $tpl_form_values, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
