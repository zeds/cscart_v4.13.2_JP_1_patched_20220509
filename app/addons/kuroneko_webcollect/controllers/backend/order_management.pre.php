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
		$krnkwc_cart = Tygh::$app['session']['cart'];

        // 支払方法に紐付けられた決済代行サービスの情報を取得
        $krnkwc_processor_data = fn_krnkwc_get_processor_data_by_order_id($krnkwc_cart['order_id']);
        $krnkwc_processor_script = $krnkwc_processor_data['processor_script'];

		// 決済代行業者を使用した注文の場合
		if( !empty($krnkwc_processor_script) ){
			// 不要なデータが注文情報に反映されないように処理
			switch($krnkwc_processor_script){
                case 'krnkwc_cc.php':
                case 'krnkwc_ccreg.php':
                case 'krnkwc_cvs.php':
                case 'krnkab.php':
                case 'krnkwc_cctkn.php':
					unset($_REQUEST['payment_info']);
					break;
				default:
					// do nothing
			}
		}
	}
}else{
    // 注文の新規登録または編集の場合
    if( $mode == 'update' || $mode == 'add'){

        // 登録済みカード決済に用いるカード番号（マスク済み）を取得
        if( !empty(Tygh::$app['session']['cart']['user_data']['user_id']) ){
            $krnkwc_registered_card = fn_krnkwc_get_registered_card_info(Tygh::$app['session']['cart']['user_data']['user_id']);

            if( !empty($krnkwc_registered_card) ){
                Tygh::$app['view']->assign('krnkwc_registered_card', $krnkwc_registered_card);
            }
        }

        // 注文の編集の場合
        if( $mode == 'update' && !empty(Tygh::$app['session']['cart']['order_id']) ){
            // 当該注文の支払方法に関する情報を取得
            $krnkwc_order_id = Tygh::$app['session']['cart']['order_id'];
            $krnkwc_processor_data = fn_krnkwc_get_processor_data_by_order_id($krnkwc_order_id);
            $is_changable = fn_krnkwc_cc_is_changeable($krnkwc_order_id, $krnkwc_processor_data);

            if($is_changable){
                Tygh::$app['view']->assign('krnkwc_is_changable', 'Y');
            }
        }
    }
}
