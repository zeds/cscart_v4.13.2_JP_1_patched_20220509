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

// $Id: result_sc.php by tommy from cs-cart.jp 2015

use Tygh\Registry;

// オリコからのIPアドレスのみ処理を許可
if( preg_match('/^210\.239\.44\.160/', $_SERVER['REMOTE_ADDR']) ) {

    ////////////////////////////////////////////////
    // CS-Cartのクラスや関数を利用可能に BOF
    ////////////////////////////////////////////////
    define('AREA', 'C');
    require '../../init.php';
    require_once(Registry::get('config.dir.addons') . 'oricopp_sw/func.php');
    require_once(Registry::get('config.dir.addons') . 'localization_jp/func.php');
    define('OPPSW_DATE_LENGTH', 12);
    ////////////////////////////////////////////////
    // CS-Cartのクラスや関数を利用可能に EOF
    ////////////////////////////////////////////////

    // 通知件数パラメータが存在しない場合
    if( empty($_REQUEST['numberOfNotify']) ){
        // エラーメッセージを表示して処理を終了
        die('ERROR - NO ORDER ID');

    // 通知件数パラメータが存在する場合
    }else{
        // 通知件数を取得
        $number_of_notify = (int)$_REQUEST['numberOfNotify'];

        // 通知件数の数だけ処理を実施
        for($cnt = 0; $cnt < $number_of_notify; $cnt++){
            // 4桁の連番を取得
            $num = sprintf("%04d", $cnt);

            // 注文IDを取得
            $order_id = substr($_REQUEST['orderId' . $num], 0, strlen($_REQUEST['orderId' . $num]) - OPPSW_DATE_LENGTH);

            // 注文IDから該当するcompany_idをセット
            fn_payments_set_company_id($order_id);

            // 現在の注文ステータスを取得
            $current_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id);

            // 審査結果が承認(04)の場合
            if( $_REQUEST['orderStateCode' . $num] == '04'){
                // 注文ステータスに「P（処理中）」をセット
                $status_to = 'P';
            // その他の場合
            }else{
                // 注文ステータスに「F（失敗）」をセット
                $status_to = 'F';
            }

            // 注文ステータスがすでに「P（処理中）」または「F（失敗）」に変更されている場合、メールによる通知は実施しない
            $force_notification = array();
            if($current_status == $status_to){
                $force_notification['C'] = false;
                $force_notification['A'] = false;
            }

            // 注文ステータスを変更
            fn_change_order_status($order_id, $status_to, '', $force_notification);

            // 処理対象となる注文ID群を取得
            $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

            // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
            foreach($order_ids_to_process as $order_id){

                // 注文データ内の支払関連情報を取得
                $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

                // 注文データ内の支払関連情報が存在する場合
                if( !empty($payment_info) ){
                    $flg_payment_info_exists = true;

                    // 支払情報が暗号化されている場合は復号化して変数にセット
                    if( !is_array($payment_info)) {
                        $info = @unserialize(fn_decrypt_text($payment_info));
                    }else{
                        // 支払情報を変数にセット
                        $info = $payment_info;
                    }

                // 注文データ内の支払関連情報が存在しない場合
                }else{
                    $flg_payment_info_exists = false;
                    $info = array();
                }

                // 支払方法に応じた結果通知内容を注文情報に記録
                fn_oppsw_set_result_info($info, $num, 'sc', $_REQUEST);

                // 支払情報を暗号化
                $_data = fn_encrypt_text(serialize($info));

                // 注文データ内の支払関連情報が存在する場合
                if( $flg_payment_info_exists ){
                    // 注文データ内の支払関連情報を上書き
                    db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);

                // 注文データ内の支払関連情報が存在しない場合
                }else{
                    // 注文データ内の支払関連情報を追加
                    $insert_data = array (
                        'order_id' => $order_id,
                        'type' => 'P',
                        'data' => $_data,
                    );
                    db_query("REPLACE INTO ?:order_data ?e", $insert_data);
                }
            }
        }
    }
}else{
    echo "INVALID ACCESS!!";
}
