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

// $Id: csp.php by tommy from cs-cart.jp 2014


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
	////////////////////////////////////////////////////////////
	// CS-Cartの機能を利用するための設定 BOF
	////////////////////////////////////////////////////////////
	define('AREA', 'C');
	define('REMISE_DATE_LENGTH', 12);
	require '../../init.php';
	////////////////////////////////////////////////////////////
	// CS-Cartの機能を利用するための設定 EOF
	////////////////////////////////////////////////////////////

	// CS-Cartにおけるルミーズコンビニ決済の設定値を取得
	$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'remise_csp.php' AND ?:payments.status = 'A'");
	$processor_data = fn_get_payment_method_data($payment_id);

	// 加盟店コード
	$shop_code = $processor_data['processor_params']['shop_code'];

	// ホストID
	$host_id = $processor_data['processor_params']['host_id'];

	// ルミーズから送信された加盟店コードとホストIDがCS-Cartの登録情報と一致し、請求番号が存在する場合
	if( $_REQUEST['SHOPCO'] == $shop_code && $_REQUEST['HOSTID'] == $host_id && $_REQUEST['S_TORIHIKI_NO']){
		$cvs_name = '';
		$payment_date = '';

		// 注文IDを取得
		$order_id = substr($_REQUEST['S_TORIHIKI_NO'], 0, strlen($_REQUEST['S_TORIHIKI_NO']) - REMISE_DATE_LENGTH);

        // 注文IDから該当するcompany_idをセット
        fn_payments_set_company_id($order_id);

        // 注文ステータスを入金完了状態に変更
        fn_change_order_status($order_id, 'P');

        // 処理対象となる注文ID群を取得
        $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

        // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
        foreach($order_ids_to_process as $order_id){
            ////////////////////////////////////////////////////////////
            // コンビニ決済の入金日時をスタッフメモに追記 BOF
            ////////////////////////////////////////////////////////////
            if( !empty($_REQUEST['REC_CVSNAME']) && !empty($_REQUEST['RECDATE']) ){

                // 登録された注文データを抽出
                $order_details = db_get_field("SELECT details FROM ?:orders WHERE order_id = ?i", $order_id);

                // コンビニ名
                $cvs_name = mb_convert_encoding($_REQUEST['REC_CVSNAME'], 'UTF-8', 'SJIS');

                // 入金日時
                $payment_date = $_REQUEST['RECDATE'];

                $remise_csp_details = "\n\n" . str_replace('[cvs_name]', $cvs_name, __('jp_cvs_payment_header'));
                $remise_csp_details .= "\n" . __('jp_cvs_payment_date') . " ： " . $payment_date;

                $details = $order_details . $remise_csp_details;

                // 文頭の改行は削除
                $data = array('details' => ltrim($details));

                db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id);
            }
            ////////////////////////////////////////////////////////////
            // コンビニ決済の入金日時をスタッフメモに追記 EOF
            ////////////////////////////////////////////////////////////
        }

		// 受信完了メッセージを返す
		echo '<SDBKDATA>STATUS=800</SDBKDATA>';

	}else{
		// 受信完了メッセージを返す
		echo '<SDBKDATA>STATUS=800</SDBKDATA>';
	}
}else{
	// エラーメッセージを返す
	echo "INVALID ACCESS!!";
}
