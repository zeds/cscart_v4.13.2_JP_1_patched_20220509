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

	// 注文情報の保存
	if( $mode == 'place_order' ) {

        $processor_script = '';

        // 決済代行業者のIDを取得
        $paygent_cart = Tygh::$app['session']['cart'];
        $paygent_payment_data = fn_get_payment_method_data($paygent_cart['payment_id']);


        // 決済代行サービスを利用した決済の場合
        if (!empty($paygent_payment_data['processor_id'])) {
            // 決済代行サービスのスクリプト名を取得
            $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id= ?i", $paygent_payment_data['processor_id']);
        }

        // 注文情報の保存のみ行い、決済代行サービスへの接続は行わない場合
        if($action == 'save'){

            // 決済代行サービスのスクリプト名が存在する場合
            if( !empty($processor_script) ){
                // 不要なデータが注文情報に反映されないように処理
                switch ($processor_script) {
                    case 'paygent_cc.php':       // カード決済
                    case 'paygent_ccreg.php':   // 登録済カード決済
                    case 'paygent_mc.php':       // 多通貨カード決済
                    case 'paygent_mcreg.php':   // 多通貨登録済カード決済
                    case 'paygent_cvs.php':     // コンビニ決済
                        unset($_REQUEST['payment_info']);
                        break;
                    default:
                        // do nothing
                }
            }

        // 決済代行サービスへの接続も行う場合
        }else{
            // 支払方法の変更内容に応じてバリデーションやメッセージの表示を行う
            fn_pygnt_validate_order_update(Tygh::$app['session']['cart']['order_id'], $processor_script);
        }
    }
}else{
    // 注文の編集の場合
    if( $mode == 'update' ){
        // 既存の注文IDが存在する場合
        if( !empty(Tygh::$app['session']['cart']['order_id']) ){
            // 当該注文の支払方法に関する情報を取得
            $paygent_order_id = Tygh::$app['session']['cart']['order_id'];
            $paygent_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $paygent_order_id);
            $paygent_processor_data = fn_get_processor_data($paygent_payment_id);
            $is_changable = fn_pygnt_cc_is_changeable($paygent_order_id, $paygent_processor_data);
            if($is_changable){
                Registry::get('view')->assign('paygent_is_changable', 'Y');
            }
        }
    }
}
