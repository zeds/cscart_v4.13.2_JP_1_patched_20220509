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

// $Id: func.php by takahashi from cs-cart.jp 2019
//
// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_sonypayment_subpay_[フックポイント名]
// (2) (1)以外の関数：fn_sonys_[任意の名称]

// Modified by takahashi from cs-cart.jp 2017
// トークン決済に対応

// Modified by takahashi from cs-cart.jp 2018
// 登録済み決済支払方法がないとテストサイトがログに出る問題を修正

// Modified by takahashi from cs-cart.jp 2019
// 会員登録変更対応（期限切れカードの場合は無効解除できない）
// マーケットプレイス版対応

use Tygh\Http;
use Tygh\Registry;

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################




/**
 * クレジットカード情報を登録済みの会員に対してのみ「登録済カードによる支払」を表示
 *
 * @param $params
 * @param $payments
 */
function fn_sonypayment_subpay_get_payments_post(&$params, &$payments)
{
    // カート画面では実行しない
    if( $_REQUEST['dispatch'] == 'checkout.cart' ){
        return false;
    }

    $tmp_name = 'sonypayment_subpay.tpl';

    if( empty(Tygh::$app['session']['auth']['user_id']) ){
        // 選択可能な支払方法が存在する場合
        if( !empty($payments) ){

            foreach($payments as $key => $val){
                // 決済代行サービスIDを取得
                $processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $val['payment_id']);

                // 各決済代行サービスにひもづけられた設定用テンプレート名を取得
                $template = db_get_field("SELECT admin_template FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

                // テンプレート名が登録済カードによる支払に関するものである場合
                if( !empty($template) && $template == $tmp_name ) {
                    unset($payments[$key]);

                    fn_set_notification('N', __('notice'), __('jp_sonys_register_required'));
                }
            }
        }
    }
}




/**
 * ソニーペイメント決済では注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためソニーペイメント決済
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_sonypayment_subpay_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
			case 'sonypayment_subpay.php':
				// 支払情報が暗号化されている場合は復号化して変数にセット
				if( !is_array($payment_info)) {
					$info = @unserialize(fn_decrypt_text($payment_info));
				}else{
					// 支払情報を変数にセット
					$info = $payment_info;
				}

				// 支払情報から注文ステータスに関する記述を削除
				unset($info['order_status']);

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
 * 注文履歴ページにおける注文情報の抽出・表示
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_sonypayment_subpay_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    // 定期課金管理ページの場合
    if( Registry::get('runtime.controller') == 'sonys_subsc_manager' && Registry::get('runtime.mode') == 'details'){
        // 定期課金により支払われた注文のみ抽出
        $pr_scripts = array("sonypayment_subpay.php");
        $sonys_payment_id = db_get_field("SELECT payment_id FROM ?:payments JOIN ?:payment_processors ON ?:payments.processor_id = ?:payment_processors.processor_id WHERE processor_script IN (?a)", $pr_scripts);
        $condition .= " AND ?:orders.payment_id = " . $sonys_payment_id;

        // 該当の定期購入の注文を抽出
        if( !empty($params['subpay_id']) ) {
            $join .= " JOIN ?:jp_sonys_subsc_history ON ?:jp_sonys_subsc_history.order_id = ?:orders.order_id AND ?:jp_sonys_subsc_history.subpay_id = " . $params['subpay_id'];
        }
        else{
            $join .= " JOIN ?:jp_sonys_subsc_history ON ?:jp_sonys_subsc_history.order_id = ?:orders.order_id";
        }

        // 各注文にひもづけられたクレジット請求ステータスコードを抽出
        $fields[] = "?:jp_sonys_status.status_code as cc_status_code";
        $join .= " LEFT JOIN ?:jp_sonys_status ON ?:jp_sonys_status.order_id = ?:orders.order_id";
    }
}




/**
 * 注文情報削除時にソニーペイメント側の
 * 注文情報削除時に後続処理のための ProcessId と ProcessPass をDBから削除
 * 注文情報削除時に各注文の請求ステータスに関するレコードを削除
 *
 * @param $order_id
 */
function fn_sonypayment_subpay_delete_order(&$order_id)
{
    $type = false;

    // 支払IDを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if( !empty($payment_id) && fn_sonys_is_deletable($payment_id) ){
        $status_code = db_get_field("SELECT status_code FROM ?:jp_sonys_status WHERE order_id = ?i", $order_id);

        switch($status_code){
            // 与信状態の注文については与信を取消
            case '1Auth':
                $type = 'cc_auth_cancel';
                break;
            // 売上処理が完了している注文については売上を取消
            case '1Gathering':
            case '1Capture':
            case 'sales_confirm':
                $type = 'cc_sales_cancel';
                break;
            default:
                // do nothing
        }

        // 取消・削除処理を実行
        if(!empty($type)) fn_sonys_send_cc_request($order_id, $type);
    }

    db_query("DELETE FROM ?:jp_sonys_process_info WHERE order_id = ?i", $order_id);
    db_query("DELETE FROM ?:jp_sonys_status WHERE order_id = ?i", $order_id);
}




/**
 * 注文データの削除時に売上取消（カード）またはデータ削除（収納代行）を実施
 *
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 * @return bool
 */
function fn_sonypayment_subpay_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses, &$place_order)
{
    if( empty($order_info['payment_id']) ) return false;

    if($status_to == 'I' && fn_sonys_is_deletable($order_info['payment_id']) ){

        $status_code = db_get_field("SELECT status_code FROM ?:jp_sonys_status WHERE order_id = ?i", $order_info['order_id']);

        switch($status_code){
            // 与信状態の注文については与信を取消
            case '1Auth':
                $type = 'cc_auth_cancel';
                break;
            // 売上処理が完了している注文については売上を取消
            case '1Gathering':
            case '1Capture':
            case 'sales_confirm':
                $type = 'cc_sales_cancel';
                break;
            default:
                return false;
        }

        // 取消処理を実行
        fn_sonys_send_cc_request($order_info['order_id'], $type);

        // 注文情報を取得
        $tmp_order_info = fn_get_order_info($order_info['order_id']);

        // 処理通番を更新
        if( !empty($tmp_order_info['payment_info']['jp_sonys_transaction_id']) ){
            $order_info['payment_info']['jp_sonys_transaction_id'] = $tmp_order_info['payment_info']['jp_sonys_transaction_id'];
        }

        // 請求ステータスを更新
        if( !empty($tmp_order_info['payment_info']['jp_sonys_status']) ){
            $order_info['payment_info']['jp_sonys_status'] = $tmp_order_info['payment_info']['jp_sonys_status'];
        }
    }
}




/**
 * ユーザー削除時にソニーペイメントに登録されている会員情報も削除する
 *
 * @param $user_id
 * @param $user_data
 * @param $result
 * @return bool
 */
function fn_sonypayment_subpay_post_delete_user(&$user_id, &$user_data, &$result)
{
    // CS-Cart側でのユーザー削除が完了した場合
    if($result){
        // ユーザーID決済に関するデータを取得
        $processor_data = fn_sonys_get_processor_data();

        $mode = $processor_data['processor_params']['mode'];

        // ソニーペイメント側の会員情報の削除に必要なパラメータを取得
        $order_info = array();
        $order_info['user_id'] = $user_id;
        $ccreg_delete_params = fn_sonys_get_params('ccreg_delete', $order_info);

        // 会員パスワードが存在しない場合
        if( empty($ccreg_delete_params['KaiinPass']) ){
            // 削除済みクレジットカード情報が存在するかチェック
            $deleted_kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_sonys_deleted_quickpay WHERE user_id = ?i AND mode = ?s", $user_id, $mode);

            // 削除済みクレジットカード情報が存在する場合
            if(!empty($deleted_kaiin_pass)){
                // 会員パスワードをセット
                $ccreg_delete_params['KaiinPass'] = $deleted_kaiin_pass;
            }
        }

        // ソニーペイメントの会員登録済みユーザーの場合
        if(!empty($ccreg_delete_params['KaiinPass'])){

            // ソニーペイメントに登録している会員情報のステータスを取得
            $card_info = fn_sonys_get_registered_card_info($user_id, true);
            $kaiin_status = $card_info['status'];

            // 会員ステータスに応じて処理を実行
            switch($kaiin_status){
                case 0: // 有効
                case 1: // カード無効
                case 2: // Login回数無効
                    // 会員を無効化
                    fn_sonys_delete_card_info($user_id, false);
                    break;
                case 3: // 会員無効
                    // do nothing
                    break;
                default:    // その他のステータス
                    // エラーメッセージを表示して処理を終了
                    fn_set_notification('E', __('error'), __('jp_sonys_delete_failed'));
                    return false;
            }

            // 会員情報の削除
            $result_params = fn_sonys_send_request($ccreg_delete_params, $processor_data, 'ccreg');

            // ソニーペイメント側で正常に処理が終了した場合
            if ( !empty($result_params['ResponseCd']) && $result_params['ResponseCd'] == 'OK' ){
                db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'sonypayment_subpay_' . $mode);
                db_query("DELETE FROM ?:jp_sonys_deleted_quickpay WHERE user_id = ?i AND mode = ?s", $user_id, $mode);
                fn_set_notification('N', __('information'), __('jp_sonys_delete_success'));
            // ソニーペイメント側でエラーが発生した場合
            }else{
                fn_set_notification('E', __('error'), __('jp_sonys_delete_failed'));
            }
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
function fn_sonypayment_subpay_save_log(&$type, &$action, &$data, &$user_id, &$content, &$event_type, &$object_primary_keys)
{
    if($type == 'requests'){
        $url = $data['url'];
        switch($url){
            // 決済手続き 本番環境の場合
            case 'https://www.e-scott.jp/online/aut/OAUT002.do':
            // 決済手続き テスト環境の場合
            case 'https://www.test.e-scott.jp/online/aut/OAUT002.do':
            // カード情報お預かり機能 本番環境の場合
            case 'https://www.e-scott.jp/online/crp/OCRP005.do':
            // カード情報お預かり機能 テスト環境の場合
            case 'https://www.test.e-scott.jp/online/crp/OCRP005.do':

                $content['request'] = 'Hidden for Security Reason';
                $content['response'] = 'Hidden for Security Reason';
                break;

            default:
                // do nothing
        }
    }
}




/**
 * 商品の定期購入に関する情報の保存
 *
 * @param $product_data
 * @param $product_id
 * @param $lang_code
 * @param $create
 * @return bool
 */
function fn_sonypayment_subpay_update_product_post(&$product_data, &$product_id, &$lang_code, &$create)
{
    // この処理を入れないと商品情報の一括編集時に値がリセットされてしまう。
    if (!isset($product_data['sonys_second_price'])) {
        return false;
    }

    $s = serialize($product_data['sonys_deliver_send_day']);

    // 定期購入の商品データを更新
    $_data = array('product_id' => $product_id,
        'second_price' => intval($product_data['sonys_second_price']),
        'deliver_day_w' => serialize($product_data['sonys_deliver_send_day_w']),
        'deliver_day_bw' => serialize($product_data['sonys_deliver_send_day_bw']),
        'deliver_day_m' => serialize($product_data['sonys_deliver_send_day_m']),
        'deliver_day_bm' => serialize($product_data['sonys_deliver_send_day_bm']),
        'deliver_time' => serialize($product_data['sonys_deliver_send_time']),
        'start_date' => strtotime($product_data['sonys_start_date']),
        'end_date' => strtotime($product_data['sonys_end_date']),
    );
    db_query("REPLACE INTO ?:jp_sonys_products ?e", $_data);

    return true;
}




/**
 * 商品の金額が０の場合でも支払い方法を表示する
 *
 * @param $cart
 * @param $payment_methods
 * @param $completed_steps
 */
function fn_sonypayment_subpay_checkout_select_default_payment_method(&$cart, &$payment_methods, &$completed_steps)
{
    if( $cart['payment_id'] == 0 ) {
        foreach ($payment_methods as $payment_method) {
            foreach ($payment_method as $pay_method) {
                // ソニーペイメントの定期課金の場合
                if ($pay_method['template'] == 'addons/sonypayment_subpay/views/orders/components/payments/sonypayment_subpay.tpl') {
                    $cart['payment_id'] = $pay_method['payment_id'];
                    break;
                }
            }
        }
    }
}




///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 BOF
// 配送先住所更新対応
///////////////////////////////////////////////
/**
 * テーブルを追加（配送先住所更新対応パッチ用）
 *
 * @param $user_data
 */
function fn_sonypayment_subpay_set_admin_notification(&$user_data)
{
    // 配送先住所更新用のテーブルが存在するか確認する
    $is_table =  db_get_field("SHOW TABLES LIKE '%jp_sonys_subsc_ship_address'");

    // 配送先住所更新用のテーブルが存在しない場合
    if(empty($is_table)){
        // インストール済みの言語を取得
        $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

        // 言語変数の追加
        $lang_variables = array(
            array('name' => 'jp_sonys_subsc_change_ship_addr', 'value' => '配送住所変更'),
            array('name' => 'jp_sonys_subsc_same_for_all', 'value' => '上記住所を全ての定期購入に適用'),
            array('name' => 'jp_sonys_change_ship_address_enabled', 'value' => 'ソニーペイメントサービス - 定期購入において<br />配送住所変更対応が適用されました。'),
            array('name' => 'jp_sonys_subsc_ship_addr_update_notice', 'value' => '配送住所が変更されました'),
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

        try {
            // 配送先住所更新用のテーブルを追加
            db_query("CREATE TABLE `?:jp_sonys_subsc_ship_address` ( `subpay_id` int(11) NOT NULL, `s_zipcode` varchar(16) CHARACTER SET utf8 DEFAULT NULL, `s_state` varchar(32) CHARACTER SET utf8 DEFAULT NULL, `s_city` varchar(255) CHARACTER SET utf8 DEFAULT NULL, `s_address` varchar(255) CHARACTER SET utf8 DEFAULT NULL, `s_address_2` varchar(255) CHARACTER SET utf8 DEFAULT NULL, `s_phone` varchar(128) CHARACTER SET utf8 DEFAULT NULL, PRIMARY KEY (`subpay_id`)) ENGINE=MyISAM DEFAULT CHARSET=UTF8;");

            // パッチ適用済みのメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_sonys_change_ship_address_enabled'));
        }
        catch (Exception $e){
            // エラー発生(Service Unavailableメッセージを出さない)
            fn_set_notification('E', __('error'), __('error_occurred') . '(' . $e->getMessage() . ')');
        }
    }
}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 EOF
///////////////////////////////////////////////
##########################################################################################
// END フックポイントで動作する関数
##########################################################################################





##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################
/**
 * アドオンのインストール時の処理
 */
function fn_sonypayment_subpay_install()
{
    fn_lcjp_install('sonypayment_subpay');
}




/**
 * アドオンのアンインストール時の処理
 */
function fn_sonypayment_subpay_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'sonypayment_subpay.php'))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'sonypayment_subpay.php')");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script = 'sonypayment_subpay.php'");
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################





##########################################################################################
// START アドオンの設定ページで動作する関数
##########################################################################################
/**
 * 二回目以降決済データの処理方法のリストを生成
 *
 * @return array
 */
function fn_settings_variants_addons_sonypayment_subpay_second_process_type()
{

    // 配列を初期化
    $variants = array();

    $variants['capture'] = __("jp_sonys_gathering");
    $variants['auth_only'] = __("jp_sonys_auth_only");

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
function fn_sonys_get_params($type, $order_info='', $processor_data='')
{
	$params = array();

    // マーチャントID
    $params['MerchantId'] = Registry::get('addons.sonypayment_subpay.merchant_id');

    // マーチャントパスワード
    $params['MerchantPass'] = Registry::get('addons.sonypayment_subpay.merchant_pass');

    // 取引の処理日付（YYYYMMDD）
    $params['TransactionDate'] = date('Ymd');

    // 取引の処理ID
    $params['OperateId'] = fn_sonys_get_operate_id($type, $processor_data);

    // 自由領域
    $params['MerchantFree1'] = "";
    $params['MerchantFree2'] = "";
    $params['MerchantFree3'] = "";

	// 支払方法別の送信パラメータをセット
	fn_sonys_get_specific_params($params, $type, $order_info, $processor_data);

	return $params;
}




/**
 * ソニーペイメントに送信する取引電文種別を取得
 *
 * @param $type
 * @param $processor_data
 * @return bool|string
 */
function fn_sonys_get_operate_id($type, $processor_data='')
{
    switch($type){
        case 'cc':
        case 'ccreg_payment':
            if($processor_data['processor_params']['process_type'] == 'auth_only'){
                // 与信
                return '1Auth';
            }else{
                // 与信売上計上
                return '1Gathering';
            }
        case 'ccreg_register':
            return '4MemAdd';
        case 'ccreg_update':
            return '4MemChg';
        case 'ccreg_ref':
            return '4MemRefM';
        case 'ccreg_invalidate':
            return '4MemInval';
        case 'ccreg_uninvalidate':
            return '4MemUnInval';
        case 'ccreg_delete':
            return '4MemDel';
        case 'cc_sales_confirm':
            return '1Capture';
        case 'cc_auth_cancel':
        case 'cc_sales_cancel':
            return '1Delete';
        case 'cc_change':
            return '1Change';
        case 'cc_search':
            return '1Search';
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
function fn_sonys_get_specific_params(&$params, $type, $order_info='', $processor_data='')
{
    switch($type) {
        // 登録済みクレジットカード決済
        case 'ccreg_payment':
            // アドオン設定画面で店舗コードが設定されている場合
            if( Registry::get('addons.sonypayment_subpay.tenant_id') ){
                // 店舗コードをセット
                $mem_prefix = Registry::get('addons.sonypayment_subpay.tenant_id');
            }else{
                $mem_prefix = '';
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // 会員登録変更対応（期限切れカードの場合は無効解除できない）
            ///////////////////////////////////////////////
            // 会員ID
            $params['KaiinId'] =fn_sonys_get_kaiin_id($mem_prefix, $order_info);
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 EOF
            ///////////////////////////////////////////////

            // 会員パスワード
            $params['KaiinPass'] = fn_sonys_get_kaiin_pass($order_info['user_id']);

            // クレジットカード支払区分
            $params['PayType'] = '01';

            // 利用金額
            $params['Amount'] = round($order_info['total']);

            // セキュリティコードによる認証を行う場合
            if( $processor_data['processor_params']['use_cvv'] == 'true' ){
                // セキュリティコード
                $params['SecCd'] = $order_info['payment_info']['cvv2'];
            }

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // マーケットプレイス版対応
            ///////////////////////////////////////////////
            // マーケットプレイス版の場合
            if( fn_allowed_for('MULTIVENDOR') ) {
                $tenant_id = db_get_field("SELECT tenant_id FROM ?:jp_sonys_companies WHERE company_id = ?i", $order_info['company_id']);
                // 出品者の店舗コードが登録されている場合
                if( !empty($tenant_id) ) {
                   // 出品者の店舗コードをセット
                   $params['TenantId'] = $tenant_id;
                }
                // アドオン設定画面で店舗コードが設定されている場合
                elseif (Registry::get('addons.sonypayment_subpay.tenant_id')) {
                    // アドオン設定の店舗コードをセット
                    $params['TenantId'] = Registry::get('addons.sonypayment_subpay.tenant_id');
                }
            }
            else {
                // アドオン設定画面で店舗コードが設定されている場合
                if (Registry::get('addons.sonypayment_subpay.tenant_id')) {
                    // 店舗コードをセット
                    $params['TenantId'] = Registry::get('addons.sonypayment_subpay.tenant_id');
                }
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 EOF
            ///////////////////////////////////////////////

            // CS-Cart の注文番号を付与
            $params['MerchantFree1'] = $order_info['order_id'];

            break;

        // クレジットカード情報の登録・更新
        case 'ccreg_register':
        case 'ccreg_update':
            // アドオン設定画面で店舗コードが設定されている場合
            if( Registry::get('addons.sonypayment_subpay.tenant_id') ){
                // 店舗コードをセット
                $mem_prefix = Registry::get('addons.sonypayment_subpay.tenant_id');
            }else{
                $mem_prefix = '';
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // 会員登録変更対応（期限切れカードの場合は無効解除できない）
            ///////////////////////////////////////////////
            // 会員ID
            $params['KaiinId'] = fn_sonys_get_kaiin_id($mem_prefix, $order_info);
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 EOF
            ///////////////////////////////////////////////

            // 会員パスワード
            $params['KaiinPass'] = fn_sonys_get_kaiin_pass($order_info['user_id']);

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            // 支払情報にトークン認証が登録されているか確認
            $org_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_info[order_id]);
            $org_payment_method_data = fn_get_payment_method_data($org_payment_id);

            // トークンが登録されていない場合
            if (!$order_info['payment_info']['token'][0]) {
                // クレジットカード番号（数値以外の値は削除）
                $card_number = mb_ereg_replace('[^0-9]', '', $order_info['payment_info']['card_number']);
                $params['CardNo'] = $card_number;

                // クレジットカード有効期限
                $params['CardExp'] = $order_info['payment_info']['expiry_year'] . $order_info['payment_info']['expiry_month'];
            }
            // トークン認証コードが登録されている場合
            else {
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2019 BOF
                // マーケットプレイス版対応
                ///////////////////////////////////////////////
                // トークン
                if( !empty($order_info['payment_info']['token'][0]) ) {
                    $params['Token'] = $order_info['payment_info']['token'][0];
                }
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2019 EOF
                ///////////////////////////////////////////////
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            break;

        // クレジットカード情報の照会・会員無効・会員無効解除・会員削除
        case 'ccreg_ref':
        case 'ccreg_invalidate':
        case 'ccreg_uninvalidate':
        case 'ccreg_delete':
            // アドオン設定画面で店舗コードが設定されている場合
            if( Registry::get('addons.sonypayment_subpay.tenant_id') ){
                // 店舗コードをセット
                $mem_prefix = Registry::get('addons.sonypayment_subpay.tenant_id');
            }else{
                $mem_prefix = '';
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // 会員登録変更対応（期限切れカードの場合は無効解除できない）
            ///////////////////////////////////////////////
            // 会員ID
            $params['KaiinId'] = fn_sonys_get_kaiin_id($mem_prefix, $order_info);
             ///////////////////////////////////////////////
             // Modified by takahashi from cs-cart.jp 2019 EOF
             ///////////////////////////////////////////////

            // 会員パスワード
            $params['KaiinPass'] = fn_sonys_get_kaiin_pass($order_info['user_id']);

            break;

        // 売上計上 / 与信取消 / 売上取消 / カード決済取引参照 / 金額変更 / データ削除
        case 'cc_sales_confirm':
        case 'cc_auth_cancel':
        case 'cc_sales_cancel':
        case 'cc_search':
        case 'cc_change':
            // 後続処理用のプロセスIDおよびプロセスパスワードを取得
            list($process_id, $process_pass) = fn_sonys_get_process_info($order_info['order_id']);

            // 後続処理用のプロセスIDおよびプロセスパスワードが存在する場合
            if( !empty($process_id) && !empty($process_pass) ){
                // プロセスID
                $params['ProcessId'] = $process_id;

                // プロセスパスワード
                $params['ProcessPass'] = $process_pass;
            }

            // 登録済みカード決済の場合、会員IDと会員パスワードもセットする
            if( !empty($processor_data['processor_script']) && $processor_data['processor_script'] == 'sonypayment_subpay.php' ){
                // アドオン設定画面で店舗コードが設定されている場合
                if( Registry::get('addons.sonypayment_subpay.tenant_id') ){
                    // 店舗コードをセット
                    $mem_prefix = Registry::get('addons.sonypayment_subpay.tenant_id');
                }else{
                    $mem_prefix = '';
                }
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2019 BOF
                // 会員登録変更対応（期限切れカードの場合は無効解除できない）
                ///////////////////////////////////////////////
                // 会員ID
                $params['KaiinId'] = fn_sonys_get_kaiin_id($mem_prefix, $order_info);
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2019 EOF
                ///////////////////////////////////////////////

                // 会員パスワード
                $params['KaiinPass'] = fn_sonys_get_kaiin_pass($order_info['user_id']);
            }

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            // トークン決済の場合、会員情報が登録されているか確認する
            if( !empty($processor_data['processor_script']) && $processor_data['processor_script'] == 'sonypayment_subpay.php') {

                $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_info['order_id'], 'P');

                // 注文データ内に支払関連情報が存在する場合
                if( !empty($payment_info) ) {

                    // 支払情報が暗号化されている場合は復号化して変数にセット
                    if (!is_array($payment_info)) {
                        $info = @unserialize(fn_decrypt_text($payment_info));
                    } else {
                        // 支払情報を変数にセット
                        $info = $payment_info;
                    }

                    if ($info['jp_sonys_registered_cc'] == 'Y') {
                        // アドオン設定画面で店舗コードが設定されている場合
                        if( Registry::get('addons.sonypayment_subpay.tenant_id') ){
                            // 店舗コードをセット
                            $mem_prefix = Registry::get('addons.sonypayment_subpay.tenant_id');
                        }else{
                            $mem_prefix = '';
                        }

                        // 会員ID
                        $params['KaiinId'] = sprintf("%012s", $mem_prefix . $order_info['user_id']);

                        // 会員パスワード
                        $params['KaiinPass'] = fn_sonys_get_kaiin_pass($order_info['user_id']);
                    }

                }
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            // 売上計上処理の場合
            if($type == 'cc_sales_confirm'){
                // 売上計上日（YYYYMMDD）
                $params['SalesDate'] = date('Ymd');
            // カード金額変更の場合
            }elseif($type == 'cc_change'){
                // 利用金額
                $params['Amount'] = round($order_info['total']);

            }

            // CS-Cart の注文番号を付与
            $params['MerchantFree1'] = $order_info['order_id'];

            break;

        default:
            // do nothing
            break;
    }
}




/**
 * ソニーペイメントへリクエストを送信
 *
 * @param $params
 * @param $processor_data
 * @param string $action
 * @return array
 */
function fn_sonys_send_request($params, $processor_data, $action = 'checkout')
{
    switch($action){
        // 決済手続き
        case 'checkout':
            // 本番環境の場合
            if( $processor_data['processor_params']['mode'] == 'live' ){
                $target_url = 'https://www.e-scott.jp/online/aut/OAUT002.do';
            //  テスト環境の場合
            }else{
                $target_url = 'https://www.test.e-scott.jp/online/aut/OAUT002.do';
            }
            break;

        // カード情報お預かり機能
        case 'ccreg':
            // 本番環境の場合
            if( $processor_data['processor_params']['mode'] == 'live' ){
                $target_url = 'https://www.e-scott.jp/online/crp/OCRP005.do';
            //  テスト環境の場合
            }else{
                $target_url = 'https://www.test.e-scott.jp/online/crp/OCRP005.do';
            }
            break;

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // 3Dセキュア認証対応
        ///////////////////////////////////////////////
        // 3Dセキュア認証
        case 'tds':
            // パラメータの暗号化
            $params = fn_sonys_encrypt_params($params, $processor_data);

            // 本番環境の場合
            if( $processor_data['processor_params']['mode'] == 'live' ){
                $target_url = 'https://www.e-scott.jp/online/tds/OTDS010.do';
                //  テスト環境の場合
            }else{
                $target_url = 'https://www.test.e-scott.jp/online/tds/OTDS010.do';
            }
            $result_param_str = Http::post($target_url, $params);
            parse_str($result_param_str,$result_params);
            return $result_params;
            break;
        ///////////////////////////////////////////////
        // Modified by tommy from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////

        default:
            // do nothing
            break;
    }

    // パラメータの値をすべてURLエンコードする
    foreach($params as $key => $param){
        $params[$key] = $params[$key];
    }

    // ソニーペイメントにデータを送信し、戻り値を配列化
    $result = Http::post($target_url, $params);
    $result = explode("&", $result);
    $result_params = array();
    foreach($result as $val){
        $key_val = explode('=', $val);
        if(!empty($key_val)) $result_params[$key_val[0]] = $key_val[1];
    }

    return $result_params;
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
function fn_sonys_format_payment_info($type, $order_id, $payment_info, $result_params, $flg_comments = false)
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
        $order_comments .= __('jp_sonys_'. $type . '_info') . "\n";
        /////////////////////////////////////////////////////////
        // 追記用コメントの初期化 EOF
        /////////////////////////////////////////////////////////

        // クレジットカード決済における分割払い判定フラグを初期化
        $flg_cc_installment = false;

        // 支払情報がすでに存在する場合
        if( !empty($info) ){
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 BOF
            ////////////////////////////////////////////////////////////////////
            foreach($info as $key => $val){
                switch($key){
                    ////////////////////////////////////////////////////////////////////
                    // クレジットカード決済 BOF
                    ////////////////////////////////////////////////////////////////////
                    // 支払方法はコードに対応した支払方法に変換
                    case "jp_cc_method":
                        switch($val){
                            // 一括
                            case '10':
                                $info[$key] = __('jp_cc_onetime');
                                break;
                            // 分割
                            case '61':
                                $info[$key] = __('jp_cc_installment');
                                $flg_cc_installment = true;
                                break;
                            // ボーナス一括
                            case '80':
                                $info[$key] = __('jp_cc_bonus_onetime');
                                break;
                            // リボ払い
                            case '88':
                                $info[$key] = __('jp_cc_revo');
                                break;
                            default:
                                // do nothing
                                break;
                        }
                        break;

                    case "jp_cc_installment_times":
                        // 支払回数には末尾に「回」を追記
                        if( $info['jp_cc_method'] == 61 || $flg_cc_installment ){
                            $info[$key] = $info[$key] . __('jp_paytimes_unit');
                        }else{
                            unset($info[$key]);
                        }
                        break;
                    ////////////////////////////////////////////////////////////////////
                    // クレジットカード決済 BOF
                    ////////////////////////////////////////////////////////////////////

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
            $info['jp_sonys_transaction_id'] = $result_params['TransactionId'];
        }

        // 取引日付
        if( !empty($result_params['TransactionDate']) ){
            $info['jp_sonys_transaction_date'] = $result_params['TransactionDate'];
        }
        ////////////////////////////////////////////////////////////////////
        // 共通項目 EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // クレジットカード BOF
        ////////////////////////////////////////////////////////////////////
        if( $type == 'cc' ){
            // オーソリが正常に完了した場合
            if( $result_params['ResponseCd'] == 'OK' ){
                // 決済データの処理方法
                if( !empty($result_params['OperateId']) ){
                    $info['jp_sonys_process_type'] = fn_sonys_get_process_type($result_params['OperateId']);
                }

                // カード会社コード
                if( !empty($result_params['CompanyCd']) ){
                    $info['jp_sonys_company_code'] = $result_params['CompanyCd'];
                }

                // 承認番号
                if( !empty($result_params['ApproveNo']) ){
                    $info['jp_sonys_approve_no'] = $result_params['ApproveNo'];
                }
           }
        }
        ////////////////////////////////////////////////////////////////////
        // クレジットカード EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // クレジットカード 登録済みカード決済 BOF
        ////////////////////////////////////////////////////////////////////

        $exist_payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

        // 注文データ内に支払関連情報が存在する場合
        if( !empty($exist_payment_info) ) {

            // 支払情報が暗号化されている場合は復号化して変数にセット
            if (!is_array($exist_payment_info)) {
                $exist_info = @unserialize(fn_decrypt_text($exist_payment_info));
            } else {
                // 支払情報を変数にセット
                $exist_info = $exist_payment_info;
            }
        }

        $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors JOIN ?:payments ON ?:payment_processors.processor_id = ?:payments.processor_id JOIN ?:orders ON ?:payments.payment_id = ?:orders.payment_id WHERE order_id = ?i;", $order_id);

        // トークン決済用 - 登録済みカードで決済した情報を保持する（注文決済時と減額処理時）
        if( $type == 'ccreg_payment' || ($exist_info['jp_sonys_registered_cc'] == 'Y' && $processor_script == 'sonypayment_subpay.php')){
            // 登録済みクレジットーカードで決済したかのフラグ
            $info['jp_sonys_registered_cc'] = 'Y';
        }
        ////////////////////////////////////////////////////////////////////
        // クレジットカード 登録済みカード決済　EOF
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
 * @return bool|string
 */
function fn_sonys_get_process_type($operate_id)
{
    if(empty($operate_id)) return false;

    switch($operate_id){
        case '1Check':
            return __('jp_sonys_check');
            break;
        case '1Auth':
            return __('jp_sonys_auth');
            break;
        case '1Capture':
            return __('jp_sonys_capture');
            break;
        case '1Gathering':
            return __('jp_sonys_gathering');
            break;
        case '1Change':
            return __('jp_sonys_change');
            break;
        case '1Delete':
            return __('jp_sonys_delete');
            break;
        case '1Search':
            return __('jp_sonys_search');
            break;
        case '1ReAuth':
            return __('jp_sonys_reauth');
            break;
    }
}




/**
 * ソニーペイメントの取消およびデータ削除処理の対象となる注文であるかを判定
 *
 * @param $payment_id
 * @return bool
 */
function fn_sonys_is_deletable($payment_id)
{
    // 注文で使用されている決済代行業者IDを取得
    $payment_method_data = fn_get_payment_method_data($payment_id);
    if( empty($payment_method_data) ) return false;
    $processor_id = $payment_method_data['processor_id'];
    if( empty($processor_id) ) return false;
    $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

    // 決済代行業者がスマートリンクの場合はtrueを返す
    switch($pr_script){
        case 'sonypayment_subpay.php':    // ソニーペイメントサービス - 定期購入
            return true;
            break;
        default:
            return false;
    }
}




/**
 * 指定された決済方法に関するデータを取得
 *
 * @return array|bool
 */
function fn_sonys_get_processor_data()
{
    $script = 'sonypayment_subpay.php';

    // 指定された決済方法に関するデータを取得
    $payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = ?s AND ?:payments.status = 'A'", $script);

    $processor_data = fn_get_processor_data($payment_id);

    // 決済方法に関するデータが存在する場合
    if( !empty($processor_data) ){
        // 取得したデータを返す
        return $processor_data;
    // 決済方法に関するデータが存在しない場合
    }else{
        // falseを返す
        return false;
    }
}
/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// クレジットカード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////
/**
 * クレジットカード情報の登録・更新
 *
 * @param $order_info
 * @param $processor_data
 */
function fn_sonys_register_cc_info($order_info, $processor_data)
{
    // ユーザーID決済に関するデータを取得
    $processor_data = fn_sonys_get_processor_data();

    $mode = $processor_data['processor_params']['mode'];

    // クレジットカード情報を登録済みかチェック
    $kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $order_info['user_id'], 'sonypayment_subpay_' . $mode);

    // 削除済みクレジットカード情報を初期化
    $deleted_kaiin_pass = '';

    // クレジットカード情報が登録済みの場合
    if(!empty($kaiin_pass)){
        // 実行する処理は「カード情報の更新」
        $type = 'ccreg_update';

    // クレジットカード情報が未登録の場合
    }else{
        // 削除済みクレジットカード情報が存在するかチェック
        $deleted_kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_sonys_deleted_quickpay WHERE user_id = ?i AND mode = ?s", $order_info['user_id'], $mode);

        // 削除済みクレジットカード情報が存在する場合
        if(!empty($deleted_kaiin_pass)){
            // 実行する処理は「カード情報の更新」
            $type = 'ccreg_update';
        }else{
            // 実行する処理は「カード情報の登録」
            $type = 'ccreg_register';
        }
    }

    // 会員が無効化されている場合は有効化する
    if($type == 'ccreg_update'){
        // ソニーペイメントに登録している会員情報のステータスを取得
        $card_info = fn_sonys_get_registered_card_info($order_info['user_id'], true);
        $kaiin_status = $card_info['status'];
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // 会員登録変更対応（期限切れカードの場合は無効解除できない）
        ///////////////////////////////////////////////
        $card_exp = $card_info['card_exp'];

        // カード期限が切れていると仮定したテスト用
        //$card_exp = '1812';
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////

        // 会員ステータスに応じて処理を実行
        switch($kaiin_status){
            case 0: // 有効
                // do nothing
                break;
            case 1: // カード無効
            case 2: // Login回数無効
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // 会員登録変更対応（期限切れカードの場合は無効解除できない）
            ///////////////////////////////////////////////
                // 会員無効解除に必要なパラメータを取得
                $ccreg_uninvalidate_params = fn_sonys_get_params('ccreg_uninvalidate', $order_info, $processor_data);

                // 削除済みクレジットカード情報が存在する場合、削除済みカード情報に含まれる会員パスワードをセット
                if(!empty($deleted_kaiin_pass)){
                    $ccreg_uninvalidate_params['KaiinPass'] = $deleted_kaiin_pass;
                }

                // カードの期限が切れてない場合
                if( $card_exp >= date("ym") ) {
                    // 会員無効解除
                    $ccreg_uninvalidate_result_params = fn_sonys_send_request($ccreg_uninvalidate_params, $processor_data, 'ccreg');

                    // 会員無効解除に失敗した場合
                    if ( empty($ccreg_uninvalidate_result_params['ResponseCd']) || $ccreg_uninvalidate_result_params['ResponseCd'] != 'OK' ) {
                        // エラーメッセージを表示して処理を終了
                        fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_register_failed') . '<br />' . __('jp_sonys_error_code') . ' : ' . $ccreg_uninvalidate_result_params['ResponseCd']);
                        return false;
                    }
                }
                // カードの期限が切れている場合（会員無効解除できない）
                else {
                    $type = 'ccreg_register';
                    $order_info['is_card_exp'] = true;
                }
                break;

            case 3: // 会員無効
                // 会員無効解除に必要なパラメータを取得
                $ccreg_uninvalidate_params = fn_sonys_get_params('ccreg_uninvalidate', $order_info, $processor_data);

                // 削除済みクレジットカード情報が存在する場合、削除済みカード情報に含まれる会員パスワードをセット
                if(!empty($deleted_kaiin_pass)){
                    $ccreg_uninvalidate_params['KaiinPass'] = $deleted_kaiin_pass;
                }

                // カードの期限が切れてない場合
                if( $card_exp >= date("ym") ) {
                    // 会員無効解除
                    $ccreg_uninvalidate_result_params = fn_sonys_send_request($ccreg_uninvalidate_params, $processor_data, 'ccreg');

                    // 会員無効解除に失敗した場合
                    if ( empty($ccreg_uninvalidate_result_params['ResponseCd']) || $ccreg_uninvalidate_result_params['ResponseCd'] != 'OK' ) {
                        // エラーメッセージを表示して処理を終了
                        fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_register_failed') . '<br />' . __('jp_sonys_error_code') . ' : ' . $ccreg_uninvalidate_result_params['ResponseCd']);
                        return false;
                    }
                }
                // カードの期限が切れている場合（会員無効解除できない）
                else {
                    $type = 'ccreg_register';
                    $order_info['is_card_exp'] = true;
                }
                break;
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 EOF
            ///////////////////////////////////////////////
            default:    // その他のステータス
                // エラーメッセージを表示して処理を終了
                fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_register_failed'));
                return false;
        }
    }

    // クレジットカード情報の登録・更新に必要なパラメータを取得
    $ccreg_params = fn_sonys_get_params($type, $order_info, $processor_data);

    // 削除済みクレジットカード情報が存在する場合、削除済みカード情報に含まれる会員パスワードをセット
    if(!empty($deleted_kaiin_pass)){
        $ccreg_params['KaiinPass'] = $deleted_kaiin_pass;
    }

    // クレジットカード情報の登録・更新
    $result_params = fn_sonys_send_request($ccreg_params, $processor_data, 'ccreg');

    // ソニーペイメントより処理結果が返された場合
    if ( !empty($result_params['TransactionId']) ) {
        // 処理でエラーが発生している場合
        if( $result_params['ResponseCd'] != 'OK' ){
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            // エラーメッセージを表示
            //fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_register_failed') . '<br />' . __('jp_sonys_error_code') . ' : ' . $result_params['ResponseCd']);
            $sonys_err_msg = fn_sonys_get_err_msg($result_params['ResponseCd']);
            fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_register_failed') . '<br />' . __('jp_sonys_error_code') . ' : ' . $sonys_err_msg);
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // 会員登録変更対応（期限切れカードの場合は無効解除できない）
            ///////////////////////////////////////////////
            // 会員更新履歴をリセット
            if( $order_info['is_card_exp'] ) {
                db_query("UPDATE ?:jp_sonys_kaiin_change SET change_cnt = change_cnt - 1 WHERE user_id = ?i", $order_info['user_id']);
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 EOF
            ///////////////////////////////////////////////

            return false;
        // クレジットカード情報の登録・更新が正常に終了した場合
        }else{
            $_data = array('user_id' => $order_info['user_id'],
                'payment_method' => 'sonypayment_subpay_' . $mode,
                'quickpay_id' => $ccreg_params['KaiinPass'],
            );
            db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);

            // 登録・更新完了メッセージを表示
            fn_set_notification('N', __('information'), __('jp_sonys_register_success'));
            return true;
        }
    }

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // 会員登録変更対応（期限切れカードの場合は無効解除できない）
    ///////////////////////////////////////////////
    if( $order_info['is_card_exp'] ) {
        unset($order_info['is_card_exp']);
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////
}




/**
 * ソニーペイメントに送信する会員パスワードを取得
 *
 * @param $user_id
 * @return array|string
 */
function fn_sonys_get_kaiin_pass($user_id)
{
    // ユーザーID決済に関するデータを取得
    $processor_data = fn_sonys_get_processor_data();

    $mode = $processor_data['processor_params']['mode'];

    // クレジットカード情報を登録済みかチェック
    $kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'sonypayment_subpay_' . $mode);

    // クレジットカード情報を登録済みの場合
    if(!empty($kaiin_pass)){
        // 登録に使用したパスワードを返す
        return $kaiin_pass;
    }else{

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // 会員を無効とした場合に請求ステータス変更処理でK71エラーが出る問題を修正
        ///////////////////////////////////////////////
        // 削除済みパスワードがあるかチェック
        $kaiin_del_pass = db_get_field("SELECT quickpay_id FROM ?:jp_sonys_deleted_quickpay WHERE user_id = ?i AND mode = ?s", $user_id, $mode);

        if(!empty($kaiin_del_pass)){
            // 削除済みパスワードを返す
            return $kaiin_del_pass;
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////

        // 暗号化されたCS-Cartログインパスワードの先頭12桁を返す
        $encrypted_password = db_get_field("SELECT password FROM ?:users WHERE user_id = ?i", $user_id);
        return substr($encrypted_password, 0, 12);
    }
}




/**
 * 登録済みカード情報を取得
 *
 * @param $user_id
 */
function fn_sonys_get_registered_card_info($user_id, $get_deleted = false)
{
    // ユーザーID決済に関するデータを取得
    $processor_data = fn_sonys_get_processor_data();

    $mode = $processor_data['processor_params']['mode'];

    // クレジットカード情報が未登録の場合はfalseを返す
    $kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'sonypayment_subpay_' . $mode);

    // 削除済みカード情報も検索する場合
    if( empty($kaiin_pass) && $get_deleted){
        $kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_sonys_deleted_quickpay WHERE user_id = ?i AND mode = ?s", $user_id, $mode);
    }

    // クレジットカード情報が未登録の場合はfalseを返す
    if(empty($kaiin_pass)) return false;

    // クレジットカード情報の照会に必要なパラメータを取得
    $order_info = array();
    $order_info['user_id'] = $user_id;
    $cc_ref_params = fn_sonys_get_params('ccreg_ref', $order_info);

    // クレジットカード情報の照会
    $result_params = fn_sonys_send_request($cc_ref_params, $processor_data, 'ccreg');

    // ソニーペイメントより処理結果が返された場合
    if ( !empty($result_params['TransactionId']) ) {
        // 処理でエラーが発生している場合
        if( $result_params['ResponseCd'] != 'OK' || empty($result_params['CardNo']) ){
            return '';
        }else{
            $card_info = array('card_number' => $result_params['CardNo'], 'card_exp' => $result_params['CardExp'], 'status' => $result_params['KaiinStatus']);
            return $card_info;
        }
    }
    return '';
}




/**
 * 登録済みカード情報の削除（CS-CartのDBからのみ削除。ソニーペイメント側の情報は削除しない）
 *
 * @param $user_id
 */
function fn_sonys_delete_card_info($user_id, $flg_notify = true)
{
    /////////////////////////////////////////////////////////////////////////////////////////////
    // メモ
    // ソニーペイメントでは一度削除した会員IDは再利用できないため、
    // 登録済カード情報を削除する場合にはCS-Cart側のレコードのみ削除する
    // そのため、CS-Cart側で登録済みカード情報を削除し、その後再度登録する場合には
    // 「新規登録(4MemAdd)」ではなく「更新(4MemChg)」となる。
    // 「更新(4MemChg)」の際には「会員ID」（店舗ID+CS-CartのユーザーID）と「パスワード」が必要。
    // jp_cc_quickpayテーブルから単純にレコードを削除してしまうとパスワードがわからなくなるので、
    // jp_sonys_deleted_quickpay にデータを移しておく処理が必要
    /////////////////////////////////////////////////////////////////////////////////////////////

    // ユーザーID決済に関するデータを取得
    $processor_data = fn_sonys_get_processor_data();

    $mode = $processor_data['processor_params']['mode'];

    // ソニーペイメントに登録した会員パスワードを取得
    $kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'sonypayment_subpay_' . $mode);

    // ソニーペイメント側の会員情報の無効化に必要なパラメータを取得
    $order_info = array();
    $order_info['user_id'] = $user_id;
    $ccreg_params = fn_sonys_get_params('ccreg_invalidate', $order_info);

    // 会員情報の無効化
    $result_params = fn_sonys_send_request($ccreg_params, $processor_data, 'ccreg');

    // ソニーペイメント側で正常に処理が終了した場合
    if ( !empty($result_params['ResponseCd']) && $result_params['ResponseCd'] == 'OK' ){
        // CS-CartのDB上から登録済みカード決済用レコードを削除
        db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'sonypayment_subpay_' . $mode);

        // ソニーペイメントネットワークの削除済みカード情報を格納するテーブルにレコードをセット
        $_data['user_id'] = $user_id;
        $_data['quickpay_id'] = $kaiin_pass;
        $_data['mode'] = $mode;
        db_query("REPLACE INTO ?:jp_sonys_deleted_quickpay ?e", $_data);

        if($flg_notify){
            fn_set_notification('N', __('notice'), __('jp_sonys_delete_success'));
        }

    // ソニーペイメント側でエラーが発生した場合
    }else{
        fn_set_notification('E', __('error'), __('jp_sonys_delete_failed'));
    }
}




/**
 * エラーメッセージを取得する
 *
 * @param $response_cd
 * @return string
 */
function fn_sonys_get_err_msg($response_cd)
{
    $err_msg = $response_cd . ' : ';

    $response_cd_lower = strtolower($response_cd);

    switch($response_cd){

        default:
            $err_msg = __('jp_sonys_error_code') . ' : ' . $response_cd;
    }

    return $err_msg;
}




/**
 * 決済ステータスおよび処理通番をDBに保存
 *
 * @param $order_id
 * @param $status_code
 * @param string $transaction_id
 * @return bool
 */
function fn_sonys_update_cc_status_code($order_id, $status_code, $transaction_id = '')
{
    // 利用額変更の場合は「与信/売上計上/与信売上計上」といったステータスは変更されないので処理を終了
    if($status_code == '1Change') return false;

    //////////////////////////////////////////////////////////////////////
    // 決済ステータスをDBに保存 BOF
    //////////////////////////////////////////////////////////////////////
    $_data = array (
        'order_id' => $order_id,
        'status_code' => $status_code,
    );
    db_query("REPLACE INTO ?:jp_sonys_status ?e", $_data);
    //////////////////////////////////////////////////////////////////////
    // 決済ステータスをDBに保存 EOF
    //////////////////////////////////////////////////////////////////////

    // 収納代行サービスの場合はここで処理を終了
    if($status_code == '2Add' || $status_code == '2Chg' || $status_code == '2Del') return false;

    //////////////////////////////////////////////////////////////////////
    // クレジット請求ステータスと処理通番を注文詳細に表示 BOF
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
    $info['jp_sonys_status'] = fn_sonys_get_cc_status_name($status_code);

    // 処理通番をセット
    if( !empty($transaction_id) ){
        $info['jp_sonys_transaction_id'] = $transaction_id;
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
    // クレジット請求ステータスと処理通番を注文詳細に表示 EOF
    //////////////////////////////////////////////////////////////////////
}




/**
 * 後続処理のための ProcessId と ProcessPass をDBに保存する
 *
 * @param $order_id
 * @param $result_params
 * @return bool
 */
function fn_sonys_update_set_process_info($order_id, $result_params)
{
    if( empty($order_id) || empty($result_params['ProcessId']) || empty($result_params['ProcessPass']) ){
        return false;
    }else{
        $_process_info = array(
            'order_id' => $order_id,
            'process_id' => $result_params['ProcessId'],
            'process_pass' => $result_params['ProcessPass']
        );

        db_query("REPLACE INTO ?:jp_sonys_process_info ?e", $_process_info);
    }
}




/**
 * 後続処理用のプロセスIDとプロセスパスワードを取得
 *
 * @param $order_id
 * @return array|bool
 */
function fn_sonys_get_process_info($order_id)
{
    // 注文IDが指定されていない場合はfalseを返す
    if( empty($order_id) ) return false;

    // 後続処理用のプロセスIDとプロセスパスワードを抽出
    $process_info = db_get_row("SELECT * FROM ?:jp_sonys_process_info WHERE order_id = ?i", $order_id);

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
 * 売上計上 / 取消 / 利用額変更 / データ削除 の実行
 *
 * @param $order_id
 * @param string $type
 */
function fn_sonys_send_cc_request($order_id, $type = 'cc_sales_confirm')
{
    // 注文IDが指定されていない場合はfalseを返す
    if( empty($order_id) ) return false;

    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_sonys_check_process_validity($order_id, $type);

    if(!$is_valid_order) return false;

    // 支払方法に関するデータを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);

    // 注文情報を取得
    $order_info = fn_get_order_info($order_id);

    // 売上計上 / 取消 / 利用額変更 / データ変更 / データ削除 に必要なパラメータを取得
    $params = fn_sonys_get_params($type, $order_info, $processor_data);

    // 処理の実行（売上計上 / 取消 / 利用額変更 / データ変更 / データ削除）
    $action = 'checkout';

    $result_params = fn_sonys_send_request($params, $processor_data, $action);

    // 処理が正常終了した場合
    if( $result_params['ResponseCd'] == 'OK' ){
        $transaction_id = '';
        if( !empty($result_params['TransactionId']) ) $transaction_id = $result_params['TransactionId'];
        // CS-Cart注文情報内の請求ステータスと請求ステータスコードを更新
        fn_sonys_update_cc_status($order_id, $type, $transaction_id);
    // 処理でエラーが発生した場合
    }else{
        // エラーメッセージを表示
        $is_valid_order = false;
        fn_sonys_get_cc_error_message($type, $order_id, $result_params);
    }
    return $is_valid_order;
}




/**
 * 注文データ内に格納されたクレジット請求ステータスや処理通番を更新
 *
 * @param $order_id
 * @param string $type
 * @param string $transaction_id
 */
function fn_sonys_update_cc_status( $order_id, $type = 'cc_sales_confirm', $transaction_id = '')
{
    // クレジット請求ステータスを初期化
    $status_code = '';

    // 処理内容に応じてセットする値を変更
    switch($type){
        // 売上計上
        case 'cc_sales_confirm':
            $status_code = 'sales_confirm';
            $msg = __('jp_sonys_sales_completed');
            break;
        // 与信取消
        case 'cc_auth_cancel':
            $status_code = 'auth_cancel';
            $msg = __('jp_sonys_auth_cancelled');
            break;
        // 売上取消
        case 'cc_sales_cancel':
            $status_code = 'sales_cancel';
            $msg = __('jp_sonys_sales_cancelled');
            break;
        // その他
        default:
            // do nothing
    }

    // クレジット請求ステータスが設定されている場合
    if( !empty($status_code) ){
        // クレジット請求ステータスを更新
        fn_sonys_update_cc_status_code($order_id, $status_code, $transaction_id);
        // 処理完了メッセージを表示
        fn_set_notification('N', __('information'), $msg, 'K');
    }
}




/**
 * 売上計上 / 取消 / 利用額変更に関するエラーメッセージを取得
 *
 * @param $type
 * @param $order_id
 * @param $result_params
 */
function fn_sonys_get_cc_error_message($type, $order_id, $result_params)
{
    $is_diplay = true;

    switch($type){
        // 売上計上時のエラー
        case 'cc_sales_confirm':
            $title = __('jp_sonys_sales_confirm_error');
            $msg = str_replace('[oid]', $order_id, __('jp_sonys_sales_confirm_failed'));
            break;
        // 与信取消時のエラー
        case 'cc_auth_cancel':
            $title = __('jp_sonys_auth_cancel_error');
            $msg = str_replace('[oid]', $order_id, __('jp_sonys_auth_cancel_failed'));
            break;
        // 売上取消時のエラー
        case 'cc_sales_cancel':
            $title = __('jp_sonys_sales_cancel_error');
            $msg = str_replace('[oid]', $order_id, __('jp_sonys_sales_cancel_failed'));
            break;
        default:
            $is_diplay = false;
    }

    if(	$is_diplay ){
        // エラーメッセージを表示
        fn_set_notification('E', $title, '<br />' . $msg . '<br />' . __('jp_sonys_error_code') . ' : ' . $result_params['ResponseCd'], 'K');
    }
}




/** 指定した処理を行うのに適した注文であるかを判定（複数注文の一括処理時に使用）
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_sonys_check_process_validity( $order_id, $type )
{
    // 注文データからクレジット請求ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_sonys_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 売上計上または与信取消
        case 'cc_sales_confirm':
        case 'cc_auth_cancel':
            // 請求ステータスが「与信(1Auth)」の場合に処理可能
            if( $cc_status == '1Auth' ) return true;
            break;
        // 売上取消
        case 'cc_sales_cancel':
            // 請求ステータスが「与信売上計上」または「売上計上」の場合に処理可能
            if( $cc_status == '1Gathering' || $cc_status == 'sales_confirm' ) return true;
            break;
        // その他
        default:
            // do nothing
    }

    return false;
}




/**
 * 利用額変更処理が可能な注文であるかを判定
 *
 * @param $order_id
 * @return array
 */
function fn_sonys_is_changeable($order_id, $order_info, $processor_data)
{
    // 利用額変更可否フラグを初期化
    $flg_changeable = false;

    // 変更不可理由
    $unchangeable_reason = '';

    // プロセスIDとプロセスパスワードを取得
    list($process_id, $process_pass) = fn_sonys_get_process_info($order_id);

    // プロセスIDが存在する場合
    if( !empty($process_id) ){
        // 取引参照を実行
        $params = fn_sonys_get_params('cc_search', $order_info, $processor_data);
        $action = 'checkout';
        $result_params = fn_sonys_send_request($params, $processor_data, $action);

        // 取引参照処理が正常に完了して利用金額が取得できた場合
        if( $result_params['ResponseCd'] == 'OK' && !empty($result_params['Amount']) ){
            // 注文編集前後の注文金額を変数にセット
            $org_amount = (int)$result_params['Amount'];
            $chg_amount = (int)$order_info['total'];

            // 編集後の注文金額が編集前よと違う場合
            if($org_amount != $chg_amount) {
                // 編集前の注文で利用された決済方法を取得
                $org_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
                $org_payment_method_data = fn_get_payment_method_data($org_payment_id);
                $org_processor_id = $org_payment_method_data['processor_id'];

                // 編集前後で同じ決済方法が選択されている場合
                if (!empty($org_processor_id) && $org_processor_id == $processor_data['processor_id']) {
                    // ステータスコードを取得
                    $status_code = db_get_field("SELECT status_code FROM ?:jp_sonys_status WHERE order_id = ?i", $order_id);

                    // ステータスコードが存在する場合
                    if (!empty($status_code)) {
                        // 特定のステータスコードを持つ注文のみ利用額変更処理を許可
                        switch ($status_code) {
                            case '1Capture':        // 売上計上
                            case '1Change':         // 利用額変更
                            case '1Gathering':      // 与信売上計上
                            case 'sales_confirm':   // 売上計上
                                $flg_changeable = true;
                                break;
                            default:
                                $unchangeable_reason = __("jp_sonys_cc_change_error_pay_status");
                                break;
                        }
                    }
                }
                else{
                    $unchangeable_reason = __("jp_sonys_cc_change_error_pay_method");
                }
            }
            else{
                $unchangeable_reason = __("jp_sonys_cc_change_error_amount");
            }
        }
        else{
            $unchangeable_reason = __("jp_sonys_cc_change_error_ref");
        }
    }
    else{
        $unchangeable_reason = __("jp_sonys_cc_change_error_no_processid");
    }

    $results = array($flg_changeable, $unchangeable_reason);

    return $results;
}
/////////////////////////////////////////////////////////////////////////////////////
//  クレジットカード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////





/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 BOF
/////////////////////////////////////////////////////////////////////////////////////

// クレジット請求ステータス名を取得
function fn_sonys_get_cc_status_name($cc_status)
{
    if(!empty($cc_status)) {
        return __('jp_sonys_' . strtolower($cc_status));
    }
}
/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 EOF
/////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2018 BOF
// 登録済み決済支払方法がないとテストサイトがログに出る問題を修正
///////////////////////////////////////////////
/**
 * 支払情報が存在するか確認
 *
 * @param $template
 * @return boolean
 */
function fn_sonys_get_payment_info($template)
{
    $result = true;

    $path = "addons/sonypayment_subpay/views/orders/components/payments/".$template;

    $payment_info = db_get_field("SELECT payment_id FROM ?:payments WHERE template = ?s AND status=?s", $path, 'A');

    if(empty($payment_info)){
        $result = false;
    }

    return $result;

}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2018 EOF
///////////////////////////////////////////////




///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2019 BOF
// 会員登録変更対応（期限切れカードの場合は無効解除できない）
///////////////////////////////////////////////
/**
 * 会員IDを取得
 *
 * @param $mem_prefix
 * @param $order_info
 * @return string
 */
function fn_sonys_get_kaiin_id($mem_prefix, $order_info){

    $user_id = $order_info['user_id'];

    $change_cnt = db_get_field("SELECT change_cnt FROM ?:jp_sonys_kaiin_change WHERE user_id = ?i", $user_id);

    // カードの期限が切れている場合
    if( $order_info['is_card_exp'] ) {
        if( empty($change_cnt) || $change_cnt == 0 ) {
            db_query("REPLACE INTO ?:jp_sonys_kaiin_change VALUES(?i, ?i)", $user_id, 1);
            $change_prefix = '01';
        }
        else {
            db_query("UPDATE ?:jp_sonys_kaiin_change SET change_cnt = change_cnt + 1 WHERE user_id = ?i", $user_id);
            $change_prefix = sprintf("%02s", $change_cnt + 1);
        }
    }
    // カードの期限が切れていない場合
    else {
        if( empty($change_cnt) ) {
            $change_prefix = '00';
        }
        else {
            $change_prefix = sprintf("%02s", $change_cnt);
        }
    }

    $kaiin_id = $change_prefix . sprintf("%010s", $mem_prefix . $user_id);

    return $kaiin_id;
}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2019 EOF
///////////////////////////////////////////////

/**
 * カードが登録済みか確認する
 *
 * @param $user_id
 */
function fn_sonys_is_registered_card($user_id)
{
    // ユーザーID決済に関するデータを取得
    $processor_data = fn_sonys_get_processor_data();

    $mode = $processor_data['processor_params']['mode'];

    // クレジットカード情報が登録済みか確認
    $kaiin_pass = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'sonypayment_subpay_' . $mode);

    if( !empty($kaiin_pass) ){
        return true;
    }
    else{
        return false;
    }
}




/**
 * 初回無料商品か確認
 *
 * @param $product_id
 * @return bool
 */
function fn_sonys_is_first_free_product($product_id){

    $prod_price = db_get_field("SELECT price FROM ?:product_prices WHERE product_id = ?i", $product_id);

    if( $prod_price == 0 ){
        return true;
    }
    else {
        return false;
    }
}




/**
 * 定期商品の期限内か確認
 *
 * @param $product_id
 * @param $timestamp
 * @return bool
 */
function fn_sonys_within_product_period($product_id, $timestamp = TIME){
    $date_items = db_get_array("SELECT start_date, end_date FROM ?:jp_sonys_products WHERE product_id = ?i", $product_id);

    foreach($date_items as $date) {
        $start_date = $date['start_date'];
        $end_date = $date['end_date'];
    }

    if( empty($start_date) && empty($end_date) ){
        return true;
    }

    $today = strtotime(date('Y/m/d', $timestamp));

    if( $start_date > $today || ($end_date > 0 && $end_date < $today) ){
        return false;
    }
    else {
        return true;
    }
}




/**
 * 定期商品の送信頻度を取得
 *
 * @param $product_id
 * @return array
 */
function fn_sonys_get_product_freq($product_id){
    $deliver_data = db_get_array("SELECT deliver_day_w, deliver_day_bw, deliver_day_m, deliver_day_bm, deliver_time FROM ?:jp_sonys_products WHERE product_id = ?i", $product_id);

    $deliver_freq = array();
    foreach($deliver_data as $data) {
        $deliver_freq['deliver_day_w'] = unserialize($data['deliver_day_w']);
        $deliver_freq['deliver_day_bw'] = unserialize($data['deliver_day_bw']);
        $deliver_freq['deliver_day_m'] = unserialize($data['deliver_day_m']);
        $deliver_freq['deliver_day_bm'] = unserialize($data['deliver_day_bm']);
        $deliver_freq['deliver_time'] = unserialize($data['deliver_time']);
    }

    return $deliver_freq;
}




/**
 * ２回目以降の金額を取得
 *
 * @param $product_id
 * @return int
 */
function fn_sonys_get_second_product_price($product_id){

    $second_price = db_get_field("SELECT second_price FROM ?:jp_sonys_products WHERE product_id = ?i", $product_id);

    return $second_price;
}
##########################################################################################
// END その他の関数
##########################################################################################

##########################################################################################
// START 定期購入管理の関数
##########################################################################################
/**
 * 定期購入管理テーブルの更新
 *
 * @param $order_id
 * @param $payment_info
 */
function fn_sonys_update_subsc_data($order_id, $payment_info){
    // 注文情報
    $order_info = fn_get_order_info($order_id);

    // 定期配送頻度
    $deliver_freq = $payment_info['sonys_deliver_freq'];

    // 発送日
    $deliver_day = $payment_info['sonys_deliver_day'];

    // お届け時間
    $deliver_time = $payment_info['sonys_deliver_time'];

    // 現在時刻
    $time = TIME;
    $today = strtotime(date('Y-m-d', $time));

    // 商品データ
    foreach($order_info['product_groups'] as $product_group){
        foreach($product_group['products'] as $products){
            $product_id = $products['product_id'];
            $product = $products['product'];
            break;
        }
    }

    // 次回支払日を取得
    $next_payment_date = fn_sonys_get_next_payment_date($today, $deliver_freq, $deliver_day, true);

    // テーブルIDの最大値
    $max_subpay_id = db_get_field("SELECT MAX(subpay_id) FROM ?:jp_sonys_subsc_manager");
    $subpay_id = $max_subpay_id + 1;

    $_data = array(
        'subpay_id' => $subpay_id,
        'user_id' => $order_info['user_id'],
        'user_name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'product_id' => $product_id,
        'product' => $product,
        'status' => SONYS_SUBSC_STATUS_A,
        'next_payment_date' => $next_payment_date,
        'deliver_freq' => $deliver_freq,
        'deliver_day' => $deliver_day,
        'deliver_time' => $deliver_time,
        'cr_timestamp' => $time,
        'up_timestamp' => $time
    );

    // テーブルの更新
    db_query("INSERT INTO ?:jp_sonys_subsc_manager ?e", $_data);
    db_query("INSERT INTO ?:jp_sonys_subsc_history(subpay_id, order_id) VALUES(?i, ?i)", $subpay_id, $order_id);
}





/**
 * 定期購入管理テーブルの更新
 *
 * @param $subsc_info
 */
function fn_sonys_update_subsc_manager($subsc_info){

    // ID
    $subpay_id = $subsc_info['subpay_id'];

    // 継続状況
    $status = $subsc_info['status'];

    // 定期配送頻度
    $deliver_freq = $subsc_info['deliver_freq'];

    // 発送日
    $deliver_day = $subsc_info['deliver_day_' . $deliver_freq];

    // お届け時間
    $deliver_time = $subsc_info['deliver_time'];

    // 次回支払日
    $next_payment_date = strtotime($subsc_info['next_payment_date']);

    // テーブルの更新
    db_query("UPDATE ?:jp_sonys_subsc_manager SET status = ?s, next_payment_date = ?i, deliver_freq = ?s, deliver_day = ?s, deliver_time = ?s, up_timestamp = ?i WHERE subpay_id = ?i", $status, $next_payment_date, $deliver_freq, $deliver_day, $deliver_time, TIME, $subpay_id);
}




/**
 * 定期購入データの検索
 *
 * @param $params
 * @return array
 */
function fn_get_sonys_subsc_data($params)
{
    // 表示パラメータのデフォルト値
    $default_params = array (
        'page' => 1,
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page')
    );

    // 表示パラメータのデフォルト値と引数にセットされた配列をマージ
    $params = array_merge($default_params, $params);

    ////////////////////////////////////////////////////////////////
    // ソート順 BOF
    ////////////////////////////////////////////////////////////////
    // ソートキー
    $sortings = array (
        'subpay_id' => 'subpay_id',
        'next_payment_date' => 'next_payment_date',
        'deliver_time' => 'deliver_time',
        'user_name' => 'user_name',
        'product' => 'product',
        'status' => 'status'
    );

    // ソート順（昇順/降順）
    $directions = array (
        'asc' => 'asc',
        'desc' => 'desc'
    );

    // デフォルトソートキー（subpay_idの降順）
    if (empty($params['sort_by']) || empty($sortings[$params['sort_by']])) {
        $params['sort_by'] = 'subpay_id';
    }
    if (empty($params['sort_order']) || empty($directions[$params['sort_order']])) {
        $params['sort_order'] = 'desc';
    }
    $sorting = $sortings[$params['sort_by']] . ' ' . $directions[$params['sort_order']];

    if( empty($params['sort_order']) || $params['sort_order'] == 'desc' ){
        $params['sort_order_rev'] = 'asc';
    }
    else{
        $params['sort_order_rev'] = 'desc';
    }
    ////////////////////////////////////////////////////////////////
    // ソート順 EOF
    ////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////
    // 抽出条件 BOF
    ////////////////////////////////////////////////////////////////
    $condition = '';
    // user_id
    if(!empty($params['user_id'])) {
        $condition .= db_quote(" AND user_id = ?i", $params['user_id']);
    }
    // お客様名
    if(!empty($params['user_name'])) {
        $condition .= db_quote(" AND user_name LIKE ?l", "%" . $params['user_name'] . "%");
    }
    // 商品名
    if(!empty($params['product'])) {
        $condition .= db_quote(" AND product LIKE ?l", "%" . $params['product'] . "%");
    }
    // ステータス
    if(!empty($params['status'])) {
        $condition .= db_quote(" AND status = ?s", $params['status']);
    }
    // 次回発送予定日
    if(!empty($params['time_from'])) {
        $time_from = strtotime($params['time_from']);
        $condition .= db_quote(" AND next_payment_date >= ?i", $time_from);
    }
    if(!empty($params['time_to'])) {
        $time_to = strtotime($params['time_to']);
        $condition .= db_quote(" AND next_payment_date <= ?i", $time_to);
    }
    if(!empty($params['subpay_id'])) {
        $condition .= db_quote(" AND subpay_id = ?i", $params['subpay_id']);
    }
    ////////////////////////////////////////////////////////////////
    // 抽出条件 EOF
    ////////////////////////////////////////////////////////////////

    // 抽出するライセンス数
    $limit = '';
    if (!empty($params['items_per_page'])) {
        // 抽出結果件数
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:jp_sonys_subsc_manager WHERE 1" . $condition);
        // ページネーション
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    // 指定された条件でライセンスを抽出
    $subscriptions = db_get_array("SELECT * FROM ?:jp_sonys_subsc_manager WHERE 1" . $condition . " ORDER BY " . $sorting . " " . $limit);

    return array($subscriptions, $params);
}




/**
 * 定期購入データのステータス変更
 *
 * @param $subpay_id
 * @param $status_to
 */
function fn_sonys_subsc_status_update($subpay_id, $status_to){
    // ステータスの更新
    db_query("UPDATE ?:jp_sonys_subsc_manager SET status = ?s, up_timestamp = ?i WHERE subpay_id =?i", $status_to, TIME, $subpay_id);
}




/**
 * 受注処理
 *
 * @param $subpay_id
 * @return bool
 */
function fn_sonys_process($subpay_id){

    // 定期購入IDが指定されていない場合はfalseを返す
    if( empty($subpay_id) ) return false;

    // 指定した処理を行うのに適した定期購入であるかを判定
    $is_valid_subsc = fn_sonys_subsc_check_process_validity($subpay_id);

    if(!$is_valid_subsc) {
        fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_subsc_status_error'));
        return false;
    }

    $subsc_data = db_get_array("SELECT next_payment_date, product_id, user_id, deliver_freq, deliver_day FROM ?:jp_sonys_subsc_manager WHERE subpay_id = ?i", $subpay_id);

    foreach($subsc_data as $data){
        $next_payment_date = $data['next_payment_date'];
        $product_id = $data['product_id'];
        $user_id = $data['user_id'];
        $deliver_freq = $data['deliver_freq'];
        $deliver_day = $data['deliver_day'];
    }

    $quickpay_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay wHere user_Id = ?i", $user_id);

    // カードが登録されていない場合はfalseを返す
    if( empty($quickpay_id) ) {
        fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_no_registered_card_error'));
        return false;
    }

    $within_product_period = fn_sonys_within_product_period($product_id, $next_payment_date);
    if( !$within_product_period ){
        fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_subsc_without_product_period_desc', array("[subpay_id]" => $subpay_id)));
        return false;
    }

    $time = TIME;
    $year_today = date('Y', $time);
    $month_today = date( 'n', $time);

    if( $month_today == 11){
        $year_next = $year_today + 1;
        $month_next = 1;
    }
    elseif($month_today == 12) {
        $year_next = $year_today + 1;
        $month_next = 2;
    }
    else {
        $year_next = $year_today;
        $month_next = $month_today + 2;
    }

    // 今月の１日
    $date_from = strtotime($year_today . '/' . $month_today . '/1');
    // ２ヶ月後の１日
    $date_to = strtotime($year_next . '/' . $month_next . '/1');

    // 次回発送予定日が今月と来月以外の月の場合処理を中止
    if( $next_payment_date < $date_from || $next_payment_date >= $date_to){
        fn_set_notification('E', __('error'), __('jp_sonys_next_send_date_invalid', array("[subpay_id]" => $subpay_id)));
        return false;
    }

    // 初回の注文情報を取得
    $init_order_id = db_get_field("SELECT MIN(order_id) FROM ?:jp_sonys_subsc_history WHERE subpay_id = ?i", $subpay_id);

    // 初回の注文から注文を作成
    $order_id = fn_sonys_create_order($init_order_id);

    $order_info = fn_get_order_info($order_id);

    // 決済処理
    $type = 'ccreg_payment';
    $processor_data = fn_sonys_get_processor_data();
    $action = 'checkout';

    // 処理方法を設定
    $processor_data['processor_params']['process_type'] = Registry::get('addons.sonypayment_subpay.second_process_type');

    // ソニーペイメントに送信するパラメータをセット
    $params = fn_sonys_get_params($type, $order_info, $processor_data);

    // オーソリ依頼
    $result_params = fn_sonys_send_request($params, $processor_data, $action);

    // ソニーペイメントより処理結果が返された場合
    if (!empty($result_params['TransactionId'])) {

        // 処理でエラーが発生している場合
        if ($result_params['ResponseCd'] != 'OK') {

            fn_sonys_subsc_status_update($subpay_id, 'E');

            fn_set_notification('E', __('jp_sonys_error'), __('jp_sonys_subsc_fail', array("[subpay_id]" => $subpay_id, "[err_cd]" => $result_params['ResponseCd'])));

        // オーソリが正常に完了した場合
        } else {

            // 正常注文として登録
            $_data = array(
                'order_id' => $order_id,
                'type' => 'S',
                'data' => TIME
            );
            db_query("INSERT INTO ?:order_data ?e", $_data);


            // 決済が正常終了した情報を保持
            $success_payment_data[] = [
                'order_id' => $order_id,
                'result_params' => $result_params
            ];

            // 後続処理のための ProcessId と ProcessPass をDBに保存
            fn_sonys_update_set_process_info($order_id, $result_params);

            // DBに保管する支払い情報を生成
            fn_sonys_format_payment_info('cc', $order_id, null, $result_params);

            fn_sonys_update_cc_status_code($order_id, $result_params['OperateId']);

            // 注文ステータスを更新
            $pp_response = array();
            $pp_response['order_status'] = 'P';
            fn_finish_payment($order_id, $pp_response);

            // 次回送信日付を更新
            $new_payment_date = fn_sonys_get_next_payment_date($next_payment_date, $deliver_freq, $deliver_day);
            db_query("UPDATE ?:jp_sonys_subsc_manager SET next_payment_date = ?i, up_timestamp =?i WHERE subpay_id = ?i", $new_payment_date, TIME, $subpay_id);

            // 注文履歴を更新
            db_query("INSERT INTO ?:jp_sonys_subsc_history(subpay_id, order_id) VALUES(?i, ?i)", $subpay_id, $order_id);

            fn_set_notification('N', __('information'), __('jp_sonys_subsc_success', array("[subpay_id]" => $subpay_id, "[oid]" => $order_id)));
        }
    }

    return $is_valid_subsc;
}




/**
 * 翌月の日付を取得
 *
 * @param $date
 * @param $delivery_freq
 * @param $delivery_day
 * @param $is_offset
 * @return int
 */
function fn_sonys_get_next_payment_date($date, $delivery_freq, $delivery_day, $is_offset = false){

    // オフセット日数
    $offset = 0;
    if($is_offset) {
        $offset = empty(Registry::get('addons.sonypayment_subpay.offset')) ? 0 : Registry::get('addons.sonypayment_subpay.offset');
    }

    // オフセット日数を追加
    $offset_date = strtotime('+' . $offset . ' day', $date);

    switch($delivery_freq){
        case 'w':
        case 'bw':
            // オフセットを追加した日付の曜日を取得（日曜の場合は 7 に変更）
            $offset_date_w = date('w', $offset_date) == 0 ? 7 : date('w', $offset_date);

            // 送信日の曜日がオフセット日付より後の曜日の場合
            if( $delivery_day >= $offset_date_w && $offset > 0 ){
                $next_payment_date = strtotime('+' . $delivery_day - $offset_date_w . ' day', $offset_date);
            }
            // 送信日の曜日がオフセット日付より前の曜日の場合
            else{
                $next_payment_date = strtotime('+' . 7 + $delivery_day - $offset_date_w . ' day', $offset_date);
            }

            // 隔週の場合
            if( $delivery_freq == 'bw' ){
                // 当日と次回送信日の間に1週間空きが無い場合
                if( date('W', $next_payment_date) - date('W', $date) < 2 ){
                    $next_payment_date = strtotime('+7 day', $next_payment_date);
                }
            }
            break;

        case 'm':
        case 'bm':
            $offset_year = date('Y', $offset_date);
            $offset_month = date('n', $offset_date);
            $offset_day = date('j', $offset_date);

            // 月末で無い場合
            if( $delivery_day != 'end' ){
                // オフセット日が送信日以下の場合
                if( $offset_day <= $delivery_day ){
                    $month_plus = $offset == 0 ? 1 : 0;
                    // オフセット当月の送信日
                    $next_payment_date = strtotime(strval(fn_sonys_get_next_year_month('Y', ($offset_month + $month_plus), $offset_year)) . '/' . strval(fn_sonys_get_next_year_month('M', ($offset_month + $month_plus))) . '/' . strval($delivery_day));
                }
                // オフセット日が送信日より大きい場合
                else{
                    // オフセット翌月の送信日
                    $next_payment_date = strtotime(strval(fn_sonys_get_next_year_month('Y', ($offset_month + 1), $offset_year)) . '/' . strval(fn_sonys_get_next_year_month('M', ($offset_month + 1))) . '/' . strval($delivery_day));
                }
            }
            // 月末の場合
            else{
                // オフセット翌月の1日
                $next_offset_month_1day = strtotime(strval(fn_sonys_get_next_year_month('Y', ($offset_month + 1), $offset_year)) . '/' . strval(fn_sonys_get_next_year_month('M', ($offset_month + 1))) . '/1');

                $next_payment_date = strtotime('-1 day', $next_offset_month_1day);

                if( $next_payment_date == $date ){
                    $next_offset_month_1day = strtotime(strval(fn_sonys_get_next_year_month('Y', ($offset_month + 2), $offset_year)) . '/' . strval(fn_sonys_get_next_year_month('M', ($offset_month + 2))) . '/1');

                    $next_payment_date = strtotime('-1 day', $next_offset_month_1day);
                }

            }

            // 隔月の場合
            if( $delivery_freq == 'bm' ) {
                $year = date('Y', $date);
                $month = date('n', $date);

                $next_payment_year = date('Y', $next_payment_date);
                $next_payment_month = date('n', $next_payment_date);

                // 月末で無い場合
                if( $delivery_day != 'end' ) {
                    // 当日と次回送信日の間に1ヶ月空きが無い場合
                    $diff = (($next_payment_year - $year) * 12 + $next_payment_month) - $month;
                    if ($diff < 2) {
                        $month_plus = 2 - $diff;
                        $next_payment_date = strtotime(strval(fn_sonys_get_next_year_month('Y', ($next_payment_month + $month_plus), $next_payment_year)) . '/' . strval(fn_sonys_get_next_year_month('M', ($next_payment_month + $month_plus))) . '/' . strval($delivery_day));
                    }
                }
                else{
                    $orig_next_payment_date = $next_payment_date;

                    // 当日と次回送信日の間に1ヶ月空きが無い場合
                    $diff = (($next_payment_year - $year) * 12 + $next_payment_month) - $month;
                    if ($diff < 2) {
                        $month_plus = 2 - $diff;
                        // 翌月の1日
                        $next_payment_month_1day = strtotime(strval(fn_sonys_get_next_year_month('Y', ($next_payment_month + $month_plus), $next_payment_year)) . '/' . strval(fn_sonys_get_next_year_month('M', ($next_payment_month + $month_plus))) . '/1');

                        $next_payment_date = strtotime('-1 day', $next_payment_month_1day);
                    }

                    if( $next_payment_date == $orig_next_payment_date ){
                        $next_payment_month_1day = strtotime(strval(fn_sonys_get_next_year_month('Y', ($next_payment_month + $month_plus +1), $next_payment_year)) . '/' . strval(fn_sonys_get_next_year_month('M', ($next_payment_month + $month_plus +1))) . '/1');

                        $next_payment_date = strtotime('-1 day', $next_payment_month_1day);
                    }
                }
            }

            break;
    }

    return $next_payment_date;
}




/**
 * 既存の注文から注文データを作成する
 *
 * @param $original_order_id
 * @return int
 */
function fn_sonys_create_order($original_order_id){
    // 初回の注文から注文を作成
    fn_clear_cart($cart, true);
    $customer_auth = fn_fill_auth(array(), array(), false, 'C');

    $cart_status = md5(serialize($cart));
    fn_form_cart($original_order_id, $cart, $customer_auth, true);

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // 配送先住所更新対応
    ///////////////////////////////////////////////
    // 配送先住所を最新の住所に変更
    $new_ship_addr = db_get_array("SELECT * from ?:jp_sonys_subsc_ship_address where subpay_id = (SELECT subpay_id FROM ?:jp_sonys_subsc_history WHERE order_id = ?i)", $original_order_id);
    if(!empty($new_ship_addr[0]['s_zipcode'])) {
        $cart['user_data']['s_zipcode'] = $new_ship_addr[0]['s_zipcode'];
        $cart['user_data']['s_state'] = $new_ship_addr[0]['s_state'];
        $cart['user_data']['s_city'] = $new_ship_addr[0]['s_city'];
        $cart['user_data']['s_address'] = $new_ship_addr[0]['s_address'];
        $cart['user_data']['s_address_2'] = $new_ship_addr[0]['s_address_2'];
        $cart['user_data']['s_phone'] = $new_ship_addr[0]['s_phone'];
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // 配送先住所更新対応
    ///////////////////////////////////////////////

    fn_store_shipping_rates($original_order_id, $cart, $customer_auth);

    // 2回目以降の金額が違う場合
    $params = array();
    foreach($cart['products'] as $key => $val){
        $second_price = db_get_field("SELECT second_price FROM ?:jp_sonys_products WHERE product_id = ?i", $cart['products'][$key]['product_id']);
        if( $second_price != $cart['products'][$key]['price'] && $second_price > 0 ){
            // 金額更新用のデータを作成
            $cart_products = array(
                $key => array(
                    'stored_price' => 'Y',
                    'price' => $second_price,
                    'product_id' => $cart['products'][$key]['product_id'],
                    'amount' => $cart['products'][$key]['amount']
                )
            );
            $params['cart_products'] = $cart_products;
        }
    }

    // Clean up saved shipping rates
    unset(Tygh::$app['session']['shipping_rates']);

    // update totals and etc.
    fn_update_cart_by_data($cart, $params, $customer_auth);

    if (!empty($params['shipping_ids'])) {
        fn_checkout_update_shipping($cart, $params['shipping_ids']);
    }

    if (empty($cart['stored_shipping'])) {
        $cart['calculate_shipping'] = true;
    }

    // recalculate cart content after update
    list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $customer_auth);
    fn_update_payment_surcharge($cart, $customer_auth);

    $cart['notes'] = !empty($_REQUEST['customer_notes']) ? $_REQUEST['customer_notes'] : '';
    $cart['payment_info'] = !empty($_REQUEST['payment_info']) ? $_REQUEST['payment_info'] : array();

    $action = 'save';

    $auth = & Tygh::$app['session']['auth'];

    Registry::set('runtime.company_id', $cart['order_company_id']);
    list($order_id, $process_payment) = fn_place_order($cart, $customer_auth, $action, $auth['user_id']);
    Registry::set('runtime.company_id', 0);

    if (!empty($order_id)) {
        if ($action != 'save') {
            $action = 'route';
        }

        if ($process_payment == true) {
            $payment_info = !empty($cart['payment_info']) ? $cart['payment_info'] : array();
            fn_start_payment($order_id, fn_get_notification_rules($_REQUEST), $payment_info);
        }

        if (!empty($_REQUEST['update_order']['details'])) {
            db_query('UPDATE ?:orders SET details = ?s WHERE order_id = ?i', $_REQUEST['update_order']['details'], $order_id);
        }

        $notification_rules = fn_get_notification_rules($_REQUEST);
        // change status if it posted
        if (!empty($_REQUEST['order_status']) && fn_check_permissions('orders', 'update_status', 'admin')) {
            $order_info = fn_get_order_short_info($order_id);

            if ($order_info['status'] != $_REQUEST['order_status']) {
                if ($process_payment == true) {
                    fn_set_notification('W', __('warning'), __('status_changed_after_process_payment'));
                } elseif (fn_change_order_status($order_id, $_REQUEST['order_status'], '', $notification_rules)) {
                    $order_info = fn_get_order_short_info($order_id);
                    $new_status = $order_info['status'];
                    if ($_REQUEST['order_status'] != $new_status) {
                        fn_set_notification('W', __('warning'), __('status_changed'));
                    }
                } else {
                    $error = false;

                    if ($order_info['is_parent_order'] == 'Y') {
                        $suborders = fn_get_suborders_info($order_id);

                        if ($suborders) {
                            foreach ($suborders as $suborder) {
                                if ($suborder['status'] != $_REQUEST['order_status']) {
                                    $error = true;
                                    break;
                                }
                            }
                        } else {
                            $error = true;
                        }
                    } else {
                        $error = true;
                    }

                    if ($error) {
                        fn_set_notification('E', __('error'), __('error_status_not_changed'));
                    }
                }
            }
        }
    }

    return $order_id;
}




/** 指定した処理を行うのに適した定期購入であるかを判定（複数定期購入での一括処理時に使用）
 * @param $subpay_id
 * @return bool
 */
function fn_sonys_subsc_check_process_validity( $subpay_id )
{
    // 定期購入データからステータスを取得
    $status = db_get_field("SELECT status FROM ?:jp_sonys_subsc_manager WHERE subpay_id = ?i", $subpay_id);

    switch($status){
        case 'A': // 継続
            return true;
            break;
        case 'P': // 休止
        case 'D': // 解約
        case 'E': // 決済失敗
            return false;
            break;
        // その他
        default:
            // do nothing
    }

    return false;
}




/** 定期購入の注文番号を取得
 * @param $subpay_id
 * @return array
 */
function fn_sonys_get_subsc_orders( $subpay_id )
{
    $subsc_orders = db_get_fields("SELECT order_id FROM ?:jp_sonys_subsc_history WHERE subpay_id = ?i", $subpay_id);

    return $subsc_orders;
}




/** 翌年の年月を取得
 * @param $month
 * @param $year
 * @return int
 */
function fn_sonys_get_next_year_month( $type, $month, $year = 0 )
{
    switch($type)
    {
        case 'Y':
            if( $month > 12 ){
                $result = $year + 1;
            }
            else{
                $result = $year;
            }
            break;

        case 'M':
            if( $month > 12 ){
                $result = $month - 12;;
            }
            else{
                $result = $month;
            }
            break;
    }

    return $result;
}
##########################################################################################
// END 定期購入管理の関数
##########################################################################################

##########################################################################################
// START 3D認証
##########################################################################################
/**
 * パラメータを暗号化する
 *
 * @param $params
 * @return array
 */
function fn_sonys_encrypt_params($params, $processor_data)
{
    $result_params = array();

    $encrypt_params = array();
    foreach( $params as $key => $value ) {
        if( $key != "MerchantId" ) {
            $encrypt_params[$key] = $value;
        }
        else if( $key == "MerchantId" ) {
            $result_params[$key] = $value;
        }
    }

    // URLエンコードされたクエリ文字列
    $encrypt_params_str = http_build_query($encrypt_params);

    // 暗号化処理
    $iv = $processor_data['processor_params']['3dsecure_aes_iv'];
    $key = $processor_data['processor_params']['3dsecure_aes_key'];
    $method = SONYS_AES_METHOD;

    // AES 暗号化
    $encrypt_params_str = openssl_encrypt ($encrypt_params_str, $method, $key, true, $iv);

    // Base64 エンコード
    $encrypt_params_str = base64_encode($encrypt_params_str);

    $result_params["EncryptValue"] = $encrypt_params_str;

    return $result_params;
}
##########################################################################################
// END 3D認証
##########################################################################################