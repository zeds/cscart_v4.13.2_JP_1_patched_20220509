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

//
// $Id: func.php by tommy from cs-cart.jp 2016
//
// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_localization_jp_[フックポイント名]
// (2) addons.xmlで定義した設定項目で動作する関数：fn_settings_variants_addons_localization_jp_[アイテムID]
// (3) (1)以外の関数：fn_lcjp_[任意の名称]

// Modified by takahashi from cs-cart.jp 2018
// $paymentをチェックする関数を追加
// 商品検索の設定を変更
// SEO作成時にピリオドがサニタイズされない問題を修正

// Modified by takahashi from cs-cart.jp 2019
// 軽減税率対応（あんどぷらす望月様よりご提供）

use Tygh\Registry;
use Tygh\Helpdesk;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

// 「お届け時間帯」「お届け日」の指定に対応するためのスクリプト
// checkout.pre.phpにて取得しセッション変数に格納したお届け時間帯を、
// 関数「fn_calculate_cart_content」内のフックポイント"apply_cart_shipping_rates"を用いて
// カート情報を格納するセッションにセットする
function fn_localization_jp_calculate_cart(&$cart, &$cart_products, &$auth, &$calculate_shipping, &$calculate_taxes, &$apply_cart_promotions)
{
    // カート情報に配送情報がセットされている場合
    if( isset($cart['shipping']) ){

        // 指定されたすべての配送方法について処理を実施
        foreach($cart['shipping'] as $_sid => $val) {

            // お届け時間帯に関するセッション変数が存在する場合
            if( isset(Tygh::$app['session']['delivery_time_selected']) ){
                // お届け時間帯に関するセッション変数のポインタを初期化
                reset(Tygh::$app['session']['delivery_time_selected']);

                foreach(Tygh::$app['session']['delivery_time_selected'] as $_group_key => $_delivery_timing_info){
                    // 配送方法にお届け日が指定されている場合
                    if( !empty($_delivery_timing_info[$_sid]) ){
                        // カート情報にお届け日をセット
                        $cart['shipping'][$_sid]['delivery_info'][$_group_key]['delivery_time_selected'] = $_delivery_timing_info[$_sid];
                    }
                }
            }

            // お届け日に関するセッション変数が存在する場合
            if( isset(Tygh::$app['session']['delivery_date_selected']) ){
                // お届け日に関するセッション変数のポインタを初期化
                reset(Tygh::$app['session']['delivery_date_selected']);

                // 指定されたすべての配送方法に関するお届け日について処理を実施
                foreach(Tygh::$app['session']['delivery_date_selected'] as $_group_key => $_delivery_date_info) {
                    // 配送方法にお届け日が指定されている場合
                    if( !empty($_delivery_date_info[$_sid]) ){
                        // カート情報にお届け日をセット
                        $cart['shipping'][$_sid]['delivery_info'][$_group_key]['delivery_date_selected'] = $_delivery_date_info[$_sid];
                    }
                }
            }
        }
    }
}




// 注文時に指定したお届け時間帯、お届け日をDBに格納
function fn_localization_jp_place_order(&$order_id, &$action, &$order_status, &$cart, &$auth)
{
    // 管理画面での処理の場合
    if(AREA == 'A'){
        $_controller = Registry::get('runtime.controller');
        $_mode = Registry::get('runtime.mode');

        // 注文情報の作成 or 編集の場合
        if($_controller == 'order_management' && $_mode == 'place_order' ){
            if (!empty($cart['product_groups'])) {
                foreach ($cart['product_groups'] as $group_key => $group) {
                    if (!empty($group['chosen_shippings'])) {
                        foreach ($group['chosen_shippings'] as $shipping_key => $shipping) {
                            // 配送IDを取得
                            $delivery_shipping_id = $shipping['shipping_id'];

                            // お届け日が指定された場合
                            if( isset($_REQUEST['delivery_date_selected_' . $shipping_key . '_' . $delivery_shipping_id]) ){
                                // カート情報にお届け時間帯をセット
                                $cart['shipping'][$delivery_shipping_id]['delivery_info'][$shipping_key]['delivery_date_selected'] = $_REQUEST['delivery_date_selected_' . $shipping_key . '_' . $delivery_shipping_id];
                            }

                            // お届け時間帯が指定された場合
                            if( isset($_REQUEST['delivery_time_selected_' . $shipping_key . '_' . $delivery_shipping_id]) ){
                                // カート情報にお届け時間帯をセット
                                $cart['shipping'][$delivery_shipping_id]['delivery_info'][$shipping_key]['delivery_time_selected'] = $_REQUEST['delivery_time_selected_' . $shipping_key . '_' . $delivery_shipping_id];
                            }
                        }
                    }
                }
            }
        }
    }

    if (!empty($cart['shipping'])){
        foreach($cart['shipping'] as $shipping_info){
            if( !empty($shipping_info['delivery_info']) && is_array($shipping_info['delivery_info']) ) {
                foreach($shipping_info['delivery_info'] as $_gkey => $_delivery_data){
                    $_data = array();
                    $_data['order_id'] = $order_id;
                    $_data['shipping_id'] = $shipping_info['shipping_id'];
                    $_data['group_key'] = $_gkey;
                    if($_delivery_data['delivery_time_selected']){
                        $_data['delivery_timing'] = $_delivery_data['delivery_time_selected'];
                    }else{
                        $_data['delivery_timing'] = '';
                    }
                    if($_delivery_data['delivery_date_selected']){
                        $_data['delivery_date'] = $_delivery_data['delivery_date_selected'];
                    }
                    db_query("REPLACE INTO ?:jp_order_delivery_info ?e", $_data);
                }
            }
        }
    }
}




// 注文削除時に関連するお届け時間帯、お届け日情報も削除
function fn_localization_jp_delete_order(&$order_id)
{
    db_query("DELETE FROM ?:jp_order_delivery_info WHERE order_id = ?i", $order_id);
}




// 注文情報取得時にお届け時間帯およびお届け日の情報も取得
// 受注処理未了のステータスの注文においてカード番号やセキュリティコードをマスキング
function fn_localization_jp_get_order_info(&$order, &$additional_data)
{
    $order_id = $order['order_id'];

    // 配送情報が存在する場合
    if (!empty($order['shipping'])) {
        foreach($order['shipping'] as $key => $shipping_info){
            $delivery_info = fn_lcjp_get_order_delivery_info($order_id, $shipping_info['shipping_id'], $shipping_info['group_key']);
            if( $delivery_info && (!empty($delivery_info['delivery_timing']) || !empty($delivery_info['delivery_date']) ) ){
                $order['shipping'][$key]['delivery_timing'] = !empty($delivery_info['delivery_timing']) ? $delivery_info['delivery_timing'] : __('jp_not_specified');
                $order['shipping'][$key]['delivery_date'] = !empty($delivery_info['delivery_date']) ? $delivery_info['delivery_date'] : __('jp_not_specified');
            }
        }
    }

    // 注文ステータスが「N（受注処理未了）」の場合にカード番号やセキュリティコードをマスキング
    if ( !empty($order_id) && $order['status'] == 'N'){
        // カード番号やセキュリティコードをマスキング
        $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
        fn_cleanup_payment_info($order_id, $payment_info, true);
    }
}




// ソート順がソート番号の小さい（大きい）順で複数の子カテゴリーを含む商品一覧はカテゴリーでグルーピングして表示
function fn_localization_jp_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by, &$lang_code)
{
    $_controller = Registry::get('runtime.controller');
    $_mode = Registry::get('runtime.mode');
    $_sort_by = !empty($params['sort_by']) ? $params['sort_by'] : "";
    $_sort_default = Registry::get('settings.Appearance.default_products_sorting');
    $_sort_order = !empty($params['sort_order']) ? $params['sort_order'] : "";

    // ソート順がソート番号の小さい（大きい）順で複数の子カテゴリーを含む商品一覧はカテゴリーでグルーピングして表示
    if (AREA == 'C' && $_controller == 'categories' && $_mode == 'view' && ((empty($_sort_by) && strstr($_sort_default, 'position') || $_sort_by == 'position') ) ){
        // パラメータでソート順に「ソート番号」が指定されている場合
        if (!empty($_sort_by) && $_sort_by == 'position') {
            // ソート順（昇順・降順）が指定されている場合
            if(!empty($_sort_order)){
                // 指定されたソート順をセット
                $_sort_order = strtoupper($_sort_order);
            // ソート順（昇順・降順）が指定されていない場合
            }else{
                // ソート順に「昇順」をセット
                $_sort_order = 'ASC';
            }
            // 商品をカテゴリー毎にグルーピング
            $sortings['position'] = "category_position $_sort_order, category_id, products_categories.position";
            $fields[] = '?:categories.position AS category_position, products_categories.category_id';

        // パラメータでソート順に「ソート番号」が指定されていない場合
        }else{
            // 商品一覧ページのデフォルトソート順に「ソート番号の小さい順」が指定されている場合
            if ($_sort_default == 'position-asc') {
                // 商品をカテゴリー毎にグルーピング
                $params['sort_by'] = 'position-asc';
                $sortings['position'] = 'category_position ASC, category_id, products_categories.position';
                $fields[] = '?:categories.position AS category_position, products_categories.category_id';
                // 商品一覧ページのデフォルトソート順に「ソート番号の大きい順」が指定されている場合
            } elseif( $_sort_default == 'position-desc' ) {
                $params['sort_by'] = 'position-desc';
                $sortings['position'] = 'category_position DESC, category_id, products_categories.position';
                $fields[] = '?:categories.position AS category_position, products_categories.category_id';
            }
        }
    }
}




////////////////////////////////////////////////////////////////////////
// 注文ステータスの並べ替えに関する処理 BOF
////////////////////////////////////////////////////////////////////////

// 注文ステータスを更新する際にソート順も更新する
function fn_localization_jp_update_status_post(&$status, &$status_data, &$type, &$lang_code)
{
    // 管理画面において注文ステータスを新規登録または更新する際にソート順が指定されている場合
    if( AREA == 'A' && $type == STATUSES_ORDER && !empty($_REQUEST['status_sort_id']) && !empty($status) ){
        // ソート順を更新
        $_data = array('status' => $status,
            'sort_id' => (int)$_REQUEST['status_sort_id']
        );
        db_query("REPLACE INTO ?:jp_order_status_sort ?e", $_data);
    }
}




// 注文ステータスを削除する際にソート順も削除する
function fn_localization_jp_delete_status_post(&$status, &$type, &$can_delete)
{
    // 管理画面においてデフォルト以外の注文ステータスを削除する場合
    if( AREA == 'A' && $type == STATUSES_ORDER && !empty($can_delete) && !empty($status) ){
        // ソート順も削除
        db_query("DELETE FROM ?:jp_order_status_sort WHERE status = ?s", $status);
    }
}




// 注文ステータスの管理画面において注文ステータスをソート順に並べる
function fn_localization_jp_get_statuses_post(&$statuses, &$join, &$condition, &$type, &$status_to_select, &$additional_statuses, &$exclude_parent, &$lang_code, &$company_id, &$order)
{
    // 管理画面で注文ステータスを表示する場合
    if( AREA == 'A' && $type == STATUSES_ORDER ){
        // 注文ステータスをソート順に並べ替え
        $statuses = fn_lcjp_sort_order_statuses($statuses);
    }
}




// 注文管理画面において注文ステータスをソート順に並べる
function fn_localization_jp_jp_get_simple_statuses_post(&$type, &$result)
{
    // 管理画面で注文ステータスを表示する場合
    if( AREA == 'A' && $type == STATUSES_ORDER ){
        // 注文ステータスをソート順に並べ替え
        $result = fn_lcjp_sort_order_statuses($result);
    }
}
////////////////////////////////////////////////////////////////////////
// 注文ステータスの並べ替えに関する処理 EOF
////////////////////////////////////////////////////////////////////////




// 請求先情報もしくは配送先情報の氏名フリガナを連絡先情報にコピーする
function fn_localization_jp_jp_update_kana(&$user_data)
{
    $familyname_kana_c = (int)Registry::get('addons.localization_jp.jp_familyname_kana_c');
    $firstname_kana_c = (int)Registry::get('addons.localization_jp.jp_firstname_kana_c');
    $familyname_kana_b = (int)Registry::get('addons.localization_jp.jp_familyname_kana_b');
    $firstname_kana_b = (int)Registry::get('addons.localization_jp.jp_firstname_kana_b');
    $familyname_kana_s = (int)Registry::get('addons.localization_jp.jp_familyname_kana_s');
    $firstname_kana_s = (int)Registry::get('addons.localization_jp.jp_firstname_kana_s');

    $user_profile_type = db_get_field("SELECT profile_type FROM ?:user_profiles WHERE profile_id = ?i", $user_data['profile_id']);

    // メインの連絡先情報の追加・更新の場合
    if( $user_profile_type == 'P' || Registry::get('settings.General.user_multiple_profiles') == 'N' ){
        // ショップフロントの場合
        if (AREA != 'A') {
            // 「住所情報の表示順」の設定内容に基づき、連絡先情報にコピーするデータの取得元を決定
            Registry::get('settings.General.address_position') == 'billing_first' ? $address_zone = 'b' : $address_zone = 's';

            // 管理画面の場合
        } else {
            // 連絡先にコピーするデータの取得元は一律「請求先情報」
            $address_zone = 'b';
        }

        // 請求先情報もしくは配送先情報の氏名フリガナを連絡先情報にコピー
        if( $address_zone == 'b' ){

            if( empty($user_data['fields'][$familyname_kana_c]) && !empty($user_data['fields'][$familyname_kana_b]) ){
                $user_data['fields'][$familyname_kana_c] = $user_data['fields'][$familyname_kana_b];
            }

            if( empty($user_data['fields'][$firstname_kana_c]) && !empty($user_data['fields'][$firstname_kana_b]) ){
                $user_data['fields'][$firstname_kana_c] = $user_data['fields'][$firstname_kana_b];
            }

        }else{

            if( empty($user_data['fields'][$familyname_kana_c]) && !empty($user_data['fields'][$familyname_kana_s]) ){
                $user_data['fields'][$familyname_kana_c] = $user_data['fields'][$familyname_kana_s];
            }

            if( empty($user_data['fields'][$firstname_kana_c]) && !empty($user_data['fields'][$firstname_kana_s]) ){
                $user_data['fields'][$firstname_kana_c] = $user_data['fields'][$firstname_kana_s];
            }

        }

    }
}




// タイムスタンプ作成時の日付フォーマットの変更
function fn_localization_jp_jp_post_format_timestamp(&$timestamp, &$h, &$m, &$s, &$ts)
{
    if( CART_LANGUAGE == 'ja' ){
        $timestamp = mktime($h, $m, $s, $ts[1], $ts[2], $ts[0]);
    }
}




// 日本の配送業者を利用した配送方法の登録・更新時に 'service_params' に独自の設定値をセット
// ※「料金計算」が「リアルタイム」の場合、'service_params' に値がセットされていないとエラー
// が発生するため
function fn_localization_jp_update_shipping(&$shipping_data, &$shipping_id, &$lang_code)
{
    // 配送サービスのIDを取得
    $service_id = (int)$shipping_data['service_id'];

    // 日本の配送サービス（IDが9000以上）で'service_params'が定義されていない場合
    if($service_id >= 9000 && !isset($shipping_data['service_params']) ){
        // 'service_params'に日本の配送サービス向けパラメータをセット
        $shipping_data['service_params']['jp_shipping'] = 'Y';
        $shipping_data['service_params'] = serialize($shipping_data['service_params']);
    }
}




// ショップ（出品者）を追加する際に各配送サービスの送料情報レコードも作成
function fn_localization_jp_update_company(&$company_data, &$company_id, &$lang_code, &$action)
{
    // ショップ（出品者）の追加の場合
    if ($action == 'add' ){
        // デフォルトの送料情報を取得
        $default_shipping_rates = db_get_array("SELECT * FROM ?:jp_shipping_rates WHERE company_id = ?i", 0);

        // デフォルトの送料情報が存在する場合
        if( !empty($default_shipping_rates) && is_array($default_shipping_rates) ){
            // デフォルトの送料情報を新しいショップ（出品者）向けにコピー
            foreach($default_shipping_rates as $rate_info){
                $_data = $rate_info;
                unset($_data['rate_id']);
                $_data['company_id'] = $company_id;
                db_query("REPLACE INTO ?:jp_shipping_rates ?e", $_data);
            }
        }
    }
}




// 出品者をインポートする際に各配送サービスの送料情報レコードも作成
function fn_localization_jp_import_post($pattern, $import_data, $options, $result, $processed_data)
{
    if ($result) {
        // 出品者の場合
        if ($pattern['section'] == 'vendors') {
            foreach($import_data as $data){
                foreach($data as $key => $value){
                    $data_email = $value['email'];
                    $company_id = db_get_field("SELECT company_id FROM ?:companies WHERE email = ?s", $data_email);

                    $is_shipping_rate = db_get_field("SELECT COUNT(rate_id) FROM ?:jp_shipping_rates WHERE company_id = ?i", $company_id);

                    // 送料情報が存在しない場合
                    if($is_shipping_rate == 0){
                        // デフォルトの送料情報を取得
                        $default_shipping_rates = db_get_array("SELECT * FROM ?:jp_shipping_rates WHERE company_id = ?i", 0);

                        // デフォルトの送料情報が存在する場合
                        if (!empty($default_shipping_rates) && is_array($default_shipping_rates)) {
                            // デフォルトの送料情報を新しいショップ（出品者）向けにコピー
                            foreach ($default_shipping_rates as $rate_info) {
                                $_data = $rate_info;
                                unset($_data['rate_id']);
                                $_data['company_id'] = $company_id;
                                db_query("REPLACE INTO ?:jp_shipping_rates ?e", $_data);
                            }
                        }
                    }
                }
            }
        }
    }
}




// ショップ（出品者）を削除する際に各配送サービスの送料情報レコードも削除
function fn_localization_jp_delete_company(&$company_id)
{
    db_query("DELETE FROM ?:jp_shipping_rates WHERE company_id = ?i", $company_id);
}




// エクスポート文字コードの選択
// controllers/admin/exim.phpに追加したフックポイントより
function fn_localization_jp_jp_output_csv_pre(&$csv)
{
    $output_char_code = Registry::get('addons.localization_jp.jp_export_char_code');
    if($output_char_code != 'UTF-8') {
        $char_codes = mb_list_encodings();
        $csv = mb_convert_encoding($csv, (in_array('SJIS-win', $char_codes) ? 'SJIS-win' : 'SJIS'), 'UTF-8');
    }
}




// 注文確認書出力において連絡先情報フィールドおよび氏名ふりがなフィールドを取得しない
// マーケットプレイスへの参加申請ページまたは管理画面からの出品者登録において氏名ふりがなフィールドを取得しない
function fn_localization_jp_get_profile_fields(&$location, &$select, &$condition)
{
    // コントローラー名とモード名を取得
    $_controller = Registry::get('runtime.controller');
    $_mode = Registry::get('runtime.mode');

    // 注文確認書出力において連絡先情報フィールドおよび氏名ふりがなフィールドを取得しない
    if( $location == 'I' && ($_REQUEST['dispatch'] == 'orders.print_invoice' || $_REQUEST['dispatch'] == 'orders.bulk_print') ){
        $condition .= db_quote(" AND ?:profile_fields.section <> 'C'");
        $kana_fields = array('first-name-kana', 'last-name-kana');
        $condition .= db_quote(' AND ?:profile_fields.class NOT IN (?a)', $kana_fields);
    // マーケットプレイスへの参加申請ページまたは管理画面からの出品者登録において氏名ふりがなフィールドを取得しない
    }elseif( $location == 'A' && ( $_controller == 'companies' && ($_mode == 'apply_for_vendor' || $_mode == 'add') ) ){
        $kana_fields = array('first-name-kana', 'last-name-kana');
        $condition .= db_quote(' AND ?:profile_fields.class NOT IN (?a)', $kana_fields);
    }
}




// SBPS決済またはゼウス決済が完了した場合に各決済代行業者に文字列'OK'を返す
function fn_localization_jp_order_placement_routines(&$order_id, &$force_notification, &$order_info)
{
    // 本スクリプトが jp_extras/sbps/result.php 経由で実行されている場合
    if( !empty(Tygh::$app['session']['sbps_process_order']) && Tygh::$app['session']['sbps_process_order'] == 'Y' ){

        // セッション変数を解放
        unset(Tygh::$app['session']['sbps_process_order']);

        // 注文情報から決済代行業者のIDを取得
        $processor_id = $order_info['payment_method']['processor_id'];

        // 決済代行業者を使った決済の場合
        if( !empty($processor_id) && $processor_id > 0 ){

            // 決済代行業者のスクリプトファイル名を取得
            $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

            // 決済代行業者のスクリプトファイル名がSBPSで利用するものと同一の場合
            if( $processor_script == 'sbps.php' || $processor_script == 'sbps_rb.php' ){
                // SBPSへの戻り値として OK を出力して処理を終了
                echo 'OK';
                exit;
            }
        }

    // 本スクリプトが jp_extras/zeus/notify.php 経由で実行されている場合
    }elseif( !empty(Tygh::$app['session']['zeus_process_order']) && Tygh::$app['session']['zeus_process_order'] == 'Y' ){
        // セッション変数を解放
        unset(Tygh::$app['session']['zeus_process_order']);

        // 注文情報から決済代行業者のIDを取得
        $processor_id = $order_info['payment_method']['processor_id'];

        // 決済代行業者を使った決済の場合
        if( !empty($processor_id) && $processor_id > 0 ){

            // 決済代行業者のスクリプトファイル名を取得
            $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

            // 決済代行業者のスクリプトファイル名がゼウスカード決済で利用するものと同一の場合
            if( $processor_script == 'zeus.php' ){
                // ゼウスへの戻り値として OK を出力して処理を終了
                echo 'OK';
                exit;
            }
        }
    }
}




// 決済代行行者などに送信する姓フリガナ、名フリガナ情報を取得
function fn_lcjp_get_name_kana($order_info)
{
    $kana_info = array();
    $firstname_kana = '';
    $lastname_kana = '';

    foreach($order_info['fields'] as $key => $val){
        // 請求先情報セクションに「姓フリガナ」が存在し、値が入力されている場合には決済代行行者に送信する「姓フリガナ」にセット
        $firstname_kana_b_total = db_get_field("SELECT COUNT(*) FROM ?:profile_field_descriptions LEFT JOIN ?:profile_fields ON ?:profile_field_descriptions.object_id = ?:profile_fields.field_id WHERE ?:profile_field_descriptions.description = '姓フリガナ' AND ?:profile_field_descriptions.lang_code = 'ja' AND ?:profile_fields.section = 'B' AND ?:profile_field_descriptions.object_id = $key");

        // 請求先情報セクションに「名フリガナ」が存在し、値が入力されている場合には決済代行行者に送信する「名フリガナ」にセット
        $lastname_kana_b_total = db_get_field("SELECT COUNT(*) FROM ?:profile_field_descriptions LEFT JOIN ?:profile_fields ON ?:profile_field_descriptions.object_id = ?:profile_fields.field_id WHERE ?:profile_field_descriptions.description = '名フリガナ' AND ?:profile_field_descriptions.lang_code = 'ja' AND ?:profile_fields.section = 'B' AND ?:profile_field_descriptions.object_id = $key");

        if( $firstname_kana_b_total > 0 ) $firstname_kana = $val;
        if( $lastname_kana_b_total > 0 ) $lastname_kana = $val;
        if( $firstname_kana != '' && $lastname_kana != '' ) break;
    }

    // 請求先情報セクションにフリガナが存在しない場合には連絡先情報セクションから抽出（連絡先氏名と請求先氏名が同一の場合のみ）
    if( ($firstname_kana == '' && $lastname_kana == '') && ($order_info['firstname'] == $order_info['b_firstname'] && $order_info['lastname'] == $order_info['b_lastname']) ){
        foreach($order_info['fields'] as $key => $val){
            // 連絡先情報セクションに「姓フリガナ」が存在し、値が入力されている場合には決済代行行者に送信する「姓フリガナ」にセット
            $firstname_kana_c_total = db_get_field("SELECT COUNT(*) FROM ?:profile_field_descriptions LEFT JOIN ?:profile_fields ON ?:profile_field_descriptions.object_id = ?:profile_fields.field_id WHERE ?:profile_field_descriptions.description = '姓フリガナ' AND ?:profile_field_descriptions.lang_code = 'ja' AND ?:profile_fields.section = 'C' AND ?:profile_field_descriptions.object_id = $key");

            // 連絡先情報セクションに「名フリガナ」が存在し、値が入力されている場合には決済代行行者に送信する「名フリガナ」にセット
            $lastname_kana_c_total = db_get_field("SELECT COUNT(*) FROM ?:profile_field_descriptions LEFT JOIN ?:profile_fields ON ?:profile_field_descriptions.object_id = ?:profile_fields.field_id WHERE ?:profile_field_descriptions.description = '名フリガナ' AND ?:profile_field_descriptions.lang_code = 'ja' AND ?:profile_fields.section = 'C' AND ?:profile_field_descriptions.object_id = $key");

            if( $firstname_kana_c_total > 0 ) $firstname_kana = $val;
            if( $lastname_kana_c_total > 0 ) $lastname_kana = $val;
            if( $firstname_kana != '' && $lastname_kana != '' ) break;
        }
    }

    $kana_info = array('firstname_kana' => $firstname_kana, 'lastname_kana' => $lastname_kana);

    return $kana_info;
}




// 日本語版専用ライセンス認証サーバーを使ってアップデートの有無をチェック
function fn_localization_jp_jp_license_auth($auth)
{
    Tygh::$app['session']['last_status'] = 'INIT';

    if (AREA != 'C') {
        if (Tygh::$app['session']['auth']['area'] == 'A' && !empty(Tygh::$app['session']['auth']['user_id'])) {
            $domains = '';
            if (fn_allowed_for('ULTIMATE')) {
                $storefronts = db_get_fields('SELECT storefront FROM ?:companies WHERE storefront != ""');
                if (!empty($storefronts)) {
                    $domains = implode(',', $storefronts);
                }
            }

            $extra_fields = array(
                'token' => Helpdesk::token(true),
                'store_key' => Helpdesk::getStoreKey(),
                'domains' => $domains,
                'store_mode' => fn_get_storage_data('store_mode'),
            );

            $data = Helpdesk::getLicenseInformation('', $extra_fields);
            Helpdesk::parseLicenseInformation($data, $auth);
        }
    }
}




// 日本語が選択された状態で会員登録済みユーザーを表示する際に使用する言語変数を変更
function fn_localization_jp_get_default_usergroups(&$default_usergroups, &$lang_code)
{
    if( !empty($default_usergroups) && $lang_code == 'ja'){
        $default_usergroups = array(
            array(
                'usergroup_id' => USERGROUP_ALL,
                'status' => 'A',
                'type' => 'C',
                'usergroup' => __('all', '', $lang_code)
            ),
            array(
                'usergroup_id' => USERGROUP_GUEST,
                'status' => 'A',
                'type' => 'C',
                'usergroup' => __('guest', '', $lang_code)
            ),
            array(
                'usergroup_id' => USERGROUP_REGISTERED,
                'status' => 'A',
                'type' => 'C',
                'usergroup' => __('registered_customers', '', $lang_code)
            )
        );
    }
}




// 出品者が管理画面に初回ログインしてパスワードを再設定する際には、会員情報の変更メールは送信しない
// （氏名などが登録されていないため）
function fn_localization_jp_update_user_pre(&$user_id, &$user_data, &$auth, &$ship_to_another, &$notify_user, $can_update)
{
    // コントローラーを取得
    $_controller = Registry::get('runtime.controller');
    // モードを取得
    $_mode = Registry::get('runtime.mode');

    // 請求先住所の「姓」が登録されていない状態でパスワードの再設定を行う場合
    if ($_mode == 'password_change' && empty($user_data['b_firstname'])){
        // 出品者への通知は行わない
        $notify_user = false;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // 注文確定の際にfirstnameとlastnameを請求書住所から更新
        if ($_mode == 'place_order') {
            if (empty($user_data['firstname'])) {
                if ($user_data['firstname'] != $user_data['b_firstname'] && !empty($user_data['b_firstname'])) {
                    $user_data['firstname'] = $user_data['b_firstname'];
                }
            }
            if (empty($user_data['lastname'])) {
                if ($user_data['lastname'] != $user_data['b_lastname'] && !empty($user_data['b_lastname'])) {
                    $user_data['lastname'] = $user_data['b_lastname'];
                }
            }

            // 国フィールドを表示しない場合の対応
            if (CART_LANGUAGE == 'ja') {
                $user_data['s_country'] = isset($user_data['s_country']) ? $user_data['s_country'] : 'JP';
                $user_data['b_country'] = isset($user_data['b_country']) ? $user_data['b_country'] : 'JP';
            }
        } elseif ($_controller == 'profiles' && ($_mode == 'update' || $_mode == 'add')) {
            // 国フィールドを表示しない場合の対応
            if (CART_LANGUAGE == 'ja') {
                $user_data['s_country'] = isset($user_data['s_country']) ? $user_data['s_country'] : 'JP';
                $user_data['b_country'] = isset($user_data['b_country']) ? $user_data['b_country'] : 'JP';
            }
        }
    }
}




// フィルタによる価格範囲指定の表記方法を変更
function fn_localization_jp_get_filter_range_name_post(&$range_name, &$range_type, &$range_id)
{
    // 価格範囲指定の場合
    if($range_type == 'P'){
        $from = fn_strtolower(__('range_from'));
        $to = fn_strtolower(__('range_to'));
        $field_name = __('price');

        $from_org = $field_name . ' : ' . $from . ' ';
        $from_replace = $field_name . ' : ';
        $to_org = ' ' . $to . ' ';
        $to_replace = ' ～';

        $range_name = mb_ereg_replace($from_org, $from_replace, $range_name);
        $range_name = mb_ereg_replace($to_org, $to_replace, $range_name);
    }
}




/**
 * 会員情報を削除する場合に、登録済みカード決済に関するレコードも削除
 *
 * @param $user_id
 * @param $user_data
 * @param $result
 */
function fn_localization_jp_post_delete_user(&$user_id, &$user_data, &$result)
{
    if($result){
        db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i", $user_id);
    }
}




/**
 * 日本の都道府県の並び順を修正
 *
 * @param $params
 * @param $items_per_page
 * @param $lang_code
 * @param $fields
 * @param $joins
 * @param $condition
 * @param $group
 * @param $sorting
 * @param $limit
 */
function fn_localization_jp_get_states(&$params,  &$items_per_page, &$lang_code, &$fields, &$joins, &$condition, &$group, &$sorting, &$limit)
{
    if(!empty($params) && $params['country_code'] == 'JP'){
        $sorting = "ORDER BY c.country, a.state_id";
    }
}




/**
 * 商品情報を取得する際に、「税込の通常価格」「税込の割引前価格」も取得する
 *
 * @param $product
 * @param $auth
 * @param $params
 */
function fn_localization_jp_gather_additional_product_data_post(&$product, &$auth, &$params)
{
    // 商品に税金が設定されている場合
    if( !empty($product['tax_ids']) ){
        // 税額を初期化
        $tax_value = 0;

        // 通常価格
        $tx_list_price = $product['list_price'];

        // 割引前の価格
        if( !empty($product['original_price']) ){
            $tx_original_price = $product['original_price'];
        }elseif( !empty($product['base_price']) ){
            $tx_original_price = $product['base_price'];
        }

        // 商品に設定された税金の詳細を取得
        $product_taxes = fn_get_set_taxes($product['tax_ids']);

        // 商品に設定された税金の詳細が存在する場合
        if( !empty($product_taxes) ){
            $calculated_data = fn_calculate_tax_rates($product_taxes, $tx_list_price, 1, $auth, Tygh::$app['session']['cart']);
            // Apply taxes to product subtotal
            if (!empty($calculated_data)) {
                foreach ($calculated_data as $_k => $v) {
                    $tax_value += $v['tax_subtotal'];
                    if ($v['price_includes_tax'] != 'Y') {
                        $tx_list_price += $v['tax_subtotal'];
                    }
                }
            }

            // 税込の通常価格をセット
            $product['taxed_list_price'] = $tx_list_price;

            $calculated_data = fn_calculate_tax_rates($product_taxes, $tx_original_price, 1, $auth, Tygh::$app['session']['cart']);
            // Apply taxes to product subtotal
            if (!empty($calculated_data)) {
                foreach ($calculated_data as $_k => $v) {
                    $tax_value += $v['tax_subtotal'];
                    if ($v['price_includes_tax'] != 'Y') {
                        $tx_original_price += $v['tax_subtotal'];
                    }
                }
            }

            // 税込の割引前価格をセット
            $product['taxed_original_price'] = $tx_original_price;
        }
    }
}




/**
 * PHPMailerによるメール送信の設定を変更
 *
 * @param $mailer
 * @param $params
 * @param $area
 * @param $lang_code
 */
function fn_localization_jp_send_mail_pre(&$mailer, &$params, &$area, &$lang_code)
{
    // エンコーディングをbase64(7bit)に変更(デフォルトは'8bit')
    $mailer->Encoding = "base64";
}




/**
 * 商品検索の設定を変更
 *
 * @param $mailer
 * @param $params
 * @param $area
 * @param $lang_code
 */
function fn_localization_jp_get_products_pre(&$params, &$items_per_page, &$lang_code)
{
    // 複数検索キーワードを OR/AND で検索する
    if((empty(fn_lcjp_get_jp_settings('jp_prod_search_mode')) && Registry::get('addons.localization_jp.jp_prod_search_mode') == "jp_prod_srch_any") || fn_lcjp_get_jp_settings('jp_prod_search_mode')  == "jp_prod_srch_any"){
        $match = "any";
    }
    else {
        $match = "all";
    }

    $params['match'] = $match;
}




/**
 * SEO作成時にピリオドがサニタイズされない問題を修正
 *
 * @param string $result      Generated file name
 * @param string $str         Basic file name
 * @param string $object_type Object type
 * @param int    $object_id   Object identifier
 */
function fn_localization_jp_generate_name_post(&$result, &$str, &$object_type, &$object_id)
{
    $result = str_replace('.', '', $str);
    if( $result === "-" ){
        $result = "";
    }
}




/**
 * SQLインジェクション不正アクセス防止
 *
 */
function fn_localization_jp_before_dispatch($controller, $mode, $action, $dispatch_extra, $area){
    if($action == '**'){
        echo 'INVALID ACCESS';
        exit();
    }
}




/**
 * 注文作成時に国データが無い場合の対応
 *
 * @param array $order
 */
function fn_localization_jp_create_order(&$order){
    // 国フィールドを表示しない場合の対応
    if (CART_LANGUAGE == 'ja') {
        $order['s_country'] = isset($order['s_country']) ? $order['s_country'] : 'JP';
        $order['b_country'] = isset($order['b_country']) ? $order['b_country'] : 'JP';
    }
}

##########################################################################################
// END フックポイントで動作する関数
##########################################################################################





##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################

// アドオンのインストール時の動作
function fn_lcjp_addon_install()
{
    require_once(Registry::get('config.dir.addons') . 'localization_jp/func_installer.php');
    fn_lcjp_install('localization_jp');
    fn_lcjp_configure_japanese_version();
}



// アドオンのアンインストール時の動作
function fn_lcjp_addon_uninstall()
{
    // アドオンのアンインストールを許可しない
    fn_set_notification('E', __('error'), __('jp_addons_unable_to_uninstall'));
    fn_redirect('addons.manage', true);
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################





##########################################################################################
// START アドオンの設定ページで動作する関数
##########################################################################################

// 連絡先情報の名フリガナに対応するフィールド指定用リストを出力
function fn_settings_variants_addons_localization_jp_jp_firstname_kana_c()
{
    return fn_lcjp_get_add_profile_fields('C','I');
}




// 請求先情報の姓フリガナに対応するフィールド指定用リストを出力
function fn_settings_variants_addons_localization_jp_jp_familyname_kana_b()
{
    return fn_lcjp_get_add_profile_fields('B','I');
}




// 請求先情報の名フリガナに対応するフィールド指定用リストを出力
function fn_settings_variants_addons_localization_jp_jp_firstname_kana_b()
{
    return fn_lcjp_get_add_profile_fields('B','I');
}




// 配送先情報の姓フリガナに対応するフィールド指定用リストを出力
function fn_settings_variants_addons_localization_jp_jp_familyname_kana_s()
{
    return fn_lcjp_get_add_profile_fields('S','I');
}




// 配送先情報の名フリガナに対応するフィールド指定用リストを出力
function fn_settings_variants_addons_localization_jp_jp_firstname_kana_s()
{
    return fn_lcjp_get_add_profile_fields('S','I');
}




// PDF納品書の請求先会社名フィールド取得関数
function fn_settings_variants_addons_localization_jp_jp_pdfinv_billing_company_field()
{
    return fn_lcjp_get_add_profile_fields('B','I');
}




// PDF納品書の配送先会社名フィールド取得関数
function fn_settings_variants_addons_localization_jp_jp_pdfinv_shipping_company_field()
{
    return fn_lcjp_get_add_profile_fields('S','I');
}




// 連絡先情報の姓フリガナに対応するフィールド指定用リストを出力
function fn_settings_variants_addons_localization_jp_jp_familyname_kana_c()
{
    return fn_lcjp_get_add_profile_fields('C','I');
}
##########################################################################################
//  END  アドオンの設定ページで動作する関数
##########################################################################################





##########################################################################################
// START その他の関数
##########################################################################################

// 配送方法に「お届け時間帯」「お届け日」指定用ドロップダウンリストを表示させるためのスクリプト
function fn_lcjp_get_delivery_timing($shipping_id)
{
    // 「お届け時間帯」「お届け日」に関する変数を初期化
    $delivery_timing_data = array();

    // 配送方法に関するデータを取得
    $service_id = db_get_field("SELECT service_id FROM ?:shippings WHERE shipping_id = ?i", (int)$shipping_id);
    $service_info = db_get_hash_array("SELECT service_id, module, code FROM ?:shipping_services WHERE service_id = ?i", 'service_id', $service_id);

    // 「shipping_services」テーブルにレコードが存在した場合
    if( array_key_exists($service_id, $service_info) ){

        // 配送モジュールのクラス名
        $module_name = fn_camelize($service_info[$service_id]['module']);
        $module = 'Tygh\\Shippings\\Services\\' . $module_name;

        // 配送モジュールのクラスが存在する場合
        if (class_exists($module)) {
            // クラスをインスタンス化
            $tmp_module_obj = new $module;

            // お届け時間帯を定義したメソッド名
            $method_name = 'get' . $module_name . 'DeliveryTime';

            // お届け時間帯を定義するメソッドが存在する場合
            if (method_exists($tmp_module_obj, $method_name)) {
                // お届け時間帯を取得
                $delivery_time_array = $tmp_module_obj->$method_name();
            }
        }
    }

    // お届け時間帯に関するデータが存在する場合
    if( !empty($delivery_time_array) ){
        // 「お届け時間帯」「お届け日」に関する変数にセット
        $delivery_timing_data['delivery_time_array'] = $delivery_time_array;
    }else{
        // 「お届け時間帯」「お届け日」に関する変数をクリア
        unset($delivery_timing_data['delivery_time_array']);
    }

    // お届け日データを取得
    $date_array = fn_lcjp_get_delivery_date((int)$shipping_id);

    // お届け日データが存在する場合
    if( count($date_array) > 0 ){
        // 「お届け時間帯」「お届け日」に関する変数にセット
        $delivery_timing_data['delivery_date_array'] = $date_array;
    }else{
        // 「お届け時間帯」「お届け日」に関する変数をクリア
        unset($delivery_timing_data['delivery_date_array']);
    }

    return $delivery_timing_data;
}




// 指定された配送方法にお届け日指定設定があるかをチェック
// お届け日設定があれば、お届け日配列を返す
function fn_lcjp_get_delivery_date($shipping_id)
{
    $delivery_setting = db_get_row("SELECT delivery_status, delivery_from, delivery_to, include_holidays FROM ?:jp_delivery_date WHERE shipping_id = ?i", $shipping_id);

    $delivery_date = array();

    if( $delivery_setting['delivery_status'] == '1' ){

        // public_holiday ライブラリをロード
        require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/public_holiday/public_holidays.php');

        // 休業日情報を取得
        $mon = Registry::get('addons.localization_jp.jp_holiday_mon');
        $tue = Registry::get('addons.localization_jp.jp_holiday_tue');
        $wed = Registry::get('addons.localization_jp.jp_holiday_wed');
        $thu = Registry::get('addons.localization_jp.jp_holiday_thu');
        $fri = Registry::get('addons.localization_jp.jp_holiday_fri');
        $sat = Registry::get('addons.localization_jp.jp_holiday_sat');
        $sun = Registry::get('addons.localization_jp.jp_holiday_sun');
        $ph = Registry::get('addons.localization_jp.jp_holiday_ph');
        $sh = Registry::get('addons.localization_jp.jp_shipping_holiday');
        $ss = Registry::get('addons.localization_jp.jp_shipping_sunday');

        $delivery_date[] = __('jp_not_specified');

        // 注文日付
        $today = explode('-', date("Y-m-d"));

        $add_date = (int)$delivery_setting['delivery_from'];

        if( $add_date > 0 ){

            $date_count = 1;

            while($add_date > 0) {

                $work_date_ymd = date("Y-m-d", mktime(0, 0, 0, $today[1], ($today[2] + $date_count), $today[0]));
                $work_date = new HolidayDateTime($work_date_ymd);
                $wod = (int)date_format($work_date, 'w');
                $holiday_name = $work_date->holiday();

                $is_countable = true;

                // 休業日をカウントしない場合
                if( $delivery_setting['include_holidays'] != '1' &&
                    (
                        // 当日が国民の祝日で国民の祝日を休業日とする場合
                        !empty($holiday_name) && $ph == 'Y' ||
                        // 当日が月曜で月曜を休業日とする場合
                        $wod == '1' && $mon == 'Y' ||
                        // 当日が火曜で火曜を休業日とする場合
                        $wod == '2' && $tue == 'Y' ||
                        // 当日が水曜で水曜を休業日とする場合
                        $wod == '3' && $wed == 'Y' ||
                        // 当日が木曜で木曜を休業日とする場合
                        $wod == '4' && $thu == 'Y' ||
                        // 当日が金曜で金曜を休業日とする場合
                        $wod == '5' && $fri == 'Y' ||
                        // 当日が土曜で土曜を休業日とする場合
                        $wod == '6' && $sat == 'Y' ||
                        // 当日が日曜で日曜を休業日とする場合
                        $wod == '0' && $sun == 'Y')
                ){
                    $is_countable = false;
                };

                if( $is_countable ) $add_date--;

                $date_count++;
            }

            $start_date = explode('-', $work_date_ymd);

        }else{
            $start_date = explode('-', date("Y-m-d"));
        }

        $add_count = 0;
        $date_count = 0;
        while($date_count < (int)$delivery_setting['delivery_to']) {
            $delivery_date_ymd = date("Y-m-d", mktime(0, 0, 0, $start_date[1], ($start_date[2] + $add_count), $start_date[0]));
            $dd = explode('-', $delivery_date_ymd);
            $work_date = new HolidayDateTime($delivery_date_ymd);
            $wod = (int)date_format($work_date, 'w');
            $holiday_name = $work_date->holiday();
            // 設定値と祝日、曜日を判定
            if(($sh != 'Y' || ($sh == 'Y' && empty($holiday_name))) && ($ss != 'Y' || ($ss == 'Y' && $wod != 0))) {
                $delivery_date[] = sprintf("%04d年%02d月%02d日", $dd[0], $dd[1], $dd[2]) . '(' . __('weekday_abr_' . date("w", mktime(0, 0, 0, $dd[1], $dd[2], $dd[0]))) . ')';
                $date_count++;
            }

            $add_count++;
        }
    }

    return $delivery_date;
}



////////////////////////////////////////////////////////////////////////
// 注文ステータスの並べ替えに関する処理 BOF
////////////////////////////////////////////////////////////////////////

// 注文ステータスをソート順に並べ替え
function fn_lcjp_sort_order_statuses($statuses)
{
    ////////////////////////////////////////////////////////////
    // ソート順を付加した注文ステータス用配列を作成 BOF
    ////////////////////////////////////////////////////////////
    $statuses_extended = array();

    foreach($statuses as $key => $status){
        $sort_id = (int)db_get_field("SELECT sort_id FROM ?:jp_order_status_sort WHERE status = ?s", $key);
        $statuses_extended[$key]['status'] = $status;
        $statuses_extended[$key]['sort_id'] = $sort_id;
    }
    ////////////////////////////////////////////////////////////
    // ソート順を付加した注文ステータス用配列を作成 EOF
    ////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////
    // 注文ステータス用配列を並べ替え BOF
    ////////////////////////////////////////////////////////////
    // ソート順情報を含む注文ステータスに関する配列が存在する場合
    if( !empty($statuses_extended) ){

        // ソート順情報を含む注文ステータスに関する配列をソート順に並べ替え
        uasort($statuses_extended, 'fn_lcjp_sort_order_compare');

        // ソート済注文ステータス用配列を作成
        $sorted_statuses = array();

        foreach($statuses_extended as $key2 => $status2){
            $sorted_statuses[$key2] = $status2['status'];
        }

        // ソート済注文ステータス用配列が空でない場合
        if( !empty($sorted_statuses) ){
            // 注文ステータス用配列をソート済注文ステータス用配列に入れ替え
            $statuses = $sorted_statuses;
        }
    }
    ////////////////////////////////////////////////////////////
    // 注文ステータス用配列を並べ替え EOF
    ////////////////////////////////////////////////////////////

    return $statuses;
}




// 注文ステータスのソート用コールバック関数
function fn_lcjp_sort_order_compare($val1, $val2)
{
    if( $val1['sort_id'] == $val2['sort_id'] ){
        return 0;
    }elseif( $val1['sort_id'] < $val2['sort_id'] ){
        return -1;
    }else{
        return 1;
    }
}
////////////////////////////////////////////////////////////////////////
// 注文ステータスの並べ替えに関する処理 EOF
////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////
// ルミーズのペイクイック（登録済みカード決済）に関する関数 START
////////////////////////////////////////////////////////////////////
// ルミーズのペイクイック（登録済みカード決済）に関する情報を取得
function fn_lcjp_get_payquick_info($user_id)
{
    $payquick_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id =?i AND payment_method =?s", $user_id, 'remise_cc');

    if( !empty($payquick_id) ){
        return $payquick_id;
    }else{
        return false;
    }
}




// ルミーズのペイクイック（登録済みカード決済）に関する情報を削除
function fn_lcjp_delete_remise_card_info($user_id)
{
    // 登録済みカード決済用レコードを削除
    db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'remise_cc');

    fn_set_notification('N', __('notice'), __('jp_remise_payquick_delete_success'));
}
////////////////////////////////////////////////////////////////////
// ルミーズのペイクイック（登録済みカード決済）に関する関数 END
////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////
// 送料計算に関する処理 BOF
////////////////////////////////////////////////////////////////////////

// 日本国内の配送業者の送料を取得
function fn_lcjp_get_shipping_rate($shipping_service_info, $shipping_weight)
{
    // 配送先がない場合は終了
    if(!$shipping_service_info['destination']){
        return false;
    }

    // 配送元のゾーンIDを取得
    $zone_id = db_get_field("SELECT zone_id FROM ?:jp_carrier_zones WHERE carrier_code = ?s AND zone_code = ?s", $shipping_service_info['carrier_code'], $shipping_service_info['origination']);

    /////////////////////////////////////////////////////////////////
    // 送料を取得するショップ（出品者）のIDを抽出条件に加える BOF
    /////////////////////////////////////////////////////////////////
    // マーケットプレイス版で各出品者が個別に料金を管理する配送方法の場合
    if( fn_allowed_for('MULTIVENDOR') && !empty($shipping_service_info['company_id']) ){
        $_condition = fn_get_company_condition('?:jp_shipping_rates.company_id', true, $shipping_service_info['company_id'], false, true);
    // その他の場合
    }else{
        $_condition = fn_get_company_condition('?:jp_shipping_rates.company_id');
    }
    /////////////////////////////////////////////////////////////////
    // 送料を取得するショップ（出品者）のIDを抽出条件に加える EOF
    /////////////////////////////////////////////////////////////////

    // 配送元に基づく各地域への配送料金データを取得
    $rates_array = db_get_field("SELECT shipping_rates FROM ?:jp_shipping_rates WHERE carrier_code = ?s AND service_code = ?s AND zone_id = ?s ?p", $shipping_service_info['carrier_code'], $shipping_service_info['service_code'], $zone_id, $_condition);

    $rates_array = unserialize($rates_array);

    ksort($rates_array);

    // ショップで設定した重量単位をKgに換算する
    $kg_weight = round($shipping_weight * (Registry::get('settings.General.weight_symbol_grams') / 1000), 3);

    foreach($rates_array as $key => $val){
        if( $kg_weight <= (float)$val['weight'] ){
            return (int)$val['rates'][$shipping_service_info['destination']];
            break;
        }
    }

    return false;
}




// 配送方法IDから出品者IDを取得
function fn_lcjp_get_company_id_by_shipping_id($shipping_id)
{
    $company_id = '';

    // マーケットプレイス版の場合
    if (fn_allowed_for('MULTIVENDOR')) {
        // 配送方法に紐付けられた出品者IDを取得
        $company_id = db_get_field("SELECT company_id FROM ?:shippings WHERE shipping_id = ?i", $shipping_id);

        // 配送方法に紐付けられた出品者IDが存在する場合、その出品者IDを返す
        if(!empty($company_id)){
            return $company_id;
        // 配送方法に紐付けられた出品者IDが存在する場合、その出品者IDを返す
        }else{
            // 空の値を返す
            $company_id = '';
        }
    }

    return $company_id;
}
////////////////////////////////////////////////////////////////////////
// 送料計算に関する処理 EOF
////////////////////////////////////////////////////////////////////////




// 注文の配送情報に紐付けられたお届け時間帯およびお届け日の情報を取得
function fn_lcjp_get_order_delivery_info($order_id, $shipping_id, $group_key)
{
    $order_delivery_info = db_get_row("SELECT delivery_date, delivery_timing FROM ?:jp_order_delivery_info WHERE order_id = ?i AND shipping_id = ?i AND group_key = ?i", $order_id, $shipping_id, $group_key);

    if( !empty($order_delivery_info) ){
        return $order_delivery_info;
    }else{
        return false;
    }
}




// 添付ファイルおよびHTML中にインラインで表示する画像の処理
function fn_lcjp_attach_images($body, $attachments, &$mail)
{
    // 添付ファイルの情報を格納する配列を初期化
    $attach = array();

    // 通常の添付ファイルに関する情報を配列に格納
    if ( !empty($attachments) ) {
        foreach($attachments as $name => $file){
            $attach[] = array(
                'PATH' => $file,
                'NAME' => $name,
                'CONTENT-TYPE'=>'application/octet-stream'
            );
        }
    }

    ///////////////////////////////////////////////////////
    // インラインHTMLに埋め込む画像を配列に格納 BOF
    ///////////////////////////////////////////////////////
    $http_location = Registry::get('config.http_location');
    $https_location = Registry::get('config.https_location');
    $http_path = Registry::get('config.http_path');
    $https_path = Registry::get('config.https_path');

    $files = array();
    if( preg_match_all("/(?<=\ssrc=|\sbackground=)('|\")(.*)\\1/SsUi", $body, $matches) ){
        $files = fn_array_merge($files, $matches[2], false);
    }
    if( preg_match_all("/(?<=\sstyle=)('|\").*url\(('|\"|\\\\\\1)(.*)\\2\).*\\1/SsUi", $body, $matches) ){
        $files = fn_array_merge($files, $matches[3], false);
    }

    // インラインHTMLに埋め込むファイルが存在しない場合
    if( empty($files) ){
        // 添付ファイルが存在すれば添付する
        if( !empty($attach) ){
            $mail -> attach($attach);
        }
        return $body;

    // インラインHTMLに埋め込むファイルが存在する場合
    }else{
        $files = array_unique($files);
        foreach($files as $k => $_path){
            $cid = 'csimg'.$k;
            $path = str_replace('&amp;', '&', $_path);

            $real_path = '';
            // Replace url path with filesystem if this url is NOT dynamic
            if( strpos($path, '?') === false && strpos($path, '&') === false ){
                if($i = (strpos($path, $http_location)) !== false ){
                    $real_path = substr_replace($path, Registry::get('config.dir.root') , $i, strlen($http_location));
                }elseif( ($i = strpos($path, $https_location)) !== false ){
                    $real_path = substr_replace($path, Registry::get('config.dir.root') , $i, strlen($https_location));
                }elseif( !empty($http_path) && ($i = strpos($path, $http_path)) !== false ){
                    $real_path = substr_replace($path, Registry::get('config.dir.root') , $i, strlen($http_path));
                }elseif( !empty($https_path) && ($i = strpos($path, $https_path)) !== false ){
                    $real_path = substr_replace($path, Registry::get('config.dir.root') , $i, strlen($https_path));
                }
            }

            if( empty($real_path) ){
                if( strpos($path, '://') === false ){
                    $real_path = Registry::get('config.dir.root') . '/' . $path;
                }else{
                    $real_path = '';
                }
            }

            list($width, $height, $mime_type) = fn_get_image_size($real_path);

            if( !empty($width) ){
                $cid .= '.' . fn_get_image_extension($mime_type);

                $attach[] = array( 'PATH' =>$real_path , 'NAME' => $cid, 'CONTENT-ID'=> $cid );

                $body = preg_replace("/(['\"])" . str_replace("/", "\/", preg_quote($_path)) . "(['\"])/Ss", "\\1cid:" . $cid . "\\2", $body);
            }
        }
    }
    ///////////////////////////////////////////////////////
    // インラインHTMLに埋め込む画像を配列に格納 EOF
    ///////////////////////////////////////////////////////

    // 添付ファイルが存在すれば添付する
    if( !empty($attach) ){
        // インラインモード指定
        $mail->inlineMode(true);

        // cidを固定化
        $mail->content_id_fix = true;

        // ファイルを添付
        $mail->attach($attach);
    }

    return $body;
}




// "fn_date_format" に言語コードを指定する機能を追加した関数
// 日本語利用時に "RSSフィード" アドオンの "pubDate" が正しく表示されないバグを修正するために利用
function fn_lcjp_date_format_en($timestamp, $format = '%b %e, %Y', $lang_code = CART_LANGUAGE)
{
    if (substr(PHP_OS,0,3) == 'WIN') {
        $hours = strftime('%I', $timestamp);
        $short_hours = ($hours < 10) ? substr($hours, -1) : $hours;
        $_win_from = array ('%e', '%T', '%D', '%l');
        $_win_to = array ('%d', '%H:%M:%S', '%m/%d/%y', $short_hours);
        $format = str_replace($_win_from, $_win_to, $format);
    }

    $date = getdate($timestamp);
    $m = $date['mon'];
    $d = $date['mday'];
    $y = $date['year'];
    $w = $date['wday'];
    $hr = $date['hours'];
    $pm = ($hr >= 12);
    $ir = ($pm) ? ($hr - 12) : $hr;
    $dy = $date['yday'];
    $fd = getdate(mktime(0, 0, 0, 1, 1, $y)); // first day of year
    $wn = (int) (($dy + $fd['wday']) / 7);
    if ($ir == 0) {
        $ir = 12;
    }
    $min = $date['minutes'];
    $sec = $date['seconds'];

    // Preload language variables if needed
    $preload = array();
    if (strpos($format, '%a') !== false) {
        $preload[] = 'weekday_abr_' . $w;
    }
    if (strpos($format, '%A') !== false) {
        $preload[] = 'weekday_' . $w;
    }

    if (strpos($format, '%b') !== false) {
        $preload[] = 'month_name_abr_' . $m;
    }

    if (strpos($format, '%B') !== false) {
        $preload[] = 'month_name_' . $m;
    }

    fn_preload_lang_vars($preload);

    $s['%a'] = __('weekday_abr_'. $w, $lang_code); // abbreviated weekday name
    $s['%A'] = __('weekday_'. $w, $lang_code); // full weekday name
    $s['%b'] = __('month_name_abr_' . $m, $lang_code); // abbreviated month name
    $s['%B'] = __('month_name_' . $m, $lang_code); // full month name
    $s['%c'] = ''; // !!!FIXME: preferred date and time representation for the current locale
    $s['%C'] = 1 + floor($y / 100); // the century number
    $s['%d'] = ($d < 10) ? ('0' . $d) : $d; // the day of the month (range 01 to 31)
    $s['%e'] = $d; // the day of the month (range 1 to 31)
    $s['%d'] = $s['%b'];
    $s['%H'] = ($hr < 10) ? ('0' . $hr) : $hr; // hour, range 00 to 23 (24h format)
    $s['%I'] = ($ir < 10) ? ('0' . $ir) : $ir; // hour, range 01 to 12 (12h format)
    $s['%j'] = ($dy < 100) ? (($dy < 10) ? ('00' . $dy) : ('0' . $dy)) : $dy; // day of the year (range 001 to 366)
    $s['%k'] = $hr; // hour, range 0 to 23 (24h format)
    $s['%l'] = $ir; // hour, range 1 to 12 (12h format)
    $s['%m'] = ($m < 10) ? ('0' . $m) : $m; // month, range 01 to 12
    $s['%M'] = ($min < 10) ? ('0' . $min) : $min; // minute, range 00 to 59
    $s['%n'] = "\n"; // a newline character
    $s['%p'] = $pm ? 'PM' : 'AM';
    $s['%P'] = $pm ? 'pm' : 'am';
    $s['%s'] = floor($timestamp / 1000);
    $s['%S'] = ($sec < 10) ? ('0' . $sec) : $sec; // seconds, range 00 to 59
    $s['%t'] = "\t"; // a tab character
    $s['%T'] = $s['%H'] .':'. $s['%M'] .':'. $s['%S'];
    $s['%U'] = $s['%W'] = $s['%V'] = ($wn < 10) ? ('0' . $wn) : $wn;
    $s['%u'] = $w + 1;  // the day of the week (range 1 to 7, 1 = MON)
    $s['%w'] = $w; // the day of the week (range 0 to 6, 0 = SUN)
    $s['%y'] = substr($y, 2, 2); // year without the century (range 00 to 99)
    $s['%Y'] = $y; // year with the century
    $s['%%'] = '%'; // a literal '%' character
    $s['%D'] = $s['%m'] .'/'. $s['%d'] .'/'. $s['%y']; // american date style: %m/%d/%y
    // FIXME: %x : preferred date representation for the current locale without the time
    // FIXME: %X : preferred time representation for the current locale without the date
    // FIXME: %G, %g (man strftime)
    // FIXME: %r : the time in am/pm notation %I:%M:%S %p
    // FIXME: %R : the time in 24-hour notation %H:%M
    return preg_replace_callback("/(%.)/", function($m) use ($s) {
        if (isset($s[$m[1]])) {
            return $s[$m[1]];
        } else {
            return false;
        }
    }, $format);
}




// 指定したフィールドが姓・名カナフィールドであるか判定
function fn_lcjp_is_kana_field($field_id)
{
    $field_class = db_get_field("SELECT class FROM ?:profile_fields WHERE field_id = ?i", $field_id);

    if($field_class == 'first-name-kana' || $field_class == 'last-name-kana'){
        return true;
    }else{
        return false;
    }
}




// 注文情報の書き込み対象となる注文ID群を取得
function fn_lcjp_get_order_ids_to_process($order_id)
{
    // 処理対象となる注文ID群を格納する配列を初期化
    $order_ids_to_process = array();

    // マーケットプレイス版の場合
    if (fn_allowed_for('MULTIVENDOR')) {
        // 複数の出品者の商品が混在した注文かどうかを判定
        $is_parent_order = db_get_field('SELECT is_parent_order FROM ?:orders WHERE order_id=?i', $order_id);

        // 複数の出品者の商品が混在した注文の場合
        if($is_parent_order == 'Y'){
            // 子注文を抽出
            $child_orders = db_get_hash_array("SELECT order_id FROM ?:orders WHERE parent_order_id = ?i", 'order_id', $order_id);

            // 子注文が存在する場合
            if(!empty($child_orders)){
                foreach($child_orders as $child_order_id => $child_order_data){
                    // 支払情報の更新対象となる子注文IDをセット
                    $order_ids_to_process[] = $child_order_id;
                }
            // 子注文が存在しない場合
            }else{
                // 注文IDをセット
                $order_ids_to_process[] = $order_id;
            }

        // 単一の出品者の商品の注文の場合
        }else{
            // 注文IDをセット
            $order_ids_to_process[] = $order_id;
        }

    // 通常版の場合
    }else{
        // 注文IDをセット
        $order_ids_to_process[] = $order_id;
    }

    return $order_ids_to_process;
}




// 注文IDからショップIDを取得し、company_idをfn_urlのリダイレクトパラメータに追加
function fn_lcjp_get_company_query_from_order($order_id)
{
    // マーケットプレイス版の場合は空データを返す
    if(PRODUCT_EDITION == 'MULTIVENDOR') return "";

    // 登録されたショップ数が1つの場合は空データを返す
    $storefront_count = db_get_field("SELECT COUNT(*) FROM ?:companies");
    if($storefront_count < 2) return "";

    // 注文IDからショップIDを取得
    $company_id = db_get_field("SELECT company_id FROM ?:orders WHERE order_id = ?i", $order_id);

    // ショップIDが存在しない場合は空データを取得
    if(empty($company_id)) return "";

    // ショップIDをfn_urlのリダイレクトパラメータとして返す
    return "&amp;company_id=" . $company_id;
}




/**
 * fn_set_notification関数を利用して配列の内容を表示
 *
 * @param $params
 * @return bool
 */
function fn_lcjp_dev_notify($params)
{
    if( empty($params)) {
        return false;
    }elseif( !is_array($params) ){
        fn_set_notification('E', __('error'), $params);
    }else{
        fn_set_notification('E', __('error'), '<pre>' . print_r($params, true) . '</pre>');
    }
}




/**
 * エラー発生時のリダイレクト先URLを取得
 * @return string
 */
function fn_lcjp_get_error_return_url()
{
    if(AREA == 'A'){
        $return_url = fn_url("order_management.update", 'A', 'current');
    }else{
        $return_url = fn_url("checkout.checkout&edit_step=step_four", 'C', 'current');
    }
    return $return_url;
}




/**
 * 決済代行サービスなどに送信するCS-Cartへの戻りURLをセット
 * 【メモ】
 * 複数ショップ運用時に決済代行サービスに対して 'jp_extras' ディレクトリへの
 * 戻りURLを指定する場合、fn_url関数などを利用すると存在しないディレクトリが
 * 指定されることがある。そのような事態を避けるためにCS-CartがインストールされたURL
 * を元に戻りURLをセットする
 *
 * @param string $destination
 * @return string
 */
function fn_lcjp_get_return_url($destination = '')
{
    // マーケットプレイス版の場合
    if( fn_allowed_for('MULTIVENDOR') ){
        if( Registry::get('settings.Security.secure_storefront') != 'none' ){
            $return_url = 'https://' . Registry::get('config.https_host') . Registry::get('config.https_path');
        }else{
            $return_url = 'http://' . Registry::get('config.http_host') . Registry::get('config.http_path');
        }
    // 通常版の場合
    }else{
        if( Registry::get('settings.Security.secure_storefront') != 'none' ){
            $base_url = Registry::get('config.jp_return_url_from_outside_https');
            if( !empty($base_url) ){
                $return_url = 'https://' . $base_url;
            }else{
                $return_url = Registry::get('config.https_location');
            }
        }else{
            $base_url = Registry::get('config.jp_return_url_from_outside_http');
            if( !empty($base_url) ){
                $return_url = 'http://' . $base_url;
            }else{
                $return_url = Registry::get('config.http_location');
            }
        }
    }

    return $return_url . $destination;
}




/**
 * 注文IDから支払方法IDに紐付けられた決済代行サービスのスクリプトファイル名を取得
 *
 * @param $order_id
 * @return bool
 */
function fn_lcjp_get_processor_script_name_by_order_id($order_id)
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




/**
 * 決済代行サービスのスクリプトファイル名から processor_id を取得
 *
 * @param $script_name
 * @return array|bool
 */
function fn_lcjp_get_processor_id_by_script_name($script_name)
{
    $processor_id = db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s", $script_name);

    if( !empty($processor_id) ){
        return $processor_id;
    }else{
        return false;
    }
}




/**
 * 機種依存文字(Platform Dependant Characters)をJIS基本漢字（JIS X 0208）に変換
 * 115～118区はそれ以前のキーと重複するとphpStormで判定されるので割愛
 * 参考URL : http://narucissus.blogspot.sg/2008/09/jisjis-x-0208.html
 *
 * @param $str
 */
function fn_lcjp_replace_pdc($str)
{
    $map = array(
        //13区
        '①' => '１',
        '②' => '２',
        '③' => '３',
        '④' => '４',
        '⑤' => '５',
        '⑥' => '６',
        '⑦' => '７',
        '⑧' => '８',
        '⑨' => '９',
        '⑩' => '10',
        '⑪' => '11',
        '⑫' => '12',
        '⑬' => '13',
        '⑭' => '14',
        '⑮' => '15',
        '⑯' => '16',
        '⑰' => '17',
        '⑱' => '18',
        '⑲' => '19',
        '⑳' => '20',
        'Ⅰ' => 'I',
        'Ⅱ' => 'II',
        'Ⅲ' => 'III',
        'Ⅳ' => 'IV',
        'Ⅴ' => 'V',
        'Ⅵ' => 'VI',
        'Ⅶ' => 'VII',
        'Ⅷ' => 'VIII',
        'Ⅸ' => 'IX',
        'Ⅹ' => 'X',
        '㍉' => 'ミリ',
        '㌔' => 'キロ',
        '㌢' => 'センチ',
        '㍍' => 'メートル',
        '㌘' => 'グラム',
        '㌧' => 'トン',
        '㌃' => 'アール',
        '㌶' => 'ヘクタール',
        '㍑' => 'リットル',
        '㍗' => 'ワット',
        '㌍' => 'カロリー',
        '㌦' => 'ドル',
        '㌣' => 'セント',
        '㌫' => 'パーセント',
        '㍊' => 'ミリバール',
        '㌻' => 'ページ',
        '㎜' => 'mm',
        '㎝' => 'cm',
        '㎞' => 'km',
        '㎎' => 'mg',
        '㎏' => 'kg',
        '㏄' => 'cc',
        '㎡' => 'm2',
        '㍻' => '平成',
        '〝' => '"',
        '〟' => '"',
        '№' => 'No.',
        '㏍' => 'KK',
        '℡' => 'TEL',
        '㊤' => '上',
        '㊥' => '中',
        '㊦' => '下',
        '㊧' => '左',
        '㊨' => '右',
        '㈱' => '(株)',
        '㈲' => '(有)',
        '㈹' => '(代)',
        '㍾' => '明治',
        '㍽' => '大正',
        '㍼' => '昭和',
        '≒' => '□',
        '≡' => '□',
        '∫' => '□',
        '∮' => '□',
        '∑' => '□',
        '√' => '□',
        '⊥' => '□',
        '∠' => '□',
        '∟' => '□',
        '⊿' => '□',
        '∵' => '□',
        '∩' => '□',
        '∪' => '□',

        //89区
        '纊' => '□',
        '褜' => '□',
        '鍈' => '□',
        '銈' => '□',
        '蓜' => '□',
        '俉' => '□',
        '炻' => '□',
        '昱' => '□',
        '棈' => '□',
        '鋹' => '□',
        '曻' => '□',
        '彅' => '□',
        '丨' => '□',
        '仡' => '□',
        '仼' => '□',
        '伀' => '□',
        '伃' => '□',
        '伹' => '□',
        '佖' => '□',
        '侒' => '□',
        '侊' => '□',
        '侚' => '□',
        '侔' => '□',
        '俍' => '□',
        '偀' => '□',
        '倢' => '□',
        '俿' => '□',
        '倞' => '□',
        '偆' => '□',
        '偰' => '□',
        '偂' => '□',
        '傔' => '□',
        '僴' => '□',
        '僘' => '□',
        '兊' => '□',
        '兤' => '□',
        '冝' => '□',
        '冾' => '□',
        '凬' => '□',
        '刕' => '□',
        '劜' => '□',
        '劦' => '□',
        '勀' => '□',
        '勛' => '□',
        '匀' => '□',
        '匇' => '□',
        '匤' => '□',
        '卲' => '□',
        '厓' => '□',
        '厲' => '□',
        '叝' => '□',
        '﨎' => '□',
        '咜' => '□',
        '咊' => '□',
        '咩' => '□',
        '哿' => '□',
        '喆' => '□',
        '坙' => '□',
        '坥' => '□',
        '垬' => '□',
        '埈' => '□',
        '埇' => '□',
        '﨏' => '□',
        '塚' => '□',
        '增' => '□',
        '墲' => '□',
        '夋' => '□',
        '奓' => '□',
        '奛' => '□',
        '奝' => '□',
        '奣' => '□',
        '妤' => '□',
        '妺' => '□',
        '孖' => '□',
        '寀' => '□',
        '甯' => '□',
        '寘' => '□',
        '寬' => '□',
        '尞' => '□',
        '岦' => '□',
        '岺' => '□',
        '峵' => '□',
        '崧' => '□',
        '嵓' => '□',
        '﨑' => '崎',
        '嵂' => '□',
        '嵭' => '□',
        '嶸' => '□',
        '嶹' => '□',
        '巐' => '□',
        '弡' => '□',
        '弴' => '□',
        '彧' => '□',
        '德' => '□',

        //90区
        '忞' => '□',
        '恝' => '□',
        '悅' => '□',
        '悊' => '□',
        '惞' => '□',
        '惕' => '□',
        '愠' => '□',
        '惲' => '□',
        '愑' => '□',
        '愷' => '□',
        '愰' => '□',
        '憘' => '□',
        '戓' => '□',
        '抦' => '□',
        '揵' => '□',
        '摠' => '□',
        '撝' => '□',
        '擎' => '□',
        '敎' => '□',
        '昀' => '□',
        '昕' => '□',
        '昻' => '□',
        '昉' => '□',
        '昮' => '□',
        '昞' => '□',
        '昤' => '□',
        '晥' => '□',
        '晗' => '□',
        '晙' => '□',
        '晴' => '□',
        '晳' => '□',
        '暙' => '□',
        '暠' => '□',
        '暲' => '□',
        '暿' => '□',
        '曺' => '□',
        '朎' => '□',
        '朗' => '□',
        '杦' => '□',
        '枻' => '□',
        '桒' => '□',
        '柀' => '□',
        '栁' => '□',
        '桄' => '□',
        '棏' => '□',
        '﨓' => '□',
        '楨' => '□',
        '﨔' => '□',
        '榘' => '□',
        '槢' => '□',
        '樰' => '□',
        '橫' => '□',
        '橆' => '□',
        '橳' => '□',
        '橾' => '□',
        '櫢' => '□',
        '櫤' => '□',
        '毖' => '□',
        '氿' => '□',
        '汜' => '□',
        '沆' => '□',
        '汯' => '□',
        '泚' => '□',
        '洄' => '□',
        '涇' => '□',
        '浯' => '□',
        '涖' => '□',
        '涬' => '□',
        '淏' => '□',
        '淸' => '□',
        '淲' => '□',
        '淼' => '□',
        '渹' => '□',
        '湜' => '□',
        '渧' => '□',
        '渼' => '□',
        '溿' => '□',
        '澈' => '□',
        '澵' => '□',
        '濵' => '□',
        '瀅' => '□',
        '瀇' => '□',
        '瀨' => '□',
        '炅' => '□',
        '炫' => '□',
        '焏' => '□',
        '焄' => '□',
        '煜' => '□',
        '煆' => '□',
        '煇' => '□',
        '凞' => '□',
        '燁' => '□',
        '燾' => '□',
        '犱' => '□',

        //91区
        '犾' => '□',
        '猤' => '□',
        '猪' => '□',
        '獷' => '□',
        '玽' => '□',
        '珉' => '□',
        '珖' => '□',
        '珣' => '□',
        '珒' => '□',
        '琇' => '□',
        '珵' => '□',
        '琦' => '□',
        '琪' => '□',
        '琩' => '□',
        '琮' => '□',
        '瑢' => '□',
        '璉' => '□',
        '璟' => '□',
        '甁' => '□',
        '畯' => '□',
        '皂' => '□',
        '皜' => '□',
        '皞' => '□',
        '皛' => '□',
        '皦' => '□',
        '益' => '□',
        '睆' => '□',
        '劯' => '□',
        '砡' => '□',
        '硎' => '□',
        '硤' => '□',
        '硺' => '□',
        '礰' => '□',
        '礼' => '□',
        '神' => '□',
        '祥' => '□',
        '禔' => '□',
        '福' => '□',
        '禛' => '□',
        '竑' => '□',
        '竧' => '□',
        '靖' => '□',
        '竫' => '□',
        '箞' => '□',
        '精' => '□',
        '絈' => '□',
        '絜' => '□',
        '綷' => '□',
        '綠' => '□',
        '緖' => '□',
        '繒' => '□',
        '罇' => '□',
        '羡' => '□',
        '羽' => '□',
        '茁' => '□',
        '荢' => '□',
        '荿' => '□',
        '菇' => '□',
        '菶' => '□',
        '葈' => '□',
        '蒴' => '□',
        '蕓' => '□',
        '蕙' => '□',
        '蕫' => '□',
        '﨟' => '□',
        '薰' => '□',
        '蘒' => '□',
        '﨡' => '□',
        '蠇' => '□',
        '裵' => '□',
        '訒' => '□',
        '訷' => '□',
        '詹' => '□',
        '誧' => '□',
        '誾' => '□',
        '諟' => '□',
        '諸' => '□',
        '諶' => '□',
        '譓' => '□',
        '譿' => '□',
        '賰' => '□',
        '賴' => '□',
        '贒' => '□',
        '赶' => '□',
        '﨣' => '□',
        '軏' => '□',
        '﨤' => '□',
        '逸' => '□',
        '遧' => '□',
        '郞' => '□',
        '都' => '□',
        '鄕' => '□',
        '鄧' => '□',
        '釚' => '□',

        //92区
        '釗' => '□',
        '釞' => '□',
        '釭' => '□',
        '釮' => '□',
        '釤' => '□',
        '釥' => '□',
        '鈆' => '□',
        '鈐' => '□',
        '鈊' => '□',
        '鈺' => '□',
        '鉀' => '□',
        '鈼' => '□',
        '鉎' => '□',
        '鉙' => '□',
        '鉑' => '□',
        '鈹' => '□',
        '鉧' => '□',
        '銧' => '□',
        '鉷' => '□',
        '鉸' => '□',
        '鋧' => '□',
        '鋗' => '□',
        '鋙' => '□',
        '鋐' => '□',
        '﨧' => '□',
        '鋕' => '□',
        '鋠' => '□',
        '鋓' => '□',
        '錥' => '□',
        '錡' => '□',
        '鋻' => '□',
        '﨨' => '□',
        '錞' => '□',
        '鋿' => '□',
        '錝' => '□',
        '錂' => '□',
        '鍰' => '□',
        '鍗' => '□',
        '鎤' => '□',
        '鏆' => '□',
        '鏞' => '□',
        '鏸' => '□',
        '鐱' => '□',
        '鑅' => '□',
        '鑈' => '□',
        '閒' => '□',
        '隆' => '□',
        '﨩' => '□',
        '隝' => '□',
        '隯' => '□',
        '霳' => '□',
        '霻' => '□',
        '靃' => '□',
        '靍' => '□',
        '靏' => '□',
        '靑' => '□',
        '靕' => '□',
        '顗' => '□',
        '顥' => '□',
        '飯' => '□',
        '飼' => '□',
        '餧' => '□',
        '館' => '□',
        '馞' => '□',
        '驎' => '□',
        '髙' => '高',
        '髜' => '□',
        '魵' => '□',
        '魲' => '□',
        '鮏' => '□',
        '鮱' => '□',
        '鮻' => '□',
        '鰀' => '□',
        '鵰' => '□',
        '鵫' => '□',
        '鶴' => '□',
        '鸙' => '□',
        '黑' => '□',
        'ⅰ' => 'i',
        'ⅱ' => 'ii',
        'ⅲ' => 'iii',
        'ⅳ' => 'iv',
        'ⅴ' => 'v',
        'ⅵ' => 'vi',
        'ⅶ' => 'vii',
        'ⅷ' => 'viii',
        'ⅸ' => 'ix',
        'ⅹ' => 'x',
        '￢' => '□',
        '￤' => '□',
        '＇' => '□',
        '＂' => '□',

        // 特殊文字
        '©' => 'C',
        '®' => 'R',
        '™' => 'TM',
    );

    $str = strtr($str, $map);

    return $str;
}




/**
 *  クレジットカード情報を登録済みの会員に対してのみ登録済みカード決済を表示
 *
 * @param $payments
 * @param $tmp_name
 * @param $method_name
 */
function fn_lcjp_filter_payments(&$payments, $tmp_name, $method_name)
{
    // ショップフロント、または管理画面における注文内容の編集の場合
    if( (AREA == 'C') || (AREA == 'A' && Registry::get('runtime.controller') == 'order_management' && Registry::get('runtime.mode') == 'update') ){

        // ユーザーIDを初期化
        $user_id = 0;

        // ショップフロントの場合
        if(AREA == 'C'){
            // ユーザーIDを取得
            if( !empty(Tygh::$app['session']['auth']['user_id']) ) $user_id = Tygh::$app['session']['auth']['user_id'];
        }else{
            if( !empty(Tygh::$app['session']['cart']['user_data']['user_id']) ) $user_id = Tygh::$app['session']['cart']['user_data']['user_id'];
        }

        // 選択可能な支払方法が存在する場合
        if( !empty($payments) ){
            foreach($payments as $key => $val){
                // 決済代行サービスIDを取得
                $processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $val['payment_id']);

                // 各決済代行サービスにひもづけられた設定用テンプレート名を取得
                $template = db_get_field("SELECT admin_template FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

                // テンプレート名が登録済カードによる支払に関するものである場合
                if( !empty($template) && $template == $tmp_name ) {
                    // カード情報が登録されていない場合
                    $quickpay_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, $method_name);

                    // 選択可能な支払方法から除外
                    if( empty($quickpay_id) ){
                        unset($payments[$key]);
                    }
                }
            }
        }
    }
}




/**
 * 各決済代行サービスで使用するスクリプトファイル名から決済代行サービスID（processor_id)を取得
 *
 * @param $processor_scripts
 * @return array
 */
function fn_lcjp_get_processor_ids($processor_scripts)
{
    // 決済代行サービスIDを格納する配列を初期化
    $processor_ids = array();

    // 各決済代行サービスで使用するスクリプトファイル名が配列で指定されている場合
    if( !empty($processor_scripts) || is_array($processor_scripts)){
        // 指定されたすべての決済代行サービスのID（processor_id)を取得
        foreach($processor_scripts as $processor_script){
            $processor_id = db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s", $processor_script);
            if( !empty($processor_id) ) $processor_ids[] = $processor_id;
        }
    }

    // 決済代行サービスのID（processor_id)を返す
    return $processor_ids;
}



//////////////////////////////////////////////////////////
// マーケットプレイスエディション固有の関数 START
//////////////////////////////////////////////////////////

// 指定した言語変数を他の言語変数に変換
function fn_lcjp_mve_convert_lang_var($lang_var, $disp_location = '')
{
    // 出品者指定ポップアップの表記について、管理画面ヘッダー部分のみ「すべての出品者」を「マーケットプレイス管理モード」に変更
    if( fn_allowed_for('MULTIVENDOR') && $lang_var == __('all_vendors') && $disp_location == 'top_company_id' && AREA == 'A'){
        $lang_var = __('jp_marketplace_admin_mode');
    }

    return $lang_var;
}
//////////////////////////////////////////////////////////
// マーケットプレイスエディション固有の関数 END
//////////////////////////////////////////////////////////




//////////////////////////////////////////////////////////
// PDF納品書に関する関数 START
//////////////////////////////////////////////////////////

// 顧客情報の追加フィールド項目を取得
function fn_lcjp_get_add_profile_fields($section, $type)
{
    // 指定無し
    $variants = array('0' => __('jp_pdfinv_not_assigned'));

    // ユーザー追加フィールドのID、名称を取得
    $extra_fields = db_get_array("SELECT ?:profile_fields.field_id, ?:profile_field_descriptions.description FROM ?:profile_fields LEFT JOIN ?:profile_field_descriptions ON ?:profile_fields.field_id = ?:profile_field_descriptions.object_id WHERE ?:profile_fields.class like '%_kana' AND ?:profile_fields.section = ?s  AND ?:profile_fields.field_type = ?s AND ?:profile_field_descriptions.lang_code = ?s AND ?:profile_field_descriptions.object_type = ?s", $section, $type, DESCR_SL, 'F');

    if ($extra_fields) {
        foreach($extra_fields as $fields){
            $variants[$fields['field_id']] = $fields['description'];
        }
    }
    return $variants;
}




// PDF納品書出力
function fn_lcjp_print_pdf_invoice($order_ids)
{
    //////////////////////////////////////////////
    // PDF出力ライブラリの設定 BOF
    //////////////////////////////////////////////
    require(Registry::get('config.dir.addons') . 'localization_jp/lib/tcpdf/config/lang/jpn.php');
    require(Registry::get('config.dir.addons') . 'localization_jp/lib/tcpdf/tcpdf.php');
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(5, 7, 5);
    $pdf->SetAutoPageBreak(false);
    $pdf->setJPEGQuality(75);
    $base_line_width = 0.201083333333;
    //////////////////////////////////////////////
    // PDF出力ライブラリの設定 EOF
    //////////////////////////////////////////////

    // 1注文分のみの出力の場合、引数に含まれる注文IDを配列にセット
    if( !is_array($order_ids) ){
        $temp_order_id = $order_ids;
        $order_ids = array();
        $order_ids[] = $temp_order_id;
    }

    // 注文バーコードの設定情報を取得
    $barcode_settings = Registry::get('addons.barcode');

    // ショップ情報
    $out_shop_data = array();

    // ショップ名
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_name')) != '' ){
        $out_shop_data[] = Registry::get('addons.localization_jp.jp_pdfinv_shop_name');
    }
    // ショップ郵便番号
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_zipcode')) != '' ) {
        $out_shop_data[] = __('jp_pdfinv_zip_title') . ' ' . Registry::get('addons.localization_jp.jp_pdfinv_shop_zipcode');
    }
    // ショップ住所上段
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_address_up')) != '' ) {
        $out_shop_data[] = Registry::get('addons.localization_jp.jp_pdfinv_shop_address_up');
    }
    // ショップ住所下段
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_address_dw')) != '' ) {
        $out_shop_data[] = Registry::get('addons.localization_jp.jp_pdfinv_shop_address_dw');
    }
    // ショップ担当者
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_person_in_charge')) != '' ) {
        $out_shop_data[] = __('jp_pdfinv_person_in_charge') . __('jp_pdfinv_full_colon') . Registry::get('addons.localization_jp.jp_pdfinv_shop_person_in_charge');
    }
    // e-mail
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_contact_mail')) != '' ) {
        $out_shop_data[] = __('email') . __('jp_pdfinv_full_colon') . Registry::get('addons.localization_jp.jp_pdfinv_shop_contact_mail');
    }
    // 電話番号/FAX番号
    $buf = '';
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_contact_phone')) != '' ) {
        $buf = __('phone') . __('jp_pdfinv_full_colon') . Registry::get('addons.localization_jp.jp_pdfinv_shop_contact_phone');
    }
    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_contact_fax')) != '' ) {
        if( $buf != '' ) $buf .= ' / ';
        $buf .= __('fax') . __('jp_pdfinv_full_colon') . Registry::get('addons.localization_jp.jp_pdfinv_shop_contact_fax');
    }
    if( $buf != '' ) $out_shop_data[] = $buf;

    // ショップ通信欄（下段）行数
    $info_line_count = 0;
    $info_line_count = fn_lcjp_adjust_customer_comment($pdf, Registry::get('addons.localization_jp.jp_pdfinv_print_info_dw'), $dummy);

    // 出力ファイル名用変数を初期化
    $output_order_id = '';

    // 出力ファイル用注文ID
    foreach( $order_ids as $order_id ){

        // 注文情報取得
        $order_data = fn_get_order_info($order_id);

        $output_order_id .= '_' . $order_id;

        // ロゴ画像のパスを取得
        $logos = fn_get_logos($order_data['company_id']);
        $filename = $logos['mail']['image']['absolute_path'];

        if(!is_file($filename)) {
            $_cid = Registry::get('runtime.forced_company_id');
            if(!empty($_cid)) {
                $logos = fn_get_logos($_cid);
            } else {
                $logos = fn_get_logos(0);
            }
            $filename = $logos['mail']['image']['absolute_path'];
        }

        // 注文時の通貨取得
        $order_currency_setting = db_get_row("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'R'", $order_id);
        if( !empty($order_currency_setting) ){
            $order_currency = unserialize($order_currency_setting['data']);
        } else {
            $order_currency = CART_PRIMARY_CURRENCY;
        }

        // 注文合計ページのみ印刷が必要か
        $order_total_page_print = false;

        // 明細行を減らす必要があるか
        $product_line_minus_flag = false;
        $product_line_minus_count = 0;

        // ページ数計算
        $total_pages = floor(count($order_data['products']) / 10);

        // 最終ページの明細行
        $fragment_line = (count($order_data['products']) % 10);
        $total_pages += ($fragment_line > 0 ? 1 : 0);

        // 注文合計行の計算
        $order_total_line = fn_lcjp_count_total_line($order_data);

        if( $order_total_line > 8 ){
            // 合計行が8行以上ある場合は商品明細行を少なくする
            $over_count = $order_total_line - 8;
            $over_line = floor($over_count / 3);
            $over_line += (($over_count % 3) > 0 ? 1 : 0);
            if( $fragment_line == 0 || ($fragment_line + $over_line) > 10 ){
                $order_total_page_print = true;
                $total_pages++;
            }else{
                $product_line_minus_flag = true;
                $product_line_minus_count = $over_line;
            }
        }

        $print_line = 99;
        // ページ小計
        $page_total = 0;
        $pages = 0;

        //////////////////////////////////////////////
        // 明細行印刷 BOF
        //////////////////////////////////////////////
        foreach($order_data['products'] as $products_array_key => $product) {
            // 改ページ処理
            if( $print_line > 9 ){
                // ページを跨がる印刷に場合はページ小計を印刷
                if( $page_total > 0 ){
                    $pdf->SetX(120);
                    $pdf->MultiCell(40, 0, __('jp_pdfinv_page_subtotal'), 1, 'R', 1, 0);
                    $print_price = fn_lcjp_convert_price($page_total, $order_currency, true);
                    $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
                }
                if( $pages > 0 ){
                    // ページ印刷
                    $pdf->SetXY(200, 288);
                    $pdf->Cell(0, 0, __('pages') . ' ' . $pages . '/' . $total_pages, 0, 0, 'R');
                    if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_url')) != '' ){
                        $pdf->SetXY(5, 288);
                        $shop_url = __('website') . ' ' . trim(mb_convert_kana(Registry::get('addons.localization_jp.jp_pdfinv_shop_url'), "as", "UTF-8"));
                        $font_size = fn_lcjp_get_url_font_size($pdf, 8, $shop_url);
                        $pdf->SetFont('kozminproregular', '', $font_size);
                        $pdf->Cell(0, 0, $shop_url, 0, 0, 'L');
                        $pdf->SetFont('kozminproregular', '', 8);
                    }
                    // バーコード印刷
                    if( !empty($barcode_settings) && $barcode_settings['status'] != 'D' ){
                        $bcStyle = array(
                            'position' => 'L',
                            'border' => false,
                            'padding' => 0,
                            'fgcolor' => array(0,0,0),
                            'bgcolor' => false,
                            'text' => true,
                            'font' => 'kozminproregular',
                            'fontsize' => 6,
                            'stretchtext' => 4
                        );
                        if( $barcode_settings['type'] != 'C128C' && $barcode_settings['type'] != 'I25' ){
                            $output_code = $barcode_settings['prefix'] . $order_data["order_id"];
                        } else {
                            $output_code = $order_data["order_id"];
                        }
                        $pdf->write1DBarcode($output_code, $barcode_settings['type'], 5, 5, 50, 8, 0.4, $bcStyle, 'N');
                    }
                }
                // ヘッダー印刷
                fn_lcjp_print_pdf_header($pdf, $out_shop_data, $filename, $barcode_settings, $order_data);
                $print_line = 0;
                // ページ小計
                $page_total = 0;
                $pages++;
            }

            // カスタマイズ商品対応（親商品のみメインループで印刷）
            if( !$product['extra']['parent'] || !empty($product['extra']['parent']['buy_together'])){
                // 商品単価
                $print_base_price = $product['original_price'];
                // 注文小計
                $print_subtotal = $product['display_subtotal'];
                $page_total += $print_subtotal;
                // 子商品があるか確認し、あれば価格を合算
                $custome_product = 0;
                foreach($order_data['products'] as $subitem){
                    if( !empty($subitem['extra']['parent']['configuration']) && $subitem['extra']['parent']['configuration'] == $products_array_key ){
                        $custome_product = 1;
                        $print_base_price += $subitem['original_price'];
                        $print_subtotal += $subitem['display_subtotal'];
                    }
                }
                fn_lcjp_print_order_line($pdf, $product, $print_base_price, $print_subtotal, $order_currency, $custome_product);
                $print_line++;
                if( $custome_product == 1 ){
                    foreach($order_data['items'] as $subitem) {
                        if( $print_line > 9 ){
                            // ヘッダー印刷
                            fn_lcjp_print_pdf_header($pdf, $out_shop_data, $filename, $barcode_settings, $order_data);
                            $print_line = 0;
                        }
                        if( !empty($subitem['extra']['parent']['configuration']) && $subitem['extra']['parent']['configuration'] == $products_array_key ){
                            $custome_product = 2;
                            // 商品単価
                            $print_base_price = $subitem['original_price'];
                            // 注文小計
                            $print_subtotal = $subitem['display_subtotal'];
                            fn_lcjp_print_order_line($pdf, $subitem, $print_base_price, $print_subtotal, $order_currency, $custome_product);
                            $print_line++;
                        }
                    }
                }
            }
        }

        if( $order_total_page_print ){
            // ページを跨がる印刷に場合はページ小計を印刷
            if( $page_total > 0 ){
                $pdf->SetX(120);
                $pdf->MultiCell(40, 0, __('jp_pdfinv_page_subtotal'), 1, 'R', 1, 0);
                $print_price = fn_lcjp_convert_price($page_total, $order_currency, true);
                $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
            }
            if( $pages > 0 ){
                // ページ印刷
                $pdf->SetXY(200, 288);
                $pdf->Cell(0, 0, __('pages') . ' ' . $pages . '/' . $total_pages, 0, 0, 'R');
                if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_url')) != '' ){
                    $pdf->SetXY(5, 288);
                    $shop_url = __('website') . ' ' . trim(mb_convert_kana(Registry::get('addons.localization_jp.jp_pdfinv_shop_url'), "as", "UTF-8"));
                    $font_size = fn_lcjp_get_url_font_size($pdf, 8, $shop_url);
                    $pdf->SetFont('kozminproregular', '', $font_size);
                    $pdf->Cell(0, 0, $shop_url, 0, 0, 'L');
                    $pdf->SetFont('kozminproregular', '', 8);
                }
                // バーコード印刷
                if( !empty($barcode_settings) && $barcode_settings['status'] != 'D' ){
                    $bcStyle = array(
                        'position' => 'L',
                        'border' => false,
                        'padding' => 0,
                        'fgcolor' => array(0,0,0),
                        'bgcolor' => false,
                        'text' => true,
                        'font' => 'kozminproregular',
                        'fontsize' => 6,
                        'stretchtext' => 4
                    );
                    if( $barcode_settings['type'] != 'C128C' && $barcode_settings['type'] != 'I25' ){
                        $output_code = $barcode_settings['prefix'] . $order_data["order_id"];
                    } else {
                        $output_code = $order_data["order_id"];
                    }
                    $pdf->write1DBarcode($output_code, $barcode_settings['type'], 5, 5, 50, 8, 0.4, $bcStyle, 'N');
                }
            }
            // ヘッダー印刷
            fn_lcjp_print_pdf_header($pdf, $out_shop_data, $filename, $barcode_settings, $order_data);
            $pages++;
        }else{
            if( $product_line_minus_flag ){
                $page_max_line = 10 - $product_line_minus_count;
            } else {
                $page_max_line = 10;
            }
            if( $print_line < $page_max_line ){
                for($lp=$print_line; $lp<$page_max_line; $lp++) {
                    $pdf->MultiCell(115, 16.5840833334, '', 1, 'L', 0, 0);
                    $pdf->MultiCell(25, 16.5840833334, '', 1, 'L', 0, 0);
                    $pdf->MultiCell(15, 16.5840833334, '', 1, 'L', 0, 0);
                    $pdf->MultiCell(20, 16.5840833334, '', 1, 'L', 0, 0);
                    $pdf->MultiCell(25, 16.5840833334, '', 1, 'L', 0, 1);
                }
            }
        }
        //////////////////////////////////////////////
        // 明細行印刷 EOF
        //////////////////////////////////////////////

        //////////////////////////////////////////////
        // 備考欄印刷 BOF
        //////////////////////////////////////////////
        $_memo_info = '';

        // 支払方法を備考欄に記載
        if ( !empty($order_data['payment_method']['payment']) ){
            $_memo_info = '【' . __('payment_method') . '】 ' . $order_data['payment_method']['payment'];
        }

        // 配送に関する情報を備考欄に記載
        $lcjp_shipping_info = '';
        if( !empty($order_data['shipping']) && is_array($order_data['shipping']) ){
            foreach($order_data['shipping'] as $shipping_data){
                // 配送方法名が登録されている場合
                if( !empty($shipping_data['shipping']) ) $lcjp_shipping_info .= "\n" . '【' . __('shipping_method') . '】 ' . $shipping_data['shipping'];
                if( !empty($shipping_data['delivery_date']) )  $lcjp_shipping_info .= "\n" . __('jp_delivery_date') . ' : ' . $shipping_data['delivery_date'];
                if( !empty($shipping_data['delivery_timing']) )  $lcjp_shipping_info .= "\n" . __('jp_shipping_delivery_time') . ' : ' . $shipping_data['delivery_timing'] . "\n";
            }
        }
        if( !empty($lcjp_shipping_info) ){
            $_memo_info .= "\n" . $lcjp_shipping_info;
        }

        // お客様コメントを備考欄に記載
        if( !empty($order_data['notes']) ){
            // お客様が入力したコメントから改行を削除
            $_customer_comments = str_replace("\r\n", '\n', $order_data['notes']);
            $_customer_comments = str_replace("\r", '\n', $_customer_comments);
            $_customer_comments = str_replace('\n', '', $_customer_comments);
            $_memo_info .= "\n" . '【' . __('customer_notes') . '】' . "\n" . $_customer_comments;
        }

        $outpuf_note = $_memo_info . "\n\n" . Registry::get('addons.localization_jp.jp_pdfinv_print_info_dw');
        $pdf->MultiCell(115, 44.224222222, $outpuf_note, 1, 'L', 0, 0, '', '', true, 0, false, true, 46.224222222);
        //////////////////////////////////////////////
        // 備考欄印刷 EOF
        //////////////////////////////////////////////

        //////////////////////////////////////////////
        // 合計欄印刷 BOF
        //////////////////////////////////////////////
        // 小計
        $pdf->SetX(120);
        $pdf->MultiCell(40, 0, __('subtotal'), 1, 'R', 1, 0);
        $print_price = fn_lcjp_convert_price($order_data['display_subtotal'], $order_currency, true);
        $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);

        // 値引き
        if( $order_data['discount'] > 0 ){
            $pdf->SetX(120);
            $pdf->MultiCell(40, 0, __('including_discount'), 1, 'R', 1, 0);
            $print_price = fn_lcjp_convert_price($order_data['discount'], $order_currency, true);
            $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
        }

        // 注文値引き
        if( $order_data['subtotal_discount'] > 0 ){
            $pdf->SetX(120);
            $pdf->MultiCell(40, 0, __('order_discount'), 1, 'R', 1, 0);
            $print_price = fn_lcjp_convert_price($order_data['subtotal_discount'], $order_currency, true);
            $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
        }

        // クーポン印刷
        if( $order_data['coupons'] ){
            $pdf->SetFont('kozminproregular', '', 7);
            foreach($order_data['coupons'] as $key => $val) {
                $pdf->SetX(120);
                $pdf->MultiCell(40, 0, __('coupons'), 1, 'R', 1, 0);
                $pdf->MultiCell(45, 0, $key, 1, 'R', 0, 1);
            }
            $pdf->SetFont('kozminproregular', '', 8);
        }

        // 送料
        $shipping_total = 0;
        if( !empty($order_data['shipping']) ){
            foreach($order_data['shipping'] as $shipping) {
                $shipping_total += $shipping['rate'];
            }
        }
        if( $shipping_total > 0 ){
            $pdf->SetX(120);
            $pdf->MultiCell(40, 0, __('shipping_cost'), 1, 'R', 1, 0);
            $print_price = fn_lcjp_convert_price($shipping_total, $order_currency, true);
            $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
        }

        // 代引き手数料
        if( !empty($order_data['payment_surcharge']) && $order_data['payment_surcharge'] > 0 ){
            $pdf->SetX(120);
            $pdf->MultiCell(40, 0, __('payment_surcharge'), 1, 'R', 1, 0);
            $print_price = fn_lcjp_convert_price($order_data['payment_surcharge'], $order_currency, true);
            $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
        }

        // ポイント情報
        if( (int)$order_data['points_info']['reward'] > 0 ){
            $pdf->SetX(120);
            $pdf->MultiCell(40, 0, __('points'), 1, 'R', 1, 0);
            $pdf->MultiCell(45, 0, number_format((int)$order_data['points_info']['reward']) . __('reward_points'), 1, 'R', 0, 1);
        }
        if( (int)$order_data['points_info']['in_use']['cost'] > 0 ){
            $pdf->SetFont('kozminproregular', '', 7);
            $pdf->SetX(120);
            $pdf->MultiCell(40, 0, __('points_in_use') . '(' . number_format((int)$order_data['points_info']['in_use']['points']) . __('points') . ')', 1, 'R', 1, 0);
            $print_price = fn_lcjp_convert_price($order_data['points_info']['in_use']['cost'], $order_currency, true);
            $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
            $pdf->SetFont('kozminproregular', '', 8);
        }
        // ギフト券
        if( $order_data['use_gift_certificates'] ){
            $pdf->SetFont('kozminproregular', '', 7);
            foreach($order_data['use_gift_certificates'] as $key => $val){
                $pdf->SetX(120);
                $pdf->MultiCell(40, 0, __('gift_certificate') . '[' . $key . ']', 1, 'R', 1, 0);
                $print_price = fn_lcjp_convert_price($val['cost'], $order_currency, true);
                $pdf->MultiCell(45, 0,'(' . $print_price . ')', 1, 'R', 0, 1);
            }
            $pdf->SetFont('kozminproregular', '', 8);
        }

        ///////////////////////////////////////////////////////////////
        // Modified for Japanese Ver by takahashi from cs-cart.jp 2019 BOF
        // 軽減税率対応（あんどぷらす望月様よりご提供）
        ///////////////////////////////////////////////////////////////
        foreach ($order_data['taxes'] as $tax) {
            if ((int) $tax['rate_value'] > 0) {
                $pdf->SetX(120);
                $pdf->MultiCell(40, 0, $tax['description'] . ' ' . number_format($tax['rate_value']) . '%', 1, 'R', 1, 0);
                $print_price = fn_lcjp_convert_price($tax['tax_subtotal'], $order_currency, true);
                $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
            }
        }
        ///////////////////////////////////////////////////////////////
        // Modified for Japanese Ver by takahashi from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////////////////////

        // 合計
        $pdf->SetFont('kozminproregular', 'B', 9);
        $pdf->SetX(120);
        $pdf->MultiCell(40, 0, __('total_cost'), 1, 'R', 1, 0);
        $print_price = fn_lcjp_convert_price($order_data['total'], $order_currency, true);
        $pdf->MultiCell(45, 0, $print_price, 1, 'R', 0, 1);
        $pdf->SetFont('kozminproregular', '', 8);
        //////////////////////////////////////////////
        // 合計欄印刷 EOF
        //////////////////////////////////////////////

        // ページ印刷
        $pdf->SetXY(200, 288);
        $pdf->Cell(0, 0, __('pages') . ' ' . $pages . '/' . $total_pages, 0, 0, 'R');
        if( trim(Registry::get('addons.localization_jp.jp_pdfinv_shop_url')) != '' ){
            $pdf->SetXY(5, 288);
            $shop_url = __('website') . ' ' . trim(mb_convert_kana(Registry::get('addons.localization_jp.jp_pdfinv_shop_url'), "as", "UTF-8"));
            $font_size = fn_lcjp_get_url_font_size($pdf, 8, $shop_url);
            $pdf->SetFont('kozminproregular', '', $font_size);
            $pdf->Cell(0, 0, $shop_url, 0, 0, 'L');
            $pdf->SetFont('kozminproregular', '', 8);
        }

        // バーコード
        if( !empty($barcode_settings) && $barcode_settings['status'] != 'D' ){
            $bcStyle = array(
                'position' => 'L',
                'border' => false,
                'padding' => 0,
                'fgcolor' => array(0,0,0),
                'bgcolor' => false,
                'text' => true,
                'font' => 'kozminproregular',
                'fontsize' => 6,
                'stretchtext' => 4
            );
            if( $barcode_settings['type'] != 'C128C' && $barcode_settings['type'] != 'I25' ){
                $output_code = $barcode_settings['prefix'] . $order_data["order_id"];
            }else{
                $output_code = $order_data["order_id"];
            }
            $pdf->write1DBarcode($output_code, $barcode_settings['type'], 5, 5, 50, 8, 0.4, $bcStyle, 'N');
        }
    }

    // 出力ファイル名をセット
    $output_filename = "order_invoice" . $output_order_id . ".pdf";

    // PDFファイルを出力
    $pdf->Output($output_filename, "D");
}




// 明細行印刷
function fn_lcjp_print_order_line(&$pdf, $product, $print_base_price, $print_subtotal, $order_currency, $custome_product = NULL)
{
    // 商品名（カスタム商品の子であれば商品名、オプション名の先頭に半角スペース2個追加）
    $product_data = ($custome_product == 2 ? '  ' : '') . fn_lcjp_adjust_strings($pdf, $product['product']);
    // 商品オプション
    $add_line = 0;
    if( $custome_product == 1 ){
        $max_add_line = 1;
    }else{
        $max_add_line = 2;
    }
    $option_price = 0;
    $over_count = 0;
    $over_count = (count($product['product_options']) - ($max_add_line + 1));
    if( count($product['product_options']) > 0 ){
        foreach($product['product_options'] as $option){
            if( $add_line <= $max_add_line ){
                $product_data .= "\n" . fn_lcjp_adjust_strings($pdf, '  ' . $option['option_name'] . __('jp_pdfinv_full_colon') . $option['variant_name'], 'option');
            }
            $option_price += ($option['modifier'] > 0 ? $option['modifier'] : 0);
            $add_line++;
        }
    }else{
        // 定期支払い
        // オプションがない場合のみ印刷
        if( $product['extra']['recurring_plan_id'] && (int)$custome_product == 0 ){
            // プラン名
            $product_data .= "\n" . __('rb_recurring_plan') . __('jp_pdfinv_full_colon') . $product['extra']['recurring_plan']['name'];
            // 支払い間隔
            $product_data .= "\n" . __('rb_recurring_period') . __('jp_pdfinv_full_colon') . fn_get_recurring_period_name($product['extra']['recurring_plan']['period']);
            // 支払い期間
            $product_data .= "\n" . __('rb_start_duration') . __('jp_pdfinv_full_colon') . $product['extra']['recurring_duration'];
        }
    }
    // その他表示
    if( $over_count > 0 ){
        $product_data .= ' ' . __('jp_pdfinv_other_option');
    }
    if( $custome_product == 1 ){
        $product_data .= "\n" . __('jp_pdfinv_customise_product');
    }
    $pdf->MultiCell(115, 16.5840833334, $product_data, 1, 'L', 0, 0);
    // 商品型番
    $pdf->MultiCell(25, 16.5840833334, $product['product_code'], 1, 'L', 0, 0);
    // 数量
    $pdf->MultiCell(15, 16.5840833334, number_format($product['amount']), 1, 'R', 0, 0);
    // 単価
    if( $product['extra']['exclude_from_calculate'] ){
        $pdf->MultiCell(20, 16.5840833334, __('free'), 1, 'R', 0, 0);
    }else{
        $print_price = fn_lcjp_convert_price(($print_base_price), $order_currency, false);
        // 多通貨対応 円、＄以外の通貨はコードを出力するためフォントサイズを調整
        $_font_sieze = 8;
        while($pdf->GetStringWidth($print_price, 'kozminproregular', '', $_font_sieze) > 18.2) {
            $_font_sieze = $_font_sieze - 0.2;
        }
        if($option_price != 0) {
            $print_price .= "\n(" . fn_lcjp_convert_price(($option_price), $order_currency, false) . ')';
        }
        $pdf->SetFont('kozminproregular', '', $_font_sieze);
        $pdf->MultiCell(20, 16.5840833334, $print_price, 1, 'R', 0, 0, '', '', true, 1);
        $pdf->SetFont('kozminproregular', '', 8);
    }

    // 税抜きの小計を表示
    if( $product['extra']['exclude_from_calculate'] ){
        $pdf->MultiCell(25, 16.5840833334, __('free'), 1, 'R', 0, 1);
    }else{
        $print_item_price = fn_lcjp_convert_price($print_subtotal, $order_currency, false);
        // 値引きがあれば印刷
        $discount_info = '';
        if( (int)$product['discount'] > 0 ){
            $discount_total = ($product['discount'] * $product['amount']);
            $_discount_price = __('discount') . ' ' . fn_lcjp_convert_price($discount_total, $order_currency, false);
            // 多通貨対応 円、＄以外の通貨はコードを出力するためフォントサイズを調整
            while($pdf->GetStringWidth($_discount_price, 'kozminproregular', '', $_font_sieze) > 23.2) {
                $_font_sieze = $_font_sieze - 0.2;
            }
            $pdf->SetFont('kozminproregular', '', $_font_sieze);
            $print_item_discount_price = "\n" . $_discount_price;
        }
        $print_price = $print_item_price . $print_item_discount_price;
        $pdf->MultiCell(25, 16.5840833334, $print_price, 1, 'R', 0, 1);
        $pdf->SetFont('kozminproregular', '', 8);
    }
}




// 商品明細行の文字列調整
function fn_lcjp_adjust_strings(&$pdf, $strings, $type = NULL)
{
    if( $type == NULL ){
        $max_width = 110;
    }elseif( $type == 'option' ){
        $max_width = 79;
    }elseif( $type == 'comment' ){
        $max_width = 196;
    }
    $work_string = str_replace("\r\n", "\n", $strings);
    $work_string = str_replace("\r", "\n", $work_string);
    $work_string = str_replace("\n", '', $work_string);
    if( $pdf->GetStringWidth($work_string, 'kozminproregular', '', 8) < $max_width ){
        return $work_string;
    }else{
        $lp = 0;
        $res = '';
        $buf = '';
        $buf = mb_substr($work_string, 0, 1, 'UTF-8');
        while($pdf->GetStringWidth($res . $buf, 'kozminproregular', '', 8) < $max_width){
            $res .= $buf;
            $lp++;
            if( $lp > mb_strlen($work_string, 'UTF-8') ) break;
            $buf = mb_substr($work_string, $lp, 1, 'UTF-8');
        }
        return $res;
    }
}




// 顧客追加情報の取得
function fn_lcjp_get_customer_extra_field_data($order_id, $field_id)
{
    return db_get_field("SELECT ?:profile_fields_data.value FROM ?:profile_fields_data WHERE ?:profile_fields_data.object_type = 'O' AND ?:profile_fields_data.object_id = ?i AND ?:profile_fields_data.field_id = ?i", $order_id, $field_id);
}




// 合計行のライン数計算
function fn_lcjp_count_total_line($order_data)
{
    // 小計、合計行で2行
    $ret_count = 2;
    // 値引き
    if( (int)$order_data['discount'] > 0 ) $ret_count++;

    // 注文値引き
    if( (int)$order_data['subtotal_discount'] > 0 ) $ret_count++;

    // クーポン印刷
    if( $order_data['coupons'] ){
        foreach($order_data['coupons'] as $key => $val){
            $ret_count++;
        }
    }

    // 送料
    /* !!!!!!!! 後で検証する !!!!!!!!!!!!!!!!
    $shipping_total = 0;

    if( !empty($order_data['shipping']) ){
        foreach($order_data['shipping'] as $shipping){
            foreach($shipping['rates'] as $shipping_rates){
                $shipping_total += $shipping_rates;
            }
        }
    }

    if( (int)$shipping_total > 0 ) $ret_count++;
    */

    // 代引き手数料
    if( !empty($order_data['payment_surcharge']) && $order_data['payment_surcharge'] > 0 ) $ret_count++;

    // 取得ポイント情報
    if( (int)$order_data['points_info']['reward'] > 0 ) $ret_count++;
    // 利用ポイント
    if( (int)$order_data['points_info']['in_use']['cost'] > 0 ) $ret_count++;
    // ギフト券
    if( $order_data['use_gift_certificates'] ){
        foreach($order_data['use_gift_certificates'] as $key => $val){
            $ret_count++;
        }
    }
    // 消費税
    $tax_total = 0;
    foreach($order_data['taxes'] as $tax){
        if( (int)$tax['rate_value'] > 0 ){
            $tax_total += $tax['tax_subtotal'];
        }
    }
    if( $tax_total > 0 ) $ret_count++;

    return $ret_count;
}




// コメント欄の整形
function fn_lcjp_adjust_customer_comment($pdf, $customer_comment, &$res_comment)
{
    $res_comment = array();
    $cr_cnv_buf = str_replace("\r\n", "\n", trim($customer_comment));
    $cr_cnv_buf = str_replace("\r", "\n", $cr_cnv_buf);
    $cr_cnv_buf = str_replace("\n", '<>', $cr_cnv_buf);
    if( $cr_cnv_buf != '' ){
        $work_comment = explode('<>', $cr_cnv_buf);
        $line_count = 0;
        foreach($work_comment as $work_line){
            if( trim($work_line) != '' ){
                $line_count++;
                $res_comment[($line_count - 1)] = '';
                for($lp=0; $lp<mb_strlen(trim($work_line), 'UTF-8'); $lp++){
                    $res_comment[($line_count - 1)] .= mb_substr(trim($work_line), $lp, 1, 'UTF-8');
                    if( $pdf->GetStringWidth($res_comment[($line_count - 1)], 'kozminproregular', '', 8) > 110.066666667 ){
                        $line_count++;
                        $res_comment[($line_count - 1)] = '';
                    }
                }
            }
        }
        return $line_count;
    }else{
        return 0;
    }
}




// pdf納品書ヘッダー出力
function fn_lcjp_print_pdf_header(&$pdf, $out_shop_data, $filename, $barcode_settings, $order_data)
{
    $pdf->AddPage();

    $pdf->SetFont('kozminproregular', 'B', 24);
    $pdf->Cell(0, 0, __('jp_pdfinv_invoice'), 0, 1, 'C');
    $pdf->SetFont('kozminproregular', '', 8);
    $pdf->SetLineStyle(array('width' => 2, 'cap' => 'square', 'color' => array(0, 0, 0)));
    $pdf->Line(5, 18, 205, 18);
    $pdf->SetLineStyle(array('width' => 0.201083333333, 'cap' => 'square', 'color' => array(0, 0, 0)));
    $order_id_width = $pdf->GetStringWidth(__('order_id') . __('jp_pdfinv_full_colon') . $order_data['order_id'], 'kozminproregular', '', 8);
    $pdf->SetXY(203.5 - $order_id_width, 8);
    $pdf->Cell(0, 0, __('order_id') . __('jp_pdfinv_full_colon') . $order_data['order_id'], 1, 1, 'R');
    $pdf->SetXY(185, 12.55);
    $pdf->Cell(0, 0, __('order_date') . __('jp_pdfinv_full_colon') . date("Y/m/d", $order_data['timestamp']), 0, 1, 'R');
    $pdf->SetY(20);

    $image_size = getimagesize($filename);
    $logo_width = ($image_size[0] / 2.834);
    $logo_height = ($image_size[1] / 2.834);

    // 顧客情報
    $out_customer = array();

    // 支払いと配送情報（氏名、住所）が違う場合
    if( $order_data['b_firstname'] != $order_data['s_firstname'] || $order_data['b_lastname'] != $order_data['s_lastname'] || $order_data['b_address'] != $order_data['s_address'] ){
        if( Registry::get('addons.localization_jp.jp_pdfinv_print_customer_address') == 0 ){
            // 請求先住所を印刷
            // 郵便番号
            $out_customer[] = __('jp_pdfinv_zip_title') . ' ' . $order_data['b_zipcode'];
            // 住所上段
            $out_customer[] = $order_data['b_state'] . $order_data['b_city'];
            // 住所下段
            $out_customer[] = $order_data['b_address'] . $order_data['b_address_2'];

            // 顧客会社名
            $out_company_name = '';
            if( (int)Registry::get('addons.localization_jp.jp_pdfinv_billing_company_field') > 0 ){
                $out_company_name = fn_lcjp_get_customer_extra_field_data($order_data['order_id'], Registry::get('addons.localization_jp.jp_pdfinv_billing_company_field'));
            }
            if( $out_company_name != '' ){
                $out_customer[] = $out_company_name;
            }else{
                if( $order_data['company'] != '' ){
                    $out_customer[] = $order_data['company'];
                }
            }

            // 顧客名
            $out_customer[] = $order_data['b_firstname'] . ' ' . $order_data['b_lastname'] . ' ' . __('dear');

            // 顧客電話番号
            $out_customer_phone = $order_data['b_phone'];

            if( !empty($out_customer_phone) ){
                $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $out_customer_phone;

            }elseif( (int)Registry::get('addons.localization_jp.jp_pdfinv_billing_phone_field') > 0 ){
                $out_customer_phone = fn_lcjp_get_customer_extra_field_data($order_data['order_id'], Registry::get('addons.localization_jp.jp_pdfinv_billing_phone_field'));

                if( !empty($out_customer_phone) ){
                    $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $out_customer_phone;
                }else{
                    if( !empty($order_data['phone']) ){
                        $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $order_data['phone'];
                    }
                }
            }else{
                if( !empty($order_data['phone']) ){
                    $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $order_data['phone'];
                }
            }

        }else{
            // 配送先住所を印刷
            // 郵便番号
            $out_customer[] = __('jp_pdfinv_zip_title') . ' ' . $order_data['s_zipcode'];
            // 住所上段
            $out_customer[] = $order_data['s_state'] . $order_data['s_city'];
            // 住所下段
            $out_customer[] = $order_data['s_address'] . $order_data['s_address_2'];
            // 顧客会社名
            $out_company_name = '';
            if( (int)Registry::get('addons.localization_jp.jp_pdfinv_shipping_company_field') > 0 ){
                $out_company_name = fn_lcjp_get_customer_extra_field_data($order_data['order_id'], Registry::get('addons.localization_jp.jp_pdfinv_shipping_company_field'));
            }
            if( $out_company_name != '' ){
                $out_customer[] = $out_company_name;
            }
            // 顧客名
            $out_customer[] = $order_data['s_firstname'] . ' ' . $order_data['s_lastname'] . ' ' . __('dear');

            // 顧客電話番号
            $out_customer_phone = $order_data['s_phone'];

            if( !empty($out_customer_phone) ){
                $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $out_customer_phone;

            }elseif( (int)Registry::get('addons.localization_jp.jp_pdfinv_shipping_phone_field') > 0 ){
                $out_customer_phone = fn_lcjp_get_customer_extra_field_data($order_data['order_id'], Registry::get('addons.localization_jp.jp_pdfinv_shipping_phone_field'));

                if( !empty($out_customer_phone) ){
                    $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $out_customer_phone;
                }else{
                    if( !empty($order_data['phone']) ){
                        $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $order_data['phone'];
                    }
                }
            }else{
                if( !empty($order_data['phone']) ){
                    $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $order_data['phone'];
                }
            }
        }
    }else{
        // 請求先住所を印刷
        // 郵便番号
        $out_customer[] = __('jp_pdfinv_zip_title') . ' ' . $order_data['b_zipcode'];
        // 住所上段
        $out_customer[] = $order_data['b_state'] . $order_data['b_city'];
        // 住所下段
        $out_customer[] = $order_data['b_address'] . $order_data['b_address_2'];

        // 顧客会社名
        $out_company_name = '';
        if( (int)Registry::get('addons.localization_jp.jp_pdfinv_billing_company_field') > 0 ){
            $out_company_name = fn_lcjp_get_customer_extra_field_data($order_data['order_id'], Registry::get('addons.localization_jp.jp_pdfinv_billing_company_field'));
        }
        if( $out_company_name != '' ){
            $out_customer[] = $out_company_name;
        }else{
            if( $order_data['company'] != '' ){
                $out_customer[] = $order_data['company'];
            }
        }
        // 顧客名
        $out_customer[] = $order_data['b_firstname'] . ' ' . $order_data['b_lastname'] . ' ' . __('dear');

        // 顧客電話番号
        $out_customer_phone = $order_data['b_phone'];

        if( !empty($out_customer_phone) ){
            $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $out_customer_phone;

        }elseif( (int)Registry::get('addons.localization_jp.jp_pdfinv_billing_phone_field') > 0 ){
            $out_customer_phone = fn_lcjp_get_customer_extra_field_data($order_data['order_id'], Registry::get('addons.localization_jp.jp_pdfinv_billing_phone_field'));

            if( !empty($out_customer_phone) ){
                $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $out_customer_phone;
            }else{
                if( !empty($order_data['phone']) ){
                    $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $order_data['phone'];
                }
            }
        }else{
            if( !empty($order_data['phone']) ){
                $out_customer[] = __('phone') . __('jp_pdfinv_full_colon') . $order_data['phone'];
            }
        }
    }
    $customer_font_size = 10;
    $shop_font_size = 10;
    for($lp=0; $lp<7; $lp++){
        if( isset($out_customer[$lp]) && !empty($out_customer[$lp]) ){
            if( $pdf->GetStringWidth($out_customer[$lp], 'kozminproregular', '', 10) > 100 ){
                $customer_font_size = 8;
            }
        }
        if( isset($out_shop_data[$lp]) && !empty($out_shop_data[$lp]) ){
            if( $pdf->GetStringWidth($out_shop_data[$lp], 'kozminproregular', '', 10) > 100 ){
                $shop_font_size = 8;
            }
        }
    }
    $pdf->SetFont('kozminproregular', '', 10);
    // 顧客、ショップ情報印刷
    for($lp=0; $lp<7; $lp++){
        if( isset($out_customer[$lp]) && !empty($out_customer[$lp]) ){
            $pdf->SetFont('kozminproregular', '', $customer_font_size);
            $pdf->MultiCell(100, 0, $out_customer[$lp], 0, 'L', 0, 0);
        }else{
            // インデント
            $pdf->SetX(105);
        }
        $pdf->SetFont('kozminproregular', '', 10);
        if( $lp==0 ){
            // ロゴ印刷
            if( Registry::get('addons.localization_jp.jp_pdfinv_print_shop_logo') == 'Y' ){
                $pdf->Image($filename, (205 - $logo_width), 20, 0, 0, '', '', '', false, 600);
            }
        }
        if( isset($out_shop_data[$lp]) && !empty($out_shop_data[$lp]) ){
            $pdf->SetFont('kozminproregular', '', $shop_font_size);
            $pdf->MultiCell(100, 0, $out_shop_data[$lp], 0, 'L', 0, 1);
        }else{
            // 改行
            $pdf->MultiCell(100, 0, '', 0, 'L', 0, 1);
        }
        $pdf->SetFont('kozminproregular', '', 10);
    }

    // ショップ側コメント上段印刷（改行はさせない）
    $pdf->SetXY(5, 64.8698055556);
    $shop_comment = str_replace("\r\n", "\n", trim(Registry::get('addons.localization_jp.jp_pdfinv_print_info_up')));
    $shop_comment = str_replace("\r", "\n", $shop_comment);
    $shop_comment = str_replace("\n", '', $shop_comment);
    $shop_comment = fn_lcjp_adjust_strings($pdf, trim($shop_comment), 'comment');
    $pdf->MultiCell(0, 0, $shop_comment, 0, 'L', 0, 1);		// ショップ担当者、電話番号/FAX番号

    $pdf->SetXY(5, 71.2797777778);
    $pdf->SetFont('kozminproregular', '', 8);
    // 明細行見出し
    $pdf->SetFillColor(221, 254, 255);
    $pdf->SetLineStyle(array('width' => $base_line_width, 'cap' => 'square', 'color' => array(11, 249, 255)));
    $pdf->MultiCell(115, 0, __('products') . ' / ' . __('options'), 1, 'C', 1, 0);
    $pdf->MultiCell(25, 0, __('sku'), 1, 'C', 1, 0);
    $pdf->MultiCell(15, 0, __('quantity'), 1, 'C', 1, 0);
    $pdf->MultiCell(20, 0, __('unit_price'), 1, 'C', 1, 0);
    $pdf->MultiCell(25, 0, __('subtotal'), 1, 'C', 1, 1);
}




// 通貨情報に従って表示金額文字列を作成
function fn_lcjp_convert_price($price, $print_currency, $total_line = false)
{
    if( Registry::get('settings.General.alternative_currency') == 'Y' ){
        $res = fn_lcjp_format_price_by_currency($price, CART_PRIMARY_CURRENCY);
        if( $print_currency != CART_PRIMARY_CURRENCY ){
            $res .= (!$total_line ? "\n" : '') . '(' . fn_lcjp_format_price_by_currency($price, $print_currency) . ')';
        }
    }else{
        $res = fn_lcjp_format_price_by_currency($price, $print_currency);
    }
    return $res;
}




// 表示通貨にて円とドル以外は通貨コードを利用する
function fn_lcjp_format_price_by_currency($price, $currency_code = CART_SECONDARY_CURRENCY)
{
    $currencies = Registry::get('currencies');
    $currency = $currencies[$currency_code];
    $result = fn_format_rate_value($price, 'F', $currency['decimals'], $currency['decimals_separator'], $currency['thousands_separator'], $currency['coefficient']);
    if ($currency['after'] == 'Y') {
        if(strtoupper($currency['currency_code']) == 'JPY' || strtoupper($currency['currency_code']) == 'USD') {
            $result .= ' ' . $currency['symbol'];
        } else {
            $result .= ' ' . $currency['currency_code'];
        }
    } else {
        if(strtoupper($currency['currency_code']) == 'JPY' || strtoupper($currency['currency_code']) == 'USD') {
            $result = $currency['symbol'] . $result;
        } else {
            $result = $currency['currency_code'] . $result;
        }
    }

    return $result;
}




// ショップURLのフォントサイズ取得
function fn_lcjp_get_url_font_size($pdf, $default_size, $url_string)
{
    $work_size = $default_size;
    while($pdf->GetStringWidth($url_string, 'kozminproregular', '', $work_size) > 180){
        $work_size = $work_size - 0.5;
        if( $work_size <= 0 ){
            $work_size = 1;
            break;
        }
    }
    return $work_size;
}

//////////////////////////////////////////////////////////
// PDF納品書に関する関数 END
//////////////////////////////////////////////////////////


/**
 * アドオンの設定値を追加
 *
 * @param $addon_id
 * @param $setting_name
 * @param $setting_data
 */
function fn_lcjp_add_setting_to_addon($addon_id, $setting_name, $setting_data)
{
    $addon_installed = db_get_field('SELECT addon FROM ?:addons WHERE addon = ?s', $addon_id);
    if ($addon_installed) {
        $section_id = db_get_field('SELECT section_id FROM ?:settings_sections WHERE name = ?s AND type = ?s', $addon_id, 'ADDON');
        if (!$section_id) {
            db_query("INSERT INTO ?:settings_sections (parent_id, edition_type, name, position, type) VALUES (0, 'ROOT', '$addon_id', 0 , 'ADDON')");
            $section_id = db_get_field('SELECT section_id FROM ?:settings_sections WHERE name = ?s AND type = ?s', $addon_id, 'ADDON');
        }
        if ($section_id) {
            $tab_id = db_get_field('SELECT section_id FROM ?:settings_sections WHERE parent_id = ?i AND type = ?s', $section_id, 'TAB');
            if (!$tab_id) {
                db_query("INSERT INTO ?:settings_sections (parent_id, edition_type, name, position, type) VALUES ($section_id, 'ROOT', 'general', 0 , 'TAB')");
                $tab_id = db_get_field('SELECT section_id FROM ?:settings_sections WHERE parent_id = ?i AND type = ?s', $section_id, 'TAB');
            }
            if ($tab_id) {
                $already_updated = db_get_field('SELECT object_id FROM ?:settings_objects WHERE section_id = ?i AND section_tab_id = ?i AND name = ?s', $section_id, $tab_id, $setting_name);
                if (!$already_updated) {
                    $position = 10;
                    $settings[$setting_name] = $setting_data;
                    $langs = db_get_fields('SELECT lang_code FROM ?:languages');
                    foreach ($settings as $name => $setting) {
                        $data_settings_objects = array(
                            'name' => $name,
                            'section_id' => $section_id,
                            'section_tab_id' => $tab_id,
                            'type' => $setting['type'],
                            'value' => $setting['value'],
                            'position' => $position,
                            'is_global' => 'N',
                        );
                        db_query('INSERT INTO ?:settings_objects ?e', $data_settings_objects);
                        $object_id = db_get_field('SELECT object_id FROM ?:settings_objects WHERE name = ?s AND section_id = ?i AND section_tab_id = ?i', $name, $section_id, $tab_id);
                        if ($object_id) {
                            $data_settings_descriptions = array(
                                'object_id' => $object_id,
                                'object_type' => 'O',
                                'lang_code' => '',
                                'value' => $setting['name'],
                                'tooltip' => $setting['tooltip'],
                            );
                            foreach ($langs as $lang_code) {
                                $data_settings_descriptions['lang_code'] = $lang_code;
                                db_query('REPLACE INTO ?:settings_descriptions ?e', $data_settings_descriptions);
                            }
                        }
                        $position += 10;
                    }
                }
            }
        }
    }
}

/**
 * $paymentの情報をチェック
 *
 * @param $payment
 * @return boolean
 */
function fn_lcjp_check_payment($payment)
{
    $result = false;

    $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors JOIN ?:payments ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE payment_id = ?i", $payment["payment_id"]);

    // トークン用のプロセッサーかを確認
    switch($pr_script){
        case "gmo_multipayment_cctkn.php": // GMO PGマルチペイメント
        case "smartlink_cctkn.php": // ソニーペイメント
        case "smartlink_ccreg.php": // ソニーペイメント登録済みカード
        case "spmc_cctkn.php": // ソニー外貨
        case "smbc_cctkn.php": // SMBC
        case "smbc_rbtkn.php": // SMBC継続課金
        case "krnkwc_cctkn.php": // ヤマト
        case "krnkwc_ccrtn.php": // ヤマト登録済み
        case "paygent_cctkn.php": // ペイジェント
        case "paygent_ccregtkn.php": // ペイジェント 登録済みカード
        case "omise_cc.php": // omise
        case "atone.php": // atone (2020/02 bugfix 一つ前に選択した支払方法の手数料が引き継がれてしまう)
        case "nttstr_cc.php": // NTTスマートトレード
        case "payjp_cc.php": // pay.jp
            $result = true;
            break;

        default:
            $result = false;
            break;
    }

    fn_set_hook('lcjp_check_payment_post', $payment, $pr_script, $result);

    return $result;
}




/**
 * // インストール処理
 */
function fn_lcjp_install($addon)
{
    $str = 'ICAgICRyZXN1bHQgPSBwcmVnX21hdGNoKCIvX0pQX1swLTldJC8iLCBQUk9EVUNUX1ZFUlNJT04pOw0KDQogICAgaWYgKCEkcmVzdWx0KSB7DQogICAgICAgICR0byA9ICd1cmdlbnRAY3MtY2FydC5qcCc7DQogICAgICAgICRmcm9tID0gJ2NvbXBhbnlfc2l0ZV9hZG1pbmlzdHJhdG9yJzsNCiAgICAgICAgJHN1YmplY3QgPSAn44Ki44OJ44Kq44Oz44Gu5LiN5q2j44Kk44Oz44K544OI44O844Or5pON5L2c44GM44GC44KK44G+44GX44GfJzsNCiAgICAgICAgJHVybCA9IGZuX3VybCgnJywgJ0MnLCAnaHR0cCcpOw0KICAgICAgICAkbGljZW5zZSA9IFR5Z2hcU2V0dGluZ3M6Omluc3RhbmNlKCktPmdldFNldHRpbmdEYXRhQnlOYW1lKCdsaWNlbnNlX251bWJlcicpOw0KICAgICAgICAkc2l0ZSA9IFR5Z2hcU2V0dGluZ3M6Omluc3RhbmNlKCktPmdldFNldHRpbmdEYXRhQnlOYW1lKCdjb21wYW55X25hbWUnKTsNCg0KICAgICAgICAkYm9keSA9ICfjgqLjg4njgqrjg7Pjga7kuI3mraPjgqTjg7Pjgrnjg4jjg7zjg6vmk43kvZzjgYzjgYLjgorjgb7jgZfjgZ/jgII8YnIvPjxici8+44K144Kk44OI5ZCNOiAnLiRzaXRlWyd2YWx1ZSddLic8YnIvPuOCteOCpOODiOOBrlVSTDogJy4kdXJsLic8YnIvPuODqeOCpOOCu+ODs+OCueeVquWPtzogJy4kbGljZW5zZVsndmFsdWUnXS4nPGJyLz7jgqLjg4njgqrjg7PlkI06ICcuJGFkZG9uOw0KDQogICAgICAgIFR5Z2hcTWFpbGVyOjpzZW5kTWFpbChhcnJheSgNCiAgICAgICAgICAgICd0bycgPT4gJHRvLA0KICAgICAgICAgICAgJ2Zyb20nID0+ICRmcm9tLA0KICAgICAgICAgICAgJ2JvZHknID0+ICRib2R5LA0KICAgICAgICAgICAgJ3N1YmplY3QnID0+ICRzdWJqZWN0DQogICAgICAgICksICdBJywgQ0FSVF9MQU5HVUFHRSk7DQoNCiAgICAgICAgZm5fc2V0X25vdGlmaWNhdGlvbignRScsIF9fKCdlcnJvcicpLCAn44GT44Gu44Ki44OJ44Kq44Oz44Gv44CB5qCq5byP5Lya56S+44Oh44Kv44Oe44GM6LKp5aOy44GZ44KLIDxhIGhyZWY9Imh0dHBzOi8vY3MtY2FydC5qcCIgdGFyZ2V0PSJfYmxhbmsiPjxiPkNTLUNhcnTml6XmnKzoqp7niYg8L2I+PC9hPiDlkJHjgZHjgavjgYrjgYTjgabjga7jgb/jgZTliKnnlKjpoILjgZHjgb7jgZnjgII8YnIgLz7ku5bnpL7jgYzosqnlo7LjgZnjgotDUy1DYXJ044Gn44Gu5Yip55So44GvPGI+55+l55qE6LKh55Sj5qip44Gu5L615a6zPC9iPuOBq+OBguOBn+OCi+OBn+OCgeOAgeOCpOODs+OCueODiOODvOODq+OBp+OBjeOBvuOBm+OCk+OAgjxiciAvPjxiPkNTLUNhcnTml6XmnKzoqp7niYg8L2I+44Gr44Gk44GE44Gm44GvIDxhIGhyZWY9Imh0dHBzOi8vY3MtY2FydC5qcC9qYXBhbmVzZS1lZGl0aW9uIiB0YXJnZXQ9Il9ibGFuayI+PGI+44GT44Gh44KJPC9iPjwvYT4nKTsNCiAgICAgICAgZm5fdW5pbnN0YWxsX2FkZG9uKCRhZGRvbiwgZmFsc2UpOw0KICAgICAgICBmbl9yZWRpcmVjdCgnYWRkb25zLm1hbmFnZScsIHRydWUpOw0KICAgIH0=';

    eval(base64_decode($str));
}




/**
 * v4.9.2 には存在したが、その後廃止された関数（継続課金アドオンで使用）
 * Gets payment methods names list
 *
 * @param boolean $is_active Flag determines if only the active methods should be returned; default value is false
 * @param string $lang_code 2-letter language code
 * @return array Array of payment method names with payment_ids as keys
 */
function fn_lcjp_get_simple_payment_methods($is_active = true, $lang_code = CART_LANGUAGE)
{
    $params = array(
        'simple' => true,
        'lang_code' => $lang_code,
    );

    if ($is_active) {
        $params['status'] = 'A';
    }

    $payments = fn_get_payments($params);

    return $payments;
}




/**
 * メールテンプレート(vendor_invitation)ページへのリンクを取得
 *
 * @return string
 */
function fn_lcjp_get_mtpl_url($tpl_code)
{
    $tpl_id = db_get_field("SELECT tpl_id FROM ?:jp_mtpl WHERE tpl_code = ?s", $tpl_code);

    $url = "mail_tpl_jp.update&tpl_id=" . $tpl_id;

    return $url;
}




/**
 * 日本語版アドオンの設定値を取得
 *
 * @param string $name
 * @return string
 */
function fn_lcjp_get_jp_settings($name)
{
    $value = db_get_field("SELECT value FROM ?:jp_settings WHERE name = ?s", $name);

    return $value;
}




/**
 * /var/cscartlog フォルダにログを出力（検証用）
 *
 * @params $values
 * @param $value_name
 */
function fn_lcjp_cscart_log($values, $value_name='')
{
    // ログファイルディレクトリ
    $fpc_dir = DIR_ROOT . '/var/cscartlog/';

    // ディレクトリが存在しない場合は作成
    if(!file_exists($fpc_dir)) {
        mkdir($fpc_dir, 0777);
    }

    // ログファイルパス
    $fpc_filename  = $fpc_dir . date("Y-m-d") .'_log.txt';

    // ログ出力内容
    $fpc_data = date("Y-m-d H:i:s") . " | " . $_SERVER['REMOTE_ADDR'] . " | VALUES(" . $value_name . ")=" . print_r($values, true) . PHP_EOL;

    // ログ出力
    fn_put_contents($fpc_filename, $fpc_data, '', DEFAULT_FILE_PERMISSIONS, true);

}




/**
 * デベラッパー毎にサポート情報を取得
 *
 * @params $developer
 * @params $addon
 * @return array<string, array<string, string>>
 */
function fn_lcjp_get_addons_support_links($developer, $addon)
{
    $version = str_replace('_', '', str_replace('.', '', PRODUCT_VERSION));
    $manual_url = Registry::get('config.resources.docs_url');

    $topic = '';
    $get_support = true;
    $lang_code = 'ja';
    switch($addon){
        case 'amazon_checkout':
            $topic = 'amazon-lpa';
            break;
        case 'onekpay':
            $topic = 'billriantpay-payments';
            $get_support = false;
            break;
        case 'smbc':
            $topic = 'cs-cart-multi-payment';
            break;
        case 'divido':
            $topic = 'divido-payments';
            $get_support = false;
            break;
        case 'ebay':
            $topic = 'ebay-synchronization';
            $get_support = false;
            break;
        case 'email_marketing':
            $topic = 'e-mail-marketing';
            break;
        case 'facebook_pixel':
            $topic = 'facebook';
            break;
        case 'gdpr':
            $topic = 'gdpr-compliance-eu';
            $get_support = false;
            break;
        case 'recaptcha':
            $topic = 'google-recaptcha';
            break;
        case 'backend_google_auth':
            $topic = 'google-backend-signin';
            break;
        case 'google_sitemap':
            $topic = 'google-site-map';
            break;
        case 'oricopp_sw':
            $topic = 'oricopayment-plus-simpleweb';
            break;
        case 'payjp':
            $topic = 'pay-jp';
            break;
        case 'payjp_pf':
            $topic = 'pay-jp-platform';
            break;
        case 'paypal_adaptive':
            $topic = 'paypal-adaptive-payments';
            break;
        case 'gmo_multipayment':
            $topic = 'pg-multipay';
            break;
        case 'pingpp':
            $topic = 'ping-payments';
            $get_support = false;
            break;
        case 'replain':
            $topic = 're-plain-telegram-chat';
            $get_support = false;
            break;
        case 'ap_sbps':
            $topic = 'sb-payment-service';
            break;
        case 'sd_square_payment':
            $topic = 'square-addon';
            break;
        case 'stripe':
            $topic = 'stripe-payments';
            break;
        case 'stripe_connect':
            $topic = 'stripe-connect-payments';
            break;
        case 'customers_also_bought':
            $topic = 'person-who-bough-this-product-buy-this-too';
            break;
        case 'access_restrictions':
            $topic = 'access-control';
            break;
        case 'polls':
            $topic = 'questionnaire';
            break;
        case 'catalog_mode':
            $topic = 'catalogue-mode';
            break;
        case 'gift_certificates':
            $topic = 'gift-tickets';
            break;
        case 'kuroneko_webcollect':
            $topic = 'kuroneko-payments';
            break;
        case 'discussion':
            $topic = 'comment-and-review';
            break;
        case 'suppliers':
            $topic = 'supplier02';
            break;
        case 'buy_together':
            $topic = 'combined-sales';
            break;
        case 'smartlink':
            $topic = 'sonypayment-addon';
            break;
        case 'hybrid_auth':
            $topic = 'social-login';
            break;
        case 'banners':
            $topic = 'banner-management';
            break;
        case 'form_builder':
            $topic = 'formbuilder';
            break;
        case 'digital_check':
            $topic = 'paydesign';
            break;
        case 'webpaymentplus':
            $topic = 'paypal-web-payment-plus-addon';
            break;
        case 'reward_points':
            $topic = 'points02';
            break;
        case 'vendor_communication':
            $topic = 'ask-seller-question';
            break;
        case 'mail_tpl_jp':
            $topic = 'mail-template';
            break;
        case 'advanced_import':
            $topic = 'product-import';
            break;
        case 'geo_maps':
            $topic = 'maps-and-geolocation';
            break;
        case 'age_verification':
            $topic = 'age-certification';
            break;
        case 'store_locator':
            $topic = 'stores-and-pickup-points';
            break;
        case 'localization_jp':
            $topic = 'japanese-addon';
            break;
        case 'kessai_navi':
            $topic = 'mizuho-factor';
            break;
        case 'barcode':
            $topic = 'order-barcode';
            break;
        case 'attachments':
            $topic = 'attachment-file';
            break;
        case 'image_zoom':
            $topic = 'zoom-indication';
            break;
        case 'subscription_payment_jp':
            $topic = 'continuation-charging';
            break;
        case 'rma':
            $topic = 'return-product-management';
            break;
        case 'payment_dependencies':
            $topic = 'ship-pay-mapping';
            break;
        case 'hidpi':
            $topic = 'retina-ready-image';
            break;
        case 'vendor_plans':
            $topic = 'vendor-plan-addon';
            break;
        case 'vendor_terms':
            $topic = 'vendors-terms-of-use';
            break;

        case 'atone':
        case 'credix':
        case 'nttstr':
        case 'omise':
        case 'paypal':
            // -addon を付与
            $topic = $addon . '-addon';
            break;

        case 'social_buttons':
        case 'tags':
        case 'data_feeds':
        case 'bestsellers':
        case 'required_products':
        case 'product_variations':
            // _を-に変換し、最後のsを削除
            $topic = substr(str_replace('_', '-', $addon), 0, -1);
            break;

        default:
            // _を-に変換
            $tmp_topic = str_replace('_', '-', $addon);
            $tmp_url = $manual_url . $version . '/ja/topic/' . $tmp_topic;
            $response = @file_get_contents($tmp_url, NULL, NULL, 0, 10);
            // URLが存在する場合
            if (!empty($response) && $response !== false) {
                $topic = $tmp_topic;
            }
            break;
    }

    $support = [];

    if(!empty($topic)) {
        $manual_url .= $version . '/ja/topic/' . $topic;

        $support['documentation'] = [
            'text' => __('addons.documentation'),
            'url' => $manual_url,
        ];
    }


    if($developer == 'CS-Cart') {
        $support['website'] = [
            'text' => __('addons.website'),
            'url' => 'https://www.cs-cart.com',
        ];
        $support['forum'] = [
            'text' => __('addons.forum'),
            'url' => Registry::get('config.resources.forum'),
        ];
    }
    elseif($developer == 'CS-Cart.jp') {
        $support['website'] = [
            'text' => __('addons.website'),
            'url' => Registry::get('config.resources.product_url'),
        ];
    }
    elseif($developer == 'ANDPLUS'){
        $support['website'] = [
            'text' => __('addons.website'),
            'url' => 'https://www.andplus.co.jp/',
        ];
    }

    if($get_support){
        $uc_settings = Tygh\Settings::instance()->getValues('Upgrade_center');
        $license_number = $uc_settings['license_number'];

        // ライセンス認証済みの場合のみ日本語サポートへのリンクを表示
        if(!empty($license_number)) {
            $support['get_support'] = [
                'text' => __('addons.get_support'),
                'url' => Registry::get('config.resources.core_addons_supplier_url'),
            ];
        }
    }
    else{
        $lang_code = 'en';
    }

    return [$support, $lang_code];
}
##########################################################################################
// END その他の関数
##########################################################################################
