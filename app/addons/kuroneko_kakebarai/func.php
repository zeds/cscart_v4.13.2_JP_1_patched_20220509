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

// $Id: func.php by takahashi from cs-cart.jp 2018

use Tygh\Http;
use Tygh\Registry;
use Tygh\Mailer;
use Tygh\Settings;
use Tygh\Languages\Languages;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################
/**
 * クロネコ掛け払いが利用可能で無い場合は支払い方法を表示しない
 *
 * @param $cart
 * @param $payment_methods
 * @param $completed_steps
 */
function fn_kuroneko_kakebarai_checkout_select_default_payment_method(&$cart, &$payment_methods, &$completed_steps)
{
    $user_id = Tygh::$app['session']['auth']['user_id'];

    // ユーザーログイン中でない
    // または、「クロネコ掛け払い取引先」のユーザーグループに所属してない
    // または、支払方法にクロネコ掛け払い（有効）が登録されていない
    // または、利用状況が「利用可」でない場合
    if(!$user_id
        || $user_id == 0
        || !fn_kuroneko_kakebarai_is_kakebarai_user($user_id)
        || !fn_kuroneko_kakebarai_is_payment_registered('A')
        || fn_krnkkb_check_use_Usable() != __("jp_kuroneko_kakebarai_use_usable_ok")) {

        // スクリプトファイル名をセット
        $processor_scripts = array('kuroneko_kakebarai.php');

        // 指定した決済方法を利用する支払方法を抽出
        $processor_ids = fn_kuroneko_kakebarai_get_processor_ids($processor_scripts);
        $kakebarai_payments = db_get_field("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);

        foreach($payment_methods as $key => $value){
            // クロネコ掛け払いの支払い方法を削除
            unset($payment_methods[$key][$kakebarai_payments]);
        }
   }
}




/**
 * 出荷情報登録
 *
 * @param $shipment_data
 * @param $order_info
 * @param $group_key
 * @param $all_products
 */
function fn_kuroneko_kakebarai_create_shipment(&$shipment_data, &$order_info, &$group_key, &$all_products)
{
    // ヤマトフィナンシャル系の決済方法でない場合は以下の処理は実施しない
    if( !fn_krnkkb_is_pay_by_kuroneko($order_info) ) return false;
    // クロネコヤマトに追跡番号を送信しない場合
    if( $shipment_data['send_slip_no'] != 'Y' ) {
        // do nothing

    // その他の場合
    }else{
        // 出荷情報登録
        $is_registered = fn_krnkkb_add_shipment($shipment_data, $order_info);

        // 出荷情報登録に失敗した場合、配送情報の新規作成は行わず、注文詳細ページにリダイレクトする
        if( !$is_registered ){
            fn_redirect("orders.details?order_id=" . $order_info['order_id']);
        }
    }
}




/**
 * クロネコ掛け払いの取引状況ページにおける注文情報の抽出・表示
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_kuroneko_kakebarai_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    if( Registry::get('runtime.mode') != 'manage' ) return false;

    // クロネコ掛け払いの場合
    if(Registry::get('runtime.controller') == 'krnkkb_manager') {
        $processor_scripts = array('kuroneko_kakebarai.php');
    }
    else {
        return false;
    }

    // 指定した支払方法を利用した注文を抽出するクエリを追加
    $processor_ids = fn_krnkkb_get_processor_ids($processor_scripts);
    $krnkkb_payments = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);
    $krnkkb_payments = implode(',', $krnkkb_payments);
    $condition .= " AND ?:orders.payment_id IN ($krnkkb_payments)";

    // 各注文にひもづけられた取引状況コードを抽出
    $fields[] = "?:jp_krnkkb_status.status_code as krnk_status_code";
    $join .= " LEFT JOIN ?:jp_krnkkb_status ON ?:jp_krnkkb_status.order_id = ?:orders.order_id";

    // 取引状況のソートに対応
    $sortings['krnkkb_status_code'] = "?:jp_krnkkb_status.status_code";
}




/**
 * ヤマトフィナンシャル系の決済方法を利用した注文の場合に、注文データにフラグをセット
 *
 * @param $order
 * @param $additional_data
 */
function fn_kuroneko_kakebarai_get_order_info(&$order, &$additional_data)
{
    // ヤマトフィナンシャル系の決済方法を利用した注文であるかを判定
    $is_pay_by_kuroneko = fn_krnkkb_is_pay_by_kuroneko($order);
    if( !empty($is_pay_by_kuroneko) ){
        if( $is_pay_by_kuroneko == 'kuroneko_kakebarai.php'){
            $order['pay_by_kuroneko_kakebarai'] = 'Y';
        }
    }
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
function fn_kuroneko_kakebarai_save_log(&$type, &$action, &$data, &$user_id, &$content, &$event_type, &$object_primary_keys)
{
    if($type == 'requests'){
        $url = $data['url'];
        switch($url){
            // 決済登録API（テスト環境）
            case KRNKKB_TEST_URL_AN010API:
            // 決済取消API（テスト環境）
            case KRNKKB_TEST_URL_AN020API:
            // 出荷登録API（テスト環境）
            case KRNKKB_TEST_URL_AN030API:
            // 出荷取消API（テスト環境）
            case KRNKKB_TEST_URL_AN040API:
            // 利用金額照会API（テスト環境）
            case KRNKKB_TEST_URL_AN050API:
            // 取引先登録API（テスト環境）
            case KRNKKB_TEST_URL_AN060API:
            // 審査状況照会API（テスト環境）
            case KRNKKB_TEST_URL_AN070API:
            // 決済登録API（本番環境）
            case KRNKKB_LIVE_URL_AN010API:
            // 決済取消API（本番環境）
            case KRNKKB_LIVE_URL_AN020API:
            // 出荷登録API（本番環境）
            case KRNKKB_LIVE_URL_AN030API:
            // 出荷取消API（本番環境）
            case KRNKKB_LIVE_URL_AN040API:
            // 利用金額照会API（本番環境）
            case KRNKKB_LIVE_URL_AN050API:
            // 取引先登録API（本番環境）
            case KRNKKB_LIVE_URL_AN060API:
            // 審査状況照会API（本番環境）
            case KRNKKB_LIVE_URL_AN070API:
                $content['request'] = 'Hidden for Security Reason';
                $content['response'] = 'Hidden for Security Reason';
                break;

            default:
                // do nothing
        }
    }
}
##########################################################################################
// END フックポイントで動作する関数
##########################################################################################





##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################

// アドオンのインストール時の動作
function fn_kuroneko_kakebarai_addon_install()
{
    fn_lcjp_install('kuroneko_kakebarai');

    // 「クロネコ掛け払い取引先」ユーザーグループの登録
    $_data = array(
        'type' => 'C',
        'status' => 'A'
    );

    $usergroup_id = db_query("INSERT INTO ?:usergroups ?e", $_data);

    $_data = array(
        'usergroup_id' => $usergroup_id,
        'usergroup' => __("jp_kuroneko_kakebarai_usergroup_name"),
    );

    foreach (Languages::getAll(false) as $_data['lang_code'] => $v) {
        db_query("INSERT INTO ?:usergroup_descriptions ?e", $_data);
    }
}




/**
 * アドオンのアンインストール時に実行する処理
 *
 */
function fn_kuroneko_kakebarai_addon_uninstall()
{
    // クロネコwebコレクト、クロネコ代金後払いサービスに関する支払方法を削除
    $file_list = array('kuroneko_kakebarai.php');

    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN (?a)))", $file_list);
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN (?a))", $file_list);
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN (?a)", $file_list);

    // 「クロネコ掛け払い取引先」ユーザーグループの削除
    $usergroup_id = fn_kuroneko_kakebarai_get_kakebarai_usergroup_id();
    db_query("DELETE FROM ?:usergroups WHERE usergroup_id = ?i", $usergroup_id);
    db_query("DELETE FROM ?:usergroup_descriptions WHERE usergroup_id = ?i", $usergroup_id);
    db_query("DELETE FROM ?:usergroup_links WHERE usergroup_id = ?i", $usergroup_id);
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################




##########################################################################################
// 共通の処理 BOF
##########################################################################################
/**
 * 各決済代行サービスで使用するスクリプトファイル名から決済代行サービスID（processor_id)を取得
 *
 * @param $processor_scripts
 * @return array
 */
function fn_kuroneko_kakebarai_get_processor_ids($processor_scripts)
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




/**
 * 指定した決済方法を利用する支払方法が登録されているかを判定する
 *
 * @param $status
 * @return bool
 */
function fn_kuroneko_kakebarai_is_payment_registered($status = null)
{
    // スクリプトファイル名をセット
    $processor_scripts = array('kuroneko_kakebarai.php');

    if(!empty($status)){
        $condition = db_quote(" AND status = ?s", $status);
    }

    // 指定した決済方法を利用する支払方法を抽出
    $processor_ids = fn_kuroneko_kakebarai_get_processor_ids($processor_scripts);
    $kakebarai_payments = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)" . $condition, $processor_ids);

    // 指定した決済方法を利用する支払方法が存在する場合、trueを返す
    if( !empty($kakebarai_payments) ){
        return true;
    }else{
        return false;
    }
}




/**
 * 指定した決済方法の接続設定モードを取得する
 *
 * @param $type
 * @return bool
 */
function fn_kuroneko_kakebarai_get_mode($type)
{
    // 指定した決済方法に対応するスクリプトファイル名をセット
    switch($type){
        // クロネコ代金後払いサービス
        case 'kb':
            $processor_scripts = array('kuroneko_kakebarai.php');
            break;

        // その他
        default:
            return false;
    }

    // 指定した決済方法を利用する支払方法を抽出
    $processor_ids = fn_kuroneko_kakebarai_get_processor_ids($processor_scripts);
    $processor_params_tmp = db_get_field("SELECT processor_params FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);

    // アンシリアル化
    $processor_params = unserialize($processor_params_tmp);

    return $processor_params['mode'];
}




/**
 * 「クロネコ掛け払い取引先」ユーザーグループIDの取得
 *
 * @return int
 */
function fn_kuroneko_kakebarai_get_kakebarai_usergroup_id()
{
    // 「クロネコ掛け払い取引先」ユーザーグループIDの取得
    $usergroup_id = db_get_field("SELECT distinct usergroup_id FROM ?:usergroup_descriptions WHERE usergroup =?s", __("jp_kuroneko_kakebarai_usergroup_name"));

    return $usergroup_id;
}




/**
 * ユーザが「クロネコ掛け払い取引先」ユーザーグループに所属するか確認
 *
 * @param $user_id
 * @return bool
 */
function fn_kuroneko_kakebarai_is_kakebarai_user($user_id)
{
    // 「クロネコ掛け払い取引先」ユーザーグループIDの取得
    $usergroup_id = fn_kuroneko_kakebarai_get_kakebarai_usergroup_id();

    // 該当のユーザーグループデータのステータスを取得
    $status = db_get_field("SELECT status FROM ?:usergroups WHERE usergroup_id = ?i", $usergroup_id);

    // ステータスが有効で無い場合
    if( $status != 'A' ){
        return false;
    }

    // 該当のユーザーグループとユーザーの有効な紐づけを取得
    $link_id = db_get_field("SELECT link_id FROM ?:usergroup_links WHERE status = ?s AND user_id = ?i AND usergroup_id = ?i", 'A', $user_id, $usergroup_id);

    // クロネコ掛け払い取引先ユーザーグループのお客様のみ使用可能の設定を取得
    $user_kakebarai_usergroup = Registry::get('addons.kuroneko_kakebarai.use_usergroup');

    // データが存在する場合
    if( !empty($link_id) || $user_kakebarai_usergroup == 'N' ){
        $result = true;
    }
    // データが存在しない場合
    else {
        $result = false;
    }

    return $result;
}




/**
 * エラーメッセージを表示
 *
 * @param $result
 * @param $title
 * @param $extra
 * @return bool
 */
function fn_kuroneko_kakebarai_set_err_msg($result, $title = null, $extra = null)
{
    // エラーコードが存在しない場合、処理を終了
    if( empty($result['errorCode']) ) return false;

    $error_code = $result['errorCode'];

    // エラーメッセージのタイトルが指定されていない場合は既定のタイトルをセット
    if( empty($title) ) $title = __('jp_kuroneko_kakebarai_error');

    $message = fn_kuroneko_kakebarai_get_err_msg($error_code) . ' (' . $error_code . ')';
    if( !empty($extra) ){
        $message .= $extra;
    }

    fn_set_notification('E', $title, $message);
}




/**
 * エラーメッセージを取得
 *
 * @param $error_code
 * @return string
 */
function fn_kuroneko_kakebarai_get_err_msg($error_code)
{
    // エラーコードを大文字に変換
    $error_code = strtoupper($error_code);

    $err_msg = __('jp_kuroneko_kakebarai_errmsg_' . strtoupper($error_code));

    // エラーコードに対応する言語変数が存在しない場合、汎用メッセージをセット
    if( strpos($err_msg, 'jp_kuroneko_kakebarai_errmsg_') === 0 || strpos($err_msg, 'jp_kuroneko_kakebarai_errmsg_') > 0) {
        $err_msg = __('jp_kuroneko_kakebarai_failed');
    }

    // エラーメッセージを返す
    return $err_msg;
}




/**
 * クロネコwebコレクトに送信するパラメータをセット
 *
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_krnkkb_get_params($type, $order_id, $order_info)
{
    // ユーザーIDを取得
    $user_id = $order_info['user_id'];

    // 送信パラメータを初期化
    $params = array();

    // 処理別に異なるパラメータをセット
    switch($type) {
        // 決済登録API
        case '10':

            // 加盟店コード
            $params['traderCode'] = Registry::get('addons.kuroneko_kakebarai.trader_code');

            // パスワード
            $params['passWord'] = Registry::get('addons.kuroneko_kakebarai.password');

            // 受注日
            $params['orderDate'] = date('Ymd');

            // 受注番号
            $params['orderNo'] = $order_id;

            // 取引先（会員）ID
            $params['buyerId'] = fn_krnkkb_get_buyer_id($user_id);

            // 決済金額
            $params['settlePrice'] = round($order_info['total'] - $order_info['tax_subtotal']);

            // 消費税
            $tax = 0;
            foreach ($order_info['taxes'] as $taxes) {
                $tax += $taxes['tax_subtotal'];
            }
            $params['shohiZei'] = round($tax);

            // 明細有無(有)
            $params['meisaiUmu'] = 1;

            // 明細情報
            // 商品明細
            $item_count = 1;
            foreach ($order_info['products'] as $product) {
                $params['shohinMei' . $item_count] = mb_strimwidth($product['product'], 0, KRNKKB_MAX_LEN_SHOHINMEI, '', 'utf-8');
                $params['shohinCode' . $item_count] = fn_kuroneko_kakebarai_get_product_code($product['product_code']);
                $params['suryo' . $item_count] = $product['amount'];
                $params['tanka' . $item_count] = round($product['price']);
                $params['kessaiKingaku' . $item_count] = round($product['subtotal']);
                $params['shohiZei' . $item_count] = 0;

                $item_count += 1;
            }

            // 送料
            if ($order_info['shipping_cost'] > 0){
                $params['shohinMei' . $item_count] = __("shipping_cost");
                $params['shohinCode' . $item_count] = "SHIPPING";
                $params['kessaiKingaku' . $item_count] = round($order_info['shipping_cost']);
                $params['shohiZei' . $item_count] = 0;

                $item_count += 1;
            }

            // ご注文割引
            if ($order_info['subtotal_discount'] > 0){
                $params['shohinMei' . $item_count] = __("order_discount");
                $params['shohinCode' . $item_count] = "DISCOUNT";
                $params['kessaiKingaku' . $item_count] = round($order_info['subtotal_discount']) * -1;
                $params['shohiZei' . $item_count] = 0;

                $item_count += 1;
            }

            // 支払手数料
            if ($order_info['payment_surcharge'] > 0){
                $params['shohinMei' . $item_count] = empty($order_info['payment_method']['surcharge_title']) ? __("payment_surcharge") : $order_info['payment_method']['surcharge_title'];
                $params['shohinCode' . $item_count] = "SURCHARGE";
                $params['kessaiKingaku' . $item_count] = round($order_info['payment_surcharge']);
                $params['shohiZei' . $item_count] = 0;

                $item_count += 1;
            }

            // 消費税
            if ($tax > 0){
                // 税金の表示順をソート
                ksort($order_info['taxes']);

                $codecnt = 1;
                foreach($order_info['taxes'] as $taxes) {
                    $params['shohinMei' . $item_count] = $taxes['description'];
                    $params['shohinCode' . $item_count] = "TAX" . $codecnt;
                    $params['kessaiKingaku' . $item_count] = 0;
                    $params['shohiZei' . $item_count] = round($taxes['tax_subtotal']);

                    $item_count += 1;
                    $codecnt += 1;
                }
            }

            // カート社識別コード
            $params['cartCode'] = 'cscart';

            break;

        // 決済取消API
        case '20':

            // 加盟店コード
            $params['traderCode'] = Registry::get('addons.kuroneko_kakebarai.trader_code');

            // パスワード
            $params['passWord'] = Registry::get('addons.kuroneko_kakebarai.password');

            // 受注番号
            $params['orderNo'] = $order_id;

            // 取引先（会員）ID
            $params['buyerId'] = fn_krnkkb_get_buyer_id($user_id);

            break;

        // 出荷登録API
        case '30':

            // 加盟店コード
            $params['traderCode'] = Registry::get('addons.kuroneko_kakebarai.trader_code');

            // パスワード
            $params['passWord'] = Registry::get('addons.kuroneko_kakebarai.password');

            // 受注番号
            $params['orderNo'] = $order_id;

            // 送り状番号
            $params['slipNo'] = $order_info['slip_no'];

            break;

        // 出荷取消API
        case '40':

            // 加盟店コード
            $params['traderCode'] = Registry::get('addons.kuroneko_kakebarai.trader_code');

            // パスワード
            $params['passWord'] = Registry::get('addons.kuroneko_kakebarai.password');

            // 受注番号
            $params['orderNo'] = $order_id;

            // 送り状番号
            $params['slipNo'] = $order_info['slip_no'];

            break;

        // 利用金額照会API
        case '50':

            // 加盟店コード
            $params['traderCode'] = Registry::get('addons.kuroneko_kakebarai.trader_code');

            // パスワード
            $params['passWord'] = Registry::get('addons.kuroneko_kakebarai.password');

            // 取引先（会員）ID
            $params['buyerId'] = fn_krnkkb_get_buyer_id($user_id);

            break;

        // 取引先登録API
        case '60':

            // 加盟店コード
            $params['traderCode'] = Registry::get('addons.kuroneko_kakebarai.trader_code');

            // パスワード
            $params['passWord'] = Registry::get('addons.kuroneko_kakebarai.password');

            // 取引先（会員）ID
            $params['cId'] = fn_krnkkb_get_buyer_id($user_id, true);

            break;

        // 審査状況照会API
        case '70':

            // 加盟店コード
            $params['traderCode'] = Registry::get('addons.kuroneko_kakebarai.trader_code');

            // パスワード
            $params['passWord'] = Registry::get('addons.kuroneko_kakebarai.password');

            // 取引先（会員）ID
            $params['buyerId'] = fn_krnkkb_get_buyer_id($user_id);

            break;

        default:
            // do nothing
            break;
    }

    return $params;
}




/**
 * クロネコ掛け払いに各種データを送信
 *
 * @param $type
 * @param $params
 * @return mixed|string
 */
function fn_krnkkb_send_request($type, $params)
{
    // データ送信先URLと結果パラメータを初期化
    $target_url = '';
    $result = '';

    // 接続環境を取得
    $mode = fn_kuroneko_kakebarai_get_mode("kb");

    switch($type){
        // 決済登録API
        case '10':
            if($mode == 'test'){
                $target_url = KRNKKB_TEST_URL_AN010API;
            }else{
                $target_url = KRNKKB_LIVE_URL_AN010API;
            }
            break;

        // 決済取消API
        case '20':
            if($mode == 'test'){
                $target_url = KRNKKB_TEST_URL_AN020API;
            }else{
                $target_url = KRNKKB_LIVE_URL_AN020API;
            }
            break;

        // 出荷登録API
        case '30':
            if($mode == 'test'){
                $target_url = KRNKKB_TEST_URL_AN030API;
            }else{
                $target_url = KRNKKB_LIVE_URL_AN030API;
            }
            break;

        // 出荷取消API
        case '40':
            if($mode == 'test'){
                $target_url = KRNKKB_TEST_URL_AN040API;
            }else{
                $target_url = KRNKKB_LIVE_URL_AN040API;
            }
            break;

        // 利用金額照会API
        case '50':
            if($mode == 'test'){
                $target_url = KRNKKB_TEST_URL_AN050API;
            }else{
                $target_url = KRNKKB_LIVE_URL_AN050API;
            }
            break;

        // 取引先登録APII
        case '60':
            if($mode == 'test'){
                $target_url = KRNKKB_TEST_URL_AN060API;
            }else{
                $target_url = KRNKKB_LIVE_URL_AN060API;
            }
            break;

        // 審査状況照会API
        case '70':
            if($mode == 'test'){
                $target_url = KRNKKB_TEST_URL_AN070API;
            }else{
                $target_url = KRNKKB_LIVE_URL_AN070API;
            }
            break;

        // その他
        default:
            // do nothing
    }

    // 送信先URLが指定されている場合
    if( !empty($target_url) ){
        $result = Http::post($target_url, $params);

        // 受信した結果情報を配列に格納
        if( !empty($result) ){
            $result = fn_krnkkb_get_result_array($result);
        }
    }

    return $result;
}




/**
 * クロネコ掛け払いからの戻り値を配列化
 *
 * @param $result
 * @return array
 */
function fn_krnkkb_get_result_array($result)
{
    $result = get_object_vars(@simplexml_load_string($result));
    return $result;
}




/**
 * ユーザープロファイルからカナ名を取得
 *
 * @param $user_info
 * @param $name_type
 * @return string
 */
function fn_krnkkb_get_user_kana_name($user_info, $name_type)
{
    switch ($name_type)
    {
        // ファーストネーム
        case "F":
            $class = "last-name-kana";
            break;

        // ラストネーム
        case "L":
            $class = "first-name-kana";
            break;
    }

    // field_id を取得
    $field_id = db_get_field("SELECT field_id FROM ?:profile_fields WHERE section = 'C' AND class = ?s", $class);

    $kana_name = $user_info['fields'][$field_id];

    return $kana_name;
}




/**
 * 取引先（会員）IDを取得
 *
 * @param $user_id
 * @param $is_renew
 * @return string
 */
function fn_krnkkb_get_buyer_id($user_id, $is_renew = false)
{
    $buyer_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE payment_method = ?s AND user_id = ?i", 'kuroneko_kakebarai', $user_id);

    if( empty($buyer_id) ) {
        $sequence = "00";
        $buyer_id = sprintf('%010d', $user_id) . $sequence;
    }
    else{
        if($is_renew) {
            $sequence_int = intval(substr($buyer_id, -2, 2)) + 1;
            $sequence = sprintf('%02d', $sequence_int);
            $buyer_id = sprintf('%010d', $user_id) . $sequence;
        }
    }

    // テスト環境テスト用
    $user_info = fn_get_user_info($user_id);
    switch ($user_info['email']){
        case '1111@cs-cart.jp':
            $buyer_id = '1111';
            break;
        case '2222@cs-cart.jp':
            $buyer_id = '2222';
            break;
        case '3333@cs-cart.jp':
            $buyer_id = '3333';
            break;
        case '4444@cs-cart.jp':
            $buyer_id = '4444';
            break;
    }

    return $buyer_id;
}




/**
 * 項目値のフォーマット
 *
 * @param $params
 * @return array
 */
function fn_krnkkb_format_field_value($params)
{
    foreach($params as $key => $value){

        // 全角文字
        foreach(KRNKKB_FULL_CHAR as $fckey => $fcvalue){
            if( $key == $fckey ){
                $params[$fckey] = mb_strimwidth(mb_convert_kana($params[$fckey], "KVANRS"), 0, $fcvalue, "", "utf-8");
                break;
            }
        }

        // 半角文字
        foreach(KRNKKB_HALF_CHAR as $hckey => $hcvalue){
            if( $key == $hckey ){
                $params[$hckey] = mb_strimwidth(mb_convert_kana($params[$hckey], "kanrs"), 0, $hcvalue, "", "utf-8");
                break;
            }
        }

        // 半角数字
        foreach(KRNKKB_HALF_NUM as $hnkey => $hnvalue){
            if( $key == $hnkey ){
                $params[$hnkey] = str_replace('-', '', $params[$hnkey]);
                $params[$hnkey] = mb_strimwidth(mb_convert_kana($params[$hnkey], "n"), 0, $hnvalue, "", "utf-8");
                break;
            }
        }

        // 事業内容2
        if( $key == 'gyscod2' ){
            $params[$key] = substr($params[$key], 2, 2);
        }
    }

    return $params;
}




/**
 * 掛け払い審査状況を確認
 *
 * @return string
 */
function fn_krnkkb_check_judge_status()
{
    // 掛け払い審査状況を確認
    $type = "70";
    $order_info['user_id'] = Tygh::$app['session']['auth']['user_id'];
    $params = array();
    $params = fn_krnkkb_get_params($type, null, $order_info);

    $result = fn_krnkkb_send_request($type, $params);

    // テスト環境テスト用
    /*
    $user_id = Tygh::$app['session']['auth']['user_id'];
    $user_info = fn_get_user_info($user_id);
    switch ($user_info['email']){
        case '1111@cs-cart.jp':
        case '2222@cs-cart.jp':
        case '3333@cs-cart.jp':
        case '4444@cs-cart.jp':
        $result['judgeStatus'] = __("jp_kuroneko_kakebarai_judge_status_ok");

        break;
    }*/

    return $result['judgeStatus'];
}




/**
 * 掛け払い利用状況を確認
 *
 * @return string
 */
function fn_krnkkb_check_use_Usable()
{
    // 掛け払い利用状況を確認
    $type = "50";
    $order_info['user_id'] = Tygh::$app['session']['auth']['user_id'];
    $params = array();
    $params = fn_krnkkb_get_params($type, null, $order_info);

    $result = fn_krnkkb_send_request($type, $params);

    if( $result['returnCode'] != 0 && $result['errorCode'] != 'E050003') {
        fn_kuroneko_kakebarai_set_err_msg($result);
        return false;
    }

    return $result['useUsable'];
}




/**
 * 決済取消依頼
 *
 * @param $order_id
 * @param bool|true $notification
 * @return bool
 */
function fn_krnkkb_cancel($order_id)
{
    // 支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkkb_get_processor_data_by_order_id($order_id);

    // クロネコ掛け払い以外の支払方法による注文は処理を終了
    if( $processor_data['processor_script'] != 'kuroneko_kakebarai.php' ) return false;

    // クロネコ掛け払いに送信するパラメータをセット
    $type = "20";
    $order_info = fn_get_order_info($order_id);
    $params = array();
    $params = fn_krnkkb_get_params($type, $order_id, $order_info);

    // 決済取消依頼
    $result = fn_krnkkb_send_request($type, $params);

    // 決済取消依頼に関するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 決済取消依頼が正常に終了している場合
        if ( $return_code == 0 ) {
            // 取引状況にKB_2（取消）をDBに反映
            $ab_status_code = 'KB_2';
            db_query("UPDATE ?:jp_krnkkb_status SET status_code = ?s WHERE order_id = ?i", $ab_status_code, $order_id);

            fn_set_notification('N', __('notice'), __('jp_kuroneko_kakebarai_cancel_order', array('order_id' => $order_id)));

            // 決済取消依頼でエラーが発生した場合
        }else{
            // エラーメッセージを表示して終了
            fn_kuroneko_kakebarai_set_err_msg($result, null, ' - ' . __("jp_kuroneko_kakebarai_order_id", array('order_id' => $order_id)));
        }

        // 決済取消依頼に関するリクエスト送信が異常終了した場合
    }else{
        // 決済取消依頼失敗メッセージを表示して終了
        fn_set_notification('N', __('jp_kuroneko_kakebarai_error'), __('jp_kuroneko_kakebarai_cancel_error_msg'));
    }
}




/**
 * 注文IDから支払方法IDに紐付けられた決済代行サービスの情報を取得
 *
 * @param $order_id
 * @return bool
 */
function fn_krnkkb_get_processor_data_by_order_id($order_id)
{
    // 支払方法IDを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);

    // 支払方法IDに紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_get_processor_data($payment_id);

    // 決済に使用する支払方法に関する情報を返す
    if( !empty($processor_data) ){
        return $processor_data;
    }else{
        return false;
    }
}




/**
 * 取引状況を更新
 *
 * @param $order_id
 * @param $job_code
 * @param string $access_id
 * @param string $access_pass
 */
function fn_krnkkb_update_status_code($order_id, $status)
{
    $_data = array (
        'order_id' => $order_id,
        'status_code' => $status,
    );

    // 当該注文に関する取引状況関連レコードの存在チェック
    $is_exists = db_get_row("SELECT * FROM ?:jp_krnkkb_status WHERE order_id = ?i", $order_id);

    // 当該注文に関する取引状況関連レコードが存在する場合
    if( !empty($is_exists) ){
        // レコードを更新
        db_query("UPDATE ?:jp_krnkkb_status SET ?u WHERE order_id = ?i", $_data, $order_id);
        // 当該注文に関する取引状況関連レコードが存在しない場合
    }else{

        // レコードを新規追加
        db_query("REPLACE INTO ?:jp_krnkkb_status ?e", $_data);
    }
}




/**
 * ステータス名を取得
 *
 * @param $kb_status
 * @return string
 */
function fn_krnkkb_get_status_name($kb_status)
{
    // 取引状況コードに含まれる数値以外の値（例 : KB_）を削除
    $ab_status = mb_ereg_replace('[^0-9]', '', $kb_status);

    // 請求ステータスコードに応じて請求ステータス名を取得
    return __('jp_kuroneko_kakebarai_status_' . (int)$ab_status);
}




/**
 * 出荷情報取消
 *
 * @param $shipment_id
 * @return bool
 */
function fn_krnkkb_delete_shipment($shipment_id, $slip_no = null, $silent_mode = false)
{
    // 配送情報IDから注文IDを取得
    $order_id = db_get_field("SELECT order_id FROM ?:shipment_items WHERE shipment_id = ?i", $shipment_id);

    // 注文IDが存在する場合
    if( !empty($order_id) ){
        // 支払方法に紐付けられた決済代行サービスの情報を取得
        $processor_data = fn_krnkkb_get_processor_data_by_order_id($order_id);

        // 当該注文で使用した決済代行サービスに関する情報が存在する場合
        if( !empty($processor_data) ){

            // 送り状番号が指定されていない場合
            if( is_null($slip_no) ){
                // DBから送り状番号を取得
                $slip_no = db_get_field("SELECT tracking_number FROM ?:shipments WHERE shipment_id = ?i", $shipment_id);
            }
            // 送り状番号からハイフンを除去
            $slip_no = preg_replace("/-/", "", $slip_no);

            $order_info['slip_no'] = $slip_no;

            $type = "40";
            $params = array();
            $params = fn_krnkkb_get_params($type, $order_id, $order_info);

            // 出荷情報取消
            $result = fn_krnkkb_send_request($type, $params);

            // 出荷情報取消に関するリクエスト送信が正常終了した場合
            if (!empty($result)) {

                // 結果コード
                $return_code = $result['returnCode'];

                // 出荷情報取消が正常に終了している場合
                if ($return_code == 0) {
                    // 出荷情報取消完了メッセージを表示
                    if( !$silent_mode ) fn_set_notification('N', __("jp_kuroneko_kakebarai_name"), __('jp_kuroneko_kakebarai_shipment_delete_success', array('[slipno]' => $slip_no)));

                    // 出荷情報取消が異常終了した場合
                } else {
                    // エラーメッセージを表示
                    $error_code = $result['errorCode'];
                    if( !$silent_mode ) fn_set_notification('E', __("jp_kuroneko_kakebarai_error"), __('jp_kuroneko_kakabarai_shipment_delete_failed', array('[slipno]' => $slip_no)) . ' (' . $error_code . ')');
                }

                // 出荷情報取消に関するリクエスト送信が異常終了した場合
            } else {
                // 削除失敗メッセージを表示
                if( !$silent_mode ) fn_set_notification('N', __('jp_kuroneko_kakebarai_error'), __('jp_kuroneko_kakabarai_shipment_delete_faile'));
            }
        }
    }

    return false;
}




/**
 * ヤマトフィナンシャル系の決済方法を利用した注文であるかを判定
 *
 * @param $order
 * @return bool
 *
 */
function fn_krnkkb_is_pay_by_kuroneko($order)
{
    // 支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkkb_get_processor_data_by_order_id($order['order_id']);

    // 決済代行業者を使った決済の場合
    if( !empty($processor_data['processor_script']) ){

        // ヤマトフィナンシャル系の決済方法の場合は true を返す
        switch( $processor_data['processor_script'] ){
            case 'kuroneko_kakebarai.php':
                return $processor_data['processor_script'];
                break;
            default:
                // do noghing
        }
    }

    return false;
}




/**
 * 出荷情報登録
 *
 * @param $shipment_data
 * @param $order_info
 * @return bool
 */
function fn_krnkkb_add_shipment(&$shipment_data, $order_info)
{
    // 送り状データの送信を指定していない場合処理を終了
    if( $shipment_data['send_slip_no'] != 'Y' ) return false;

    // 送り情報番号を取得
    $slip_no = preg_replace("/-/", "", mb_convert_kana($shipment_data['tracking_number'],"a"));

    // 当該注文の支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkkb_get_processor_data_by_order_id($order_info['order_id']);
    $mode = $processor_data['processor_params']['mode'];

    // 受付番号を取得
    $order_no = $order_info['order_id'];

    // 受付番号が存在しない場合は処理を終了
    if( empty($order_no) ) return false;

    // 出荷情報登録判定フラグ
    $is_registered = true;

    $order_info['slip_no'] = $slip_no;
    $type = "30";
    $params = array();
    $params = fn_krnkkb_get_params($type, $order_no, $order_info);

    // 出荷情報登録
    $result = fn_krnkkb_send_request($type, $params);

    // 出荷情報登録に関するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 出荷情報登録が正常に終了している場合
        if ( $return_code == 0 ) {

            // 出荷情報登録でエラーが発生した場合
        }else{
            fn_kuroneko_kakebarai_set_err_msg($result, __('jp_kuroneko_kakebarai_shipment_entry_error'));
            $is_registered = false;
        }

        // 出荷情報登録に関するリクエスト送信が異常終了した場合
    }else{
        // 出荷情報登録失敗メッセージを表示
        fn_set_notification('N', __('jp_kuroneko_kakebarai_shipment_entry_error'), __('jp_kuroneko_kakebarai_shipment_entry_error_msg'));
        $is_registered = false;
    }

    return $is_registered;
}




/**
 * 各決済代行サービスで使用するスクリプトファイル名から決済代行サービスID（processor_id)を取得
 *
 * @param $processor_scripts
 * @return array
 */
function fn_krnkkb_get_processor_ids($processor_scripts)
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




/**
 * 取引先登録用パラメータの再設定
 *
 * @param $buyer
 * @return array
 */
function fn_krnkkb_prep_params($buyer)
{
    $result = array();

    /////////////////////////////////////////////////////////////////////////////////////
    // 取引先情報エリア BOF
    /////////////////////////////////////////////////////////////////////////////////////
    // 法人・個人事業主
    if( !empty($buyer['hjkjKbn']) ){
        $result['hjkjKbn'] = $buyer['hjkjKbn'];
    }
    // 法人格
    if( !empty($buyer['houjinKaku']) ){
        $result['houjinKaku'] = $buyer['houjinKaku'];
    }
    // 法人格前後
    if( !empty($buyer['houjinZengo']) || $buyer['houjinZengo'] == '0' ){
        $result['houjinZengo'] = $buyer['houjinZengo'];
    }
    // 取引先名(漢字)
    if( !empty($buyer['sMei']) ){
        $result['sMei'] = $buyer['sMei'];
    }
    // 支店名(漢字)
    $result['shitenMei'] = $buyer['shitenMei'];
    // 取引先名(カナ)
    if( !empty($buyer['sMeikana']) ){
        $result['sMeikana'] = $buyer['sMeikana'];
    }
    // 支店名(カナ)
    $result['shitenMeikana'] = $buyer['shitenMeikana'];
    // 郵便番号 住所（漢字）
    if( !empty($buyer['ybnNo']) ){
        $result['ybnNo'] = $buyer['ybnNo'];
        $result['Adress'] = $buyer['Adress_state'] . $buyer['Adress_city'] . $buyer['Adress_address'] . $buyer['Adress_address_2'];
    }
    // 電話番号(代表)
    if( !empty($buyer['telNo']) ){
        $result['telNo'] = $buyer['telNo'];
    }
    // 携帯電話番号
    if( !empty($buyer['keitaiNo']) ){
        $result['keitaiNo'] = $buyer['keitaiNo'];
    }
    // 事業内容1
    if( !empty($buyer['gyscod1']) ){
        $result['gyscod1'] = $buyer['gyscod1'];
    }
    // 事業内容2
    if( !empty($buyer['gyscod2']) ){
        $result['gyscod2'] = $buyer['gyscod2'];
    }
    // 設立年月
    if( !empty($buyer['setsurituNgt']) ){
        $result['setsurituNgt'] = $buyer['setsurituNgt'];
    }
    // 資本金
    if( !empty($buyer['shk']) ){
        $result['shk'] = $buyer['shk'];
    }
    // 年商
    if( !empty($buyer['nsyo']) ){
        $result['nsyo'] = $buyer['nsyo'];
    }
    // 従業員数
    if( !empty($buyer['kmssyainsu']) ){
        $result['kmssyainsu'] = $buyer['kmssyainsu'];
    }
    // 代表者名(漢字・姓)
    if( !empty($buyer['daikjmeiSei']) ){
        $result['daikjmeiSei'] = $buyer['daikjmeiSei'];
    }
    // 代表者名(漢字・名)
    if( !empty($buyer['daikjmeiMei']) ){
        $result['daikjmeiMei'] = $buyer['daikjmeiMei'];
    }
    // 代表者名(カナ・姓)
    if( !empty($buyer['daiknameiSei']) ){
        $result['daiknameiSei'] = $buyer['daiknameiSei'];
    }
    // 代表者名(カナ・名)
    if( !empty($buyer['daiknameiMei']) ){
        $result['daiknameiMei'] = $buyer['daiknameiMei'];
    }
    /////////////////////////////////////////////////////////////////////////////////////
    // 取引先情報エリア EOF
    /////////////////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////////////////
    // 代表者情報エリア BOF
    /////////////////////////////////////////////////////////////////////////////////////
    // 自宅郵便番号 自宅住所（漢字）
    if( !empty($buyer['daiYbnno']) ){
        $result['daiYbnno'] = $buyer['daiYbnno'];
        $result['daiAddress'] = $buyer['daiAddress_state'] . $buyer['daiAddress_city'] . $buyer['daiAddress_address'] . $buyer['daiAddress_address_2'];
    }
    /////////////////////////////////////////////////////////////////////////////////////
    // 代表者情報エリア EOF
    /////////////////////////////////////////////////////////////////////////////////////

    // 運営会社有無
    if( !empty($buyer['szUmu']) || $buyer['szUmu'] == '0' ){
        $result['szUmu'] = $buyer['szUmu'];
    }
    /////////////////////////////////////////////////////////////////////////////////////
    // 運営会社情報エリア BOF
    /////////////////////////////////////////////////////////////////////////////////////
    // 法人・個人事業主
    if( !empty($buyer['szHjkjKbn']) ){
        $result['szHjkjKbn'] = $buyer['szHjkjKbn'];
    }
    // 法人格
    if( !empty($buyer['szHoujinKaku']) && $buyer['szHjkjKbn'] == 1){
        $result['szHoujinKaku'] = $buyer['szHoujinKaku'];
    }
    // 法人格前後
    if( !empty($buyer['szHoujinZengo']) || $buyer['szHoujinZengo'] == '0'  && $buyer['szHjkjKbn'] == 1){
        $result['szHoujinZengo'] = $buyer['szHoujinZengo'];
    }
    // 運営会社名(漢字)
    if( !empty($buyer['szHonknjmei']) ){
        $result['szHonknjmei'] = $buyer['szHonknjmei'];
    }
    // 運営会社名(カナ)
    if( !empty($buyer['szHonknamei']) ){
        $result['szHonknamei'] = $buyer['szHonknamei'];
    }
    // 郵便番号 住所（漢字）
    if( !empty($buyer['szYbnno']) ){
        $result['szYbnno'] = $buyer['szYbnno'];
        $result['szAddress'] = $buyer['szAddress_state'] . $buyer['szAddress_city'] . $buyer['szAddress_address'] . $buyer['szAddress_address_2'];
    }
    // 電話番号（代表）
    if( !empty($buyer['szTelno']) ){
        $result['szTelno'] = $buyer['szTelno'];
    }
    // 代表者名(漢字・姓)
    if( !empty($buyer['szDaikjmei_sei']) ){
        $result['szDaikjmei_sei'] = $buyer['szDaikjmei_sei'];
    }
    // 代表者名(漢字・名)
    if( !empty($buyer['szDaikjmei_mei']) ){
        $result['szDaikjmei_mei'] = $buyer['szDaikjmei_mei'];
    }
    // 代表者名(カナ・姓)
    if( !empty($buyer['szDaiknamei_sei']) ){
        $result['szDaiknamei_sei'] = $buyer['szDaiknamei_sei'];
    }
    // 代表者名(カナ・名)
    if( !empty($buyer['szDaiknamei_mei']) ){
        $result['szDaiknamei_mei'] = $buyer['szDaiknamei_mei'];
    }
    /////////////////////////////////////////////////////////////////////////////////////
    // 運営会社情報エリア EOF
    /////////////////////////////////////////////////////////////////////////////////////

    // 送付先選択
    if( !empty($buyer['sqssfKbn']) ){
        $result['sqssfKbn'] = $buyer['sqssfKbn'];
    }
    /////////////////////////////////////////////////////////////////////////////////////
    // 請求書送付先情報エリア BOF
    /////////////////////////////////////////////////////////////////////////////////////
    // 郵便番号 住所（漢字）
    if( !empty($buyer['sqYbnno']) ){
        $result['sqYbnno'] = $buyer['sqYbnno'];
        $result['sqAddress'] = $buyer['sqAddress_state'] . $buyer['sqAddress_city'] . $buyer['sqAddress_address'] . $buyer['sqAddress_address_2'];
    }
    // 送付先名称
    if( !empty($buyer['sofuKnjnam']) ){
        $result['sofuKnjnam'] = $buyer['sofuKnjnam'];
    }
    // 担当者名
    if( !empty($buyer['sofuTntnam']) ){
        $result['sofuTntnam'] = $buyer['sofuTntnam'];
    }
    // 部署・役職
    if( !empty($buyer['syz']) ){
        $result['syz'] = $buyer['syz'];
    }
    // 電話番号
    if( !empty($buyer['kmsTelno']) ){
        $result['kmsTelno'] = $buyer['kmsTelno'];
    }
    /////////////////////////////////////////////////////////////////////////////////////
    // 請求書送付先情報エリア EOF
    /////////////////////////////////////////////////////////////////////////////////////

    // 支払方法
    if( !empty($buyer['shrhohKbn']) ){
        $result['shrhohKbn'] = $buyer['shrhohKbn'];
    }

    return $result;
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $krnkwc_exec_results
 * @param bool $flg_comments
 * @return bool
 */
function fn_krnkkb_format_payment_info($type, $order_id, $payment_info = array(), $results)
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

        $info['jp_kuroneko_kakebarai_return_date'] = $results['returnDate'];

        // エラーの場合
        if( $results['returnCode'] != 0 ){
            $info['jp_kuroneko_kakebarai_error_code'] = $results['errorCode'];
        }

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
 * 登録した取引IDをDBに登録
 *
 * @param $buyer_id
 */
function fn_krnkkb_register_buyer_info($buyer_id)
{
    $user_id = Tygh::$app['session']['auth']['user_id'];

    $_data = array('user_id' => $user_id,
        'payment_method' => 'kuroneko_kakebarai',
        'quickpay_id' => $buyer_id,
    );
    db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);
}



/**
 * 支払方法の設定により商品コードを取得する
 *
 * @param $product_code
 * @return string
 */
function fn_kuroneko_kakebarai_get_product_code($product_code)
{
    // スクリプトファイル名をセット
    $processor_scripts = array('kuroneko_kakebarai.php');

    // 指定した決済方法を利用する支払方法を抽出
    $processor_ids = fn_kuroneko_kakebarai_get_processor_ids($processor_scripts);
    $processor_params_tmp = db_get_field("SELECT processor_params FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);

    // アンシリアル化
    $processor_params = unserialize($processor_params_tmp);

    // 「商品コードが10文字を超える場合、頭から超えている値を切る」設定の場合
    if( $processor_params['krnkkb_product_code'] == '10' ) {
        if( strlen($product_code) > 10 ){
            $result_product_code = substr($product_code,(10)*(-1),10);
        }
        else {
            $result_product_code = $product_code;
        }
    }
    // 商品コードを「連携しない」設定の場合
    else {
        $result_product_code = '';
    }

    return $result_product_code;
}
##########################################################################################
// 共通の処理 EOF
##########################################################################################
