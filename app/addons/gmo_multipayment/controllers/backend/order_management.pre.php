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

// $Id: order_management.pre.php by tommy from cs-cart.jp 2016

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// 注文情報の保存のみ行い決済処理は実施しない場合
	if( $mode == 'place_order' && $action == 'save' ){
		// 決済代行業者のIDを取得
		$smbc_cart = Tygh::$app['session']['cart'];
		$smbc_payment_data = fn_get_payment_method_data($smbc_cart['payment_id']);
		$smbc_processor_id = $smbc_payment_data['processor_id'];

		// 決済代行業者を使用した注文の場合
		if( !empty($smbc_processor_id) ){
			// 不要なデータが注文情報に反映されないように処理
			switch($smbc_processor_id){
				case '9041': // コンビニ受付番号決済
				case '9042': // 銀行振込
				case '9043': // 払込票決済
					unset($_REQUEST['payment_info']);
					break;
				default:
					// do nothing
			}
		}
	}
}else{
    // 登録済みカード決済に用いるカード番号（マスク済み）を取得
    if( $mode == 'update' ){
        if( !empty(Tygh::$app['session']['cart']['user_data']['user_id']) ){
            //$registered_card = fn_smbcks_get_registered_card_info(Tygh::$app['session']['cart']['user_data']['user_id']);
            if( !empty($registered_card) ){
                Registry::get('view')->assign('registered_card', $registered_card);
            }
        }

        if( !empty(Tygh::$app['session']['cart']['order_id']) ){
            // 当該注文の支払方法に関する情報を取得
            $gmomp_order_id = Tygh::$app['session']['cart']['order_id'];
            $gmomp_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $gmomp_order_id);
            $gmomp_processor_data = fn_get_processor_data($gmomp_payment_id);

            $is_changable = fn_gmomp_cc_is_changeable($gmomp_order_id, $gmomp_processor_data);

            if($is_changable){
                Registry::get('view')->assign('gmomp_is_changable', 'Y');
            }
        }
    }
}
