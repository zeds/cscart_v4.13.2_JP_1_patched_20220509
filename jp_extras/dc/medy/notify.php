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

// $Id: notify.php by tommy from cs-cart.jp 2014

use Tygh\Registry;

// ペイデザインからのIPアドレスのみ処理を許可
if( preg_match('/^125\.29\.34\./', $_SERVER['REMOTE_ADDR']) || preg_match('/^114\.160\.46\./', $_SERVER['REMOTE_ADDR']) || preg_match('/^27\.110\.52\./', $_SERVER['REMOTE_ADDR']) ){
    
	// 初期設定
	define('AREA', 'C');
	require '../../../init.php';
    require_once(Registry::get('config.dir.addons') . 'digital_check/func.php');

	// ペイデザインから正常な入金通知データを受信した場合
	if( fn_dgtlchck_validate_notification($_POST) ){

		// 取引コードからCS-Cartの注文IDを抽出
		$order_id = fn_dgtlchck_get_order_id($_REQUEST['SID']);

        // 注文IDから該当するcompany_idをセット
        fn_payments_set_company_id($order_id);

		// 注文情報を抽出
		$order_info = db_get_row("SELECT user_id, total, status FROM ?:orders WHERE order_id = ?i", $order_id);

		// CS-Cartに該当する注文データが存在しない場合
		if( empty($order_info) ){
			// 処理を終了
			header("Content-type: text/plain; charset=Shift_JIS");
			echo "0\r\n";
			exit;
		}

        // 処理対象となる注文ID群を取得
        $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

		// MobileEdy決済かつ注文ステータスが「処理待ち」の場合
		if( $_REQUEST['CVS'] == 'm_edy' && $order_info['status'] == 'O' ){

            // 注文ステータスを変更
            $force_notification = array();
            $force_notification['C'] = true;
            $force_notification['A'] = true;

            // 注文金額と入金金額が同じ場合
            if( round($order_info['total']) == $_REQUEST['KINGAKU'] ){
                // 注文ステータスを入金完了状態に変更
                $to_status = 'P';
            // 注文金額と入金金額が異なる場合
            }else{
                // 注文ステータスをユーザーが指定したものに変更
                $to_status = strtoupper(Registry::get('addons.digital_check.pending_status'));
            }
            fn_change_order_status($order_id, $to_status, '', $force_notification);

            // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
            foreach($order_ids_to_process as $order_id){
                ////////////////////////////////////////////////////////////
                // 入金日時等を注文データ内の支払関連情報に追記 BOF
                ////////////////////////////////////////////////////////////
                // 入金日時がセットされている場合
                if( !empty($_REQUEST['DATE']) && !empty($_REQUEST['TIME']) ){

                    // 注文データ内の支払関連情報を取得
                    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

                    // 注文データ内の支払関連情報が存在する場合
                    if( !empty($payment_info) ){
                        $flg_payment_info_exists = true;

                        // 支払情報が暗号化されている場合は復号化して変数にセット
                        if( !is_array($payment_info)) {
                            $info = @unserialize(fn_decrypt_text($payment_info));
                        }else{
                            // 支払情報を変数にセット
                            $info = $payment_info;
                        }

                    // 注文データ内の支払関連情報が存在しない場合
                    }else{
                        $flg_payment_info_exists = false;
                        $info = array();
                    }

                    // 支払関連情報として入金日時をセット
                    $info['jp_cvs_payment_date'] = fn_dgtlchck_format_date($_REQUEST['DATE'], $_REQUEST['TIME']);

                    // 支払関連情報として決済方法をセット
                    if( !empty($_REQUEST['CVS']) ){
                        if( $_REQUEST['CVS'] == 'm_edy' ){
                            $info['jp_digital_check_trans_method'] = __('jp_payment_mobileedy');
                        }else{
                            $info['jp_digital_check_trans_method'] = $_REQUEST['CVS'];
                        }
                    }

                    // 支払情報を暗号化
                    $_data = fn_encrypt_text(serialize($info));

                    // 注文データ内の支払関連情報が存在する場合
                    if( $flg_payment_info_exists ){
                        // 注文データ内の支払関連情報を上書き
                        db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);

                    // 注文データ内の支払関連情報が存在しない場合
                    }else{
                        // 注文データ内の支払関連情報を追加
                        $insert_data = array (
                            'order_id' => $order_id,
                            'type' => 'P',
                            'data' => $_data,
                        );
                        db_query("REPLACE INTO ?:order_data ?e", $insert_data);
                    }
                }
                ////////////////////////////////////////////////////////////
                // 入金日時等を注文データ内の支払関連情報に追記 EOF
                ////////////////////////////////////////////////////////////
            }

		// CyberEdy決済かつ注文ステータスが「処理中」「受注処理未了」の場合
		}elseif( $_REQUEST['CVS'] == 'c_edy' && ($order_info['status'] == 'P' || $order_info['status'] == 'N') ){

            // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
            foreach($order_ids_to_process as $order_id){
                ////////////////////////////////////////////////////////////
                // 決済完了日時を注文データ内の支払関連情報に追記 BOF
                ////////////////////////////////////////////////////////////
                // 決済完了日時がセットされている場合
                if( !empty($_REQUEST['DATE']) && !empty($_REQUEST['TIME']) ){

                    // 注文データ内の支払関連情報を取得
                    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

                    // 注文データ内の支払関連情報が存在する場合
                    if( !empty($payment_info) ){
                        $flg_payment_info_exists = true;

                        // 支払情報が暗号化されている場合は復号化して変数にセット
                        if( !is_array($payment_info)) {
                            $info = @unserialize(fn_decrypt_text($payment_info));
                        }else{
                            // 支払情報を変数にセット
                            $info = $payment_info;
                        }

                    // 注文データ内の支払関連情報が存在しない場合
                    }else{
                        $flg_payment_info_exists = false;
                        $info = array();
                    }

                    // 支払関連情報として取引コードをセット
                    if( !empty($_REQUEST['SID']) ){
                        $info['jp_digital_check_sid'] = $_REQUEST['SID'];
                    }

                    // 支払関連情報として決済日時をセット
                    $info['jp_digital_check_process_date'] = fn_dgtlchck_format_date($_REQUEST['DATE'], $_REQUEST['TIME']);

                    // 支払関連情報として決済方法をセット
                    if( !empty($_REQUEST['CVS']) ){
                        if( $_REQUEST['CVS'] == 'c_edy' ){
                            $info['jp_digital_check_trans_method'] = __('jp_payment_cyberedy');
                        }else{
                            $info['jp_digital_check_trans_method'] = $_REQUEST['CVS'];
                        }
                    }

                    // 支払情報を暗号化
                    $_data = fn_encrypt_text(serialize($info));

                    // 注文データ内の支払関連情報が存在する場合
                    if( $flg_payment_info_exists ){
                        // 注文データ内の支払関連情報を上書き
                        db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);

                    // 注文データ内の支払関連情報が存在しない場合
                    }else{
                        // 注文データ内の支払関連情報を追加
                        $insert_data = array (
                            'order_id' => $order_id,
                            'type' => 'P',
                            'data' => $_data,
                        );
                        db_query("REPLACE INTO ?:order_data ?e", $insert_data);
                    }
                }
                ////////////////////////////////////////////////////////////
                // 決済完了日時を注文データ内の支払関連情報に追記 EOF
                ////////////////////////////////////////////////////////////
            }
		}
	}

	header("Content-type: text/plain; charset=Shift_JIS");
	echo "0\r\n";
}else{
	die('INVALID ACCESS!!');
}
