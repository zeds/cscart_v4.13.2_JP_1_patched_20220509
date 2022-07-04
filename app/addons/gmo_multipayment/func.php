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

// $Id: func.php by tommy from cs-cart.jp 2017
//
// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_gmo_multipayment_[フックポイント名]
// (2) (1)以外の関数：fn_gmomp_[任意の名称]

// Modified by tommy from cs-cart.jp 2017
// トークン決済に対応

// Modified by takahashi from cs-cart.jp 2017
// コンビニコードの変更・ペイジー決済タイプパラメータの追加

use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * クレジットカード情報を登録済みの会員に対してのみ登録済みカード決済を表示
 *
 * @param $params
 * @param $payments
 */
function fn_gmo_multipayment_get_payments_post(&$params, &$payments)
{
    fn_lcjp_filter_payments($payments, 'gmo_multipayment_ccreg.tpl', 'gmomp_ccreg');
}




/**
 * GMOマルチペイメントサービスでは注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためGMOマルチペイメントサービス
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_gmo_multipayment_finish_payment(&$order_id, &$pp_response, &$force_notification)
{
	// 注文データ内の支払関連情報を取得
	$payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

	// 注文データ内の支払関連情報が存在する場合
	if( !empty($payment_info) ){

		// 決済代行サービスのIDを取得
		$payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
		if( empty($payment_id) ) return false;
		$payment_method_data = fn_get_payment_method_data($payment_id);
		if( empty($payment_method_data) ) return false;
		$processor_id = $payment_method_data['processor_id'];
		if( empty($processor_id) ) return false;

		switch($processor_id){
			case '9200':
			case '9201':
			case '9202':
			case '9203':
			case '9204':
            case '9205':
            case '9206':
            case '9207':
            case '9208':
            case '9209':
				// 支払情報が暗号化されている場合は復号化して変数にセット
				if( !is_array($payment_info)) {
					$info = @unserialize(fn_decrypt_text($payment_info));
				}else{
					// 支払情報を変数にセット
					$info = $payment_info;
				}

				// 支払情報から注文ステータスに関する記述を削除
				unset($info['order_status']);

				// カード情報への登録有無に関する記述を削除
				unset($info['use_uid']);

				// 支払情報を暗号化
				$_data = fn_encrypt_text(serialize($info));

				// 注文データ内の支払関連情報を上書き
				db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);
				break;
			default:
				// do nothing
		}
	}
}




/**
 * クレジット請求管理ページにおける注文情報の抽出・表示
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_gmo_multipayment_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    // クレジット請求管理ページの場合
    if( Registry::get('runtime.controller') == 'gmomp_cc_manager' && Registry::get('runtime.mode') == 'manage'){
        // カード決済および登録済カードにより支払われた注文のみ抽出
        $processor_ids = array(9200, 9201, 9204);
        $gmomp_cc_payments = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);
        $gmomp_cc_payments = implode(',', $gmomp_cc_payments);
        $condition .= " AND ?:orders.payment_id IN ($gmomp_cc_payments)";

        // 各注文にひもづけられたクレジット請求ステータスコードを抽出
        $fields[] = "?:jp_gmomp_cc_status.status_code as cc_status_code";
        $join .= " LEFT JOIN ?:jp_gmomp_cc_status ON ?:jp_gmomp_cc_status.order_id = ?:orders.order_id";
    }
}




/**
 * 注文情報削除時にクレジット決済の請求ステータスを削除
 *
 * @param $order_id
 */
function fn_gmo_multipayment_delete_order(&$order_id)
{
    db_query("DELETE FROM ?:jp_gmomp_cc_status WHERE order_id = ?i", $order_id);
}




/**
 * ログにカード番号や有効期限、ShopIDなどが記録されることを回避
 *
 * @param $type
 * @param $action
 * @param $data
 * @param $user_id
 * @param $content
 * @param $event_type
 * @param $object_primary_keys
 */
function fn_gmo_multipayment_save_log(&$type, &$action, &$data, &$user_id, &$content, &$event_type, &$object_primary_keys)
{
    if($type == 'requests'){
        $url = $data['url'];
        switch($url){
            // カード決済（テスト環境） : 取引登録用URL
            case GMOMP_TEST_URL_ENTRYTRAN:
            // カード決済（本番環境） : 取引登録用URL
            case GMOMP_LIVE_URL_ENTRYTRAN:
            // コンビニ決済（テスト環境） : 取引登録用URL
            case GMOMP_TEST_URL_ENTRYTRANCVS:
            // ペイジー決済（テスト環境） : 取引登録用URL
            case GMOMP_TEST_URL_ENTRYTRANPAYEASY:
            // ペイジー決済（本番環境） : 取引登録用URL
            case GMOMP_LIVE_URL_ENTRYTRANPAYEASY:
                $content['request'] = 'Hidden for Security Reason';
                $content['response'] = 'Hidden for Security Reason';
                break;

            // カード決済（テスト環境） : 決済実行用URL
            case GMOMP_TEST_URL_EXECTRAN:
            // カード決済（テスト環境） : 3D認証決済実行用URL
            case GMOMP_TEST_URL_SECURETRAN:
            // カード決済（テスト環境） : 会員登録用URL
            case GMOMP_TEST_URL_SAVEMEMBER:
            // カード決済（テスト環境） : 会員検索用URL
            case GMOMP_TEST_URL_SEARCHMEMBER:
            // カード決済（テスト環境） : 決済後カード登録URL
            case GMOMP_TEST_URL_TRADEDCARD:
            // カード決済（テスト環境） : 決済取消、再オーソリ、売上確定URL
            case GMOMP_TEST_URL_ALTERTRAN:
            // カード決済（テスト環境） : 金額変更URL
            case GMOMP_TEST_URL_CHANGETRAN:
            // カード決済（テスト環境） : 登録済みカード削除URL
            case GMOMP_TEST_URL_DELETECARD:
            // カード決済（テスト環境） : 登録済みカード照会URL
            case GMOMP_TEST_URL_SEARCHCARD:
            // カード決済（テスト環境） : 取引照会用URL
            case GMOMP_TEST_URL_SEARCHTRAN:
            // コンビニ決済（テスト環境） : 決済実行用URL
            case GMOMP_TEST_URL_EXECTRANCVS:
            // ペイジー決済（テスト環境） : 決済実行用URL
            case GMOMP_TEST_URL_EXECTRANPAYEASY:
            // カード決済（本番環境） : 決済実行用URL
            case GMOMP_LIVE_URL_EXECTRAN:
            // カード決済（本番環境） : 3D認証決済実行用URL
            case GMOMP_LIVE_URL_SECURETRAN:
            // カード決済（本番環境） : 会員登録用URL
            case GMOMP_LIVE_URL_SAVEMEMBER:
            // カード決済（本番環境） : 会員検索用URL
            case GMOMP_LIVE_URL_SEARCHMEMBER:
            // カード決済（本番環境） : 決済後カード登録URL
            case GMOMP_LIVE_URL_TRADEDCARD:
            // カード決済（本番環境） : 決済取消、再オーソリ、売上確定URL
            case GMOMP_LIVE_URL_ALTERTRAN:
            // カード決済（本番環境） : 金額変更URL
            case GMOMP_LIVE_URL_CHANGETRAN:
            // カード決済（本番環境） : 登録済みカード削除URL
            case GMOMP_LIVE_URL_DELETECARD:
            // カード決済（本番環境） : 登録済みカード照会URL
            case GMOMP_LIVE_URL_SEARCHCARD:
            // カード決済（本番環境） : 取引照会用URL
            case GMOMP_LIVE_URL_SEARCHTRAN:
            // コンビニ決済（本番環境） : 決済実行用URL
            case GMOMP_LIVE_URL_EXECTRANCVS:
            // ペイジー決済（本番環境） : 決済実行用URL
            case GMOMP_LIVE_URL_EXECTRANPAYEASY:
                $content['request'] = 'Hidden for Security Reason';
                break;

            default:
                // do nothing
        }
    }
}

/**
 * 言語変数とトークン決済用のprocessor_idを追加してメッセージを出す
 *
 * @param $user_data
 */
function fn_gmo_multipayment_set_admin_notification(&$user_data)
{
    // トークン決済用のprocessor_idが存在するか確認する
    $tokenId =  db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'gmo_multipayment_cctkn.php'");

    // トークン決済用のprocessor_idが存在しない場合
    if(empty($tokenId)){
        try {
            // インストール済みの言語を取得
            $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

            // 言語変数の追加
            $lang_variables = array(
                array('name' => 'jp_gmo_multipayment_token_enabled', 'value' => 'PGマルチペイメントサービスにおいて<br />トークンを利用したクレジットカード決済がご利用いただけるようになりました。'),
                array('name' => 'error_validator_cc_check_length_jp', 'value' => 'カード番号が正しくありません'),
                array('name' => 'error_validator_cc_exp_jp', 'value' => '有効期限が不正です'),
            );

            foreach ($languages as $lc => $_v) {
                foreach ($lang_variables as $k1 => $v1) {
                    if (!empty($v1['name'])) {
                        preg_match("/(^[a-zA-z0-9][a-zA-Z0-9_]*)/", $v1['name'], $matches);
                        if (strlen($matches[0]) == strlen($v1['name'])) {
                            $v1['lang_code'] = $lc;
                            db_query("REPLACE INTO ?:language_values ?e", $v1);
                        }
                    }
                }
            }

            // トークン決済用のprocessor_idを追加
            db_query("INSERT INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9204, 'PGマルチペイメントサービス（プロトコルタイプ・カード決済・トークン決済）', 'gmo_multipayment_cctkn.php', 'addons/gmo_multipayment/views/orders/components/payments/gmo_multipayment_cctkn.tpl', 'gmo_multipayment_cctkn.tpl', 'N', 'P')");

            // トークン決済利用可能のメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_gmo_multipayment_token_enabled'));
        }
        catch (Exception $e){
            // エラー発生(Service Unavailableメッセージを出さない)
        }
    }
}
##########################################################################################
// END フックポイントで動作する関数
##########################################################################################





##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################
/**
 * アドオンのインストール時の処理
 */
function fn_gmomp_install()
{
    fn_lcjp_install('gmo_multipayment');
}




/**
 * アドオンのアンインストール時に支払関連のレコードを削除
 */
function fn_gmomp_delete_payment_processors()
{
    ///////////////////////////////////////////////
    // Modified by tommy from cs-cart.jp 2017 BOF
    // トークン決済に対応
    ///////////////////////////////////////////////
    // db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('gmo_multipayment_cc.php', 'gmo_multipayment_ccreg.php', 'gmo_multipayment_cvs.php', 'gmo_multipayment_payeasy.php')))");
    // db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('gmo_multipayment_cc.php', 'gmo_multipayment_ccreg.php', 'gmo_multipayment_cvs.php', 'gmo_multipayment_payeasy.php'))");
    // db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('gmo_multipayment_cc.php', 'gmo_multipayment_ccreg.php', 'gmo_multipayment_cvs.php', 'gmo_multipayment_payeasy.php')");
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('gmo_multipayment_cc.php', 'gmo_multipayment_ccreg.php', 'gmo_multipayment_cvs.php', 'gmo_multipayment_payeasy.php', 'gmo_multipayment_cctkn.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('gmo_multipayment_cc.php', 'gmo_multipayment_ccreg.php', 'gmo_multipayment_cvs.php', 'gmo_multipayment_payeasy.php', 'gmo_multipayment_cctkn.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('gmo_multipayment_cc.php', 'gmo_multipayment_ccreg.php', 'gmo_multipayment_cvs.php', 'gmo_multipayment_payeasy.php', 'gmo_multipayment_cctkn.php')");
    ///////////////////////////////////////////////
    // Modified by tommy from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################





##########################################################################################
// START アドオンの設定ページで動作する関数
##########################################################################################

/**
 * 注文金額と入金金額が相違した注文に割り当てる注文ステータスのリストを生成
 *
 * @return array
 */
function fn_settings_variants_addons_gmo_multipayment_pending_status()
{
    // 配列を初期化
    $variants = array();

    // 注文ステータスのコードと名称を取得
    $order_statuses = db_get_array("SELECT ?:statuses.status_id, ?:statuses.status, ?:status_descriptions.description FROM ?:statuses LEFT JOIN ?:status_descriptions ON ?:statuses.status_id = ?:status_descriptions.status_id WHERE ?:statuses.type = 'O' AND ?:status_descriptions.lang_code = ?s", DESCR_SL);

    // 在庫が減少する注文ステータスのみリストに表示する
    if($order_statuses){
        foreach($order_statuses as $order_status) {
            $inventory_setting = db_get_field("SELECT value FROM ?:status_data WHERE param = 'inventory' AND status_id = ?i", $order_status['status_id']);
            if($inventory_setting == 'D'){
                $variants[$order_status['status']] = $order_status['description'];
            }
        }
    }
    return $variants;
}
##########################################################################################
//  END  アドオンの設定ページで動作する関数
##########################################################################################





##########################################################################################
// START その他の関数
##########################################################################################

/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * GMOペイメントゲートウェイに送信するパラメータをセット
 *
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_gmomp_get_params($type, $order_id, $order_info, $processor_data)
{
    // 送信パラメータを初期化
    $params = array();

    // 処理別に異なるパラメータをセット
    switch($type){
        // クレジットカード決済（取引登録）
        case 'entrytran':

            // オーダーID
            $params['OrderID'] = fn_gmomp_get_gmo_order_id($order_id);

            // ショップID
            $params['ShopID'] = Registry::get('addons.gmo_multipayment.shop_id');

            // ショップパスワード
            $params['ShopPass'] = Registry::get('addons.gmo_multipayment.shop_pass');

            // 処理区分
            $params['JobCd'] = $processor_data['processor_params']['jobcd'];

            // 利用金額
            $params['Amount'] = round($order_info['total']);

            // 本人認証サービス（3Dセキュア）を利用する場合
            if( $processor_data['processor_params']['tdflag'] == 'true'){
                // 本人認証サービス利用フラグ
                $params['TdFlag'] = 1;
                // 3Dセキュア表示店舗名
                $store_name = Registry::get('addons.gmo_multipayment.td_tenant_name');
                $store_name_eucjp = mb_convert_encoding($store_name, 'EUC-JP', 'UTF-8');;
                $params['TdTenantName'] = base64_encode($store_name_eucjp);
            }

            break;

        // クレジットカード決済（決済実行）
        case 'exectran':

            // 支払方法
            $params['Method'] = $order_info['payment_info']['jp_cc_method'];

            // 支払方法が「分割」の場合
            if($params['Method'] == "2"){
                // 支払回数
                $params['PayTimes'] = $order_info['payment_info']['jp_cc_installment_times'];
            }

            ///////////////////////////////////////////////
            // Modified by tommy from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            $processor_script_name = fn_gmomp_get_processor_script_name_by_order_id($order_id);
            if($processor_script_name == 'gmo_multipayment_cctkn.php'){
                // トークン
                $params['Token'] = $order_info['payment_info']['token'];
            }
            else {
                // カード番号（数値以外の値は削除）
                $card_number = mb_ereg_replace('[^0-9]', '', $order_info['payment_info']['card_number']);
                $params['CardNo'] = $card_number;

                // 有効期限
                $params['Expire'] = $order_info['payment_info']['expiry_year'] . $order_info['payment_info']['expiry_month'];
            }

            ///////////////////////////////////////////////
            // Modified by tommy from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            // セキュリティコードによる認証を行う場合
            if( $processor_data['processor_params']['use_cvv'] == 'true' ){
                // セキュリティコード
                $params['SecurityCode'] = $order_info['payment_info']['cvv2'];
            }

            // HTTP_ACCEPT
            $params['HttpAccept'] = $_SERVER['HTTP_ACCEPT'];

            // HTTP_USER_AGENT
            $params['HttpUserAgent'] = $_SERVER['HTTP_USER_AGENT'];

            // 仕様端末情報
            $params['DeviceCategory'] = 0;

            break;

        // クレジットカード決済（登録済みカード決済実行）
        case 'exectran_ccreg':

            // 支払方法
            $params['Method'] = $order_info['payment_info']['jp_cc_method'];

            // 支払方法が「分割」の場合
            if($params['Method'] == "2"){
                // 支払回数
                $params['PayTimes'] = $order_info['payment_info']['jp_cc_installment_times'];
                // 支払方法が「ボーナス分割」の場合
            }

            // サイトID
            $params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

            // サイトパスワード
            $params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

            // 会員ID
            $params['MemberID'] = fn_gmomp_get_member_id($order_info['user_id']);

            // カード登録連番（0固定）
            $params['CardSeq'] = 0;

            // セキュリティコードによる認証を行う場合
            if( $processor_data['processor_params']['use_cvv'] == 'true' ){
                // セキュリティコード
                $params['SecurityCode'] = $order_info['payment_info']['cvv2'];
            }

            // HTTP_ACCEPT
            $params['HttpAccept'] = $_SERVER['HTTP_ACCEPT'];

            // HTTP_USER_AGENT
            $params['HttpUserAgent'] = $_SERVER['HTTP_USER_AGENT'];

            // 仕様端末情報
            $params['DeviceCategory'] = 0;

            break;

        // 会員登録
        case 'savemember':

            // サイトID
            $params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

            // サイトパスワード
            $params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

            // 会員ID
            $params['MemberID'] = Registry::get('addons.gmo_multipayment.uid_prefix') . '_' . $order_info['user_id'];

            ///////////////////////////////////////////////
            // Modified by tommy from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            $processor_script_name = fn_gmomp_get_processor_script_name_by_order_id($order_id);
            if($processor_script_name == 'gmo_multipayment_cctkn.php') {
                // トークン
                $params['Token'] = $order_info['payment_info']['token'];
            }
            ///////////////////////////////////////////////
            // Modified by tommy from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            break;

        // 決済後カード登録
        case 'tradedcard':

            // ショップID
            $params['ShopID'] = Registry::get('addons.gmo_multipayment.shop_id');

            // ショップパスワード
            $params['ShopPass'] = Registry::get('addons.gmo_multipayment.shop_pass');

            // サイトID
            $params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

            // サイトパスワード
            $params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

            break;

        // 登録済みカードの削除
        case 'deletecard':

            // サイトID
            $params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

            // サイトパスワード
            $params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

            // カード登録連番（0固定）
            $params['CardSeq'] = 0;

            break;

        // 登録済みカードの照会
        case 'searchcard':

            // サイトID
            $params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

            // サイトパスワード
            $params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

            // カード登録連番（0固定）
            $params['CardSeq'] = 0;

            break;

        // コンビニ決済（取引登録）、ペイジー決済（取引登録）
        case 'entrytrancvs':
        case 'entrytranpayeasy':

            // ショップID
            $params['ShopID'] = Registry::get('addons.gmo_multipayment.shop_id');

            // ショップパスワード
            $params['ShopPass'] = Registry::get('addons.gmo_multipayment.shop_pass');

            // オーダーID
            $params['OrderID'] = fn_gmomp_get_gmo_order_id($order_id);

            // 利用金額
            $params['Amount'] = round($order_info['total']);

            break;

        // コンビニ決済（決済実行）
        case 'exectrancvs':

            // 支払先コンビニコード
            $params['Convenience'] = $order_info['payment_info']['convenience'];

            // 氏名
            $lastname = mb_strcut(mb_convert_kana($order_info['b_firstname'], "KVHA", 'UTF-8'), 0, 20);
            $firstname = mb_strcut(mb_convert_kana($order_info['b_lastname'], "KVHA", 'UTF-8'), 0, 20);
            $params['CustomerName'] = mb_convert_encoding($lastname . $firstname, 'SJIS-WIN', 'UTF-8');

            // フリガナ
            $name_kana_info = fn_lcjp_get_name_kana($order_info);
            $lastname_kana = mb_strcut(mb_convert_kana($name_kana_info['firstname_kana'], "KVC", 'UTF-8'), 0, 20);
            $firstname_kana = mb_strcut(mb_convert_kana($name_kana_info['lastname_kana'], "KVC", 'UTF-8'), 0, 20);
            $params['CustomerKana'] = mb_convert_encoding($lastname_kana . $firstname_kana, 'SJIS-WIN', 'UTF-8');

            // 電話番号
            $params['TelNo'] = substr(preg_replace("/-/", "", mb_convert_kana($order_info['phone'],"a")), 0, 11);

            // 支払期限日数
            $params['PaymentTermDay'] = (int)$processor_data['processor_params']['paymenttermday'];

            // 結果通知先メールアドレス
            $params['MailAddress'] = $order_info['email'];

            // 加盟店メールアドレス
            $params['ShopMailAddress'] = Registry::get('settings.Company.company_orders_department');

            // ショップ名称
            $params['RegisterDisp1'] = mb_convert_encoding(mb_strcut(mb_convert_kana(fn_get_company_name($order_info['company_id']),'ASKV'), 0, 32), 'SJIS-WIN', 'UTF-8');

            // お問い合わせ先
            $params['ReceiptsDisp11'] = mb_convert_encoding(mb_strcut(mb_convert_kana($processor_data['processor_params']['receiptsdisp11'],'ASKV'), 0, 42), 'SJIS-WIN', 'UTF-8');

            // お問い合わせ先電話番号
            $params['ReceiptsDisp12'] = mb_convert_encoding(mb_strcut($processor_data['processor_params']['receiptsdisp12'], 0, 12), 'SJIS-WIN', 'UTF-8');

            // お問い合わせ先受付時間
            $params['ReceiptsDisp13'] = $processor_data['processor_params']['receiptsdisp13_start'] . '-' . $processor_data['processor_params']['receiptsdisp13_end'];

            break;

        // ペイジー決済（決済実行）
        case 'exectranpayeasy':

            // 氏名
            $lastname = mb_strcut(mb_convert_kana($order_info['b_firstname'], "KVHA", 'UTF-8'), 0, 20);
            $firstname = mb_strcut(mb_convert_kana($order_info['b_lastname'], "KVHA", 'UTF-8'), 0, 20);
            $params['CustomerName'] = mb_convert_encoding($lastname . $firstname, 'SJIS-WIN', 'UTF-8');

            // フリガナ
            $name_kana_info = fn_lcjp_get_name_kana($order_info);
            $lastname_kana = mb_strcut(mb_convert_kana($name_kana_info['firstname_kana'], "KVC", 'UTF-8'), 0, 20);
            $firstname_kana = mb_strcut(mb_convert_kana($name_kana_info['lastname_kana'], "KVC", 'UTF-8'), 0, 20);
            $params['CustomerKana'] = mb_convert_encoding($lastname_kana . $firstname_kana, 'SJIS-WIN', 'UTF-8');

            // 電話番号
            $params['TelNo'] = substr(preg_replace("/-/", "", mb_convert_kana($order_info['phone'],"a")), 0, 11);

            // 支払期限日数
            $params['PaymentTermDay'] = (int)$processor_data['processor_params']['paymenttermday'];

            // 結果通知先メールアドレス
            $params['MailAddress'] = $order_info['email'];

            // 加盟店メールアドレス
            $params['ShopMailAddress'] = Registry::get('settings.Company.company_orders_department');

            // ショップ名称
            $params['RegisterDisp1'] = mb_convert_encoding(mb_strcut(mb_convert_kana(fn_get_company_name($order_info['company_id']),'ASKV'), 0, 32), 'SJIS-WIN', 'UTF-8');

            // お問い合わせ先
            $params['ReceiptsDisp11'] = mb_convert_encoding(mb_strcut(mb_convert_kana($processor_data['processor_params']['receiptsdisp11'],'ASKV'), 0, 42), 'SJIS-WIN', 'UTF-8');

            // お問い合わせ先電話番号
            $params['ReceiptsDisp12'] = mb_convert_encoding(mb_strcut($processor_data['processor_params']['receiptsdisp12'], 0, 12), 'SJIS-WIN', 'UTF-8');

            // お問い合わせ先受付時間
            $params['ReceiptsDisp13'] = $processor_data['processor_params']['receiptsdisp13_start'] . '-' . $processor_data['processor_params']['receiptsdisp13_end'];

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // ペイジー決済タイプパラメータの追加
            ///////////////////////////////////////////////
            // 決済タイプ
            $params['PaymentType'] = 'E';
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            break;

        default:
            // do nothing
            break;
    }

    return $params;
}




/**
 * 27桁のオーダーIDを生成
 *
 * @param $order_id
 * @return string
 */
function fn_gmomp_get_gmo_order_id($order_id)
{
	// オーダーIDを生成
	return sprintf("%017d", (int)$order_id) . (string)time();
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $gmomp_exec_results
 * @param bool $flg_comments
 * @return bool
 */
function fn_gmomp_format_payment_info($type, $order_id, $payment_info, $gmomp_exec_results)
{
     // 注文IDが存在しない場合は処理を終了
    if( empty($order_id) ) return false;

    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

    // 注文データ内の支払関連情報を取得（注文完了ページ表示前に決済完了通知が実行された場合に対応するため）
    if(empty($payment_info)){
        $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');
    }

    // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
    foreach($order_ids_to_process as $order_id){

        // 支払情報がすでに存在する場合
        if( !empty($payment_info) ){
            // 支払情報が暗号化されている場合は復号化して変数にセット
            if( !is_array($payment_info)) {
                $info = @unserialize(fn_decrypt_text($payment_info));
            }else{
                // 支払情報を変数にセット
                $info = $payment_info;
            }
        }

        // 請求ステータスを取得
        $gmomp_cc_status = db_get_field("SELECT status_code FROM ?:jp_gmomp_cc_status WHERE order_id = ?i", $order_id);

        // 支払情報がすでに存在する場合
        if( !empty($info) ){
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 BOF
            ////////////////////////////////////////////////////////////////////
            foreach($info as $key => $val){
                switch($type){
                    // クレジットカード決済に関する処理の場合
                    case 'cc' :
                    case 'cc_sales_confirm' :
                    case 'cc_auth_cancel' :
                    case 'cc_sales_cancel' :
                    case 'cc_change' :
                    case 'cc_reauth' :
                        switch($key){
                            // カード決済に関する情報のみ保持
                            case 'jp_gmo_multipayment_order_id':
                            case 'jp_gmo_multipayment_cc_status':
                            case 'jp_gmo_multipayment_approve':
                            case 'jp_gmo_multipayment_tran_id':
                            case 'jp_gmo_multipayment_tran_date':
                            case 'jp_gmo_multipayment_method':
                            case 'jp_gmo_multipayment_paytimes':
                            case 'jp_gmo_multipayment_card_company':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // コンビニ決済に関する処理の場合
                    case 'cvs' :
                        switch($key){
                            // コンビニ決済に関する情報のみ保持
                            case 'jp_gmo_multipayment_order_id':
                            case 'jp_gmo_multipayment_cvs_name':
                            case 'jp_gmo_multipayment_cvs_confno':
                            case 'jp_gmo_multipayment_cvs_receiptno':
                            case 'jp_gmo_multipayment_cvs_paymentterm':
                            case 'jp_gmo_multipayment_tran_date':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // コンビニ決済（入金通知）に関する処理の場合
                    case 'cvs_notify':
                        switch($key){
                            // コンビニ決済（入金通知）に関する情報のみ保持
                            case 'jp_gmo_multipayment_order_id':
                            case 'jp_gmo_multipayment_cvs_name':
                            case 'jp_gmo_multipayment_tran_id':
                            case 'jp_gmo_multipayment_process_date':
                            case 'jp_gmo_multipayment_cvs_confno':
                            case 'jp_gmo_multipayment_cvs_receiptno':
                            case 'jp_gmo_multipayment_cvs_paymentterm':
                            case 'jp_gmo_multipayment_finishdate':
                            case 'jp_gmo_multipayment_paid_amount':
                            case 'jp_gmo_multipayment_receiptdate':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // ペイジー決済に関する処理の場合
                    case 'payeasy' :
                        switch($key){
                            // ペイジー決済に関する情報のみ保持
                            case 'jp_gmo_multipayment_order_id':
                            case 'jp_gmo_multipayment_payeasy_custid':
                            case 'jp_gmo_multipayment_payeasy_bkcode':
                            case 'jp_gmo_multipayment_payeasy_confno':
                            case 'jp_gmo_multipayment_payeasy_paymentterm':
                            case 'jp_gmo_multipayment_tran_date':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // ペイジー決済（入金通知）に関する処理の場合
                    case 'payeasy_notify' :
                        switch($key){
                            // ペイジー決済（入金通知）に関する情報のみ保持
                            case 'jp_gmo_multipayment_order_id':
                            case 'jp_gmo_multipayment_tran_id':
                            case 'jp_gmo_multipayment_process_date':
                            case 'jp_gmo_multipayment_payeasy_custid':
                            case 'jp_gmo_multipayment_payeasy_bkcode':
                            case 'jp_gmo_multipayment_payeasy_confno':
                            case 'jp_gmo_multipayment_payeasy_paymentterm':
                            case 'jp_gmo_multipayment_finishdate':
                            case 'jp_gmo_multipayment_paid_amount':
                            case 'jp_gmo_multipayment_receiptdate':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // その他の場合
                    default:
                        // do noting
                }
            }
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 EOF
            ////////////////////////////////////////////////////////////////////
        }

        ////////////////////////////////////////////////////////////////////
        // 各支払方法固有の項目 BOF
        ////////////////////////////////////////////////////////////////////
        switch($type){
            // クレジットカード
            case 'cc' :
            case 'cc_reauth' :

                if( !empty($gmomp_exec_results['OrderID']) ){
                    // オーダーID
                    $info['jp_gmo_multipayment_order_id'] = $gmomp_exec_results['OrderID'];
                }

                // 請求ステータス
                if(!empty($gmomp_cc_status)){
                    $info['jp_gmo_multipayment_cc_status'] = fn_gmomp_get_cc_status_name($gmomp_cc_status);
                }

                // 承認番号
                $info['jp_gmo_multipayment_approve'] = $gmomp_exec_results['Approve'];

                // トランザクションID
                $info['jp_gmo_multipayment_tran_id'] = $gmomp_exec_results['TranID'];

                // 決済日付
                $info['jp_gmo_multipayment_tran_date'] = fn_gmomp_format_date($gmomp_exec_results['TranDate']);

                // 支払方法
                $info['jp_gmo_multipayment_method'] = fn_gmomp_get_method_name($gmomp_exec_results['Method']);

                // 支払回数
                $info['jp_gmo_multipayment_paytimes'] = $gmomp_exec_results['PayTimes'];

                // カード会社
                $info['jp_gmo_multipayment_card_company'] = fn_gmomp_get_card_company($gmomp_exec_results['Forward']);

                break;

            // クレジットカード売上確定/与信取消/売上取消/金額変更
            case 'cc_sales_confirm' :
            case 'cc_auth_cancel' :
            case 'cc_sales_cancel' :
            case 'cc_change' :

                // 請求ステータス
                if(!empty($gmomp_cc_status)){
                    $info['jp_gmo_multipayment_cc_status'] = fn_gmomp_get_cc_status_name($gmomp_cc_status);
                }

                // 承認番号
                $info['jp_gmo_multipayment_approve'] = $gmomp_exec_results['Approve'];

                // トランザクションID
                $info['jp_gmo_multipayment_tran_id'] = $gmomp_exec_results['TranID'];

                // 決済日付
                $info['jp_gmo_multipayment_tran_date'] = fn_gmomp_format_date($gmomp_exec_results['TranDate']);

                // カード会社
                $info['jp_gmo_multipayment_card_company'] = fn_gmomp_get_card_company($gmomp_exec_results['Forward']);

                break;

            // コンビニ決済
            case 'cvs' :
                // オーダーID
                $info['jp_gmo_multipayment_order_id'] = $gmomp_exec_results['OrderID'];

                // 支払先コンビニ名
                $gmo_cvs_name = fn_gmomp_get_cvs_name($gmomp_exec_results['Convenience']);
                if( !empty($gmo_cvs_name) ){
                    $info['jp_gmo_multipayment_cvs_name'] = $gmo_cvs_name;
                }

                // 確認番号
                $info['jp_gmo_multipayment_cvs_confno'] = $gmomp_exec_results['ConfNo'];

                // 受付番号
                $info['jp_gmo_multipayment_cvs_receiptno'] = $gmomp_exec_results['ReceiptNo'];

                // 支払期限日時
                $info['jp_gmo_multipayment_cvs_paymentterm'] = fn_gmomp_format_date($gmomp_exec_results['PaymentTerm']);

                // 決済日付
                $info['jp_gmo_multipayment_tran_date'] = fn_gmomp_format_date($gmomp_exec_results['TranDate']);

                break;

            // コンビニ決済（入金通知）
            case 'cvs_notify' :
                // オーダーID
                $info['jp_gmo_multipayment_order_id'] = $gmomp_exec_results['OrderID'];

                // 支払先コンビニ名
                $gmo_cvs_name = fn_gmomp_get_cvs_name($gmomp_exec_results['CvsCode']);
                if( !empty($gmo_cvs_name) ){
                    $info['jp_gmo_multipayment_cvs_name'] = $gmo_cvs_name;
                }

                // トランザクションID
                $info['jp_gmo_multipayment_tran_id'] = $gmomp_exec_results['TranID'];

                // 処理日付
                $info['jp_gmo_multipayment_process_date'] = fn_gmomp_format_date($gmomp_exec_results['TranDate']);

                // 確認番号
                $info['jp_gmo_multipayment_cvs_confno'] = $gmomp_exec_results['CvsConfNo'];

                // 受付番号
                $info['jp_gmo_multipayment_cvs_receiptno'] = $gmomp_exec_results['CvsReceiptNo'];

                // 支払期限日時
                $info['jp_gmo_multipayment_cvs_paymentterm'] = fn_gmomp_format_date($gmomp_exec_results['PaymentTerm']);

                // 入金確定日時
                $info['jp_gmo_multipayment_finishdate'] = fn_gmomp_format_date($gmomp_exec_results['FinishDate']);

                // 入金金額
                $info['jp_gmo_multipayment_paid_amount'] = $gmomp_exec_results['Amount'];

                // 受付日時
                $info['jp_gmo_multipayment_receiptdate'] = fn_gmomp_format_date($gmomp_exec_results['ReceiptDate']);

                break;

            // ペイジー決済
            case 'payeasy' :
                // オーダーID
                $info['jp_gmo_multipayment_order_id'] = $gmomp_exec_results['OrderID'];

                // お客様番号
                $info['jp_gmo_multipayment_payeasy_custid'] = $gmomp_exec_results['CustID'];

                // 収納機関番号
                $info['jp_gmo_multipayment_payeasy_bkcode'] = $gmomp_exec_results['BkCode'];

                // 確認番号
                $info['jp_gmo_multipayment_payeasy_confno'] = $gmomp_exec_results['ConfNo'];

                // 支払期限日時
                $info['jp_gmo_multipayment_payeasy_paymentterm'] = fn_gmomp_format_date($gmomp_exec_results['PaymentTerm']);

                // 決済日付
                $info['jp_gmo_multipayment_tran_date'] = fn_gmomp_format_date($gmomp_exec_results['TranDate']);

                break;

            // ペイジー決済（入金通知）
            case 'payeasy_notify' :
                // オーダーID
                $info['jp_gmo_multipayment_order_id'] = $gmomp_exec_results['OrderID'];

                // トランザクションID
                $info['jp_gmo_multipayment_tran_id'] = $gmomp_exec_results['TranID'];

                // 処理日付
                $info['jp_gmo_multipayment_process_date'] = fn_gmomp_format_date($gmomp_exec_results['TranDate']);

                // お客様番号
                $info['jp_gmo_multipayment_payeasy_custid'] = $gmomp_exec_results['CustID'];

                // 収納機関番号
                $info['jp_gmo_multipayment_payeasy_bkcode'] = $gmomp_exec_results['BkCode'];

                // 確認番号
                $info['jp_gmo_multipayment_payeasy_confno'] = $gmomp_exec_results['ConfNo'];

                // 支払期限日時
                $info['jp_gmo_multipayment_payeasy_paymentterm'] = fn_gmomp_format_date($gmomp_exec_results['PaymentTerm']);

                // 入金確定日時
                $info['jp_gmo_multipayment_finishdate'] = fn_gmomp_format_date($gmomp_exec_results['FinishDate']);

                // 入金金額
                $info['jp_gmo_multipayment_paid_amount'] = $gmomp_exec_results['Amount'];

                // 受付日時
                $info['jp_gmo_multipayment_receiptdate'] = fn_gmomp_format_date($gmomp_exec_results['ReceiptDate']);

                break;

            // その他
            default:
                // do nothing

        }
        ////////////////////////////////////////////////////////////////////
        // 各支払方法固有の項目 EOF
        ////////////////////////////////////////////////////////////////////

        // 支払情報を暗号化
        $_data = fn_encrypt_text(serialize($info));

        // 注文データ内の支払関連情報の有無をチェック
        $tmp_order_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

        // 注文データ内の支払関連情報が存在する場合
        if( !empty($tmp_order_id) ){
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
}




/**
 * GMOペイメントゲートウェイに各種データを送信
 *
 * @param $type
 * @param $params
 * @return mixed|string
 */
function fn_gmomp_send_request($type, $params, $mode)
{
    // データ送信先URLと結果パラメータを初期化
    $target_url = '';
    $result = '';

    switch($type){
        // カード決済（取引登録）
        case 'entrytran':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_ENTRYTRAN;
            }else{
                $target_url = GMOMP_LIVE_URL_ENTRYTRAN;
            }
            break;

        // カード決済（決済実行）
        case 'exectran':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_EXECTRAN;
            }else{
                $target_url = GMOMP_LIVE_URL_EXECTRAN;
            }
            break;

        // カード決済（3Dセキュアによる決済実行）
        case 'securetran':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_SECURETRAN;
            }else{
                $target_url = GMOMP_LIVE_URL_SECURETRAN;
            }
            break;

        // 会員登録
        case 'savemember':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_SAVEMEMBER;
            }else{
                $target_url = GMOMP_LIVE_URL_SAVEMEMBER;
            }
            break;

        // 会員検索
        case 'searchmember':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_SEARCHMEMBER;
            }else{
                $target_url = GMOMP_LIVE_URL_SEARCHMEMBER;
            }
            break;

        // 決済後カード登録
        case 'tradedcard':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_TRADEDCARD;
            }else{
                $target_url = GMOMP_LIVE_URL_TRADEDCARD;
            }
            break;

        // 決済取消、再オーソリ、売上確定
        case 'altertran':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_ALTERTRAN;
            }else{
                $target_url = GMOMP_LIVE_URL_ALTERTRAN;
            }
            break;

        // 決済取消、再オーソリ、売上確定
        case 'changetran':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_CHANGETRAN;
            }else{
                $target_url = GMOMP_LIVE_URL_CHANGETRAN;
            }
            break;

        // 登録済みカード削除
        case 'deletecard':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_DELETECARD;
            }else{
                $target_url = GMOMP_LIVE_URL_DELETECARD;
            }
            break;

        // 登録済みカード照会
        case 'searchcard':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_SEARCHCARD;
            }else{
                $target_url = GMOMP_LIVE_URL_SEARCHCARD;
            }
            break;

        // コンビニ決済（取引登録）
        case 'entrytrancvs':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_ENTRYTRANCVS;
            }else{
                $target_url = GMOMP_LIVE_URL_ENTRYTRANCVS;
            }
            break;

        // コンビニ決済（決済実行）
        case 'exectrancvs':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_EXECTRANCVS;
            }else{
                $target_url = GMOMP_LIVE_URL_EXECTRANCVS;
            }
            break;

        // ペイジー決済（取引登録）
        case 'entrytranpayeasy':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_ENTRYTRANPAYEASY;
            }else{
                $target_url = GMOMP_LIVE_URL_ENTRYTRANPAYEASY;
            }
            break;

        // ペイジー決済（決済実行）
        case 'exectranpayeasy':
            if($mode == 'test'){
                $target_url = GMOMP_TEST_URL_EXECTRANPAYEASY;
            }else{
                $target_url = GMOMP_LIVE_URL_EXECTRANPAYEASY;
            }
            break;

        // その他
        default:
            // do nothing
    }

    // 送信先URLが指定されている場合
    if( !empty($target_url) ){
        // GMOペイメントゲートウェイにデータを送信
        $result = Http::post($target_url, $params);
    }

    return $result;
}




/**
 * GMOペイメントゲートウェイからの戻り値を配列化
 *
 * @param $res_content
 * @return array|string
 */
function fn_gmomp_get_result_array($res_content)
{
    // 変数を初期化
    $gmomp_results = array();

    // アンパサンドをセパレータとして戻り値を配列化
    $result_array_amps = explode("&", $res_content);

    // 等号をセパレータとして戻り値を配列化
    foreach( $result_array_amps as $result_array_amp ){
        if(!empty($result_array_amp)){
            $result_array_eq = explode("=", $result_array_amp, 2);
            $gmomp_results[$result_array_eq[0]] = trim($result_array_eq[1]);
        }
    }

    return $gmomp_results;
}




/**
 * 日時データを読みやすくフォーマット
 *
 * @param $date
 * @param string $time
 * @return bool|string
 */
function fn_gmomp_format_date($datetime)
{
    $_year = substr($datetime, 0, 4);
    $_month = substr($datetime, 4, 2);
    $_date = substr($datetime, 6, 2);
    $_hour = substr($datetime, 8, 2);
    $_minute = substr($datetime, 10, 2);

    return $_year . '/' . $_month . '/' . $_date . ' ' . $_hour . ':' . $_minute;
}




/**
 * GMOペイメントゲートウェイのオーダーIDからCS-Cartの注文IDを取得
 *
 * @param $gmomp_order_id
 * @return int|string
 */
function fn_gmomp_get_order_id($gmomp_order_id)
{
	// オーダーIDの先頭17桁を抽出（末尾10桁はUNIXタイムスタンプのため除外）
	$order_id = substr($gmomp_order_id, 0, 17);

	// オーダーIDを整数化（プレースホルダとして付与された 0 を削除）
    $order_id = (int)$order_id;

	return $order_id;
}




/**
 * エラーメッセージを表示
 *
 * @param $errcode
 * @param $errinfo
 */
function fn_gmomp_set_err_msg($errcode, $errinfo)
{
    // エラーコードおよびエラー詳細コードを配列化
    $errcode_array = explode('|', $errcode);
    $errinfo_array = explode('|', $errinfo);

    // エラーメッセージを表示
    if(is_array($errcode_array)){
        foreach($errcode_array as $key => $code){
            fn_set_notification('E', __('jp_gmo_multipayment_cc_error'), fn_gmomp_get_err_msg($errinfo_array[$key]));
        }
    }
}




/**
 * エラーメッセージを取得
 *
 * @param $err_detail_code
 * @return string
 */
function fn_gmomp_get_err_msg($err_detail_code)
{
    // CAFISもしくはカード会社返却エラーコード
    if( strpos($err_detail_code, '42C') === 0){
        $err_msg = __('jp_gmo_multipayment_errmsg_42c');
    // その他のエラー
    }else{
        // エラー詳細コードを小文字に変換
        $err_detail_code = strtolower($err_detail_code);
        // エラーコードに対応する言語変数をセット
        $err_msg = __('jp_gmo_multipayment_errmsg_' . $err_detail_code);
    }

    // エラーコードに対応する言語変数が存在しない場合、汎用メッセージをセット
    if( strpos($err_msg, 'jp_gmo_multipayment_errmsg_') === 0 || strpos($err_msg, 'jp_gmo_multipayment_errmsg_') > 0) {
        $err_msg = __('jp_gmo_multipayment_cc_failed');
    }

    // エラーメッセージを返す
    return $err_msg;
}




/**
 * 入金通知データのバリデーション
 *
 * @param $data_received
 * @return bool
 */
function fn_gmomp_validate_notification($data_received)
{
    $is_valid = true;

    // 受信したショップIDとCS-Cartに登録されたショップIDが一致しない場合はエラー
    if( $data_received['ShopID'] != Registry::get('addons.gmo_multipayment.shop_id') ){
        $is_valid = false;

    // エラーコードやエラー詳細コードがセットされている場合はエラー
    }elseif( !empty($data_received['ErrCode']) || !empty($data_received['ErrInfo']) ){
        $is_valid = false;

    // 「決済完了」以外のステータスがセットされている場合はエラー
    }elseif( $data_received['Status'] != 'PAYSUCCESS' ){
        $is_valid = false;
    }

    return $is_valid;
}

/**
 * 注文IDから支払方法IDに紐付けられた決済代行サービスのスクリプトファイル名を取得
 *
 * @param $order_id
 * @return bool
 */
function fn_gmomp_get_processor_script_name_by_order_id($order_id)
{
    // 支払方法IDを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);

    // 支払方法IDに紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_get_processor_data($payment_id);

    // 決済に使用する支払方法に関する情報を返す
    if( !empty($processor_data['processor_script']) ){
        return $processor_data['processor_script'];
    }else{
        return false;
    }
}
/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 EOF
/////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////
// カード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 支払方法を取得
 *
 * @param $method_id
 * @return string
 */
function fn_gmomp_get_method_name($method_id)
{
    if(empty($method_id)) return __('unknown');

    switch($method_id){
        case 1:
            return __('jp_cc_onetime');
            break;
        case 2:
            return __('jp_payment_installment');
            break;
        case 3:
            return __('jp_gmo_multipayment_cc_bonus');
            break;
        case 5:
            return __('jp_cc_revo');
            break;
        default:
            return __('unknown');
    }
}




/**
 * 支払方法名から支払方法IDを取得
 *
 * @param $method_name
 * @return bool|int
 */
function fn_gmomp_get_method_id_by_name($method_name)
{
    if( empty($method_name) ) return false;

    switch($method_name){
        // 一括
        case __('jp_cc_onetime'):
            return 1;
            break;
        // 分割
        case __('jp_payment_installment'):
            return 2;
            break;
        // ボーナス一括
        case __('jp_gmo_multipayment_cc_bonus'):
            return 3;
            break;
        // リボ
        case __('jp_cc_revo'):
            return 5;
            break;
        // その他
        default:
            return false;
    }

}






/**
 * ステータスコードをセット
 *
 * @param $order_id
 * @param $job_code
 * @param string $access_id
 * @param string $access_pass
 */
function fn_gmomp_update_cc_status_code($order_id, $job_code, $process_timestamp = '', $access_id = '', $access_pass = '')
{
    // 注文確定前の場合
    if($job_code == 'IN_PROCESS'){
        $_data = array (
            'order_id' => $order_id,
            'status_code' => fn_gmomp_get_status_code($job_code),
            'access_id' => $access_id,
            'access_pass' => $access_pass,
        );

    // 注文確定後の場合
    }else{
        $_data = array (
            'order_id' => $order_id,
            'status_code' => fn_gmomp_get_status_code($job_code),
        );

        // ステータスコードに応じてタイムスタンプをセット
        if( !empty($process_timestamp) ){
            fn_gmomp_set_timestamp($_data, $process_timestamp);
        }
    }

    // 当該注文に関するステータスコード関連レコードの存在チェック
    $is_exists = db_get_row("SELECT * FROM ?:jp_gmomp_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文に関するステータスコード関連レコードが存在する場合
    if( !empty($is_exists) ){
        // レコードを更新
        db_query("UPDATE ?:jp_gmomp_cc_status SET ?u WHERE order_id = ?i", $_data, $order_id);
    // 当該注文に関するステータスコード関連レコードが存在しない場合
    }else{
        // レコードを新規追加
        db_query("REPLACE INTO ?:jp_gmomp_cc_status ?e", $_data);
    }
}




/**
 * 処理区分に応じてステータスコードを取得
 *
 * @param $job_code
 * @return string
 */
function fn_gmomp_get_status_code($job_code)
{
    switch($job_code){
        case 'AUTH':
            return 'AUTH_OK';
            break;
        case 'CAPTURE':
            return 'CAPTURE_OK';
            break;
        default:
            return strtoupper($job_code);
            // do nothing
    }
}




/**
 * ステータスコードに応じてタイムスタンプをセット
 *
 * @param $_data
 * @return bool
 */
function fn_gmomp_set_timestamp(&$_data, $process_timestamp)
{
    // ステータスコードが存在しない場合、処理を終了
    if( empty($_data['status_code']) ) return false;

    // ステータスコードに応じてタイムスタンプをセット
    switch($_data['status_code']){
        // 与信OKの場合
        case 'AUTH_OK':
            // 与信日時のタイムスタンプをセット
            $_data['auth_timestamp'] = $process_timestamp;
            break;

        // 即時売上または実売上の場合
        case 'CAPTURE_OK':
        case 'SALES_CONFIRMED':
            // 売上日時のタイムスタンプをセット
            $_data['capture_timestamp'] = $process_timestamp;
            break;

        // 中間処理の場合はタイムスタンプはセットしない
        case 'IN_PROCESS':
            // do nothing
            break;

        // その他のステータスコードの場合
        default:
            // 与信日時・売上日時のタイムスタンプをリセット
            $_data['auth_timestamp'] = 0;
            $_data['capture_timestamp'] = 0;
    }
}




/**
 * 注文データ内に格納されたクレジット請求ステータスを更新
 *
 * @param $order_id
 * @param string $type
 */
function fn_gmomp_update_cc_status($order_id, $type = 'cc_sales_confirm', $process_timestamp = '')
{
    // クレジット請求ステータスを初期化
    $status_code = '';

    // 処理内容に応じてセットする値を変更
    switch($type){
        // 実売上
        case 'cc_sales_confirm':
            $status_code = 'SALES_CONFIRMED';
            $msg = __('jp_gmo_multipayment_cc_sales_completed');
            break;
        // 金額変更
        case 'cc_change':
            // 支払方法に関するデータを取得
            $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
            $processor_data = fn_get_processor_data($payment_id);
            $jobcd = $processor_data['processor_params']['jobcd'];
            if($jobcd == 'AUTH'){
                $status_code = 'AUTH_OK';
            }elseif($jobcd == 'CAPTURE'){
                $status_code = 'CAPTURE_OK';
            }
            $msg = __('jp_gmo_multipayment_cc_auth_changed');
            break;
        // 与信取消
        case 'cc_auth_cancel':
            $status_code = 'AUTH_CANCELLED';
            $msg = __('jp_gmo_multipayment_cc_auth_cancelled');
            break;
        // 売上取消
        case 'cc_sales_cancel':
            $status_code = 'SALES_CANCELLED';
            $msg = __('jp_gmo_multipayment_cc_sales_cancelled');
            break;
        // 再オーソリ
        case 'cc_reauth':
            // 支払方法に関するデータを取得
            $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
            $processor_data = fn_get_processor_data($payment_id);
            $jobcd = $processor_data['processor_params']['jobcd'];
            if($jobcd == 'AUTH'){
                $status_code = 'AUTH_OK';
            }elseif($jobcd == 'CAPTURE'){
                $status_code = 'CAPTURE_OK';
            }
            $msg = __('jp_gmo_multipayment_cc_reauth_completed');
        // その他
        default:
            // do nothing
    }

    // クレジット請求ステータスが設定されている場合
    if( !empty($status_code) ){
        // クレジット請求ステータスを更新
        fn_gmomp_update_cc_status_code($order_id, $status_code, $process_timestamp);
        // 処理完了メッセージを表示
        fn_set_notification('N', __('information'), $msg, 'K');
    }
}




/**
 * カード会社名を取得
 *
 * @param $forward
 * @return string
 */
function fn_gmomp_get_card_company($forward)
{
    if(empty($forward)) return __('unknown');

    switch($forward){
        case '137':
            return 'アメリカン・エキスプレス・インターナショナル，Inc';
            break;
        case '15250':
            return 'ユーシーカード';
            break;
        case '2959876':
            return 'ライフ';
            break;
        case '2K60252':
            return 'コメリ';
            break;
        case '2P50002':
            return 'GE コンシューマー・ファイナンス';
            break;
        case '2S10035':
            return 'クレディセゾン';
            break;
        case '2S49631':
            return 'すみしんライフカード';
            break;
        case '2S63046':
            return 'イオンクレジットサービス';
            break;
        case '2a77001':
            return '日立キャピタル';
            break;
        case '2a90100':
            return 'バンクカードサービス';
            break;
        case '2a99660':
            return '(ダイナース)シティカードジャパン';
            break;
        case '2a99661':
            return 'ジェーシービー';
            break;
        case '2a99662':
            return '三菱UFJ ニコス(旧DC)';
            break;
        case '2a99663':
            return '三井住友カード';
            break;
        case '2a99664':
            return '三菱UFJ ニコス(旧UFJ)';
            break;
        case '2s50001':
            return '三菱UFJ ニコス(旧ニコス)';
            break;
        case '2s50057':
            return 'クオーク';
            break;
        case '2s50109':
            return '東急カード';
            break;
        case '2s58588':
            return 'セントラルファイナンス';
            break;
        case '2s58650':
            return '全日信販';
            break;
        case '2s59110':
            return 'ジャックス';
            break;
        case '2s59681':
            return 'アプラス';
            break;
        case '2s59875':
            return '楽天KC';
            break;
        case '2s59880':
            return 'オリエントコーポレーション';
            break;
        case '2s59910':
            return 'オークス';
            break;
        case '2s60020':
            return 'ポケットカード';
            break;
        case '2s61203':
            return '高島屋クレジット';
            break;
        case '2s62234':
            return 'UCS';
            break;
        case '2s62781':
            return 'エポス';
            break;
        case '2s63007':
            return 'イズミヤカード';
            break;
        case '2s63141':
            return 'オーエムシーカード';
            break;
        case '2s64102':
            return '東武';
            break;
        case '2s65068':
            return 'JFR カード';
            break;
        case '2s77020':
            return 'パナソニック';
            break;
        case '2s77334':
            return 'トヨタファイナンス';
            break;
        case '2s80001':
            return '日本専門店会連盟';
            break;
        case '2s83000':
            return '日本商店連盟';
            break;
        case '8a99682':
            return '協同クレジットサービス';
            break;
        case 'LAWSON':
            return 'ローソンCSカード';
            break;
        default:
            return __('unknown');
            break;
    }

    return __('unknown');
}
/////////////////////////////////////////////////////////////////////////////////////
// カード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// 登録済みカード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * GMOペイメントゲートウェイに登録済みのカード情報を削除
 *
 * @param $user_id
 */
function fn_gmomp_delete_card_info($user_id)
{
    // GMOペイメントゲートウェイに会員登録済みかチェック
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'gmomp_ccreg');

    // GMOペイメントゲートウェイに会員登録済みの場合
    if(!empty($member_id)){
        // 登録済みカード決済用レコードを削除
        db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'gmomp_ccreg');

        // GMOペイメントゲートウェイに登録されたカード情報を削除
        $success_delete = fn_gmomp_delete_cc($member_id, $user_id);

        // GMOペイメントゲートウェイに登録されたカード情報の削除に成功した場合
        if( $success_delete ){
            // 削除成功メッセージを表示
            fn_set_notification('N', __('notice'), __('jp_gmo_multipayment_ccreg_delete_success'));
        // GMOペイメントゲートウェイに登録されたカード情報の削除に失敗した場合
        }else{
            // 削除失敗メッセージを表示
            fn_set_notification('N', __('notice'), __('jp_gmo_multipayment_ccreg_delete_failed'));
        }
    }
}




/**
 * 登録済みカード決済を検出
 *
 * @param $payment_id
 * @return bool
 */
function fn_gmomp_is_ccreg($payment_id)
{
    // CS-Cartに登録された決済代行サービスIDを取得
	$processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $payment_id);

    // 決済代行サービスIDがCS-Cartに登録されている場合
	if( !empty($processor_id) ){
        // 登録されている決済代行サービスのPHPスクリプトファイル名を取得
		$processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

        // 決済代行サービスのPHPスクリプトファイル名がGMOペイメントゲートウェイの登録済みカード決済である場合にtrueを返す
		if(	!empty($processor_script) && $processor_script == 'gmo_multipayment_ccreg.php' ){
			return true;
		}
	}

	return false;
}




/**
 * 注文に使用したカード番号をGMOペイメントゲートウェイに登録
 *
 * @param $order_info
 * @param $processor_data
 * @return bool
 */
function fn_gmomp_register_cc_info($order_info, $processor_data)
{
    // GMOペイメントゲートウェイに会員登録済みかチェック
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $order_info['user_id'], 'gmomp_ccreg');

    // CS-Cart上でカード情報を削除した会員でないかチェック
    if(empty($member_id)){
        $member_id = fn_gmomp_search_member($order_info['user_id']);
    }

    // GMOペイメントゲートウェイに会員登録されていない場合
    if( empty($member_id) ){

        // 会員登録に必要なパラメータを取得して、会員登録を実行
        $savemember_params = fn_gmomp_get_params('savemember', $order_info['order_id'], $order_info, $processor_data);
        $savemember_result = fn_gmomp_send_request('savemember', $savemember_params, $processor_data['processor_params']['mode']);

        // 会員登録に関するリクエスト送信が正常終了した場合
        if (!empty($savemember_result)) {

            // GMOペイメントゲートウェイから受信した会員登録情報を配列に格納
            $gmomp_savemember_results = fn_gmomp_get_result_array($savemember_result);

            // 会員登録が正常に終了している場合
            if (!empty($gmomp_savemember_results['MemberID']) && empty($gmomp_savemember_results['ErrCode']) && empty($gmomp_savemember_results['ErrInfo'])) {
                $member_id = $gmomp_savemember_results['MemberID'];
            // 会員登録に失敗した場合
            }else{
                // エラーメッセージを表示して処理を終了
                fn_gmomp_set_err_msg($gmomp_savemember_results['ErrCode'], $gmomp_savemember_results['ErrInfo']);
                return false;
            }

        // 会員登録に関するリクエスト送信に失敗した場合
        }else{
            // エラーメッセージを表示して処理を終了
            fn_set_notification('E', __('jp_gmo_multipayment_ccreg_error'),__('jp_gmo_multipayment_ccreg_register_failed'));
            return false;
        }
    }

    // GMOペイメントゲートウェイに会員登録済の場合
    if( !empty($member_id) ){

        // 登録済みカード情報を参照
        $card_exists = fn_gmomp_get_registered_cc($order_info, $processor_data, $member_id);

        // カードが登録済みの場合
        if( !empty($card_exists) ){
            // 登録済みカードを削除
            $success_delete = fn_gmomp_delete_cc($member_id, $order_info['user_id']);

            // 登録済みカードの削除に失敗した場合
            if($success_delete == false){
                // エラーメッセージを表示して処理を終了
                fn_set_notification('E', __('jp_gmo_multipayment_ccreg_error'), __('jp_gmo_multipayment_ccreg_register_failed'));
                return false;
            }
        }

        // カード番号登録に必要なパラメータを取得
        $tradedcard_params = fn_gmomp_get_params('tradedcard', $order_info['order_id'], $order_info, $processor_data);
        $tradedcard_params['OrderID'] = $order_info['gmo_order_id'];
        $tradedcard_params['MemberID'] = $member_id;

        // 決済後カード登録実行
        $tradedcard_result = fn_gmomp_send_request('tradedcard', $tradedcard_params, $processor_data['processor_params']['mode']);

        // 決済後カード登録に関するリクエスト送信が正常終了した場合
        if (!empty($tradedcard_result)) {

            // GMOペイメントゲートウェイから受信した決済後カード登録情報を配列に格納
            $gmomp_tradedcard_results = fn_gmomp_get_result_array($tradedcard_result);

            // 決済後カード登録が正常に終了している場合
            if ( !is_null($gmomp_tradedcard_results['CardSeq']) && empty($gmomp_tradedcard_results['ErrCode']) && empty($gmomp_tradedcard_results['ErrInfo'])) {
                fn_set_notification('N', __('information'), __('jp_gmo_multipayment_ccreg_register_success'));
                $_data = array('user_id' => $order_info['user_id'],
                    'payment_method' => 'gmomp_ccreg',
                    'quickpay_id' => $member_id,
                );
                db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);

            // 決済後カード登録に失敗した場合
            }else{
                // エラーメッセージを表示して処理を終了
                fn_gmomp_set_err_msg($gmomp_tradedcard_results['ErrCode'], $gmomp_tradedcard_results['ErrInfo']);
                return false;
            }
        // 決済後カード登録に関するリクエスト送信に失敗した場合ｓ
        }else{
            // エラーメッセージを表示して処理を終了
            fn_set_notification('E', __('jp_gmo_multipayment_ccreg_error'),__('jp_gmo_multipayment_ccreg_register_failed'));
            return false;
        }
    }

    return true;
}




/**
 * 会員IDをキーにしてGMOペイメントゲートウェイに登録された会員情報を検索
 *
 * @param $user_id
 * @return bool
 */
function fn_gmomp_search_member($user_id)
{
    // パラメータを初期化
    $searchmember_params = array();

    ///////////////////////////////////////////////////
    // 会員検索に必要なパラメータを取得 BOF
    ///////////////////////////////////////////////////
    // サイトID
    $searchmember_params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

    // サイトパスワード
    $searchmember_params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

    // 会員ID
    $searchmember_params['MemberID'] = Registry::get('addons.gmo_multipayment.uid_prefix') . '_' . $user_id;
    ///////////////////////////////////////////////////
    // 会員検索に必要なパラメータを取得 EOF
    ///////////////////////////////////////////////////

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    // トークン決済に対応
    ///////////////////////////////////////////////
    // 支払方法に関するデータを取得（接続先のテスト環境 / 本番環境を判定するために使用）
    //$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = ?s AND ?:payments.status = 'A'", 'gmo_multipayment_cc.php');
    $payment_id = db_get_field("select ?:orders.payment_id from ?:orders  join ?:payments on ?:orders.payment_id = ?:payments.payment_id join ?:payment_processors on ?:payments.processor_id = ?:payment_processors.processor_id where ?:payment_processors.processor_script in ('gmo_multipayment_cc.php', 'gmo_multipayment_cctkn.php') and ?:orders.user_id = ?i order by ?:orders.order_id desc limit 1", $user_id);
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////

    $processor_data = fn_get_processor_data($payment_id);

    // 会員検索
    $searchmember_result = fn_gmomp_send_request('searchmember', $searchmember_params, $processor_data['processor_params']['mode']);

    // 会員検索に関するリクエスト送信が正常終了した場合
    if (!empty($searchmember_result)) {

        // GMOペイメントゲートウェイから受信した会員検索結果を配列に格納
        $gmomp_searchmember_results = fn_gmomp_get_result_array($searchmember_result);

        // GMOペイメントゲートウェイに登録済みの会員の場合
        if ( !is_null($gmomp_searchmember_results['MemberID']) && empty($gmomp_searchmember_results['ErrCode']) && empty($gmomp_searchmember_results['ErrInfo'])) {
            // 会員IDを返す
            return $gmomp_searchmember_results['MemberID'];
        // GMOペイメントゲートウェイに未登録の会員の場合
        }else{
            return false;
        }
    // 登録済みカード削除に関するリクエスト送信に失敗した場合
    }else{
        return false;
    }
}




/**
 * 登録済みカード情報の削除
 *
 * @param $order_info
 * @param $processor_data
 * @param $member_id
 * @param $user_id
 * @return bool
 */
function fn_gmomp_delete_cc($member_id, $user_id)
{
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    // トークン決済に対応
    ///////////////////////////////////////////////
    // 支払方法に関するデータを取得（接続先のテスト環境 / 本番環境を判定するために使用）
    //$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = ?s AND ?:payments.status = 'A'", 'gmo_multipayment_cc.php');
    $payment_id = db_get_field("select ?:orders.payment_id from ?:orders  join ?:payments on ?:orders.payment_id = ?:payments.payment_id join ?:payment_processors on ?:payments.processor_id = ?:payment_processors.processor_id where ?:payment_processors.processor_script in ('gmo_multipayment_cc.php', 'gmo_multipayment_cctkn.php') and ?:orders.user_id = ?i order by ?:orders.order_id desc limit 1", $user_id);
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    ///////////////////////////////////////////////

    $processor_data = fn_get_processor_data($payment_id);

    ///////////////////////////////////////////////////
    // カード番号削除に必要なパラメータを取得 BOF
    ///////////////////////////////////////////////////
    // サイトID
    $deletecard_params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

    // サイトパスワード
    $deletecard_params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

    // カード登録連番（0固定）
    $deletecard_params['CardSeq'] = 0;

    // 会員ID
    $deletecard_params['MemberID'] = $member_id;
    ///////////////////////////////////////////////////
    // カード番号削除に必要なパラメータを取得 EOF
    ///////////////////////////////////////////////////

    // 登録済みカード削除
    $deletecard_result = fn_gmomp_send_request('deletecard', $deletecard_params, $processor_data['processor_params']['mode']);

    // 登録済みカード削除に関するリクエスト送信が正常終了した場合
    if (!empty($deletecard_result)) {

        // GMOペイメントゲートウェイから受信した登録済みカード削除情報を配列に格納
        $gmomp_deletecard_results = fn_gmomp_get_result_array($deletecard_result);

        // 登録済みカード削除が正常に終了している場合
        if ( !is_null($gmomp_deletecard_results['CardSeq']) && empty($gmomp_deletecard_results['ErrCode']) && empty($gmomp_deletecard_results['ErrInfo'])) {
            return true;
        // 登録済みカード削除に失敗した場合
        }else{
            return false;
        }
    // 登録済みカード削除に関するリクエスト送信に失敗した場合
    }else{
        return false;
    }
}




/**
 * GMOペイメントゲートウェイに登録されたカード情報を取得
 *
 * @param $order_info
 * @param $processor_data
 * @param $member_id
 */
function fn_gmomp_get_registered_cc($order_info, $processor_data, $member_id)
{
    // 登録済みカード照会に必要なパラメータを取得
    $searchcard_params = fn_gmomp_get_params('searchcard', $order_info['order_id'], $order_info, $processor_data);
    $searchcard_params['MemberID'] = $member_id;

    // 登録済みカード照会
    $searchcard_result = fn_gmomp_send_request('searchcard', $searchcard_params, $processor_data['processor_params']['mode']);

    // 登録済みカード照会に関するリクエスト送信が正常終了した場合
    if (!empty($searchcard_result)) {

        // GMOペイメントゲートウェイから受信した登録済みカード照会情報を配列に格納
        $gmomp_searchcard_results = fn_gmomp_get_result_array($searchcard_result);

        // 登録済みカード照会が正常に終了している場合
        if ( !is_null($gmomp_searchcard_results['CardSeq']) && empty($gmomp_searchcard_results['ErrCode']) && empty($gmomp_searchcard_results['ErrInfo'])) {
            return true;
        // 登録済みカード照会に失敗した場合
        }else{
            return false;
        }
    // 登録済みカード削除に関するリクエスト送信に失敗した場合ｓ
    }else{
        return false;
    }
}




/**
 * CS-Cartに格納された会員IDを取得
 *
 * @param $user_id
 * @return array
 */
function fn_gmomp_get_member_id($user_id)
{
    // CS-Cartに登録された会員IDを取得
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'gmomp_ccreg');

    return $member_id;
}




/**
 * GMOペイメントゲートウェイに登録されたカード情報を取得
 *
 * @param $user_id
 * @return array|bool
 */
function fn_gmomp_get_registered_card_info($user_id)
{
    // ユーザーIDが指定されていない場合は処理を終了
    if( empty($user_id) ) return false;

    // 登録済みカード情報を格納する変数を初期化
    $registered_card = false;

    // 支払方法に関するデータを取得
    $payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'gmo_multipayment_ccreg.php' AND ?:payments.status = 'A'");
    $processor_data = fn_get_processor_data($payment_id);

    // 会員IDを取得
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'gmomp_ccreg');

    // 会員IDが存在する場合
    if(!empty($member_id)){
        ///////////////////////////////////////////////////
        // 登録済みカード照会に必要なパラメータを取得 BOF
        ///////////////////////////////////////////////////
        $searchcard_params = array();

        // サイトID
        $searchcard_params['SiteID'] = Registry::get('addons.gmo_multipayment.site_id');

        // サイトパスワード
        $searchcard_params['SitePass'] = Registry::get('addons.gmo_multipayment.site_pass');

        // 会員ID
        $searchcard_params['MemberID'] = $member_id;

        // カード登録連番モード
        $searchcard_params['SeqMode'] = 0;

        // カード登録連番
        $searchcard_params['CardSeq'] = 0;
        ///////////////////////////////////////////////////
        // 登録済みカード照会に必要なパラメータを取得 EOF
        ///////////////////////////////////////////////////

        // 登録済みカード照会
        $searchcard_result = fn_gmomp_send_request('searchcard', $searchcard_params, $processor_data['processor_params']['mode']);

        // 登録済みカード照会に関するリクエスト送信が正常終了した場合
        if (!empty($searchcard_result)) {

            // GMOペイメントゲートウェイから受信した登録済みカード照会情報を配列に格納
            $gmomp_searchcard_results = fn_gmomp_get_result_array($searchcard_result);

            // 登録済みカード照会が正常に終了している場合
            if ( !is_null($gmomp_searchcard_results['CardSeq']) && empty($gmomp_searchcard_results['ErrCode']) && empty($gmomp_searchcard_results['ErrInfo'])) {

                $registered_card = array('card_number' => $gmomp_searchcard_results['CardNo'], 'card_valid_term' => $gmomp_searchcard_results['Expire']);

            // 登録済みカード照会に失敗した場合
            }else{
                // カード情報は表示しない
            }
        // 登録済みカード削除に関するリクエスト送信に失敗した場合
        }else{
            // カード情報は表示しない
        }
    }

    return $registered_card;
}
/////////////////////////////////////////////////////////////////////////////////////
//  登録済みカード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// 金額変更 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 金額変更可能な注文か判定する
 *
 * @param $order_id
 * @param $processor_data
 * @return bool
 */
function fn_gmomp_cc_is_changeable($order_id, $processor_data)
{
    // 子注文の存在有無をチェック
    $parent_order_info = db_get_row("SELECT is_parent_order, parent_order_id FROM ?:orders WHERE order_id = ?i", $order_id);

    // 親子関係を持つ注文ではない場合（マーケットプレイスやサプライヤー機能を使った注文を考慮）
    if( $parent_order_info['is_parent_order'] == 'N' && $parent_order_info['parent_order_id'] == 0 ) {

        // 編集前の注文で利用された決済方法を取得
        $org_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $org_payment_method_data = fn_get_payment_method_data($org_payment_id);
        $org_processor_id = $org_payment_method_data['processor_id'];
        $changable_processor_ids = array(9200, 9201, 9204);

        // 編集前後で共にGMOペイメントゲートウェイのカード決済または登録済みカード決済が選択されている場合
        if( in_array($org_processor_id, $changable_processor_ids) && in_array($processor_data['processor_id'], $changable_processor_ids)){

            // 注文データからクレジット請求ステータスを取得
            $cc_status = db_get_field("SELECT status_code FROM ?:jp_gmomp_cc_status WHERE order_id = ?i", $order_id);

            // ステータスコードが存在する場合
            if (!empty($cc_status)) {
                // ステータスコードが仮売上、即時売上、実売上のいずれかに該当する注文は利用額変更処理を許可
                switch ($cc_status) {
                    case 'AUTH_OK':
                    case 'CAPTURE_OK':
                    case 'SALES_CONFIRMED':
                        return array(true, 'cc_change');
                        break;

                    // ステータスコードが与信取消・売上取消のいずれかに該当する注文は再オーソリ処理を許可
                    case 'AUTH_CANCELLED':
                    case 'SALES_CANCELLED':
                        return array(true, 'cc_reauth');
                        break;

                    default:
                        // do nothing;
                }
            }
        }
    }

    return array(false, false);
}
/////////////////////////////////////////////////////////////////////////////////////
// 金額変更 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * クレジット請求ステータス名を取得
 *
 * @param $cc_status
 * @return string
 */
function fn_gmomp_get_cc_status_name($cc_status)
{
    // クレジット請求ステータス名を初期化
    $cc_status_name = '';

    // 請求ステータスコードに応じて請求ステータス名を取得
    switch($cc_status){
        // 仮売上
        case 'AUTH_OK':
            $cc_status_name = __('jp_gmo_multipayment_cc_auth_ok');
            break;
        // 与信NG
        case 'AUTH_NG':
            $cc_status_name = __('jp_gmo_multipayment_cc_auth_ng');
            break;
        // 与信取消
        case 'AUTH_CANCELLED':
            $cc_status_name = __('jp_gmo_multipayment_cc_auth_cancel');
            break;
        // 即時売上
        case 'CAPTURE_OK':
            $cc_status_name = __('jp_gmo_multipayment_cc_captured');
            break;
        // 実売上
        case 'SALES_CONFIRMED':
            $cc_status_name = __('jp_gmo_multipayment_cc_sales_confirm');
            break;
        // 売上取消
        case 'SALES_CANCELLED':
            $cc_status_name = __('jp_gmo_multipayment_cc_sales_cancel');
            break;
    }

    return $cc_status_name;
}




/**
 * 実売上・金額変更・与信取消・売上取消処理を実行
 *
 * @param $order_id
 * @param string $type
 * @param string $org_amount
 * @return bool
 */
function fn_gmomp_send_cc_request( $order_id, $type = 'cc_sales_confirm')
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_gmomp_check_process_validity($order_id, $type);

    // 指定した処理を行うのに適した注文でない場合
    if ( !$is_valid_order ){
        return false;
    }

    $params = array();

    // 金額変更の場合
    if($type == 'cc_change'){
        $transaction_type = 'changetran';
    // その他の場合
    }else{
        $transaction_type = 'altertran';
    }

    // アクセスIDとアクセスパスワードを取得
    $gmomp_cc_access_info = db_get_row("SELECT * FROM ?:jp_gmomp_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文の支払方法に関する情報を取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);

    //////////////////////////////////////////////////////////////////////////
    // 共通パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    // ショップID
    $params['ShopID'] = Registry::get('addons.gmo_multipayment.shop_id');

    // ショップパスワード
    $params['ShopPass'] = Registry::get('addons.gmo_multipayment.shop_pass');

    // アクセスID
    $params['AccessID'] = $gmomp_cc_access_info['access_id'];

    // アクセスパスワード
    $params['AccessPass'] = $gmomp_cc_access_info['access_pass'];
    //////////////////////////////////////////////////////////////////////////
    // 共通パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    switch($type){
        //  実売上
        case 'cc_sales_confirm':
            // 処理区分
            $params['JobCd'] = 'SALES';
            // 利用金額
            $total = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);
            $params['Amount'] = (int)$total;
            break;

        // 与信取消 / 売上取消
        case 'cc_auth_cancel':
        case 'cc_sales_cancel':
            // 処理区分
            $params['JobCd'] = fn_gmomp_get_cancel_jobcd($order_id, $type);
            break;

        // 金額変更
        case 'cc_change':
            // 処理区分
            $params['JobCd'] = $processor_data['processor_params']['jobcd'];
            // 利用金額
            $total = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);
            $params['Amount'] = (int)$total;
            break;

        // 再オーソリ
        case 'cc_reauth':
            // 処理区分
            $params['JobCd'] = $processor_data['processor_params']['jobcd'];

            // 利用金額
            $total = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);
            $params['Amount'] = (int)$total;

            // 支払情報から支払方法と支払回数を取得
            list($method, $paytimes) = fn_gmomp_get_selected_payment_method($order_id);

            if( !empty($method) ){
                $params['Method'] = $method;
            }

            if( !empty($paytimes) && $paytimes > 1){
                $params['PayTimes'] = $paytimes;
            }

            break;


        default :
            // do nothing
    }
    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    // 処理実行
    $altertran_result = fn_gmomp_send_request($transaction_type, $params, $processor_data['processor_params']['mode']);

    // GMOペイメントゲートウェイに対するリクエスト送信が正常終了した場合
    if (!empty($altertran_result)) {

        // GMOペイメントゲートウェイから受信した決済実行情報を配列に格納
        $gmomp_altertran_results = fn_gmomp_get_result_array($altertran_result);

        // 決済実行が正常に完了している場合
        if (!empty($gmomp_altertran_results['AccessID']) && !empty($gmomp_altertran_results['AccessPass']) && empty($gmomp_altertran_results['ErrCode']) && empty($gmomp_altertran_results['ErrInfo'])) {

            // 与信日時を取得
            if( !empty($gmomp_altertran_results['TranDate']) ){
                $process_timestamp = strtotime($gmomp_altertran_results['TranDate']);
            }else{
                $process_timestamp = time();
            }

            // 注文データ内に格納されたクレジット請求ステータスを更新
            fn_gmomp_update_cc_status($order_id, $type, $process_timestamp);

            // 注文情報を取得
            $order_info = fn_get_order_info($order_id);

            // DBに保管する支払い情報を生成
            fn_gmomp_format_payment_info($type, $order_id, $order_info['payment_info'], $gmomp_altertran_results);

            return true;

        // エラー処理
        } else {
            // エラーメッセージを表示
            fn_gmomp_set_err_msg($gmomp_altertran_results['ErrCode'], $gmomp_altertran_results['ErrInfo']);
        }

    // リクエスト送信が失敗した場合
    }else{
        // エラーメッセージを表示
        fn_set_notification('E', __('jp_gmo_multipayment_cc_error'), __('jp_gmo_multipayment_cc_status_change_failed'));
    }

    return false;
}




function fn_gmomp_get_selected_payment_method($order_id)
{
    // 注文情報を取得
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

    $paytimes = 1;

    // 支払情報がすでに存在する場合
    if( !empty($payment_info) ){
        // 支払情報が暗号化されている場合は復号化して変数にセット
        if( !is_array($payment_info)) {
            $info = @unserialize(fn_decrypt_text($payment_info));
        }else{
            // 支払情報を変数にセット
            $info = $payment_info;
        }

        if( !empty($info['jp_gmo_multipayment_method']) ){
            $method = fn_gmomp_get_method_id_by_name($info['jp_gmo_multipayment_method']);

            if($method == 2 || $method == 4){
                $paytimes = $info['jp_gmo_multipayment_paytimes'];
            }
        }

        // 支払方法と支払回数を返す
        return array($method, $paytimes);

    // 支払情報が存在しない場合、falseを返す
    }else{
        return array(false, false);
    }
}





/**
 * 仮売上・即時売上・実売上のキャンセルを実行する際の処理区分を取得
 *（処理区分には処理タイミングにより VOID / RETURN / RETURNX の3種類がある）
 *
 * @param $order_id
 * @param $type
 * @return string
 */
function fn_gmomp_get_cancel_jobcd($order_id, $type)
{
    // 処理区分を初期化
    $jobcd = '';

    // 当該注文に関する請求関連レコードを取得
    $cc_status_data = db_get_row("SELECT * FROM ?:jp_gmomp_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文に関する請求ステータス関連レコードが存在する場合
    if( !empty($cc_status_data) ){

        // 請求ステータスの種類に応じて処理を実施
        switch($cc_status_data['status_code']){

            // 請求ステータスが仮売上の場合
            case 'AUTH_OK':
                // 仮売上をキャンセルする場合
                if($type == 'cc_auth_cancel'){
                    // 仮売上日時のタイムスタンプを取得
                    $auth_timestamp = $cc_status_data['auth_timestamp'];

                    // 現在のタイムスタンプを取得
                    $current_timestamp = time();

                    // 仮売上日時が現在のタイムスタンプよりも古い場合（通常仮売上日時が現在時刻よりも新しいことはない）
                    if($current_timestamp > $auth_timestamp){
                        // YYMMDD形式の仮売上日時を取得
                        $auth_ymd = date("Ymd", $auth_timestamp);
                        // YYMMDD形式の現在日時を取得
                        $current_ymd = date("Ymd", $current_timestamp);

                        // 仮売上と同日のキャンセルの場合
                        if($auth_ymd == $current_ymd){
                            $jobcd = 'VOID';

                        // 仮売上の翌日以降のキャンセルの場合
                        }else{
                            $jobcd = 'RETURN';
                        }
                    }
                }
                break;

            // 請求ステータスが即時売上の場合
            case 'CAPTURE_OK':
                // 即時売上をキャンセルする場合
                if($type == 'cc_sales_cancel'){
                    // 即時売上日時のタイムスタンプを取得
                    $capture_timestamp = $cc_status_data['capture_timestamp'];

                    // 現在のタイムスタンプを取得
                    $current_timestamp = time();

                    // 即時売上日時が現在のタイムスタンプよりも古い場合（通常即時売上日時が現在時刻よりも新しいことはない）
                    if($current_timestamp > $capture_timestamp){
                        // YYMMDD形式で即時売上日時と現在日時を取得
                        $capture_ymd = date("Ymd", $capture_timestamp);
                        $current_ymd = date("Ymd", $current_timestamp);

                        // MM形式で即時売上月と現在月を取得
                        $capture_m = date("m", $capture_timestamp);
                        $current_m = date("m", $current_timestamp);

                        // 即時売上と同日のキャンセルの場合
                        if($capture_ymd == $current_ymd){
                            $jobcd = 'VOID';
                        // 即時売上の翌月以降のキャンセルの場合
                        }elseif( $capture_m != $current_m ){
                            $jobcd = 'RETURNX';
                        // その他の場合
                        }else{
                            $jobcd = 'RETURN';
                        }
                    }
                }
                break;

            // 請求ステータスが「実売上」の場合
            case 'SALES_CONFIRMED':
                // 実売上をキャンセルする場合
                if($type == 'cc_sales_cancel'){
                    // 仮売上日時、実売上日時、現在のタイムスタンプを取得
                    $auth_timestamp = $cc_status_data['auth_timestamp'];
                    $capture_timestamp = $cc_status_data['capture_timestamp'];
                    $current_timestamp = time();

                    // 仮売上日時が現在のタイムスタンプよりも古い場合（通常仮売上日時が現在時刻よりも新しいことはない）
                    if($current_timestamp > $auth_timestamp){
                        // YYMMDD形式で仮売上日時、現在日時を取得
                        $auth_ymd = date("Ymd", $capture_timestamp);
                        $current_ymd = date("Ymd", $current_timestamp);

                        // MM形式で実売上月、現在月を取得
                        $capture_m = date("m", $capture_timestamp);
                        $current_m = date("m", $current_timestamp);

                        // 仮売上と同日のキャンセルの場合
                        if($auth_ymd == $current_ymd){
                            $jobcd = 'VOID';
                        // 実売上の翌月以降のキャンセルの場合
                        }elseif( $capture_m != $current_m ){
                            $jobcd = 'RETURNX';
                        }else{
                            $jobcd = 'RETURN';
                        }
                    }
                }
                break;

            default:
                // do nothing
        }
    }

    return $jobcd;
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_gmomp_check_process_validity( $order_id, $type )
{
    // 注文データからクレジット請求ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_gmomp_cc_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 請求確定
        case 'cc_sales_confirm':
        // 与信取消
        case 'cc_auth_cancel':
            if( $cc_status == 'AUTH_OK' ) return true;
            break;
        // 金額変更
        case 'cc_change':
            if( $cc_status == 'AUTH_OK' || $cc_status == 'SALES_CONFIRMED' || $cc_status == 'CAPTURE_OK' ) return true;
            break;
        // 売上取消
        case 'cc_sales_cancel':
            if( $cc_status == 'SALES_CONFIRMED' || $cc_status == 'CAPTURE_OK' ) return true;
            break;
        // 再オーソリ
        case 'cc_reauth':
            if( $cc_status == 'AUTH_CANCELLED' || $cc_status == 'SALES_CANCELLED' ) return true;
            break;

        // その他
        default:
            // do nothing
    }

    return false;
}
/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// コンビニ決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * コンビニコードに対応したコンビニ名を返す
 *
 * @param $cvs_code
 * @return bool|string
 */
function fn_gmomp_get_cvs_name( $cvs_code = null )
{
    // コンビニコードが指定されていない場合は処理を終了
    if(empty($cvs_code)) return false;

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    // コンビニコードの変更
    ///////////////////////////////////////////////
    // 指定されたコンビニコードに対応したコンビ二名を返す
    switch($cvs_code){
        // ローソン
        //case '00001' :
        case '10001' :
            return __("jp_cvs_ls");
            break;
        // ファミリーマート
        //case '00002' :
        case '10002' :
            return __("jp_cvs_fm");
            break;
        // サンクス
        //case '00003' :
        case '10003' :
            return __("jp_cvs_ts");
            break;
        // サークルK
        //case '00004' :
        case '10004' :
            return __("jp_cvs_ck");
            break;
        // ミニストップ
        //case '00005' :
        case '10005' :
            return __("jp_cvs_ms");
            break;
        // デイリーヤマザキ
        case '00006' :
            return __("jp_cvs_dy");
            break;
        // セブンイレブン
        case '00007' :
            return __("jp_cvs_se");
            break;
        // セイコーマート
        //case '00008' :
        case '10008' :
            return __("jp_cvs_sm");
            break;
        // スリーエフ
        case '00009' :
            return __("jp_gmo_multipayment_cvs_3f");
            break;
        default :
            // do nothing
    }
    //////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    ///////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////
// コンビニ決済 EOF
/////////////////////////////////////////////////////////////////////////////////////



##########################################################################################
// END その他の関数
##########################################################################################
