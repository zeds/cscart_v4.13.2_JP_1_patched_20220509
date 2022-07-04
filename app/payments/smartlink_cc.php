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

// $Id: smartlink_cc.php by tommy from cs-cart.jp 2015
// スマートリンクネットワーク（クレジットカード決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でスマートリンクに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    // 減額処理判定
    $is_cc_changeable = fn_sln_cc_is_changeable($order_id, $order_info, $processor_data);

    // 注文編集の場合
    if( Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {
        // 減額処理の場合
        if( $is_cc_changeable ){
            $type = 'cc_change';
        // 減額処理以外でセキュリティコードが入力されていない場合
        }elseif( $processor_data['processor_params']['use_cvv'] == 'true' && empty($order_info['payment_info']['cvv2']) ){
            // エラーメッセージを表示して処理を中断
            fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_cc_failed') . '<br />' . __('jp_sln_security_code_is_empty'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        // その他の場合
        }else{
            $type = 'cc';
        }
    // 通常の注文処理の場合
    }else{
        $type = 'cc';
    }

    // スマートリンクに送信するパラメータをセット
    $params = fn_sln_get_params($type, $order_info, $processor_data);
    $action = 'checkout';

	// オーソリ依頼
	$result_params = fn_sln_send_request($params, $processor_data, $action);

    // スマートリンクより処理結果が返された場合
    if ( !empty($result_params['TransactionId']) ) {

		// 処理でエラーが発生している場合
		if( $result_params['ResponseCd'] != 'OK' ){
			// 注文処理ページへリダイレクト
            $sln_err_msg = fn_sln_get_err_msg($result_params['ResponseCd']);
			fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_cc_failed') . '<br />' . $sln_err_msg);
			$return_url = fn_lcjp_get_error_return_url();
			fn_redirect($return_url, true);

		// オーソリが正常に完了した場合
		}else{
            if (fn_check_payment_script('smartlink_cc.php', $order_id)) {
                // 後続処理のための ProcessId と ProcessPass をDBに保存
                fn_sln_update_set_process_info($order_id, $result_params);

                // DBに保管する支払い情報を生成
                fn_sln_format_payment_info('cc', $order_id, $order_info['payment_info'], $result_params);

                // クレジットカード情報お預かり機能を利用する場合（減額処理時は除く）
                if( $order_info['payment_info']['register_card_info'] == 'true' && !empty($order_info['user_id']) && $type != 'cc_change' ){
                    // クレジットカード情報を登録
                    fn_sln_register_cc_info($order_info, $processor_data);
                }

                // 注文処理ページへリダイレクト
                $pp_response = array();
                $pp_response['order_status'] = 'P';
                fn_finish_payment($order_id, $pp_response);
                fn_sln_update_cc_status_code($order_id, $result_params['OperateId']);
                fn_order_placement_routines('route', $order_id);
            }
		}
	// リクエスト送信が異常終了した場合
	}else{
		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('jp_sln_cc_error'), __('jp_sln_cc_invalid'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	}
}
