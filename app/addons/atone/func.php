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

//
// $Id: func.php by sun from cs-cart.jp 2018
//

use Tygh\Http;
use Tygh\Registry;
use Tygh\Mailer;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################

/**
 * アドオンのインストール時の処理
 */
function fn_atone_install()
{
    fn_lcjp_install('atone');
}


function fn_atone_delete_payment_processors()
{
	db_query("DELETE FROM ?:payment_processors WHERE processor_id = 9088");
    db_query("ALTER TABLE ?:users DROP `user_token`");
    db_query("ALTER TABLE ?:orders DROP `transaction_no`, DROP `tr_id`");
}

##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################




##########################################################################################
// START フックを使用した関数
##########################################################################################

/*
//注文確定ボタン
function fn_atone_get_checkout_payment_buttons($cart, $cart_products, $auth, &$checkout_buttons, $checkout_payments, $payment_id)
{
	if(isset($cart['payment_method_data']) && $cart['payment_method_data']['template'] == 'addons/atone/views/orders/components/payments/atone.tpl') {
		$atn_btn = __("atone_pay");
		$checkout_buttons[$payment_id] = <<<HTML
            <a class="ty-btn__big ty-btn__primary ty-btn" id="atone-button">{$atn_btn}</a>
			<input id="place_order" class="hidden ty-btn__big ty-btn__primary cm-checkout-place-order ty-btn" type="submit" name="dispatch[checkout.place_order]">
HTML;

	}
}
*/


//注文削除時
function fn_atone_delete_order($order_id)
{
	      
	$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $order_id);
	
	if(isset($transaction_id)) {
		fn_atn_canceled_base($order_id);
	}

}


//注文ステータス変更時
function fn_atone_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{

	$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $order_info['order_id']);
	if(isset($transaction_id)) {
 	
 		$order_id = $order_info['order_id'];
		$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');				
		$private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');
		$c_private_key = base64_encode($private_key);
		$basic = "Basic ".$c_private_key; 

		if($status_from == 'I'){
				if($status_to != 'N') { 
		   			$result = fn_atn_reference($order_id, $basic, $atone_mode);
		   		
					if(isset($result['refunds'])) {
						foreach ($order_info['products'] as $k => $v) {
        					if (Registry::get('settings.General.inventory_tracking') == 'Y') {
            					if($status_to == 'B' || $status_to == 'D' || $status_to == 'F') {
              			  			
       				 			}else{
       				 				fn_update_product_amount($v['product_id'], $v['amount'], @$v['extra']['product_options'], '+');
       				 			}
       				 		}
       					 }
						fn_set_notification('E', __('error'),__('atone_canceled_text'));
						exit;
					}
				}
		}

		if( $status_to == 'I' ){    
	
			fn_atn_canceled_base($order_id);
		
		}elseif( $status_to == 'C') {
       
			$result = fn_atn_reference($order_id, $basic, $atone_mode);
			if(isset($result['transaction_options'])) {
				$r_options = $result['transaction_options'];
				foreach ($r_options as $r_option) {
					if($r_option == 3) {
						fn_atn_register_c($order_id, $result);
					}
				}
			}
			
			$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $order_id);
      
			if($atone_mode == "N"){
				$target_urls = "https://ct-api.a-to-ne.jp/v1/transactions/".$transaction_id."/settle";
			}else{
				$target_urls = "https://api.atone.be/v1/transactions/".$transaction_id."/settle";
			}
      
			$header = [
				"Authorization:".$basic,
  				"Content-Type: application/json",
  				"X-HTTP-Method-Override: PATCH",
  				"Accept: application/json",
  				"X-NP-Terminal-Id: 4000000400"
			];
 
			$curl = curl_init($target_urls);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $s_data);

			$json_response = curl_exec($curl);

			$response = json_decode($json_response,true);

			if(isset($response['errors'])) {
				$errors = $response['errors'];
				foreach ($errors as $key => $value){
						$messages = $value['messages'];
						foreach ($messages as $val){
							fn_set_notification('E', __('error'),'atone:'.$val);
						}
					if($value['code'] != 'EATN1201') {
						exit;
					}
				}
		
			}elseif($response['authorization_result'] == '1'){
				fn_set_notification('N', __('notice'), __('atone_be_determined'));
			}

			curl_close($curl);
		}
	}
}


//atone決済注文一覧取得
function fn_atone_get_orders($params, $fields, $sortings, &$condition, $join, $group)
{
	$current_controller = Registry::get('runtime.controller');
	$current_mode = Registry::get('runtime.mode');

	if( ($current_controller == 'atone_c') && $current_mode == 'manage' ){
		$condition .= " AND ?:orders.tr_id is NOT NULL";
	}
}


//注文完了時
function fn_atone_finish_payment($order_id, &$pp_response, &$force_notification)
{
	$payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');
	$order_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id);
	
	if( !empty($payment_info) ){

        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        if( empty($payment_id) ) return false;
        $payment_method_data = fn_get_payment_method_data($payment_id);
        if( empty($payment_method_data) ) return false;
        $processor_id = $payment_method_data['processor_id'];
        if( empty($processor_id) ) return false;
        
		switch($processor_id){
		case '9088':
		
				//20180710 初期注文ステータス挙動の不具合修正
				if($order_status != "C") {
					$pp_response["order_status"] = "O";
        		}else{
					$pp_response["order_status"] = "C";
        		}
		    
                if( !is_array($payment_info)) {
                    $info = @unserialize(fn_decrypt_text($payment_info));
                }else{
                    $info = $payment_info;
                }

                unset($info['order_status']);
                unset($info['use_uid']);

                $_data = fn_encrypt_text(serialize($info));

                db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);
                break;
        default:
		}
	}
}

//202007 repay箇所での除外処理追加
function fn_atone_prepare_checkout_payment_methods($cart, $auth, &$payment_groups)
{

	$controller = Registry::get('runtime.controller');
    $mode = Registry::get('runtime.mode');
    $area = AREA;

    if($controller == 'orders' && $mode == 'details' && $area == 'C') {
        foreach ($payment_groups['tab3'] as $k => $v) {
            if ($v['processor_id'] == '9088') {
            	unset($payment_groups['tab3'][$k]);
            }
        }
    }
}


##########################################################################################
// END フックを使用した関数
##########################################################################################



##########################################################################################
// START オリジナル関数
##########################################################################################


//atone:注文編集
function fn_atn_update_order()
{

	$atone_cart = $_SESSION['cart'];
	$orderinfo = fn_get_order_info($atone_cart['order_id']);

	$ap_product = $atone_cart['products'];
	ksort($ap_product);

	$private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');
	$transaction_no = db_get_field('SELECT transaction_no FROM ?:orders WHERE order_id = ?i', $atone_cart['order_id']);
	$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $atone_cart['order_id']);
	       
	$total = "";

	//商品
	foreach ($ap_product as $itemValue) {
		$p_id = $itemValue['product_id'];
		//190111 atone管理画面側に掲載される商品URLについて修正
		$product_url = fn_url("products.view&product_id=$p_id", 'C');

		$item = array(
			'shop_item_id' => $itemValue['product_id'],
			'item_name' => $itemValue['product'],
			'item_price' => $itemValue['price'],
			'item_count' => $itemValue['amount'],
			'item_url' => $product_url,
		);
		ksort($item);
		$items[] = $item;
		$itemprice = $itemValue['price'] * $itemValue['amount'];
              
		$total += $itemprice;
     }
          
	//税金
	if (isset($atone_cart['taxes'])) {
		$tax = $atone_cart['taxes'];
			foreach ($tax as $taxValue) {
			//外税の場合のみ
				if($taxValue['price_includes_tax'] != "Y") {
					$item = array(
						'shop_item_id' =>'tax001',
						'item_name' => $taxValue['description'],
						'item_price' => $taxValue['tax_subtotal'],
						'item_count' => '1',         
					);
				ksort($item);
				$items[] = $item;
				$total += $taxValue['tax_subtotal'];
				}
			}
	}
	ksort($items);

          
	//配送料
	//20180625 サプライヤーアドオン有効時・配送方法に対して価格、重量、商品数に応じた送料を設定している際に生じる不具合を修正
	if (isset($atone_cart['product_groups'])) {
		$p_group = $atone_cart['product_groups'];
		foreach ($p_group as $shippingValue) {
		  foreach ($shippingValue['chosen_shippings'] as $shipping) {
			$item = array(
				'shop_item_id' =>'ship'.$shipping['shipping_id'],
				'item_name' => $shipping['shipping'],
				'item_price' => $shipping['rate'],
				'item_count' => 1,
		    );
			ksort($item);
			$items[] = $item;
            $total += $shipping['rate'];
          }
		}
		
	}
	ksort($items);
	
	//割引額が0以上の時
	if ($atone_cart['subtotal_discount'] > 0) {
		$discount = __('including_discount', null, CART_LANGUAGE);
		$item = array(
				'shop_item_id' =>'discount',
				'item_name' => $discount,
				'item_price' => '-'.$atone_cart['subtotal_discount'],
				'item_count' => 1,
		);
		ksort($item);
		$items[] = $item;
		
		$discount = '-'.$atone_cart['subtotal_discount'];
		
		$total += $discount;
	}
	ksort($items);
	
	//支払手数料が0以上の時
	if ($atone_cart['payment_surcharge'] > 0) {
		$surcharge = __('payment_surcharge', null, CART_LANGUAGE);
		$item = array(
				'shop_item_id' =>'surcharge',
				'item_name' => $surcharge,
				'item_price' => $atone_cart['payment_surcharge'],
				'item_count' => 1,
		);
		ksort($item);
		$items[] = $item;
		
		$total += $atone_cart['payment_surcharge'];
	}
	ksort($items);

	//session内にuser_dataがある時
	if(!empty($atone_cart['user_data'])){
		//購入者
		$customer = array(
			'customer_name' => $atone_cart['user_data']['b_firstname'].$atone_cart['user_data']['b_lastname'], // 購入者氏名
			'customer_family_name' => $atone_cart['user_data']['b_firstname'],
			'customer_given_name' => $atone_cart['user_data']['b_lastname'],
			'zip_code' => $atone_cart['user_data']['b_zipcode'], // 郵便番号
			'address' => $atone_cart['user_data']['b_state'].$atone_cart['user_data']['b_city'].$atone_cart['user_data']['b_address'].$atone_cart['user_data']['b_address_2'], // 住所
			'tel' => $atone_cart['user_data']['b_phone'], // 電話番号
			'email' => $atone_cart['user_data']['email'], // メールアドレス
		);
		ksort($customer);

		//配送先
		$dest_customer = array(
			'dest_customer_name' => $atone_cart['user_data']['s_firstname'].$atone_cart['user_data']['s_lastname'], // 購入者氏名
			'dest_zip_code' => $atone_cart['user_data']['s_zipcode'], // 郵便番号
			'dest_address' => $atone_cart['user_data']['s_state'].$atone_cart['user_data']['s_city'].$atone_cart['user_data']['s_address'].$atone_cart['user_data']['s_address_2'], // 住所
			'dest_tel' => $atone_cart['user_data']['s_phone'], // 電話番号
		);
		ksort($dest_customer);
		$dest_customers[] = $dest_customer;
            

		if($atone_cart['user_data']['user_id']) {
			$pre_token = db_get_field('SELECT user_token FROM ?:users WHERE user_id = ?i', $atone_cart['user_data']['user_id']);
		}else{
		    $pre_token = $_SESSION['pre_token'];
		}
            
		$price_total = $orderinfo['subtotal'] + $orderinfo['shipping_cost'];
		
		$transaction_ids[] = $transaction_id;

		$c_private_key = base64_encode($private_key);
		$basic = "Basic ".$c_private_key;
	
		$order_id = $atone_cart['order_id'];
	
		$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
	
    	$order_detail = fn_atn_reference($order_id, $basic, $atone_mode);
    	$t_option = $order_detail['transaction_options'];

		//決済データまとめ
		$data = array(
			'amount' => $total, // 課金額
			'authentication_token' => $pre_token,
			'customer' => $customer,//購入者
			'dest_customers' => $dest_customers, // 配送先
			'items' => $items, // 商品明細
			'updated_transactions' => $transaction_ids,
			'shop_transaction_no' => $transaction_no,
			'transaction_options' => $t_option
		);
		ksort($data);

	}

	if($atone_mode == "N"){
		//テスト環境の場合(APIエンドポイント)
		$target_url = "https://ct-api.a-to-ne.jp/v1/transactions/"; 
	}else{
		//本番環境の場合(APIエンドポイント)
		$target_url = "https://api.atone.be/v1/transactions/";
	}             
	
	$data = json_encode($data, JSON_UNESCAPED_UNICODE);
	
	$header = [
		"Authorization:".$basic,  // 前準備で取得したtokenをヘッダに含める
  		"Content-Type: application/json",
  		"X-HTTP-Method-Override: POST",
  		"Accept: application/json",
  		"X-NP-Terminal-Id: 4000000400"
	]; 
 
	$curl = curl_init($target_url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

	$json_response = curl_exec($curl);

	$response = json_decode($json_response,true);

	if(isset($response['errors'])) {
		$errors = $response['errors'];
		foreach ($errors as $key => $value){
				$messages = $value['messages'];
				foreach ($messages as $val){
					fn_set_notification('E', __('error'),'atone:'.$val);
				}
		}
	}elseif($response['authorization_result'] == '1'){
		fn_set_notification('N', __('notice'), __('atone_be_order'));
	}
	
	$_SESSION['pre_token'] = "";
	
	if(isset($response['id'])){
		db_query('UPDATE ?:orders SET tr_id = ?s WHERE order_id = ?i',$response['id'],$atone_cart['order_id']);
	}

	curl_close($curl);
      
}

//atone:注文参照
function fn_atn_reference($order_id, $basic, $atone_mode)
{

	$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $order_id);
	
	if($atone_mode == "N"){
		//テスト環境の場合
		$target_url = "https://ct-api.a-to-ne.jp/v1/transactions/".$transaction_id; 
	}else{
		//本番環境の場合
		$target_url = "https://api.atone.be/v1/transactions/".$transaction_id;
	}
                   
	$s_data = array(
		'id' => $transaction_id, // 取引オブジェクトID
	); 
      
	//json_encode
	$s_data = json_encode($s_data, JSON_UNESCAPED_UNICODE);
            
                
	$header = [
				"Authorization:".$basic,  // 前準備で取得したtokenをヘッダに含める
  			 	"Content-Type: application/json",
  				"X-HTTP-Method-Override: GET",
  			  	"Accept: application/json",
  			  	"X-NP-Terminal-Id: 4000000400"
			]; 
 
	$curl = curl_init($target_url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $s_data);

	$json_response = curl_exec($curl);

	$response = json_decode($json_response,true);
	curl_close($curl);
	
	return $response;
	
}

//atone:キャンセル処理
function fn_atn_canceled_base($order_id)
{

	$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $order_id);
		
	$private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');
	$c_private_key = base64_encode($private_key);

	$basic = "Basic ".$c_private_key;
			
	$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
       
	if($atone_mode == "N"){
		//テスト環境の場合
		$target_url = "https://ct-api.a-to-ne.jp/v1/transactions/".$transaction_id."/refund";
	}else{
		//本番環境の場合
		$target_url = "https://api.atone.be/v1/transactions/".$transaction_id."/refund";
	}
       
	$canceldata = array(
		'id' => $transaction_id,
	);
          
	$canceldatas = json_encode($canceldata, JSON_UNESCAPED_UNICODE);

	$header = [
				"Authorization:".$basic,  // 前準備で取得したtokenをヘッダに含める
  			 	"Content-Type: application/json",
  				"X-HTTP-Method-Override: PATCH",
  			  	"Accept: application/json",
  			  	"X-NP-Terminal-Id: 4000000400"
			]; 
 
	$curl = curl_init($target_url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $canceldatas);

	$json_response = curl_exec($curl);

	$response = json_decode($json_response,true);

	if(isset($response['errors'])) {
		$errors = $response['errors'];
		foreach ($errors as $key => $value){
			$messages = $value['messages'];
			foreach ($messages as $val){
				fn_set_notification('E', __('error'),'atone:'.$val);
			}
		}
		//exit();
	}elseif($response['authorization_result'] == '1'){
		fn_set_notification('N', __('notice'), __('atone_be_canceled'));
	}

	curl_close($curl);
}


//atone:注文の本登録(注文詳細画面にて)
function fn_atn_register($order_id)
{
	$atone_cart = $_SESSION['cart'];

	$orderinfo = fn_get_order_info($order_id);

	$ap_product = $atone_cart['products'];
	ksort($ap_product);

	$private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');
	$transaction_no = db_get_field('SELECT transaction_no FROM ?:orders WHERE order_id = ?i', $order_id);
	$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $order_id);
	       
	$total = "";

	//商品
	foreach ($ap_product as $itemValue) {
		$p_id = $itemValue['product_id'];
		//190111 atone管理画面側に掲載される商品URLについて修正
		$product_url = fn_url("products.view&product_id=$p_id", 'C');

		$item = array(
			'shop_item_id' => $itemValue['product_id'],
			'item_name' => $itemValue['product'],
			'item_price' => $itemValue['price'],
			'item_count' => $itemValue['amount'],
			'item_url' => $product_url,
		);
		ksort($item);
		$items[] = $item;
		$itemprice = $itemValue['price'] * $itemValue['amount'];
              
		$total += $itemprice;
     }
          
	//税金
	if (isset($atone_cart['taxes'])) {
		$tax = $atone_cart['taxes'];
			foreach ($tax as $taxValue) {
			//外税の場合のみ
				if($taxValue['price_includes_tax'] != "Y") {
					$item = array(
						'shop_item_id' =>'tax001',
						'item_name' => $taxValue['description'],
						'item_price' => $taxValue['tax_subtotal'],
						'item_count' => '1',         
					);
				ksort($item);
				$items[] = $item;
				$total += $taxValue['tax_subtotal'];
				}
			}
	}
	ksort($items);
    
	//配送料
	//20180625 サプライヤーアドオン有効時・配送方法に対して価格、重量、商品数に応じた送料を設定している際に生じる不具合を修正
	if (isset($atone_cart['product_groups'])) {
		$p_group = $atone_cart['product_groups'];
		foreach ($p_group as $shippingValue) {
		  foreach ($shippingValue['chosen_shippings'] as $shipping) {
			$item = array(
				'shop_item_id' =>'ship'.$shipping['shipping_id'],
				'item_name' => $shipping['shipping'],
				'item_price' => $shipping['rate'],
				'item_count' => 1,
		    );
			ksort($item);
			$items[] = $item;
            $total += $shipping['rate'];
          }
		}
		
	}
	ksort($items);
	
	//割引額が0以上の時
	if ($atone_cart['subtotal_discount'] > 0) {
		$discount = __('including_discount', null, CART_LANGUAGE);
		$item = array(
				'shop_item_id' =>'discount',
				'item_name' => $discount,
				'item_price' => '-'.$atone_cart['subtotal_discount'],
				'item_count' => 1,
		);
		ksort($item);
		$items[] = $item;
		
		$discount = '-'.$atone_cart['subtotal_discount'];
		
		$total += $discount;
	}
	ksort($items);
	
	//支払手数料が0以上の時
	if ($atone_cart['payment_surcharge'] > 0) {
		$surcharge = __('payment_surcharge', null, CART_LANGUAGE);
		$item = array(
				'shop_item_id' =>'surcharge',
				'item_name' => $surcharge,
				'item_price' => $atone_cart['payment_surcharge'],
				'item_count' => 1,
		);
		ksort($item);
		$items[] = $item;
		
		$total += $atone_cart['payment_surcharge'];
	}
	ksort($items);

	//session内にuser_dataがある時
	if(!empty($atone_cart['user_data'])){
		//購入者
		$customer = array(
			'customer_name' => $atone_cart['user_data']['b_firstname'].$atone_cart['user_data']['b_lastname'], // 購入者氏名
			'customer_family_name' => $atone_cart['user_data']['b_firstname'],
			'customer_given_name' => $atone_cart['user_data']['b_lastname'],
			'zip_code' => $atone_cart['user_data']['b_zipcode'], // 郵便番号
			'address' => $atone_cart['user_data']['b_state'].$atone_cart['user_data']['b_city'].$atone_cart['user_data']['b_address'].$atone_cart['user_data']['b_address_2'], // 住所
			'tel' => $atone_cart['user_data']['b_phone'], // 電話番号
			'email' => $atone_cart['user_data']['email'], // メールアドレス
		);
		ksort($customer);

		//配送先
		$dest_customer = array(
			'dest_customer_name' => $atone_cart['user_data']['s_firstname'].$atone_cart['user_data']['s_lastname'], // 購入者氏名
			'dest_zip_code' => $atone_cart['user_data']['s_zipcode'], // 郵便番号
			'dest_address' => $atone_cart['user_data']['s_state'].$atone_cart['user_data']['s_city'].$atone_cart['user_data']['s_address'].$atone_cart['user_data']['s_address_2'], // 住所
			'dest_tel' => $atone_cart['user_data']['s_phone'], // 電話番号
		);
		ksort($dest_customer);
		$dest_customers[] = $dest_customer;
            
		if($atone_cart['user_data']['user_id']) {
			$pre_token = db_get_field('SELECT user_token FROM ?:users WHERE user_id = ?i', $atone_cart['user_data']['user_id']);
		}else{
		    $pre_token = $_SESSION['pre_token'];
		}
                  
		$transaction_ids[] = $transaction_id;
            
		$price_total = $orderinfo['subtotal'] + $orderinfo['shipping_cost'];
            
		$c_private_key = base64_encode($private_key);
		$basic = "Basic ".$c_private_key;
                
		$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');

    	$order_detail = fn_atn_reference($order_id, $basic, $atone_mode);

   		$t_options = $order_detail['transaction_options'];
    	$t_option = array_diff($t_options, array('3'));

		$data = array(
			'amount' => $total, // 課金額
			'authentication_token' => $pre_token,
			'customer' => $customer,//購入者
			'dest_customers' => $dest_customers, // 配送先
			'items' => $items, // 商品明細
			'updated_transactions' => $transaction_ids,
			'shop_transaction_no' => $transaction_no,
			'transaction_options' => $t_option
		);
		ksort($data);

	}
	
	if($atone_mode == "N"){
		//テスト環境の場合(APIエンドポイント)
		$target_url = "https://ct-api.a-to-ne.jp/v1/transactions/"; 
	}else{
		//本番環境の場合(APIエンドポイント)
		$target_url = "https://api.atone.be/v1/transactions/";
	}             
	
	$data = json_encode($data, JSON_UNESCAPED_UNICODE);
	
	$header = [
		"Authorization:".$basic,  // 前準備で取得したtokenをヘッダに含める
  		"Content-Type: application/json",
  		"X-HTTP-Method-Override: POST",
  		"Accept: application/json",
  		"X-NP-Terminal-Id: 4000000400"
	]; 
 
	$curl = curl_init($target_url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

	$json_response = curl_exec($curl);

	$response = json_decode($json_response,true);

	if(isset($response['errors'])) {
		$errors = $response['errors'];
		foreach ($errors as $key => $value){
				$messages = $value['messages'];
				foreach ($messages as $val){
					fn_set_notification('E', __('error'),'atone:'.$val);
				}
		}
		exit;
		
	}elseif($response['authorization_result'] == '1'){
		fn_set_notification('N', __('notice'),__('atone_be_option_register'));
	}
	
	$_SESSION['pre_token'] = "";
	
	if(isset($response['id'])){
		db_query('UPDATE ?:orders SET tr_id = ?s WHERE order_id = ?i',$response['id'],$order_id);
	}

	curl_close($curl);

}

//atone:注文の本登録(atone_c.manage画面)
function fn_atn_register_c($order_id, $result)
{

	$orderinfo = fn_get_order_info($order_id);

	$private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');
	$transaction_no = db_get_field('SELECT transaction_no FROM ?:orders WHERE order_id = ?i', $order_id);
	$transaction_id = db_get_field('SELECT tr_id FROM ?:orders WHERE order_id = ?i', $order_id);
	
	$user_id = db_get_field('SELECT user_id FROM ?:orders WHERE transaction_no = ?s', $transaction_no);
	
	$transaction_ids[] = $transaction_id;
	       
	if($user_id) {
		$pre_token = db_get_field('SELECT user_token FROM ?:users WHERE user_id = ?i', $user_id);
	}
	
	$c_private_key = base64_encode($private_key);
	$basic = "Basic ".$c_private_key;
                
	$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
	
	
    $order_detail = fn_atn_reference($order_id, $basic, $atone_mode);
    
    $t_options = $order_detail['transaction_options'];
    $t_option = array_diff($t_options, array('3'));
    
    $data = array();
	
	$data += array('amount' => $result['amount']);
	$data += array('authentication_token' => $pre_token);
	$data += array('customer' => $result['customer']);
	$data += array('dest_customers' => $result['dest_customers']);
	$data += array('items' => $result['items']);
	$data += array('shop_transaction_no' => $transaction_no);
	$data += array('transaction_options' => $t_option);
	$data += array('updated_transactions' => $transaction_ids);

	ksort($data);
	
       
	if($atone_mode == "N"){
		//テスト環境の場合(APIエンドポイント)
		$target_url = "https://ct-api.a-to-ne.jp/v1/transactions/"; 
	}else{
		//本番環境の場合(APIエンドポイント)
		$target_url = "https://api.atone.be/v1/transactions/";
	}             
	
	$data = json_encode($data, JSON_UNESCAPED_UNICODE);
	
	$header = [
		"Authorization:".$basic,  // 前準備で取得したtokenをヘッダに含める
  		"Content-Type: application/json",
  		"X-HTTP-Method-Override: POST",
  		"Accept: application/json",
  		"X-NP-Terminal-Id: 4000000400"
	]; 
 
	$curls = curl_init($target_url);
	curl_setopt($curls, CURLOPT_HEADER, false);
	curl_setopt($curls, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curls, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curls, CURLOPT_POST, true);
	curl_setopt($curls, CURLOPT_POSTFIELDS, $data);

	$json_response = curl_exec($curls);

	$response = json_decode($json_response,true);

	if(isset($response['errors'])) {
		$errors = $response['errors'];
		foreach ($errors as $key => $value){
				$messages = $value['messages'];
				foreach ($messages as $val){
					fn_set_notification('E', __('error'),'atone:'.$val);
				}
		}
		//exit;
	}elseif($response['authorization_result'] == '1'){
		fn_set_notification('N', __('notice'),__('atone_be_option_register'));
	}
	
	if(isset($response['id'])){
		db_query('UPDATE ?:orders SET tr_id = ?s WHERE order_id = ?i',$response['id'],$order_id);
	}

	curl_close($curls);

}


//atone:売上確定
function fn_atn_sales_finalized($order_id)
{

	fn_change_order_status($order_id, 'C');
 
}


//atone:(atone_c.manage)画面にて注文キャンセル時
function fn_atn_cancel_order($order_id)
{

	fn_change_order_status($order_id, 'I');

}

##########################################################################################
// END オリジナル関数
##########################################################################################