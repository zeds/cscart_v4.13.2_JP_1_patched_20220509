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

// $Id: set_result.php by tommy from cs-cart.jp 2013


// ルミーズからのIPアドレスのみ処理を許可
if( preg_match('/^211\.0\.149\./', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^61\.119\.27\./', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^124\.155\.127\./', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^183\.177\.132\./', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^210\.248\.158\./', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^210\.160\.253\./', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^3\.115\.50\.231/', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^3\.113\.156\.98/', $_SERVER['REMOTE_ADDR'])
){
	define('AREA', 'C');
	define('REMISE_CC_DATE_LENGTH', 12);
	require '../../init.php';

	// 決済処理が正常終了している場合
	if( $_REQUEST['X-ERRLEVEL'] == 0 ){
		$torihiki_no = $_REQUEST['X-S_TORIHIKI_NO'];
		$torihiki_no_len = strlen($torihiki_no);

		$payquick_id = $_REQUEST['X-PAYQUICKID'];
		$payquick_id_len = strlen($payquick_id);


		// 請求番号とペイクイックIDが正しくセットされている場合
		if($torihiki_no_len > REMISE_CC_DATE_LENGTH && $payquick_id_len == 20){

			// 請求番号からCS-Cartの注文番号を抽出
			$order_id_len = $torihiki_no_len - REMISE_CC_DATE_LENGTH;
			$order_id = substr($torihiki_no, 0, $order_id_len);

            // 注文IDから該当するcompany_idをセット
            fn_payments_set_company_id($order_id);

			$user_id = db_get_field("SELECT user_id FROM ?:orders WHERE order_id =?i", (int)$order_id);

			// ゲスト購入以外の場合にルミーズのペイクイックID管理用テーブルにレコードを追加
			if( $user_id > 0 ){

				$remise_result = array(
						array('user_id' => $user_id,
								'payment_method' => 'remise_cc', 
								'quickpay_id' => $payquick_id,
								),
						);

				$_data = array();

				foreach ($remise_result as $_data) {
					db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);
				}
			}
		}
	}

	// ルミーズに受信処理完了を通知する
	echo "<SDBKDATA>STATUS=800</SDBKDATA>";

}else{
	echo "INVALID ACCESS!!";
}
