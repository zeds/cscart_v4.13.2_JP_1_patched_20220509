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

// $Id: func.php by tommy from cs-cart.jp 2016
//
// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_paygent_[フックポイント名]
// (2) (1)以外の関数：fn_pygnt_[任意の名称]
// Modified by takahashi from cs-cart.jp 2017
// PHP7モジュールに対応

// Modified by takahashi from cs-cart.jp 2017
// トークン決済に対応

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * クレジットカード情報を登録済みの会員に対してのみ登録済みカード決済を表示
 * ベース通貨に応じて選択可能な支払方法をフィルタリング
 *
 * @param $params
 * @param $payments
 */
function fn_paygent_get_payments_post(&$params, &$payments)
{
    fn_lcjp_filter_payments($payments, 'paygent_ccreg.tpl', 'paygent_ccreg');

    // トークン決済に対応
    fn_lcjp_filter_payments($payments, 'paygent_ccregtkn.tpl', 'paygent_ccreg');

    // 【メモ】日本円のカード決済と多通貨カード決済で登録済みカード決済に関するペイジェント側の処理は同一。
    // そのため fn_lcjp_filter_payments の第3引数には 'paygent_mcreg' ではなく 'paygent_mcreg' をセットする
    fn_lcjp_filter_payments($payments, 'paygent_mcreg.tpl', 'paygent_ccreg');
    fn_pygnt_filter_payments_by_currency($payments);
}




/**
 * ペイジェントでは注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためペイジェント
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_paygent_finish_payment(&$order_id, &$pp_response, &$force_notification)
{
	// 注文データ内の支払関連情報を取得
	$payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

	// 注文データ内の支払関連情報が存在する場合
	if( !empty($payment_info) ){

        // 注文に利用した決済代行サービスのスクリプトファイル名を取得
        $payment_script_name = fn_pygnt_get_processor_script_name_by_order_id($order_id);
        if( empty($payment_script_name) ) return false;

        // ペイジェント関連の支払方法による注文の場合
        if( strpos($payment_script_name, 'paygent_') !== false ){
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
function fn_paygent_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    $current_controller = Registry::get('runtime.controller');
    $current_mode = Registry::get('runtime.mode');

    // クレジット決済ステータス管理ページまたは多通貨クレジット決済ステータス管理ページの場合
    if( ($current_controller == 'paygent_cc_manager' || $current_controller == 'paygent_mc_manager') && $current_mode == 'manage' ){
        if($current_controller == 'paygent_cc_manager'){
            // カード決済および登録済カードにより支払われた注文のみ抽出
            $processor_scripts = array('paygent_cc.php', 'paygent_ccreg.php', 'paygent_cctkn.php', 'paygent_ccregtkn.php');
        }else{
            // 多通貨カード決済および多通貨登録済カードにより支払われた注文のみ抽出
            $processor_scripts = array('paygent_mc.php', 'paygent_mcreg.php');
        }

        $processor_ids = fn_lcjp_get_processor_ids($processor_scripts);
        $pygnt_payment_ids = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);
        $pygnt_payment_ids = implode(',', $pygnt_payment_ids);
        $condition .= " AND ?:orders.payment_id IN ($pygnt_payment_ids)";

        // 各注文にひもづけられたクレジット請求ステータスコードを抽出
        $fields[] = "?:jp_paygent_cc_status.status_code as cc_status_code";
        $join .= " LEFT JOIN ?:jp_paygent_cc_status ON ?:jp_paygent_cc_status.order_id = ?:orders.order_id";
    }
}




/**
 * 注文情報削除時に当該注文の決済ステータスと決済通知IDを削除
 *
 * @param $order_id
 */
function fn_paygent_delete_order(&$order_id)
{
    db_query("DELETE FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);
    db_query("DELETE FROM ?:jp_paygent_notice WHERE order_id = ?i", $order_id);
}




/**
 * 注文ステータスが「キャンセル」に変更された場合に決済方法に応じた処理を行う
 *
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 * @return bool
 */
function fn_paygent_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses, &$place_order)
{
    if( empty($order_info['payment_id']) ) return false;

    // 注文キャンセルの場合
    if( $status_to == 'I' ){

        // 注文で利用した決済代行サービスのスクリプト名を取得
        $processor_script = fn_pygnt_get_processor_script_name_by_order_id($order_info['order_id']);

        // 注文で決済代行サービスを利用している場合
        if( !empty($processor_script) ){
            // 利用した決済代行サービスに応じて処理を実施
            switch($processor_script){
                case 'paygent_cc.php':          // カード決済
                case 'paygent_ccreg.php':       // 登録済カード決済
                case 'paygent_cctkn.php':       // カード決済・トークン決済
                case 'paygent_ccregtkn.php':    // 登録済カード決済・トークン決済
                    // 決済ステータスを取得
                    $status_code = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_info['order_id']);

                    // 決済ステータスが「オーソリOK」の場合
                    if( $status_code == 'AUTH_OK' ){
                        // オーソリキャンセル処理を実施
                        fn_pygnt_send_cc_action('cc_auth_cancel', $order_info['order_id']);
                    // 決済ステータスが「消込済」の場合
                    }elseif( $status_code == 'SALES_CONFIRMED' ){
                        // 売上キャンセル処理を実施
                        fn_pygnt_send_cc_action('cc_sales_cancel', $order_info['order_id']);
                    }
                    break;

                case 'paygent_mc.php':          // 多通貨カード決済
                case 'paygent_mcreg.php':       // 多通貨登録済カード決済
                    // 決済ステータスを取得
                    $status_code = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_info['order_id']);

                    // 決済ステータスが「オーソリOK」の場合
                    if( $status_code == 'AUTH_OK' ){
                        // オーソリキャンセル処理を実施
                        fn_pygnt_send_cc_action('mc_auth_cancel', $order_info['order_id']);
                        // 決済ステータスが「消込済」の場合
                    }elseif( $status_code == 'SALES_CONFIRMED' ){
                        // 売上キャンセル処理を実施
                        fn_pygnt_send_cc_action('mc_sales_cancel', $order_info['order_id']);
                    }
                    break;

                case 'paygent_cvs.php':             // コンビニ決済（番号方式）
                case 'paygent_atm.php':             // ATM決済
                case 'paygent_netbank.php':         // 銀行ネット決済
                case 'paygent_netbank_asp.php':     // 銀行ネットASP決済
                case 'paygent_bank_va.php':         // 仮想口座決済
                    // これらの決済方法についてはCS-Cart側でペイジェント側のステータスを変更することはできない
                    // ペイジェント管理画面にてキャンセル処理が実行できることを確認できたらメッセージを表示する
                    // fn_set_notification('E', __("warning"), __("jp_paygent_cancel_not_synced"));
                    break;

                default:
                    // do nothing
            }
        }
    }
}

/**
 * 言語変数とトークン決済用のprocessor_idを追加してメッセージを出す
 *
 * @param $user_data
 */
function fn_paygent_set_admin_notification(&$user_data)
{
    // トークン決済用のprocessor_idが存在するか確認する
    $tokenId =  db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paygent_cctkn.php', 'paygent_ccregtkn.php')");

    // トークン決済用のprocessor_idが存在しない場合
    if(empty($tokenId)){
        try {
            // インストール済みの言語を取得
            $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

            // 言語変数の追加
            $lang_variables = array(
                array('name' => 'jp_paygent_tokey_key', 'value' => 'トークン生成鍵'),
                array('name' => 'jp_paygent_connections_settings', 'value' => '接続設定'),
                array('name' => 'jp_paygent_token_enabled', 'value' => 'ペイジェント決済 において<br />トークンを利用したクレジットカード決済がご利用いただけるようになりました。'),
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
            db_query("INSERT INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9275, 'ペイジェント（クレジットカード決済・トークン決済）', 'paygent_cctkn.php', 'addons/paygent/views/orders/components/payments/paygent_cctkn.tpl', 'paygent_cctkn.tpl', 'N', 'P')");

            db_query("INSERT INTO cscart_payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9276, 'ペイジェント（登録済カード決済・トークン決済）', 'paygent_ccregtkn.php', 'addons/paygent/views/orders/components/payments/paygent_ccregtkn.tpl', 'paygent_ccregtkn.tpl', 'N', 'P');");

            // トークン決済利用可能のメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_paygent_token_enabled'));
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
function fn_pygnt_install()
{
    fn_lcjp_install('paygent');
}




/**
 * アドオンのアンインストール時に支払関連のレコードを削除
 */
function fn_pygnt_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paygent_cc.php', 'paygent_ccreg.php', 'paygent_mc.php', 'paygent_mcreg.php', 'paygent_cvs.php', 'paygent_cctkn.php', 'paygent_ccregtkn.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paygent_cc.php', 'paygent_ccreg.php', 'paygent_mc.php', 'paygent_mcreg.php', 'paygent_cvs.php', 'paygent_cctkn.php', 'paygent_ccregtkn.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('paygent_cc.php', 'paygent_ccreg.php', 'paygent_mc.php', 'paygent_mcreg.php', 'paygent_cvs.php', 'paygent_cctkn.php', 'paygent_ccregtkn.php')");
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################





##########################################################################################
// START アドオンの設定ページで動作する関数
##########################################################################################

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
 * ペイジェント接続モジュールのインスタンス作成および初期化
 *
 * @param $p
 * @return bool
 */
function fn_pygnt_initialize_module(&$p)
{
    ////////////////////////////////////////////////////////////////////
    // Modified for Japanese version by takahashi from cs-cart.jp 2017 BOF
    // PHP7モジュールに対応
    ////////////////////////////////////////////////////////////////////
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        include_once 'lib/connect_module/paygent_php5_init.php';
        $objPhpInit = new paygent_php5_init();
    }elseif (version_compare(PHP_VERSION, '7.0.0', '>=')) {
        include_once 'lib/connect_module/paygent_php7_init.php';
        $objPhpInit = new paygent_php7_init();
    }

    $p = $objPhpInit->getModule();
    ////////////////////////////////////////////////////////////////////
    // Modified for Japanese version by takahashi from cs-cart.jp 2017 EOF
    ////////////////////////////////////////////////////////////////////

    $p->init();

    return true;
}




/**
 * ペイジェントにデータを送信
 *
 * @param $p
 * @param $paygent_params
 * @return mixed
 */
function fn_pygnt_send_params(&$p, $paygent_params)
{
    // 接続モジュールのインスタンスにパラメータをセット
    foreach($paygent_params as $key => $val){
        $p->reqPut($key, mb_convert_encoding($val, 'Shift-JIS', 'UTF-8'));
    }

    // ペイジェントにデータを送信
    return $p->post();
}




/**
 * ペイジェントに送信するパラメータをセット
 *
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_pygnt_get_params($type, $order_id = '', $order_info = array(), $processor_data = array(), $extras = array())
{
    // 送信パラメータを初期化
    $params = array();

    // マーチャントID
    $params['merchant_id'] = Registry::get('addons.paygent.merchant_id');

    // 接続ID
    $params['connect_id'] = Registry::get('addons.paygent.connect_id');

    // 接続パスワード
    $params['connect_password'] = Registry::get('addons.paygent.connect_password');

    // 電文種別ID
    $params['telegram_kind'] = fn_pygnt_get_telegram_kind($type);

    // 電文バージョン番号
    $params['telegram_version'] = Registry::get('addons.paygent.telegram_version');

    // マーチャント取引ID
    if( $type != 'diff') $params['trading_id'] = Registry::get('addons.paygent.trading_id_prefix') . $order_id;

    // 支払方法別の送信パラメータをセット
    fn_pygnt_get_specific_params($params, $type, $order_id, $order_info, $processor_data, $extras);

    // ペイジェントに送信するパラメータを返す
    return $params;
}




/**
 * 電文種別IDを取得
 *
 * @param $type
 * @return string
 */
function fn_pygnt_get_telegram_kind($type)
{
    // 電文種別IDを初期化
    $telegram_kind = '';

    switch($type){
        // クレジットカード決済または登録済みカード決済
        case 'cc':
        case 'ccreg':
        case 'cctkn':
            $telegram_kind = '020';
            break;

        // カード決済オーソリキャンセル
        case 'cc_auth_cancel':
            $telegram_kind = '021';
            break;

        // カード決済売上
        case 'cc_sales_confirm':
            $telegram_kind = '022';
            break;

        // カード決済売上キャンセル
        case 'cc_sales_cancel':
            $telegram_kind = '023';
            break;

        // カード決済補正オーソリ
        case 'cc_change_auth':
            $telegram_kind = '028';
            break;

        // カード決済補正売上
        case 'cc_change_sales':
            $telegram_kind = '029';
            break;

        // カード情報設定
        case 'cc_register':
            $telegram_kind = '025';
            break;

        // カード情報照会
        case 'cc_retrieve':
            $telegram_kind = '027';
            break;

        // カード情報削除
        case 'cc_delete':
            $telegram_kind = '026';
            break;

        // カード決済（多通貨）または登録済みカード決済（多通貨）
        case 'mc':
        case 'mcreg':
            $telegram_kind = '180';
            break;

        // カード決済（多通貨）オーソリキャンセル
        case 'mc_auth_cancel':
            $telegram_kind = '181';
            break;

        // カード決済（多通貨）売上
        case 'mc_sales_confirm':
            $telegram_kind = '182';
            break;

        // カード決済（多通貨）売上キャンセル
        case 'mc_sales_cancel':
            $telegram_kind = '183';
            break;

        // カード決済（多通貨）補正オーソリ
        case 'mc_change_auth':
            $telegram_kind = '184';
            break;

        // カード決済（多通貨）補正売上
        case 'mc_change_sales':
            $telegram_kind = '185';
            break;

        // コンビニ決済
        case 'cvs':
            $telegram_kind = '030';
            break;

        // 決済情報差分照会
        case 'diff':
            $telegram_kind = '091';
            break;

        default:
            // do nothing
    }

    return $telegram_kind;
}




/**
 * 支払方法別の送信パラメータをセット
 *
 * @param $params
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_pygnt_get_specific_params(&$params, $type, $order_id = '', $order_info = array(), $processor_data = array(), $extras = array())
{
    // 支払方法別に異なるパラメータをセット
    switch ($type) {

        // クレジットカード決済
        case 'cc':

            // 決済金額
            $params['payment_amount'] = fn_format_price($order_info['total']);

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            if(!empty($processor_data['processor_params']['token_key'])){
                $params['card_token'] = $order_info['payment_info']['token'];

                // 3Dセキュアを使用する場合
                if( $processor_data['processor_params']['3dsecure'] == 'true' ){
                    // 3Dセキュア戻りURL
                    $params['term_url'] = fn_url("payment_notification.process&payment=paygent_cctkn&order_id=$order_id&process_type=3dsecure_return", AREA, 'current');
                }
            }
            else {
                // カード番号
                $params['card_number'] = $order_info['payment_info']['card_number'];

                // カード有効期限
                $params['card_valid_term'] = $order_info['payment_info']['expiry_month'] . $order_info['payment_info']['expiry_year'];

                // セキュリティコードによる認証を行う場合
                if ($processor_data['processor_params']['use_cvv'] == 'true' && !empty($order_info['payment_info']['cvv2'])) {
                    // セキュリティコード
                    $params['card_conf_number'] = $order_info['payment_info']['cvv2'];
                }

                // 3Dセキュアを使用する場合
                if( $processor_data['processor_params']['3dsecure'] == 'true' ){
                    // 3Dセキュア戻りURL
                    $params['term_url'] = fn_url("payment_notification.process&payment=paygent_cc&order_id=$order_id&process_type=3dsecure_return", AREA, 'current');
                }
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            // 支払区分
            $params['payment_class'] = $order_info['payment_info']['jp_cc_method'];

            // 分割回数
            if( $order_info['payment_info']['jp_cc_method'] == 61 ){
                $params['split_count'] = $order_info['payment_info']['jp_cc_installment_times'];
            }

            // 3Dセキュアを使用する場合
            if( $processor_data['processor_params']['3dsecure'] == 'true' ){

                // 3Dセキュア利用タイプ
                $params['3dsecure_use_type'] = 1;

                // HttpAccept
                $params['http_accept'] = $_SERVER['HTTP_ACCEPT'];

                // HttpUserAgent
                $params['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            }else{
                // 3Dセキュア不要区分に1をセット
                $params['3dsecure_ryaku'] = 1;
            }

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !isset($site_id) ){
                $params['site_id'] = $site_id;
            }

            break;

        // 登録済カード決済
        case 'ccreg':

            // 決済金額
            $params['payment_amount'] = fn_format_price($order_info['total']);

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            // セキュリティコードによる認証を行う場合
            if ($processor_data['processor_params']['use_cvv'] == 'true') {
                if(!empty($processor_data['processor_params']['token_key']) && $extras != true){
                    $params['security_code_token'] = '1';
                    $params['card_token'] = $order_info['payment_info']['token'];
                }
                else if(!empty($order_info['payment_info']['cvv2'])) {
                    // セキュリティコード
                    $params['card_conf_number'] = $order_info['payment_info']['cvv2'];
                }
            }

            if(!empty($processor_data['processor_params']['token_key'])){
                // 3Dセキュアを使用する場合
                if( $processor_data['processor_params']['3dsecure'] == 'true' ){
                    // 3Dセキュア戻りURL
                    $params['term_url'] = fn_url("payment_notification.process&payment=paygent_ccregtkn&order_id=$order_id&process_type=3dsecure_return", AREA, 'current');
                }
            }
            else {
                // 3Dセキュアを使用する場合
                if( $processor_data['processor_params']['3dsecure'] == 'true' ){
                    // 3Dセキュア戻りURL
                    $params['term_url'] = fn_url("payment_notification.process&payment=paygent_ccreg&order_id=$order_id&process_type=3dsecure_return", AREA, 'current');
                }
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            // 支払区分
            $params['payment_class'] = $order_info['payment_info']['jp_cc_method'];

            // 分割回数
            if( $order_info['payment_info']['jp_cc_method'] == 61 ){
                $params['split_count'] = $order_info['payment_info']['jp_cc_installment_times'];
            }

            // 3Dセキュアを使用する場合
            if( $processor_data['processor_params']['3dsecure'] == 'true' ){

                // 3Dセキュア利用タイプ
                $params['3dsecure_use_type'] = 1;

                // HttpAccept
                $params['http_accept'] = $_SERVER['HTTP_ACCEPT'];

                // HttpUserAgent
                $params['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            }else{
                // 3Dセキュア不要区分に1をセット
                $params['3dsecure_ryaku'] = 1;
            }

            // カード情報お預りモード
            $params['stock_card_mode'] = 1;

            // 顧客ID
            $params['customer_id'] = $order_info['user_id'];

            // 顧客カードID
            $params['customer_card_id'] = fn_pygnt_get_customer_card_id($order_info['user_id']);

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !isset($site_id) ){
                $params['site_id'] = $site_id;
            }

            break;

        // カード決済のオーソリキャンセル / 売上確定 / 売上キャンセル
        case 'cc_auth_cancel':
        case 'cc_sales_confirm':
        case 'cc_sales_cancel':
        case 'mc_auth_cancel':
        case 'mc_sales_confirm':
        case 'mc_sales_cancel':

            if( !empty($extras['payment_id']) ){
                // 決済ID
                $params['payment_id'] = $extras['payment_id'];
            }

            break;

        // カード決済補正オーソリ / 補正売上
        case 'cc_change_auth':
        case 'cc_change_sales':
        case 'mc_change_auth':
        case 'mc_change_sales':

            // 決済金額
            $params['payment_amount'] = fn_format_price($order_info['total']);

            if( !empty($extras['payment_id']) ){
                // 決済ID
                $params['payment_id'] = $extras['payment_id'];
            }

            // 減額フラグ( 0 = 補正後の取引金額 固定)
            $params['reduction_flag'] = 0;

            break;

        // カード情報設定
        case 'cc_register':

            // 顧客ID
            $params['customer_id'] = $order_info['user_id'];

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            if(!empty($processor_data['processor_params']['token_key'])){
                $params['card_token'] = $order_info['payment_info']['token'];
            }
            else {
                // カード番号
                $params['card_number'] = $order_info['payment_info']['card_number'];

                // カード有効期限
                $params['card_valid_term'] = $order_info['payment_info']['expiry_month'] . $order_info['payment_info']['expiry_year'];
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !isset($site_id) ){
                $params['site_id'] = $site_id;
            }

            // 有効性チェックフラグ
            $params['valid_check_flg'] = 1;

            break;

        // カード情報照会 / カード情報削除
        case 'cc_retrieve':
        case 'cc_delete':

            // 顧客ID
            $params['customer_id'] = $extras['user_id'];

            // 顧客カードID
            $params['customer_card_id'] = fn_pygnt_get_customer_card_id($extras['user_id']);

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !isset($site_id) ){
                $params['site_id'] = $site_id;
            }

            break;

        // クレジットカード決済（多通貨）
        case 'mc':

            // 決済金額
            $params['payment_amount'] = fn_format_price($order_info['total']);

            // 通貨コード
            $primary_currency_code = db_get_field("SELECT currency_code FROM ?:currencies WHERE is_primary = ?s", "Y");
            $params['currency_code'] = strtoupper($primary_currency_code);

            // カード番号
            $params['card_number'] = $order_info['payment_info']['card_number'];

            // カード有効期限
            $params['card_valid_term'] = $order_info['payment_info']['expiry_month'] . $order_info['payment_info']['expiry_year'];

            // セキュリティコードによる認証を行う場合
            if ($processor_data['processor_params']['use_cvv'] == 'true' && !empty($order_info['payment_info']['cvv2'])) {
                // セキュリティコード
                $params['card_conf_number'] = $order_info['payment_info']['cvv2'];
            }

            // 支払区分（「10」: 1回払い固定）
            $params['payment_class'] = 10;

            // 3Dセキュアを使用する場合
            if( $processor_data['processor_params']['3dsecure'] == 'true' ){

                // HttpAccept
                $params['http_accept'] = $_SERVER['HTTP_ACCEPT'];

                // HttpUserAgent
                $params['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                // 3Dセキュア戻りURL
                $params['term_url'] = fn_url("payment_notification.process&payment=paygent_mc&order_id=$order_id&process_type=3dsecure_return", AREA, 'current');

            }else{
                // 3Dセキュア不要区分に1をセット
                $params['3dsecure_ryaku'] = 1;
            }

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !isset($site_id) ){
                $params['site_id'] = $site_id;
            }

            break;

        // 登録済カード決済（多通貨）
        case 'mcreg':

            // 決済金額
            $params['payment_amount'] = fn_format_price($order_info['total']);

            // 通貨コード
            $primary_currency_code = db_get_field("SELECT currency_code FROM ?:currencies WHERE is_primary = ?s", "Y");
            $params['currency_code'] = strtoupper($primary_currency_code);

            // セキュリティコードによる認証を行う場合
            if ($processor_data['processor_params']['use_cvv'] == 'true' && !empty($order_info['payment_info']['cvv2'])) {
                // セキュリティコード
                $params['card_conf_number'] = $order_info['payment_info']['cvv2'];
            }

            // 支払区分（「10」: 1回払い固定）
            $params['payment_class'] = 10;

            // 3Dセキュアを使用する場合
            if( $processor_data['processor_params']['3dsecure'] == 'true' ){

                // HttpAccept
                $params['http_accept'] = $_SERVER['HTTP_ACCEPT'];

                // HttpUserAgent
                $params['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                // 3Dセキュア戻りURL
                $params['term_url'] = fn_url("payment_notification.process&payment=paygent_mcreg&order_id=$order_id&process_type=3dsecure_return", AREA, 'current');

            }else{
                // 3Dセキュア不要区分に1をセット
                $params['3dsecure_ryaku'] = 1;
            }

            // カード情報お預りモード
            $params['stock_card_mode'] = 1;

            // 顧客ID
            $params['customer_id'] = $order_info['user_id'];

            // 顧客カードID
            $params['customer_card_id'] = fn_pygnt_get_customer_card_id($order_info['user_id']);

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !isset($site_id) ){
                $params['site_id'] = $site_id;
            }

            break;


        // コンビニ決済
        case 'cvs':

            // 決済金額
            $params['payment_amount'] = fn_format_price($order_info['total']);

            // 利用者姓
            if( !empty($order_info['b_firstname']) ){
                $params['customer_family_name'] = mb_substr(mb_convert_kana($order_info['b_firstname'], 'RNAK', 'UTF-8'), 0, 10, 'UTF-8');
            }

            // 利用者名
            if( !empty($order_info['b_lastname']) ){
                $params['customer_name'] = mb_substr(mb_convert_kana($order_info['b_lastname'], 'RNAK', 'UTF-8'), 0, 10, 'UTF-8');
            }

            // 利用者電話番号
            if( !empty($order_info['b_phone']) ){
                $customer_tel = substr(preg_replace("/[^0-9]+/", "", mb_convert_kana($order_info['b_phone'],"a")), 0, 11);
            }elseif( !empty($order_info['phone']) ){
                $customer_tel = substr(preg_replace("/[^0-9]+/", "", mb_convert_kana($order_info['phone'],"a")), 0, 11);
            }elseif( !empty($order_info['s_phone']) ){
                $customer_tel = substr(preg_replace("/[^0-9]+/", "", mb_convert_kana($order_info['s_phone'],"a")), 0, 11);
            }
            $params['customer_tel'] = $customer_tel;

            // 支払期限日
            if( isset($processor_data['processor_params']['payment_limit_date']) ){
                $params['payment_limit_date'] = (int)$processor_data[processor_params]['payment_limit_date'];
            }else{
                $params['payment_limit_date'] = 30;
            }

            // 申込コンビニ企業CD
            $params['cvs_company_id'] = $order_info['payment_info']['jp_paygent_cvs_name'];

            // CVSタイプ
            $params['cvs_type'] = fn_pygnt_get_cvs_type($params['cvs_company_id']);

            // 支払種別
            if( $order_info['payment_info']['jp_paygent_cvs_name'] == '00C001' ) {
                if (!empty($order_info['payment_info']['jp_paygent_cvs_payment_timing'])) {
                    $params['sales_type'] = $order_info['payment_info']['jp_paygent_cvs_payment_timing'];
                } else {
                    $params['sales_type'] = 1;
                }
            }

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !empty($site_id) ){
                $params['site_id'] = $site_id;
            }

            break;

        // 決済情報差分照会
        case 'diff':

            // 決済通知ID
            if( !empty($extras['payment_notice_id']) ){
                $params['payment_notice_id'] = $extras['payment_notice_id'];
            }

            // サイトID
            $site_id = Registry::get('addons.paygent.site_id');
            if( !empty($site_id) ){
                $params['site_id'] = $site_id;
            }

            break;

        default:
            // do nothing
    }
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $res_array
 * @param bool $flg_comments
 * @return bool
 */
function fn_pygnt_format_payment_info($type, $order_id, $payment_info = '', $res_array)
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

        // 支払情報がすでに存在する場合
        if( !empty($info) ){
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 BOF
            ////////////////////////////////////////////////////////////////////
            foreach($info as $key => $val){
                switch($type){
                    // クレジットカード決済
                    case 'cc' :
                    case 'ccreg' :
                    case 'mc' :
                    case 'mcreg' :
                    case 'cc_change_amount':
                    case 'mc_change_amount':

                        $flg_cc_installment = false;

                        switch($key){
                            // 支払方法はコードに対応した支払方法に変換
                            case "jp_cc_method":

                                switch($val){
                                    case 10:
                                        $info['jp_cc_method'] = __("jp_cc_onetime");
                                        break;
                                    case 23:
                                        $info['jp_cc_method'] = __("jp_cc_bonus_onetime");
                                        break;
                                    case 61:
                                        $info['jp_cc_method'] = __("jp_cc_installment");
                                        $flg_cc_installment = true;
                                        break;
                                    case 80:
                                        $info['jp_cc_method'] = __("jp_cc_revo");
                                        break;
                                    default:
                                        // do nothing
                                }
                                break;

                            // 支払回数
                            case "jp_cc_installment_times":
                                // 支払回数には末尾に「回」を追記
                                if( empty(mb_strpos($info['jp_cc_installment_times'], __("jp_paytimes_unit"), 0, 'UTF-8')) ){
                                    $info['jp_cc_installment_times'] = $info[$key] . __("jp_paytimes_unit");
                                }

                                // 分割払い以外の場合は支払回数を削除
                                if( $info['jp_cc_method'] != 61 && $info['jp_cc_method'] != __("jp_cc_installment")){
                                    unset($info['jp_cc_installment_times']);
                                }
                                break;

                            // クレジットカード決済に関する情報のみ保持
                            case 'jp_paygent_payment_id':
                            case 'jp_paygent_acq_name':
                            case 'jp_paygent_issur_name':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // コンビニ決済
                    case 'cvs':

                        switch($key){
                            // 支払いタイミング用コードを文字列に変換
                            case 'jp_paygent_cvs_payment_timing':
                                switch($val){
                                    case 3:
                                        $info['jp_paygent_cvs_payment_timing'] = __("jp_paygent_cvs_postpaid");
                                        break;
                                    default:
                                        $info['jp_paygent_cvs_payment_timing'] = __("jp_paygent_cvs_prepaid");
                                }
                                break;

                            // コンビ二名
                            case 'jp_paygent_cvs_name':
                                $info['jp_paygent_cvs_name'] = fn_pygnt_cvs_get_cvs_name($payment_info['jp_paygent_cvs_name']);
                                break;

                            // コンビニ決済に関する情報のみ保持
                            case 'jp_paygent_payment_id':
                            case 'jp_paygent_cvs_receipt_number1':
                            case 'jp_paygent_cvs_payment_due':
                                // do nothing
                                break;

                            // 一時的に保存された他の情報はすべて削除
                            default:
                                unset($info[$key]);
                        }

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
            // クレジットカード決済
            case 'cc' :
            case 'ccreg' :
            case 'mc' :
            case 'mcreg' :

                // 決済ID
                if( !empty($res_array['payment_id']) ){
                    $info['jp_paygent_payment_id'] = $res_array['payment_id'];
                }

                // 取扱カード会社名
                if( !empty($res_array['acq_name']) ) {
                    $info['jp_paygent_acq_name'] = $res_array['acq_name'];
                }

                // カード発行会社名
                if( !empty($res_array['issur_name']) ) {
                    $info['jp_paygent_issur_name'] = $res_array['issur_name'];
                }

                break;

            // クレジットカード決済（金額変更）
            // 【メモ】
            // 半角カナで戻されるカード発行会社名を保存しようとすると文字化けするため保存しない
            // （mb_detect_encoding と mb_convert_encoding を使用しても文字化け）
            // 決済金額を変更する際にはカード発行会社名は変更されないため、保存しなくても問題なし
            case 'cc_change_amount':
            case 'mc_change_amount':

                // 決済ID
                if( !empty($res_array['payment_id']) ){
                    $info['jp_paygent_payment_id'] = $res_array['payment_id'];
                }

                break;

            // コンビニ決済
            case 'cvs':

                // 決済ID
                if( !empty($res_array['payment_id']) ){
                    $info['jp_paygent_payment_id'] = $res_array['payment_id'];
                }

                // 決済ベンダ受付番号
                if( !empty($res_array['receipt_number']) ){
                    $info['jp_paygent_cvs_receipt_number1'] = $res_array['receipt_number'];
                }

                // 支払期限日
                if( !empty($res_array['payment_limit_date']) ){
                    $info['jp_paygent_cvs_payment_due'] = fn_pygnt_format_payment_limit_date($res_array['payment_limit_date']);
                }

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
 * ペイジェント側の処理でエラーが発生した場合の処理
 *
 * @param $responseCode
 * @param string $responseDetail
 */
function fn_pygnt_handle_process_error($responseCode, $responseDetail = '')
{
    // エラー詳細メッセージがセットされている場合
    if( !empty($responseDetail) ){

        // エラーメッセージを作成
        $err_msg = __("jp_paygent_general_error") . '<br />' . __("errorCode") . ' : ' . $responseCode . ' - ' . $responseDetail;

        // レスポンスコードが 'P010' の場合
        if($responseCode == 'P010'){
            // カード有効期限に関するエラーの場合
            if( strpos($responseDetail, 'card_valid_term') )
            {
                // エラーメッセージを作成
                $err_msg = __("jp_paygent_error_details") . ' : ' . __("jp_paygent_valid_term_error") ;
            }
        }

    // エラー詳細メッセージがセットされていない場合
    }else{
        // エラーメッセージを作成
        $err_msg = __("jp_paygent_general_error") . '<br />' . __("errorCode") . ' : ' . $responseCode;
    }

    // エラーの詳細を表示して注文手続きページにリダイレクト
    fn_set_notification('E', __("error"), $err_msg);
    fn_redirect(fn_lcjp_get_error_return_url(), true);
}




/**
 * 支払期限日の表示形式をフォーマット
 *
 * @param $payment_limit_date
 * @return string
 */
function fn_pygnt_format_payment_limit_date($payment_limit_date)
{
    // 正しい長さの日付データが渡された場合
    if( strlen($payment_limit_date) == 8 ){
        $year = substr($payment_limit_date, 0, 4);
        $month = substr($payment_limit_date, 4, 2);
        $date = substr($payment_limit_date, 6, 2);

        // 「YYYY/MM/DD HH:MM」形式の値を返す
        return $year . '/' . $month . '/' . $date;

        // 正しい長さの日付データが渡されなかった場合
    }else{
        // 引数をそのまま返す
        return $payment_limit_date;
    }
}




/** クロンジョブで決済情報差分照会を実施するためのコマンドを表示
 * @return string
 */
function fn_paygent_get_cron_command()
{
    /////////////////////////////////////////////
    // cronジョブでアクセスするURLを生成 BOF
    /////////////////////////////////////////////
    // 管理画面がSSLで保護されている場合
    if( Registry::get('settings.Security.secure_admin') == "Y" ){
        $cron_url = Registry::get('config.https_location') . '/';
    // 管理画面がSSLで保護されていない場合
    }else{
        $cron_url = Registry::get('config.http_location') . '/';
    }

    $cron_url .= Registry::get('config.admin_index') . "?dispatch=paygent_cc_manager.cron_check_diff&cron_password=" .  Registry::get('addons.paygent.cron_password');
    /////////////////////////////////////////////
    // cronジョブでアクセスするURLを生成 EOF
    /////////////////////////////////////////////

    // cronジョブで実行するコマンドを生成
    $cron_command = 'wget --delete-after ' . $cron_url;

    return $cron_command;
}




/**
 * 指定した決済方法を利用する支払方法が登録されているかを判定する
 * 【メモ】
 * 　支払方法のステータスが「無効」であっても登録されていれば true を返す
 * 　（一時的に支払方法を無効化する場合もあるので）
 *
 * @param $type
 * @return bool
 */
function fn_pygnt_is_payment_registered($type)
{
    // 指定した決済方法に対応するスクリプトファイル名をセット
    switch($type){
        //  ペイジェント（カード払いまたは登録済みカード払い）
        case 'cc':
            $processor_scripts = array('paygent_cc.php', 'paygent_ccreg.php', 'paygent_cctkn.php', 'paygent_ccregtkn.php');
            break;

        //  ペイジェント（多通貨カード払いまたは多通貨登録済みカード払い）
        case 'mc':
            $processor_scripts = array('paygent_mc.php', 'paygent_mcreg.php');
            break;

        // その他
        default:
            return false;
    }

    // 指定した決済方法を利用する支払方法を抽出
    $processor_ids = fn_lcjp_get_processor_ids($processor_scripts);
    $pygnt_payments = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);

    // 指定した決済方法を利用する支払方法が存在する場合、trueを返す
    if( !empty($pygnt_payments) ){
        return true;
    }else{
        return false;
    }
}




/**
 * ベース通貨が日本円であるかを判定する
 *
 * @return bool
 */
function fn_pygnt_is_jpy_primary()
{
    $primary_currency = db_get_field("SELECT currency_code FROM ?:currencies WHERE is_primary = ?s", 'Y');

    if( strtoupper($primary_currency) == 'JPY' ){
        return true;
    }else{
        return false;
    }
}




/**
 * ベース通貨に応じて選択可能な支払方法をフィルタリング
 *
 * @param $payments
 */
function fn_pygnt_filter_payments_by_currency(&$payments)
{
    // ショップフロント、または管理画面における注文内容の編集の場合
    if( (AREA == 'C') || (AREA == 'A' && Registry::get('runtime.controller') == 'order_management' && Registry::get('runtime.mode') == 'update') ){

        // 選択可能な支払方法が存在する場合
        if( !empty($payments) ){
            foreach($payments as $key => $val) {
                // 決済代行サービスIDを取得
                $processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $val['payment_id']);

                // 各決済代行サービスにひもづけられたスクリプトファイル名
                $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

                // ベース通貨が日本円の場合
                if( fn_pygnt_is_jpy_primary() ){
                    switch( $processor_script ){
                        // 多通貨カード決済を無効化
                        case 'paygent_mc.php':
                        case 'paygent_mcreg.php':
                            unset($payments[$key]);
                            break;

                        default:
                            // do nothing
                    }

                // ベース通貨が日本円以外の場合
                }else{
                    // 多通貨カード決済以外のペイジェント決済を無効化
                    if( strpos($processor_script, 'paygent_') !== false ){
                        switch( $processor_script ){
                            case 'paygent_mc.php':
                            case 'paygent_mcreg.php':
                                //  do nothing
                                break;

                            default:
                                unset($payments[$key]);
                        }
                    }
                }
            }
        }
    }
}
/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// カード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////
/**
 * 顧客カードIDを取得
 *
 * @param $user_id
 * @return array
 */
function fn_pygnt_get_customer_card_id($user_id)
{
    $customer_card_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'paygent_ccreg');
    if( empty($customer_card_id) ) $customer_card_id = false;
    return $customer_card_id;
}




/**
 * 登録済カードの情報を取得
 *
 * @param $user_id
 * @return array
 */
function fn_pygnt_get_registered_card_info($user_id)
{
    // 会員未登録またはカード情報を登録していないユーザーは登録済カードに関する処理は行わない
    if( empty($user_id) || !fn_pygnt_get_customer_card_id($user_id) ) return false;

    $registered_card = array();
    $extras = array();
    $extras['user_id'] = $user_id;

    // ペイジェント接続モジュールのインスタンス作成および初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 BOF
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントに送信するパラメータをセット
    $params = fn_pygnt_get_params('cc_retrieve', '', array(), array(), $extras);

    // ペイジェントにデータを送信
    $result = fn_pygnt_send_params($p, $params);
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 EOF
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 BOF
    ////////////////////////////////////////////////////////////////////////////
    // データ送信においてエラーが発生した場合
    if( !$result === true ){
        // 通信エラー処理
        fn_pygnt_handle_process_error($result);

    // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ){
            // レスポンスコードを取得
            $responseCode = $p->getResponseCode();

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

            // カード情報の登録完了メッセージを表示
            fn_set_notification('E', __("jp_paygent_ccreg_retrieve_error"), __("jp_paygent_ccreg_retrieve_failed") . '<br />' . $responseDetail);

        // カード情報照会が完了した場合
        }else{
            // ペイジェント側に登録されているカード情報が存在する場合
            if( $p->hasResNext() ){
                $res_array = $p->resNext();
                $registered_card['card_number'] = $res_array['card_number'];
                if( !empty($res_array['card_valid_term']) ){
                    $registered_card['card_valid_term'] = substr($res_array['card_valid_term'], 0, 2) . '/'. substr($res_array['card_valid_term'], 2, 2);
                }
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 EOF
    ////////////////////////////////////////////////////////////////////////////

    return $registered_card;
}




/**
 * 登録済カードの情報を削除
 *
 * @param $user_id
 */
function fn_pygnt_delete_card_info($user_id, $notification = true)
{
    $registered_card = array();
    $extras = array();
    $extras['user_id'] = $user_id;

    // ペイジェント接続モジュールのインスタンス作成および初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 BOF
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントに送信するパラメータをセット
    $params = fn_pygnt_get_params('cc_delete', '', array(), array(), $extras);

    // ペイジェントにデータを送信
    $result = fn_pygnt_send_params($p, $params);
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 EOF
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 BOF
    ////////////////////////////////////////////////////////////////////////////
    // データ送信においてエラーが発生した場合
    if( !$result === true ){
        // 通信エラー処理
        fn_pygnt_handle_process_error($result);

    // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ){
            // レスポンスコードを取得
            $responseCode = $p->getResponseCode();

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

            // カード情報の削除エラーメッセージを表示
            fn_set_notification('E', __("jp_paygent_ccreg_delete_error"), __("jp_paygent_ccreg_delete_failed") . '<br />' . $responseDetail);

        // カード情報照会が完了した場合
        }else{
            // カード情報を管理するDBから当該レコードを削除
            db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'paygent_ccreg');

            // カード情報の削除完了メッセージを表示する場合
            if($notification){
                // カード情報の削除完了メッセージを表示
                fn_set_notification('N', __("notice"), __("jp_paygent_ccreg_delete_success"));
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 EOF
    ////////////////////////////////////////////////////////////////////////////
}




/**
 * クレジットカード情報を登録
 *
 * @param $p
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_pygnt_register_card($p, $type, $order_id, $order_info, $processor_data)
{

    // クレジットカード情報お預かり機能を利用する場合（金額変更時は除く）
    if( $order_info['payment_info']['register_card_info'] == 'true' && !empty($order_info['user_id']) && $type != 'cc_change' ){

        // ペイジェントに送信するカード情報設定用パラメータをセット
        $params = fn_pygnt_get_params('cc_register', $order_id, $order_info, $processor_data);

        // ペイジェントに要求を送信
        $result = fn_pygnt_send_params($p, $params);

        // データ送信においてエラーが発生した場合
        if( !($result === true) ){
            // エラー処理を記述
            return false;
        // データ送信が正常に終了した場合
        }else{

            // 処理結果を取得
            $resultStatus = $p->getResultStatus();

            // 処理にエラーが発生している場合
            if( $resultStatus == 1 ){
                // レスポンスコードを取得
                $responseCode = $p->getResponseCode();

                // レスポンス詳細を取得
                $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

                // カード情報の登録完了メッセージを表示
                fn_set_notification('E', __("jp_paygent_ccreg_error"), __("jp_paygent_ccreg_register_failed") . '<br />' . $responseDetail);
                return false;
            // カード情報お預りが完了した場合
            }else{
                // CS-Cart側でのカード情報登録に必要なデータが存在する場合
                if( $p->hasResNext() ){
                    $res_array = $p->resNext();

                    $_data = array('user_id' => $order_info['user_id'], 'payment_method' => 'paygent_ccreg', 'quickpay_id' => $res_array['customer_card_id']);

                    $old_card_id = fn_pygnt_get_customer_card_id($order_info['user_id']);

                    if( !empty($old_card_id) ){
                        fn_pygnt_delete_card_info($order_info['user_id'], false);
                    }

                    db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);

                    // カード情報の登録完了メッセージを表示
                    fn_set_notification('N', __("notice"), __("jp_paygent_card_info_resistered"));
                    return true;
                // CS-Cart側でのカード情報登録に必要なデータが存在しない場合
                }else{
                    // エラーメッセージを表示
                    fn_set_notification('E', __("error"), __('jp_paygent_card_info_failed'));
                    return false;
                }
            }
        }
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
function fn_pygnt_update_cc_status_code($order_id, $cc_status, $payment_id = '')
{
    // ステータスコードをセット
    $_data = array('order_id' => $order_id, 'status_code' => $cc_status);

    if( !empty($payment_id) ){
        $_data['payment_id'] = $payment_id;
    }

    // 当該注文に関するステータスコード関連レコードの存在チェック
    $is_exists = db_get_row("SELECT * FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文に関するステータスコード関連レコードが存在する場合
    if( !empty($is_exists) ){
        // レコードを更新
        db_query("UPDATE ?:jp_paygent_cc_status SET ?u WHERE order_id = ?i", $_data, $order_id);

    // 当該注文に関するステータスコード関連レコードが存在しない場合
    }else{
        // レコードを新規追加
        db_query("REPLACE INTO ?:jp_paygent_cc_status ?e", $_data);
    }
}
/////////////////////////////////////////////////////////////////////////////////////
// カード決済 EOF
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
function fn_pygnt_cc_is_changeable($order_id, $processor_data)
{
    // 子注文の存在有無をチェック
    $parent_order_info = db_get_row("SELECT is_parent_order, parent_order_id FROM ?:orders WHERE order_id = ?i", $order_id);

    // 親子関係を持つ注文ではない場合（マーケットプレイスやサプライヤー機能を使った注文を考慮）
    if( $parent_order_info['is_parent_order'] == 'N' && $parent_order_info['parent_order_id'] == 0 ) {

        // 編集前の注文で利用された決済代行サービスのスクリプト名を取得
        $org_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $org_payment_method_data = fn_get_payment_method_data($org_payment_id);

        // 3Dセキュアを指定した決済は補正できない（3Dセキュア非対応カードによる決済でも、3Dセキュア利用フラグがオンになっている場合は補正できない）
        if( $org_payment_method_data['processor_params']['3dsecure'] == 'true' ){
            return false;
        }

        $org_processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id= ?i", $org_payment_method_data['processor_id']);

        // 編集後の注文で利用された決済代行サービスのスクリプト名を取得
        $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id= ?i", $processor_data['processor_id']);

        $changable_processor_scripts = array('paygent_cc.php', 'paygent_ccreg.php', 'paygent_mc.php', 'paygent_mcreg.php', 'paygent_cctkn.php', 'paygent_ccregtkn.php');

        // 編集前にペイジェントのカード決済または登録済みカード決済が選択されている場合
        if( in_array($org_processor_script, $changable_processor_scripts) && in_array($processor_script, $changable_processor_scripts) ){

            // 注文データからクレジット請求ステータスを取得
            $cc_status = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

            // ステータスコードが存在する場合
            if( !empty($cc_status) ){
                // ステータスコードがオーソリOK、消込済のいずれかに該当する注文は金額補正を許可
                switch($cc_status){
                    case 'AUTH_OK':
                    case 'SALES_CONFIRMED':
                        return true;
                        break;

                    default:
                        // do nothing;
                }
            }
        }
    }

    return false;
}




/**
 * 金額変更（補正オーソリ/補正売上）を実行
 *
 * @param $order_id
 * @return bool
 */
function fn_pygnt_cc_change_amount($order_id)
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_pygnt_check_process_validity('cc_change', $order_id);

    // 指定した処理を行うのに適した注文でない場合
    if ( !$is_valid_order ){
        return false;
    }

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 BOF
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェント接続モジュールのインスタンス作成および初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    // 変更後の金額
    $total = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);

    // 決済金額をセット
    $order_info = array();
    $order_info['total'] = $total;

    // 決済IDをセット
    $extras = array();
    $extras['payment_id'] = fn_pygnt_get_payment_id($order_id);

    // 注文データから決済ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

    // 決済ステータスが「オーソリOK」の場合
    if( $cc_status == 'AUTH_OK' ){
        // 処理タイプは「補正オーソリ」
        $type = 'cc_change_auth';

    // 決済ステータスが「消込済」の場合
    }elseif( $cc_status == 'SALES_CONFIRMED'){
        // 処理タイプは「補正売上」
        $type = 'cc_change_sales';

    // その他の場合
    }else{
        // 処理を終了
        return false;
    }

    // ペイジェントに送信するパラメータをセット
    $params = fn_pygnt_get_params($type, $order_id, $order_info, array(), $extras);

    // ペイジェントにデータを送信
    $result = fn_pygnt_send_params($p, $params);
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 EOF
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 BOF
    ////////////////////////////////////////////////////////////////////////////
    // データ送信においてエラーが発生した場合
    if( !$result === true ){
        // エラーメッセージを表示
        fn_set_notification('E', __("jp_paygent_" . $type . "_error"), __("jp_paygent_failed_" . $type, array('[oid]' => $order_id) ));

    // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ){

            // レスポンスコードを取得
            $responseCode = $p->getResponseCode();

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

            // エラーメッセージを表示
            fn_set_notification('E', __("jp_paygent_" . $type . "_error"), $responseDetail . '<br />' . __("jp_paygent_failed_" . $type, array('[oid]' => $order_id) ));

        // ペイジェントからデータが正常に戻された場合
        }else{

            if( $p->hasResNext() ){
                $res_array = $p->resNext();

                // 請求ステータスの変更に成功した場合
                if( $res_array['result'] == 0 ){

                    // セットする請求ステータスを取得
                    $cc_status = fn_pygnt_get_cc_status_code($type);

                    // セットする決済IDを取得
                    $payment_id = $res_array['payment_id'];

                    // 請求ステータスを更新
                    fn_pygnt_update_cc_status_code($order_id, $cc_status, $payment_id);

                    // DBに保管する支払い情報を生成
                    fn_pygnt_format_payment_info('cc_change_amount', $order_id, '', $res_array);

                    // 処理完了メッセージを表示
                    fn_set_notification('N', __("notice"), __("jp_paygent_completed_" . $type, array('[oid]' => $order_id)) );

                    return true;
                }
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 EOF
    ////////////////////////////////////////////////////////////////////////////

    return false;
}




/**
 * 金額変更（補正オーソリ/補正売上）を実行
 *
 * @param $order_id
 * @return bool
 */
function fn_pygnt_mc_change_amount($order_id)
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_pygnt_check_process_validity('mc_change', $order_id);

    // 指定した処理を行うのに適した注文でない場合
    if ( !$is_valid_order ){
        return false;
    }

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 BOF
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェント接続モジュールのインスタンス作成および初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    // 変更後の金額
    $total = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);

    // 決済金額をセット
    $order_info = array();
    $order_info['total'] = $total;

    // 決済IDをセット
    $extras = array();
    $extras['payment_id'] = fn_pygnt_get_payment_id($order_id);

    // 注文データから決済ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

    // 決済ステータスが「オーソリOK」の場合
    if($cc_status == 'AUTH_OK'){
        // 処理タイプは「補正オーソリ」
        $type = 'mc_change_auth';

        // 決済ステータスが「消込済」の場合
    }elseif( $cc_status == 'SALES_CONFIRMED' ){
        // 処理タイプは「補正売上」
        $type = 'mc_change_sales';

        // その他の場合
    }else{
        // 処理を終了
        return false;
    }

    // ペイジェントに送信するパラメータをセット
    $params = fn_pygnt_get_params($type, $order_id, $order_info, array(), $extras);

    // ペイジェントにデータを送信
    $result = fn_pygnt_send_params($p, $params);
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 EOF
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 BOF
    ////////////////////////////////////////////////////////////////////////////
    // データ送信においてエラーが発生した場合
    if( !$result === true ){
        // エラーメッセージを表示
        fn_set_notification('E', __("jp_paygent_" . $type . "_error"), __("jp_paygent_failed_" . $type, array('[oid]' => $order_id) ));

        // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ){

            // レスポンスコードを取得
            $responseCode = $p->getResponseCode();

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

            // エラーメッセージを表示
            fn_set_notification('E', __("jp_paygent_" . $type . "_error"), $responseDetail . '<br />' . __("jp_paygent_failed_" . $type, array('[oid]' => $order_id) ));

            // ペイジェントからデータが正常に戻された場合
        }else{

            if( $p->hasResNext() ){
                $res_array = $p->resNext();

                // 請求ステータスの変更に成功した場合
                if( $res_array['result'] == 0 ){

                    // セットする請求ステータスを取得
                    $cc_status = fn_pygnt_get_cc_status_code($type);

                    // セットする決済IDを取得
                    $payment_id = $res_array['payment_id'];

                    // 請求ステータスを更新
                    fn_pygnt_update_cc_status_code($order_id, $cc_status, $payment_id);

                    // DBに保管する支払い情報を生成
                    fn_pygnt_format_payment_info('mc_change_amount', $order_id, '', $res_array);

                    // 処理完了メッセージを表示
                    fn_set_notification('N', __("notice"), __("jp_paygent_completed_" . $type, array('[oid]' => $order_id)) );

                    return true;
                }
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 EOF
    ////////////////////////////////////////////////////////////////////////////

    return false;
}




/**
 * @param $order_id
 * @param string $processor_script
 */
function fn_pygnt_validate_order_update($order_id, $processor_script = '')
{
    // 編集前の注文で利用された決済代行サービスのスクリプト名を取得
    $org_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $org_payment_method_data = fn_get_payment_method_data($org_payment_id);
    $org_processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id= ?i", $org_payment_method_data['processor_id']);

    // 編集前の注文で決済代行サービスが利用されている場合
    if( !empty($org_processor_script) ){

        switch($org_processor_script){

            // 編集前の支払方法がカード決済または登録済みカード決済の場合
            case 'paygent_cc.php':
            case 'paygent_ccreg.php':
            case 'paygent_cctkn.php':
            case 'paygent_ccregtkn.php':
                // 編集後の支払方法もカード決済または登録済みカード決済の場合
                if( $processor_script == 'paygent_cc.php' || $processor_script == 'paygent_ccreg.php' || $processor_script == 'paygent_cctkn.php' || $processor_script == 'paygent_ccregtkn.php') {
                    // 注文データからクレジット請求ステータスを取得
                    $cc_status = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

                    // 3Dセキュアを指定した決済は補正できない（3Dセキュア非対応カードによる決済でも、3Dセキュア利用フラグがオンになっている場合は補正できない）
                    if ($org_payment_method_data['processor_params']['3dsecure'] == 'true') {
                        fn_set_notification('E', __("error"), __("jp_paygent_cc_new_payment_not_allowed"));
                        fn_redirect(fn_lcjp_get_error_return_url(), true);
                    }

                    // ステータスコードが存在する場合
                    if (!empty($cc_status)) {
                        // ステータスコードがオーソリOK、消込済のいずれかに該当する注文は処理を許可
                        switch ($cc_status) {
                            case 'AUTH_OK':
                            case 'SALES_CONFIRMED':
                                // do nothing
                                break;

                            default:
                                // ステータスコードがオーソリOK、消込済以外の場合は補正は許可しない
                                fn_set_notification('E', __("error"), __("jp_paygent_cc_new_payment_not_allowed"));
                                fn_redirect(fn_lcjp_get_error_return_url(), true);
                        }
                    }

                // カード決済から多通貨カード決済または多通貨登録済みカード決済に変更した場合
                }elseif( $processor_script == 'paygent_mc.php' || $processor_script == 'paygent_mcreg.php' ){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cc_changed_cc_to_mc"));

                // カード決済からコンビニ決済に変更した場合
                }elseif( $processor_script == 'paygent_cvs.php' ){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cc_changed_cc_to_cvs"));

                // カード決済から他の決済に変更した場合
                }elseif( empty($processor_script) || ($processor_script != 'paygent_cc.php' && $processor_script != 'paygent_ccreg.php') || ($processor_script != 'paygent_cctkn.php' && $processor_script != 'paygent_ccregtkn.php')){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cc_changed_cc_to_others"));
                }

                break;

            // 編集前の支払方法が多通貨カード決済または多通貨登録済みカード決済の場合
            case 'paygent_mc.php':
            case 'paygent_mcreg.php':
                // 編集後の支払方法も多通貨カード決済または多通貨登録済みカード決済の場合
                if( $processor_script == 'paygent_mc.php' || $processor_script == 'paygent_mcreg.php' ) {
                    // 注文データからクレジット請求ステータスを取得
                    $cc_status = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

                    // 3Dセキュアを指定した決済は補正できない（3Dセキュア非対応カードによる決済でも、3Dセキュア利用フラグがオンになっている場合は補正できない）
                    if ($org_payment_method_data['processor_params']['3dsecure'] == 'true') {
                        fn_set_notification('E', __("error"), __("jp_paygent_mc_new_payment_not_allowed"));
                        fn_redirect(fn_lcjp_get_error_return_url(), true);
                    }

                    // ステータスコードが存在する場合
                    if (!empty($cc_status)) {
                        // ステータスコードがオーソリOK、消込済のいずれかに該当する注文は処理を許可
                        switch ($cc_status) {
                            case 'AUTH_OK':
                            case 'SALES_CONFIRMED':
                                // do nothing
                                break;

                            default:
                                // ステータスコードがオーソリOK、消込済以外の場合は補正は許可しない
                                fn_set_notification('E', __("error"), __("jp_paygent_mc_new_payment_not_allowed"));
                                fn_redirect(fn_lcjp_get_error_return_url(), true);
                        }
                    }

                // 多通貨カード決済からカード決済または登録済みカード決済に変更した場合
                }elseif( $processor_script == 'paygent_cc.php' || $processor_script == 'paygent_ccreg.php' || $processor_script == 'paygent_cctkn.php' || $processor_script == 'paygent_ccregtkn.php'){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cc_changed_mc_to_cc"));

                // 多通貨カード決済からコンビニ決済に変更した場合
                }elseif( $processor_script == 'paygent_cvs.php' ){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cc_changed_mc_to_cvs"));

                // 多通貨カード決済から他の決済に変更した場合
                }elseif( empty($processor_script) || ($processor_script != 'paygent_mc.php' && $processor_script != 'paygent_mcreg.php') ){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cc_changed_mc_to_others"));
                }

                break;

            // 編集前の支払方法がコンビニ決済の場合
            case 'paygent_cvs.php':
                // 変更後の支払方法もコンビニ決済の場合
                if( $processor_script == 'paygent_cvs.php' ) {
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cvs_changed_cvs_to_cvs"));

                // コンビニ決済からカード決済に変更した場合
                }elseif($processor_script == 'paygent_cc.php' || $processor_script == 'paygent_ccreg.php' || $processor_script == 'paygent_cctkn.php' || $processor_script == 'paygent_ccregtkn.php'){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cvs_changed_cvs_to_cc"));

                // コンビニ決済から多通貨カード決済に変更した場合
                }elseif($processor_script == 'paygent_mc.php' || $processor_script == 'paygent_mcreg.php'){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cvs_changed_cvs_to_mc"));

                // コンビニ決済から他の決済に変更した場合
                }elseif( empty($processor_script) || $processor_script != 'paygent_cvs.php' ){
                    // 警告メッセージを表示
                    fn_set_notification('E', __("attention"), __("jp_paygent_cvs_changed_cvs_to_others"));
                }

            // その他の場合
            default:
                // do nothing
        }
    }
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
function fn_pygnt_get_cc_status_name($cc_status)
{
    // クレジット請求ステータス名を初期化
    $cc_status_name = '';

    // 請求ステータスコードに応じて請求ステータス名を取得
    switch($cc_status){
        // オーソリOK
        case 'AUTH_OK':
            $cc_status_name = __("jp_paygent_cc_auth_ok");
            break;
        // オーソリNG
        case 'AUTH_NG':
            $cc_status_name = __("jp_paygent_cc_auth_ng");
            break;
        // オーソリキャンセル済
        case 'AUTH_CANCELLED':
            $cc_status_name = __("jp_paygent_cc_auth_cancelled");
            break;
        // 実売上
        case 'SALES_CONFIRMED':
            $cc_status_name = __("jp_paygent_cc_sales_confirmed");
            break;
        // 売上取消済
        case 'SALES_CANCELLED':
            $cc_status_name = __("jp_paygent_cc_sales_cancelled");
            break;
    }

    return $cc_status_name;
}




/**
 * 売上確定 / 与信キャンセル / 売上キャンセル
 *
 * @param $type
 * @param $order_id
 * @return bool
 */
function fn_pygnt_send_cc_action($type, $order_id)
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_pygnt_check_process_validity($type, $order_id);

    // 指定した処理を行うのに適した注文でない場合
    if ( !$is_valid_order ){
        return false;
    }

    // ペイジェント接続モジュールのインスタンス作成および初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 BOF
    ////////////////////////////////////////////////////////////////////////////
    // 決済IDをセット
    $extras = array();
    $extras['payment_id'] = fn_pygnt_get_payment_id($order_id);

    // ペイジェントに送信するパラメータをセット
    $params = fn_pygnt_get_params($type, $order_id, array(), array(), $extras);

    // ペイジェントにデータを送信
    $result = fn_pygnt_send_params($p, $params);
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 EOF
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 BOF
    ////////////////////////////////////////////////////////////////////////////
    // データ送信においてエラーが発生した場合
    if( !$result === true ){
        // 通信エラー処理
        fn_pygnt_handle_process_error($result);

    // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ){
            // レスポンスコードを取得
            $responseCode = $p->getResponseCode();

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

            // 処理エラーメッセージを表示
            fn_set_notification('E', __("jp_paygent_" . $type . "_error"), __("jp_paygent_failed_" . $type, array('[oid]' => $order_id) ) . '<br />' . $responseDetail);

        // ペイジェントからデータが正常に戻された場合
        }else{

            if( $p->hasResNext() ){
                $res_array = $p->resNext();

                // 請求ステータスの変更に成功した場合
                if( $res_array['result'] == 0 ){

                    // CS-Cartにおける注文IDを取得
                    $order_id = str_replace(Registry::get('addons.paygent.trading_id_prefix'), '', $res_array['trading_id']);

                    // セットする請求ステータスを取得
                    $cc_status = fn_pygnt_get_cc_status_code($type);

                    // 請求ステータスを更新
                    fn_pygnt_update_cc_status_code($order_id, $cc_status);

                    // 処理完了メッセージを表示
                    fn_set_notification('N', __("notice"), __("jp_paygent_completed_" . $type) );

                    return true;
                }
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 EOF
    ////////////////////////////////////////////////////////////////////////////

    return false;
}




/**
 * 決済IDを取得
 *
 * @param $order_id
 * @return array
 */
function fn_pygnt_get_payment_id($order_id)
{
    $payment_id = db_get_field("SELECT payment_id FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

    return $payment_id;
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_pygnt_check_process_validity($type, $order_id)
{
    // 注文データからクレジット請求ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_paygent_cc_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 売上確定
        case 'cc_sales_confirm':
        case 'mc_sales_confirm':
        // 与信キャンセル
        case 'cc_auth_cancel':
        case 'mc_auth_cancel':
            if( $cc_status == 'AUTH_OK' ) return true;
            break;

        // 金額変更
        case 'cc_change':
        case 'mc_change':
            if( $cc_status == 'AUTH_OK' || $cc_status == 'SALES_CONFIRMED' ) return true;
            break;

        // 売上キャンセル
        case 'cc_sales_cancel':
        case 'mc_sales_cancel':
            if( $cc_status == 'SALES_CONFIRMED' ) return true;
            break;

        // その他
        default:
            // do nothing
    }

    return false;
}




/**
 * 処理内容に応じて請求ステータスコードを取得
 *
 * @param $type
 * @return string
 */
function fn_pygnt_get_cc_status_code($type)
{
    $status_code = '';

    // 処理内容に応じて請求ステータスコードを取得
    switch($type) {
        // 売上確定または補正売上
        case 'cc_sales_confirm':
        case 'cc_change_sales':
        case 'mc_sales_confirm':
        case 'mc_change_sales':
            $status_code = 'SALES_CONFIRMED';
            break;

        // 補正オーソリ
        case 'cc_change_auth':
        case 'mc_change_auth':
            $status_code = 'AUTH_OK';
            break;

        // 与信キャンセル
        case 'cc_auth_cancel':
        case 'mc_auth_cancel':
            $status_code = 'AUTH_CANCELLED';
            break;

        // 売上キャンセル
        case 'cc_sales_cancel':
        case 'mc_sales_cancel':
            $status_code = 'SALES_CANCELLED';
            break;

        default:
            //  do nothing
    }

    return $status_code;
}
/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 EOF
/////////////////////////////////////////////////////////////////////////////////////





/////////////////////////////////////////////////////////////////////////////////////
// コンビニ決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * コンビニ企業CDをコンビ二名に変換
 *
 * @param $cvs_company_id
 * @return string
 */
function fn_pygnt_cvs_get_cvs_name($cvs_company_id)
{
    switch($cvs_company_id){
        // セイコーマート
        case '00C016':
            return __("jp_cvs_sm");
            break;
        // ローソン
        case '00C002':
            return __("jp_cvs_ls");
            break;
        // ミニストップ
        case '00C004':
            return __("jp_cvs_ms");
            break;
        // サンクス
        case '00C006':
            return __("jp_cvs_ts");
            break;
        // サークルK
        case '00C007':
            return __("jp_cvs_ck");
            break;
        // デイリーヤマザキ
        case '00C014':
            return __("jp_cvs_dy");
            break;
        // セブンイレブン
        case '00C001':
            return __("jp_cvs_se");
            break;
        // ファミリーマート
        case '00C005':
            return __("jp_cvs_fm");
            break;
        // その他
        default:
            return 'N/A';
            break;
    }
}




/**
 * 申込コンビニ企業CDを元にCSVタイプを取得
 *
 * @param string $cvs_company_id
 * @return string
 */
function fn_pygnt_get_cvs_type($cvs_company_id = '')
{
    $cvs_type = '';

    // 申込コンビニ企業CDを元にCSVタイプを取得
    switch($cvs_company_id) {
        case "00C016":  // セイコーマート
        case "00C002":  // ローソン
        case "00C004":  // ミニストップ
        case "00C006":  // サンクス
        case "00C007":  // サークルKサンクス
            $cvs_type = "01";
            break;

        case "00C014":  // デイリーヤマザキ
            $cvs_type = "02";
            break;

        case "00C001":  // セブンイレブン
            $cvs_type = "03";
            break;

        case "00C005":  // ファミリーマート
            $cvs_type = "04";
            break;

        default:
            // do nothing
    }

    return $cvs_type;
}




/**
 * コメント欄にコンビニ決済情報をセット
 *
 * @param $order_id
 * @param $payment_info
 * @param $res_array
 * @param $params
 */
function fn_pygnt_cvs_set_comments($order_id, $payment_info, $res_array, $params)
{
    // 既存のコメントを取得
    $order_comments = db_get_field("SELECT notes FROM ?:orders WHERE order_id = ?i", $order_id);

    // 既存のコメントが存在する場合、改行を追加
    if($order_comments != ''){
        $order_comments .= "\n\n";
    }

    //////////////////////////////////////////////////////////////////
    // ペイジェント（コンビニ決済）用コメントを生成 BOF
    //////////////////////////////////////////////////////////////////
    // 見出し
    $order_comments .= __("jp_paygent_cvs_info") . "\n";

    // お支払可能なコンビ二
    $order_comments .= fn_pygnt_cvs_get_available_cvs($res_array['usable_cvs_company_id']) . "\n";

    // 決済ベンダ受付番号
    $order_comments .= fn_pygnt_cvs_get_receipt_number_name($payment_info['jp_paygent_cvs_name']) . ' : ' . $res_array['receipt_number'] . "\n";

    // セイコーマートの場合
    if( $params['cvs_company_id'] == '00C016' ){
        // 受付電話番号を追加
        $order_comments .= __("jp_paygent_cvs_conf_tel") . ' : ' . $params['customer_tel'] . "\n";
    // ローソンの場合
    }elseif( $params['cvs_company_id'] == '00C002' ){
        // 確認番号（400008固定）を追加
        $order_comments .= __("jp_paygent_cvs_conf_number") . ' : 400008' . "\n";
    }

    // 結果URL情報を追加
    if( !empty($res_array['receipt_print_url'])	){
        $order_comments .= __("jp_paygent_cvs_receipt_print_url") . " :\n" . $res_array['receipt_print_url'] . "\n";
    }

    // 支払期限日
    $order_comments .= __("jp_paygent_cvs_payment_due") . ' : ' . fn_pygnt_format_payment_limit_date($res_array['payment_limit_date']);
    //////////////////////////////////////////////////////////////////
    // ペイジェント（コンビニ決済）用コメントを生成 EOF
    //////////////////////////////////////////////////////////////////

    $valid_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);

    // 正常なフローでの処理の場合のみ追記する
    if( !empty($valid_id) ){
        $data = array('notes' => $order_comments);
        db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id);
    }
}




/**
 * コンビニの種類に応じて決済ベンダ受付番号の表記を変更
 *
 * @param $cvs_company_id
 * @return string
 */
function fn_pygnt_cvs_get_receipt_number_name($cvs_company_id)
{
    switch($cvs_company_id){
        // セイコーマート
        case '00C016':
            return __("jp_paygent_cvs_receipt_number1");
            break;
        // セブンイレブン
        case '00C001':
            return __("jp_paygent_cvs_receipt_number2");
            break;
        // ローソン・ファミリーマート
        case '00C002':
        case '00C005':
            return __("jp_paygent_cvs_receipt_number3");
            break;
        default:
            return __("jp_paygent_cvs_receipt_number4");
            break;
    }
}




/**
 * お支払い可能なコンビニ名を取得
 *
 * @param $usable_cvs_company_id
 * @return string
 */
function fn_pygnt_cvs_get_available_cvs($usable_cvs_company_id)
{
    $company_array = explode('-', $usable_cvs_company_id);

    $available_cvs = __("jp_paygent_cvs_available_cvs") . ' : ';

    if( count($company_array) > 1 ){
        $available_cvs .= "\n";
    }

    foreach($company_array as $company_id){
        $available_cvs .= fn_pygnt_cvs_get_cvs_name($company_id);

        if( count($company_array) > 1 ){
            $available_cvs .= "\n";
        }
    }

    return $available_cvs;
}
/////////////////////////////////////////////////////////////////////////////////////
// コンビニ決済 EOF
/////////////////////////////////////////////////////////////////////////////////////





/////////////////////////////////////////////////////////////////////////////////////
// 決済情報差分照会 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 決済情報差分照会を実施し、注文ステータスを変更
 */
function fn_pygnt_chk_status_change()
{
    $diff_exists = true;

    while($diff_exists){
        $diff_exists = fn_pygnt_chk_status_change_each();
    }
}




/**
 * 決済情報差分照会を実施し、注文ステータスを変更（決済情報差分1件ごとの処理）
 *
 * @param null $payment_notice_id
 * @return bool
 */
function fn_pygnt_chk_status_change_each($payment_notice_id = null)
{
    // ペイジェント接続モジュールのインスタンス作成および初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 BOF
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントに送信するパラメータをセット
    if( !empty($payment_notice_id) ){
        $extras = array();
        $extras['payment_notice_id'] = $payment_notice_id;
        $params = fn_pygnt_get_params('diff', '', array(), array(), $extras);
    }else{
        $params = fn_pygnt_get_params('diff');
    }

    // ペイジェントにデータを送信
    $result = fn_pygnt_send_params($p, $params);
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントとのデータ送受信 EOF
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 BOF
    ////////////////////////////////////////////////////////////////////////////
    // データ送信においてエラーが発生した場合
    if( !$result === true ){
        // do nothing
    // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ){
            // do nothing
        // ペイジェントから決済情報差分を正常に受信した場合
        }else{

            if( $p->hasResNext() ){
                $res_array = $p->resNext();

                // 決済情報差分が存在する場合
                if( ($res_array['success_code'] == 0 || $res_array['success_code'] == 2) && !empty($res_array['payment_type']) && !empty($res_array['trading_id']) ){
                    // CS-Cartにおける注文IDを取得
                    $order_id = str_replace(Registry::get('addons.paygent.trading_id_prefix'), '', $res_array['trading_id']);
                    // 決済情報差分に含まれる決済ステータスに基づきCS-Cartにおける注文ステータスを変更
                    fn_pygnt_process_diff($order_id, $res_array);
                    return true;
                }
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    // ペイジェントからの応答電文に基づく処理 EOF
    ////////////////////////////////////////////////////////////////////////////

    return false;
}




/**
 * 決済情報差分に含まれる決済ステータスに基づきCS-Cartにおける注文ステータスを変更
 *
 * @param $order_id
 * @param $payment_type
 * @param $payment_status
 * @param null $payment_notice_id
 */
function fn_pygnt_process_diff($order_id, $res_array)
{
    // CS-Cart上の注文ステータス変更フラグを初期化
    $flg_change_status = false;

    // CS-Cart上の注文ステータスを取得
    $order_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $order_id);

    // CS-Cart上の注文ステータスが「注文受付 (O)」の場合
    if( $order_status == 'O' ){

        switch($res_array['payment_type']){

            case '01': // ATM決済
            case '05': // 銀行ネット
                // 決済ステータスが「消込済」の場合
                if( $res_array['payment_status'] == '40' ){
                    $flg_change_status = true;
                }
                break;

            // コンビニ決済（番号方式）
            case '03':
                // 決済ステータスが「消込済」「速報検知済」の場合
                if( $res_array['payment_status'] == '40' || $res_array['payment_status'] == '43' ){
                    $flg_change_status = true;
                }
                break;

            // その他
            default:
                // do nothing
                break;
        }

        // CS-Cart上の注文ステータス変更フラグがtrueの場合
        if( $flg_change_status ){
            // fn_pygnt_initialize_module で変更した include_path を元に戻す
            // 【メモ】 戻さないと注文確認メールの送信時に以下のエラーが発生する
            // require_once(): Failed opening required 'Net/IDNA2/Exception.php' (include_path='/home/php7cscart/public_html/paygent/app/addons/paygent/lib/connect_module/')
            ini_set('include_path', Registry::get('config.dir.root') . '/app/lib/pear/' . PATH_SEPARATOR . ini_get('include_path'));

            // CS-Cart上の注文ステータスを「処理中 (P)」に変更
            fn_change_order_status($order_id, 'P');
        }
    }

    if( !empty($res_array['payment_notice_id']) || !empty($res_array['user_payment_date']) || !empty($res_array['cvs_company_id'])){
        // 支払情報にペイジェントから戻された決済通知ID、現地支払日時、支払コンビニ企業CD をセット
        fn_pygnt_chkdiff_format_payment_info($order_id, $res_array);
    }
}




/**
 * 決済情報差分照会時にDBに保管する支払情報を生成
 *
 * @param $order_id
 * @param $payment_notice_id
 * @return bool
 */
function fn_pygnt_chkdiff_format_payment_info($order_id, $res_array)
{
    // 注文IDが存在しない場合は処理を終了
    if( empty($order_id) ) return false;

    // 支払情報を取得
    $order_info = fn_get_order_info($order_id, true);
    $payment_info = $order_info['payment_info'];

    // 支払情報が存在しない場合は処理を終了
    if( empty($payment_info)  ) return false;

    // 支払情報が暗号化されている場合は復号化して変数にセット
    if( !is_array($payment_info) ){
        $info = @unserialize(fn_decrypt_text($payment_info));
        // 支払情報を変数にセット
    }else{
        $info = $payment_info;
    }

    // ペイジェントから戻された決済通知IDをセット
    if( !empty($res_array['payment_notice_id']) ){
        $info['jp_paygent_payment_notice_id'] = $res_array['payment_notice_id'];
    }

    // ペイジェントから戻された現地支払日時をセット
    if( !empty($res_array['user_payment_date']) ){
        $info['jp_paygent_user_payment_date'] = fn_pygnt_format_payment_date($res_array['user_payment_date']);
    }

    // ペイジェントから戻された支払コンビ二企業CDに基づきお支払いコンビ二名をセット
    if( !empty($res_array['cvs_company_id']) ) {
        $info['jp_paygent_cvs_paid'] = fn_pygnt_cvs_get_cvs_name($res_array['cvs_company_id']);
    }

    if( !empty($res_array['payment_notice_id']) ){
        // 決済通知IDを管理するテーブルを更新
        $notice_data = array('order_id' => $order_id, 'payment_notice_id' => $res_array['payment_notice_id']);
        db_query("REPLACE INTO ?:jp_paygent_notice ?e", $notice_data);
    }

    // 支払情報を暗号化
    $_data = fn_encrypt_text(serialize($info));

    // 支払情報を更新
    db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);

}




/**
 * 現地支払日時の表示形式をフォーマット
 *
 * @param $user_payment_date
 * @return string
 */
function fn_pygnt_format_payment_date($user_payment_date)
{
    // 正しい長さの日付データが渡された場合
    if( strlen($user_payment_date) == 14 ){
        $year = substr($user_payment_date, 0, 4);
        $month = substr($user_payment_date, 4, 2);
        $date = substr($user_payment_date, 6, 2);
        $hour = substr($user_payment_date, 8, 2);

        // 「YYYY/MM/DD HH:MM」形式の値を返す
        return $year . '/' . $month . '/' . $date . ' ' . $hour . ':00';

        // 正しい長さの日付データが渡されなかった場合
    }else{
        // 引数をそのまま返す
        return $user_payment_date;
    }
}




/**
 * 欠番となっている決済通知IDについて決済情報差分照会を実施し、注文ステータスを変更
 */
function fn_pygnt_chk_missing_diff()
{
    // 決済通知ID管理用テーブルに登録されているレコード数を取得
    $total = db_get_field("SELECT COUNT(*) FROM ?:jp_paygent_notice");

    // 決済通知ID管理用テーブルに登録されているレコードが2件以上存在する場合
    if ( !empty($total) && $total > 1 ){

        // 最小の決済通知ID
        $id_min = db_get_field('SELECT payment_notice_id FROM ?:jp_paygent_notice ORDER BY payment_notice_id');

        // 最大の決済通知ID
        $id_max = db_get_field('SELECT payment_notice_id FROM ?:jp_paygent_notice ORDER BY payment_notice_id DESC');

        for( $cnt = $id_min; $cnt <= $id_max; $cnt++ ){

            $payment_notice_id = db_get_field("SELECT payment_notice_id FROM ?:jp_paygent_notice WHERE payment_notice_id = ?i", $cnt);
            // 欠番となっている決済通知IDについて決済情報差分照会を実施
            if( empty($payment_notice_id) ){
                fn_pygnt_chk_status_change_each($cnt);
            }
        }
    }
}





/**
 * 注文IDから支払方法IDに紐付けられた決済代行サービスのスクリプトファイル名を取得
 * 【メモ】v4.3.9以降では fn_lcjp_get_processor_script_name_by_order_id を使用するためこの関数は削除する
 *
 * @param $order_id
 * @return bool
 */
function fn_pygnt_get_processor_script_name_by_order_id($order_id)
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
// 決済情報差分照会 EOF
/////////////////////////////////////////////////////////////////////////////////////

##########################################################################################
// END その他の関数
##########################################################################################
