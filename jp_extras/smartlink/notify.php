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
// Modified to fix bug by takahashi from cs-cart.jp 2018
// マーケットプレイス版で複数出品者からの注文ステータスが更新されない不具合を修正

// Modified by takahashi from cs-cart.jp 2021
// ソニーペイメント新環境によりIPアドレス追加

use Tygh\Registry;

// スマートリンクからのIPアドレスのみ処理を許可
if( preg_match('/^211\.128\.98\.141/', $_SERVER['REMOTE_ADDR']) || preg_match('/^211\.128\.98\.133/', $_SERVER['REMOTE_ADDR'])
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2021 BOF
    // ソニーペイメント新環境によりIPアドレス追加
    ///////////////////////////////////////////////
    // ・本番環境(メインサイト)
    || preg_match('/^54\.238\.10\.224/', $_SERVER['REMOTE_ADDR'])
    || preg_match('/^3\.114\.145\.91/', $_SERVER['REMOTE_ADDR'])
    || preg_match('/^54\.248\.234\.19/', $_SERVER['REMOTE_ADDR'])
    // ・本番環境(DRサイト)
    || preg_match('/^13\.208\.56\.91/', $_SERVER['REMOTE_ADDR'])
    || preg_match('/^13\.208\.108\.172/', $_SERVER['REMOTE_ADDR'])
    || preg_match('/^15\.152\.0\.135/', $_SERVER['REMOTE_ADDR'])
    // ・テスト環境
    || preg_match('/^35\.72\.54\.3/', $_SERVER['REMOTE_ADDR'])
    || preg_match('/^54\.248\.208\.159/', $_SERVER['REMOTE_ADDR'])
    || preg_match('/^35\.72\.47\.156/', $_SERVER['REMOTE_ADDR'])
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2021 EOF
    ///////////////////////////////////////////////
){

	// 初期設定
	define('AREA', 'C');
	require '../../init.php';
	require_once(Registry::get('config.dir.addons') . 'smartlink/func.php');

	// スマートリンクから正常な入金通知データを受信した場合
	if( fn_sln_validate_notification($_POST) ){

        // CS-Cartの注文IDを抽出
        $order_id = fn_sln_get_order_id($_REQUEST['MerchantFree1']);

        // 注文IDから該当するcompany_idをセット
        fn_payments_set_company_id($order_id);

		// 注文情報を抽出
		$order_info = db_get_row("SELECT user_id, total, status FROM ?:orders WHERE order_id = ?i", $order_id);

		// CS-Cartに該当する注文データが存在しない場合
		if( empty($order_info) ){
			// 処理を終了
			exit;
		}

        // 処理対象となる注文ID群を取得
        $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

        // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
        foreach($order_ids_to_process as $order_id_to_process){
            ////////////////////////////////////////////////////////////
            // 入金日時を注文データ内の支払関連情報に追記 BOF
            ////////////////////////////////////////////////////////////
            // 入金日がセットされている場合
            if( !empty($_REQUEST['NyukinDate']) ){

                // 注文データ内の支払関連情報を取得
                $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id_to_process, 'P');

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
                $info['jp_sln_daiko_nyukindate'] = $_REQUEST['NyukinDate'];

                // 支払関連情報として収納機関名をセット
                if( !empty($_REQUEST['CvsCd']) ){
                    $info['jp_sln_daiko_cvsname'] = fn_sln_get_cvs_name($_REQUEST['CvsCd']);
                }

                // 支払関連情報として店舗コードをセット
                if( !empty($_REQUEST['TenantCd']) ){
                    $info['jp_sln_daiko_tenant_code'] = $_REQUEST['TenantCd'];
                }

                // 支払関連情報として受付番号をセット
                if( !empty($_REQUEST['RecvNum']) ){
                    $info['jp_sln_daiko_recvnum'] = $_REQUEST['RecvNum'];
                }

                // 支払関連情報として入金金額をセット
                if( !empty($_REQUEST['Amount']) && (int)$_REQUEST['Amount'] > 0 ){
                    $info['jp_sln_daiko_paid_amount'] = fn_format_price($_REQUEST['Amount']);
                }

                // 支払関連情報として印紙有無をセット
                if( $_REQUEST['StampFlag'] ){
                    $info['jp_sln_daiko_stamp'] = fn_sln_get_stamp_status($_REQUEST['StampFlag']);
                }

                // 支払情報を暗号化
                $_data = fn_encrypt_text(serialize($info));

                // 注文データ内の支払関連情報が存在する場合
                if( $flg_payment_info_exists ){
                    // 注文データ内の支払関連情報を上書き
                    db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id_to_process);

                // 注文データ内の支払関連情報が存在しない場合
                }else{
                    // 注文データ内の支払関連情報を追加
                    $insert_data = array (
                        'order_id' => $order_id_to_process,
                        'type' => 'P',
                        'data' => $_data,
                    );
                    db_query("REPLACE INTO ?:order_data ?e", $insert_data);
                }

                /////////////////////////////////////////////////////////
                // コメント欄から支払方法確認URLを削除 BOF
                /////////////////////////////////////////////////////////
                // 既存のコメントを取得
                $order_comments = db_get_field("SELECT notes FROM ?:orders WHERE order_id = ?i", $order_id_to_process);

                // 既存のコメントが存在する場合
                if($order_comments != ''){
                    $order_comments = str_replace(__('jp_sln_daiko_info') . "\n" . __('jp_sln_daiko_instruction') . "\n", '', $order_comments);
                    $order_comments = str_replace(__('jp_sln_daiko_info') . "\r\n" . __('jp_sln_daiko_instruction') . "\r\n", '', $order_comments);
                    $pattern = '/((?:https?|ftp):\/\/link.kessai.info[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+)/u';
                    $replacement = '';
                    $order_comments = preg_replace($pattern, $replacement, $order_comments);
                    $order_comments = trim($order_comments);
                    $data = array('notes' => $order_comments);
                    db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id_to_process);
                }
                /////////////////////////////////////////////////////////
                // コメント欄から支払方法確認URLを削除 EOF
                /////////////////////////////////////////////////////////
            }
            ////////////////////////////////////////////////////////////
            // 入金日時を注文データ内の支払関連情報に追記 EOF
            ////////////////////////////////////////////////////////////

            ////////////////////////////////////////////////////////////////////
            // Modified to fix bug by takahashi from cs-cart.jp 2018 BOF
            // マーケットプレイス版で複数出品者からの注文ステータスが更新されない不具合を修正
            ////////////////////////////////////////////////////////////////////
            $order_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id_to_process);

            // 注文ステータスが「注文受付」の場合
            if( $order_status == 'O' ){
                // 注文ステータスを変更
                $force_notification = array();
                $force_notification['C'] = true;
                $force_notification['A'] = true;

                // 注文金額と入金金額が同じで、結果コードが正常（OK）の場合
                if( round($order_info['total']) == $_REQUEST['Amount'] && $_REQUEST['ResponseCd'] == 'OK'){
                    // 注文ステータスを入金完了状態に変更
                    $to_status = 'P';
                    // 注文金額と入金金額が異なる、もくは結果コードが正常（OK）以外の場合
                }else{
                    // 注文ステータスをユーザーが指定したものに変更
                    $to_status = strtoupper(Registry::get('addons.smartlink.pending_status'));
                }
                fn_change_order_status($order_id_to_process, $to_status, '', $force_notification);
            }
            ////////////////////////////////////////////////////////////////////
            // Modified to fix bug by takahashi from cs-cart.jp 2018 EOF
            ////////////////////////////////////////////////////////////////////
        }
	}
    // 処理を終了
    echo '<BODY>OK</BODY>';
    exit;
}
