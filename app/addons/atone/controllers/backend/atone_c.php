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

// $Id: atone_c.php by sun from cs-cart.jp 2018

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if( !empty($_REQUEST['order_ids']) ){
		$order_ids = $_REQUEST['order_ids'];

		switch($mode){
            case 'sales_settled':  // 売上確定
             foreach ($order_ids as $order_id) {
                fn_atn_sales_finalized($order_id);
             }
            break;
        
	        case 'cancel':    // キャンセル
              foreach ($order_ids as $order_id) {
                fn_atn_cancel_order($order_id);
              }
            break;
            
            case 'register': //本登録
            
              $private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');     
        	  $c_private_key = base64_encode($private_key);

        	  $basic = "Basic ".$c_private_key;
			  $atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
		
              foreach ($order_ids as $order_id) {
				$result = fn_atn_reference($order_id, $basic, $atone_mode);
        		fn_atn_register_c($order_id, $result);
              }
            break;
            
        default:
        // do nothing
        }
    }
	return array(CONTROLLER_STATUS_OK, "atone_c.manage");
}

$params = $_REQUEST;

switch($mode){
    case 'sales_settled':  // 売上確定
        $order_id = $_REQUEST['order_id'];
        fn_atn_sales_finalized($order_id);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;
        
	case 'cancel':    // キャンセル
        $order_id = $_REQUEST['order_id'];
        fn_atn_cancel_order($order_id);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;
        
	case 'manage':  // 一覧ページ

		$atone_total = db_get_field("SELECT COUNT(*) FROM ?:orders WHERE tr_id is NOT NULL");

		if( !empty($atone_total) ){
			list($orders, $search, $atone_total) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);
		}else{
			$orders = array();
		}
		
        $private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');        
        $c_private_key = base64_encode($private_key);

        $basic = "Basic ".$c_private_key;
        
		$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
			
		foreach ($orders as $order_data => $val) {

           $order_id = $val['order_id'];
           $result = fn_atn_reference($order_id, $basic, $atone_mode);      

           if(!isset($result['sales_settled_datetime'])) {
                $orders[$order_data]['sales_settled'] = "unsettled";
           }
           
           if(!empty($result['refunds'])) {
                $orders[$order_data]['refunds'] = "canceled";
           }
           
			if(isset($result['transaction_options'])) {
					$r_options = $result['transaction_options'];
					foreach ($r_options as $r_option) {
						if($r_option == 3) {
							Tygh::$app['view']->assign('r_option', $r_option);
							$orders[$order_data]['provisional'] = "provisional";
						}
					}
			}
		   
		}

		Registry::get('view')->assign('orders', $orders);
		Registry::get('view')->assign('search', $search);
		
		break;
		
	case 'register':    // 本登録
        $order_id = $_REQUEST['order_id'];
        //店舗秘密鍵
        $private_key = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "private_key"');     
        $c_private_key = base64_encode($private_key);

        //認証用キー
        $basic = "Basic ".$c_private_key;
        
        //atoneモード確認
		$atone_mode = db_get_field('SELECT value FROM ?:settings_objects WHERE name = "atone_mode"');
		
		$result = fn_atn_reference($order_id, $basic, $atone_mode);
        
        fn_atn_register_c($order_id, $result);
        
        return array(CONTROLLER_STATUS_REDIRECT);
        break;
	// その他
	default:
	// do nothing
}
