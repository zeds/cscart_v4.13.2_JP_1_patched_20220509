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

// ゼウスからのIPアドレスのみ処理を許可
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


	// ゼウスから決済完了データが送信された場合
	if( !empty($_REQUEST['result']) && $_REQUEST['result'] == 'OK' && !empty($_REQUEST['sendid']) ){

		/////////////////////////////////////////////////////////////////////////
		// 注文データの存在チェック BOF
		/////////////////////////////////////////////////////////////////////////
		// 注文IDと顧客IDを取得
		$zeus_info = explode(ZEUS_SEPARATOR, $_REQUEST['sendid']);

		if( sizeof($zeus_info) != 2 ){
			// エラーメッセージを返す
			echo 'NG, INVALID SENDID';
			exit;
		}

		$zeus_order_id = $zeus_info[0];
		$zeus_user_id = $zeus_info[1];

        // 注文IDから該当するcompany_idをセット
        fn_payments_set_company_id($zeus_order_id);

		// 注文情報を抽出
		$order_info = fn_get_order_info($zeus_order_id);

		// ゼウスの設定情報を取得
		$zeus_settings = fn_get_payment_method_data($order_info['payment_id']);

		// 受信したIPコードとCS-Cartに登録したIPコードが異なる場合
		if( $_REQUEST['clientip'] != $zeus_settings['processor_params']['clientip'] ){
			// エラーメッセージを返す
			echo 'NG, IP CODE DOES NOT MATCH';
			exit;
		}

		// CS-Cartに該当する注文データが存在しない場合
		if( empty($order_info) ){
			// エラーメッセージを返す
			echo 'NG, NO ORDER DATA FOUND,' . $zeus_order_id;
			exit;
		}

		// ゲスト購入でCS-Cart内の注文データとゼウスから送信されたデータの内容が一致しない場合
		if( $zeus_user_id == 0 ){
			if( $order_info['user_id'] != 0 || $order_info['total'] != $_REQUEST['money'] ){
				// エラーメッセージを返す
				echo 'NG,INVALID DATA RECEIVED1';
				exit;
			}

		// 通常購入でCS-Cart内の注文データとゼウスから送信されたデータの内容が一致しない場合
		}else{

			if( ($order_info['user_id'] != $zeus_user_id) || ($order_info['total'] != $_REQUEST['money']) ){
				// エラーメッセージを返す
				echo 'NG,INVALID DATA RECEIVED';
				exit;
			}

		}
		/////////////////////////////////////////////////////////////////////////
		// 注文データの存在チェック EOF
		/////////////////////////////////////////////////////////////////////////

		// 正常終了した場合
		if (fn_check_payment_script('zeus.php', $zeus_order_id)) {

			$pp_response = array();

			// 注文ステータスをセット
			$pp_response['order_status'] = 'P';

			// 支払内容をコメント欄などに追記
			fn_zeus_add_notes($zeus_order_id, $_REQUEST['ordd']);

			Tygh::$app['session']['zeus_process_order'] = 'Y';

			if($order_info['status'] == 'N') {
				fn_finish_payment($zeus_order_id, $pp_response);
				fn_order_placement_routines('route', $zeus_order_id);
			}
		}

		echo 'OK';
		exit;
	}

	echo "INVALID ACCESS!!";

}else{
	echo "INVALID ACCESS!!";
}




// 支払内容をスタッフメモ欄に追記
function fn_zeus_add_notes($zeus_order_id, $ordd)
{
    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($zeus_order_id);

    // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
    foreach($order_ids_to_process as $order_id){
        // 登録された注文データを抽出
        $order_details = db_get_field("SELECT details FROM ?:orders WHERE order_id = ?i", $order_id);

        $zeus_notes = "\n" . __('jp_zeus_notes_header') . "\n" . __('jp_zeus_ordd') . " ： " . $ordd;

        // スタッフメモ
        $details = $order_details . $zeus_notes;

        // 文頭の改行は削除
        $data = array('details' => ltrim($details));

        db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id);
    }
}
