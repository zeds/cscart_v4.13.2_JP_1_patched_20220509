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
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_kuroneko_webcollect_[フックポイント名]
// (2) (1)以外の関数：fn_krnkwc_[任意の名称]

// Modified by takahashi from cs-cart.jp 2017
// トークン決済に対応
// ログにカード番号や有効期限、ShopIDなどが記録されることを回避

// Modified by takahashi from cs-cart.jp 2018
// 登録済み決済支払方法がないとテストサイトがログに出る問題を修正

// Modified by takahashi from cs-cart.jp 2019
// 出荷情報登録仕様変更対応（他社配送）

// Modified by takahashi from cs-cart.jp 2020
// ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）

// Modified by takahashi from cs-cart.jp 2020
// 後払い請求金額変更対応

// Modified by takahashi from cs-cart.jp 2021
// スマホタイプ対応

// Modified by takahashi from cs-cart.jp 2022
// Chrome 89 以降対応

use Tygh\Http;
use Tygh\Registry;
use Tygh\Mailer;
use Tygh\Settings;
use Tygh\Shippings\Shippings;

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
function fn_kuroneko_webcollect_get_payments_post(&$params, &$payments)
{
    fn_lcjp_filter_payments($payments, 'krnkwc_ccreg.tpl', 'krnkwc_ccreg');
    fn_lcjp_filter_payments($payments, 'krnkwc_ccrtn.tpl', 'krnkwc_ccreg');
}




/**
 * クロネコwebコレクトでは注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためクロネコwebコレクト
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_kuroneko_webcollect_finish_payment(&$order_id, &$pp_response, &$force_notification)
{
	// 注文データ内の支払関連情報を取得
	$payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

	// 注文データ内の支払関連情報が存在する場合
	if( !empty($payment_info) ){

        // 支払方法に紐付けられた決済代行サービスの情報を取得
        $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);

        // 決済代行サービスのスクリプトが存在しない場合は処理を終了
		if( empty($processor_data['processor_script']) ) return false;

		switch($processor_data['processor_script']){
            // クロネコヤマト関連の決済方法の場合
			case 'krnkwc_cc.php':
			case 'krnkwc_ccreg.php':
			case 'krnkwc_cvs.php':
            case 'krnkwc_cctkn.php':
            case 'krnkwc_ccrtn.php':
			case 'krnkab.php':
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
 * クロネコwebコレクトの取引状況ページにおける注文情報の抽出・表示
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_kuroneko_webcollect_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
    if( Registry::get('runtime.mode') != 'manage' ) return false;

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // 出荷情報登録仕様変更対応（他社配送）
    ///////////////////////////////////////////////
    $controller = Registry::get('runtime.controller');

    switch( $controller ){
        // クロネコwebコレクト（クレジットカード払い）
        case 'krnkwc_cc_manager':
        case 'krnkwc_cc_shipments':
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////
            $processor_scripts = array('krnkwc_cc.php', 'krnkwc_ccreg.php', 'krnkwc_cctkn.php', 'krnkwc_ccrtn.php');
            break;

        // クロネコwebコレクト（コンビニ払い）
        case 'krnkwc_cvs_manager':
            $processor_scripts = array('krnkwc_cvs.php');
            break;

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
        ///////////////////////////////////////////////
        // クロネコ代金後払いサービス
        case 'krnkab_manager':
        case 'krnkab_shipments':
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////
            $processor_scripts = array('krnkab.php');
            break;

        // その他
        default :
            return false;
    }

    // 指定した支払方法を利用した注文を抽出するクエリを追加
    $processor_ids = fn_krnkwc_get_processor_ids($processor_scripts);
    $krnk_payments = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);
    $krnk_payments = implode(',', $krnk_payments);
    $condition .= " AND ?:orders.payment_id IN ($krnk_payments)";

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // 出荷情報登録仕様変更対応（他社配送）
    ///////////////////////////////////////////////
    // 出荷登録画面の場合
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
    ///////////////////////////////////////////////
    if( $controller == 'krnkwc_cc_shipments' || $controller == 'krnkab_shipments') {
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 EOF
    ///////////////////////////////////////////////
        // 配送方法を抽出
        $fields[] = "?:shipping_descriptions.shipping_id as shipping_id";
        $fields[] = "?:shipping_descriptions.shipping as shipping";
        $join .= " LEFT JOIN ?:shipping_descriptions ON ?:orders.shipping_ids = ?:shipping_descriptions.shipping_id AND ?:shipping_descriptions.lang_code = '" . DESCR_SL . "'";

        // 出荷情報を抽出
        $fields[] = "?:jp_krnkwc_all_shipments.tracking_number as tracking_number";
        $fields[] = "?:jp_krnkwc_all_shipments.carrier as carrier";
        $fields[] = "?:jp_krnkwc_all_shipments.delivery_service_code as delivery_service_code";
        $fields[] = "?:jp_krnkwc_all_shipments.timestamp as shipment_timestamp";
        $join .= " LEFT JOIN ?:jp_krnkwc_all_shipments ON ?:orders.order_id = ?:jp_krnkwc_all_shipments.order_id";

        // 検索条件に対応
        if (!empty($params['shipping'])) {
            $condition .= db_quote(" AND ?:shipping_descriptions.shipping LIKE ?s", '%' . $params['shipping'] . '%');
        }
        if (!empty($params['tracking_number'])) {
            $condition .= db_quote(" AND ?:jp_krnkwc_all_shipments.tracking_number LIKE ?s", '%' . $params['tracking_number'] . '%');
        }
        if (!empty($params['carrier'])) {
            $condition .= db_quote(" AND ?:jp_krnkwc_all_shipments.carrier = ?s", $params['carrier']);
        }
        if (!empty($params['delivery_service_code'])) {
            $condition .= db_quote(" AND ?:jp_krnkwc_all_shipments.delivery_service_code = ?s", $params['delivery_service_code']);
        }
        if (!empty($params['shipment_period']) && $params['shipment_period'] != 'A') {
            $shipment_params['period'] = $params['shipment_period'];
            $shipment_params['time_from'] = $params['shipment_time_from'];
            $shipment_params['time_to'] = $params['shipment_time_to'];

            list($params['shipment_time_from'], $params['shipment_time_to']) = fn_create_periods($shipment_params);

            $condition .= db_quote(" AND (?:jp_krnkwc_all_shipments.timestamp >= ?i AND ?:jp_krnkwc_all_shipments.timestamp <= ?i)", $params['shipment_time_from'], $params['shipment_time_to']);
        }

        // ソートに対応
        $sortings['shipping'] = "?:shipping_descriptions.shipping";
        $sortings['tracking_number'] = "?:jp_krnkwc_all_shipments.tracking_number";
        $sortings['carrier'] = "?:jp_krnkwc_all_shipments.carrier";
        $sortings['delivery_service_code'] = "?:jp_krnkwc_all_shipments.delivery_service_code";
        $sortings['shipment_timestamp'] = "?:jp_krnkwc_all_shipments.timestamp";
    }
    // 出荷登録画面以外の場合
    else {
        // 各注文にひもづけられた取引状況コードを抽出
        $fields[] = "?:jp_krnkwc_cc_status.status_code as krnk_status_code";
        $join .= " LEFT JOIN ?:jp_krnkwc_cc_status ON ?:jp_krnkwc_cc_status.order_id = ?:orders.order_id";

        // 取引状況のソートに対応
        $sortings['krnk_status_code'] = "?:jp_krnkwc_cc_status.status_code";
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////

}




/**
 * 注文情報削除時にクレジット決済の請求ステータスを削除
 *
 * @param $order_id
 */
function fn_kuroneko_webcollect_delete_order(&$order_id)
{
    // 支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);

    // 決済代行サービスを利用した注文の場合
    if( !empty($processor_data['processor_script']) ) {
        // 利用した決済代行サービスに応じて処理を実行
        switch ($processor_data['processor_script']) {
            // クレジットカード決済の場合
            case 'krnkwc_cc.php':
            case 'krnkwc_ccreg.php':
            case 'krnkwc_cctkn.php':
            case 'krnkwc_ccrtn.php':
                // 決済取消
                fn_krnkwc_send_cc_request($order_id, 'creditcancel');
                break;

            // クロネコ代金後払いサービスの場合
            case 'krnkab.php':
                // 決済取消依頼
                fn_krnkwc_ab_cancel($order_id, false);
                break;

            // その他の場合
            default:
                // do nothing
        }
    }

    // クロネコ系決済代行サービスに関する情報を削除
    db_query("DELETE FROM ?:jp_krnkwc_cc_status WHERE order_id = ?i", $order_id);

    // 配送情報が登録されていれば削除
    // 【メモ】決済取消すると出荷情報は自動で削除されるため出荷情報取消処理は不要
    // 受注データが削除されるため、出荷情報取消処理を実行するとエラー（E021010351）になる
    $shipment_id = db_get_field("SELECT shipment_id FROM ?:shipment_items WHERE order_id = ?i", $order_id);
    if (!empty($shipment_id)) {
        db_query('DELETE FROM ?:jp_krnkwc_shipments WHERE shipment_id = ?i', $shipment_id);
    }
}




/**
 * 本アドオン用のメールテンプレートを読み込み
 *
 * @param $tpl_code
 * @param $filename
 */
function fn_kuroneko_webcollect_get_addons_mail_tpl(&$tpl_code, &$filename)
{
    if( !file_exists($filename) ){
        // 各メールテンプレートで利用可能なテンプレート変数が定義されたファイル名
        $filename = Registry::get('config.dir.addons') . 'kuroneko_webcollect/tpl_variants/' . $tpl_code . '.php';
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
function fn_kuroneko_webcollect_create_shipment(&$shipment_data, &$order_info, &$group_key, &$all_products)
{
    // ヤマトフィナンシャル系の決済方法でない場合は以下の処理は実施しない
    if( !fn_krnkwc_is_pay_by_kuroneko($order_info) ) return false;

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // 出荷情報登録仕様変更対応（他社配送）
    ///////////////////////////////////////////////
    // クロネコヤマトに追跡番号を送信する場合
    if( $shipment_data['send_slip_no'] == 'Y' ) {
        // 当該注文の支払方法に紐付けられた決済代行サービスの情報を取得
        $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_info['order_id']);

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
        ///////////////////////////////////////////////
        // クレジットカード決済、後払いの場合
        if( $processor_data['processor_script'] == 'krnkwc_cctkn.php' || $processor_data['processor_script'] == 'krnkwc_ccreg.php' || $processor_data['processor_script'] == 'krnkwc_ccrtn.php' || $processor_data['processor_script'] == 'krnkab.php'){
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////
            // 他社配送の場合
            if( $shipment_data['delivery_service'] == '99' ) {
                // 送り状番号が無い場合
                if( empty($shipment_data['tracking_number']) ) {

                    // 受付番号を取得
                    $order_no = fn_krnkwc_get_order_no($order_info['order_id']);

                    // 送り状番号に受付番号をセット
                    $shipment_data['tracking_number'] = $order_no;

                    // 後払いの場合は12桁にカット
                    if($processor_data['processor_script'] == 'krnkab.php'){
                        $shipment_data['tracking_number'] = substr($shipment_data['tracking_number'], -12);
                    }
                }
            }
            // ヤマト配送の場合
            else {
                $shipment_data['delivery_service'] = '00';

                // 送り状番号が無い場合
                if( empty($shipment_data['tracking_number']) ) {
                    // 支払サービス名を取得
                    $service_name = __("jp_kuroneko_webcollect_service_name_wc");
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2020 BOF
                    // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
                    ///////////////////////////////////////////////
                    if($processor_data['processor_script'] == 'krnkab.php'){
                        $service_name = __("jp_kuroneko_webcollect__ab");
                    }
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2020 EOF
                    ///////////////////////////////////////////////

                    fn_set_notification('E', $service_name, __('jp_kuroneko_webcollect_shipment_no_slipno', array('[order_id]' => $shipment_data['order_id'])) );

                    unset($shipment_data);

                }
            }
        }
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////

    // 追跡番号が入力されていない、またはクロネコヤマトに追跡番号を送信しない場合
    if( empty($shipment_data['tracking_number']) || $shipment_data['send_slip_no'] != 'Y' ) {
      // do nothing

    // その他の場合
    }else{
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // 出荷情報登録仕様変更対応（他社配送）
        ///////////////////////////////////////////////
        // 登録完了メッセージを表示するかどうかのフラグ
        if( $shipment_data['no_silent'] == true ){
            $silent_mode = false;
        }
        else {
            $silent_mode = true;
        }

        // 出荷情報登録
        $is_registered = fn_krnkwc_add_shipment($shipment_data, $order_info, $silent_mode);

        // 一括登録ではない場合
        if( !$shipment_data['bulk_add'] ) {
            // 出荷情報登録に失敗した場合、配送情報の新規作成は行わず、注文詳細ページにリダイレクトする
            if (!$is_registered) {
                fn_redirect("orders.details?order_id=" . $order_info['order_id']);
            }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////
        }
    }
}




/**
 * 出荷情報登録(2)
 * 配送管理を行わない設定下において、クロネコヤマトにデータを送信せずに一旦発送情報レコードが生成されている場合にのみ実行
 *
 * @param $shipment_data
 * @param $order_info
 * @param $group_key
 * @param $all_products
 */
function fn_kuroneko_webcollect_jp_update_shipment($shipment_id, &$shipment_data, &$group_key, &$all_products)
{
    // 配送情報の更新に失敗している場合や出荷情報登録処理を実行しない場合は処理を終了
    if( $shipment_id === false || empty($shipment_data['send_slip_no']) || $shipment_data['send_slip_no'] != 'Y' ) return false;

    /////////////////////////////////////////////////////////////////////////////////
    // 配送管理を行う設定の場合や、追跡番号が入力されていない場合は処理を終了 BOF
    /////////////////////////////////////////////////////////////////////////////////
    $order_info = fn_get_order_info($shipment_data['order_id'], false, true, true);
    $use_shipments = (Settings::instance()->getValue('use_shipments', '', $order_info['company_id']) == 'Y') ? true : false;

    if ($use_shipments) {
        return false;
    }elseif( empty($shipment_data['tracking_number']) ){
        return false;
    }
    /////////////////////////////////////////////////////////////////////////////////
    // 配送管理を行う設定の場合や、追跡番号が入力されていない場合は処理を終了 EOF
    /////////////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////////////
    // 登録済みの出荷情報があれば削除 BOF
    /////////////////////////////////////////////////////////////////////////////////
    // 当該注文の取引情報を照会
    $trade_info = fn_krnkwc_get_trade_info($shipment_data['order_id']);

    // 取引情報に既存の出荷情報が含まれる場合
    if( !empty($trade_info) && !empty($trade_info['slipNo']) ){
        $slips = get_object_vars($trade_info['slipNo']);
        if( !empty($slips) && is_array($slips) ){
            foreach($slips as $slip_no){
                // 既存の出荷情報を削除
                fn_krnkwc_delete_shipment($shipment_id, $slip_no, true);
            }
        }
    }
    /////////////////////////////////////////////////////////////////////////////////
    // 登録済みの出荷情報があれば削除 EOF
    /////////////////////////////////////////////////////////////////////////////////

    // 出荷情報登録
    $is_registered = fn_krnkwc_add_shipment($shipment_data, $order_info);

    // 出荷情報登録に失敗した場合、配送情報の新規作成は行わず、注文詳細ページにリダイレクトする
    if( !$is_registered ){
        fn_redirect("orders.details?order_id=" . $order_info['order_id']);
    }else{
        // 荷物問い合わせURLが指定されている場合
        if( !empty($shipment_data['tracking_url']) ){
            // 配送IDと荷物問い合わせURLをセット
            $_data = array(
                'shipment_id' => $shipment_id,
                'tracking_url' => $shipment_data['tracking_url'],
            );

            // レコードを新規追加・更新
            db_query("REPLACE INTO ?:jp_krnkwc_shipments ?e", $_data);
        }
    }
}




/**
 * クロネコヤマトによる配送情報を管理するテーブルを更新
 *
 * @param $shipment_data
 * @param $order_info
 * @param $group_key
 * @param $all_products
 * @param $shipment_id
 */
function fn_kuroneko_webcollect_create_shipment_post(&$shipment_data, &$order_info, &$group_key, &$all_products, &$shipment_id)
{
    // 荷物問い合わせURLが指定されている場合
    if( !empty($shipment_data['tracking_url']) ){
        // 配送IDと荷物問い合わせURLをセット
        $_data = array(
            'shipment_id' => $shipment_id,
            'tracking_url' => $shipment_data['tracking_url'],
        );

        // レコードを新規追加
        db_query("REPLACE INTO ?:jp_krnkwc_shipments ?e", $_data);
    }
}




/**
 * 配送情報を削除したらヤマトの荷物問い合わせURLも削除する
 *
 * @param $shipment_ids
 */
function fn_kuroneko_webcollect_delete_shipments(&$shipment_ids)
{
    if (!empty($shipment_ids)) {
        db_query('DELETE FROM ?:jp_krnkwc_shipments WHERE shipment_id IN (?a)', $shipment_ids);
    }
}




/**
 * 登録済みの荷物問い合わせURLを取得
 *
 * @param $shipments
 * @param $params
 */
function fn_kuroneko_webcollect_get_shipments_info_post(&$shipments, &$params)
{
    if( !empty($shipments) ){
        foreach($shipments as $shipment_key => $shipment_val){
            $tracking_url = db_get_field("SELECT tracking_url FROM ?:jp_krnkwc_shipments WHERE shipment_id = ?i", $shipment_val['shipment_id']);
            if(!empty($tracking_url)) $shipments[$shipment_key]['tracking_url'] = $tracking_url;
        }
    }
}




/**
 * メール送信時に荷物問い合わせURLをヤマトフィナンシャルから取得したものに書き換え
 *
 * @param $mail_tpl_var
 * @param $tpl_order_info
 * @param $tpl_shipment
 * @param $mail_template
 */
function fn_kuroneko_webcollect_mail_tpl_var_shipments_shipment_products(&$mail_tpl_var, &$tpl_order_info, &$tpl_shipment, &$mail_template)
{
    // 配送IDおよび荷物問い合わせURLが存在する場合
    if( !empty($tpl_shipment['shipment_id']) && !empty($mail_tpl_var['TRACK_URL']['value']) ){
        $tracking_url = db_get_field("SELECT tracking_url FROM ?:jp_krnkwc_shipments WHERE shipment_id = ?i", $tpl_shipment['shipment_id']);
        if(!empty($tracking_url)){
            $mail_tpl_var['TRACK_URL']['value'] = $tracking_url;
        }
    }
}




/**
 * ヤマトフィナンシャル系の決済方法を利用した注文の場合に、注文データにフラグをセット
 *
 * @param $order
 * @param $additional_data
 */
function fn_kuroneko_webcollect_get_order_info(&$order, &$additional_data)
{
    // ヤマトフィナンシャル系の決済方法を利用した注文であるかを判定
    $is_pay_by_kuroneko = fn_krnkwc_is_pay_by_kuroneko($order);
    if( !empty($is_pay_by_kuroneko) ){
        $order['pay_by_kuroneko'] = 'Y';
        if( $is_pay_by_kuroneko == 'krnkab.php'){
            $order['pay_by_kuroneko_atobarai'] = 'Y';
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
function fn_kuroneko_webcollect_save_log(&$type, &$action, &$data, &$user_id, &$content, &$event_type, &$object_primary_keys)
{
    if($type == 'requests'){
        $url = $data['url'];
        switch($url){
            // A01 : クレジット決済登録(1) 用URL（テスト環境）
            case KRNKWC_TEST_URL_CREDIT:
            // A02 : クレジット決済登録(2) 用URL（テスト環境）
            case KRNKWC_TEST_URL_CREDIT3D:
            // A03 : お預かり情報照会用URL（テスト環境）
            case KRNKWC_TEST_URL_CREDITINFOGET:
            // A04 : お預かり情報変更用URL（テスト環境）
            case KRNKWC_TEST_URL_CREDITINFOUPDATE:
            // A05 : お預かり情報削除用URL（テスト環境）
            case KRNKWC_TEST_URL_CREDITINFODELETE:
            // A06 : クレジット決済取消用URL（テスト環境）
            case KRNKWC_TEST_URL_CREDITCANCEL:
            // A07 : クレジット金額変更用URL（テスト環境）
            case KRNKWC_TEST_URL_CREDITCHANGEPRICE:
            // A08 : トークン決済登録（テスト環境）
            case KRNKWC_TEST_URL_CREDITTOKEN:
            // A09 : トークン決済登録 ３Ｄセキュア結果用（テスト環境）
            case KRNKWC_TEST_URL_CREDITTOKEN3D:
            // A01 : クレジット決済登録(1) 用URL（本番環境）
            case KRNKWC_LIVE_URL_CREDIT:
            // A02 : クレジット決済登録(2) 用URL（本番環境）
            case KRNKWC_LIVE_URL_CREDIT3D:
            // A03 : お預かり情報照会用URL（本番環境）
            case KRNKWC_LIVE_URL_CREDITINFOGET:
            // A04 : お預かり情報変更用URL（本番環境）
            case KRNKWC_LIVE_URL_CREDITINFOUPDATE:
            // A05 : お預かり情報削除用URL（本番環境）
            case KRNKWC_LIVE_URL_CREDITINFODELETE:
            // A06 : クレジット決済取消用URL（本番環境）
            case KRNKWC_LIVE_URL_CREDITCANCEL:
            // A07 : クレジット金額変更用URL（本番環境）
            case KRNKWC_LIVE_URL_CREDITCHANGEPRICE:
            // A08 : トークン決済登録（本番環境）
            case KRNKWC_LIVE_URL_CREDITTOKEN:
            // A09 : トークン決済登録 ３Ｄセキュア結果用（本番環境）
            case KRNKWC_LIVE_URL_CREDITTOKEN3D:
                $content['request'] = 'Hidden for Security Reason';
                $content['response'] = 'Hidden for Security Reason';
                break;

            default:
                // do nothing
        }
    }
}




///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2019 BOF
// 出荷情報登録仕様変更対応（他社配送）
///////////////////////////////////////////////
/**
 * テーブルと言語変数を追加する（出荷登録変更パッチ用）
 *
 * @param $user_data
 */
function fn_kuroneko_webcollect_set_admin_notification(&$user_data)
{
    // トークン決済用のprocessor_idが存在するか確認する
    $tokenId =  db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'krnkwc_cctkn.php'");

    // トークン決済用のprocessor_idが存在しない場合
    if(empty($tokenId)){
        try {
            // インストール済みの言語を取得
            $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

            // 言語変数の追加
            $lang_variables = array(
                array('name' => 'jp_kuroneko_webcollect_token_enabled', 'value' => 'クロネコwebコレクト & クロネコ代金後払いサービス において<br />トークンを利用したクレジットカード決済がご利用いただけるようになりました。'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010201', 'value' => '加盟店コードなし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010202', 'value' => '加盟店コード桁数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010204', 'value' => '加盟店コード誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010301', 'value' => '端末区分なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010401', 'value' => '受付番号なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010402', 'value' => '受付番号の桁数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010404', 'value' => '受付番号文字種別誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010501', 'value' => '決済金額なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010502', 'value' => '決済金額オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010504', 'value' => '決済金額文字種別誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010571', 'value' => '決済金額誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010572', 'value' => '決済上限金額オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010601', 'value' => '購入者名(漢字)なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010671', 'value' => '購入者名(漢字)にサポート外文字あり'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010672', 'value' => '購入者名(漢字)の桁数／バイト数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010701', 'value' => '購入者TEL なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010702', 'value' => '購入者TEL 桁数誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010704', 'value' => '購入者TEL 文字種別誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010801', 'value' => '購入者E-Mail なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010802', 'value' => '購入者E-Mail の桁数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010871', 'value' => '購入者E-Mail 誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010872', 'value' => '購入者 E-Mail に一部未入力あり'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010901', 'value' => '支払回数なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010902', 'value' => '支払回数誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081010904', 'value' => '支払回数文字種別誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011071', 'value' => '商品名にサポート外文字あり'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011072', 'value' => '商品名の桁数／バイト数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011171', 'value' => 'カード会社コード(API 用)なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011172', 'value' => 'カード会社コード(API 用)誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011201', 'value' => 'トークンなし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011202', 'value' => 'トークンの桁数誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011204', 'value' => 'トークンにサポート外文字あり'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011371', 'value' => '加盟店EC サイトURL なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011372', 'value' => '加盟店EC サイトURL 桁数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011373', 'value' => '加盟店EC サイトURL 3D 認証不可'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011471', 'value' => '出荷予定日の形式誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011472', 'value' => '出荷予定日誤り（過去日）'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A081011473', 'value' => '出荷予定日誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000001', 'value' => '加盟店の決済利用不可'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000002', 'value' => 'クレジット決済利用開始日設定不備'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000003', 'value' => '受付番号重複'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000004', 'value' => 'トークンのカード情報取得失敗'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000005', 'value' => 'オプションサービスの契約無し'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000006', 'value' => 'システムエラー（カード情報取得）'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000007', 'value' => 'カード支払回数誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000008', 'value' => 'クレジットカード情報誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000010', 'value' => '与信処理不可'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000011', 'value' => 'お預かり処理不可'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A082000012', 'value' => '各種マスタ取得失敗'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010201', 'value' => '加盟店コードなし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010202', 'value' => '加盟店コード桁数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010204', 'value' => '加盟店コード誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010301', 'value' => '受付番号なし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010302', 'value' => '受付番号の桁数オーバー'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010304', 'value' => '受付番号文字種別誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010401', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010501', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010601', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010701', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010801', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091010901', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091011001', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091011101', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091011201', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091011501', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091011701', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091011801', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091011901', 'value' => '3D セキュア応答電文パラメータ誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091012001', 'value' => '3D トークンなし'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A091012002', 'value' => '3D トークン桁数誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A092000001', 'value' => '受付番号重複'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A092000002', 'value' => 'システムエラー（カード情報取得）'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A092000003', 'value' => '3D セキュア応答電文誤り'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A092000004', 'value' => '3D セキュア結果ＮＧ'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A092000005', 'value' => '与信処理NG'),
                array('name' => 'Languages::jp_kuroneko_webcollect_errmsg_A092000006', 'value' => 'お預かり処理失敗'),
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
            db_query("INSERT INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9233, 'クロネコwebコレクト（クレジットカード払い・トークン方式）', 'krnkwc_cctkn.php', 'addons/kuroneko_webcollect/views/orders/components/payments/krnkwc_cctkn.tpl', 'krnkwc_cctkn.tpl', 'N', 'P')");

            // トークン決済利用可能のメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_kuroneko_webcollect_token_enabled'));
        }
        catch (Exception $e){
            // エラー発生(Service Unavailableメッセージを出さない)
        }
    }

    // 出荷登録用のテーブルが存在するか確認する
    $is_table =  db_get_field("SHOW TABLES LIKE '%jp_krnkwc_all_shipments'");

    // 出荷登録用のテーブルが存在しない場合
    if(empty($is_table)){
        // インストール済みの言語を取得
        $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

        // 言語変数の追加
        $lang_variables = array(
            array('name' => 'jp_kuroneko_webcollect_delivery_service', 'value' => '配送サービス'),
            array('name' => 'jp_kuroneko_webcollect_delivery_service_00', 'value' => 'ヤマト配送'),
            array('name' => 'jp_kuroneko_webcollect_delivery_service_99', 'value' => '他社配送'),
            array('name' => 'jp_kuroneko_webcollect_service_name_wc_cc_shipments', 'value' => 'クロネコwebコレクト（カード払い）出荷登録'),
            array('name' => 'jp_kuroneko_webcollect_cc_shipments_status_long', 'value' => 'クロネコwebコレクト（カード払い）の出荷情報登録状況'),
            array('name' => 'jp_kuroneko_webcollect_cancel_shipment', 'value' => '出荷情報取消'),
            array('name' => 'jp_kuroneko_webcollect_add_shipment', 'value' => '出荷情報登録'),
            array('name' => 'jp_kuroneko_webcollect_shipment_add_success', 'value' => '送り状番号 [slipno] の配送情報を登録しました。'),
            array('name' => 'jp_kuroneko_webcollect_shipment_no_slipno', 'value' => '注文番号 [order_id] に送り状番号が入力されていません。'),
            array('name' => 'jp_kuroneko_webcollect_shipment_slipno_not_12digit', 'value' => '注文番号 [order_id] の送り状番号 [slipno] の桁数が不正です。'),
            array('name' => 'jp_kuroneko_webcollect_shipment_slipno_not_num', 'value' => '注文番号 [order_id] の送り状番号 [slipno] に禁止文字があります。'),
            array('name' => 'jp_kuroneko_webcollect_shipment_change_enabled', 'value' => 'クロネコwebコレクト（カード払い）において<br />出荷情報登録の変更が適用されました。'),
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
            // 出荷登録用のテーブルを追加
            db_query("CREATE TABLE ?:jp_krnkwc_all_shipments (order_id mediumint(8) unsigned NOT NULL, tracking_number	varchar(255) NOT NULL, carrier varchar(255) NOT NULL, delivery_service_code varchar(2) NOT NULL, 	timestamp	int(11) NOT NULL, PRIMARY KEY (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

            // ト出荷情報登録変更のメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_kuroneko_webcollect_shipment_change_enabled'));
        }
        catch (Exception $e){
            // エラー発生(Service Unavailableメッセージを出さない)
            fn_set_notification('E', __('error'), __('error_occurred') . '(' . $e->getMessage() . ')');
        }
    }

    // トークン決済用のprocessor_idが存在するか確認する
    $tokenId_rtn =  db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'krnkwc_ccrtn.php'");

    // トークン決済用のprocessor_idが存在しない場合
    if(empty($tokenId_rtn)){
        try {
            // インストール済みの言語を取得
            $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

            // 言語変数の追加
            $lang_variables = array(
                array('name' => 'jp_kuroneko_webcollect_reg_token_enabled', 'value' => 'クロネコwebコレクト & クロネコ代金後払いサービス において<br />登録済みクレジットカード払いにおけるトークンを利用したクレジットカード決済がご利用いただけるようになりました。'),
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
            db_query("INSERT INTO ?:payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('クロネコwebコレクト（登録済みクレジットカード払い・トークン方式）', 'krnkwc_ccrtn.php', 'addons/kuroneko_webcollect/views/orders/components/payments/krnkwc_ccrtn.tpl', 'krnkwc_ccrtn.tpl', 'N', 'P')");

            // トークン決済利用可能のメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_kuroneko_webcollect_reg_token_enabled'));
        }
        catch (Exception $e){
            // エラー発生(Service Unavailableメッセージを出さない)
            fn_set_notification('E', __('error'), __('error_occurred') . '(' . $e->getMessage() . ')');
        }
    }

    // クロネコ後払い追加機能
    $jp_kuroneko_webcollect__ab = db_get_field("SELECT count(*) cnt FROM ?:language_values WHERE name = ?s", "jp_kuroneko_webcollect__ab");
    if($jp_kuroneko_webcollect__ab == 0){
        // インストール済みの言語を取得
        $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

        // 言語変数の追加
        $lang_variables = array(
            array('name' => 'jp_kuroneko_webcollect__ab', 'value' => 'クロネコ代金後払いサービス'),
            array('name' => 'jp_kuroneko_webcollect__abchangeprice_success', 'value' => '金額変更が完了しました。'),
            array('name' => 'jp_kuroneko_webcollect_ab_changeprice_error_msg', 'value' => '金額変更に失敗しました。'),
            array('name' => 'jp_kuroneko_webcollect_service_name_wc_ab_shipments', 'value' => 'クロネコ代金後払いサービス 出荷登録'),
            array('name' => 'jp_kuroneko_webcollect_ab_shipments_status_long', 'value' => 'クロネコ代金後払いサービスの出荷情報登録状況'),
            array('name' => 'jp_kuroneko_webcollect_ab_use_smartphone', 'value' => 'スマホタイプ (請求書SMS送付) 使用'),
            array('name' => 'jp_kuroneko_webcollect_ab_sms_invoice', 'value' => '請求書SMS送付'),
            array('name' => 'jp_kuroneko_webcollect_ab_smartphone', 'value' => '携帯電話番号'),
            array('name' => 'jp_kuroneko_webcollect_ab_sms_auth_nincode', 'value' => '請求書SMS送付 認証コード送信'),
            array('name' => 'jp_kuroneko_webcollect_ab_nincode', 'value' => '認証コード'),
            array('name' => 'jp_kuroneko_webcollect_ab_payment_type', 'value' => '決済タイプ'),
            array('name' => 'jp_kuroneko_webcollect_ab_separate_include_only', 'value' => '別送/同梱のみ'),
            array('name' => 'jp_kuroneko_webcollect_ab_smartphone_only', 'value' => 'スマホタイプ(請求書SMS送付)のみ'),
            array('name' => 'jp_kuroneko_webcollect_ab_separate_include_or_smartphone', 'value' => '別送/同梱あるいはスマホタイプ(請求書SMS送付)'),
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
    }

    // Chrome 89 対応
    // 3Dトークンと受付番号を保持用のテーブルが存在するか確認する
    $is_table =  db_get_field("SHOW TABLES LIKE '%jp_krnkwc_3dsecure'");
    if(empty($is_table)){
        // インストール済みの言語を取得
        $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

        // 言語変数の追加
        $lang_variables = array(
            array('name' => 'jp_kuroneko_webcollect_shipment_change_enabled', 'value' => 'クロネコwebコレクト（カード払い）において<br />3D認証修正パッチが適用されました。'),
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
            // 3Dトークンと受付番号を保持用のテーブルを追加
            db_query("CREATE TABLE ?:jp_krnkwc_3dsecure (order_id mediumint(8) unsigned NOT NULL, three_d_token	varchar(255) NOT NULL, order_no varchar(255) NOT NULL, auth_key varchar(255) NOT NULL, PRIMARY KEY (order_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

            // お客様コメント一時保持用のテーブルを追加
            db_query("CREATE TABLE ?:jp_krnkwc_notes (user_id mediumint(8) unsigned NOT NULL, notes	text NOT NULL, PRIMARY KEY (user_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

            // ト出荷情報登録変更のメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_kuroneko_webcollect_shipment_change_enabled'));
        }
        catch (Exception $e){
            // エラー発生(Service Unavailableメッセージを出さない)
            fn_set_notification('E', __('error'), __('error_occurred') . '(' . $e->getMessage() . ')');
        }
    }
}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2019 EOF
///////////////////////////////////////////////




///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2022 BOF
///////////////////////////////////////////////
/**
 * 決済時のお客様コメントを一時的に保持（3D認証決済用）
 * Chrome 89 対応
 *
 * @param $cart
 * @param $cart_products
 * @param $auth
 * @param $calculate_shipping
 * @param $calculate_taxes
 * @param $apply_cart_promotions
 */
function fn_kuroneko_webcollect_calculate_cart(&$cart, $cart_products, $auth, $calculate_shipping, $calculate_taxes, $apply_cart_promotions){
    $user_id = $cart['user_data']['user_id'];

    db_query("REPLACE INTO ?:jp_krnkwc_notes(user_id, notes) VALUES(?i, ?s)", $user_id, $cart['notes']);
}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2022 EOF
///////////////////////////////////////////////
##########################################################################################
// END フックポイントで動作する関数
##########################################################################################





##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################

// アドオンのインストール時の動作
function fn_krnkwc_addon_install()
{
    fn_lcjp_install('kuroneko_webcollect');

    // 本アドオン用のメールテンプレートレコードを抽出
    $is_record_inserted = db_get_row("SELECT * FROM ?:jp_mtpl WHERE tpl_code= 'addons_kuroneko_webcollect_cvs1_notification'");

    // 本アドオン用のメールテンプレートレコードが作成されていない場合、作成する
    if( empty($is_record_inserted) ){

        $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

        $add_mtpl = array(
            array('tpl_code' => 'addons_kuroneko_webcollect_cvs1_notification',
                'tpl_name' => "コンビニ支払い（セブンイレブン）の支払情報",
                'tpl_trigger' => "支払方法にコンビニ支払い（セブンイレブン）を指定した注文",
                'subject' => "【{%SP_NAME%}】 ご注文番号:{%ORDER_ID%} のお支払いについて",
                'body_txt' => "{%LASTNAME%} {%FIRSTNAME%}様\r\n\r\n{%SP_NAME%}をご利用いただきありがとうございます。\r\n\r\n注文番号:{%ORDER_ID%} において、コンビニエンスストア（{%CVS_NAME%}）でのお支払い希望を承りました。\r\n下記のURLにアクセスし、払込票を印刷またはスマートフォンなどに表示して店頭でお支払いをお願いいたします。\r\n━━━━━━━━━━━━━━━━━━━━━━\r\n払込票URL : {%BILLING_URL%}\r\n払込票番号 : {%BILLING_NO%}\r\n支払い期限日 : {%EXPIRY_DATE%}\r\n━━━━━━━━━━━━━━━━━━━━━━",
            ),
            array('tpl_code' => 'addons_kuroneko_webcollect_cvs2_notification',
                'tpl_name' => "コンビニ支払い（ファミリーマート）の支払情報",
                'tpl_trigger' => "支払方法にコンビニ支払い（ファミリーマート）を指定した注文",
                'subject' => "【{%SP_NAME%}】 ご注文番号:{%ORDER_ID%} のお支払いについて",
                'body_txt' => "{%LASTNAME%} {%FIRSTNAME%}様\r\n\r\n{%SP_NAME%}をご利用いただきありがとうございます。\r\n\r\n注文番号:{%ORDER_ID%} において、コンビニエンスストア（{%CVS_NAME%}）でのお支払い希望を承りました。\r\nコンビニ店頭端末で以下の情報を入力して申込券を発券し、レジでお支払いをお願いいたします。\r\n━━━━━━━━━━━━━━━━━━━━━━\r\n企業コード : {%COMPANY_CODE%}\r\n注文番号 : {%ORDER_NO_F%}\r\n支払期限日 : {%EXPIRY_DATE%}\r\n━━━━━━━━━━━━━━━━━━━━━━",
            ),
            array('tpl_code' => 'addons_kuroneko_webcollect_cvs3_notification',
                'tpl_name' => "コンビニ支払い（ローソン/サークルKサンクス/ミニストップ/セイコーマート）の支払情報",
                'tpl_trigger' => "支払方法にコンビニ支払い（ローソン/サークルKサンクス/ミニストップ/セイコーマート）を指定した注文",
                'subject' => "【{%SP_NAME%}】 ご注文番号:{%ORDER_ID%} のお支払いについて",
                'body_txt' => "{%LASTNAME%} {%FIRSTNAME%}様\r\n\r\n{%SP_NAME%}をご利用いただきありがとうございます。\r\n\r\n注文番号:{%ORDER_ID%} において、コンビニエンスストア（{%CVS_NAME%}）でのお支払い希望を承りました。\r\nコンビニ店頭端末で以下の情報を入力して申込券を発券し、レジでお支払いをお願いいたします。\r\n━━━━━━━━━━━━━━━━━━━━━━\r\nお支払受付番号 : {%ECON_NO%}\r\n電話番号 : {%PHONE%}\r\n支払期限日 : {%EXPIRY_DATE%}\r\n━━━━━━━━━━━━━━━━━━━━━━\r\n※ こちらはローソン、サークルKサンクス、ミニストップ、セイコーマートの店頭端末を利用してお支払いいただけます。",
            ),
        );

        foreach($add_mtpl as $_data){
            $tpl_id = db_query("INSERT INTO ?:jp_mtpl ?e", $_data);

            foreach ($languages as $lc => $_v) {
                $_data['tpl_id'] = $tpl_id;
                $_data['lang_code'] = $lc;
                db_query("REPLACE INTO ?:jp_mtpl_descriptions ?e", $_data);
            }

            if (fn_allowed_for('ULTIMATE')){
                // 登録済みのショップ（出品者）のIDを取得
                $company_ids = db_get_fields("SELECT company_id FROM ?:companies");

                // 登録済みのショップ（出品者）が存在する場合
                if( !empty($company_ids) ){
                    // 問い合わせ控え用メールテンプレート情報を取得
                    $default_mtpl_descs = db_get_array("SELECT * FROM ?:jp_mtpl_descriptions WHERE company_id = ?i AND tpl_id = ?i", 0, $tpl_id);

                    // 問い合わせ控え用メールテンプレート情報が存在する場合
                    if( !empty($default_mtpl_descs) && is_array($default_mtpl_descs) ){
                        // 問い合わせ控え用メールテンプレート情報を新しいショップ（出品者）向けにコピー
                        foreach($default_mtpl_descs as $default_mtpl_desc){
                            $_data = $default_mtpl_desc;
                            foreach( $company_ids as $company_id){
                                $_data['company_id'] = $company_id;
                                db_query("REPLACE INTO ?:jp_mtpl_descriptions ?e", $_data);
                            }
                        }
                    }
                }
            }
        }
    }
}




/**
 * アドオンのアンインストール時に実行する処理
 *
 */
function fn_krnkwc_addon_uninstall()
{
    // クロネコwebコレクト、クロネコ代金後払いサービスに関する支払方法を削除
    $file_list = array('krnkwc_cc.php', 'krnkwc_ccreg.php', 'krnkwc_cvs.php', 'krnkab.php', 'krnkwc_cctkn.php', 'krnkwc_ccrtn.php');

    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN (?a)))", $file_list);
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN (?a))", $file_list);
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN (?a)", $file_list);

    // クロネコwebコレクト、クロネコ代金後払いサービスに関するメールテンプレートを削除
    $tpl_ids = db_get_fields('SELECT tpl_id FROM ?:jp_mtpl WHERE tpl_code LIKE ?s', "%addons_kuroneko_webcollect%");
    if( !empty($tpl_ids) ){
        foreach($tpl_ids as $tpl_id){
            db_query("DELETE FROM ?:jp_mtpl WHERE tpl_id = ?i", $tpl_id);
            db_query("DELETE FROM ?:jp_mtpl_descriptions WHERE tpl_id = ?i", $tpl_id);
        }
    }
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
function fn_settings_variants_addons_kuroneko_webcollect_pending_status()
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
 * クロネコwebコレクトに送信するパラメータをセット
 *
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_krnkwc_get_params($type, $order_id, $order_info, $processor_data)
{
    // 送信パラメータを初期化
    $params = array();

    // 処理別に異なるパラメータをセット
    switch($type){
        // クレジット決済登録
        case 'credit':

            // 端末区分
            $params['device_div'] = fn_krnkwc_get_device_div();

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            if($processor_data['processor_script'] == 'krnkwc_cctkn.php' || $processor_data['processor_script'] == 'krnkwc_ccrtn.php'){
                // 機能区分
                $params['function_div'] = 'A08';
            }
            else {
                // 機能区分
                $params['function_div'] = 'A01';

                // 認証区分
                $params['auth_div'] = fn_krnkwc_get_auth_div($params['device_div'], $processor_data);
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            // 加盟店コード
            $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

            // 受付番号
            $params['order_no'] = fn_krnkwc_get_krnk_order_no($order_id);

            // 決済金額
            $params['settle_price'] = round($order_info['total']);

            // 購入者名（漢字）
            $params['buyer_name_kanji'] = mb_strcut(str_replace('－', '-', mb_convert_kana($order_info['b_firstname'] . $order_info['b_lastname'], "KVHA", 'UTF-8')), 0, 60);

            // 購入者電話番号
            $params['buyer_tel'] = substr(preg_replace("/[^0-9]+/", "", mb_convert_kana($order_info['b_phone'],"a")), 0, 11);

            // 購入者 E-Mail
            $params['buyer_email'] = $order_info['email'];

            // 支払回数
            if( is_null($order_info['payment_info']['pay_way']) ){
                $params['pay_way'] = 1;
            }elseif( $order_info['payment_info']['pay_way'] == '2'){
                if( empty($order_info['payment_info']['jp_cc_installment_times']) ){
                    $params['pay_way'] = 2;
                }else{
                    $params['pay_way'] = $order_info['payment_info']['jp_cc_installment_times'];
                }
            }else{
                $params['pay_way'] = $order_info['payment_info']['pay_way'];
            }

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            // 通常のカード決済の場合
            if( $processor_data['processor_script'] == 'krnkwc_cc.php' || $processor_data['processor_script'] == 'krnkwc_cctkn.php'){

                // 次回以降のお買い物でこのカード情報を使用する場合
                if( $order_info['payment_info']['register_card_info'] == 'true' ){
                    $params['member_id'] = $order_info['user_id'];   // カード保有者を特定するID

                    if($processor_data['processor_script'] == 'krnkwc_cctkn.php') {
                        $params['authentication_key'] = $order_info['payment_info']['authKey']; // トークン取得時と同じ認証キー
                    }
                    else {
                        $params['option_service_div'] = '01';   // オプションサービス受注
                        $params['authentication_key'] = fn_krnkwc_gererate_auth_key($order_info['user_id']);   // 認証キー
                    }

                    $krnkwc_access_key = Registry::get('addons.kuroneko_webcollect.access_key'); // アクセスキー
                    $params['check_sum'] = fn_krnkwc_generate_checksum($params['member_id'], $params['authentication_key'], $krnkwc_access_key);   // チェックサム
                // その他の場合
                }else{
                    if($processor_data['processor_script'] == 'krnkwc_cc.php') {
                        $params['option_service_div'] = '00';   // 通常受注
                    }
                }

                if($processor_data['processor_script'] == 'krnkwc_cctkn.php'){
                    // カード会社コード（API用）
                    $params['card_code_api'] = $order_info['payment_info']['card_code_api'];

                        // トークン
                    $params['token'] = $order_info['payment_info']['token'];

                    // 加盟店ECサイトURL
                    $params['trader_ec_url'] = fn_url("payment_notification.process&payment=krnkwc_cctkn&order_id=$order_id&pt=3ds", AREA, 'current');
                }
                else {
                    // カード番号（数値以外の値は削除）
                    $params['card_no'] = mb_ereg_replace('[^0-9]', '', $order_info['payment_info']['card_number']);

                    // カード名義人
                    $params['card_owner'] = strtoupper($order_info['payment_info']['card_owner']);

                    // カード有効期限
                    $params['card_exp'] = $order_info['payment_info']['expiry_month'] . $order_info['payment_info']['expiry_year'];

                    // カード会社コード（API用）
                    $params['card_code_api'] = fn_krnkwc_get_card_code_api($params['card_no']);

                    // 加盟店ECサイトURL
                    $params['trader_ec_url'] = fn_url("payment_notification.process&payment=krnkwc_cc&order_id=$order_id&pt=3ds", AREA, 'current');
                }
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2017 EOF
                ///////////////////////////////////////////////

                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2022 BOF
                // カートコード
                ///////////////////////////////////////////////
                $params['ec_cart_code'] = KRNK_CART_CODE;
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2022 EOF
                ///////////////////////////////////////////////

            // 登録済みカード決済の場合
            }elseif( $processor_data['processor_script'] == 'krnkwc_ccreg.php' || $processor_data['processor_script'] == 'krnkwc_ccrtn.php'){

                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2019 BOF
                // トークン決済に対応
                ///////////////////////////////////////////////
                if($processor_data['processor_script'] == 'krnkwc_ccrtn.php'){
                    // トークン
                    $params['token'] = $order_info['payment_info']['token'];

                    // 加盟店ECサイトURL
                    $params['trader_ec_url'] = fn_url("payment_notification.process&payment=krnkwc_ccrtn&order_id=$order_id&pt=3ds", AREA, 'current');
                }
                else {
                    // オプションサービス区分
                    $params['option_service_div'] = '01';   // オプションサービス受注

                    // カード保有者を特定するID
                    $params['member_id'] = $order_info['user_id'];

                    // 認証キー
                    $params['authentication_key'] = fn_krnkwc_gererate_auth_key($order_info['user_id']);

                    // アクセスキー
                    $krnkwc_access_key = Registry::get('addons.kuroneko_webcollect.access_key');

                    // チェックサム
                    $params['check_sum'] = fn_krnkwc_generate_checksum($params['member_id'], $params['authentication_key'], $krnkwc_access_key);

                    // カード識別キー
                    $params['card_key'] = 1;

                    // 最終利用日時
                    $registered_card_info = fn_krnkwc_get_registered_card_info($order_info['user_id']);
                    $params['last_credit_date'] = $registered_card_info['lastCreditDate'];

                    // 加盟店ECサイトURL
                    $params['trader_ec_url'] = fn_url("payment_notification.process&payment=krnkwc_ccreg&order_id=$order_id&pt=3ds", AREA, 'current');
                }
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2019 EOF
                ///////////////////////////////////////////////
            }

            // セキュリティコードによる認証を行う場合
            if( $params['auth_div'] == 2 ){
                // セキュリティコード
                $params['security_code'] = $order_info['payment_info']['cvv2'];
            }

            // 予備1（加盟店テスト環境返却用エラーコード）
            // $params['reserve_1'] = 'A012060001';

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2022 BOF
            // カートコード
            ///////////////////////////////////////////////
            $params['ec_cart_code'] = KRNK_CART_CODE;
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2022 EOF
            ///////////////////////////////////////////////

            break;

        // クレジット決済登録（3Dセキュア後）
        case 'credit_3ds':

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 BOF
            // トークン決済に対応
            ///////////////////////////////////////////////
            if($processor_data['processor_script'] == 'krnkwc_cctkn.php' || $processor_data['processor_script'] == 'krnkwc_ccrtn.php') {
                // 機能区分
                $params['function_div'] = 'A09';
            }
            else {
                // 機能区分
                $params['function_div'] = 'A02';
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2017 EOF
            ///////////////////////////////////////////////

            // 加盟店コード
            $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

            // 予備1（加盟店テスト環境返却用エラーコード）
            //$params['reserve_1'] = 'A021010401';

            break;

        // コンビニ（オンライン）払い
        case 'cvs':

            // コンビニ種別から機能区分を取得
            $params['function_div'] = fn_krnkwc_get_cvs_function_div($order_info['payment_info']['cvs']);

            // 加盟店コード
            $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

            // 端末区分
            $params['device_div'] = fn_krnkwc_get_device_div();

            // 受付番号
            $params['order_no'] = fn_krnkwc_get_krnk_order_no($order_id);

            // 商品名
            $params['goods_name'] = fn_krnkwc_format_product_name($order_info);

            // 決済金額
            $params['settle_price'] = round($order_info['total']);

            // 購入者名（漢字）
            $params['buyer_name_kanji'] = mb_substr(str_replace('－', '-', mb_convert_kana($order_info['b_firstname'] . $order_info['b_lastname'], "KVHA", 'UTF-8')), 0, 20);

            // セブンイレブン以外の場合
            if($params['function_div'] != 'B01'){
                // ファミリーマートの場合
                if( $params['function_div'] == 'B02' ){
                    // 購入者名（フリガナ）
                    $name_kana_info = fn_lcjp_get_name_kana($order_info);
                    $params['buyer_name_kana'] = mb_substr(str_replace('－', '-', mb_convert_kana($name_kana_info['firstname_kana'] . $name_kana_info['lastname_kana'], "KVC", 'UTF-8')), 0, 15);
                }

                // 購入者電話番号
                $params['buyer_tel'] = substr(preg_replace("/[^0-9]+/", "", mb_convert_kana($order_info['b_phone'],"a")), 0, 11);
            }

            // 購入者 E-Mail
            $params['buyer_email'] = $order_info['email'];

            // 予備1（加盟店テスト環境返却用エラーコード）
            //$params['reserve_1'] = 'A021010401';

            break;

        // クロネコ代金後払いサービス
        case 'ab':

            // 加盟店コード
            $params['ycfStrCode'] = Registry::get('addons.kuroneko_webcollect.ycf_str_code');

            // 受注番号
            $params['orderNo'] = fn_krnkwc_get_krnk_ab_order_no($order_id);

            // 受注日
            $params['orderYmd'] = date('Ymd');

            // 出荷予定日
            $params['shipYmd'] = date("Ymd",strtotime("+" . (int)$processor_data['processor_params']['ship_after'] . " day"));

            // 氏名
            $params['name'] = mb_substr(str_replace('－', '-', mb_convert_kana($order_info['b_firstname'] . '　' . $order_info['b_lastname'], "KVHA", 'UTF-8')), 0, KRNKAB_MAXLEN_NAME);

            // 氏名カナ
            $name_kana_info = fn_lcjp_get_name_kana($order_info);
            $params['nameKana'] = mb_substr(str_replace('－', '-', mb_convert_kana($name_kana_info['firstname_kana'] . ' ' . $name_kana_info['lastname_kana'], "krnash", 'UTF-8')), 0, KRNKAB_MAXLEN_NAME_KANA);

            // 郵便番号
            $params['postCode'] = preg_replace("/[^0-9]+/", '', $order_info['b_zipcode']);

            // クロネコ代金後払いサービスに送信する住所情報をフォーマット
            list($address1, $address2) = fn_krnkwc_ab_format_address($order_info);

            // 住所1
            $params['address1'] = $address1;

            // 住所2
            if( !empty($address2) ){
                $params['address2'] = $address2;
            }

            // 電話番号
            $params['telNum'] = substr(preg_replace("/[^0-9]+/", "", mb_convert_kana($order_info['b_phone'],"a")), 0, KRNKAB_MAXLEN_NAME_TELNUM);

            // メールアドレス
            $params['email'] = $order_info['email'];

            // 決済金額総計
            $params['totalAmount'] = round($order_info['total']);

            // 送り先区分
            $params['sendDiv'] = $order_info['payment_info']['sendDiv'];

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2022 BOF
            // カートコード
            ///////////////////////////////////////////////
            $params['cartCode'] = KRNK_CART_CODE;
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2022 EOF
            ///////////////////////////////////////////////

            // 購入商品名称
            $params['itemName1'] = fn_krnkwc_format_product_name($order_info, 'atobrai');

            // 購入商品小計
            $params['subTotal1'] = $params['totalAmount'];

            // 送り先区分が「1:別送・本人以外送り」の場合
            if($params['sendDiv'] == 1){
                // 送り先名称
                $params['sendName'] = mb_substr(str_replace('－', '-', mb_convert_kana($order_info['s_firstname'] . '　' . $order_info['s_lastname'], "KVHA", 'UTF-8')), 0, KRNKAB_MAXLEN_NAME);

                // 送り先郵便番号
                $params['sendPostCode'] = preg_replace("/[^0-9]+/", '', $order_info['s_zipcode']);

                // クロネコ代金後払いサービスに送信する送り先住所情報をフォーマット
                list($send_address1, $send_address2) = fn_krnkwc_ab_format_address($order_info, 'shipping');

                // 住所1
                $params['sendAddress1'] = $send_address1;

                // 住所2
                if( !empty($address2) ){
                    $params['sendAddress2'] = $send_address2;
                }

                // 電話番号
                $params['sendTelNum'] = substr(preg_replace("/[^0-9]+/", "", mb_convert_kana($order_info['s_phone'],"a")), 0, KRNKAB_MAXLEN_NAME_TELNUM);
            }

            // 依頼日時
            $params['requestDate'] = date('YmdHis');

            // パスワード
            $params['password'] = Registry::get('addons.kuroneko_webcollect.atobarai_password');

            break;

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2021 BOF
        // スマホタイプ対応
        ///////////////////////////////////////////////
        // クロネコ代金後払いサービス マホタイプ(請求書SMS送付）
        case 'absms':
            // 加盟店コード
            $params['ycfStrCode'] = Registry::get('addons.kuroneko_webcollect.ycf_str_code');

            // 受注番号
            $params['orderNo'] = $order_info['krnkab_orderno'];

            // 認証コード
            $params['ninCode'] = $order_info['nincode'];

            // 受注日
            $params['requestDate'] = date('YmdHis');

            // パスワード
            $params['password'] = Registry::get('addons.kuroneko_webcollect.atobarai_password');
            break;
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2021 EOF
        ///////////////////////////////////////////////
        default:
            // do nothing
            break;
    }

    return $params;
}




/**
 * アクセス元デバイスの種類から端末区分を取得する
 *
 * @return int
 */
function fn_krnkwc_get_device_div()
{
    // Mobile Detect をロード（Mobile Detect は定期的に最新版に入れ替えた方がよい
    require_once(Registry::get('config.dir.addons') . 'kuroneko_webcollect/lib/Mobile_Detect.php');

    // インスタンスを作成
    $detect = new Mobile_Detect;

    // モバイル端末（= 非PC）であるかを判定
    $result = $detect->isMobile();

    // モバイル端末の場合
    if($result){
        // 1（スマートフォン）を返す
        return 1;
    // PC（非モバイル端末）の場合
    }else{
        // 2（パソコン）を返す
        return 2;
    }
}




/**
 * 23桁の受付番号を生成
 *
 * @param $order_id
 * @return string
 */
function fn_krnkwc_get_krnk_order_no($order_id)
{
    // 受付番号を生成
    return sprintf("%013d", (int)$order_id) . (string)time();
}




/**
 * 認証区分を取得
 *
 * @param $device_div
 * @param $processor_data
 * @return int
 */
function fn_krnkwc_get_auth_div($device_div, $processor_data)
{
    // 3Dセキュアの利用有無
    $_3dsecure_flag = $processor_data['processor_params']['tdflag'];

    // PCからのアクセスでかつ3Dセキュアを利用する設定の場合
    if( $device_div == 2 && $_3dsecure_flag == 'true'){
        // 3Dセキュアあり、セキュリティコード認証なし
        return 1;
    // PC以外のデバイスからのアクセス、または3Dセキュアを利用しない設定の場合
    }else{
        // 3Dセキュアなし、セキュリティコード認証あり
        return 2;
    }
}




/**
 * ８桁の認証キーを生成
 *
 * @param int $length
 * @return string
 */
function fn_krnkwc_gererate_auth_key($user_id, $length = 8)
{
    // クロネコwebコレクトにカード情報が登録されているかチェック
    $auth_key = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method =?s", $user_id, 'krnkwc_ccreg');

    // クロネコwebコレクトにカード情報が登録されている場合
    if( !empty($auth_key) ){
        // 登録されている認証キーを返す
        return $auth_key;
    }else{
        // vars
        $auth_key = array();
        $auth_key_strings = array(
            "sletter" => range('a', 'z'),
            "cletter" => range('A', 'Z'),
            "number"  => range('0', '9'),
            "symbol"  => array_merge(range('!', '/'), range(':', '?'), range('{', '~')),
        );

        //logic
        while (count($auth_key) < $length) {
            // 4種類必ず入れる
            if (count($auth_key) < 4) {
                $key = key($auth_key_strings);
                next($auth_key_strings);
            } else {
                // 後はランダムに取得
                $key = array_rand($auth_key_strings);
            }
            $auth_key[] = $auth_key_strings[$key][array_rand($auth_key_strings[$key])];
        }
        // 生成したパスワードの順番をランダムに並び替え
        shuffle($auth_key);

        return implode($auth_key);
    }
}




/**
 * チェックサムを生成
 *
 * @param $member_id
 * @param $auth_key
 * @param $access_key
 * @return string
 */
function fn_krnkwc_generate_checksum($member_id, $auth_key, $access_key)
{
    return hash('sha256', $member_id . $auth_key . $access_key);
}




/**
 * ユーザーが入力したカード番号からカード会社コードを取得
 *
 * @param $card_no
 * @return int
 */
function fn_krnkwc_get_card_code_api($card_no)
{
    // カード番号の先頭12桁を取得
    $twelve_digit = substr( $card_no , 0 , 12 );

    // テスト用カード番号の場合
    if($twelve_digit >= '000000000000' && $twelve_digit <= '000000000020'){
        // VISAのコードを返す
        return 9;
    }

    // VISA
    if( preg_match("/(^4[0-9]{12}(?:[0-9]{3})?$)/", $card_no, $matches) ){
        return 9;
    // Master
    }elseif( preg_match("/(^5[1-5][0-9]{14}$)/", $card_no, $matches ) ) {
        return 10;
    // Amex
    }elseif( preg_match("/(^3[47][0-9]{13}$)/", $card_no, $matches ) ) {
        return 12;
    // Diners
    }elseif( preg_match("/(^3(?:0[0-5]|[68][0-9])[0-9]{11}$)/", $card_no, $matches ) ) {
        return 2;
    // JCB
    }elseif( preg_match('/(^(?:2131|1800|35\d{3})\d{11}$)/', $card_no, $matches ) ) {
        return 3;
    // その他
    }else{
        return 0;
    }
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
function fn_krnkwc_format_payment_info($type, $order_id, $payment_info = array(), $results)
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
                    // クレジットカード決済に関する処理の場合
                    case 'cc' :
                    case 'creditcancel' :
                    case 'creditchangeprice' :
                        switch($key){
                            // カード決済に関する情報のみ保持
                            case 'jp_kuroneko_webcollect_order_no':
                            case 'jp_kuroneko_webcollect_crd_c_res_cd':
                            case 'jp_kuroneko_webcollect_return_date':
                            case 'jp_kuroneko_webcollect_renewal_date':
                            case 'jp_kuroneko_webcollect_cancel_date':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                                break;
                        }
                        break;

                    // コンビニ（オンライン）払い - セブンイレブン に関する処理の場合
                    case 'cvs1':
                        switch($key){
                            // コンビニ払いに関する情報のみ保持
                            case 'jp_kuroneko_webcollect_cvs_name':
                            case 'jp_kuroneko_webcollect_cvs_billing_no':
                            case 'jp_kuroneko_webcollect_cvs_billing_url':
                            case 'jp_kuroneko_webcollect_cvs_expired_date':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                        }
                        break;

                    // コンビニ（オンライン）払い - ファミリーマート に関する処理の場合
                    case 'cvs2':
                        switch($key){
                            // コンビニ払いに関する情報のみ保持
                            case 'jp_kuroneko_webcollect_cvs_name':
                            case 'jp_kuroneko_webcollect_cvs_company_code':
                            case 'jp_kuroneko_webcollect_cvs_order_no_f':
                            case 'jp_kuroneko_webcollect_cvs_expired_date':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                        }
                        break;

                    // コンビニ（オンライン）払い - ローソン、サークルKサンクス、ミニストップ、セイコーマート に関する処理の場合
                    case 'cvs3':
                        switch($key){
                            // コンビニ払いに関する情報のみ保持
                            case 'jp_kuroneko_webcollect_cvs_name':
                            case 'jp_kuroneko_webcollect_cvs_econ_no':
                            case 'jp_kuroneko_webcollect_cvs_expired_date':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                        }
                        break;

                    // コンビニ入金通知
                    case 'cvs_settle':
                        switch($key){
                            // コンビニ入金通知に関する情報のみ保持
                            case 'jp_kuroneko_webcollect_cvs_name':
                            case 'jp_kuroneko_webcollect_settle_date':
                            case 'jp_kuroneko_webcollect_settle_price':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
                        }
                        break;

                    // クロネコ代金後払いサービス（決済依頼）
                    case 'ab':
                        switch($key){
                            // クロネコ代金後払いサービスに関する情報のみ保持
                            case 'jp_kuroneko_webcollect_ab_order_no':
                                // do nothing
                                break;

                            // 一時的に保存されたカード番号などの情報はすべて削除
                            default:
                                unset($info[$key]);
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
            case 'cc':
            case 'creditcancel':
            case 'creditchangeprice':

                // 受付番号
                if( !empty($results['order_no']) ){
                    $info['jp_kuroneko_webcollect_order_no'] = $results['order_no'];
                }

                // 与信承認番号
                if( !empty($results['crdCResCd']) ){
                    $info['jp_kuroneko_webcollect_crd_c_res_cd'] = $results['crdCResCd'];
                }

                // 処理日時
                if( !empty($results['returnDate']) ){
                    $info['jp_kuroneko_webcollect_return_date'] = fn_krnkwc_format_date($results['returnDate']);
                }

                // 最終更新日時
                if( !empty($results['renewalDate']) ){
                    $info['jp_kuroneko_webcollect_renewal_date'] = fn_krnkwc_format_date($results['renewalDate']);
                }

                // 取消日時
                if( !empty($results['cancelDate']) ){
                    $info['jp_kuroneko_webcollect_cancel_date'] = fn_krnkwc_format_date($results['cancelDate']);
                }

                break;

            // コンビニ（オンライン）払い - セブンイレブン
            case 'cvs1':

                // 支払先コンビ二名
                if( !empty($results['cvs_name']) ){
                    $info['jp_kuroneko_webcollect_cvs_name'] = $results['cvs_name'];
                }

                // 払込票番号
                if( !empty($results['billingNo']) ){
                    $info['jp_kuroneko_webcollect_cvs_billing_no'] = $results['billingNo'];
                }

                // 払込票URL
                if( !empty($results['billingUrl']) ){
                    $info['jp_kuroneko_webcollect_cvs_billing_url'] = $results['billingUrl'];
                }

                // 支払期限日
                if( !empty($results['expiredDate']) ){
                    $info['jp_kuroneko_webcollect_cvs_expired_date'] = fn_krnkwc_format_date($results['expiredDate'], true);
                }

                break;

            // コンビニ（オンライン）払い - ファミリーマート
            case 'cvs2':

                // 支払先コンビ二名
                if( !empty($results['cvs_name']) ){
                    $info['jp_kuroneko_webcollect_cvs_name'] = $results['cvs_name'];
                }

                // 企業コード
                if( !empty($results['companyCode']) ){
                    $info['jp_kuroneko_webcollect_cvs_company_code'] = $results['companyCode'];
                }

                // 注文番号
                if( !empty($results['orderNoF']) ){
                    $info['jp_kuroneko_webcollect_cvs_order_no_f'] = $results['orderNoF'];
                }

                // 支払期限日
                if( !empty($results['expiredDate']) ){
                    $info['jp_kuroneko_webcollect_cvs_expired_date'] = fn_krnkwc_format_date($results['expiredDate'], true);
                }

                break;

            // コンビニ（オンライン）払い - ローソン、サークルKサンクス、ミニストップ、セイコーマート
            case 'cvs3':

                // 支払先コンビ二名
                if( !empty($results['cvs_name']) ){
                    $info['jp_kuroneko_webcollect_cvs_name'] = $results['cvs_name'];
                }

                // 受付番号
                if( !empty($results['econNo']) ){
                    $info['jp_kuroneko_webcollect_cvs_econ_no'] = $results['econNo'];
                }

                // 支払期限日
                if( !empty($results['expiredDate']) ){
                    $info['jp_kuroneko_webcollect_cvs_expired_date'] = fn_krnkwc_format_date($results['expiredDate'], true);
                }

                break;

            // コンビニ入金通知
            case 'cvs_settle':

                // 支払先コンビ二名
                if( !empty($results['settle_method']) ){
                    list($cvs_type, $cvs_name) = fn_krnkwc_get_cvs_info($results['settle_method']);
                    $info['jp_kuroneko_webcollect_cvs_name'] = $cvs_name;
                }

                // 入金日時
                if( !empty($results['settle_date']) ){
                    $info['jp_kuroneko_webcollect_settle_date'] = fn_krnkwc_format_date($results['settle_date']) . ' (' . $results['notification_type'] .   ')';
                }

                // 入金金額
                if( !empty($results['settle_price']) ){
                    $info['jp_kuroneko_webcollect_settle_price'] = fn_format_price($results['settle_price']);
                }

                break;

            // クロネコ代金後払いサービス（決済依頼）
            case 'ab':
                // 受注番号
                if( !empty($results['orderNo']) ){
                    $info['jp_kuroneko_webcollect_ab_order_no'] = $results['orderNo'];
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
 * 注文IDから取引状況を取得
 *
 * @param $order_id
 * @return array|bool
 */
function fn_krnkwc_get_order_status_by_order_id($order_id)
{
    // 取引状況を取得
    $krnkwc_order_status = db_get_field("SELECT status_code FROM ?:jp_krnkwc_cc_status WHERE order_id = ?i", $order_id);

    if( !empty($krnkwc_order_status) ){
        return $krnkwc_order_status;
    }else{
        return false;
    }
}




/**
 * クロネコwebコレクトに各種データを送信
 *
 * @param $type
 * @param $params
 * @mode $mode
 * @processor_script_name $processor_script_name
 * @return mixed|string
 */
function fn_krnkwc_send_request($type, $params, $mode, $processor_script_name = null)
{
    // データ送信先URLと結果パラメータを初期化
    $target_url = '';
    $result = '';

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2017 BOF
    // トークン決済に対応
    ///////////////////////////////////////////////
    $token_flag= false;

    if($processor_script_name == 'krnkwc_cctkn.php' || $processor_script_name == 'krnkwc_ccrtn.php'){
        $token_flag = true;
    }

    switch($type){
        // クレジット決済登録(1)
        case 'credit':
            if($mode == 'test'){
                if($token_flag){
                    $target_url = KRNKWC_TEST_URL_CREDITTOKEN;
                }
                else {
                    $target_url = KRNKWC_TEST_URL_CREDIT;
                }
            }else{
                if($token_flag){
                    $target_url = KRNKWC_LIVE_URL_CREDITTOKEN;
                }
                else {
                    $target_url = KRNKWC_LIVE_URL_CREDIT;
                }
            }
            break;

        // クレジット決済登録(2)
        case 'credit3d':
            if($mode == 'test'){
                if($token_flag){
                    $target_url = KRNKWC_TEST_URL_CREDITTOKEN3D;
                }
                else {
                    $target_url = KRNKWC_TEST_URL_CREDIT3D;
                }
            }else{
                if($token_flag){
                    $target_url = KRNKWC_LIVE_URL_CREDITTOKEN3D;
                }
                else {
                    $target_url = KRNKWC_LIVE_URL_CREDIT3D;
                }
            }
            break;
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2017 EOF
        ///////////////////////////////////////////////

        // お預かり情報照会
        case 'creditinfoget':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CREDITINFOGET;
            }else{
                $target_url = KRNKWC_LIVE_URL_CREDITINFOGET;
            }
            break;

        // お預かり情報変更
        case 'creditinfoupdate':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CREDITINFOUPDATE;
            }else{
                $target_url = KRNKWC_LIVE_URL_CREDITINFOUPDATE;
            }
            break;

        // お預かり情報削除
        case 'creditinfodelete':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CREDITINFODELETE;
            }else{
                $target_url = KRNKWC_LIVE_URL_CREDITINFODELETE;
            }
            break;

        // クレジット決済取消
        case 'creditcancel':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CREDITCANCEL;
            }else{
                $target_url = KRNKWC_LIVE_URL_CREDITCANCEL;
            }
            break;

        // クレジット金額変更
        case 'creditchangeprice':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CREDITCHANGEPRICE;
            }else{
                $target_url = KRNKWC_LIVE_URL_CREDITCHANGEPRICE;
            }
            break;

        // コンビニ決済登録（セブン-イレブン）
        case 'cvs1':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CVS1;
            }else{
                $target_url = KRNKWC_LIVE_URL_CVS1;
            }
            break;

        // コンビニ決済登録（ファミリーマート）
        case 'cvs2':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CVS2;
            }else{
                $target_url = KRNKWC_LIVE_URL_CVS2;
            }
            break;

        // コンビニ決済登録（ローソン、サークルKサンクス、ミニストップ、セイコーマート）
        case 'cvs3':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CVS3;
            }else{
                $target_url = KRNKWC_LIVE_URL_CVS3;
            }
            break;

        // 電子マネー（楽天Edy・PC）
        case 'emoney1':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_EMONEY1;
            }else{
                $target_url = KRNKWC_LIVE_URL_EMONEY1;
            }
            break;

        // 電子マネー（楽天Edy・携帯）
        case 'emoney2':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_EMONEY2;
            }else{
                $target_url = KRNKWC_LIVE_URL_EMONEY2;
            }
            break;

        // 電子マネー（Suica・PC）
        case 'emoney3':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_EMONEY3;
            }else{
                $target_url = KRNKWC_LIVE_URL_EMONEY3;
            }
            break;

        // 電子マネー（Suica・携帯）
        case 'emoney4':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_EMONEY4;
            }else{
                $target_url = KRNKWC_LIVE_URL_EMONEY4;
            }
            break;

        // 電子マネー（WAON・PC）用URL
        case 'emoney5':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_EMONEY5;
            }else{
                $target_url = KRNKWC_LIVE_URL_EMONEY5;
            }
            break;

        // 電子マネー（WAON・携帯）
        case 'emoney6':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_EMONEY6;
            }else{
                $target_url = KRNKWC_LIVE_URL_EMONEY6;
            }
            break;

        // ネットバンク決済登録
        case 'bank1':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_BANK1;
            }else{
                $target_url = KRNKWC_LIVE_URL_BANK1;
            }
            break;

        // 出荷情報登録
        case 'shipmententry':
            // クロネコ代金後払いサービスの場合
            if( !empty($params['ycfStrCode'])){
                if($mode == 'test'){
                    $target_url = KRNKWC_TEST_URL_KAASL0010;
                }else{
                    $target_url = KRNKWC_LIVE_URL_KAASL0010;
                }
                // その他の場合
            }else{
                if($mode == 'test'){
                    $target_url = KRNKWC_TEST_URL_SHIPMENTENTRY;
                }else{
                    $target_url = KRNKWC_LIVE_URL_SHIPMENTENTRY;
                }
            }
            break;

        // 出荷情報取消
        case 'shipmentcancel':
            // クロネコ代金後払いサービスの場合
            if( !empty($params['ycfStrCode'])){
                if($mode == 'test'){
                    $target_url = KRNKWC_TEST_URL_KAASL0010;
                }else{
                    $target_url = KRNKWC_LIVE_URL_KAASL0010;
                }
            // その他の場合
            }else{
                if($mode == 'test'){
                    $target_url = KRNKWC_TEST_URL_SHIPMENTCANCEL;
                }else{
                    $target_url = KRNKWC_LIVE_URL_SHIPMENTCANCEL;
                }
            }
            break;

        // 出荷予定日変更
        case 'changedate':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_CHANGEDATE;
            }else{
                $target_url = KRNKWC_LIVE_URL_CHANGEDATE;
            }
            break;

        // 取引情報照会
        case 'tradeinfo':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_TRADEINFO;
            }else{
                $target_url = KRNKWC_LIVE_URL_TRADEINFO;
            }
            break;

        // 継続課金登録(1)
        case 'regular':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST__URL_REGULAR;
            }else{
                $target_url = KRNKWC_LIVE__URL_REGULAR;
            }
            break;

        // 継続課金登録(2)
        case 'regular3d':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_REGULAR3D;
            }else{
                $target_url = KRNKWC_LIVE_URL_REGULAR3D;
            }
            break;

        // 継続課金照会
        case 'regularinfo':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_REGULARINFO;
            }else{
                $target_url = KRNKWC_LIVE_URL_REGULARINFO;
            }
            break;

        // 継続課金更新
        case 'regularupdate':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_REGULARUPDATE;
            }else{
                $target_url = KRNKWC_LIVE_URL_REGULARUPDATE;
            }
            break;

        // 継続課金削除
        case 'regulardelete':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_REGULARDELETE;
            }else{
                $target_url = KRNKWC_LIVE_URL_REGULARDELETE;
            }
            break;

        // クロネコ代金後払いサービス（決済依頼）
        case 'KAARA0010':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_KAARA0010;
            }else{
                $target_url = KRNKWC_LIVE_URL_KAARA0010;
            }
            break;

        // クロネコ代金後払いサービス（取引照会）
        case 'KAAST0010':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_KAAST0010;
            }else{
                $target_url = KRNKWC_LIVE_URL_KAAST0010;
            }
            break;

        // クロネコ代金後払いサービス（決済取消依頼）
        case 'KAACL0010':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_KAACL0010;
            }else{
                $target_url = KRNKWC_LIVE_URL_KAACL0010;
            }
            break;

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // 後払い請求金額変更対応
        ///////////////////////////////////////////////
        // クロネコ代金後払いサービス（請求金額変更（減額））
        case 'abchangeprice':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_KAAKK0010;
            }else{
                $target_url = KRNKWC_LIVE_URL_KAAKK0010;
            }
            break;
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2021 BOF
        // 後払い請求金額変更対応
        ///////////////////////////////////////////////
        // クロネコ代金後払いサービス（マホタイプ(請求書SMS送付））
        case 'absms':
            if($mode == 'test'){
                $target_url = KRNKWC_TEST_URL_KAASA0020;
            }else{
                $target_url = KRNKWC_LIVE_URL_KAASA0020;
            }
            break;
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////
        // その他
        default:
            // do nothing
    }
    // 送信先URLが指定されている場合
    if( !empty($target_url) ){
        $result = Http::post($target_url, $params);

        // 受信した結果情報を配列に格納
        if( !empty($result) ){
            $result = fn_krnkwc_get_result_array($result);
        }
    }

    return $result;
}




/**
 * クロネコwebコレクトからの戻り値を配列化
 *
 * @param $result
 * @return array
 */
function fn_krnkwc_get_result_array($result)
{
    $result = get_object_vars(@simplexml_load_string($result));
    return $result;
}




/**
 * 日時データを読みやすくフォーマット
 *
 * @param $returndate
 * @param bool|false $is_simple
 * @return string
 */
function fn_krnkwc_format_date($returndate, $is_simple = false)
{
    // YYYYMMDDのみの場合
    if($is_simple){
        list($year, $month, $date) = sscanf($returndate, "%04d%02d%02d");
        return sprintf('%04d', $year) . '/' . sprintf('%02d', $month) . '/' . sprintf('%02d', $date);
    // YYYYMMDDHHMMSSの場合
    }else{
        list($year, $month, $date, $hour, $minute, $second) = sscanf($returndate, "%04d%02d%02d%02d%02d%02d");
        return sprintf('%04d', $year) . '/' . sprintf('%02d', $month) . '/' . sprintf('%02d', $date) . ' ' . sprintf('%02d', $hour) . ':' . sprintf('%02d', $minute) . ':' . sprintf('%02d', $second);
    }
}




/**
 * クロネコwebコレクトのお支払い受付番号からCS-Cartの注文IDを取得
 *
 * @param $krnkwc_order_id
 * @return int|string
 */
function fn_krnkwc_get_order_id($order_no)
{
	// お支払い受付番号の末尾10桁（UNIXタイムスタンプ）を削除
	$order_id = substr( $order_no , 0 , strlen($order_no) - 10 );
	// オーダーIDを整数化（プレースホルダとして付与された 0 を削除）
    $order_id = (int)$order_id;

	return $order_id;
}




/**
 * エラーメッセージを表示
 *
 * @param $result
 * @return bool
 */
function fn_krnkwc_set_err_msg($result, $title ='', $is_ab=false)
{
    // エラーコードが存在しない場合、処理を終了
    if( empty($result['errorCode']) ) return false;

    // エラーコードが「加盟店の与信処理不可」の場合
    if( $result['errorCode'] == 'A012060001' ){
        $error_code = $result['errorCode'];
        $credit_error_code = $result['creditErrorCode'];
    }else{
        $error_code = $result['errorCode'];
        $credit_error_code = '';
    }

    // エラーメッセージのタイトルが指定されていない場合は既定のタイトルをセット
    if( empty($title) ) $title = __('jp_kuroneko_webcollect_cc_error');

    fn_set_notification('E', $title, fn_krnkwc_get_err_msg($error_code, $credit_error_code, $is_ab) . ' (' . $error_code . ')');
}




/**
 * エラーメッセージを取得
 *
 * @param $err_detail_code
 * @return string
 */
function fn_krnkwc_get_err_msg($error_code, $credit_error_code, $is_ab=false)
{
    // エラーコードを大文字に変換
    $error_code = strtoupper($error_code);

    // エラーコードが「加盟店の与信処理不可」の場合
    if( $error_code == 'A012060001' && !empty($credit_error_code) ){
        $err_msg = __('jp_kuroneko_webcollect_cc_errmsg_' . strtoupper($credit_error_code));

        // エラーコードに対応する言語変数が存在しない場合、汎用メッセージをセット
        if( strpos($err_msg, 'jp_kuroneko_webcollect_cc_errmsg_') === 0 || strpos($err_msg, 'jp_kuroneko_webcollect_cc_errmsg_') > 0) {
            if(!$is_ab) {
                $err_msg = __('jp_kuroneko_webcollect_cc_failed');
            }
            else{
                $err_msg = __('jp_kuroneko_webcollect__ab') . __('error');
            }
        }
    }else{
        $err_msg = __('jp_kuroneko_webcollect_errmsg_' . strtoupper($error_code));

        // エラーコードに対応する言語変数が存在しない場合、汎用メッセージをセット
        if( strpos($err_msg, 'jp_kuroneko_webcollect_errmsg_') === 0 || strpos($err_msg, 'jp_kuroneko_webcollect_errmsg_') > 0) {
            if(!$is_ab) {
                $err_msg = __('jp_kuroneko_webcollect_cc_failed');
            }
            else{
                $err_msg = __('jp_kuroneko_webcollect__ab') . __('error');
            }
        }
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
function fn_krnkwc_validate_notification($data_received)
{
    $is_valid = true;

    // 受信した加盟店コードとCS-Cartに登録された加盟店コードが一致しない場合はエラー
    if( $data_received['trader_code'] != Registry::get('addons.kuroneko_webcollect.trader_code') ){
        $is_valid = false;

    // 決済結果に異常値がセットされている場合はエラー
    }elseif( $data_received['settle_result'] == 2 ){
        $is_valid = false;
    }

    return $is_valid;
}




/**
 * クロネコwebコレクト、またはクロネコ代金後払いサービス側に送信する商品名をフォーマット
 * 購入商品が複数存在する場合は1つの商品のみ記載
 * 【メモ】
 * 商品名の最大長はバイト数ではなく文字数で定義されている
 * クロネコwebコレクトの場合は200文字
 * クロネコ代金後払いサービスの場合は30文字
 *
 * @param $order_info
 * @param string $type
 * @return string
 */
function fn_krnkwc_format_product_name($order_info, $type = 'webcollect')
{
    $krnkwc_product_name = '';
    $krnkwc_etc = __('jp_kuroneko_webcollect_etc');

    // クロネコwebコレクトの場合
    if($type == 'webcollect'){
        $product_maxlen = KRNKWC_MAXLEN_PRODUCT;
    // クロネコ代金後払いサービスの場合
    }else{
        $product_maxlen = KRNKAB_MAXLEN_PRODUCT;
    }

    // 商品データが存在する場合
    if (!empty($order_info['products'])) {

        $product_info = reset(array_slice($order_info['products'], 0, 1));

        // 商品名
        // 全半角英数、日本語、ハイフン、スペース、ピリオド、アンダーバー以外の文字は削除（全角変換できない可能性があるため）
        $_product_name = preg_replace("/[^ぁ-んァ-ンーa-zA-Z0-9一-龠０-９\-\s\.\_]+/u", '', $product_info['product']);
        $_product_name = mb_convert_kana($_product_name, 'RNASKV', 'UTF-8');

        // 全角マイナスは半角マイナスに変換
        if($type == 'webcollect'){
            $_product_name = str_replace('－', '-', $_product_name);
        }

        // 商品数が１つの場合
        if( count($order_info['products']) == 1 ){
            // 商品名を指定した長さのみ取得
            $krnkwc_product_name = mb_substr($_product_name, 0, $product_maxlen, 'UTF-8');

        // 商品数が２つ以上の場合は最初の商品名に「など」を追加する
        }elseif( count($order_info['products']) > 1 ){
            // 追記用文字の文字数を取得
            $krnkwc_etc_length = mb_strlen($krnkwc_etc);
            $krnkwc_product_name_length = $product_maxlen - $krnkwc_etc_length;

            // 配列の最初の商品名に「など」を追記した文字列を取得
            $krnkwc_product_name = mb_substr($_product_name, 0, $krnkwc_product_name_length, 'UTF-8') . $krnkwc_etc;
        }

    // 商品データがない場合は一律「お買い上げ商品」とする（通常発生しないが、念のため）
    }else{
        $krnkwc_product_name = mb_substr(__('jp_kuroneko_webcollect_item_name'), 0, $product_maxlen, 'UTF-8');
    }

    return $krnkwc_product_name;
}




/**
 * ヤマトフィナンシャル系の決済方法を利用した注文であるかを判定
 *
 * @param $order
 * @return bool
 *
 */
function fn_krnkwc_is_pay_by_kuroneko($order)
{
    // 支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkwc_get_processor_data_by_order_id($order['order_id']);

    // 決済代行業者を使った決済の場合
    if( !empty($processor_data['processor_script']) ){

        // ヤマトフィナンシャル系の決済方法の場合は true を返す
        switch( $processor_data['processor_script'] ){
            case 'krnkwc_cc.php':
            case 'krnkwc_ccreg.php':
            case 'krnkwc_cvs.php':
            case 'krnkwc_cctkn.php':
            case 'krnkwc_ccrtn.php':
            case 'krnkab.php':
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
 * @param $silent_mode 出荷情報登録仕様変更対応（他社配送）
 * @return bool
 */
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2019 BOF
// 出荷情報登録仕様変更対応（他社配送）
///////////////////////////////////////////////
function fn_krnkwc_add_shipment(&$shipment_data, $order_info, $silent_mode = true)
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2019 EOF
///////////////////////////////////////////////
{
    // 送り状番号が入力されていない場合、送り状データの送信を指定していない場合処理を終了
    if( empty($shipment_data['tracking_number']) || $shipment_data['send_slip_no'] != 'Y' ) return false;

    // 送り情報番号を取得
    $slip_no = preg_replace("/-/", "", mb_convert_kana($shipment_data['tracking_number'],"a"));

    // 当該注文の支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_info['order_id']);
    $mode = $processor_data['processor_params']['mode'];

    // 受付番号を取得
    $order_no = fn_krnkwc_get_order_no($order_info['order_id']);

    // 受付番号が存在しない場合は処理を終了
    if( empty($order_no) ) return false;

    // 出荷情報登録判定フラグ
    $is_registered = true;

    ///////////////////////////////////////////////////
    // 出荷情報登録に必要なパラメータを取得 BOF
    ///////////////////////////////////////////////////
    $params = array();

    // クロネコ代金後払いサービスの場合
    if( $processor_data['processor_script'] == 'krnkab.php'){
        // 加盟店コード
        $params['ycfStrCode'] = Registry::get('addons.kuroneko_webcollect.ycf_str_code');

        // 受注番号
        $params['orderNo'] = $order_no;

        // 送り状番号
        $params['paymentNo'] = $slip_no;

        // 処理区分（CS-Cartには出荷情報の変更という概念がないので、0（新規登録固定）
        $params['processDiv'] = 0;
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
        ///////////////////////////////////////////////
        if($shipment_data['delivery_service'] == '99'){
            $params['processDiv'] = 2;
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////

        // 依頼日時
        $params['requestDate'] = date('YmdHis');

        // パスワード
        $params['password'] = Registry::get('addons.kuroneko_webcollect.atobarai_password');

    // その他の場合
    }else{
        // 機能区分
        $params['function_div'] = 'E01';

        // 加盟店コード
        $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

        // 受付番号
        $params['order_no'] = $order_no;

        // 送り状番号
        $params['slip_no'] = $slip_no;

        // 予備1（加盟店テスト環境返却用エラーコード）
        // $params['reserve_1'] = 'A012060001';

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // 出荷情報登録仕様変更対応（他社配送）
        ///////////////////////////////////////////////
        // クレジットカード決済の場合
        if( $processor_data['processor_script'] == 'krnkwc_cctkn.php' || $processor_data['processor_script'] == 'krnkwc_ccreg.php' || $processor_data['processor_script'] == 'krnkwc_ccrtn.php') {
            // 配送サービスコード
            $params['delivery_service_code'] = $shipment_data['delivery_service'];
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////
    }
    ///////////////////////////////////////////////////
    // 出荷情報登録に必要なパラメータを取得 EOF
    ///////////////////////////////////////////////////

    // 出荷情報登録
    $result = fn_krnkwc_send_request('shipmententry', $params, $mode);

    // 出荷情報登録に関するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 出荷情報登録が正常に終了している場合
        if ( $return_code == 0 ) {
            // 荷物お問い合わせURLが戻された場合
            if(!empty($result['slipUrlPc'])){
                $shipment_data['tracking_url'] = $result['slipUrlPc'];
            }

            // クロネコ代金後払いサービスの場合
            if( $processor_data['processor_script'] == 'krnkab.php' ){
                // DB上の取引情報を更新
                fn_krnkwc_get_ab_status($order_info['order_id']);
            }

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // 出荷情報登録仕様変更対応（他社配送）
            ///////////////////////////////////////////////
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 BOF
            // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
            ///////////////////////////////////////////////
            // クレジットカード決済、後払いの場合
            if( $processor_data['processor_script'] == 'krnkwc_cctkn.php' || $processor_data['processor_script'] == 'krnkwc_ccreg.php' || $processor_data['processor_script'] == 'krnkwc_ccrtn.php' || $processor_data['processor_script'] == 'krnkab.php' ) {
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 EOF
            ///////////////////////////////////////////////
                // ヤマト配送、他社配送の情報を登録
                db_query("INSERT INTO ?:jp_krnkwc_all_shipments VALUES(?i, ?s, ?s, ?s, ?i)", $shipment_data['order_id'], $shipment_data['tracking_number'], $shipment_data['carrier'], $shipment_data['delivery_service'], TIME);

                // 支払サービス名を取得
                $service_name = __("jp_kuroneko_webcollect_service_name_wc");
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2020 BOF
                // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
                ///////////////////////////////////////////////
                if($processor_data['processor_script'] == 'krnkab.php'){
                    $service_name = __("jp_kuroneko_webcollect__ab");
                }
                ///////////////////////////////////////////////
                // Modified by takahashi from cs-cart.jp 2020 EOF
                ///////////////////////////////////////////////

                // 出荷情報登録完了メッセージを表示
                if( !$silent_mode ) fn_set_notification('N', $service_name, __('jp_kuroneko_webcollect_shipment_add_success', array('[slipno]' => $slip_no)));
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 EOF
            ///////////////////////////////////////////////

        // 出荷情報登録でエラーが発生した場合
        }else{
            if($processor_data['processor_script'] == 'krnkab.php'){
                $is_ab = true;
            }
            fn_krnkwc_set_err_msg($result, __('jp_kuroneko_webcollect_shipment_entry_error'), $is_ab);
            $is_registered = false;
        }

    // 出荷情報登録に関するリクエスト送信が異常終了した場合
    }else{
        // 出荷情報登録失敗メッセージを表示
        fn_set_notification('N', __('jp_kuroneko_webcollect_shipment_entry_error'), __('jp_kuroneko_webcollect_shipment_entry_error_msg'));
        $is_registered = false;
    }

    return $is_registered;
}






/**
 * 出荷情報取消
 *
 * @param $shipment_id
 * @return bool
 */
function fn_krnkwc_delete_shipment($shipment_id, $slip_no = null, $silent_mode = false)
{
    // 配送情報IDから注文IDを取得
    $order_id = db_get_field("SELECT order_id FROM ?:shipment_items WHERE shipment_id = ?i", $shipment_id);

    // 注文IDが存在する場合
    if( !empty($order_id) ){
        // 支払方法に紐付けられた決済代行サービスの情報を取得
        $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);

        // 当該注文で使用した決済代行サービスに関する情報が存在する場合
        if( !empty($processor_data) ){

            // 送り状番号が指定されていない場合
            if( is_null($slip_no) ){
                // DBから送り状番号を取得
                $slip_no = db_get_field("SELECT tracking_number FROM ?:shipments WHERE shipment_id = ?i", $shipment_id);
            }
            // 送り状番号からハイフンを除去
            $slip_no = preg_replace("/-/", "", $slip_no);

            switch ($processor_data['processor_script']) {
                // クロネコwebコレクトの場合
                case 'krnkwc_cc.php':
                case 'krnkwc_ccreg.php':
                case 'krnkwc_cvs.php':
                case 'krnkwc_cctkn.php':
                case 'krnkwc_ccrtn.php':
                    // 接続先環境（テスト/本番）を取得
                    $mode = $processor_data['processor_params']['mode'];

                    // 支払サービス名を取得
                    $service_name = __("jp_kuroneko_webcollect_service_name_wc");

                    ///////////////////////////////////////////////////
                    // 出荷情報取消に必要なパラメータを取得 BOF
                    ///////////////////////////////////////////////////
                    // 機能区分
                    $params['function_div'] = 'E02';

                    // 加盟店コード
                    $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

                    // 受付番号
                    $params['order_no'] = fn_krnkwc_get_order_no($order_id);

                    // 送り状番号
                    $params['slip_no'] = $slip_no;

                    // 予備1（加盟店テスト環境返却用エラーコード）
                    // $params['reserve_1'] = 'A012060001';
                    ///////////////////////////////////////////////////
                    // 出荷情報取消に必要なパラメータを取得 EOF
                    ///////////////////////////////////////////////////
                    break;

                // クロネコ代金後払いサービス
                case 'krnkab.php':
                    // 接続先環境（テスト/本番）を取得
                    $mode = $processor_data['processor_params']['mode'];

                    // 支払サービス名を取得
                    $service_name = __("jp_kuroneko_webcollect_service_name_ab");

                    ///////////////////////////////////////////////////
                    // 出荷情報取消に必要なパラメータを取得 BOF
                    ///////////////////////////////////////////////////
                    // 加盟店コード
                    $params['ycfStrCode'] = Registry::get('addons.kuroneko_webcollect.ycf_str_code');

                    // 受注番号
                    $params['orderNo'] = fn_krnkwc_get_order_no($order_id);

                    // 処理区分（削除）
                    $params['processDiv'] = 9;

                    // 依頼日時
                    $params['requestDate'] = date('YmdHis');

                    // パスワード
                    $params['password'] = Registry::get('addons.kuroneko_webcollect.atobarai_password');
                    ///////////////////////////////////////////////////
                    // 出荷情報取消に必要なパラメータを取得 EOF
                    ///////////////////////////////////////////////////
                    break;

                default:
                    return false;
            }

            // 出荷情報取消
            $result = fn_krnkwc_send_request('shipmentcancel', $params, $mode);

            // 出荷情報取消に関するリクエスト送信が正常終了した場合
            if (!empty($result)) {

                // 結果コード
                $return_code = $result['returnCode'];

                // 出荷情報取消が正常に終了している場合
                if ($return_code == 0) {
                    // 出荷情報取消完了メッセージを表示
                    if( !$silent_mode ) fn_set_notification('N', $service_name, __('jp_kuroneko_webcollect_shipment_delete_success', array('[slipno]' => $slip_no)));

                    // クロネコ代金後払いサービスの場合
                    if( $processor_data['processor_script'] == 'krnkab.php'){
                        // DB上の取引情報を更新
                        fn_krnkwc_get_ab_status($order_id);
                    }

                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2019 BOF
                    // 出荷情報登録仕様変更対応（他社配送）
                    ///////////////////////////////////////////////
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2020 BOF
                    // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
                    ///////////////////////////////////////////////
                    // クレジットカード決済、後払いの場合
                    if( $processor_data['processor_script'] == 'krnkwc_cctkn.php' || $processor_data['processor_script'] == 'krnkwc_ccreg.php' || $processor_data['processor_script'] == 'krnkwc_ccrtn.php' || $processor_data['processor_script'] == 'krnkab.php' ) {
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2020 EOF
                    ///////////////////////////////////////////////
                        // ヤマト配送、他社配送の情報を削除
                        db_query("DELETE FROM ?:jp_krnkwc_all_shipments WHERE order_id = ?i", $order_id);
                    }
                    ///////////////////////////////////////////////
                    // Modified by takahashi from cs-cart.jp 2019 EOF
                    ///////////////////////////////////////////////

                // 出荷情報取消が異常終了した場合
                } else {
                    // エラーメッセージを表示
                    $error_code = $result['errorCode'];
                    if( !$silent_mode ) fn_set_notification('E', $service_name, __('jp_kuroneko_webcollect_shipment_delete_failed', array('[slipno]' => $slip_no)) . ' (' . $error_code . ')');
                }

            // 出荷情報取消に関するリクエスト送信が異常終了した場合
            } else {
                // 削除失敗メッセージを表示
                if( !$silent_mode ) fn_set_notification('N', __('jp_kuroneko_webcollect_ccreg_delete_error'), __('jp_kuroneko_webcollect_ccreg_delete_failed'));
            }
        }
    }

    return false;
}




/**
 * 各決済代行サービスで使用するスクリプトファイル名から決済代行サービスID（processor_id)を取得
 *
 * @param $processor_scripts
 * @return array
 */
function fn_krnkwc_get_processor_ids($processor_scripts)
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
 * 注文IDから支払方法IDに紐付けられた決済代行サービスの情報を取得
 *
 * @param $order_id
 * @return bool
 */
function fn_krnkwc_get_processor_data_by_order_id($order_id)
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
 * 注文IDから受付番号/受注番号を取得
 *
 * @param $order_id
 * @return array|bool
 */
function fn_krnkwc_get_order_no($order_id)
{
    // 受注番号を取得
    $order_no = db_get_field("SELECT order_no FROM ?:jp_krnkwc_cc_status WHERE order_id = ?i", $order_id);

    // 受注番号が存在しない場合は処理を終了
    if( !empty($order_no) ){
        return $order_no;
    }else{
        return false;
    }
}




/**
 * クロネコwebコレクトの取引状況名を取得
 *
 * @param $status_code
 * @return string
 */
function fn_krnkwc_wc_get_status_info_name($status_code)
{
    return __('jp_kuroneko_webcollect_wc_order_status' . (int)$status_code);
}




/**
 * クロネコwebコレクトの取引状況を一括照会
 *
 */
function fn_krnkwc_get_mass_wc_status()
{
    // 照会対象外の取引状況を取得
    $excluded_statuses = fn_krnkwc_get_excluded_statuses();

    // 取引状況を照会する注文ID群を取得
    $o_ids = db_get_fields("SELECT order_id FROM ?:jp_krnkwc_cc_status WHERE (status_code LIKE 'CC_%' OR status_code LIKE 'CVS_%') AND status_code NOT IN (?a)", $excluded_statuses);

    // クロネコwebコレクトの取引状況を照会
    foreach($o_ids as $order_id){
        fn_krnkwc_get_trade_info($order_id);
    }
}




/**
 * 照会対象外の取引状況を取得
 *
 * @return array
 */
function fn_krnkwc_get_excluded_statuses()
{
    // 取引状況が「31 : 精算確定」の注文については照会対象から除外する（ステータスの変更がないため）
    $excluded_statuses = array('CC_31', 'CVS_31');

    return $excluded_statuses;
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
function fn_krnkwc_is_payment_registered($type)
{
    // 指定した決済方法に対応するスクリプトファイル名をセット
    switch($type){
        // クロネコwebコレクト（カード払いまたは登録済みカード払い）
        case 'cc':
            $processor_scripts = array('krnkwc_cc.php', 'krnkwc_ccreg.php', 'krnkwc_cctkn.php', 'krnkwc_ccrtn.php');
            break;

        // クロネコwebコレクト（コンビニ払い）
        case 'cvs':
            $processor_scripts = array('krnkwc_cvs.php');
            break;

        // クロネコ代金後払いサービス
        case 'ab':
            $processor_scripts = array('krnkab.php');
            break;

        // その他
        default:
            return false;
    }

    // 指定した決済方法を利用する支払方法を抽出
    $processor_ids = fn_krnkwc_get_processor_ids($processor_scripts);
    $krnk_payments = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);

    // 指定した決済方法を利用する支払方法が存在する場合、trueを返す
    if( !empty($krnk_payments) ){
        return true;
    }else{
        return false;
    }
}

/**
 * 注文IDから支払方法IDに紐付けられた決済代行サービスのスクリプトファイル名を取得
 *
 * @param $order_id
 * @return bool
 */
function fn_krnkwc_get_processor_script_name_by_order_id($order_id)
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
function fn_krnkwc_get_method_name($method_id)
{
    // 支払方法IDの指定がない場合は「不明」を返す
    if(empty($method_id)) return __('unknown');

    // 各支払方法IDに対応した支払方法名を返す
    switch($method_id){
        case 1:
            // 1回払い
            return __('jp_cc_onetime');
            break;
        case 2:
            // 分割払い
            return __('jp_payment_installment');
            break;
        case 0:
            // リボ払い
            return __('jp_cc_revo');
            break;
        default:
            // 不明
            return __('unknown');
    }
}




/**
 * 支払方法名から支払方法IDを取得
 *
 * @param $method_name
 * @return bool|int
 */
function fn_krnkwc_get_method_id_by_name($method_name)
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
        // リボ
        case __('jp_cc_revo'):
            return 0;
            break;
        // その他
        default:
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
function fn_krnkwc_update_cc_status_code($order_id, $status, $order_no = '')
{
    $_data = array (
        'order_id' => $order_id,
        'status_code' => $status,
    );

    // 当該注文に関する取引状況関連レコードの存在チェック
    $is_exists = db_get_row("SELECT * FROM ?:jp_krnkwc_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文に関する取引状況関連レコードが存在する場合
    if( !empty($is_exists) ){
        // 受付番号が存在する場合（注文情報の編集など）
        if( !empty($order_no) ){
            $_data['order_no'] = $order_no;
        }

        // レコードを更新
        db_query("UPDATE ?:jp_krnkwc_cc_status SET ?u WHERE order_id = ?i", $_data, $order_id);
    // 当該注文に関する取引状況関連レコードが存在しない場合
    }else{
        // 受付番号
        $_data['order_no'] = $order_no;

        // レコードを新規追加
        db_query("REPLACE INTO ?:jp_krnkwc_cc_status ?e", $_data);
    }
}
/////////////////////////////////////////////////////////////////////////////////////
// カード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// 登録済みカード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * クロネコwebコレクトに登録済みのカード情報を削除
 *
 * @param $user_id
 * @param $authentication_key
 * @param $card_key
 * @param $last_credit_date
 * @param string $mode
 */
function fn_krnkwc_delete_card_info($user_id, $authentication_key, $card_key, $last_credit_date, $mode = 'test')
{
    ///////////////////////////////////////////////////
    // 登録済みカード削除に必要なパラメータを取得 BOF
    ///////////////////////////////////////////////////
    $params = array();

    // 機能区分
    $params['function_div'] = 'A05';

    // 加盟店コード
    $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

    // カード保有者を特定するID
    $params['member_id'] = $user_id;

    // 認証キー
    $params['authentication_key'] = $authentication_key;

    // アクセスキー
    $krnkwc_access_key = Registry::get('addons.kuroneko_webcollect.access_key');

    // チェックサム
    $params['check_sum'] = fn_krnkwc_generate_checksum($params['member_id'], $params['authentication_key'], $krnkwc_access_key);

    // カード識別キー
    $params['card_key'] = $card_key;

    // 最終利用日時
    $params['last_credit_date'] = $last_credit_date;
    ///////////////////////////////////////////////////
    // 登録済みカード削除に必要なパラメータを取得 EOF
    ///////////////////////////////////////////////////

    // 登録済みカード削除
    $result = fn_krnkwc_send_request('creditinfodelete', $params, $mode);

    // 登録済みカード削除に関するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 登録済みカード削除が正常に終了している場合
        if ( $return_code == 00 ) {
            // クロネコwebコレクトから登録カードをすべて削除する場合
            if( $card_key == 1 ) {
                // 登録済みカード決済用レコードを削除
                db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'krnkwc_ccreg');

                // 削除成功メッセージを表示
                fn_set_notification('N', __('notice'), __('jp_kuroneko_webcollect_ccreg_delete_success'));
            }

        // 登録済みカード削除でエラーが発生した場合
        }else{
            fn_krnkwc_set_err_msg($result, __('jp_kuroneko_webcollect_ccreg_delete_error'));
        }

    // 登録済みカード削除に関するリクエスト送信が異常終了した場合
    }else{
        // 削除失敗メッセージを表示
        fn_set_notification('N', __('jp_kuroneko_webcollect_ccreg_delete_error'), __('jp_kuroneko_webcollect_ccreg_delete_failed'));
    }
}




/**
 * 注文に使用したカードに紐付けられた認証コードをDBに登録
 *
 * @param $order_info
 * @param $processor_data
 */
function fn_krnkwc_register_cc_info($order_info, $processor_data)
{
    $_data = array('user_id' => $order_info['user_id'],
        'payment_method' => 'krnkwc_ccreg',
        'quickpay_id' => $order_info['authentication_key'],
    );
    db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);
}




/**
 * クロネコwebコレクトに登録されたカード情報を取得
 * 第2引数によりカード情報の明示的な削除にも対応
 *
 * @param $user_id
 * @param bool|false $is_delete_by_user
 * @return array|bool
 */
function fn_krnkwc_get_registered_card_info($user_id, $is_delete_by_user = false)
{
    // ユーザーIDが指定されていない場合は処理を終了
    if( empty($user_id) ) return false;

    // 登録済みカード情報を格納する変数を初期化
    $registered_card = false;

    // 支払方法に関するデータを取得
    $payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script IN ('krnkwc_ccreg.php', 'krnkwc_ccrtn.php') AND ?:payments.status = 'A'");
    $processor_data = fn_get_processor_data($payment_id);

    // 認証キーを取得
    $authentication_key = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'krnkwc_ccreg');

    // 認証キーが存在する場合
    if(!empty($authentication_key)){
        ///////////////////////////////////////////////////
        // 登録済みカード照会に必要なパラメータを取得 BOF
        ///////////////////////////////////////////////////
        $params = array();

        // 機能区分
        $params['function_div'] = 'A03';

        // 加盟店コード
        $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

        // カード保有者を特定するID
        $params['member_id'] = $user_id;

        // 認証キー
        $params['authentication_key'] = $authentication_key;

        // アクセスキー
        $krnkwc_access_key = Registry::get('addons.kuroneko_webcollect.access_key');

        // チェックサム
        $params['check_sum'] = fn_krnkwc_generate_checksum($params['member_id'], $params['authentication_key'], $krnkwc_access_key);
        ///////////////////////////////////////////////////
        // 登録済みカード照会に必要なパラメータを取得 EOF
        ///////////////////////////////////////////////////

        // 登録済みカード照会
        $result = fn_krnkwc_send_request('creditinfoget', $params, $processor_data['processor_params']['mode']);

        // 結果コード
        $return_code = $result['returnCode'];

        // 登録済みカード照会に関するリクエスト送信が正常終了した場合
        if (!empty($result)) {
            // 登録済みカード照会が正常に終了している場合
            if ( $return_code == 00 ) {
                // 登録済みカード情報が存在する場合
                if( !empty($result['cardData']) ){
                    // 登録済みカードが複数登録されている場合
                    if( is_array($result['cardData']) ){
                        foreach($result['cardData'] as $key => $val){
                            $card_info = get_object_vars($val);

                            // 1枚目（最新）のカード番号を取得
                            if( $card_info['cardKey'] == 1 ){
                                $registered_card = $card_info;

                            // 2枚目以降の登録カードはすべて削除
                            // 【メモ】CS-Cartでは登録カード数は1枚のみ。
                            // すでに1枚登録された状態でカードを登録すると、クロネコwebコレクト側では一時的に2枚登録される。
                            // ただし、次回注文時に支払方法選択画面を表示したタイミングや、登録済みカードページを表示したタイミングで最新のカード以外は削除される
                            }else{
                                fn_krnkwc_delete_card_info($user_id, $authentication_key, $card_info['cardKey'], $card_info['lastCreditDate'], $processor_data['processor_params']['mode']);
                            }
                        }
                    // 登録済みカードが1枚だけ登録されている場合
                    }else{
                        $card_info = get_object_vars($result['cardData']);
                        $registered_card = $card_info;
                    }

                    // カード情報の明示的な削除の場合
                    if( $is_delete_by_user ){
                        // カード情報を削除
                        fn_krnkwc_delete_card_info($user_id, $authentication_key, $registered_card['cardKey'], $registered_card['lastCreditDate'], $processor_data['processor_params']['mode']);
                    }
                }

            // 登録済みカード照会に失敗した場合
            }else{
                // カード情報は表示しない
            }
        // 登録済みカード照会に関するリクエスト送信に失敗した場合
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
function fn_krnkwc_cc_is_changeable($order_id, $processor_data)
{
    // 子注文の存在有無をチェック
    $parent_order_info = db_get_row("SELECT is_parent_order, parent_order_id FROM ?:orders WHERE order_id = ?i", $order_id);

    // 親子関係を持つ注文ではない場合（マーケットプレイスやサプライヤー機能を使った注文を考慮）
    if( $parent_order_info['is_parent_order'] == 'N' && $parent_order_info['parent_order_id'] == 0 ) {

        // 編集前の注文で支払方法に紐付けられた決済代行サービスの情報を取得
        $org_processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);
        $org_processor_script = $org_processor_data['processor_script'];
        $changable_processor_scripts = array('krnkwc_cc.php', 'krnkwc_ccreg.php', 'krnkwc_cctkn.php', 'krnkwc_ccrtn.php');

        // 編集前後で共にクロネコwebコレクトのカード決済または登録済みカード決済が選択されている場合
        if( in_array($org_processor_script, $changable_processor_scripts) && in_array($processor_data['processor_script'], $changable_processor_scripts)){
            // 注文データから取引状況を取得
            $cc_status = fn_krnkwc_get_order_status_by_order_id($order_id);

            // ステータスコードが存在する場合
            if (!empty($cc_status)) {
                // 金額変更可能な取引状況であるかを判定
                return fn_krnkwc_is_wc_processable($cc_status, 'creditchangeprice');
            }
        }
    }

    return false;
}
/////////////////////////////////////////////////////////////////////////////////////
// 金額変更 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 取引状況名を取得
 *
 * @param $cc_status
 * @return string
 */
function fn_krnkwc_get_cc_status_name($cc_status)
{
    // 取引状況名を初期化
    $cc_status_name = '';

    // 取引状況コードが存在する場合
    if( !empty($cc_status) ){
        // 取引状況コードに含まれる数値以外の値（例 : CC_ や CVS_）を削除
        $cc_status = mb_ereg_replace('[^0-9]', '', $cc_status);
        // 取引状況名を取得
        $cc_status_name = fn_krnkwc_wc_get_status_info_name($cc_status);
    }
    return $cc_status_name;
}




/**
 * クレジット決済取消 / 金額変更 / 取引照会 処理を実行
 *
 * @param $order_id
 * @param string $type
 * @param string $org_amount
 * @return bool
 */
function fn_krnkwc_send_cc_request( $order_id, $type = 'creditcancel')
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_krnkwc_check_process_validity($order_id, $type);

    // 指定した処理を行うのに適した注文でない場合、処理を終了
    if ( !$is_valid_order ){
        return false;
    }

    $params = array();

    // 受付番号を取得
    $order_no = db_get_field("SELECT order_no FROM ?:jp_krnkwc_cc_status WHERE order_id = ?i", $order_id);

    // 当該注文の支払方法に関する情報を取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);

    //////////////////////////////////////////////////////////////////////////
    // 共通パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // 後払い請求金額変更対応
    ///////////////////////////////////////////////
    if($type == 'abchangeprice') {
        // 加盟店コード
        $params['ycfStrCode'] = Registry::get('addons.kuroneko_webcollect.ycf_str_code');
        // 受注番号
        $params['orderNo'] = $order_no;
    }
    else{
        // 加盟店コード
        $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');
        // 受付番号
        $params['order_no'] = $order_no;
    }
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 EOF
    ///////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////
    // 共通パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ BOF
    //////////////////////////////////////////////////////////////////////////
    switch($type){
        // 決済取消
        case 'creditcancel':
            // 機能区分
            $params['function_div'] = 'A06';

            // テスト環境に接続する場合
            if($processor_data['processor_params']['mode'] == 'test'){
                // 予備1（エラーコードを指定して強制的にエラーを発生させる際に使用）
                // $params['reserve_1'] = '';
            }

            // エラー発生時のタイトル
            $error_title = __('jp_kuroneko_webcollect_creditcancel_error') . ' (' . __('order') . '#' . $order_id . ')';

            break;

        // 金額変更
        case 'creditchangeprice':

            // 機能区分
            $params['function_div'] = 'A07';

            // 利用金額
            $new_price = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);
            $params['new_price'] = round($new_price);

            // エラー発生時のタイトル
            $error_title = __('jp_kuroneko_webcollect_creditchangeprice_error') . ' (' . __('order') . '#' . $order_id . ')';

            // テスト環境に接続する場合
            if($processor_data['processor_params']['mode'] == 'test'){
                // 予備1（エラーコードを指定して強制的にエラーを発生させる際に使用）
                // $params['reserve_1'] = '';
            }

            break;

        // 取引照会
        case 'tradeinfo':

            // 機能区分
            $params['function_div'] = 'E04';

            // テスト環境に接続する場合
            if($processor_data['processor_params']['mode'] == 'test'){
                // 予備1（エラーコードを指定して強制的にエラーを発生させる際に使用）
                // $params['reserve_1'] = '';
            }

            break;

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // 後払い請求金額変更対応
        ///////////////////////////////////////////////
        // クロネコ代金後払いサービス 請求金額変更（減額）
        case 'abchangeprice':

            // 受注日
            $params['orderYmd'] = date('Ymd');

            $order_info = fn_get_order_info($order_id);

            // 郵便番号
            $params['postCode'] = preg_replace("/[^0-9]+/", '', $order_info['b_zipcode']);

            // クロネコ代金後払いサービスに送信する住所情報をフォーマット
            list($address1, $address2) = fn_krnkwc_ab_format_address($order_info);

            // 住所1
            $params['address1'] = $address1;

            // 住所2
            if( !empty($address2) ){
                $params['address2'] = $address2;
            }

            // 購入商品名称
            $params['itemName1'] = fn_krnkwc_format_product_name($order_info, 'atobrai');

            // 購入商品小計
            $params['subTotal1'] = round($order_info['total']);

            // 注文合計
            $params['totalAmount'] = $params['subTotal1'];

            // パスワード
            $params['password'] = Registry::get('addons.kuroneko_webcollect.atobarai_password');

            // 依頼日時
            $params['requestDate'] = date('YmdHis');

            // メッセージタイトル
            $error_title = __('jp_kuroneko_webcollect__ab') . ' (' . __('order') . '#' . $order_id . ')';

            break;

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////
        default :
            // do nothing
    }
    //////////////////////////////////////////////////////////////////////////
    // 個別パラメータ EOF
    //////////////////////////////////////////////////////////////////////////

    // 処理実行
    $result = fn_krnkwc_send_request($type, $params, $processor_data['processor_params']['mode']);

    // クロネコwebコレクトに対するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 処理が正常終了している場合
        if ( $return_code == 0 ) {

            switch($type){
                // 金額変更が完了した場合
                case 'creditchangeprice':

                    // 金額変更した取引情報を照会
                    $trade_info = fn_krnkwc_get_trade_info($order_id);
                    $result['crdCResCode'] = $trade_info['crdCResCode'];
                    $result['returnDate'] = $trade_info['crdCResDate'];
                    if( !empty($trade_info['renewalDate']) ) $result['renewalDate'] = $trade_info['renewalDate'];
                    break;

                // 決済取消が完了した場合
                case 'creditcancel':

                    // 決済取消した取引情報を照会
                    $trade_info = fn_krnkwc_get_trade_info($order_id);
                    $result['crdCResCode'] = $trade_info['crdCResCode'];
                    $result['returnDate'] = $trade_info['crdCResDate'];
                    if( !empty($trade_info['cancelDate']) ) $result['cancelDate'] = $trade_info['cancelDate'];
                    break;

            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 BOF
            // 後払い請求金額変更対応
            ///////////////////////////////////////////////
                // 後払い金額変更が完了した場合
                case 'abchangeprice':
                    fn_set_notification('N', $error_title, __('jp_kuroneko_webcollect__abchangeprice_success'));
                    break;

                default:
                    // do nothing
            }

            if($type != 'abchangeprice') {
                // 注文情報を取得
                $order_info = fn_get_order_info($order_id);

                // 取引状況を更新
                fn_krnkwc_get_trade_info($order_id);
            }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 EOF
            ///////////////////////////////////////////////
            // DBに保管する支払い情報を生成
            fn_krnkwc_format_payment_info($type, $order_id, $order_info['payment_info'], $result);

            return true;

        // エラー処理
        } else {
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 BOF
            // 後払い請求金額変更対応
            ///////////////////////////////////////////////
            // エラーメッセージを表示
            if( $type == 'abchangeprice' ) {
                fn_set_notification('E', $error_title, __('jp_kuroneko_webcollect_ab_changeprice_error_msg') . '(' . $result['errorCode'] . ')');
            }
            else {
                fn_krnkwc_set_err_msg($result, $error_title);
            }
        }

    // リクエスト送信が失敗した場合
    }else{

        if( $type == 'abchangeprice' ){
            $msg = 'jp_kuroneko_webcollect_ab_changeprice_error_msg';
        }
        else{
            $msg = 'jp_kuroneko_webcollect_cc_status_change_failed';
        }
        // エラーメッセージを表示
        fn_set_notification('E', $error_title, __($msg));
    }
            ///////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2020 EOF
            ///////////////////////////////////////////////
    return false;
}




/**
 * クロネコwebコレクトの取引照会
 *
 * @param $order_id
 * @return array
 */
function fn_krnkwc_get_trade_info($order_id)
{
    ///////////////////////////////////////////////////
    // 取引照会に必要なパラメータを取得 BOF
    ///////////////////////////////////////////////////
    $params = array();

    // 機能区分
    $params['function_div'] = 'E04';

    // 加盟店コード
    $params['trader_code'] = Registry::get('addons.kuroneko_webcollect.trader_code');

    // 受付番号
    $params['order_no'] = fn_krnkwc_get_order_no($order_id);

    // 当該注文の支払方法に関する情報を取得
    $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);

    $status_prefix = fn_krnkwc_get_status_prefix($processor_data['processor_script']);

    // テスト環境に接続する場合
    if($processor_data['processor_params']['mode'] == 'test') {
        // 予備1（エラーコードを指定して強制的にエラーを発生させる際に使用）
        // $params['reserve_1'] = '';
    }
    ///////////////////////////////////////////////////
    // 取引照会に必要なパラメータを取得 EOF
    ///////////////////////////////////////////////////

    // 取引照会
    $result = fn_krnkwc_send_request('tradeinfo', $params, $processor_data['processor_params']['mode']);

    // クロネコwebコレクトに対するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 処理が正常終了している場合
        if ($return_code == 0) {

            // 結果件数がある場合
            if( $result['resultCount'] > 0 ){
                $result_data = get_object_vars($result['resultData']);

                // 取得した取引状況をDBに反映
                $wc_status_code = $status_prefix . $result_data['statusInfo'];
                db_query("UPDATE ?:jp_krnkwc_cc_status SET status_code = ?s WHERE order_id = ?i", $wc_status_code, $order_id);
            }

            return $result_data;
        }
    }
}




/**
 * 取引状況ステータスに付加するプリフィックスを取得
 *
 * @param $script_name
 * @return string
 */
function fn_krnkwc_get_status_prefix($script_name)
{
    $status_prefix ='';

    // 決済用スクリプト名が指定されている場合
    if( !empty($script_name) ){
        // 決済用スクリプト名に応じてプリフィックスを指定
        switch($script_name){
            // クレジットカード決済
            case 'krnkwc_cc.php':
            case 'krnkwc_ccreg.php':
            case 'krnkwc_cctkn.php':
            case 'krnkwc_ccrtn.php':
                $status_prefix = 'CC_';
                break;
            // コンビニ決済
            case 'krnkwc_cvs.php':
                $status_prefix = 'CVS_';
                break;
            // 後払い
            case 'krnkab.php':
                $status_prefix = 'AB_';
            default:
                // do nothing
        }
    }

    return $status_prefix;
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_krnkwc_check_process_validity( $order_id, $type )
{
    // 注文データから取引状況を取得
    $cc_status = fn_krnkwc_get_order_status_by_order_id($order_id);

    switch($type){
        // 決済取消
        case 'creditcancel':
            return fn_krnkwc_is_wc_processable($cc_status, 'creditcancel');
            break;
        // 金額変更
        case 'creditchangeprice':
            return fn_krnkwc_is_wc_processable($cc_status, 'creditchangeprice');
            break;
        // クロネコ代金後払いサービス（決済依頼取消）
        case 'ab_cancel':
            return fn_krnkwc_is_ab_cancelable($cc_status);
            break;
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 BOF
        // 後払い請求金額変更対応
        ///////////////////////////////////////////////
        // クロネコ代金後払いサービス（請求金額変更（減額））
        case 'abchangeprice':
            return fn_krnkwc_is_ab_changeable($cc_status);
            break;
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2020 EOF
        ///////////////////////////////////////////////
        // その他
        default:
            // do nothing
    }

    return false;
}




/**
 * 決済取消または金額変更可能な取引状況であるかをチェック
 *
 * @param $cc_status_code
 * @param $type
 * @return bool
 */
function fn_krnkwc_is_wc_processable($cc_status_code, $type)
{
    switch($type){
        // 決済取消
        case 'creditcancel':
            switch($cc_status_code){
                case 'CC_4':    // 与信完了
                case 'CC_5':    // 予約受付完了
                case 'CC_15':   // 予約販売与信エラー
                case 'CC_17':   // 金額変更NG
                case 'CC_30':   // 精算確定待ち
                case 'CC_31':   // 精算確定
                    return true;
                    break;
                default:
                    // do nothing;
            }
            break;
        // 金額変更
        case 'creditchangeprice':
            switch($cc_status_code){
                case 'CC_4':    // 与信完了
                case 'CC_5':    // 予約受付完了
                case 'CC_30':   // 精算確定待ち
                case 'CC_31':   // 精算確定
                    return true;
                    break;
                default:
                    // do nothing;
            }
            break;
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
 * コンビニ種別に応じた機能区分コードを取得
 *
 * @param $cvs_code
 * @return string
 */
function fn_krnkwc_get_cvs_function_div($cvs_code)
{
    $function_div = '';

    switch($cvs_code){
        case 'se': // セブンイレブン
            $function_div = 'B01';
            break;
        case 'fm': // ファミリーマート
            $function_div = 'B02';
            break;
        case 'ls': // ローソン
            $function_div = 'B03';
            break;
        case 'ck': // サークルKサンクス
            $function_div = 'B04';
            break;
        case 'ms': // ミニストップ
            $function_div = 'B05';
            break;
        case 'sm': // セイコーマート
            $function_div = 'B06';
            break;
    }
    return $function_div;
}




/**
 * コンビニ種別またはコンビニコードに応じてデータの送信先とコンビ二名をセット
 *
 * @param $code
 * @return array
 */
function fn_krnkwc_get_cvs_info($code)
{
    $cvs_type = '';
    $cvs_name = '';

    switch($code){
        case 'B01': // セブンイレブン
        case '21':
            $cvs_type = 'cvs1';
            $cvs_name = __("jp_cvs_se");
            break;
        case 'B02': // ファミリーマート
        case '22' :
            $cvs_type = 'cvs2';
            $cvs_name = __("jp_cvs_fm");
            break;
        case 'B03': // ローソン
        case '22':
            $cvs_type = 'cvs3';
            $cvs_name = __("jp_cvs_ls");
            break;
        case 'B04': // サークルKサンクス
        case '26':
            $cvs_type = 'cvs3';
            $cvs_name = __("jp_cvs_ck") . __("jp_cvs_ts");
            break;
        case 'B05': // ミニストップ
        case '25':
            $cvs_type = 'cvs3';
            $cvs_name = __("jp_cvs_ms");
            break;
        case 'B06': // セイコーマート
        case '24':
            $cvs_type = 'cvs3';
            $cvs_name = __("jp_cvs_sm");
            break;
        default:
            // do nothing
    }

    return array($cvs_type, $cvs_name);
}




/**
 * コンビニ決済に関するお知らせを購入者にメール送信
 *
 * @param $cvs_type
 * @param $order_id
 * @param $order_info
 * @param $cvs_payment_info
 */
function fn_krnkwc_send_cvs_payment_info($cvs_type, $order_id, $order_info, $cvs_payment_info)
{
    Mailer::sendMail(array(
        'to' => $order_info['email'],
        'from' => 'company_orders_department',
        'data' => array(
            'order_info' => $order_info,
            'cvs_payment_info' => $cvs_payment_info,
        ),
        'tpl' => 'addons/kuroneko_webcollect/' . $cvs_type . '_notification.tpl',
        'company_id' => $order_info['company_id'],
    ), 'C', $order_info['lang_code']);
}
/////////////////////////////////////////////////////////////////////////////////////
// コンビニ決済 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// クロネコ代金後払いサービス BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 20桁の受注番号を生成
 *
 * @param $order_id
 * @return string
 */
function fn_krnkwc_get_krnk_ab_order_no($order_id)
{
    // 受付番号を生成
    return sprintf("%010d", (int)$order_id) . (string)time();
}




/**
 * クロネコ代金後払いサービスに送信する住所情報をフォーマット
 *
 *
 * @param $order_info
 * @return array
 */
function fn_krnkwc_ab_format_address($order_info, $type = 'billing')
{
    // 送り先住所
    if($type == 'shipping'){
        $address_prefix = 's_';
    // 請求先住所
    }else{
        $address_prefix = 'b_';
    }

    // 住所1
    $address1_org = mb_convert_kana($order_info[$address_prefix . 'state'] . $order_info[$address_prefix . 'city'], 'RNASKV', 'UTF-8');
    $address1 = mb_substr($address1_org, 0 , KRNKAB_MAXLEN_ADDRESS, 'UTF-8');

    // 住所1の内容がクロネコ代金後払いサービスにおける最大文字数を超過している場合
    if($address1_org != $address1){
        // 超過している文言を変数にセット
        $address1_rest = mb_substr($address1_org, KRNKAB_MAXLEN_ADDRESS + 1 , '', 'UTF-8')  . '　';

    // 住所1の内容がクロネコ代金後払いサービスにおける最大文字数以内の場合
    }else{
        $address1_rest = '';
    }

    // 住所2
    $address2 = '';
    if( !empty($order_info[$address_prefix . 'address']) ){
        $address2_org =  mb_convert_kana($order_info[$address_prefix . 'address'], 'RNASKV', 'UTF-8');

        if( !empty($order_info[$address_prefix . 'address_2']) ){
            $additonal_address = mb_convert_kana($order_info[$address_prefix . 'address_2'], 'RNASKV', 'UTF-8');
            $address2 = mb_substr($address1_rest . $address2_org . '　' . $additonal_address, 0 , KRNKAB_MAXLEN_ADDRESS, 'UTF-8');
        }else{
            $address2 = mb_substr($address1_rest . $address2_org, 0 , KRNKAB_MAXLEN_ADDRESS, 'UTF-8');
        }
    }

    return array($address1, $address2);
}




/**
 * クロネコ代金後払いサービスの取引状況を照会
 *
 * @param $order_id
 * @return array|bool
 */
function fn_krnkwc_get_ab_status($order_id, $notification = true)
{
    $ab_order_info = array();

    // 受注番号を取得
    $order_no = fn_krnkwc_get_order_no($order_id);

    // 受注番号が存在しない場合は処理を終了
    if( empty($order_no) ) return $ab_order_info;

    // 支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);

    // クロネコ代金後払いサービス以外の支払方法による注文は処理を終了
    if( $processor_data['processor_script'] != 'krnkab.php' ) return $ab_order_info;

    ///////////////////////////////////////////////////
    // 取引状況照会に必要なパラメータを取得 BOF
    ///////////////////////////////////////////////////
    $params = array();

    // 加盟店コード
    $params['ycfStrCode'] = fn_krnkwc_get_addon_setting($order_id, 'ycf_str_code');

    // 受注番号
    $params['orderNo'] = $order_no;

    // 依頼日時
    $params['requestDate'] = date('YmdHis');

    // パスワード
    $params['password'] = fn_krnkwc_get_addon_setting($order_id, 'atobarai_password');
    ///////////////////////////////////////////////////
    // 取引状況照会に必要なパラメータを取得 EOF
    ///////////////////////////////////////////////////

    $mode = $processor_data['processor_params']['mode'];

    // 取引状況照会
    $result = fn_krnkwc_send_request('KAAST0010', $params, $mode);

    // 取引情報照会に関するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 取引状況照会が正常に終了している場合
        if ( $return_code == 0 ) {

            // クロネコ代金後払いサービスの取引状況を取得
            if( !empty($result['result']) ){
                // 取得した取引状況をDBに反映
                $ab_status_code = 'AB_' . $result['result'];
                db_query("UPDATE ?:jp_krnkwc_cc_status SET status_code = ?s WHERE order_id = ?i", $ab_status_code, $order_id);

                // クロネコ代金後払いサービスの取引状況をセット
                $ab_order_info['ab_order_status'] = fn_krnkwc_get_ab_order_status($result['result']);
            }

            // クロネコ代金後払いサービスの警報情報を取得
            if( !empty($result['warning']) && (int)$result['warning'] > 0 ){
                $ab_order_info['ab_warning_info'] = fn_krnkwc_get_ab_warning_info($result['warning']);
            }
            return $ab_order_info;

        // 取引状況照会でエラーが発生した場合
        }else{
            // エラーメッセージを表示して終了
            if( $notification ) fn_krnkwc_set_err_msg($result, __('jp_kuroneko_webcollect_ab_order_ref_error'));
            return $ab_order_info;
        }

    // 取引状況照会に関するリクエスト送信が異常終了した場合
    }else{
        // 取引状況照会失敗メッセージを表示して終了
        if( $notification ) fn_set_notification('N', __('jp_kuroneko_webcollect_ab_order_ref_error'), __('jp_kuroneko_webcollect_ab_order_ref_error_msg'));
        return $ab_order_info;
    }
}




/**
 * クロネコ代金後払いサービスの取引状況を一括照会
 *
 */
function fn_krnkwc_get_mass_ab_status()
{
    // 12（入金済み）以外は変更可能 = 取引状況照会の対象
    $updatable_statuses = array('AB_1', 'AB_2', 'AB_3', 'AB_5', 'AB_6', 'AB_10', 'AB_11');
    $o_ids = db_get_fields('SELECT order_id FROM ?:jp_krnkwc_cc_status WHERE status_code IN (?a)', $updatable_statuses);

    // クロネコ代金後払いサービスの取引状況を照会
    foreach($o_ids as $order_id){
        fn_krnkwc_get_ab_status($order_id, false);
    }
}




/**
 * 決済取消依頼
 *
 * @param $order_id
 * @param bool|true $notification
 * @return bool
 */
function fn_krnkwc_ab_cancel($order_id, $notification = true)
{
    // 受注番号を取得
    $order_no = fn_krnkwc_get_order_no($order_id);

    // 受注番号が存在しない場合は処理を終了
    if( empty($order_no) ) return false;

    // 支払方法に紐付けられた決済代行サービスの情報を取得
    $processor_data = fn_krnkwc_get_processor_data_by_order_id($order_id);

    // クロネコ代金後払いサービス以外の支払方法による注文は処理を終了
    if( $processor_data['processor_script'] != 'krnkab.php' ) return false;

    ///////////////////////////////////////////////////
    // 決済取消依頼に必要なパラメータを取得 BOF
    ///////////////////////////////////////////////////
    $params = array();

    // 加盟店コード
    $params['ycfStrCode'] = Registry::get('addons.kuroneko_webcollect.ycf_str_code');

    // 受注番号
    $params['orderNo'] = $order_no;

    // 依頼日時
    $params['requestDate'] = date('YmdHis');

    // パスワード
    $params['password'] = Registry::get('addons.kuroneko_webcollect.atobarai_password');
    ///////////////////////////////////////////////////
    // 決済取消依頼に必要なパラメータを取得 EOF
    ///////////////////////////////////////////////////

    $mode = $processor_data['processor_params']['mode'];

    // 決済取消依頼
    $result = fn_krnkwc_send_request('KAACL0010', $params, $mode);

    // 決済取消依頼に関するリクエスト送信が正常終了した場合
    if (!empty($result)) {

        // 結果コード
        $return_code = $result['returnCode'];

        // 決済取消依頼が正常に終了している場合
        if ( $return_code == 0 ) {
            // 取引状況にAB_2（取消）をDBに反映
            $ab_status_code = 'AB_2';
            db_query("UPDATE ?:jp_krnkwc_cc_status SET status_code = ?s WHERE order_id = ?i", $ab_status_code, $order_id);

        // 決済取消依頼でエラーが発生した場合
        }else{
            // エラーメッセージを表示して終了
            if( $notification ) fn_krnkwc_set_err_msg($result, __('jp_kuroneko_webcollect_ab_cancel_error'));
        }

    // 決済取消依頼に関するリクエスト送信が異常終了した場合
    }else{
        // 決済取消依頼失敗メッセージを表示して終了
        if( $notification ) fn_set_notification('N', __('jp_kuroneko_webcollect_ab_cancel_error'), __('jp_kuroneko_webcollect_ab_cancel_error_msg'));
    }
}




/**
 * クロネコ代金後払いサービスの取引状況名を取得
 *
 * @param $code
 * @return bool|string
 */
function fn_krnkwc_get_ab_order_status($code)
{
    return __('jp_kuroneko_webcollect_ab_order_status' . (int)$code);
}




/**
 * クロネコ代金後払いサービスの警報情報を取得
 *
 * @param $code
 * @return bool|string
 */
function fn_krnkwc_get_ab_warning_info($code)
{
    return __('jp_kuroneko_webcollect_ab_warning' . (int)$code);
}




/**
 * 代金後払いステータス名を取得
 *
 * @param $ab_status
 * @return string
 */
function fn_krnkwc_get_ab_status_name($ab_status)
{
    // 取引状況コードに含まれる数値以外の値（例 : CC_ や CVS_）を削除
    $ab_status = mb_ereg_replace('[^0-9]', '', $ab_status);

    // 請求ステータスコードに応じて請求ステータス名を取得
    return __('jp_kuroneko_webcollect_ab_order_status' . (int)$ab_status);
}




/**
 * 決済取消可能な取引状況であるかをチェック
 *
 * @param $ab_status_code
 * @return bool
 */
function fn_krnkwc_is_ab_cancelable($ab_status_code)
{
    // 決済取消可能な取引状況であるかをチェック
    switch($ab_status_code){
        case 'AB_1':    // 承認済み
        case 'AB_3':    // 送り状番号登録済み
        case 'AB_5':    // 配送用調査
        case 'AB_6':    // 警報メール送信済み
        case 'AB_10':   // 売上確定
        case 'AB_11':   // 請求書発行済み
            // 削除可能
            return true;
        default:
            // 削除不可
            return false;
    }
    return false;
}




/**
 * 取引状況を一括で参照するためのcronジョブコマンドを生成
 *
 * @param string $type
 * @return string
 */
function fn_krnkwc_get_cron_command($type = 'wc')
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

    $cron_url .= Registry::get('config.admin_index');

    // クロネコwebコレクトの場合
    if($type == 'wc'){
        $cron_url .= "?dispatch=krnkwc_cc_manager.cron_status_update_wc&cron_password=" . Registry::get('addons.kuroneko_webcollect.cron_password_wc');
    // クロネコ代金後払いサービスの場合
    }else{
        $cron_url .= "?dispatch=krnkab_manager.cron_status_update_ab&cron_password=" . Registry::get('addons.kuroneko_webcollect.cron_password_ab');
    }
    /////////////////////////////////////////////
    // cronジョブでアクセスするURLを生成 EOF
    /////////////////////////////////////////////

    // cronジョブで実行するコマンドを生成
    $cron_command = 'wget --delete-after ' . '"' .  $cron_url . '"';

    return $cron_command;
}

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
function fn_krnkwc_get_payment_info($template)
{
    $result = true;

    $path = "addons/kuroneko_webcollect/views/orders/components/payments/".$template;

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
// 出荷情報登録仕様変更対応（他社配送）
///////////////////////////////////////////////
/**
 * 運送会社情報を取得
 *
 * @param $carrier
 * @return array
 */
function fn_krnkwc_get_carrier_name($carrier)
{
    $carriers = Shippings::getCarriers();

    foreach($carriers as $key => $value) {
        if( $key == $carrier ) {
            $result = $value;
            break;
        }
    }

    return $result;

}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2019 EOF
///////////////////////////////////////////////



///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 BOF
// 後払い請求金額変更対応
///////////////////////////////////////////////
/**
 * 金額変更可能な取引状況であるかをチェック
 *
 * @param $ab_status_code
 * @return bool
 */
function fn_krnkwc_is_ab_changeable($ab_status_code)
{
    // 金額変更可能な取引状況であるかをチェック
    switch($ab_status_code){
        case 'AB_1':    // 承認済み
        case 'AB_3':    // 送り状番号登録済み
        case 'AB_5':    // 配送用調査
        case 'AB_10':   // 売上確定
        case 'AB_11':   // 請求書発行済み
            // 金額変更可能
            return true;
        default:
            // 金額変更不可
            return false;
    }
    return false;
}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 EOF
///////////////////////////////////////////////




///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2021 BOF
// 複数ショップで「すべて」選択時にアドオン設定を取得
///////////////////////////////////////////////
/**
 * 複数ショップで「すべて」選択時にアドオン設定を取得
 *
 * @param $order_id
 * @param $setting_field
 * @return string
 */
function fn_krnkwc_get_addon_setting($order_id, $setting_field)
{
    $order_info = fn_get_order_info($order_id);
    $company_id = $order_info['company_id'];

    $setting_value = db_get_field("SELECT svv.value FROM ?:settings_vendor_values svv JOIN ?:settings_objects so ON svv.object_id = so.object_id AND so.name = ?s WHERE svv.company_id = ?i", $setting_field, $company_id);

    if(empty($setting_value)){
        return Registry::get('addons.kuroneko_webcollect.' . $setting_field);
    }
    else {
        return $setting_value;
    }
}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2021 EOF
///////////////////////////////////////////////
##########################################################################################
// END その他の関数
##########################################################################################
