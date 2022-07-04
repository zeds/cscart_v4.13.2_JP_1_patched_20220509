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

// $Id: notify.php by takahashi from cs-cart.jp 2018

use Tygh\Registry;

// ソニーペイメントからのIPアドレスのみ処理を許可
if( preg_match('/^211\.128\.98\.141/', $_SERVER['REMOTE_ADDR']) || preg_match('/^211\.128\.98\.133/', $_SERVER['REMOTE_ADDR']) ){

	// 初期設定
	define('AREA', 'C');
	require '../../init.php';
	require_once(Registry::get('config.dir.addons') . 'sonypayment_carrier/func.php');

	// ソニーペイメントから正常な状態通知データを受信した場合
	if( fn_sonyc_validate_notification($_POST) ){

        // ソニーペイメントより処理結果が返された場合
        if (!empty($_REQUEST['TransactionId'])) {
            // CS-Cartの注文IDを抽出
            $order_id = fn_sonyc_get_order_id($_REQUEST['ProcNo']);

            if (fn_check_payment_script('sonypayment_carrier_ep.php', $order_id)) {

                // 処理対象となる注文ID群を取得
                $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

                // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
                foreach($order_ids_to_process as $order_id_to_process) {
                    // 状態通知がOKの場合
                    if ($_REQUEST['ResponseCd'] == 'OK') {

                        $status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id_to_process);

                        if($status != 'P'){
                            // 後続処理のための ProcessId と ProcessPass をDBに保存
                            fn_sonyc_update_set_process_info($order_id_to_process, $_REQUEST);

                            // DBに保管する支払い情報を生成
                            fn_sonyc_format_payment_info('ep', $order_id_to_process, null, $_REQUEST);

                            // 注文処理
                            db_query("UPDATE ?:orders SET status = ?s WHERE order_id = ?i", 'P', $order_id_to_process);
                            fn_sonyc_update_status_code($order_id_to_process, $_REQUEST['OperateId']);
                        }

                    } // 状態通知がエラーの場合
                    else {
                        // DBに保管する支払い情報を生成
                        fn_sonyc_format_payment_info('ep', $order_id_to_process, null, $_REQUEST);

                        // 注文処理
                        $pp_response = array();
                        $pp_response['order_status'] = 'N';
                        fn_finish_payment($order_id_to_process, $pp_response);
                    }
                }
            }
            elseif (fn_check_payment_script('sonypayment_carrier_rb.php', $order_id)) {

                // 処理対象となる注文ID群を取得
                $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

                // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
                foreach($order_ids_to_process as $order_id_to_process) {
                    // 状態通知がOKの場合
                    if ($_REQUEST['ResponseCd'] == 'OK') {

                        $status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id_to_process);

                        if($status != 'P') {
                            // 後続処理のための ProcessId と ProcessPass をDBに保存
                            fn_sonyc_update_set_process_info($order_id_to_process, $_REQUEST);

                            // DBに保管する支払い情報を生成
                            fn_sonyc_format_payment_info('rb', $order_id_to_process, null, $_REQUEST);

                            // 注文処理ページへリダイレクト
                            db_query("UPDATE ?:orders SET status = ?s WHERE order_id = ?i", 'P', $order_id_to_process);
                        }
                        
                    } // 状態通知がエラーの場合
                    else {
                        // DBに保管する支払い情報を生成
                        fn_sonyc_format_payment_info('rb', $order_id_to_process, null, $_REQUEST);

                        // 注文処理ページへリダイレクト
                        $pp_response = array();
                        $pp_response['order_status'] = 'N';
                        fn_finish_payment($order_id_to_process, $pp_response);
                    }
                }
            }
        }

	}
    // 処理を終了
    echo '<BODY>OK</BODY>';
    exit;
}
