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

// Modified by tommy from cs-cart.jp 2016

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    // 注文手続きページ
    if( $mode == 'checkout' ){
        // 登録済みカード情報を取得
        $registered_card_info = fn_pygnt_get_registered_card_info($auth['user_id']);

        // 登録済みカード情報が存在する場合
        if( !empty($registered_card_info) ){
            // 登録済みカード情報をSmarty変数にセット
            Tygh::$app['view']->assign('registered_card', $registered_card_info);
        }

    // 注文手続き完了ページ
    }elseif( $mode == 'complete' ){

        // 注文に利用した決済代行サービスのスクリプトファイル名を取得
        $payment_script_name = fn_pygnt_get_processor_script_name_by_order_id($_REQUEST['order_id']);

        // ペイジェントを利用した決済方法の場合、決済情報差分照会を実施
        if( !empty($payment_script_name) && strpos($payment_script_name, 'paygent_') !== false ){
            fn_pygnt_chk_status_change();
        }
    }
}