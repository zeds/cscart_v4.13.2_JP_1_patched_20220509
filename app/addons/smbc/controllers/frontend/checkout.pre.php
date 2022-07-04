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

// $Id: checkout.pre.php by tommy from cs-cart.jp 2017
// 登録済みカード情報の取得および注文完了ページにコンビニ決済と銀行振込決済の詳細を表示

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//if( $mode == 'checkout' && !empty($auth['user_id']) ){
if( $mode == 'checkout' ){

    // 登録済みカード情報を取得
    if(!empty($auth['user_id'])){
        $registered_card = fn_smbcks_get_registered_card_info($auth['user_id']);
        if (!empty($registered_card)) {
            Registry::get('view')->assign('registered_card', $registered_card);
        }
    }

    // 契約コード、収納企業コードをSmarty変数にセット
    Registry::get('view')->assign('smbc_shop_cd', Registry::get('addons.smbc.shop_cd'));
    Registry::get('view')->assign('smbc_shuno_co_cd', Registry::get('addons.smbc.syuno_co_cd'));

    // 契約コード、収納企業コード、API認証キーをSmarty変数にセット (継続課金)
    Registry::get('view')->assign('smbc_rb_shop_cd', Registry::get('addons.smbc.rb_shop_cd'));
    Registry::get('view')->assign('smbc_rb_shuno_co_cd', Registry::get('addons.smbc.rb_syuno_co_cd'));

    $payment_methods = fn_get_payments(array('usergroup_ids' => $auth['usergroup_ids']));

    foreach ($payment_methods as $payment_method) {
        if($payment_method['template'] == 'views/orders/components/payments/smbc_rbtkn.tpl') {
            $rb_api_key = unserialize($payment_method['processor_params'])['rb_api_key'];
            Registry::get('view')->assign('smbc_rb_api_key', $rb_api_key);
        }
    }

// 注文完了ページにコンビニ決済と銀行振込決済の詳細を表示
}elseif( $mode == 'complete' && !empty($_REQUEST['order_id']) ){

	// 注文情報から支払方法IDとコメントを取得
	$smbc_order = db_get_row("SELECT payment_id, notes FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);
	// 支払方法IDを取得
	$smbc_processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $smbc_order['payment_id']);

	// 支払方法の詳細を抽出するデリミタを初期化
	$delimiter_phrase = false;

	// 支払方法に応じてコメント欄から支払方法の詳細を抽出
	switch( $smbc_processor_id ){
		// コンビニ払い
		case '9041':
			// 支払方法の詳細を抽出するデリミタをセット
			$delimiter_phrase = __('jp_smbc_cvs_info');
			break;
		// 銀行振込
		case '9042':
			// 支払方法の詳細を抽出するデリミタをセット
			$delimiter_phrase = __('jp_smbc_bnk_info');
			break;
		default:
			// do nothing
	}

	// 支払方法の詳細を抽出するデリミタが存在する場合
	if( $delimiter_phrase ){
		// コメント欄から支払方法の詳細を抽出
		$smbc_payment_info = mb_strstr($smbc_order['notes'], $delimiter_phrase, false, 'UTF-8');
        $smbc_payment_info = nl2br($smbc_payment_info);
        $smbc_payment_info = strip_tags($smbc_payment_info, '<br>');
		Registry::get('view')->assign('smbc_payment_info', $smbc_payment_info);
	}
}
