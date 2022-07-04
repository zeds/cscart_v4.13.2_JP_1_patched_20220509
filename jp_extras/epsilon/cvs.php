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

// $Id: cvs.php by tommy from cs-cart.jp 2014


// イプシロンからのIPアドレスのみ処理を許可
// 【旧サーバ】テスト環境： 150.48.237.114
// 【旧サーバ】本番環境： 150.48.237.113
// 【新サーバ】テスト・本番環境： 210.254.37.215 / 210.254.37.220

if( preg_match('/^150\.48\.237\./', $_SERVER['REMOTE_ADDR']) || preg_match('/^210\.254\.37\./', $_SERVER['REMOTE_ADDR']) )
{
	////////////////////////////////////////////////////////////
	// CS-Cartの機能を利用するための設定 BOF
	////////////////////////////////////////////////////////////
	define('AREA', 'C');
	define('EPSILON_DATE_LENGTH', 12);
	require '../../init.php';
	////////////////////////////////////////////////////////////
	// CS-Cartの機能を利用するための設定 EOF
	////////////////////////////////////////////////////////////

	// トランザクションコードが存在する場合
	if( $_REQUEST['trans_code'] ){
		$cvs_name = '';
		$payment_date = '';

		if( $_REQUEST['order_number'] ){

			// 注文IDを取得
			$order_id = substr($_REQUEST['order_number'], 0, strlen($_REQUEST['order_number']) - EPSILON_DATE_LENGTH);

            // 注文IDから該当するcompany_idをセット
            fn_payments_set_company_id($order_id);

            // 注文ステータスを入金完了状態に変更
            fn_change_order_status($order_id, 'P');

            // 処理対象となる注文ID群を取得
            $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

            // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
            foreach($order_ids_to_process as $order_id){
                ////////////////////////////////////////////////////////////
                // コンビニ・ペイジー決済の入金日時をスタッフメモに追記 BOF
                ////////////////////////////////////////////////////////////
                if( !empty($_REQUEST['conveni_name']) && !empty($_REQUEST['conveni_date']) ){

                    // 登録された注文データを抽出
                    $order_details = db_get_field("SELECT details FROM ?:orders WHERE order_id = ?i", $order_id);

                    // コンビニ名
                    $cvs_name = mb_convert_encoding($_REQUEST['conveni_name'], 'UTF-8', 'SJIS');

                    // 入金日時
                    $payment_date = $_REQUEST['conveni_date'];

                    $epsilon_details = "\n\n" . str_replace('[cvs_name]', $cvs_name, __('jp_cvs_payment_header'));
                    $epsilon_details .= "\n" . __('jp_cvs_payment_date') . " ： " . $payment_date;

                    $details = $order_details . $epsilon_details;

                    // 文頭の改行は削除
                    $data = array('details' => ltrim($details));

                    db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id);
                }
                ////////////////////////////////////////////////////////////
                // コンビニ・ペイジー決済の入金日時をスタッフメモに追記 EOF
                ////////////////////////////////////////////////////////////
            }
		}

		echo 1;

	}else{
		// エラーメッセージを返す
		echo '0 999 PROCESS_ERROR';
	}
}else{
	// エラーメッセージを返す
	echo '0 999 PROCESS_ERROR';
}
