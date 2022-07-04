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

// $Id: smartlink_daiko.php by tommy from cs-cart.jp 2014
// スマートリンクネットワーク（オンライン代行収納サービス）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でスマートリンクに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    // データ変更可否判定
    $is_daiko_changeable = fn_sln_daiko_is_changeable($order_id);

    // 注文編集の場合
    if( Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {
        // データ変更の場合
        if( $is_daiko_changeable ){
            $type = 'daiko_change';
        // すでに1度データ変更が行われているか、データが削除されている場合
        }else{
            // エラーメッセージを表示して処理を中断
            fn_set_notification('E', __('jp_sln_daiko_error'), __('jp_sln_daiko_not_changeable'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }
    // 通常の注文処理の場合
    }else{
        $type = 'daiko';
    }

    // スマートリンクに送信するパラメータをセット
    $params = fn_sln_get_params($type, $order_info, $processor_data);

    $action = 'daiko';

	// 収納代行データ登録
	$result_params = fn_sln_send_request($params, $processor_data, $action);

	// スマートリンクより処理結果が返された場合
	if ( !empty($result_params['TransactionId']) ) {

		// 処理でエラーが発生している場合
		if( $result_params['ResponseCd'] != 'OK' ){
			// 注文処理ページへリダイレクト
			fn_set_notification('E', __('jp_sln_daiko_error'), __('jp_sln_daiko_failed') . '<br />' . __('jp_sln_daiko_error_code') . ' : ' . $result_params['ResponseCd']);
			$return_url = fn_lcjp_get_error_return_url();
			fn_redirect($return_url, true);

		// 収納代行データ登録が正常に完了した場合
		}else{
            if (fn_check_payment_script('smartlink_daiko.php', $order_id)) {
                // 後続処理のための ProcessId と ProcessPass をDBに保存
                fn_sln_update_set_process_info($order_id, $result_params);

                // 支払期限がCS-Cart側で指定されている場合は支払情報として記録
                if( !empty($params['PayLimit']) ) $result_params['PayLimit'] = $params['PayLimit'];

                // DBに保管する支払情報を生成
                fn_sln_format_payment_info('daiko', $order_id, $order_info['payment_info'], $result_params, true);

                // リダイレクト用コードをセッション変数にセット
                if($type == 'daiko' && !empty($result_params['FreeArea']) ) Tygh::$app['session']['SLN_CODE'] = trim($result_params['FreeArea']);

				// 注文処理ページへリダイレクト
				$pp_response = array();
				$pp_response['order_status'] = 'O';
				fn_finish_payment($order_id, $pp_response);
                fn_sln_update_cc_status_code($order_id, $result_params['OperateId']);
				fn_order_placement_routines('route', $order_id);
			}
		}
	// リクエスト送信が異常終了した場合
	}else{
		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('jp_sln_daiko_error'), __('jp_sln_daiko_invalid'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	}
}
