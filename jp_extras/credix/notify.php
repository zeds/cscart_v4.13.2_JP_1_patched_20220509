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

// $Id: notify.php by tommy from cs-cart.jp 2016

use Tygh\Registry;

// CREDIXからのIPアドレスのみ処理を許可
if( preg_match('/^210\.164\.6\.67/', $_SERVER['REMOTE_ADDR']) || preg_match('/^202\.221\.139\.50/', $_SERVER['REMOTE_ADDR']) ){

	////////////////////////////////////////////////
	// CS-Cartのクラスや関数を利用可能に BOF
	////////////////////////////////////////////////
	define('AREA', 'C');
	require '../../init.php';
	require_once(Registry::get('config.dir.addons') . 'localization_jp/config.php');
	////////////////////////////////////////////////
	// CS-Cartのクラスや関数を利用可能に EOF
	////////////////////////////////////////////////

	// CREDIXから決済完了データが送信された場合
	if( !empty($_REQUEST['result']) && $_REQUEST['result'] == 'ok' && !empty($_REQUEST['sendpoint']) ){

		/////////////////////////////////////////////////////////////////////////
		// 注文データの存在チェック BOF
		/////////////////////////////////////////////////////////////////////////
		// 注文IDと顧客IDを取得
		$credix_info = explode(CREDIX_SEPARATOR, $_REQUEST['sendpoint']);

		if( sizeof($credix_info) != 2 ){
			// エラーメッセージを返す
			echo 'NG, INVALID SENDID';
			exit;
		}

		$credix_order_id = $credix_info[0];
		$credix_user_id = $credix_info[1];

        // 注文IDから該当するcompany_idをセット
        fn_payments_set_company_id($credix_order_id);

		// 注文情報を抽出
		$order_info = fn_get_order_info($credix_order_id);

		// CREDIXの設定情報を取得
		$credix_settings = fn_get_payment_method_data($order_info['payment_id']);

		// 受信したIPコードとCS-Cartに登録したIPコードが異なる場合
		if( $_REQUEST['clientip'] != Registry::get('addons.credix.ip') ){
			// エラーメッセージを返す
			echo 'NG, IP CODE DOES NOT MATCH';
			exit;
		}

		// CS-Cartに該当する注文データが存在しない場合
		if( empty($order_info) ){
			// エラーメッセージを返す
			echo 'NG, NO ORDER DATA FOUND,' . $credix_order_id;
			exit;
		}

		// ゲスト購入でCS-Cart内の注文データとCREDIXから送信されたデータの内容が一致しない場合
		if( $credix_user_id == 0 ){
			if( $order_info['user_id'] != 0 || $order_info['total'] != $_REQUEST['money'] ){
				// エラーメッセージを返す
				echo 'NG,INVALID DATA RECEIVED1';
				exit;
			}

		// 通常購入でCS-Cart内の注文データとCREDIXから送信されたデータの内容が一致しない場合
		}else{
			if( ($order_info['user_id'] != $credix_user_id) || ($order_info['total'] != $_REQUEST['money']) ){
				// エラーメッセージを返す
				echo 'NG,INVALID DATA RECEIVED';
				exit;
			}
		}
		/////////////////////////////////////////////////////////////////////////
		// 注文データの存在チェック EOF
		/////////////////////////////////////////////////////////////////////////

		// 正常終了した場合
		if (fn_check_payment_script('credix_cc.php', $credix_order_id)) {

            // 登録済みカード決済用ID（sendid)をセット
            if( !empty($_REQUEST['sendid']) && strlen($_REQUEST['sendid']) == 25 ){
                $_data = array('user_id' => $credix_user_id,
                                'payment_method' => 'credix',
                                'quickpay_id' => $_REQUEST['sendid']);

                db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);
            }

			$pp_response = array();

			// 注文ステータスをセット
			$pp_response['order_status'] = 'P';

            Tygh::$app['session']['credix_process_order'] = 'Y';

			if($order_info['status'] == 'N') {
				fn_finish_payment($credix_order_id, $pp_response);
				fn_order_placement_routines('route', $credix_order_id);
			}
		}

		echo 'OK';
		exit;
	}

	echo "INVALID ACCESS!!";


}else{
	echo "INVALID ACCESS!!";
}
