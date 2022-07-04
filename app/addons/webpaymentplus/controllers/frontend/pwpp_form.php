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

// $Id: pwpp_form.php by tommy from cs-cart.jp 2014

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if( $mode == 'view' ){

	// 注文に関する情報を取得
    $cart = & $_SESSION['cart'];
	$order_info = $cart;
	$order_info['products'] = $cart['products'];
	$order_info = fn_array_merge($order_info, fn_check_table_fields($cart['user_data'], 'orders'));

	// ウェブペイメントプラスの設定情報を取得
	$pwpp_payment_id = $order_info['payment_id'];
	$pwpp_processor_data = fn_get_processor_data($pwpp_payment_id);
	$pwpp_params = $pwpp_processor_data['processor_params'];

	// アカウント番号
	$pwpp_business = $pwpp_params['account'];

	// 通貨記号
	$pwpp_currency_code = $pwpp_params['currency'];

	// 接続先URL
	if( $pwpp_params['mode'] == 'test' ){
		$pwpp_host = "https://securepayments.sandbox.paypal.com/acquiringweb";
	}else{
		$pwpp_host = "https://securepayments.paypal.com/acquiringweb";
	}

	// 戻りURL
	$pwpp_return = fn_url('pwpp_return.process', AREA, 'current');

	// ページロード中に表示するメッセージ
	$pwpp_desc = __('pwpp_desc');

	// 請求先氏名
	if(CART_LANGUAGE == 'ja'){
		$pwpp_billing_firstname = $order_info['b_lastname'];
		$pwpp_billing_lastname = $order_info['b_firstname'];
	}else{
		$pwpp_billing_firstname = $order_info['b_firstname'];
		$pwpp_billing_lastname = $order_info['b_lastname'];
	}

	// 請求先郵便番号
	$pwpp_billing_zip = $order_info['b_zipcode'];

	// 請求先国名
	$pwpp_billing_country = $order_info['b_country'];

	// 請求先都道府県
	// US states
	if ($order_info['b_country'] == 'US') {
		$pwpp_billing_state = $order_info['b_state'];
	// all other states
	} else {
		$pwpp_billing_state = fn_get_state_name($order_info['b_state'], $order_info['b_country']);
	}

	// 請求先市区町村名
	$pwpp_billing_city = $order_info['b_city'];

	// 請求先住所
	$pwpp_billing_address1 = $order_info['b_address'];

	// 注文合計金額
	$pwpp_billing_subtotal = round($order_info['total']);

    // 利用言語に応じてインラインフレーム内に表示する言語を切り替え
    if(CART_LANGUAGE == 'ja'){
        $pwpp_lc = 'JP';
    }else{
        $pwpp_lc = 'US';
    }

echo <<<EOT
<body onLoad="pwpp_hide_desc();">
<div id="pwpp_desc" style="text-align: center">{$pwpp_desc}</div>
<iframe id="hss_iframe" name="hss_iframe" width="570px" height="370px" scrolling="no"></iframe>
<form style="display:none" target="hss_iframe" name="form_iframe" method="post" action="{$pwpp_host}" target="_parent">
<input type="hidden" name="lc" value="{$pwpp_lc}">
<input type="hidden" name="cmd" value="_hosted-payment" />
<input type="hidden" name="billing_first_name" value="{$pwpp_billing_firstname}">
<input type="hidden" name="billing_last_name" value="{$pwpp_billing_lastname}">
<input type="hidden" name="billing_zip" value="{$pwpp_billing_zip}">
<input type="hidden" name="billing_country" value="{$pwpp_billing_country}">
<input type="hidden" name="billing_state" value="{$pwpp_billing_state}">
<input type="hidden" name="billing_city" value="{$pwpp_billing_city}">
<input type="hidden" name="billing_address1" value="{$pwpp_billing_address1}">
<input type="hidden" name="subtotal" value="{$pwpp_billing_subtotal}">
<input type="hidden" name="business" value="{$pwpp_business}" />
<input type="hidden" name="template" value="templateD" />
<input type="hidden" name="currency_code" value="{$pwpp_currency_code}" />
<input type="hidden" name="return" value="{$pwpp_return}">
<input type="hidden" name="showHostedThankyouPage" value="false">
</form>
<script type="text/javascript">
document.form_iframe.submit();
</script>
<script type="text/javascript">
function pwpp_hide_desc(){
  document.getElementById('pwpp_desc').style.display = 'none';
}
</script>
</body>
EOT;

	exit();
}
