<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: addons_kroneko_webcollect_cvs2_notification.php by tommy from cs-cart.jp 2016
// コンビニ支払い（ファミリーマート）のお支払い情報通知メールで使用可能なテンプレート変数

use Tygh\Registry;

/////////////////////////////////////////////////////////////////////////////
// データ取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート編集ページの場合
if( $_edit_mail_tpl ){

	// 注文情報
	$tpl_order_info = array();

	// 注文が実行された際のお客様選択言語コード
	$mtpl_lang_code = DESCR_SL;

}else{
	// 注文情報
	$tpl_order_info = $tpl_base_data['order_info']->value;

	// コンビニ払いの支払い情報
	$tpl_cvs_payment_info = $tpl_base_data['cvs_payment_info']->value;

	// 注文が実行された際のお客様選択言語コード
	$mtpl_lang_code = $tpl_order_info['lang_code'];
}
/////////////////////////////////////////////////////////////////////////////
// データ取得 EOF
/////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 BOF
/////////////////////////////////////////////////////////////////////////////
// メールテンプレートコードと注文が実行された際のお客様選択言語コードでメールテンプレートを抽出
if( !empty($tpl_code) ){
	$mail_template = fn_mtpl_get_email_contents($tpl_code, $mtpl_lang_code, $tpl_order_info['company_id'], $tpl_order_info['order_id']);
}
/////////////////////////////////////////////////////////////////////////////
// メールテンプレート取得 EOF
/////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 BOF
/////////////////////////////////////////////////////////////////////////////

// 全般
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
		'CVS_NAME' =>
			array('desc' => 'jp_kuroneko_webcollect_cvs_name',
				'value' => empty($_edit_mail_tpl) ? $tpl_cvs_payment_info['cvs_name'] : ''),
		'COMPANY_CODE' =>
			array('desc' => 'jp_kuroneko_webcollect_cvs_company_code',
				'value' => empty($_edit_mail_tpl) ? $tpl_cvs_payment_info['companyCode'] : ''),
		'ORDER_NO_F' =>
			array('desc' => 'jp_kuroneko_webcollect_cvs_order_no_f',
				'value' => empty($_edit_mail_tpl) ? $tpl_cvs_payment_info['orderNoF'] : ''),
		'EXPIRY_DATE' =>
			array('desc' => 'jp_kuroneko_webcollect_cvs_expired_date',
				'value' => empty($_edit_mail_tpl) ? $tpl_cvs_payment_info['expiredDate'] : ''),
	);

if( empty($_edit_mail_tpl) ) {
	fn_set_hook('mail_tpl_var_addons_kuroneko_webcollect_cvs2_notification', $mail_tpl_var, $tpl_order_info, $mail_template);
}
/////////////////////////////////////////////////////////////////////////////
// 利用可能なテンプレート変数を定義 EOF
/////////////////////////////////////////////////////////////////////////////
