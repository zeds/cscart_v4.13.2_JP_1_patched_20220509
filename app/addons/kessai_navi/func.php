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
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_kessai_navi_[フックポイント名]
// (2) (1)以外の関数：fn_knv_[任意の名称]

use Tygh\Registry;
use Tygh\Http;

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * 決済ナビでは注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるため決済ナビ
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_kessai_navi_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
            case '9160':
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
 * 注文情報削除時に加盟店取引番号（stran）を削除
 *
 * @param $order_id
 */
function fn_kessai_navi_delete_order(&$order_id)
{
    db_query("DELETE FROM ?:jp_knv_stran WHERE order_id = ?i", $order_id);
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
function fn_kessai_navi_install()
{
    fn_lcjp_install('kessai_navi');
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
 * 加盟店取引番号（stran）を生成する
 *
 * @param $order_id
 */
function fn_knv_generate_stran($order_id)
{
    $stran = mt_rand(1, 999999);
    $stran = sprintf("%06d", $stran);

    $is_exist = db_get_field('SELECT stran FROM ?:jp_knv_stran WHERE stran = ?i', $stran);

    if(!empty($is_exist)){
        fn_knv_generate_stran($order_id);
    }else{
        $data = array (
            'order_id' => $order_id,
            'stran' => $stran,
        );
        db_query("REPLACE INTO ?:jp_knv_stran ?e", $data);
        return $stran;
    }
}




/**
 * 加盟店取引番号からCS-Cartの注文IDを取得
 *
 * @param $stan
 */
function fn_knv_get_order_id($stran)
{
    $order_id = db_get_field('SELECT order_id FROM ?:jp_knv_stran WHERE stran = ?i', $stran);

    if( !empty($order_id) ){
        return $order_id;
    }else{
        return false;
    }
}




/**
 * 決済ナビから戻されたパラメータが改竄されていないかチェックする
 *
 * @param $params
 */
function fn_knv_is_valid_params($params)
{
    // 検証用文字列を初期化
    $linkstr = '';

    // 指定されたパラメータの値を検証用文字列として連結
    foreach($params as $key => $val){
        switch($key){
            case 'p_ver':
            case 'stdate':
            case 'stran':
            case 'bkcode':
            case 'shopid':
            case 'cshopid':
            case 'amount':
            case 'mbtran':
            case 'bktrans':
            case 'tranid':
            case 'ddate':
            case 'tdate':
            case 'rsltcd':
            case 'mpidcd':
            case 'payurl':
            case 'skno':
            case 'vdate':
                $linkstr .= $val;
                break;
            default:
                // do nothing
        }
    }

    // 検証用文字列にハッシュ用パスワードを追加
    $linkstr = $linkstr . Registry::get('addons.kessai_navi.hashpass');

    // メッセージダイジェストを作成
    $schksum = htmlspecialchars(md5($linkstr));

    // 作成したメッセージダイジェストとパラメータ'rchksum'の値が一致する場合
    if($schksum == $params['rchksum']){
        // true（改竄なし）を返す
        return true;
    // 作成したメッセージダイジェストとパラメータ'rchksum'の値が一致しない場合
    }else{
        // false（改竄あり）を返す
        return false;
    }
}




/**
 * 収納機関コードに応じて決済手続き完了後に割り当てる注文ステータスを取得
 *
 * @param $bkcode   収納機関コード
 * @return string   注文ステータス
 */
function fn_knv_get_order_status($bkcode)
{
    if(empty($bkcode)) return 'F';

    switch($bkcode){
        case 'bg01':    // クレジットカード（オーソリ）
        case 'bg04':    // クレジットカード（番号預かり）
        case 'pt01':    // 永久不滅ポイント（セゾン）
        case '0033':    // 銀行ネット振込（ジャパンネット銀行）
        case '0001':    // 銀行ネット振込（みずほ銀行）
        case '0005':    // 銀行ネット振込（東京三菱UFJ銀行（旧東京三菱））
        case '0008':    // 銀行ネット振込（東京三菱UFJ銀行（旧UFJ））
        case '0009':    // 銀行ネット振込（三井住友銀行）
        case '0036':    // 銀行ネット振込（楽天銀行）
        case '0150':    // 銀行ネット振込（スルガ銀行）
        case '9900':    // 銀行ネット振込（ゆうちょ銀行）
        case '9901':    // 銀行ネット振込（ゆうちょ銀行（モバイル））
            // 注文ステータスに「P（処理中）」をセット
            return 'P';
            break;
        case 'cv01':    // コンビニ（ウェルネット）
        case 'cv02':    // コンビニ（セブンイレブン）
        case 'pe01':    // ペイジー（番号入力）
        case 'pe02':    // ペイジー（インターネットバンキング）
            // 注文ステータスに「O（処理待ち）」をセット
            return 'O';
            break;
        default:        // その他
            // 注文ステータスに「F（失敗）」をセット
            return 'F';
    }
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $order_id
 * @param $payment_info
 * @param $params
 * @return bool
 */
function fn_knv_format_payment_info($order_id, $payment_info, $params)
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

        // 支払情報がすでに存在する場合
        if( !empty($info) ){
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 BOF
            ////////////////////////////////////////////////////////////////////
            foreach($info as $key => $val){
                unset($info[$key]);
            }
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 EOF
            ////////////////////////////////////////////////////////////////////
        }

        ////////////////////////////////////////////////////////////////////
        // 注文情報として記録する各種項目を取得 BOF
        ////////////////////////////////////////////////////////////////////
        // 加盟店取引番号
        if( !empty($params['stran']) ){
            $info['jp_knv_stran'] = $params['stran'];
        }

        // 取引番号
        if( !empty($params['mbtran']) ){
            $info['jp_knv_mbtran'] = $params['mbtran'];
        }

        // 決済手段
        if( !empty($params['bkcode']) ){
            $info['jp_knv_payment_method'] = __('jp_knv_bkcode_' . $params['bkcode']);
        }

        // 取引金額
        if( !empty($params['amount']) ){
            $info['jp_knv_amount'] = $params['amount'];
        }

        // 収納機関受付番号
        if( !empty($params['bktrans']) ){
            $info['jp_knv_bktrans'] = $params['bktrans'];
        }

        // 消込識別情報
        if( !empty($params['tranid']) ){
            $info['jp_knv_tranid'] = $params['tranid'];
        }

        // 処理日付
        if( !empty($params['ddate']) ){
            $info['jp_knv_ddate'] = $params['ddate'];
        }

        // 支払期限
        if( !empty($params['vdate']) ){
            $info['jp_knv_vdate'] = $params['vdate'];
        }

        // 振込日（入金日）
        if( !empty($params['tdate']) ){
            $info['jp_knv_tdate'] = $params['tdate'];
        }
        ////////////////////////////////////////////////////////////////////
        // 注文情報として記録する各種項目を取得 EOF
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
 * DBキーを取得
 *
 * @param $user_id
 * @return array|string
 */
function fn_knv_get_dbkey()
{
    // ゲスト購入の場合はDBキーの取得・登録を行わない
    $auth = & Tygh::$app['session']['auth'];
    if( empty($auth['user_id']) ) return false;

    // お客様に対して発行されたDBキーを取得
    $dbkey = db_get_field('SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE payment_method = ?s AND user_id = ?i', 'kessai_navi', $auth['user_id']);

    // お客様に対してDBキーが発行されていない場合
    if( empty($dbkey) ){
        // DBキーを生成
        $dbkey = fn_knv_generate_dbkey();

        // 生成したDBキーを登録
        $data = array (
            'user_id' => $auth['user_id'],
            'payment_method' => 'kessai_navi',
            'quickpay_id' => $dbkey,
        );
        db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $data);
    }

    // DBキーを返す
    return $dbkey;
}




/**
 * DBキーを生成
 *
 * @return string
 */
function fn_knv_generate_dbkey($length=30)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; ++$i) {
        $str .= $chars[mt_rand(0, 61)];
    }
    $dbkey = strtoupper($str);

    // 同じDBキーがすでに登録されていないかチェック
    $is_exist = db_get_field('SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE payment_method = ?s AND quickpay_id = ?s', 'kessai_navi', $dbkey);

    // 同じDBキーがすでに登録されている場合
    if( !empty($is_exist) ){
        fn_knv_generate_dbkey();
    }else{
        return $dbkey;
    }
}




/**
 * 入金通知対象の決済手段であるかを判定
 *
 * @param string $bkcode
 * @return bool
 */
function fn_knv_method_notify($bkcode='')
{
    if(empty($bkcode)) return false;

    switch($bkcode){
        case 'cv01':    // コンビニ（ウェルネット）
        case 'cv02':    // コンビニ（セブンイレブン）
        case 'pe01':    // ペイジー（番号入力）
        case 'pe02':    // インターネットバンキング
            return true;
            break;
        default:        // その他
            return false;
    }
}




/**
 * 登録済みクレジットカード情報の取得
 *
 * @param $user_id
 * @return bool
 */
function fn_knv_get_registered_card_info($user_id)
{
    // ユーザーIDが存在しない場合はfalseを返す
    if( empty($user_id) ) return false;

    // 決済ナビに送信するパラメータを取得
    $params = fn_knv_get_ccreg_params('X', $user_id);

    // 決済ナビにデータを送信
    $registered_card = fn_knv_post_ccreg($params);

    // カード情報の取得に成功した場合
    if($registered_card['errcd'] == '00000'){
        // 有効期限の表示形式をフォーマット
        $registered_card['expirydate'] = substr($registered_card['expirydate'], 0, 2) . '/' . substr($registered_card['expirydate'], -2, 2);
        // 取得したカード情報を返す
        return $registered_card;

    // カード情報の取得に失敗した場合
    }else{
        // falseを返す
        return false;
    }
}




/**
 * 登録済みクレジットカード情報の削除
 *
 * @param $user_id
 * @return bool
 */
function fn_knv_delete_card_info($user_id)
{
    // ユーザーIDが存在しない場合はfalseを返す
    if( empty($user_id) ) return false;

    // 決済ナビに送信するパラメータを取得
    $params = fn_knv_get_ccreg_params('T', $user_id);

    // 決済ナビにデータを送信
    $registered_card = fn_knv_post_ccreg($params);

    // カード番号の削除に成功した場合
    if( !empty($registered_card['errcd']) && $registered_card['errcd'] == '00000'){
        // CS-CartからもDBキーを削除
        db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'kessai_navi');
        // 削除成功メッセージを表示
        fn_set_notification('N', __('information'), __('jp_knv_ccreg_delete_success'));

    // カード番号の削除に成功した場合
    }else{
        // 削除失敗メッセージを表示
        fn_set_notification('', __('jp_knv_ccreg_delete_error'), __('jp_knv_ccreg_delete_failed'));
    }
}




/**
 * 決済ナビにデータを送信
 *
 * @param $params
 * @return mixed
 */
function fn_knv_post_ccreg($params)
{
    // 表示中のショップの company_id を取得
    $company_id =  Registry::get('runtime.company_id');

    // 支払方法に関するデータを取得
    $payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'kessai_navi.php' AND ?:payments.status = 'A' AND ?:payments.company_id = ?i", $company_id);

    // 表示中のショップの company_id で支払方法が登録されていない場合、他のショップと共有されていないかチェック
    if(empty($payment_id)){
        $payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'kessai_navi.php' AND ?:payments.status = 'A'");

        $is_payment_shared = db_get_field("SELECT share_object_id FROM ?:ult_objects_sharing WHERE share_company_id = ?i AND share_object_id = ?i AND share_object_type =?s", $company_id, $payment_id, 'payments');

        // 他のショップと支払方法が共有されていない場合
        if( empty($is_payment_shared) ){
            // falseを返す
            return false;
        }
    }

    // 支払方法の設定を取得
    $processor_data = fn_get_processor_data($payment_id);

    ////////////////////////////////////////////////////////////////
    // 接続するURLをセット BOF
    ////////////////////////////////////////////////////////////////
    $target_url = '';
    if( $processor_data['processor_params']['mode'] == 'live' ){
        // 本番環境
        $target_url = $processor_data['processor_params']['url_ccreg_production'];
    }else{
        // テスト環境
        $target_url = $processor_data['processor_params']['url_ccreg_test'];
    }

    // 接続先URLがセットされていない場合は、テストサイトへ強制的に接続する
    if( empty($target_url) ){
        $target_url = 'https://tst.kessai-navi.jp/mltbank/MBBGDataReference';
    }
    ////////////////////////////////////////////////////////////////
    // 接続するURLをセット EOF
    ////////////////////////////////////////////////////////////////

    // 決済ナビにデータを送信
    $_result_str = Http::post($target_url, $params);

    // GETパラメータ形式（文字列）で結果データが返されるので配列化
    $_result_arr_1 = explode("&", $_result_str);
    foreach($_result_arr_1 as $result_params_1){
        $result_arr_2 = explode('=', $result_params_1);
        $registered_card[$result_arr_2[0]] = $result_arr_2[1];
    }

    // 配列化した結果データを返す
    return $registered_card;
}




/**
 * カード番号照会・削除用パラメータの作成
 *
 * @param $type
 * @param $user_id
 * @return array|bool
 */
function fn_knv_get_ccreg_params($type, $user_id)
{
    // 処理タイプやユーザーIDが正しく指定されていない場合はfalseを返す
    if( empty($type) | ($type != 'X' && $type != 'T') | empty($user_id) ) return false;

    // DBキーを取得
    $dbkey = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'kessai_navi');

    // DBキーが存在しない場合はfalseを返す
    if( empty($dbkey) ) return false;

    // 決済ナビに送信するデータ用配列を初期化
    $knv_data = array();

    // プロトコルバージョン
    $knv_data['p_ver'] = '0200';

    // 登録区分
    $knv_data['regid'] = strtoupper($type);

    // 収納機関コード
    $knv_data['bkcode'] = 'bg04';

    // 加盟店コード
    $knv_data['shopid'] = Registry::get('addons.kessai_navi.shopid');

    // 加盟店サブコード
    $knv_data['cshopid'] = Registry::get('addons.kessai_navi.cshopid');

    // DBキー
    $knv_data['dbkey'] = $dbkey;

    $linkstr = '';

    foreach($knv_data as $key => $knv_param){
        $linkstr .= $knv_param;
    }

    $linkstr = $linkstr . Registry::get('addons.kessai_navi.hashpass');
    $linkstr = mb_convert_encoding($linkstr, 'SJIS', 'UTF-8');

    // メッセージダイジェスト
    $knv_data['schksum'] = htmlspecialchars(md5($linkstr));

    return $knv_data;
}
##########################################################################################
// END その他の関数
##########################################################################################
