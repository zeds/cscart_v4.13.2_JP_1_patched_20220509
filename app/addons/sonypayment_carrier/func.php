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
use Tygh\Addons\SonyPayment_Carrier\HttpShiftJS;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * ソニーペイメントサービスでは注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためソニーペイメントサービス
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_sonypayment_carrier_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
        $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors JOIN ?:payments ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE payment_id = ?i", $payment_id);
        if( empty($pr_script) ) return false;

		switch($pr_script){
			case 'sonypayment_carrier_ep.php':
			case 'sonypayment_carrier_rb.php':
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
 * 請求管理ページにおける注文情報の抽出・表示
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_sonypayment_carrier_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    // 請求管理ページの場合
    if( Registry::get('runtime.controller') == 'sonyc_ep_manager' && Registry::get('runtime.mode') == 'manage'){
        // 都度決済により支払われた注文のみ抽出
        $pr_scripts = array("sonypayment_carrier_ep.php");
        $sonyc_ep_payments = db_get_fields("SELECT payment_id FROM ?:payments JOIN ?:payment_processors ON ?:payments.processor_id = ?:payment_processors.processor_id WHERE processor_script IN (?a)", $pr_scripts);
        $sonyc_ep_payments = implode(',', $sonyc_ep_payments);
        $condition .= " AND ?:orders.payment_id IN ($sonyc_ep_payments)";

        // 各注文にひもづけられた請求ステータスコードを抽出
        $fields[] = "?:jp_sonyc_status.status_code as ep_status_code";
        $join .= " LEFT JOIN ?:jp_sonyc_status ON ?:jp_sonyc_status.order_id = ?:orders.order_id";
    }
}




/**
 * 注文情報削除時に決済の請求ステータスを削除
 *
 * @param $order_id
 */
function fn_sonypayment_carrier_delete_order(&$order_id)
{
    $type = false;

    // 支払IDを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if( !empty($payment_id) && fn_sonyc_is_deletable($payment_id) ){
        $status_code = db_get_field("SELECT status_code FROM ?:jp_sonyc_status WHERE order_id = ?i", $order_id);

        switch($status_code){
            // 与信状態の注文については与信を取消
            case '7Auth':
                $type = 'ep_auth_cancel';
                break;
            // 売上処理が完了している注文については売上を取消
            case '7Gathering':
            case 'sales_confirm':
                $type = 'ep_sales_cancel';
                break;
            case '7RecStart':
                $type = 'rb_cancel';
                break;
            default:
                // do nothing
        }

        // 取消・削除処理を実行
        if(!empty($type)) fn_sonyc_send_proc_request($order_id, $type);
    }

    db_query("DELETE FROM ?:jp_sonyc_process_info WHERE order_id = ?i", $order_id);
    db_query("DELETE FROM ?:jp_sonyc_status WHERE order_id = ?i", $order_id);
}



/**
 * 注文データの削除時に売上取消（都度決済）または終了（継続課金）を実施
 *
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 * @return bool
 */
function fn_sonypayment_carrier_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses, &$place_order)
{
    if( empty($order_info['payment_id']) ) return false;

    if($status_to == 'I' && fn_sonyc_is_deletable($order_info['payment_id']) ){

        $status_code = db_get_field("SELECT status_code FROM ?:jp_sonyc_status WHERE order_id = ?i", $order_info['order_id']);

        switch($status_code){
            // 与信状態の注文については与信を取消
            case '7Auth':
                $type = 'ep_auth_cancel';
                break;
            // 売上処理が完了している注文については売上を取消
            case '7Gathering':
            case 'sales_confirm':
                $type = 'ep_sales_cancel';
                break;
            case '7RecStart':
                $type = 'rb_cancel';
                break;
            default:
                return false;
        }

        // 取消処理を実行
        fn_sonyc_send_proc_request($order_info['order_id'], $type);

        // 注文情報を取得
        $tmp_order_info = fn_get_order_info($order_info['order_id']);

        // 処理通番を更新
        if( !empty($tmp_order_info['payment_info']['jp_sonypayment_carrier_transaction_id']) ){
            $order_info['payment_info']['jp_sonypayment_carrier_transaction_id'] = $tmp_order_info['payment_info']['jp_sonypayment_carrier_transaction_id'];
        }

        // 請求ステータスを更新
        if( !empty($tmp_order_info['payment_info']['jp_sonypayment_carrier_status']) ){
            $order_info['payment_info']['jp_sonypayment_carrier_status'] = $tmp_order_info['payment_info']['jp_sonypayment_carrier_status'];
        }
    }
}





/**
 * ログにShopIDなどが記録されることを回避
 *
 * @param $type
 * @param $action
 * @param $data
 * @param $user_id
 * @param $content
 * @param $event_type
 * @param $object_primary_keys
 */
function fn_sonypayment_carrier_save_log(&$type, &$action, &$data, &$user_id, &$content, &$event_type, &$object_primary_keys)
{
    if($type == 'requests'){
        $url = $data['url'];
        switch($url){
            // キャリア決済（テスト環境） : オンライン取引
            case SONYC_TEST_URL_OCAC010:
            // キャリア決済（本番環境） : オンライン取引
            case SONYC_LIVE_URL_OCAC010:

            $content['request'] = 'Hidden for Security Reason';
                $content['response'] = 'Hidden for Security Reason';
                break;

            default:
                // do nothing
        }
    }
}




/**
 * 商品の継続課金に関する情報の保存
 *
 * @param $product_data
 * @param $product_id
 * @param $lang_code
 * @param $create
 * @return bool
 */
function fn_sonypayment_carrier_update_product_post(&$product_data, &$product_id, &$lang_code, &$create)
{
    // この処理を入れないと商品情報の一括編集時に値がリセットされてしまう。
    if (!isset($product_data['sonypayment_carrier_rb_payment_day_01'])) {
        return false;
    }

    // 各キャリア毎にデータを更新
    for($carrier = 1; $carrier <= 3; $carrier++) {

        // 継続課金の設定値を格納する配列を初期化
        $subscription_data = array();

        // キャリアコード
        // 01: docomo, 02: au, 03: softbank
        $carrier_cd = '0'.strval($carrier);

        // 初回継続課金日
        $first_payment_day = $product_data['sonypayment_carrier_rb_first_payment_day_'.$carrier_cd];

        // 継続課金日
        $payment_day = $product_data['sonypayment_carrier_rb_payment_day_'.$carrier_cd];

        // 継続課金の設定値をセット
        $subscription_data = array(
            'product_id' => (int)$product_id,
            'carrier_cd' => $carrier_cd,
            'first_payment_day' => $first_payment_day,
            'payment_day' => $payment_day
        );

        // CS-Cartマルチ決済継続課金の設定値をDBに書き込み
        db_query("REPLACE INTO ?:jp_sonyc_rb_products ?e", $subscription_data);
    }

    return true;
}



/**
 * 商品を削除した際にその商品の継続課金に関する情報も削除する
 *
 * @param $product_id
 */
function fn_sonypayment_carrier_delete_product_post(&$product_id)
{
    db_query("DELETE FROM ?:jp_sonyc_rb_products WHERE product_id = ?i", $product_id);
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
function fn_sonyc_install()
{
    fn_lcjp_install('sonypayment_carrier');
}




/**
 * アドオンのアンインストール時に支払関連のレコードを削除
 */
function fn_sonyc_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('sonypayment_carrier_ep.php', 'sonypayment_carrier_rb.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('sonypayment_carrier_ep.php', 'sonypayment_carrier_rb.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('sonypayment_carrier_ep.php', 'sonypayment_carrier_rb.php')");

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
function fn_settings_variants_addons_sonypayment_carrier_pending_status()
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
 * 各支払方法共通の送信パラメータをセット
 *
 * @param $type

 * @param $order_info
 * @param $processor_data
 * @return array
 */
function fn_sonyc_get_params($type, $order_info='', $processor_data='')
{
    $params = array();

    // マーチャントID
    $params['MerchantId'] = Registry::get('addons.sonypayment_carrier.merchant_id');

    // マーチャントパスワード
    $params['MerchantPass'] = Registry::get('addons.sonypayment_carrier.merchant_pass');

    // 取引の処理日付（YYYYMMDD）
    $params['TransactionDate'] = date('Ymd');

    // 取引の処理ID
    $params['OperateId'] = fn_sonyc_get_operate_id($type, $processor_data, $order_info['payment_info']['carrier']);

    // 自由領域
    $params['MerchantFree1'] = "";
    $params['MerchantFree2'] = "";
    $params['MerchantFree3'] = "";

    // 支払方法別の送信パラメータをセット
    fn_sonyc_get_specific_params($params, $type, $order_info, $processor_data);

    return $params;
}




/**
 * ソニーペイメントに送信する取引電文種別を取得
 *
 * @param $type
 * @param $processor_data
 * @return bool|string
 */
function fn_sonyc_get_operate_id($type, $processor_data='', $carrier='')
{
    switch($type){
        case 'ep':
            // ソフトバンク以外の場合
            if($carrier != '03') {
                if ($processor_data['processor_params']['process_type'] == 'auth_only') {
                    // 与信
                    return '7Auth';
                } else {
                    // 与信売上確定
                    return '7Gathering';
                }
            }
            // ソフトバンクの場合
            else{
                if ($processor_data['processor_params']['process_type_softbank'] == 'auth_only') {
                    // 与信
                    return '7Auth';
                } else {
                    // 与信売上確定
                    return '7Gathering';
                }
            }
        case 'ep_sales_confirm':
            return '7Capture';
        case 'ep_auth_cancel':
        case 'ep_sales_cancel':
            return '7Delete';
        case 'ep_search':
            return '7Search';
        case 'rb':
            return '7RecStart';
        case 'rb_cancel':
            return '7RecCancel';
        default:
            return false;
    }
}




/**
 * 支払方法別の送信パラメータを取得
 *
 * @param $params
 * @param $type
 * @param $order_info
 * @param $processor_data
 */
function fn_sonyc_get_specific_params(&$params, $type, $order_info='', $processor_data='')
{
    switch($type) {
        // 都度決済
        case 'ep':
            // キャリア区分
            $params['CarrierKbn'] = $order_info['payment_info']['carrier'];

            // SoftBank以外の場合
            if ( $params['CarrierKbn'] != "03" ) {
                // NTTドコモの場合
                if( $params['CarrierKbn'] == "01") {
                    // キャリアパスワード
                    $params['CarrierPass'] =Registry::get('addons.sonypayment_carrier.docomo_pass');

                    // 店舗コード
                    $tenant_id = Registry::get('addons.sonypayment_carrier.docomo_ep_tenant_id');

                    // 表示項目内容
                    $params['DisplayItem1'] = mb_strimwidth(mb_convert_kana(fn_get_company_name($order_info['company_id']), "KVANRS"), 0, 40, '', 'utf-8');
                }
                // auの場合
                else if( $params['CarrierKbn'] == "02") {
                    // キャリアパスワード
                    $params['CarrierPass'] =Registry::get('addons.sonypayment_carrier.au_pass');

                    // 店舗コード
                    $tenant_id = Registry::get('addons.sonypayment_carrier.au_ep_tenant_id');

                    // 表示項目内容
                    $params['DisplayItem1'] = mb_strimwidth(mb_convert_kana(fn_get_company_name($order_info['company_id']), "KVANRS"), 0, 40, '', 'utf-8');
                }
            }
            // SoftBankの場合
            else {
                // キャリアパスワード
                $params['CarrierPass'] =Registry::get('addons.sonypayment_carrier.softbank_pass');

                // 店舗コード
                $tenant_id = Registry::get('addons.sonypayment_carrier.softbank_ep_tenant_id');

                // 顧客ID
                $params['CustomerId'] = $order_info['user_id'];

                $prodid_cnt = 0;
                $list_productids = "";
                foreach($order_info['products'] as $product) {
                    if($prodid_cnt > 0) {
                        $list_productids .= 'P';
                    }

                    $list_productids .= $product['product_id'];

                    $prodid_cnt += 1;
                }

                // 商品ID
                $params['ProductId'] = mb_strimwidth($list_productids, 0, 32);

                // 表示項目内容
                $prod_cnt = 0;
                $width = 40;
                $list_products = '';
                foreach($order_info['products'] as $product) {
                    if($prod_cnt == 0) {
                        $list_products = str_replace('-', '', $product['product']);
                    }
                    else {
                        $list_products .= '他';
                        break;
                    }
                    $prod_cnt += 1;
                }

                // 表示項目内容
                $params['DisplayItem1'] = mb_strimwidth(mb_convert_kana($list_products, "KVANRS"), 0, $width, '', 'utf-8');
            }

            // アドオン設定画面で店舗コードが設定されている場合
            if( $tenant_id ){
                // 店舗コードをセット
                $params['TenantId'] = $tenant_id;
            }else{
                $params['TenantId'] = '0001';
            }

            // 処理番号
            $params['ProcNo'] = $params['MerchantId'].$params['TenantId'].str_pad($order_info["order_id"],8, '0', STR_PAD_LEFT);

            // 申込完了時URL
            $params['SuccesURL'] = fn_url("payment_notification.process&payment=sonypayment_carrier_ep&order_id=".$order_info['order_id']."&process_type=order_complete", AREA, 'current');
            // 申込キャンセル時URL
            $params['CancelURL'] = fn_url("payment_notification.process&payment=sonypayment_carrier_ep&order_id=".$order_info['order_id']."&process_type=order_cancel", AREA, 'current');
            // エラー時URL
            $params['ErrorURL'] = fn_url("payment_notification.process&payment=sonypayment_carrier_ep&order_id=".$order_info['order_id']."&process_type=order_error", AREA, 'current');

            // 在庫確認POST先URL
            $params['OrderConfirmURL'] = fn_lcjp_get_return_url('/jp_extras/sonypayment_carrier/stockcheck.php');
            // 状態通知POST先URL
            $params['EndNoticeURL'] = fn_lcjp_get_return_url('/jp_extras/sonypayment_carrier/notify.php');

            break;

        // 継続課金
        case 'rb':
            // キャリア区分
            $params['CarrierKbn'] = $order_info['payment_info']['carrier_rb'];

            // カート内商品の最初の1つを取得（継続課金はカート内に1商品しか投入できない）
            $product_info = reset(array_slice($order_info['products'], 0, 1));

            // SoftBank以外の場合
            if ( $params['CarrierKbn'] != "03" ) {
                // NTTドコモの場合
                if( $params['CarrierKbn'] == "01") {
                    // キャリアパスワード
                    $params['CarrierPass'] =Registry::get('addons.sonypayment_carrier.docomo_pass');

                    // 店舗コード
                    $tenant_id = Registry::get('addons.sonypayment_carrier.docomo_rb_tenant_id');

                    $sonyc_rb_product = fn_sonyc_get_rb_product_info($product_info['product_id'], $params['CarrierKbn']);

                    // 初回継続課金日
                    $first_payment_day = $sonyc_rb_product['first_payment_day'];

                    // 継続課金日
                    $payment_day = $sonyc_rb_product['payment_day'];

                    // 表示項目内容
                    $params['DisplayItem1'] = mb_strimwidth(mb_convert_kana(fn_get_company_name($order_info['company_id']), "KVANRS"), 0, 40, '', 'utf-8');
                }
                // auの場合
                else if( $params['CarrierKbn'] == "02") {
                    // キャリアパスワード
                    $params['CarrierPass'] =Registry::get('addons.sonypayment_carrier.au_pass');

                    // 店舗コード
                    $tenant_id = Registry::get('addons.sonypayment_carrier.au_rb_tenant_id');

                    $sonyc_rb_product = fn_sonyc_get_rb_product_info($product_info['product_id'], $params['CarrierKbn']);

                    // 初回継続課金日
                    $first_payment_day = $sonyc_rb_product['first_payment_day'];

                    // 継続課金日
                    $payment_day = $sonyc_rb_product['payment_day'];

                    // 表示項目内容1
                    if( $payment_day == 0 ) {
                        $params['DisplayItem1'] = __("jp_sonypayment_carrier_rb_everymonth").mb_convert_kana(date('j'), "N").__("jp_sonypayment_carrier_rb_day_day").__("jp_sonypayment_carrier_rb_charge");
                    }
                    elseif ($payment_day == 99 ) {
                        $params['DisplayItem1'] = __("jp_sonypayment_carrier_rb_everymonth").__("jp_sonypayment_carrier_rb_day_eom").__("jp_sonypayment_carrier_rb_charge");
                    }
                    else {
                        $params['DisplayItem1'] = __("jp_sonypayment_carrier_rb_everymonth").mb_convert_kana($payment_day, "N").__("jp_sonypayment_carrier_rb_day_day").__("jp_sonypayment_carrier_rb_charge");
                    }
                }
            }
            // SoftBankの場合
            else {
                // キャリアパスワード
                $params['CarrierPass'] =Registry::get('addons.sonypayment_carrier.softbank_pass');

                // 店舗コード
                $tenant_id = Registry::get('addons.sonypayment_carrier.softbank_rb_tenant_id');

                $sonyc_rb_product = fn_sonyc_get_rb_product_info($product_info['product_id'], $params['CarrierKbn']);

                // 初回継続課金日
                $first_payment_day = $sonyc_rb_product['first_payment_day'];

                // 継続課金日
                $payment_day = $sonyc_rb_product['payment_day'];

                $tmp_auth = Tygh::$app['session']['auth'];

                // 顧客ID
                $params['CustomerId'] = $tmp_auth['user_id'];

                // 商品ID
                $params['ProductId'] = $product_info['product_id'];

                // 表示項目内容
                $prod_cnt = 0;
                $width = 40;
                $list_products = '';
                foreach($order_info['products'] as $product) {
                    if($prod_cnt == 0) {
                        $list_products = str_replace('-', '', $product['product']);
                    }
                    else {
                        $list_products .= '他';
                        break;
                    }
                    $prod_cnt += 1;
                }

                // 表示項目内容
                $params['DisplayItem1'] = mb_strimwidth(mb_convert_kana($list_products, "KVANRS"), 0, $width, '', 'utf-8');
            }

            // アドオン設定画面で店舗コードが設定されている場合
            if( $tenant_id ){
                // 店舗コードをセット
                $params['TenantId'] = $tenant_id;
            }else{
                $params['TenantId'] = '0001';
            }

            // 処理番号
            $params['ProcNo'] = $params['MerchantId'].$params['TenantId'].str_pad($order_info["order_id"],8, '0', STR_PAD_LEFT);

            // 初回継続課金日
            if( $first_payment_day != 0 && $first_payment_day != 99) {
                $twodigitday = substr('0'.strval($first_payment_day),(2)*(-1),2);

                if( $first_payment_day >= date('j') ){
                    $params['FirstRecDate'] = date('Ym').$twodigitday;
                }
                else {
                    $params['FirstRecDate'] = date('Ym', mktime(0, 0, 0, date('n') + 1, 1, date('Y'))).$twodigitday;
                }
            }
            // 月末の場合
            elseif( $first_payment_day == 99 ) {
                $params['FirstRecDate'] = date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, date('Y')));
            }

            // 継続課金日
            if( $payment_day != 0 ) {
                $params['RecDate'] = $payment_day;
            }

            // 申込完了時URL
            $params['SuccesURL'] = fn_url("payment_notification.process&payment=sonypayment_carrier_rb&order_id=". $order_info['order_id']."&process_type=order_complete", AREA, 'current');
            // 申込キャンセル時URL
            $params['CancelURL'] = fn_url("payment_notification.process&payment=sonypayment_carrier_rb&order_id=".$order_info['order_id']."&process_type=order_cancel", AREA, 'current');
            // エラー時URL
            $params['ErrorURL'] = fn_url("payment_notification.process&payment=sonypayment_carrier_rb&order_id=".$order_info['order_id']."&process_type=order_error", AREA, 'current');

            // 在庫確認POST先URL
            $params['OrderConfirmURL'] = fn_lcjp_get_return_url('/jp_extras/sonypayment_carrier/stockcheck.php');
            // 状態通知POST先URL
            $params['EndNoticeURL'] = fn_lcjp_get_return_url('/jp_extras/sonypayment_carrier/notify.php');

            break;

        // 売上確定 / 与信取消 / 売上確定取消
        case 'ep_sales_confirm':
        case 'ep_auth_cancel':
        case 'ep_sales_cancel':
        case 'ep_search':
        case 'rb_cancel':
            // 後続処理用のプロセスIDおよびプロセスパスワードを取得
            list($process_id, $process_pass) = fn_sonyc_get_process_info($order_info['order_id']);

            // 後続処理用のプロセスIDおよびプロセスパスワードが存在する場合
            if( !empty($process_id) && !empty($process_pass) ){
                // プロセスID
                $params['ProcessId'] = $process_id;

                // プロセスパスワード
                $params['ProcessPass'] = $process_pass;
            }

            // 注文データ内の支払関連情報を取得
            $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_info['order_id'], 'P');

            $flg_payment_info_exists = false;
            // 注文データ内に支払関連情報が存在する場合
            if( !empty($payment_info) ){

                // 支払情報が暗号化されている場合は復号化して変数にセット
                if( !is_array($payment_info)) {
                    $info = @unserialize(fn_decrypt_text($payment_info));
                }else{
                    // 支払情報を変数にセット
                    $info = $payment_info;
                }

                if( $info['jp_sonypayment_carrier_cd'] ){
                    // キャリアパス
                    // NTTドコモの場合
                    if( $info['jp_sonypayment_carrier_cd'] == "01") {
                        // キャリアパスワード
                        $params['CarrierPass'] = Registry::get('addons.sonypayment_carrier.docomo_pass');
                    }
                    // auの場合
                    elseif( $info['jp_sonypayment_carrier_cd'] == "02") {
                        // キャリアパスワード
                        $params['CarrierPass'] = Registry::get('addons.sonypayment_carrier.au_pass');
                    }
                    // SoftBankの場合
                    elseif( $info['jp_sonypayment_carrier_cd'] == "03") {
                        // キャリアパスワード
                        $params['CarrierPass'] = Registry::get('addons.sonypayment_carrier.softbank_pass');
                    }
                }

                if( $info['jp_sonypayment_carrier_settlement_no'] ){
                    // 決済番号
                    $params['SettlementNo'] = $info['jp_sonypayment_carrier_settlement_no'];
                }
            }

            break;

        default:
            // do nothing
            break;
    }

    // 利用金額
    $params['Amount'] = round($order_info['total']);



}




/**
 * ソニーペイメントへリクエストを送信
 *
 * @param $params
 * @param $processor_data
 * @param string $action
 * @return array
 */
function fn_sonyc_send_request($params, $processor_data, $action = 'checkout')
{
    // キャリア区分を取得
    $carrierkbn = $params['CarrierKbn'];

    // 本番環境の場合
    if( $processor_data['processor_params']['mode'] == 'live' ){
        $target_url = SONYC_LIVE_URL_OCAC010;
        //  テスト環境の場合
    }else{
        $target_url = SONYC_TEST_URL_OCAC010;
    }

    switch($action){
        // 決済手続き
        case 'checkout':
        case 'rb':
            // パラメータの暗号化
            $params = fn_sonyc_encrypt_params($params);
            break;

        default:
            // do nothing
            break;
    }

    // ソニーペイメントにデータを送信する
    // ソフトバンク以外の場合
    if( $carrierkbn != '03' ) {
        $result = Http::post($target_url, $params);
    }
    // ソフトバンクの場合
    else {
        $result = HttpShiftJS::post($target_url, $params);
    }

    return $result;
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $result_params
 * @param bool $flg_comments
 * @return bool
 */
function fn_sonyc_format_payment_info($type, $order_id, $payment_info, $result_params, $flg_comments = false)
{
    // 注文IDが存在しない場合は処理を終了
    if( empty($order_id) ) return false;

    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

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

        /////////////////////////////////////////////////////////
        // 追記用コメントの初期化 BOF
        /////////////////////////////////////////////////////////
        // 既存のコメントを取得
        $order_comments = db_get_field("SELECT notes FROM ?:orders WHERE order_id = ?i", $order_id);

        // 既存のコメントが存在する場合、改行を追加
        if($order_comments != ''){
            $order_comments .= "\n\n";
        }

        // 見出し
        $order_comments .= __('jp_sonypayment_carrier_'. $type . '_info') . "\n";
        /////////////////////////////////////////////////////////
        // 追記用コメントの初期化 EOF
        /////////////////////////////////////////////////////////

        // 支払情報がすでに存在する場合
        if( !empty($info) ){
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 BOF
            ////////////////////////////////////////////////////////////////////
            foreach($info as $key => $val){
                switch($key){

                    // 一時的に保存されたカード番号などの情報はすべて削除
                    default:
                        unset($info[$key]);
                        break;
                }
            }
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 EOF
            ////////////////////////////////////////////////////////////////////
        }

        ////////////////////////////////////////////////////////////////////
        // 共通項目 BOF
        ////////////////////////////////////////////////////////////////////
        // 処理通番
        if( !empty($result_params['TransactionId']) ){
            $info['jp_sonypayment_carrier_transaction_id'] = $result_params['TransactionId'];
        }

        // 取引日付
        if( !empty($result_params['TransactionDate']) ){
            $info['jp_sonypayment_carrier_transaction_date'] = $result_params['TransactionDate'];
        }

        // 決済番号
        if( !empty($result_params['SettlementNo']) ){
            $info['jp_sonypayment_carrier_settlement_no'] = $result_params['SettlementNo'];
        }

        // キャリア区分
        if( !empty($result_params['CarrierKbn']) ){
            $info['jp_sonypayment_carrier_cd'] = $result_params['CarrierKbn'];
        }

        ////////////////////////////////////////////////////////////////////
        // 共通項目 EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // 都度決済 BOF
        ////////////////////////////////////////////////////////////////////
        if( $type == 'ep' ){
            // オーソリが正常に完了した場合
            if( $result_params['ResponseCd'] == 'OK' ){
                // 決済データの処理方法
                if( !empty($result_params['OperateId']) ){
                    if( $info['jp_sonypayment_carrier_cd'] != '03' ) {
                        $info['jp_sonypayment_carrier_ep_process_type'] = fn_sonyc_get_process_type($result_params['OperateId'], $info['jp_sonypayment_carrier_cd']);
                    }
                    else {
                        $info['jp_sonypayment_carrier_ep_process_type_softbank'] = fn_sonyc_get_process_type($result_params['OperateId'], $info['jp_sonypayment_carrier_cd']);
                    }
                }
            }
            else {
                $info['jp_sonypayment_carrier_ep_error_code'] = $result_params['ResponseCd'];
            }
        }
        ////////////////////////////////////////////////////////////////////
        // 都度決済 EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // 継続課金 BOF
        ////////////////////////////////////////////////////////////////////
        if( $type == 'rb' ){
            // 決済情報登録が異常終了した場合
            if( $result_params['ResponseCd'] != 'OK' ){
                $info['jp_sonypayment_carrier_rb_error_code'] = $result_params['ResponseCd'];
            }
        }

        ////////////////////////////////////////////////////////////////////
        // 継続課金 EOF
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

        // 指定した場合のみコメント欄に支払情報を追記する
        if( $flg_comments ){
            $valid_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);

            // 正常なフローでの処理の場合のみ追記する
            if( !empty($valid_id) ){
                $data = array('notes' => $order_comments);
                db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id);
            }
        }
    }
}




/**
 * 処理方法を取得
 *
 * @param $operate_id
 * @param $carrier_code
 * @return bool|string
 */
function fn_sonyc_get_process_type($operate_id, $carrier_code)
{
    if(empty($operate_id)) return false;

    switch($operate_id){
        case '7Auth':
            if($carrier_code != '03') {
                return __('jp_sonypayment_carrier_ep_auth');
            }
            else {
                return __('jp_sonypayment_carrier_ep_auth_only_softbank');
            }
            break;
        case '7Capture':
            return __('jp_sonypayment_carrier_ep_capture');
            break;
        case '7Gathering':
            if($carrier_code != '03') {
                return __('jp_sonypayment_carrier_ep_gathering');
            }
            else {
                return __('jp_sonypayment_carrier_ep_gathering_softbank');
            }
            break;
        case '7Delete':
            return __('jp_sonypayment_carrier_ep_delete');
            break;
        case '7Search':
            return __('jp_sonypayment_carrier_ep_search');
            break;
        case '7RecStart':
            return __('jp_sonypayment_carrier_rb_recstart');
            break;
        case '7RecCancel':
            return __('jp_sonypayment_carrier_rb_reccancel');
            break;
    }
}




/**
 * ソニーペイメントの取消およびデータ削除処理の対象となる注文であるかを判定
 *
 * @param $payment_id
 * @return bool
 */
function fn_sonyc_is_deletable($payment_id)
{
    // 注文で使用されている決済代行業者IDを取得
    $payment_method_data = fn_get_payment_method_data($payment_id);
    if( empty($payment_method_data) ) return false;
    $processor_id = $payment_method_data['processor_id'];
    if( empty($processor_id) ) return false;
    $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

    // 決済代行業者がスマートリンクの場合はtrueを返す
    switch($pr_script){
        case 'sonypayment_carrier_ep.php':    // ソニーペイメントサービス - キャリア決済（都度決済）
        case 'sonypayment_carrier_rb.php':    // ソニーペイメントサービス - キャリア決済（継続課金）
            return true;
            break;
        default:
            return false;
    }
}




/**
 * パラメータを暗号化する
 *
 * @param $params
 * @return array
 */
function fn_sonyc_encrypt_params($params)
{
    $result_params = array();

    $encrypt_params = array();
    foreach( $params as $key => $value ) {
        if( $key != "MerchantId" && $key != "OperateId" ) {
            $encrypt_params[$key] = $value;
        }
        else if( $key == "MerchantId" || $key == "OperateId" ) {
            $result_params[$key] = $value;
        }
    }

    // URLエンコードされたクエリ文字列
    $encrypt_params_str = http_build_query($encrypt_params);

    // 暗号化処理
    $iv = SONYC_AES_IV;
    $key = Registry::get('addons.sonypayment_carrier.aes_encrypt_key');
    $method = SONYC_AES_METHOD;

    // AES 暗号化
    $encrypt_params_str = openssl_encrypt ($encrypt_params_str, $method, $key, true, $iv);

    // Base64 エンコード
    $encrypt_params_str = base64_encode($encrypt_params_str);

    $result_params["EncryptValue"] = $encrypt_params_str;

    return $result_params;
}



/**
 * パラメータを復号化する
 *
 * @param $result_str
 * @return array
 */
function fn_sonyc_decrypt_params($result_str)
{
    $result_params = array();

    // 復号化処理
    $iv = SONYC_AES_IV;
    $key = Registry::get('addons.sonypayment_carrier.aes_encrypt_key');
    $method = SONYC_AES_METHOD;

    // Base64 デコード
    $decrypt_value = base64_decode($result_str);
    // AES 復号化
    $decrypt_value = openssl_decrypt($decrypt_value, $method, $key, true, $iv);

    $decrypt_tmp = explode("&", $decrypt_value);

    foreach($decrypt_tmp as $val){
        $key_val = explode('=', $val);
        if(!empty($key_val)) $result_params[$key_val[0]] = urldecode($key_val[1]);
    }

    return $result_params;
}




/**
 * エラーメッセージを取得する
 *
 * @param $response_cd
 * @return string
 */
function fn_sonyc_get_err_msg($response_cd)
{
    $err_msgs = explode('|', $response_cd);

    $err_message = $response_cd . ' : ';

    $err_cnt = 0;
    foreach($err_msgs as $err_msg) {

        if($err_cnt > 0 ) {
            $err_message .= '|';
        }

        $response_cd_lower = strtolower($err_msg);

        $err_message .= __('jp_sonypayment_carrier_error_msg_' . $response_cd_lower);

        $err_cnt += 1;
    }

    return $err_message;
}




/**
 * 後続処理のための ProcessId と ProcessPass をDBに保存する
 *
 * @param $order_id
 * @param $result_params
 * @return bool
 */
function fn_sonyc_update_set_process_info($order_id, $result_params)
{
    if( empty($order_id) || empty($result_params['ProcessId']) || empty($result_params['ProcessPass']) ){
        return false;
    }else{
        $_process_info = array(
            'order_id' => $order_id,
            'process_id' => $result_params['ProcessId'],
            'process_pass' => $result_params['ProcessPass']
        );

        db_query("REPLACE INTO ?:jp_sonyc_process_info ?e", $_process_info);
    }
}




/**
 * 後続処理用のプロセスIDとプロセスパスワードを取得
 *
 * @param $order_id
 * @return array|bool
 */
function fn_sonyc_get_process_info($order_id)
{
    // 注文IDが指定されていない場合はfalseを返す
    if( empty($order_id) ) return false;

    // 後続処理用のプロセスIDとプロセスパスワードを抽出
    $process_info = db_get_row("SELECT * FROM ?:jp_sonyc_process_info WHERE order_id = ?i", $order_id);

    // 後続処理用のプロセスIDとプロセスパスワードが存在する場合
    if( !empty($process_info) ){
        // 後続処理用のプロセスIDとプロセスパスワードを返す
        return array($process_info['process_id'], $process_info['process_pass']);

        // 後続処理用のプロセスIDとプロセスパスワードが存在しない場合
    }else{
        // falseを返す
        return false;
    }
}




/**
 * 決済ステータスおよび処理通番をDBに保存
 *
 * @param $order_id
 * @param $status_code
 * @param string $transaction_id
 * @return bool
 */
function fn_sonyc_update_status_code($order_id, $status_code, $transaction_id = '')
{
    //////////////////////////////////////////////////////////////////////
    // 決済ステータスをDBに保存 BOF
    //////////////////////////////////////////////////////////////////////
    $_data = array (
        'order_id' => $order_id,
        'status_code' => $status_code,
    );
    db_query("REPLACE INTO ?:jp_sonyc_status ?e", $_data);
    //////////////////////////////////////////////////////////////////////
    // 決済ステータスをDBに保存 EOF
    //////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////
    // 請求ステータスと処理通番を注文詳細に表示 BOF
    //////////////////////////////////////////////////////////////////////
    // 注文データ内の支払関連情報を取得
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

    // 注文データ内に支払関連情報が存在する場合
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

    // 請求ステータスをセット
    $info['jp_sonypayment_carrier_status'] = fn_sonyc_get_status_name($status_code);

    // 処理通番をセット
    if( !empty($transaction_id) ){
        $info['jp_sonypayment_carrier_transaction_id'] = $transaction_id;
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
    //////////////////////////////////////////////////////////////////////
    // 請求ステータスと処理通番を注文詳細に表示 EOF
    //////////////////////////////////////////////////////////////////////
}




/**
 * 売上確定 / 取消 の実行
 *
 * @param $order_id
 * @param string $type
 */
function fn_sonyc_send_proc_request($order_id, $type = 'ep_sales_confirm')
{
    // 注文IDが指定されていない場合はfalseを返す
    if( empty($order_id) ) return false;

    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_sonyc_check_process_validity($order_id, $type);

    if(!$is_valid_order) return false;

    // 支払方法に関するデータを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);

    // 注文情報を取得
    $order_info = fn_get_order_info($order_id);

    // 売上確定 / 取消 に必要なパラメータを取得
    $params = fn_sonyc_get_params($type, $order_info, $processor_data);

    // 処理の実行（売上確定  / 取消）
    $action = 'process';

    $result_param_str = fn_sonyc_send_request($params, $processor_data, $action);

    parse_str($result_param_str,$result_params);

    // 処理が正常終了した場合
    if( $result_params['ResponseCd'] == 'OK' ){
        $transaction_id = '';
        if( !empty($result_params['TransactionId']) ) $transaction_id = $result_params['TransactionId'];
        // CS-Cart注文情報内の請求ステータスと請求ステータスコードを更新
        fn_sonyc_update_status($order_id, $type, $transaction_id);
        // 処理でエラーが発生した場合
    }else{
        // エラーメッセージを表示
        $is_valid_order = false;
        fn_sonyc_get_error_message($type, $order_id, $result_params);
    }
    return $is_valid_order;
}




/**
 * 注文データ内に格納された請求ステータスや処理通番を更新
 *
 * @param $order_id
 * @param string $type
 * @param string $transaction_id
 */
function fn_sonyc_update_status( $order_id, $type = 'ep_sales_confirm', $transaction_id = '')
{
    // 請求ステータスを初期化
    $status_code = '';

    // 処理内容に応じてセットする値を変更
    switch($type){
        // 売上確定
        case 'ep_sales_confirm':
            $status_code = 'sales_confirm';
            $msg = __('jp_sonypayment_carrier_ep_sales_completed');
            break;
        // 与信取消
        case 'ep_auth_cancel':
            $status_code = 'auth_cancel';
            $msg = __('jp_sonypayment_carrier_ep_auth_cancelled');
            break;
        // 売上確定取消
        case 'ep_sales_cancel':
            $status_code = 'sales_cancel';
            $msg = __('jp_sonypayment_carrier_ep_sales_cancelled');
            break;
        // 継続課金終了
        case 'rb_cancel':
            $status_code = 'rb_cancel';
            $msg = __('jp_sonypayment_carrier_rb_reccancel');
            break;
        // その他
        default:
            // do nothing
    }

    // 請求ステータスが設定されている場合
    if( !empty($status_code) ){
        // 請求ステータスを更新
        fn_sonyc_update_status_code($order_id, $status_code, $transaction_id);
        // 処理完了メッセージを表示
        fn_set_notification('N', __('information'), $msg, 'K');
    }
}




/**
 * 売上確定/ 取消 に関するエラーメッセージを取得
 *
 * @param $type
 * @param $order_id
 * @param $result_params
 */
function fn_sonyc_get_error_message($type, $order_id, $result_params)
{
    $is_diplay = true;

    $msg = fn_sonyc_get_err_msg($result_params['ResponseCd']);

    if($order_id) {
        $msg = __("jp_sonypayment_carrier_orderid").$order_id. ': '. $msg;
    }

    switch($type){
        // 売上確定時のエラー
        case 'ep_sales_confirm':
            $title = __('jp_sonypayment_carrier_ep_sales_confirm_error');
            break;
        // 与信取消時のエラー
        case 'ep_auth_cancel':
            $title = __('jp_sonypayment_carrier_ep_auth_cancel_error');
            break;
        // 売上確定取消時のエラー
        case 'ep_sales_cancel':
            $title = __('jp_sonypayment_carrier_ep_sales_cancel_error');
            break;
        // 継続課金解約時のエラー
        case 'rb_cancel':
            $title = __('jp_sonypayment_carrier_rb_cancel_error');
            break;
        default:
            $is_diplay = false;
    }

    if(	$is_diplay ){
        // エラーメッセージを表示
        fn_set_notification('E', $title ,'<br />' . $msg, "K");
    }
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_sonyc_check_process_validity( $order_id, $type )
{
    // 注文データから請求ステータスを取得
    $ep_status = db_get_field("SELECT status_code FROM ?:jp_sonyc_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 売上確定または与信取消
        case 'ep_sales_confirm':
        case 'ep_auth_cancel':
            // 請求ステータスが「与信(7Auth)」の場合に処理可能
            if( $ep_status == '7Auth' ) return true;
            break;
        // 売上確定取消
        case 'ep_sales_cancel':
            // 請求ステータスが「与信売上確定」または「売上確定」の場合に処理可能
            if( $ep_status == '7Gathering' || $ep_status == 'sales_confirm' ) return true;
            break;

        // 継続課金終了
        case 'rb_cancel':
            // 請求ステータスが「与信売上確定」または「売上確定」の場合に処理可能
            if( $ep_status == '7RecStart' ) return true;
            break;

        // その他
        default:
            // do nothing
    }

    return false;
}




/**
 * 請求ステータス名を取得
 *
 * @param $status
 * @return string
 */
function fn_sonyc_get_status_name($status)
{
    return __('jp_sonypayment_carrier_' . strtolower($status));
}
/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// 継続課金 BOF
/////////////////////////////////////////////////////////////////////////////////////
/**
 * ソニーペイメントキャリア決済 - 継続課金対象商品の情報を取得
 *
 * @param $product_id
 * @param $carrier_cd
 * @return array
 */
function fn_sonyc_get_rb_product_info($product_id, $carrier_cd)
{
    $sonyc_rb_product_info = db_get_row("SELECT * FROM ?:jp_sonyc_rb_products WHERE product_id = ?i AND carrier_cd = ?s", $product_id, $carrier_cd);

    return $sonyc_rb_product_info;
}
/////////////////////////////////////////////////////////////////////////////////////
// 継続課金 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// オプション処理（在庫確認、状態通知） BOF
/////////////////////////////////////////////////////////////////////////////////////
/**
 * ソニーペイメントから受信した在庫確認通知データのバリデーション
 *
 * @param $post
 * @return boolean
 */
function fn_sonyc_validate_notification($post)
{
    // 送信されたマーチャントIDとCS-Cartに登録されたマーチャントIDが一致しない場合はエラー
    if( $post['MerchantId'] != Registry::get('addons.sonypayment_carrier.merchant_id') ) return false;

    // 送信されたマーチャントパスワードとCS-Cartに登録されたマーチャントパスワードが一致しない場合はエラー
    if( $post['MerchantPass'] != Registry::get('addons.sonypayment_carrier.merchant_pass') ) return false;

    return true;
}




/**
 * ソニーペイメントから受信した「処理番号1」からCS-Cartの注文IDを抽出
 *
 * @param $merchantid
 * @param $tenantid
 * @param $procno
 * @return int
 */
function fn_sonyc_get_order_id($procno)
{
    if(empty($procno)) return false;

    // ProcNoの右から８文字を取得
    $order_id_str = substr($procno,(8)*(-1),8);
    $order_id = intval($order_id_str);

    return $order_id;
}
/////////////////////////////////////////////////////////////////////////////////////
// オプション処理（在庫確認、状態通知） EOF
/////////////////////////////////////////////////////////////////////////////////////

##########################################################################################
// END その他の関数
##########################################################################################
