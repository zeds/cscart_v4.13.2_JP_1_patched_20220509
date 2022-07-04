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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// 注文情報の保存のみ行い決済処理は実施しない場合
	if( $mode == 'place_order' && $action == 'save' ){
		// 決済代行業者のIDを取得
		$digital_check_cart = Tygh::$app['session']['cart'];
		$digital_check_payment_data = fn_get_payment_method_data($digital_check_cart['payment_id']);
		$digital_check_processor_id = $digital_check_payment_data['processor_id'];

		// 決済代行業者を使用した注文の場合
		if( !empty($digital_check_processor_id) ){
			// 不要なデータが注文情報に反映されないように処理
			switch($digital_check_processor_id){
				case '9082': // コンビニ決済
				case '9083': // MobileEdy決済
				case '9084': // CyberEdy決済
				case '9085': // リンクタイプ・カード決済
					unset($_REQUEST['payment_info']);
					break;
				default:
					// do nothing
			}
		}
	}
}
