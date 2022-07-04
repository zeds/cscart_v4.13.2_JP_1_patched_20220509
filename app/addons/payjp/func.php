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

// $Id: func.php by takahashi from cs-cart.jp 2020

use Tygh\Http;
use Tygh\Registry;

require_once dirname(__FILE__) . '/lib/init.php';

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * クレジットカード情報を登録済みの会員に対してのみ表示
 *
 * @param $params
 * @param $payments
 */
function fn_payjp_get_payments_post(&$params, &$payments)
{
    $tmp_name = 'payjp_cc.tpl';

    // 選択可能な支払方法が存在する場合
    if( !empty($payments) ){
        foreach($payments as $key => $val){
            if(!empty($val['payment_id'])) {
                // 決済代行サービスIDを取得
                $processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $val['payment_id']);

                // 各決済代行サービスにひもづけられた設定用テンプレート名を取得
                $template = db_get_field("SELECT admin_template FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

                // テンプレート名が登録済カードによる支払に関するものである場合
                if (!empty($template) && $template == $tmp_name) {

                    // ユーザー登録されていない場合、選択可能な支払方法から除外
                    if (empty(Tygh::$app['session']['auth']['user_id'])) {
                        unset($payments[$key]);
                    }
                }
            }
        }
    }
}




/**
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_payjp_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
			case 'payjp_cc.php':
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
function fn_payjp_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    // クレジット請求管理ページの場合
    if( Registry::get('runtime.controller') == 'payjp_cc_manager' && Registry::get('runtime.mode') == 'manage'){
        // カード決済および登録済カードにより支払われた注文のみ抽出
        $pr_scripts = array("payjp_cc.php");
        $payjp_cc_payments = db_get_fields("SELECT payment_id FROM ?:payments JOIN ?:payment_processors ON ?:payments.processor_id = ?:payment_processors.processor_id WHERE processor_script IN (?a)", $pr_scripts);
        $payjp_cc_payments = implode(',', $payjp_cc_payments);
        $condition .= " AND ?:orders.payment_id IN ($payjp_cc_payments)";

        // 各注文にひもづけられたクレジット請求ステータスコードを抽出
        $fields[] = "?:jp_payjp_cc_status.status_code as cc_status_code";
        $join .= " LEFT JOIN ?:jp_payjp_cc_status ON ?:jp_payjp_cc_status.order_id = ?:orders.order_id";
    }
}




/**
 * ユーザー削除時にpayjpに登録されている会員情報も削除する
 *
 * @param $user_id
 * @param $user_data
 * @param $result
 * @return bool
 */
function fn_payjp_post_delete_user(&$user_id, &$user_data, &$result)
{
    // CS-Cart側でのユーザー削除が完了した場合
    if($result){

        // payjpに顧客がとうろくされているか確認
        $member_id = fn_payjp_get_customer_id($user_id);

        $ccreg_delete_params['id'] = $member_id;

        // payjpの顧客登録済みユーザーの場合
        if(!empty($ccreg_delete_params['id'])){

            // 顧客情報の削除
            $result_params = fn_payjp_send_request('deletemember', $ccreg_delete_params);

            // payjp側で正常に処理が終了した場合
            if ( !is_array($result_params['error']) ){
                db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'payjp_ccreg');
                fn_set_notification('N', __('information'), __('jp_payjp_member_delete_success'));
            // payjp側でエラーが発生した場合
            }else{
                // エラーメッセージを表示
                fn_payjp_set_err_msg($result_params['error']['code'], $result_params['error']['message']);
            }
        }
    }
}




/**
 * 注文情報削除時にクレジット決済の請求ステータスを削除
 *
 * @param $order_id
 */
function fn_payjp_delete_order(&$order_id)
{
    $type = false;

    // 支払IDを取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if( !empty($payment_id) && fn_payjp_is_deletable($payment_id) ){
        $status_code = db_get_field("SELECT status_code FROM ?:jp_payjp_cc_status WHERE order_id = ?i", $order_id);

        switch($status_code){
            // 認証状態の注文については与信を取消
            case 'AUTH_OK':
                $type = 'cc_auth_cancel';
                break;
            // 支払が完了している注文については売上を取消
            case 'CAPTURE_OK':
            case 'SALES_CONFIRMED':
                $type = 'cc_sales_cancel';
                break;
            default:
                // do nothing
        }

        // 取消・削除処理を実行
        if(!empty($type)) fn_payjp_send_cc_request($order_id, $type);
    }

    db_query("DELETE FROM ?:jp_payjp_cc_status WHERE order_id = ?i", $order_id);
}




/**
 * 注文データの削除時に売上取消（カード）を実施
 *
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 * @return bool
 */
function fn_payjp_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses, &$place_order)
{
    if( empty($order_info['payment_id']) ) return false;

    if($status_to == 'I' && fn_payjp_is_deletable($order_info['payment_id']) ){

        $status_code = db_get_field("SELECT status_code FROM ?:jp_payjp_cc_status WHERE order_id = ?i", $order_info['order_id']);

        switch($status_code){
            // 認証状態の注文については与信を取消
            case 'AUTH_OK':
                $type = 'cc_auth_cancel';
                break;
            // 支払が完了している注文については売上を取消
            case 'CAPTURE_OK':
            case 'SALES_CONFIRMED':
                $type = 'cc_sales_cancel';
                break;
            default:
                return false;
        }

        // 取消処理を実行
        fn_payjp_send_cc_request($order_info['order_id'], $type);

        // 注文情報を取得
        $tmp_order_info = fn_get_order_info($order_info['order_id']);

        // 処理通番を更新
        if( !empty($tmp_order_info['payment_info']['jp_payjp_tran_id']) ){
            $order_info['payment_info']['jp_payjp_tran_id'] = $tmp_order_info['payment_info']['jp_payjp_tran_id'];
        }

        // 請求ステータスを更新
        if( !empty($tmp_order_info['payment_info']['jp_payjp_cc_status']) ){
            $order_info['payment_info']['jp_payjp_cc_status'] = $tmp_order_info['payment_info']['jp_payjp_cc_status'];
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
function fn_payjp_install()
{
    fn_lcjp_install('payjp');
}




/**
 * アドオンのアンインストール時に支払関連のレコードを削除
 */
function fn_payjp_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('payjp_cc.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('payjp_cc.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('payjp_cc.php')");

}

##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################




##########################################################################################
// START その他の関数
##########################################################################################

/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * payjpゲートウェイに送信するパラメータをセット 
 *
 * @param $type
 * @param $order_info
 * @param $processor_data
 */
function fn_payjp_get_params($type, $order_info, $processor_data)
{
    // 送信パラメータを初期化
    $params = array();

    // 処理別に異なるパラメータをセット
    switch($type){

        // クレジットカード決済（決済実行）
        case 'exectran':

            // 処理区分
            $jobcd = $processor_data['processor_params']['jobcd'];

            if($jobcd == 'AUTH') {
                $params['capture'] = 0;
            } else {
                $params['capture'] = 1;
            }

            // 利用金額
            $params['amount'] = round($order_info['total']);

            // 通貨コード
            $params['currency'] = strtolower(db_get_field('SELECT currency_code FROM ?:currencies WHERE is_primary = ?s', 'Y'));

            // エラーコード、エラーメッセージ
            $params['ErrorMsg'] = empty($order_info['payment_info']['errorMsg']) ? '' : $order_info['payment_info']['errorMsg'];

            break;

        // 顧客登録・カード更新
        case 'savemember':
        case 'updatecard':

            // 顧客のメールアドレス
            $params['email'] = $order_info['email'];

            // 顧客の説明文
            $params['description'] = $order_info['firstname'] . ' ' . $order_info['lastname'];

            // トークン
            $params['card'] = $order_info['payment_info']['token'];

            break;

        default:
            // do nothing
            break;
    }

    return $params;
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $payjp_exec_results
 * @param bool $flg_comments
 * @return bool
 */
function fn_payjp_format_payment_info($type, $order_id, $payment_info, $payjp_exec_results)
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
        $payjp_cc_status = db_get_field("SELECT status_code FROM ?:jp_payjp_cc_status WHERE order_id = ?i", $order_id);

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
                        switch($key){
                            // カード決済に関する情報のみ保持
                            case 'jp_payjp_tran_id':
                            case 'jp_payjp_cc_status':
                            case 'jp_payjp_tran_date':
                            case 'jp_payjp_card_company':
                            case 'jp_payjp_finishdate':
                            case 'jp_payjp_process_date':
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

                // 支払ID
                $info['jp_payjp_tran_id'] = $payjp_exec_results['id'];

                // 請求ステータス
                if(!empty($payjp_cc_status)){
                    $info['jp_payjp_cc_status'] = fn_payjp_get_cc_status_name($payjp_cc_status);
                }

                // 決済日付
                $info['jp_payjp_tran_date'] = fn_payjp_format_date($payjp_exec_results['created']);

                // カード会社
                $info['jp_payjp_card_company'] = $payjp_exec_results['card']['brand'];

                // 支払確定日付
                if(!empty($payjp_exec_results['captured_at'])){
                    $info['jp_payjp_finishdate'] = fn_payjp_format_date($payjp_exec_results['captured_at']);
                }

                break;

            // クレジットカード売上確定/与信取消/売上取消/金額変更
            case 'cc_sales_confirm' :
            case 'cc_auth_cancel' :
            case 'cc_sales_cancel' :

                // 支払ID
                $info['jp_payjp_tran_id'] = $payjp_exec_results['id'];

                // 請求ステータス
                if(!empty($payjp_cc_status)){
                    $info['jp_payjp_cc_status'] = fn_payjp_get_cc_status_name($payjp_cc_status);
                }

                // 決済日付
                $info['jp_payjp_tran_date'] = fn_payjp_format_date($payjp_exec_results['created']);

                // カード会社
                $info['jp_payjp_card_company'] = $payjp_exec_results['card']['brand'];

                // 支払確定日付
                if(!empty($payjp_exec_results['captured_at'])){
                    $info['jp_payjp_finishdate'] = fn_payjp_format_date($payjp_exec_results['captured_at']);
                }

                //処理日時
                if($type == 'cc_auth_cancel' || $type == 'cc_sales_cancel'){
                    $info['jp_payjp_process_date'] = fn_payjp_format_date(time());
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

        // 注文データ内の支払関連情報を追加
        $insert_data = array (
            'order_id' => $order_id,
            'type' => 'P',
            'data' => $_data,
        );
        db_query("REPLACE INTO ?:order_data ?e", $insert_data);
    }
}




/**
 * payjpゲートウェイに各種データを送信
 *
 * @param $type
 * @param $params
 * @return mixed|string
 */
function fn_payjp_send_request($type, $params)
{
    // シークレットキー
    define('PAYJP_SECRET_KEY', Registry::get('addons.payjp.secret_key'));

    // 結果パラメータを初期化
    $result = '';

    switch($type) {
        // カード決済（登録済みカード決済実行）
        case 'exectranwc':

            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $result = \Payjp\Charge::create(array(
                    "customer" => $params['customer'],
                    "amount" => $params['amount'],
                    "currency" => $params['currency'],
                    "capture" => $params['capture'] == 1 ? true : false,
                    "description" => __("order_id") . ": " . $params['order_id']
                ));

            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;

        case 'searchcard':
            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $result = \Payjp\Customer::retrieve($params['id'])->cards->all(array("limit"=>1));
            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;

        // トークンで顧客作成
        case 'savemember':

            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $result = \Payjp\Customer::create(array(
                    'email' => $params['email'],
                    'description' => $params['description'],
                    'card' => $params['card']
                ));
            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;

        // 顧客削除
        case 'deletemember':

            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $cu = \Payjp\Customer::retrieve($params['id']);
                $result = $cu->delete();
            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;

        // トークンでカード更新
        case 'updatecard':

            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $cu = \Payjp\Customer::retrieve($params['id']);
                $cu->card = $params['card'];
                $result = $cu->save();

            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;


        // カード削除
        case 'deletecard':
            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $car = \Payjp\Customer::retrieve($params['id'])->cards->all(array("limit"=>1));

                if($car['count'] >= 1) {
                    \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                    $cu = \Payjp\Customer::retrieve($params['id']);
                    $card = $cu->cards->retrieve($car['data'][0]['id']);
                    $card_result = $card->delete();
                    $result = $card_result;
                }
                else{
                    $result = array('deleted' => true);
                }

            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;

        // 支払確定
        case 'sales':
            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $ch = \Payjp\Charge::retrieve($params['charge_id']);
                $result = $ch->capture();
            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;

        // 認証取消、支払取消
        case 'authcancel':
        case 'salescancel':
            try {
                \Payjp\Payjp::setApiKey(PAYJP_SECRET_KEY);
                $ch = \Payjp\Charge::retrieve($params['charge_id']);
                $result = $ch->refund();
            } catch (\Payjp\Error\Card $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\InvalidRequest $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Authentication $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\ApiConnection $e) {
                $result = $e->getJsonBody();
            } catch (\Payjp\Error\Base $e) {
                $result = $e->getJsonBody();
            } catch (Exception $e) {
                $result = $e->getJsonBody();
            }

            break;

        default:
            // do nothing
    }

    return $result;
}




/**
 * 日時データを読みやすくフォーマット
 *
 * @param $date
 * @return string
 */
function fn_payjp_format_date($date)
{
    return date('Y/m/d H:i:s', (int)$date);
}





/**
 * エラーメッセージを表示
 *
 * @param $errcode
 * @param $errinfo
 * @param $order_id
 */
function fn_payjp_set_err_msg($errcode, $errinfo, $type = '', $order_id = null)
{
    // エラーコードおよびエラー詳細コード
    if(empty($errcode)) {
        $errcode = str_replace(' ', '_', $errinfo);
        $errcode = strtolower($errcode);
    }

    // エラーメッセージを表示
    if(!empty($errcode)){
        if($order_id){
            fn_set_notification('E', __('jp_payjp_error'), fn_payjp_get_err_msg($errcode, $errinfo, $type) . __("order_Id") . ": " . $order_id);
        }
        else{
            fn_set_notification('E', __('jp_payjp_error'), fn_payjp_get_err_msg($errcode, $errinfo, $type));
        }
    }
}




/**
 * エラーメッセージを取得
 *
 * @param $err_detail_code
 * @return string
 */
function fn_payjp_get_err_msg($err_detail_code, $err_message, $type = '')
{
    $err_msg = __('jp_payjp_errmsg_'.$err_detail_code);

    // エラーコードに対応する言語変数が存在しない場合、汎用メッセージをセット
    if( strpos($err_msg, 'jp_payjp_errmsg_') === 0 || strpos($err_msg, 'jp_payjp_errmsg_') > 0) {
        $err_msg = __('jp_payjp_' . $type . '_failed') . "<br/>" . $err_message;
    }

    // エラーメッセージを返す
    return $err_msg;
}




/**
 * payjpの取消およびデータ削除処理の対象となる注文であるかを判定
 *
 * @param $payment_id
 * @return bool
 */
function fn_payjp_is_deletable($payment_id)
{
    // 注文で使用されている決済代行業者IDを取得
    $payment_method_data = fn_get_payment_method_data($payment_id);
    if( empty($payment_method_data) ) return false;
    $processor_id = $payment_method_data['processor_id'];
    if( empty($processor_id) ) return false;
    $pr_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

    // 決済代行業者がpayjpの場合はtrueを返す
    switch($pr_script){
        case 'payjp_cc.php':    // payjp（カード決済）
            return true;
            break;
        default:
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
 * ステータスコードをセット
 *
 * @param $order_id
 * @param $job_code
 * @param string $charge_id
 */
function fn_payjp_update_cc_status_code($order_id, $job_code)
{
    $_data = array (
        'order_id' => $order_id,
        'status_code' => fn_payjp_get_status_code($job_code)
    );

    // レコードを追加更新
    db_query("REPLACE INTO ?:jp_payjp_cc_status ?e", $_data);
}




/**
 * 処理区分に応じてステータスコードを取得
 *
 * @param $job_code
 * @return string
 */
function fn_payjp_get_status_code($job_code)
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
 * 注文データ内に格納されたクレジット請求ステータスを更新
 *
 * @param $order_id
 * @param string $type
 */
function fn_payjp_update_cc_status($order_id, $type = 'cc_sales_confirm')
{
    // クレジット請求ステータスを初期化
    $status_code = '';

    // 処理内容に応じてセットする値を変更
    switch($type){
        // 実売上
        case 'cc_sales_confirm':
            $status_code = 'SALES_CONFIRMED';
            $msg = __('jp_payjp_cc_sales_completed');
            break;
        // 与信取消
        case 'cc_auth_cancel':
            $status_code = 'AUTH_CANCELLED';
            $msg = __('jp_payjp_cc_auth_cancelled');
            break;
        // 売上取消
        case 'cc_sales_cancel':
            $status_code = 'SALES_CANCELLED';
            $msg = __('jp_payjp_cc_sales_cancelled');
            break;

        // その他
        default:
            // do nothing
    }

    // クレジット請求ステータスが設定されている場合
    if( !empty($status_code) ){
        // クレジット請求ステータスを更新
        fn_payjp_update_cc_status_code($order_id, $status_code);
        // 処理完了メッセージを表示
        fn_set_notification('N', __('information'), $msg, 'K');
    }
}

/////////////////////////////////////////////////////////////////////////////////////
// カード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// 登録済みカード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * payjpゲートウェイに登録済みのカード情報を削除
 *
 * @param $user_id
 */
function fn_payjp_delete_card_info($user_id)
{
    // payjpゲートウェイに会員登録済みかチェック
    $member_id = fn_payjp_get_customer_id($user_id);

    // payjpゲートウェイに会員登録済みの場合
    if(!empty($member_id)){

        // payjpゲートウェイに登録された顧客カード情報を削除
        $deletecard_params['id'] = $member_id;
        $success_delete = fn_payjp_send_request('deletecard', $deletecard_params);

        // payjpゲートウェイに登録された顧客情報の削除に成功した場合
        if( $success_delete['deleted'] ){

            // 削除成功メッセージを表示
            fn_set_notification('N', __('notice'), __('jp_payjp_ccreg_delete_success'));

        // payjpゲートウェイに登録された顧客情報の削除に失敗した場合
        }else{
            // エラーメッセージを表示
            fn_payjp_set_err_msg($success_delete['error']['code'], $success_delete['error']['message']);
        }
    }
}




/**
 * 注文に使用したカード番号をpayjpゲートウェイに登録
 *
 * @param $order_info
 * @param $processor_data
 * @return bool
 */
function fn_payjp_register_cc_info($order_info, $processor_data)
{
    $member_id = fn_payjp_get_customer_id($order_info['user_id']);

    // payjpゲートウェイに顧客登録されていない場合
    if( empty($member_id) ) {

        // 顧客登録に必要なパラメータを取得して、顧客登録を実行
        $savemember_params = fn_payjp_get_params('savemember', $order_info, $processor_data);
        $savemember_result = fn_payjp_send_request('savemember', $savemember_params);

        // 顧客登録に関するリクエスト送信が正常終了した場合
        if (!is_array($savemember_result['error'])) {

            $member_id = $savemember_result['id'];

            $_data = array('user_id' => $order_info['user_id'],
                'payment_method' => 'payjp_ccreg',
                'quickpay_id' => $member_id,
            );
            db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);

            return true;

        // 顧客登録に関するリクエスト送信に失敗した場合
        }
        else{
            // エラーメッセージを表示
            fn_payjp_set_err_msg($savemember_result['error']['code'], $savemember_result['error']['message']);
            return false;
        }
    }
    // 顧客登録済みの場合
    else{
        // 顧客に登録済みのカードを削除
        $deletecard_params['id'] = $member_id;
        $deletecard_result = fn_payjp_send_request('deletecard', $deletecard_params);

        if($deletecard_result['deleted']) {
            // カード更新に必要なパラメータを取得して、顧客へのカード更新を実行
            $updatecard_params = fn_payjp_get_params('updatecard', $order_info, $processor_data);
            $updatecard_params['id'] = $member_id;
            $savemember_result = fn_payjp_send_request('updatecard', $updatecard_params);

            // カード更新に関するリクエスト送信が正常終了した場合
            if (!is_array($savemember_result['error'])) {
                return true;
            }
            else{
                // エラーメッセージを表示
                fn_payjp_set_err_msg($savemember_result['error']['code'], $savemember_result['error']['message']);
                return false;
            }
        }
    }

    return false;
}




/**
 * payjpゲートウェイに登録されたカード情報を取得
 *
 * @param $user_id
 * @return array|bool
 */
function fn_payjp_get_registered_card_info($user_id)
{
    // ユーザーIDが指定されていない場合は処理を終了
    if( empty($user_id) ) return false;

    // 登録済みカード情報を格納する変数を初期化
    $registered_card = false;

    // 会員IDを取得
    $member_id = fn_payjp_get_customer_id($user_id);

    // 会員IDが存在する場合
    if(!empty($member_id)){

        $searchcard_params = array();

        // 顧客ID
        $searchcard_params['id'] = $member_id;

        // 登録済みカード照会
        $searchcard_result = fn_payjp_send_request('searchcard', $searchcard_params);

        // 登録済みカード照会に関するリクエスト送信が正常終了した場合
        if (!is_array($searchcard_result['error'])) {

            // 登録済みカード照会が正常に終了している場合
            if ( !is_null($searchcard_result['data'][0]['id'])) {

                $registered_card = array(
                    'brand' => $searchcard_result['data'][0]['brand'],
                    'card_number' => '**** '.$searchcard_result['data'][0]['last4'],
                    'card_valid_term' => $searchcard_result['data'][0]['exp_year']. '/' . substr('0' . $searchcard_result['data'][0]['exp_month'], -2)
                );

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




/**
 * 登録された顧客IDを取得
 *
 * @param $user_id
 * @return array|bool
 */
function fn_payjp_get_customer_id($user_id){
    $member_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'payjp_ccreg');

    return $member_id;
}

/////////////////////////////////////////////////////////////////////////////////////
//  登録済みカード決済 EOF
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
function fn_payjp_get_cc_status_name($cc_status)
{
    // クレジット請求ステータス名を初期化
    $cc_status_name = '';

    // 請求ステータスコードに応じて請求ステータス名を取得
    switch($cc_status){
        // 認証済み
        case 'AUTH_OK':
            $cc_status_name = __('jp_payjp_cc_auth_ok');
            break;
        // 与信NG
        case 'AUTH_NG':
            $cc_status_name = __('jp_payjp_cc_auth_ng');
            break;
        // 認証取消
        case 'AUTH_CANCELLED':
            $cc_status_name = __('jp_payjp_cc_auth_cancel');
            break;
        // 支払済み
        case 'CAPTURE_OK':
            $cc_status_name = __('jp_payjp_cc_captured');
            break;
        // 支払確定
        case 'SALES_CONFIRMED':
            $cc_status_name = __('jp_payjp_cc_sales_confirm');
            break;
        // 支払取消
        case 'SALES_CANCELLED':
            $cc_status_name = __('jp_payjp_cc_sales_cancel');
            break;
    }

    return $cc_status_name;
}




/**
 * 実売上・金額変更・与信取消・売上取消処理を実行
 *
 * @param $order_id
 * @param string $type
 * @return bool
 */
function fn_payjp_send_cc_request( $order_id, $type = 'cc_sales_confirm')
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_payjp_check_process_validity($order_id, $type);

    // 指定した処理を行うのに適した注文でない場合
    if ( !$is_valid_order ){
        return false;
    }

    $params = array();

    // 課金IDの取得
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

    // 支払情報が存在する場合
    if (!empty($payment_info)) {
        // 支払情報が暗号化されている場合は復号化して変数にセット
        if (!is_array($payment_info)) {
            $info = @unserialize(fn_decrypt_text($payment_info));
        } else {
            // 支払情報を変数にセット
            $info = $payment_info;
        }
    }

    $params['charge_id'] = $info['jp_payjp_tran_id'];

    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    switch($type){
        //  実売上
        case 'cc_sales_confirm':
            $transaction_type = 'sales';

            break;

        // 与信取消
        case 'cc_auth_cancel':
            $transaction_type = 'authcancel';

            break;

        // 売上取消
        case 'cc_sales_cancel':
            $transaction_type = 'salescancel';

            break;

        default :
            // do nothing
    }
    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    // 処理実行
    $altertran_result = fn_payjp_send_request($transaction_type, $params);

    // payjpゲートウェイに対するリクエスト送信が正常終了した場合
    if (!is_array($altertran_result['error'])) {

        // 注文データ内に格納されたクレジット請求ステータスを更新
        fn_payjp_update_cc_status($order_id, $type);

        // 注文情報を取得
        $order_info = fn_get_order_info($order_id);

        // DBに保管する支払い情報を生成
        fn_payjp_format_payment_info($type, $order_id, $order_info['payment_info'], $altertran_result);

        return true;

    // リクエスト送信が失敗した場合
    }else{
        // エラーメッセージを表示
        fn_payjp_set_err_msg($altertran_result['error']['code'], $altertran_result['error']['message'], '', $order_id);
    }

    return false;
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_payjp_check_process_validity( $order_id, $type )
{
    // 注文データからクレジット請求ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_payjp_cc_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 請求確定
        case 'cc_sales_confirm':
        // 与信取消
        case 'cc_auth_cancel':
            if( $cc_status == 'AUTH_OK' ) return true;
            break;
        // 売上取消
        case 'cc_sales_cancel':
            if( $cc_status == 'SALES_CONFIRMED' || $cc_status == 'CAPTURE_OK' ) return true;
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




##########################################################################################
// END その他の関数
##########################################################################################
