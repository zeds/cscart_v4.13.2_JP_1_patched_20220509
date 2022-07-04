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

// $Id: order_management.pre.php by sun from cs-cart.jp 2018

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$atone_cart = $_SESSION['cart'];

if(!empty($atone_cart['user_data']['user_id'])) {
	$pre = db_get_field('SELECT user_token FROM ?:users WHERE user_id = ?i', $atone_cart['user_data']['user_id']);
	$_SESSION['pre_token'] = $pre;
}

if (isset($atone_cart['payment_id'])) {
   $atone_payment_data = fn_get_payment_method_data($atone_cart['payment_id']);
}

if ( $mode == 'place_order'  ) {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
               
        if($action == 'save'){   
          //「保存」ボタン押下時の処理
                   
        }else{
          //「保存して支払処理を実施」ボタン押下時の処理
          if($atone_payment_data['template'] === "addons/atone/views/orders/components/payments/atone.tpl") {
            fn_atn_update_order();
          }	              
        }
    }
    
}else{
    if( $mode == 'update' ){
        $order_id = $atone_cart['order_id'];
        Tygh::$app['view']->assign('order_id', $order_id);
        
        if( !empty($order_id) ){
         if($atone_payment_data['template'] === "addons/atone/views/orders/components/payments/atone.tpl") {
         
			$private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');        
			$c_private_key = base64_encode($private_key);
			$basic = "Basic ".$c_private_key;

			$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
    
          	$result = fn_atn_reference($order_id, $basic, $atone_mode);
          
          	if(isset($result['transaction_options'])) {
				$r_options = $result['transaction_options'];
				foreach ($r_options as $r_option) {
					if($r_option == 3) {
						Tygh::$app['view']->assign('r_option', $r_option);
					}
				}
	
			}
           
         }

        }
             
    }elseif( $mode == 'registration' ){
        $order_id = $atone_cart['order_id'];
        fn_atn_register($order_id);
    }
}
