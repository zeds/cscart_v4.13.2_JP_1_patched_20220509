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

// $Id: smbc_cctkn.php by tommy from cs-cart.jp 2017
// SMBCファイナンスサービス（クレジットカード決済）
// 2017/09 : トークン決済（シングルユース方式）に対応

// Modified by takahashi from cs-cart.jp 2020
// Chrome 80 以降対応

use Tygh\Registry;
use Tygh\Tools\SecurityHelper;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でSMBCに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

	// ACSからの3D認証結果が戻された場合
	if( !empty($_REQUEST['PaRes']) && $_REQUEST['process_type'] == '3dsecure_return' ){

		// SMBCファイナンスサービスに送信するパラメータをセット
		$params = array();
		$params = fn_smbcks_get_3dsecure_params($_REQUEST);
		$action = '3dsecure';

		// 支払方法に関する各種設定値が取得できていない場合
		if( empty($processor_data) ){
			// 支払方法に関するデータを取得
			$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = ?s AND ?:payments.status = 'A'", 'smbc_cctkn.php');
			$processor_data = fn_get_processor_data($payment_id);
		}

    // その他の場合
    }else{

        // 処理タイプを初期化
        $type = 'cc';

        // 注文編集の場合
        if( Registry::get('runtime.mode') == 'place_order' && Registry::get('runtime.controller') == 'order_management') {
            // 減額処理判定
            list($is_cc_changeable, $change_type) = fn_smbcks_cc_is_changeable($order_id, $order_info, $processor_data);

            // 減額処理の場合
            if ($is_cc_changeable) {
                // 処理タイプを減額処理に変更
                $type = $change_type;
            // 通常のカード決済の場合
            } else {
                // エラーメッセージを表示して処理を終了
                fn_set_notification('E', __('warning'), __('jp_smbc_cc_data_not_sent'));
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }
        }

        // 処理タイプが減額処理の場合
        if($type == 'cc_change' || $type == 'cc_change_sales'){
            // 与信・売上済金額を取得
            $org_amount = fn_smbcks_get_auth_amount($order_id);
            fn_smbcks_send_cc_request($order_id, $type, $org_amount);
            return true;
        // 処理タイプがカード決済の場合
        }else{
            // トークン発行でエラーが発生している場合
            if( !empty($order_info['payment_info']['token_error_detail']) ){
                // エラーメッセージを表示して処理を終了
                fn_set_notification('E', __('jp_smbc_cc_error'), SecurityHelper::sanitizeHtml($order_info['payment_info']['token_error_detail']));
                $return_url = fn_lcjp_get_error_return_url();
                fn_redirect($return_url, true);
            }

            // SMBCファイナンスサービスに送信するパラメータをセット
            $params = array();
            $params = fn_smbcks_get_params('cc', $order_id, $order_info, $processor_data);
            $action = 'checkout';
        }
    }

	// オーソリ依頼
	$return_val = fn_smbcks_send_request($params, $processor_data, $action);
	$response = $return_val['response'];
	$request = $return_val['request'];

	// リクエスト送信が正常終了した場合
	if (!PEAR::isError($response)) {

		// 応答内容の解析  
		$res_content = $request->getResponseBody();

		// SMBCファイナンスサービスから受信した請求情報を配列に格納
		$smbc_results = fn_smbcks_get_result_array($res_content);

		// 3Dセキュア認証情報を受け取った場合
		if( strcmp($smbc_results['rescd'], '000100') === 0){

			$pareq = $smbc_results['pareq'];
			$md = $smbc_results['shoporder_no'];
			$smbc_sess = $smbc_results['sessionid'];
			$term_url = fn_url("payment_notification.process&payment=smbc_cctkn&order_id=$order_id&process_type=3dsecure_return&smbcsess=$smbc_sess", AREA, 'current');

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2021 BOF
            // Chrome 80 以降対応
            ///////////////////////////////////////////////
            /** @var \Tygh\Web\Session $session */
            $session = Tygh::$app['session'];
            $term_url = fn_link_attach($term_url, $session->getName() . '=' . $session->getID());
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2021 EOF
            ///////////////////////////////////////////////

			$issuer_url = $smbc_results['issuer_url'];
echo <<<EOT
<html>
<body onLoad="document.process.submit();">
<form action="{$issuer_url}" method="POST" name="process">
	<input type="hidden" name="PaReq" value="{$pareq}" />
	<input type="hidden" name="TermUrl" value="{$term_url}" />
	<input type="hidden" name="MD" value="{$md}" />
EOT;

$msg = __('jp_smbc_cc_redirect_acs');
echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
exit;
		}

		// 3Dセキュアによる決済の場合、注文IDをセット
		if( $_REQUEST['process_type'] == '3dsecure_return' && !empty($_REQUEST['smbcsess']) && empty($order_id) ){
			$order_id = (int)$_REQUEST['order_id'];
			$order_info = fn_get_order_info($order_id);
		}

		// オーソリでエラーが発生している場合
		if( strcmp($smbc_results['rescd'], '000000') !== 0){

			// カード決済エラー時に、注文データに格納されていた支払情報のうち、SMBCカード決済に関するデータのみ残す
			if( !empty($order_id) ) fn_smbcks_filter_payment_info($order_id);

			// 注文処理ページへリダイレクト
			fn_set_notification('E', __('jp_smbc_cc_error'), __('jp_smbc_cc_failed') . '<br />' . $smbc_results['rescd'] . ' : ' . $smbc_results['res']);
			$return_url = fn_lcjp_get_error_return_url();
			fn_redirect($return_url, true);

		// オーソリが正常に完了した場合
		}else{
			// クレジットカード情報お預かり機能を利用する場合
			if( $order_info['payment_info']['register_card_info'] == 'true' ){
				// クレジットカード情報を登録
				fn_smbcks_register_cc_info($order_info['user_id'], $processor_data, $smbc_results['kessai_no']);
			}

			if (fn_check_payment_script('smbc_cctkn.php', $order_id)) {
				// DBに保管する支払い情報を生成
				fn_smbcks_format_payment_info('cc', $order_id, $order_info['payment_info'], $smbc_results);

				// 注文処理ページへリダイレクト
				$pp_response = array();
				$pp_response['order_status'] = 'P';
				fn_finish_payment($order_id, $pp_response);
				fn_smbcks_update_cc_status_code($order_id, 'AUTH_OK');
				fn_order_placement_routines('route', $order_id);
			}
		}
	// リクエスト送信が異常終了した場合
	}else{
		// カード決済エラー時に、注文データに格納されていた支払情報のうち、SMBCカード決済に関するデータのみ残す
		if( !empty($order_id) ) fn_smbcks_filter_payment_info($order_id);

		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('jp_smbc_cc_error'), __('jp_smbc_cc_invalid'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	}
}
