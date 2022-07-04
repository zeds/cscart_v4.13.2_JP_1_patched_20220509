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

// クロネコwebコレクトからのIPアドレスのみ処理を許可

if( preg_match('/^218\.40\.0\.72/', $_SERVER['REMOTE_ADDR']) ||
    preg_match('/^132\.147\.105\.144/', $_SERVER['REMOTE_ADDR'])  ||
    preg_match('/^52\.155\.115\.90/', $_SERVER['REMOTE_ADDR']) ){

	// 初期設定
	define('AREA', 'C');
	require '../../init.php';
	require_once(Registry::get('config.dir.addons') . 'kuroneko_webcollect/func.php');

    // クロネコwebコレクトから受信したデータを変数にセット
    $data_received = $_POST;

	// クロネコwebコレクトから正常な入金通知データを受信した場合
	if( fn_krnkwc_validate_notification($data_received) ){

        // CS-Cartの注文IDを抽出
        $order_id = fn_krnkwc_get_order_id($data_received['order_no']);

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

        ////////////////////////////////////////////////////////////
        // 入金関連情報を注文データ内の支払関連情報に書き込み BOF
        ////////////////////////////////////////////////////////////
        // 正常に入金が完了している場合
        if( !empty($data_received['settle_detail']) && !empty($data_received['settle_method']) ) {

            $type = false;

            switch ($data_received['settle_detail']) {
                case 2:
                case 3:
                    $type = 'cvs_settle';
                    if( $data_received['settle_detail'] == 2 ){
                        $data_received['notification_type'] = __("jp_kuroneko_webcollect_settle_pre");
                    }elseif( $data_received['settle_detail'] == 3 ){
                        $data_received['notification_type'] = __("jp_kuroneko_webcollect_settle_def");
                    }
                    break;
                default:
                    // do nothing
            }

            if (empty($type)) {
                echo 0;
                exit;
            }

            // 入金関連情報を注文データ内の支払関連情報に書き込み
            fn_krnkwc_format_payment_info($type, $order_id, array(), $data_received);
        }
        ////////////////////////////////////////////////////////////
        // 入金関連情報を注文データ内の支払関連情報に書き込み BOF
        ////////////////////////////////////////////////////////////


        // 注文ステータスが「注文受付」の場合
        if( $order_info['status'] == 'O' ){
            // 通知ステータスを初期化
            $force_notification = array();

            // 注文金額と入金金額が同額の場合
            if( round($order_info['total']) == (int)$data_received['settle_price'] ){
                // 注文ステータスを入金完了状態に変更
                $to_status = 'P';

                // お客様とショップ管理者に通知
                $force_notification['C'] = true;
                $force_notification['A'] = true;

            // 注文金額と入金金額が異なる場合
            }else{
                // 注文ステータスをユーザーが指定したものに変更
                $to_status = strtoupper(Registry::get('addons.kuroneko_webcollect.pending_status'));

                // ショップ管理者にのみ通知
                $force_notification['C'] = false;
                $force_notification['A'] = true;
            }

            // 注文ステータスを変更
            fn_change_order_status($order_id, $to_status, '', $force_notification);
        }
	}
    // 処理を終了
    echo 0;
    exit;
}
