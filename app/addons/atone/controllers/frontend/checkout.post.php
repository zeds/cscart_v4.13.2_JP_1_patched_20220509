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

// $Id: checkout.post.php from andplus 2020

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'checkout') {

	$c = $_SESSION['cart'];
	$atone_processor_data = fn_get_payment_method_data($c['payment_id']);

	if($atone_processor_data['processor_id'] == '9088') {

		$ap_product = $c['products'];
		ksort($ap_product);

		$pub_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "pub_key"');
		Tygh::$app['view']->assign('pub_key', $pub_key);

		$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
		Tygh::$app['view']->assign('atone_mode', $atone_mode);  

		$private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');
		
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
		}

		//税金
		if (isset($c['taxes'])) {
			$tax = $c['taxes'];
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
				}
			}
		}
		ksort($items);

		//配送料
		//20180625 サプライヤーアドオン有効時・配送方法に対して価格、重量、商品数に応じた送料を設定している際に生じる不具合を修正
		if (isset($c['shipping'])) {
			$shipping = $c['shipping'];
			foreach ($shipping as $shippingValue) {
	  			foreach ($shippingValue['rates'] as $rates) {
					$item = array(
						'shop_item_id' =>'ship001',
						'item_name' => $shippingValue['shipping'],
						'item_price' => $rates,
						'item_count' => 1,
					);
					ksort($item);
	    			$items[] = $item;
	  			}
			}
		}
		ksort($items);
	
		//割引額が0以上の時
		if ($c['subtotal_discount'] > 0) {
			$discount = __('including_discount', null, CART_LANGUAGE);
			$item = array(
					'shop_item_id' =>'discount',
					'item_name' => $discount,
					'item_price' => '-'.$c['subtotal_discount'],
					'item_count' => 1,
			);
			ksort($item);
			$items[] = $item;
	
			$discount = '-'.$c['subtotal_discount'];
		}
		ksort($items);


		//支払手数料が0以上の時
		if ($c['payment_surcharge'] > 0) {
			$surcharge = __('payment_surcharge', null, CART_LANGUAGE);
			$item = array(
					'shop_item_id' =>'surcharge',
					'item_name' => $surcharge,
					'item_price' => $c['payment_surcharge'],
					'item_count' => 1,
			);
			ksort($item);
			$items[] = $item;
		}
		ksort($items);


		//20190514
		$data = array();

		if(!empty($c['user_data'])){
			//購入者	
			
			if (Registry::get('addons.step_by_step_checkout.status') == 'A') {
			//旧購入手続きアドオンが有効の場合
				$customer = array(
					'customer_name' => $c['user_data']['b_firstname'].$c['user_data']['b_lastname'], // 購入者氏名
					'customer_family_name' => $c['user_data']['b_firstname'],
					'customer_given_name' => $c['user_data']['b_lastname'],
					'zip_code' => $c['user_data']['b_zipcode'], // 郵便番号
					'address' => $c['user_data']['b_state'].$c['user_data']['b_city'].$c['user_data']['b_address'].$c['user_data']['b_address_2'], // 住所
					'tel' => $c['user_data']['b_phone'], // 電話番号
					'email' => $c['user_data']['email'], // メールアドレス
				);
				ksort($customer);

				//配送先
				$dest_customer = array(
					'dest_customer_name' => $c['user_data']['s_firstname'].$c['user_data']['s_lastname'], // 購入者氏名
					'dest_zip_code' => $c['user_data']['s_zipcode'], // 郵便番号
					'dest_address' => $c['user_data']['s_state'].$c['user_data']['s_city'].$c['user_data']['s_address'].$c['user_data']['s_address_2'], // 住所
					'dest_tel' => $c['user_data']['s_phone'], // 電話番号
				);
				
				ksort($dest_customer);
				$dest_customers[] = $dest_customer;
			
			}else{
			//旧購入手続きアドオンが無効の場合
				
				if($c['user_data']['ship_to_another'] == '1') {	
					//購入者住所と配送先住所が異なる場合
					$customer = array(
						'customer_name' => $c['user_data']['b_firstname'].$c['user_data']['b_lastname'], // 購入者氏名
						'customer_family_name' => $c['user_data']['b_firstname'],
						'customer_given_name' => $c['user_data']['b_lastname'],
						'zip_code' => $c['user_data']['b_zipcode'], // 郵便番号
						'address' => $c['user_data']['b_state'].$c['user_data']['b_city'].$c['user_data']['b_address'].$c['user_data']['b_address_2'], // 住所
						'tel' => $c['user_data']['b_phone'], // 電話番号
						'email' => $c['user_data']['email'], // メールアドレス
					);
					ksort($customer);

					//配送先
					$dest_customer = array(
						'dest_customer_name' => $c['user_data']['s_firstname'].$c['user_data']['s_lastname'], // 購入者氏名
						'dest_zip_code' => $c['user_data']['s_zipcode'], // 郵便番号
						'dest_address' => $c['user_data']['s_state'].$c['user_data']['s_city'].$c['user_data']['s_address'].$c['user_data']['s_address_2'], // 住所
						'dest_tel' => $c['user_data']['s_phone'], // 電話番号
					);
					
					ksort($dest_customer);
					$dest_customers[] = $dest_customer;
			
				}else{
				
					//購入者住所と配送先住所が同じ場合(配送先住所に内容を合わせる)
					$customer = array(
						'customer_name' => $c['user_data']['s_firstname'].$c['user_data']['s_lastname'], // 購入者氏名
						'customer_family_name' => $c['user_data']['s_firstname'],
						'customer_given_name' => $c['user_data']['s_lastname'],
						'zip_code' => $c['user_data']['s_zipcode'], // 郵便番号
						'address' => $c['user_data']['s_state'].$c['user_data']['s_city'].$c['user_data']['s_address'].$c['user_data']['s_address_2'], // 住所
						'tel' => $c['user_data']['s_phone'], // 電話番号
						'email' => $c['user_data']['email'], // メールアドレス
					);
					ksort($customer);

					//配送先
					$dest_customer = array(
						'dest_customer_name' => $c['user_data']['s_firstname'].$c['user_data']['s_lastname'], // 購入者氏名
						'dest_zip_code' => $c['user_data']['s_zipcode'], // 郵便番号
						'dest_address' => $c['user_data']['s_state'].$c['user_data']['s_city'].$c['user_data']['s_address'].$c['user_data']['s_address_2'], // 住所
						'dest_tel' => $c['user_data']['s_phone'], // 電話番号
					);
					
					ksort($dest_customer);
					$dest_customers[] = $dest_customer;
				
				}
			}
	
	
			if ($c['payment_surcharge'] > 0) {
			//支払手数料がある場合
				$amount = $c['total'];
				$amount += $c['payment_surcharge'];

				$data = array(
					'amount' => $amount, // 課金額
					'sales_settled' => "false", // 売上確定
					'customer' => $customer,//購入者
					'dest_customers' => $dest_customers, // 配送先
					'items' => $items, // 商品明細
				);
				ksort($data);
			}else{
				$data = array(
					'amount' => $c['total'],
					'sales_settled' => "false", // 売上確定
					'customer' => $customer,//購入者
					'dest_customers' => $dest_customers, // 配送先
					'items' => $items, // 商品明細
				);
				ksort($data);
			}

		}

		$sort_data = "";

		foreach ($data as $key => $val) {
	
			if($key == "checksum" || $key == "shop_transaction_no"){
				unset($data[$key]);
			}

			if($key == "items"){
				foreach ($val as $item_k => $item_v) {
					foreach ($item_v as $ik => $iv) {
						$sort_data .= $item_v[$ik];
					}
				}
			}

			elseif($key == "dest_customers"){
				foreach ($val as $item_k => $item_v) {
					foreach ($item_v as $ik => $iv) {
						$sort_data .= $item_v[$ik];
					}
				}
			}

			elseif(is_array($val) == true) {
				foreach ($val as $k => $v) {
					$sort_data .= $val[$k];
				}
			}

			else{
				$sort_data .= $data[$key];
			}
		}

		$checksum = $private_key.",".$sort_data;
		$checksum = base64_encode(hash('sha256', $checksum, true));

		if(!empty($checksum)){
     		if(is_array($data)){
         		$data['checksum'] = $checksum;
     		}
 		}
		
		$datas = json_encode($data, JSON_UNESCAPED_UNICODE);
		$datas = ltrim($datas, "\xEF\xBB\xBF");
		Tygh::$app['view']->assign('datas', $datas);

		$p_params = $atone_processor_data['processor_params'];

		if(isset($p_params)) {
			Tygh::$app['view']->assign('p_params', $p_params);	
			//定期取引オプション
			if(isset($p_params['opreg'])) {
				if($p_params['opreg'] === "true") {
					$opreg = 1;
					Tygh::$app['view']->assign('opreg', $opreg);
				}
			}

			//金額更新オプション
			if(isset($p_params['opupdates'])) {
				if($p_params['opupdates'] === "true") {
					$opupdates = 2;
					Tygh::$app['view']->assign('opupdates', $opupdates);
				}
			}

			//金額更新オプション
			if(isset($p_params['oppro'])) {
				if($p_params['oppro'] === "true") {
					$oppro = 3;
					Tygh::$app['view']->assign('oppro', $oppro);
				}
			}
		}

	}

}