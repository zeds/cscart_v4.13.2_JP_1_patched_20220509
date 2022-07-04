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

use Tygh\Registry;

// $Id: credix_qc.php by tommy from cs-cart.jp 2015
// CREDIX決済（Quick Charge）

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でSMBCに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    // CREDIXに送信するパラメータをセット
    $params = fn_crdx_qc_get_params($order_info);
    $result = fn_crdx_send_request($params);

    // 正常にQuick Chargeが完了した場合
    if($result == 'Success_order' ){
        if (fn_check_payment_script('credix_qc.php', $order_id)) {
            $pp_response = array();
            $force_notification = array();

            // 割り当てる注文ステータスは「P（処理中）」
            $status_to = 'P';

            // 現在の注文ステータスを取得
            $current_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id);

            // 注文ステータスがすでに変更されている場合、メールによる通知は実施しない
            if($current_status == $status_to){
                $force_notification['C'] = false;
                $force_notification['A'] = false;
            }

            $pp_response['order_status'] = $status_to;
            fn_finish_payment($order_id, $pp_response, $force_notification);
            fn_order_placement_routines('route', $order_id, $force_notification);
        }
    }else{
        // 注文処理ページへリダイレクト
        fn_set_notification('E', __('jp_credix_cc_error'), __('jp_credix_cc_failed'));
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }
}
