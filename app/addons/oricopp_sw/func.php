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
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_oricopp_sw_[フックポイント名]
// (2) (1)以外の関数：fn_oppsw_[任意の名称]

use Tygh\Registry;
use Tygh\Session;
use Tygh\Http;

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

// OricoPayment Plusでは注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
// 【解説】
// 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
// 注文ステータスを指定している。
// $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
// 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
// 支払情報に強制的に書き込まれる。
// この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためOricoPayment Plus
// では注文完了時に支払情報から注文ステータスに関する記述を削除する。
function fn_oricopp_sw_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
            case '9170':
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
##########################################################################################
// END フックポイントで動作する関数
##########################################################################################





##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################
/**
 * アドオンのインストール時の処理
 */
function fn_oricopp_sw_install()
{
    fn_lcjp_install('oricopp_sw');
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
 * OricoPayment Plusに送信するパラメータをセット
 *
 * @param $type
 * @param string $order_info
 * @return array
 */
function fn_oppsw_get_params($type, $order_info='')
{
    $params = array();

    // メールアドレス
    $params['MAILADDRESS'] = $order_info['email'];

    // 名前1
    if(!empty($order_info['b_firstname'])){
        $params['NAME1'] = mb_substr(mb_convert_kana($order_info['b_firstname'], "KVHA", 'UTF-8'), 0, 10, 'UTF-8');
    }

    // 名前2
    if(!empty($order_info['b_lastname'])){
        $params['NAME2'] = mb_substr(mb_convert_kana($order_info['b_lastname'], "KVHA", 'UTF-8'), 0, 10, 'UTF-8');
    }

    // フリガナに関する情報を取得
    $kana_info = fn_lcjp_get_name_kana($order_info);

    // カナ1
    if(!empty($kana_info['firstname_kana'])){
        $params['KANA1'] = mb_substr(mb_convert_kana($kana_info['firstname_kana'], "KVHA", 'UTF-8'), 0, 10, 'UTF-8');
    }

    // カナ2
    if(!empty($kana_info['lastname_kana'])){
        $params['KANA2'] = mb_substr(mb_convert_kana($kana_info['lastname_kana'], "KVHA", 'UTF-8'), 0, 10, 'UTF-8');
    }

    // 郵便番号
    $zip = preg_replace("/[^0-9]+/", '', $order_info['b_zipcode']);
    if( !empty($zip) && strlen($zip) == 7 ){
        $params['ZIP_CODE'] = substr($zip, 0, 3) . '-' . substr($zip, 3, 4);
    }

    // 住所1 / 住所2 / 住所3
    $billing_address = mb_convert_kana($order_info['b_state'] . $order_info['b_city'] . $order_info['b_address']. $order_info['b_address_2'], "KVHAS", 'UTF-8');
    if(!empty($billing_address)){
        if(mb_strlen($billing_address, 'UTF-8') <= 25){
            $params['ADDRESS1'] = $billing_address;
        }elseif (mb_strlen($billing_address, 'UTF-8') <= 50){
            $params['ADDRESS1'] = mb_substr($billing_address, 0, 25, 'UTF-8');
            $params['ADDRESS2'] = mb_substr($billing_address, 26, 50, 'UTF-8');
        }else{
            $params['ADDRESS1'] = mb_substr($billing_address, 0, 25, 'UTF-8');
            $params['ADDRESS2'] = mb_substr($billing_address, 26, 50, 'UTF-8');
            $params['ADDRESS3'] = mb_substr($billing_address, 51, 100, 'UTF-8');
        }
    }

    // 電話番号
    if( !empty($order_info['phone']) ){
        $params['TELEPHONE_NO'] = substr(preg_replace("/-/", "", mb_convert_kana($order_info['phone'],"a")), 0, 11);
    }

    // 決済タイプ
    $params['SETTLEMENT_TYPE'] = $type;

    // マーチャントID
    $params['MERCHANT_ID'] = Registry::get('addons.oricopp_sw.merchant_id');

    // 取引ID
    $params['ORDER_ID'] = $order_info['order_id'] . date('ymdHis');

    // WEB申込商品ID
    $web_desc_id = Registry::get('addons.oricopp_sw.web_desc_id');
    if(!empty($web_desc_id)){
        $params['WEB_DESCRIPTION_ID'] = $web_desc_id;
    }

    // 契約書有無区分
    $contract_doc = Registry::get('addons.oricopp_sw.contract_doc');
    if(!empty($contract_doc)){
        $params['CONTRACT_DOCUMENT_KBN'] = $contract_doc;
    }

    // 取扱契約番号
    $contract_no = Registry::get('addons.oricopp_sw.contract_no');
    if(!empty($contract_no)){
        $params['HANDLING_CONTRACT_NO'] = $contract_no;
    }

    // SessionID
    $params['SESSION_ID'] = Tygh::$app['session']->getId();

    // 購入金額
    $params['AMOUNT'] = round($order_info['total']);

    // カード売上フラグ
    $params['CARD_CAPTURE_FLAG'] = Registry::get('addons.oricopp_sw.cc_auth');

    // モバイルEdy用ショップ名
    $medy_shop_name = Registry::get('addons.oricopp_sw.medy_shop_name');
    if(!empty($medy_shop_name)){
        $params['SHOP_NAME'] = mb_strcut($medy_shop_name, 0, 48, 'UTF-8');
    }

    ////////////////////////////////////////////////////////////////
    // Suica用商品名 BOF
    ////////////////////////////////////////////////////////////////
    $tmp_items = $order_info['products'];

    // 最初の商品を取得
    $first_item = reset($tmp_items);

    // 注文商品が1種類のみの場合
    if( count($order_info['products']) == 1 ){
        // 商品名をセット（先頭40バイト分のみ抽出）
        $item_name = mb_strcut($first_item['product'], 0, 40, 'UTF-8');
    // 注文商品が複数種類存在する場合
    }else{
        // 商品名の先頭36バイト分と " etc" をセット
        $item_name = mb_strcut($first_item['product'], 0, 36, 'UTF-8') . ' etc';
    }

    $params['SCREEN_TITLE'] = $item_name;
    ////////////////////////////////////////////////////////////////
    // Suica用商品名 EOF
    ////////////////////////////////////////////////////////////////

    // 請求内容
    $bnk_contents = Registry::get('addons.oricopp_sw.bnk_contents');
    if(!empty($bnk_contents)){
        $params['CONTENTS'] = mb_strcut($bnk_contents, 0, 12, 'UTF-8');
    }

    // 請求内容カナ
    $bnk_contents_kana = Registry::get('addons.oricopp_sw.bnk_contents_kana');
    if( !empty($bnk_contents_kana) ){
        $params['CONTENTS_KANA'] = mb_strcut($bnk_contents_kana, 0, 24, 'UTF-8');
    }

    // 支払期限
    $timelimit_payment = (int)Registry::get('addons.oricopp_sw.timelimit_payment');
    if(!empty($timelimit_payment) ){
        $params['TIMELIMIT_OF_PAYMENT'] = fn_oppsw_get_timelimit($timelimit_payment);
    }

    // 支払取消期限
    $timelimit_cancel = (int)Registry::get('addons.oricopp_sw.timelimit_cancel');
    if( !empty($timelimit_cancel) ){
        $params['TIMELIMIT_OF_CANCEL'] = fn_oppsw_get_timelimit($timelimit_cancel);
    }

    // マーチャント生成ハッシュ
    $params['MERCHANTHASH'] = fn_oppsw_get_merchanthash($params);

    // SSLの設定状況に応じた戻り先URLを生成
    $location_to = (((Registry::get('settings.General.secure_checkout') == 'Y'))? Registry::get('config.https_location') : Registry::get('config.http_location'));

    // 決済完了後戻りURL
    $params['FINISH_PAYMENT_RETURN_URL'] = fn_lcjp_get_return_url('/jp_extras/oricopp_sw/process.php');

    // 未決時済戻りURL
    $params['UNIFINISH_PAYMENT_RETURN_URL'] = fn_lcjp_get_return_url('/jp_extras/oricopp_sw/process.php');

    // 決済エラー時戻りURL
    $params['ERROR_PAYMENT_RETURN_URL'] = fn_lcjp_get_return_url('/jp_extras/oricopp_sw/process.php');

    // 決済結果通知先URL
    $params['FINISH_PAYMENT_ACCESS_URL'] = fn_lcjp_get_return_url('/jp_extras/oricopp_sw/notify.php');

    // 注文番号
    $params['ORICO_ORDER_NO'] = 'SC' . $params['ORDER_ID'];

    // 会員番号（加盟店）
    $params['MEMBERSHIP_NO'] = $order_info['user_id'];

    // 本人認証有効フラグ
    $params['DDD_ENABLE_FLAG'] = Registry::get('addons.oricopp_sw.flg_auth');

    // ダミー取引フラグ
    $params['DUMMY_PAYMENT_FLAG'] = Registry::get('addons.oricopp_sw.is_dummy');

    // 単価
    $params['COMMODITY_UNIT'] = round($order_info['total']);

    // 個数（1固定）
    $params['COMMODITY_NUM'] = 1;

    ////////////////////////////////////////////////////////////////
    // 商品名 BOF
    ////////////////////////////////////////////////////////////////
    $tmp_items = $order_info['products'];

    // 最初の商品を取得
    $first_item = reset($tmp_items);

    // 商品ID（商品コードから15桁以内の半角英数とアンダースコア、ハイフンのみ抽出。商品コードがなければUNIXタイムスタンプをセット）
    $params['COMMODITY_ID'] = fn_oppsw_get_commodity_id($first_item['product_code']);

    // 注文商品が1種類のみの場合
    if( count($order_info['products']) == 1 ){
        // 商品名をセット（先頭50バイト分のみ抽出）
        $item_name = mb_strcut($first_item['product'], 0, 50, 'UTF-8');
    // 注文商品が複数種類存在する場合
    }else{
        // 商品名の先頭46バイト分と " etc" をセット
        $item_name = mb_strcut($first_item['product'], 0, 46, 'UTF-8') . ' etc';
    }

    $params['COMMODITY_NAME'] = $item_name;
    ////////////////////////////////////////////////////////////////
    // 商品名 EOF
    ////////////////////////////////////////////////////////////////

    // 商品価格合計
    $params['COMMODITY_AMOUNT'] = round($order_info['total']);

    // 配送先郵便番号
    $zip_shipping = preg_replace("/[^0-9]+/", '', $order_info['s_zipcode']);
    if( !empty($zip_shipping) && strlen($zip_shipping) == 7 ){
        $params['SHIPPING_ZIP_CODE'] = substr($zip_shipping, 0, 3) . '-' . substr($zip_shipping, 3, 4);
    }

    return $params;
}




/**
 * 商品ID(COMMODITY_ID)を取得する
 *
 * @param string $product_code
 * @return int|string
 */
function fn_oppsw_get_commodity_id($product_code = '')
{
    // 商品コードが設定されていない場合はUNIXタイムスタンプを返す
    if( empty($product_code) ) return time();

    // 商品コードから半角英数とアンダースコア、ハイフンのみ抽出
    $product_code = mb_ereg_replace('[^a-zA-Z0-9_-]', '', $product_code);

    // 商品コードが設定されていない場合はUNIXタイムスタンプを返す
    if( empty($product_code) ){
        return time();
    // 商品コードが設定されている場合は先頭15文字を返す
    }else{
        return substr($product_code, 0, OPPSW_COMMODITY_ID_MAXLEN);
    }
}




/**
 * ハッシュを生成
 *
 * @param $params
 * @return string
 */
function fn_oppsw_get_merchanthash($params)
{
    // ハッシュシードを取得
    $hash_seed = Registry::get('addons.oricopp_sw.hash_seed');

    // 文字列からハッシュ値を生成する
    $ctx = hash_init('sha512');

    $str = $hash_seed .
        "," . $params['MERCHANT_ID'] .
        "," . $params['SETTLEMENT_TYPE'] .
        "," . $params['ORDER_ID'] .
        "," . $params['AMOUNT'];

    hash_update($ctx, $str);

    $hash = hash_final($ctx, true);
    $hash = bin2hex($hash);
    return $hash;
}




/**
 * データをOricoPayment Plusに送信して暗号鍵を取得
 *
 * @param $params
 * @return array
 */
function fn_oppsw_send_request($params)
{
    // 暗号鍵およびエラーメッセージを初期化
    $merchant_key = '';
    $browser_key = '';
    $err_msg = '';

    // データをOricoPayment Plusに送信
    $result = Http::post(OPPSW_URL_POST, $params);

    // 送信結果を受信した場合
    if( !empty($result) ){
        $body_line = explode("\n", $result);
        foreach ($body_line as $line) {
            if (preg_match('/^MERCHANT_ENCRYPTION_KEY=(.+)/', $line, $match)) {
                // マーチャント暗号鍵
                $merchant_key = str_replace(array("\r\n","\r","\n"), '', $match[1]);
            } elseif (preg_match('/^BROWSER_ENCRYPTION_KEY=(.+)/', $line, $match)) {
                // ブラウザ暗号鍵
                $browser_key = str_replace(array("\r\n","\r","\n"), '', $match[1]);
            } elseif (preg_match('/^ERROR_MESSAGE=(.+)/', $line, $match)) {
                // エラーメッセージ
                $err_msg = str_replace(array("\r\n","\r","\n"), '', $match[1]);
            }
        }
    }

    // 取得した暗号鍵とエラーメッセージを返す
    return array($merchant_key, $browser_key, $err_msg);
}




/**
 * 結果コードから割り当てる注文ステータスを決定
 *
 * @param $vrc
 * @return string
 */
function fn_oppsw_get_status_to($vrc)
{
    // 結果コードの先頭1文字を取得
    $payment_method_code = substr($vrc, 0, 1);

    switch($payment_method_code){
        case 'A':   // カード決済
        case 'G':   // MPI
            return 'P';
            break;
        default:
            return 'O';
    }
}




/**
 * 注文時点からX日後の日付を取得
 *
 * @param $days
 */
function fn_oppsw_get_timelimit($days)
{
    return date("Ymd",strtotime("+" . $days . " day"));
}




/**
 * 結果コードから支払方法を判別
 *
 * @param $vrc
 * @return string
 */
function fn_oppsw_get_payment_method($vrc)
{
    // 結果コードの先頭1文字を取得
    $payment_method_code = substr($vrc, 0, 1);

    switch($payment_method_code){
        case 'A':   // カード決済
        case 'G':   // MPI
            return __('jp_oppsw_cc');
            break;
        case 'D':   // コンビニ決済
            return __('jp_oppsw_cvs');
            break;
        case 'E':   // 電子マネー決済
            return __('jp_oppsw_em');
            break;
        case 'P':   // 銀行決済
            return __('jp_oppsw_bnk');
            break;
        case 'Z':   // ショッピングクレジット決済
            return __('jp_oppsw_sc');
            break;
        default:
            return '';
    }
}




/**
 * 結果通知パラメータの内容から支払方法を取得
 *
 * @param $num
 * @param $params
 * @return bool|string
 */
function fn_oppsw_get_result_type($num, $params)
{
    if( !empty($params['cvsType' . $num]) ){
        return 'cvs';
    }elseif( !empty($params['emType' . $num]) ){
        return 'em';
    }elseif( !empty($params['kikanNo' . $num]) ){
        return 'bnk';
    }else{
        return false;
    }
}




/**
 * 支払方法に応じた結果通知内容を注文情報に記録
 *
 * @param $info
 * @param $num
 * @param $type
 * @param $params
 */
function fn_oppsw_set_result_info(&$info, $num, $type, $params)
{
    switch($type){
        // コンビニ決済
        case 'cvs':
            // CVSタイプ
            if( !empty($_REQUEST['cvsType' . $num]) ){
                // CVSタイプを取得
                $info['jp_oppsw_cvs_type'] = fn_oppsw_get_cvs_name($_REQUEST['cvsType' . $num]);
            }

            // 受付番号
            if( !empty($_REQUEST['receiptNo' . $num]) ){
                $info['jp_oppsw_receipt_no'] = $_REQUEST['receiptNo' . $num];
            }

            // 完了日時
            if( !empty($_REQUEST['receiptDate' . $num]) ){
                $info['jp_oppsw_receipt_date'] = $_REQUEST['receiptDate' . $num];
            }

            // 入金金額
            if( !empty($_REQUEST['rcvAmount' . $num]) ){
                $info['jp_oppsw_rcv_amount'] = $_REQUEST['rcvAmount' . $num];
            }
            break;

        // 電子マネー
        case 'em':
            // EMタイプ
            if( !empty($_REQUEST['emType' . $num]) ){
                // EMタイプを取得
                $info['jp_oppsw_em_type'] = fn_oppsw_get_em_name($_REQUEST['emType' . $num]);
            }

            // 完了日時
            if( !empty($_REQUEST['receiptDate' . $num]) ){
                $info['jp_oppsw_receipt_date'] = $_REQUEST['receiptDate' . $num];
            }
            break;

        // 銀行決済
        case 'bnk':
            // 収納機関コード
            if( !empty($_REQUEST['kikanNo' . $num]) ){
                $info['jp_oppsw_kikan_no'] = $_REQUEST['kikanNo' . $num];
            }

            // 収納企業コード
            if( !empty($_REQUEST['kigyoNo' . $num]) ){
                $info['jp_oppsw_kigyo_no'] = $_REQUEST['kigyoNo' . $num];
            }

            // 収納日時
            if( !empty($_REQUEST['rcvDate' . $num]) ){
                $info['jp_oppsw_rcv_date'] = $_REQUEST['rcvDate' . $num];
            }

            // お客様番号
            if( !empty($_REQUEST['customerNo' . $num]) ){
                $info['jp_oppsw_customer_no'] = $_REQUEST['customerNo' . $num];
            }

            // 確認番号
            if( !empty($_REQUEST['confNo' . $num]) ){
                $info['jp_oppsw_conf_no'] = $_REQUEST['confNo' . $num];
            }

            // 入金金額
            if( !empty($_REQUEST['rcvAmount' . $num]) ){
                $info['jp_oppsw_rcv_amount'] = $_REQUEST['rcvAmount' . $num];
            }
            break;

        case 'sc':
            // 注文番号
            if( !empty($_REQUEST['oricoOrderNo' . $num]) ){
                $info['jp_oppsw_orico_order_no'] = $_REQUEST['oricoOrderNo' . $num];
            }

            // 審査結果
            if( !empty($_REQUEST['orderStateCode' . $num]) ){
                if( $_REQUEST['orderStateCode' . $num] == '04' ){
                    $info['jp_oppsw_sc_result'] = __('jp_oppsw_sc_approved');
                }elseif( $_REQUEST['orderStateCode' . $num] == '02' ){
                    $info['jp_oppsw_sc_result'] = __('jp_oppsw_sc_denied');
                }
            }

            // 受付番号
            if( !empty($_REQUEST['receiptNo' . $num]) ){
                $info['jp_oppsw_receipt_no'] = $_REQUEST['receiptNo' . $num];
            }

            // 承認番号
            if( !empty($_REQUEST['approvalNo' . $num]) ){
                $info['jp_oppsw_approval_no'] = $_REQUEST['approvalNo' . $num];
            }

            // 申込日
            if( !empty($_REQUEST['requestDate' . $num]) ){
                $info['jp_oppsw_request_date'] = $_REQUEST['requestDate' . $num];
            }

            // ローン元金
            if( !empty($_REQUEST['loanPrincipal' . $num]) ){
                $info['jp_oppsw_loan_principal'] = $_REQUEST['loanPrincipal' . $num];
            }

            // 支払回数
            if( !empty($_REQUEST['paymentCount' . $num]) ){
                $info['jp_oppsw_payment_count'] = $_REQUEST['paymentCount' . $num];
            }
            break;

        // その他
        default:
            // do noghing
    }
}




/**
 * CVSタイプを取得
 *
 * @param $cvs_code
 * @return string
 */
function fn_oppsw_get_cvs_name($cvs_code)
{
    // CVSタイプに応じてコンビ二名を取得
    switch($cvs_code){
        // セブンイレブン
        case 'sej':
            return __('jp_cvs_se');
            break;
        // ローソン
        case 'econ-lw':
            return __('jp_cvs_ls');
            break;
        // ファミリーマート
        case 'econ-fm':
            return __('jp_cvs_fm');
            break;
        // サンクス
        case 'econ-sn':
            return __('jp_cvs_ts');
            break;
        // サークルK
        case 'econ-ck':
            return __('jp_cvs_ck');
            break;
        // ミニストップ
        case 'econ-mini':
            return __('jp_cvs_ms');
            break;
        // セイコーマート
        case 'econ-other':
            return __('jp_cvs_sm');
            break;
        // デイリーヤマザキ
        case 'other':
            return __('jp_cvs_dy');
            break;
        // その他
        default:
            return  __('others');
    }
}




/**
 * EMタイプを取得
 *
 * @param $em_code
 * @return string
 */
function fn_oppsw_get_em_name($em_code)
{
    // EMタイプに応じて電子マネー名を取得
    switch($em_code){
        // Edy
        case 'edy':
            return __('jp_oppsw_edy');
            break;
        // Suica
        case 'suica':
            return __('jp_oppsw_suica');
            break;
        // Waon
        case 'waon':
            return __('jp_oppsw_waon');
            break;
        // その他
        default:
            return  __('others');
    }
}
##########################################################################################
// END その他の関数
##########################################################################################
