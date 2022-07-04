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

// $Id: stockcheck.php by takahashi from cs-cart.jp 2018

use Tygh\Registry;

// ソニーペイメントからのIPアドレスのみ処理を許可
if( preg_match('/^211\.128\.98\.141/', $_SERVER['REMOTE_ADDR']) || preg_match('/^211\.128\.98\.133/', $_SERVER['REMOTE_ADDR']) ){

    // 初期設定
    define('AREA', 'C');
    require '../../init.php';
    require_once(Registry::get('config.dir.addons') . 'sonypayment_carrier/func.php');

    $return_code = 'OK';

    // ソニーペイメントから正常な在庫通知データを受信した場合
    if( fn_sonyc_validate_notification($_POST) ){

        // CS-Cartの注文IDを抽出
        $order_id = fn_sonyc_get_order_id($_REQUEST['ProcNo']);

        // 注文IDから該当するcompany_idをセット
        fn_payments_set_company_id($order_id);

        // 注文情報を抽出
        $order_info = db_get_row("SELECT user_id, total, status FROM ?:orders WHERE order_id = ?i", $order_id);

        // CS-Cartに該当する注文データが存在しない場合
        if( empty($order_info) ){
            // 処理を終了
            echo 'InventoryCheckResult=NG';
            exit;
        }

        // 処理対象となる注文ID群を取得
        $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

        // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
        foreach($order_ids_to_process as $order_id_to_process){
            $products = db_get_array("SELECT product_id, amount, extra FROM ?:order_details WHERE order_id = ?i", $order_id_to_process);

            // 各注文商品の在庫数を確認
            foreach( $products as $product ) {

                // ダウンロード商品のフラグを取得
                $is_edp = db_get_field("SELECT is_edp FROM ?:products WHERE product_id =?i", $product['product_id']);

                $cart = null;
                $extra = unserialize($product['extra']);

                // 在庫確認
                $amount = fn_check_amount_in_stock($product['product_id'], $product['amount'], $extra['product_options'], 0, $is_edp, 0, $cart);

                // 在庫がない場合
                if (!$amount || $amount < $product['amount']) {
                    // 結果にNGをセット
                    $return_code = 'NG';
                    break;
                }
            }
        }
    }
    // 処理を終了
    echo 'InventoryCheckResult='.$return_code;
    exit;
}