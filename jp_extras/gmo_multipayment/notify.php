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

// $Id: notify.php by tommy from cs-cart.jp 2015
// Modified to fix bug by takahashi from cs-cart.jp 2017
// マーケットプレイス版で複数出品者からの注文ステータスが更新されない不具合を修正

use Tygh\Registry;

// GMOペイメントサービスからのIPアドレスのみ処理を許可
if( preg_match('/^210\.197\.108\.196/', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^210\.197\.108\.197/', $_SERVER['REMOTE_ADDR'])  ||
    preg_match('/^210\.175\.7\.20/', $_SERVER['REMOTE_ADDR'])  ||
    preg_match('/^210\.175\.7\.21/', $_SERVER['REMOTE_ADDR']) ){

	// 初期設定
	define('AREA', 'C');
	require '../../init.php';
	require_once(Registry::get('config.dir.addons') . 'gmo_multipayment/func.php');

    // GMOペイメントサービスから受信したデータを変数にセット
    $data_received = $_POST;

	// GMOペイメントサービスから正常な入金通知データを受信した場合
	if( fn_gmomp_validate_notification($data_received) ){

        // CS-Cartの注文IDを抽出
        $order_id = fn_gmomp_get_order_id($data_received['OrderID']);

        // 注文IDから該当するcompany_idをセット
        fn_payments_set_company_id($order_id);

		// 注文情報を抽出
		$order_info = db_get_row("SELECT user_id, total, status FROM ?:orders WHERE order_id = ?i", $order_id);

		// CS-Cartに該当する注文データが存在しない場合
		if( empty($order_info) ){
			// 処理を終了
            echo 0;
			exit;
		}

        // 処理対象となる注文ID群を取得
        $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

        // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
        foreach($order_ids_to_process as $order_id_to_process){
            ////////////////////////////////////////////////////////////
            // 入金関連情報を注文データ内の支払関連情報に書き込み BOF
            ////////////////////////////////////////////////////////////
            // 正常に入金が完了している場合
            if( !empty($data_received['PayType']) && !empty($data_received['Status']) && $data_received['Status'] == 'PAYSUCCESS' ) {

                $type = false;

                switch ($data_received['PayType']) {
                    case 3 :
                        $type = 'cvs_notify';
                        break;
                    case 4 :
                        $type = 'payeasy_notify';
                        break;
                    default:
                        // do nothing
                }

                if (empty($type)) {
                    echo 0;
                    exit;
                }

                // 入金関連情報を注文データ内の支払関連情報に書き込み
                fn_gmomp_format_payment_info($type, $order_id, array(), $data_received);

                ////////////////////////////////////////////////////////////////////
                // Modified to fix bug by takahashi from cs-cart.jp 2017 BOF
                // マーケットプレイス版で複数出品者からの注文ステータスが更新されない不具合を修正
                ////////////////////////////////////////////////////////////////////
                $order_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id_to_process);

                // 注文ステータスが「処理待ち」の場合
                if( $order_status == 'O' ){
                    // 通知ステータスを初期化
                    $force_notification = array();

                    // 注文金額と入金金額が同額の場合
                    if( round($order_info['total']) == $data_received['Amount'] ){
                        // 注文ステータスを入金完了状態に変更
                        $to_status = 'P';

                        // お客様とショップ管理者に通知
                        $force_notification['C'] = true;
                        $force_notification['A'] = true;

                        // 注文金額と入金金額が異なる場合
                    }else{
                        // 注文ステータスをユーザーが指定したものに変更
                        $to_status = strtoupper(Registry::get('addons.gmo_multipayment.pending_status'));

                        // ショップ管理者にのみ通知
                        $force_notification['C'] = false;
                        $force_notification['A'] = true;
                    }

                    // 注文ステータスを変更
                    fn_change_order_status($order_id_to_process, $to_status, '', $force_notification);

                    ////////////////////////////////////////////////////////////////////
                    // Modified to fix bug by takahashi from cs-cart.jp 2017 EOF
                    ////////////////////////////////////////////////////////////////////
                }

            // 正常に入金が完了していない場合
            }else{
                echo 0;
                exit;
            }
            ////////////////////////////////////////////////////////////
            // 入金関連情報を注文データ内の支払関連情報に書き込み EOF
            ////////////////////////////////////////////////////////////
        }
	}
    // 処理を終了
    echo 0;
    exit;
}
