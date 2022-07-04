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
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_credix_[フックポイント名]
// (2) (1)以外の関数：fn_crdx_[任意の名称]

use Tygh\Registry;
use Tygh\Http;

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

// CREDIX決済が完了した場合に各決済代行業者に文字列'OK'を返す
function fn_credix_order_placement_routines(&$order_id, &$force_notification, &$order_info)
{
    // 本スクリプトが jp_extras/credix/notify.php 経由で実行されている場合
    if( !empty(Tygh::$app['session']['credix_process_order']) && Tygh::$app['session']['credix_process_order'] == 'Y' ){
        // セッション変数を解放
        unset(Tygh::$app['session']['credix_process_order']);

        // 注文情報から決済代行業者のIDを取得
        $processor_id = $order_info['payment_method']['processor_id'];

        // 決済代行業者を使った決済の場合
        if( !empty($processor_id) && $processor_id > 0 ){

            // 決済代行業者のスクリプトファイル名を取得
            $processor_script = db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $processor_id);

            // 決済代行業者のスクリプトファイル名がCREDIXカード決済で利用するものと同一の場合
            if( $processor_script == 'credix_cc.php' || $processor_script == 'credix_qc.php' ){
                // CREDIXへの戻り値として OK を出力して処理を終了
                echo 'OK';
                exit;
            }
        }
    }
}




// CREDIX決済では注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
// 【解説】
// 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
// 注文ステータスを指定している。
// $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
// 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
// 支払情報に強制的に書き込まれる。
// この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためCREDIX決済
// では注文完了時に支払情報から注文ステータスに関する記述を削除する。
function fn_credix_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
            case '9180':
            case '9181':
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
 * Quick Charge用ID登録済ユーザーのみ支払方法に「Quick Charge」を表示
 *
 * @param $params
 * @param $payments
 */
function fn_credix_get_payments_post(&$params, &$payments)
{
    fn_lcjp_filter_payments($payments, 'credix_qc.tpl', 'credix');
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
function fn_credix_install()
{
    fn_lcjp_install('credix');
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

/**
 * QuickChargeで利用するsendid（25桁のランダムな英数字）を生成
 *
 * @return string
 */
function fn_crdx_generate_sendid()
{
    // 25桁のランダムな英数字を生成
    static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < 25; ++$i) {
        $str .= $chars[mt_rand(0, 61)];
    }

    // 生成した文字列がすでに使用されている場合には再度生成
    $existing_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE quickpay_id = ?s", $str);
    if( !empty($existing_id) ){
        fn_crdx_generate_sendid();
    }

    return $str;
}




/**
 * Quick Charge用のID（sendid）を取得
 *
 * @param $user_id
 * @return array|bool
 */
function fn_crdx_get_sendid($user_id)
{
    $sendid = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?s AND payment_method = 'credix'", $user_id);

    if( !empty($sendid) ){
        return $sendid;
    }else{
        return false;
    }
}




/**
 * CREDIXに送信するQuick Charge用パラメータを取得
 *
 * @param $sendid
 * @param $order_info
 * @return array
 */
function fn_crdx_qc_get_params($order_info)
{
    $params = array();

    // IPコード
    $params['clientip'] = Registry::get('addons.credix.ip');

    // 決済形態
    $params['send'] = 'cardsv';

    // 会員データの検索条件（「clientip」＋「sendid」で検索）
    $params['cardnumber'] = '8888888888888882';

    // 有効期限年
    $params['expyy'] = '00';

    // 有効期限月
    $params['expmm'] = '00';

    // 決済金額
    $params['money'] = round($order_info['total']);

    // 電話番号
    $params['telno'] = '0000000000';

    // メールアドレス
    $params['email'] = $order_info['email'];

    // フリーパラメータ
    $params['sendid'] = fn_crdx_get_sendid($order_info['user_id']);

    fn_crdx_get_sendid($order_info['user_id']);

    // フリーパラメータ
    $params['sendpoint'] = $order_info['order_id'] . CREDIX_SEPARATOR . (int)$order_info['user_id'];

    // レスポンス時にオーダーナンバーを取得する
    $params['printord'] = 'yes';

    // レスポンスのみを取得し、CGI送信無し
    $params['pubsec'] = 'yes';

    return $params;
}




/**
 * Quick Charge用パラメータをCREDIXに送信して決済実行
 *
 * @param $params
 * @return array|bool|mixed
 */
function fn_crdx_send_request($params)
{
    // 接続先URL
    $connection_url = 'https://secure.credix-web.co.jp/cgi-bin/secure.cgi';

    // データをCREDIXに送信
    $result = Http::post($connection_url, $params);

    if( !empty($result) ){
        $result = explode("\n", $result);
        $result = $result[0];
    }else{
        $result = false;
    }

    return $result;
}




/**
 * Quick Charge（登録済みカード決済）の登録有無を取得
 *
 * @param $user_id
 * @return array|bool
 */
function fn_crdx_get_qc_info($user_id)
{
    $qc_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id =?i AND payment_method =?s", $user_id, 'credix');

    if( !empty($qc_id) ){
        return $qc_id;
    }else{
        return false;
    }
}




/**
 * Quick Charge（登録済みカード決済）用レコードを削除
 *
 * @param $user_id
 */
function fn_crdx_delete_qc_info($user_id)
{
    // Quick Charge（登録済みカード決済）用レコードを削除
    db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'credix');
    fn_set_notification('N', __('notice'), __('jp_credix_qc_delete_success'));
}
##########################################################################################
// END その他の関数
##########################################################################################
