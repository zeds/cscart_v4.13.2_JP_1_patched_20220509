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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START アドオンのインストール、アンインストール時に動作する関数
##########################################################################################

function fn_gmo_deferred_payment_add_payment_processors()
{
    fn_lcjp_install('gmo_deferred_payment');
    
    db_query("ALTER TABLE ?:orders ADD gmo_transaction_id varchar(255)");
	db_query("ALTER TABLE ?:orders ADD gmo_author_result varchar(255)");
	db_query("ALTER TABLE ?:orders ADD gmo_include mediumint(8)");
	db_query("CREATE TABLE ?:gmo_shipments (order_id mediumint(8) unsigned NOT NULL, shipment_id mediumint(8) unsigned NOT NULL,carrier mediumtext NOT NULL,slipno mediumtext NOT NULL,gmoTransactionId mediumtext NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=UTF8");
	db_query("INSERT INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type, addon) VALUES (9345, 'GMO後払い', 'gmo_deferred_payment.php', 'addons/gmo_deferred_payment/views/orders/components/payments/gmo_deferred_payment.tpl', 'gmo_deferred_payment.tpl', 'Y', 'B', 'gmo_deferred_payment')");

}

function fn_gmo_deferred_payment_delete_payment_processors()
{
    db_query("ALTER TABLE ?:orders DROP `gmo_transaction_id`, DROP `gmo_author_result`, DROP `gmo_include`");
    db_query("DROP TABLE ?:gmo_shipments");
    db_query("DELETE FROM ?:payment_processors WHERE processor_id = 9345");

}

##########################################################################################
// END アドオンのインストール、アンインストール時に動作する関数
##########################################################################################

##########################################################################################
// START オリジナル関数
##########################################################################################

// START 配列データをxmlに変換
/**
 * @param $name
 * @param $data
 * @return mixed|string
 */

function fn_gmo_deferred_payment_toXml($name, $data) {

    $duplicate = array();
    $ret = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    $ret .= fn_gmo_deferred_toXmlString($name, $data);
    preg_match_all('/<("[^"]*"|\'[^\']*\'|[^\'">])*>/',$ret,$matches);
    for ( $iCounter = 0; $iCounter < count($matches[0])-1; $iCounter++ ) {
        if ( strcmp( $matches[0][ $iCounter ], $matches[0][ $iCounter + 1 ] ) ) {
            $duplicate[] = $matches[0][ $iCounter ];
        }
    }

    for ($iCounter = 0; $iCounter < count($duplicate); $iCounter++) {
        $ret = str_replace(
            $duplicate[$iCounter].$duplicate[$iCounter],
            $duplicate[$iCounter],
            $ret);
    }
    $ret = str_replace("</${name}></${name}>","</${name}>", $ret);
    $ret = fn_gmo_deferred_parseXml($ret);
    return $ret;
}

/**
 * @param $data
 * @return string
 */
function fn_gmo_deferred_parseXml($data) {

    $ret = "";
    $tabs = 0;
    $EndFlag = false;
    for ( $i = 0; $i < strlen($data) - 2; $i++ ) {
        $ret .= $data[$i];
        if ( $data[$i] == '<' && $data[$i+1] == '/' ) {
            $EndFlag = true;
        } else if ( $data[$i] == '>' ) {
            if ( $EndFlag == true ) {
                $ret .= "";
                $tabs--;
                $EndFlag = false;
            }
        }
        if ( $data[$i] == '>' && $data[$i+1] == '<' && $data[$i+2] != '/' ) {
            $ret .= "\n";
            $tabs++;
            $ret .= fn_gmo_deferred_pushTabs( $tabs );
        } else if ( $data[$i] == '>' && $data[$i+1] == '<' && $data[$i+2] == '/' ) {
            $ret .= "\n";
            $ret .= fn_gmo_deferred_pushTabs( $tabs );
        }
    }
    $ret .= $data[$i].$data[$i+1];
    return $ret;
}

/**
 * @param $tabs
 * @return string
 */
function fn_gmo_deferred_pushTabs($tabs) {

    $ret = "";
    for ( $i = 0; $i < $tabs; $i++ ) {
        $ret .= "\t";
    }
    return $ret;
}

/**
 * @param $name
 * @param $data
 * @return string
 */
function fn_gmo_deferred_toXmlString($name, $data) {

    $s = '';
    if ( is_array( $data ) ) {
        foreach( $data as $k => $v ) {
            if ( is_numeric( $k ) ) {
                $s .= fn_gmo_deferred_toXmlString( $name, $v );
            } else {
                $s .= fn_gmo_deferred_toXmlString( $k, $v );
            }
        }
    } else {
        $s = fn_gmo_deferred_xmlEscape($data);
    }

    return "<$name>$s</$name>";
}

/**
 * @param $value
 * @return string
 */
function fn_gmo_deferred_xmlEscape($value) {
    return htmlSpecialChars($value, ENT_QUOTES, 'UTF-8');
}
// END 配列データをxmlに変換


//curl
/**
 * @param $target_url
 * @param $data
 * @return bool|mixed
 */
function fn_gmo_deferred_payment_curl($target_url, $data) {
  
    //配列のxml化
    $xml = fn_gmo_deferred_payment_toXml("request", $data);

    $curl = curl_init($target_url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
    $response = curl_exec($curl);
        
    $xml_response = simplexml_load_string($response);
    $gmo_response = json_decode(json_encode($xml_response), true);
    
    if($errno = curl_errno($curl)) {
    	if(AREA != 'A'){
    	    fn_set_notification('E', __('error'), __('text_order_placed_error'));
    	    fn_redirect(fn_url("checkout.checkout&edit_step=step_four", 'C', 'current'),true);
    	}else{
    	    fn_set_notification('E', __('error'), __('gmo_communication_error'));
    	    fn_set_notification('E', __('error'), 'curl error'.$errno);
    	}
	}
    
    curl_close($curl);

    return $gmo_response;

}

//運送会社コード取得
/**
 * @param $shipment_carrier
 * @return string
 */
function fn_gmo_deferred_shipping_company($shipment_carrier)
{

    switch($shipment_carrier){

        case 'sagawa': // 佐川急便
            $pdcompanycode = '11';
            return $pdcompanycode;
        case 'yamato': // ヤマト運輸
            $pdcompanycode = '12';
            return $pdcompanycode;
        case 'seino': // 西濃運輸
            $pdcompanycode = '14';
            return $pdcompanycode;
        case 'jpost': // ゆうパック
            $pdcompanycode = '16';
            return $pdcompanycode;
        case 'fukutsu': // 福山通運
            $pdcompanycode = '18';
            return $pdcompanycode;

        default: //その他
            $pdcompanycode = '99';
            return $pdcompanycode;
    }

}

//接続先URL
/**
 * @param $connection_code
 * @param $payment_params
 * @return string
 */
function fn_gmo_deferred_connection_url($connection_code,$payment_params)
{

    switch($connection_code){

        case '1'://取引登録・修正
            //注文編集の場合
            if($_REQUEST['dispatch'] == 'order_management.place_order') {
                if($payment_params['test'] == 'Y') {
                    $target_url = 'https://testshop.gmo-ab.com/auto/modifycanceltransaction.do';
                }else{
                    $target_url = 'https://shop.gmo-ab.com/auto/modifycanceltransaction.do';
                }
                //新規注文の場合
            }else{
                if($payment_params['test'] == 'Y') {
                    $target_url = 'https://testshop.gmo-ab.com/auto/transaction.do';
                }else{
                    $target_url = 'https://shop.gmo-ab.com/auto/transaction.do';
                }
            }
            return $target_url;

        case '2'://与信審査結果確認
            if($payment_params['test'] == 'Y') {
                $target_url = 'https://testshop.gmo-ab.com/auto/getauthor.do';
            }else{
                $target_url = 'https://shop.gmo-ab.com/auto/getauthor.do';
            }
            return $target_url;

        case '3'://出荷登録
            if($payment_params['test'] == 'Y') {
                $target_url = 'https://testshop.gmo-ab.com/auto/pdrequest.do';
            }else{
                $target_url = 'https://shop.gmo-ab.com/auto/pdrequest.do';
            }
            return $target_url;

        case '4'://取引キャンセル
            if($payment_params['test'] == 'Y') {
                $target_url = 'https://testshop.gmo-ab.com/auto/modifycanceltransaction.do';
            }else{
                $target_url = 'https://shop.gmo-ab.com/auto/modifycanceltransaction.do';
            }
            return $target_url;

        case '5'://出荷報告修正・キャンセル
            if($payment_params['test'] == 'Y') {
                $target_url = 'https://testshop.gmo-ab.com/auto/modifycancelpdrequest.do';
            }else{
                $target_url = 'https://shop.gmo-ab.com/auto/modifycancelpdrequest.do';
            }
            return $target_url;

        default://その他
            break;
    }

}

//エラー出力
/**
 * @param $errors
 */
function fn_gmo_deferred_error_code($errors)
{
    foreach($errors as $error) {
        if (!empty($error['errorMessage'])) {
            if($error['errorCode'] == 'CT0049'){
                fn_set_notification('E', __('error'),__('gmo_invoice_data'));
            }else{
                fn_set_notification('E', __('error'),$error['errorMessage']);
            }
        }else{
            foreach($error as $er) {
                if($er['errorCode'] == 'CT0049'){
                    fn_set_notification('E', __('error'),__('gmo_invoice_data'));
                }else{
                    fn_set_notification('E', __('error'),$er['errorMessage']);
                }
            }
        }
    }

}

//注文詳細画面で与信結果表示
/**
 * @param $order_id
 * @param $gmo_transaction_id
 * @param $payment_params
 * @return bool|mixed
 */
function fn_gmo_deferred_payment_credit($order_id, $gmo_transaction_id, $payment_params) {

    $data = [
        'shopInfo' => [
            'authenticationId' => $payment_params['gmo_certification_id'],
            'shopCode' =>  $payment_params['gmo_merchant_code'],
            'connectPassword' => $payment_params['gmo_connection_pass']
        ],
        'transaction' => [
            'gmoTransactionId' => $gmo_transaction_id
        ]
    ];

    //接続URL
    $connection_code = '2';
    $target_url = fn_gmo_deferred_connection_url($connection_code,$payment_params);

    $gmo_response = fn_gmo_deferred_payment_curl($target_url, $data);

    return $gmo_response;

}


//配送管理更新時(出荷報告編集)
/**
 * @param $order_info
 * @param $shipment
 * @param $gmo_transaction_id
 */
function fn_gmo_deferred_update_shipment($order_info, $shipment, $gmo_transaction_id)
{

    //支払方法情報取得
    $payment_id = $order_info['payment_id'];
    $processor_data = fn_get_payment_method_data($payment_id);
    $payment_params = $processor_data['processor_params'];

    //接続URL
    $connection_code = '5';
    $target_url = fn_gmo_deferred_connection_url($connection_code,$payment_params);

    //運送会社コード     
    $pdcompanycode = fn_gmo_deferred_shipping_company($shipment['carrier']);

    $data = [
        'shopInfo' => [
            'authenticationId' => $payment_params['gmo_certification_id'],
            'shopCode' =>  $payment_params['gmo_merchant_code'],
            'connectPassword' => $payment_params['gmo_connection_pass']
        ],
        'kindInfo' => [
            'updateKind' => '1'
        ],
        'transaction' => [
            'gmoTransactionId' => $gmo_transaction_id,
            'pdcompanycode' => $pdcompanycode,
            'slipno' => $shipment['tracking_number'],
            'invoiceIssueDate' => ''
        ]
    ];

    $gmo_response = fn_gmo_deferred_payment_curl($target_url, $data);

    //出荷報告にエラーが発生した場合
    if($gmo_response['result'] == 'NG') {
        fn_gmo_deferred_error_code($gmo_response['errors']);
        fn_set_notification('E', __('error'), __('gmo_error_shipment'));
        fn_redirect("orders.details?order_id=" . $order_info['order_id']);
        //出荷報告が正常におこなわれた場合
    }else{
        fn_set_notification('N', __('notice'), __('gmo_success_shipment'));
    }

}

//配送手続きの追加画面(tpl側)にて使用
/**
 * @param $order_id
 * @return bool
 */
function fn_gmo_deferred_payment_order($order_id)
{
    $gmo_transaction_id = db_get_field("SELECT gmo_transaction_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if(!empty($gmo_transaction_id)) {
        return true;
    }else{
        return false;
    }

}

##########################################################################################
// END オリジナル関数
##########################################################################################


##########################################################################################
// START フックを使用した関数
##########################################################################################

//注文登録・修正時
/**
 * @param $order_id
 * @param $action
 * @param $order_status
 * @param $cart
 * @param $auth
 */
function fn_gmo_deferred_payment_place_order($order_id, $action, $order_status, $cart, $auth)
{

    //支払方法情報取得
    $payment_id = $cart['payment_id'];
    $processor_data = fn_get_payment_method_data($payment_id);
    $payment_params = $processor_data['processor_params'];

    //GMO後払いの注文の場合のみ処理
    if(!empty($payment_params['gmo_certification_id'])) {
    
    	$header = getallheaders();
    	$fraudbuster = '';
    	$brouser = '';
    	$remote_addr = '';
    	
    	if(!empty($_POST['fraudbuster'])) {
	    	$fraudbuster = $_POST['fraudbuster'];
		}
		
		if(!empty($_SERVER["REMOTE_ADDR"])) {
	    	$remote_addr = $_SERVER["REMOTE_ADDR"];
		}
			
		if(!empty($header['DNT'])) {
			$brouser = $header['DNT'];
		}else {
			$brouser = $header['X-Do-Not-Track'];
		}
	
		$h = array(
			"Accept" => $header['Accept'],
			"Accept-Charset" => "",
			"Accept-Encoding" => $header['Accept-Encoding'],
			"Accept-Language" => $header['Accept-Language'],
			"Client-IP" => "",
			"Connection" => $header['Connection'],
			"DNT" => $brouser,
			"Host" => $header['Host'],
			"Referrer" => $header['Referrer'],
			"User-Agent" => $header['User-Agent'],
			"Keep-Alive" => "",
			"UA-CPU" => "",
			"Via" => "",
			"X-Forwarded-For" => "",
			"add1" => "",
			"IP" => $remote_addr,
			"add3" => ""
		);
	
		$param = "";
	
		foreach ($h as $name => $value) {
    		$param .= $value.";:";
		}
    
        //注文日
        if($_REQUEST['dispatch'] == 'order_management.place_order') {
            $timestamp = $cart['order_timestamp'];
            $date = date("Y/m/d",$timestamp);
        }else{
            $date = date("Y/m/d");
        }

        //同梱サービス使用時
        if($payment_params['gmo_include'] == 'Y') {
            if($cart['ship_to_another'] == '1') {
                //購入者住所と配送先住所が異なる取引
                $payment_type = '2';
            }else{
                //購入者住所と配送先住所が同じ取引
                $payment_type = '3';
            }
            //同梱サービス不使用時
        }else{
            $payment_type = '2';
        }
        
        if($_REQUEST['dispatch'] == 'order_management.place_order') {
        	$order_info = fn_get_order_info($order_id, false, false);
        	$payment_type = $order_info['gmo_include'];
        }

        //接続URL
        $connection_code = '1';
        $target_url = fn_gmo_deferred_connection_url($connection_code,$payment_params);

        $data = [
            'shopInfo' => [
                'authenticationId' => $payment_params['gmo_certification_id'],
                'shopCode' =>  $payment_params['gmo_merchant_code'],
                'connectPassword' => $payment_params['gmo_connection_pass']
            ],
            'httpInfo' => [
                'httpHeader' => $param,
                'deviceInfo' => $fraudbuster,
            ],
            'buyer' => [
                'shopTransactionId' => $order_id,
                'shopOrderDate' => $date,
                'fullName' => $cart['user_data']['b_firstname'].$cart['user_data']['b_lastname'],
                'zipCode' => $cart['user_data']['b_zipcode'],
                'address' => $cart['user_data']['b_state'].$cart['user_data']['b_city'].$cart['user_data']['b_address'].$cart['user_data']['b_address2'],
                'tel1' => $cart['user_data']['b_phone'],
                'email1' => $cart['user_data']['email'],
                'billedAmount' => $cart['total'],
                'paymentType' => $payment_type, //同梱サービス使用の有無

            ],
            'deliveries' => [
                'delivery' => [
                    'deliveryCustomer' => [
                        'fullName' => $cart['user_data']['s_firstname'].$cart['user_data']['s_lastname'],
                        'zipCode' => $cart['user_data']['s_zipcode'],
                        'address' => $cart['user_data']['s_state'].$cart['user_data']['s_city'].$cart['user_data']['s_address'].$cart['user_data']['s_address2'],
                        'tel' => $cart['user_data']['s_phone'],
                    ],
                ]
            ]

        ];

        if($_REQUEST['dispatch'] == 'order_management.place_order') {
            $gmo_transaction_id = db_get_field("SELECT gmo_transaction_id FROM ?:orders WHERE order_id = ?i", $order_id);
            $data['buyer']['gmoTransactionId'] = $gmo_transaction_id;
            $data['kindInfo']['updateKind'] = 1;
        }

        //商品
        //種類が11より多い場合(商品の種類が11を超えると明細には「その他商品総額」という形でまとめられます)
        if(count($cart['products']) > 11) {
       		$products_total = __('gmo_products_total', null, CART_LANGUAGE);
       		$i = '0';
       		$total = '';
        	foreach ($cart['products'] as $itemValue) {
        		$i++;
        		if($i < 11) {
        			$detail = [
                		'detailName' => $itemValue['product'],
                		'detailPrice' => $itemValue['price'],
                		'detailQuantity' => $itemValue['amount'],
                		'gmoExtend2' => '', //null
                		'gmoExtend3' => '', //null
                		'gmoExtend4' => '', //null
            		];
            		$data['deliveries']['delivery']['details']['detail'][] = $detail;
        		}else{
        			$total += $itemValue['price'];
        		}
        	}
        	$data['deliveries']['delivery']['details']['detail'][] = [
        			'detailName' => $products_total,
        			'detailPrice' => $total,
                	'detailQuantity' => 1,
                	'gmoExtend2' => '', //null
                	'gmoExtend3' => '', //null
                	'gmoExtend4' => '', //null
            ];
        //種類が11と同じまたは少ない場合
        }else{
        	foreach ($cart['products'] as $itemValue) {
            	$detail = [
                	'detailName' => $itemValue['product'],
                	'detailPrice' => $itemValue['price'],
                	'detailQuantity' => $itemValue['amount'],
                	'gmoExtend2' => '', //null
                	'gmoExtend3' => '', //null
                	'gmoExtend4' => '', //null
            	];
            	$data['deliveries']['delivery']['details']['detail'][] = $detail;
        	}
        }

        //配送料
        if (isset($cart['shipping'])) {
            $shipping = $cart['shipping'];
            foreach ($shipping as $shippingValue) {
                foreach ($shippingValue['rates'] as $rates) {
                    $detail = [
                        'detailName' => $shippingValue['shipping'],
                        'detailPrice' => $rates,
                        'detailQuantity' => 1,
                        'gmoExtend2' => '', //null
                        'gmoExtend3' => '', //null
                        'gmoExtend4' => '', //null
                    ];
                    $data['deliveries']['delivery']['details']['detail'][] = $detail;
                }
            }
        }

        //支払手数料が0以上の時
        if ($cart['payment_surcharge'] > 0) {
            $surcharge = __('payment_surcharge', null, CART_LANGUAGE);
            $detail = [
                'detailName' => $surcharge,
                'detailPrice' => $cart['payment_surcharge'],
                'detailQuantity' => 1,
                'gmoExtend2' => '', //null
                'gmoExtend3' => '', //null
                'gmoExtend4' => '', //null
            ];
            $data['deliveries']['delivery']['details']['detail'][] = $detail;
        }

        //税金
        if (isset($cart['taxes'])) {
            $tax = $cart['taxes'];
            foreach ($tax as $taxValue) {
                //外税の場合のみ
                if($taxValue['price_includes_tax'] != "Y") {
                    $detail = [
                        'detailName' => $taxValue['description'],
                        'detailPrice' => $taxValue['tax_subtotal'],
                        'detailQuantity' => 1,
                        'gmoExtend2' => '', //null
                        'gmoExtend3' => '', //null
                        'gmoExtend4' => '', //null
                    ];
                    $data['deliveries']['delivery']['details']['detail'][] = $detail;
                }
            }
        }

        //割引額が0以上の時
        if ($cart['subtotal_discount'] > 0) {
            $discount = __('including_discount', null, CART_LANGUAGE);
            $detail = [
                'detailName' => $discount,
                'detailPrice' => $cart['subtotal_discount'],
                'detailQuantity' => 1,
                'gmoExtend2' => '', //null
                'gmoExtend3' => '', //null
                'gmoExtend4' => '', //null
            ];
            $data['deliveries']['delivery']['details']['detail'][] = $detail;
        }

        $gmo_response = fn_gmo_deferred_payment_curl($target_url, $data);


        //決済時にエラーが発生した場合
        if($gmo_response['result'] == 'NG') {

            if($_REQUEST['dispatch'] == 'order_management.place_order') {
                fn_set_notification('E', __('error'), __('gmo_error_order'));
                fn_gmo_deferred_error_code($gmo_response['errors']);
            }else{
                fn_set_notification('E', __('error'), __('text_order_placed_error'));
                fn_redirect(fn_url('checkout.checkout&edit_step=step_four', 'C', 'current'), true);
            }
            //決済が正常におこなわれた場合
        }elseif($gmo_response['result'] == "OK"){

            //自動審査結果がOKの場合
            if($gmo_response['transactionResult']['authorResult'] == 'OK') {

                if($_REQUEST['dispatch'] == 'order_management.place_order') {
                    fn_set_notification('N', __('notice'), __('gmo_success_order'));
                }
                db_query("UPDATE ?:orders SET gmo_transaction_id = ?s, gmo_author_result = ?s, gmo_include = ?i WHERE order_id = ?i",$gmo_response['transactionResult']['gmoTransactionId'],$gmo_response['transactionResult']['authorResult'],$payment_type,$order_id);
            
                //自動審査結果がNGの場合        
            }elseif($gmo_response['transactionResult']['authorResult'] == 'NG') {

                if($_REQUEST['dispatch'] == 'order_management.place_order') {
                    fn_set_notification('E', __('error'), __('gmo_examination_ng'));
                }else{
                    fn_set_notification('E', __('error'), __('gmo_examination_ng'));
                    fn_redirect(fn_url('checkout.checkout&edit_step=step_four', 'C', 'current'), true);
                }

                //自動審査結果が審査中の場合
            }elseif($gmo_response['transactionResult']['authorResult'] == '審査中') {

                if($_REQUEST['dispatch'] == 'order_management.place_order') {
                    fn_set_notification('W', __('warning'), __('gmo_under_review_admin'));
                }else{
                    fn_set_notification('W', __('warning'), __('gmo_under_review'));
                }
                db_query("UPDATE ?:orders SET gmo_transaction_id = ?s, gmo_author_result = ?s, gmo_include = ?i WHERE order_id = ?i",$gmo_response['transactionResult']['gmoTransactionId'],$gmo_response['transactionResult']['authorResult'],$payment_type,$order_id);
            }
        }

    }

}


//注文完了時
/**
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_gmo_deferred_payment_finish_payment($order_id, &$pp_response, $force_notification)
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
            case '9345':

                //ステータスの指定
                if($order_status != 'C') {
                    $pp_response['order_status'] = 'O';
                }else{
                    $pp_response['order_status'] = 'C';
                }

                if(!is_array($payment_info)) {
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

//注文ステータス変更時
/**
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 */
function fn_gmo_deferred_payment_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{

    //gmoの注文の場合のみ
    $gmo_transaction_id = db_get_field("SELECT gmo_transaction_id FROM ?:orders WHERE order_id = ?i", $order_info['order_id']);
    if(!empty($gmo_transaction_id)) {

        //キャンセルから別ステータスへの変更の場合
        if($status_from == 'I' && $status_to != 'N'){
            fn_set_notification('E', __('error'), __('gmo_already_canceled'));
            exit;
        }

        //キャンセルへの変更時
        if( $status_to == 'I' ){

            //支払方法情報取得
            $payment_id = $order_info['payment_id'];
            $processor_data = fn_get_payment_method_data($payment_id);
            $payment_params = $processor_data['processor_params'];

            //接続URL
            $connection_code = '4';
            $target_url = fn_gmo_deferred_connection_url($connection_code,$payment_params);

            $data = [
                'shopInfo' => [
                    'authenticationId' => $payment_params['gmo_certification_id'],
                    'shopCode' =>  $payment_params['gmo_merchant_code'],
                    'connectPassword' => $payment_params['gmo_connection_pass']
                ],
                'kindInfo' => [
                    'updateKind' => '2'
                ],
                'buyer' => [
                    'gmoTransactionId' => $gmo_transaction_id,
                ]
            ];

            $gmo_response = fn_gmo_deferred_payment_curl($target_url, $data);

            //キャンセル時にエラーが発生した場合
            if($gmo_response['result'] == 'NG') {
                fn_set_notification('E', __('error'), __('gmo_canceled_error'));
                fn_gmo_deferred_error_code($gmo_response['errors']);
                fn_redirect(fn_url('order_management.update', 'C', 'current'), true);

                //キャンセルが正常におこなわれた場合
            }else{
                fn_set_notification('N', __('notice'), __('gmo_canceled_success'));
                db_query("UPDATE ?:orders SET gmo_transaction_id = ?s,gmo_author_result = ?s WHERE order_id = ?i",$gmo_response['transactionResult']['gmoTransactionId'],$gmo_response['transactionResult']['authorResult'],$order_id);
            }

        }

    }
}

//配送管理追加時(新規出荷報告)
/**
 * @param $shipment_data
 * @param $order_info
 * @param $group_key
 * @param $all_products
 */
function fn_gmo_deferred_payment_create_shipment($shipment_data, $order_info, $group_key, $all_products)
{

    $gmo_transaction_id = db_get_field("SELECT gmo_transaction_id FROM ?:orders WHERE order_id = ?i", $order_info['order_id']);
    if(!empty($gmo_transaction_id)) {

        //運送会社
        $pdcompanycode = fn_gmo_deferred_shipping_company($shipment_data['carrier']);

        //支払方法情報取得
        $payment_id = $order_info['payment_method']['payment_id'];
        $processor_data = fn_get_payment_method_data($payment_id);
        $payment_params = $processor_data['processor_params'];

        //接続URL
        $connection_code = '3';
        $target_url = fn_gmo_deferred_connection_url($connection_code,$payment_params);

        $data = [
            'shopInfo' => [
                'authenticationId' => $payment_params['gmo_certification_id'],
                'shopCode' =>  $payment_params['gmo_merchant_code'],
                'connectPassword' => $payment_params['gmo_connection_pass']
            ],
            'transaction' => [
                'gmoTransactionId' => $gmo_transaction_id,
                'pdcompanycode' => $pdcompanycode,
                'slipno' => $shipment_data['tracking_number'],
                'invoiceIssueDate' => '' //null
            ]
        ];

        $gmo_response = fn_gmo_deferred_payment_curl($target_url, $data);

        //出荷報告にエラーが発生した場合
        if($gmo_response['result'] == 'NG') {
            fn_gmo_deferred_error_code($gmo_response['errors']);
            fn_set_notification('E', __('error'), __('gmo_error_shipment'));
            fn_redirect('orders.details?order_id=' . $order_info['order_id']);
        //出荷報告が正常におこなわれた場合
        }else{
            fn_set_notification('N', __('notice'), __('gmo_success_shipment'));
            $update_data = array(
                'gmoTransactionId' => $gmo_transaction_id,
                'order_id' => $order_info['order_id'],
                'carrier' => $pdcompanycode,
                'slipno' => $shipment_data['tracking_number'],
            );
            db_query("INSERT INTO ?:gmo_shipments ?e", $update_data);
        }
    }

}

//正常に登録できた場合にshipment_idをdbに格納
/**
 * @param $shipment_data
 * @param $order_info
 * @param $group_key
 * @param $all_products
 * @param $shipment_id
 */
function fn_gmo_deferred_payment_create_shipment_post($shipment_data, $order_info, $group_key, $all_products, $shipment_id)
{

    $gmo_transaction_id = db_get_field("SELECT gmoTransactionId FROM ?:gmo_shipments WHERE order_id = ?i", $order_info['order_id']);
    if(!empty($gmo_transaction_id)) {
        db_query("UPDATE ?:gmo_shipments SET shipment_id = ?i WHERE order_id = ?i", $shipment_id,$order_info['order_id']);
    }

}

//配送管理削除時
/**
 * @param $shipment_ids
 * @param $result
 */
function fn_gmo_deferred_payment_delete_shipments($shipment_ids, $result)
{

    if (!empty($shipment_ids)) {

        $order_id = db_get_field("SELECT order_id FROM ?:gmo_shipments WHERE shipment_id = ?i", $shipment_ids);
        $gmo_transaction_id = db_get_field("SELECT gmo_transaction_id FROM ?:orders WHERE order_id = ?i", $order_id);
        if(!empty($gmo_transaction_id)) {

            $order_info = fn_get_order_info($order_id, false, false);

            //支払方法情報取得
            $payment_id = $order_info['payment_id'];
            $processor_data = fn_get_payment_method_data($payment_id);
            $payment_params = $processor_data['processor_params'];

            //接続URL
            $connection_code = '5';
            $target_url = fn_gmo_deferred_connection_url($connection_code,$payment_params);

            $data = [
                'shopInfo' => [
                    'authenticationId' => $payment_params['gmo_certification_id'],
                    'shopCode' =>  $payment_params['gmo_merchant_code'],
                    'connectPassword' => $payment_params['gmo_connection_pass']
                ],
                'kindInfo' => [
                    'updateKind' => '2'
                ],
                'transaction' => [
                    'gmoTransactionId' => $gmo_transaction_id,
                    'invoiceIssueDate' => '' //null
                ]
            ];

            $gmo_response = fn_gmo_deferred_payment_curl($target_url, $data);

            //出荷報告キャンセル処理時にエラーが発生した場合
            if($gmo_response['result'] == 'NG') {
                fn_gmo_deferred_error_code($gmo_response['errors']);
                fn_redirect("orders.details?order_id=" . $order_info['order_id']);
                //出荷報告キャンセル処理が正常におこなわれた場合
            }else{
                fn_set_notification('N', __('notice'), __('gmo_cancel_shipment'));
                db_query("DELETE FROM ?:gmo_shipments WHERE shipment_id IN (?n)", $shipment_ids);
            }

        }
    }

}
