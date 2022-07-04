<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

// Modified by tommy from cs-cart.jp 2016

use Tygh\Registry;

if ( !defined('BOOTSTRAP') ) { die('Access denied'); }

// 注文ステータスを格納する変数を初期化
$pp_response = array(
    'order_status' => 'F'
);

// Amazon Payのクライアントのインスタンスを初期化
$client = fn_get_client_exemplar();

// Amazon Payのクライアントのインスタンスが存在するか、またはリクエストにAmazonリファレンスIDが含まれない場合
if( $client !== false || !isset(Tygh::$app['session']['amazon_order_reference_id']) || empty(Tygh::$app['session']['amazon_order_reference_id']) ){

    // アドオンの各種設定値を取得
    $params = Registry::get('addons.amazon_checkout');

    // 販売事業者リファレンスID のプリフィックスを初期化
    $auth_id_prefix = '';

    // 販売事業者リファレンスID のプリフィックスが存在する場合
    if( !empty($params['auth_id_prefix']) ){
        // 設定値から半角英数とアンダースコア、ハイフンのみ抽出{
        $auth_id_prefix = mb_ereg_replace('[^a-zA-Z0-9_-]', '', $params['auth_id_prefix']);
    }

    // 販売事業者リファレンスID のプリフィックスが存在する場合
    if( !empty($auth_id_prefix) ){
        // プリフィックス付きの販売事業者リファレンスID をセット
        $authorization_reference_id = $auth_id_prefix . '_'  . $order_info['order_id'] . '_'  . (string)time();
    // 販売事業者リファレンスID のプリフィックスが存在しない場合
    }else{
        // プリフィックスなしの販売事業者リファレンスID をセット
        $authorization_reference_id = $order_info['order_id'] . '_'  . (string)time();
    }

    // 変数にセットされた販売事業者リファレンスIDのうち、Amazonが受け付ける最大長である32文字を末尾から取得
    // 【メモ】先頭から32文字取得するとプリセットや注文番号の桁数が大きい場合に重複する可能性がある。
    // 末尾10桁はUNIXタイムスタンプのため重複する可能性は低いため、末尾から取得する
    $authorization_reference_id = substr($authorization_reference_id, -32);

    // Amazon Payを利用して与信を実行
    $response = $client->authorize(array(
        'amazon_order_reference_id' => Tygh::$app['session']['amazon_order_reference_id'],
        'mws_auth_token' => null,
        'authorization_amount' => $order_info['total'],
        'authorization_reference_id' => $authorization_reference_id,
        'transaction_timeout' => 0,
        'capture_now' => ($params['capture_now'] == 'Y') ? true : false,
        'soft_descriptor' => null
    ));

    // 商品の配送先住所を更新
    if( !empty(Tygh::$app['session']['order_shipping_info']) ){
        fn_update_order_shipping_info($order_info['order_id'], Tygh::$app['session']['order_shipping_info']);
        unset(Tygh::$app['session']['order_shipping_info']);
    }

    // 与信結果を変数にセット
    $responsearray = json_decode($response->toJson());

    // 与信処理が完了した場合
    if( $client->success ){
        // 注文情報に各種パラメータをセット
        $pp_response['amazon_authorization_id'] = $responsearray->AuthorizeResult->AuthorizationDetails->AmazonAuthorizationId;
        $pp_response['authorization_amount'] = $responsearray->AuthorizeResult->AuthorizationDetails->AuthorizationAmount->Amount;
        $pp_response['captured_amount'] = $responsearray->AuthorizeResult->AuthorizationDetails->CapturedAmount->Amount;
        $pp_response['expiration_timestamp'] = $responsearray->AuthorizeResult->AuthorizationDetails->ExpirationTimestamp;
        $pp_response['authorization_status'] = $responsearray->AuthorizeResult->AuthorizationDetails->AuthorizationStatus->State;
        $pp_response['authorization_fee'] = $responsearray->AuthorizeResult->AuthorizationDetails->AuthorizationFee->Amount;
        $pp_response['capture_now'] = $responsearray->AuthorizeResult->AuthorizationDetails->CaptureNow;
        $pp_response['creation_timestamp'] = $responsearray->AuthorizeResult->AuthorizationDetails->CreationTimestamp;
        $pp_response['authorization_reference_id'] = $responsearray->AuthorizeResult->AuthorizationDetails->AuthorizationReferenceId;

        // 注文ステータスに「P（支払い確認済み）」をセット
        if (($params['capture_now'] != 'Y' && $pp_response['authorization_status'] == 'Open') || ($params['capture_now'] == 'Y' && $pp_response['authorization_status'] == 'Closed')) {
            $pp_response['order_status'] = 'P';
        }

        // 与信結果が「Declined（与信不可）」の場合
        if( $pp_response['authorization_status'] == 'Declined' ){
            // エラーメッセージを表示してAmazon Pay専用の注文手続きページに遷移
            fn_set_notification('E', __("error"), __("amzn_auth_declined"));
            $return_url = fn_url("checkout.checkout.amazon_checkout&check_amount=1", 'C', 'current');
            fn_redirect($return_url, true);
        }

    // 与信処理でエラーが発生した場合
    }else{
        // エラーメッセージを取得
        $err_msg = $responsearray->Error->Message;

        // エラーメッセージが存在する場合
        if( !empty($err_msg) ){
            // エラーメッセージを表示してAmazon Pay専用の注文手続きページに遷移
            fn_set_notification('E', __("error"), $err_msg);
            $return_url = fn_url("checkout.checkout.amazon_checkout&check_amount=1", 'C', 'current');
            fn_redirect($return_url, true);
        }
    }
}

// Amazon Pay用スクリプトを使って決済が行われた場合、注文確定処理を実施
if( fn_check_payment_script('pay_with_amazon.php', $order_info['order_id']) ){
    $idata = array (
        'order_id' => $order_info['order_id'],
        'type' => 'S',
        'data' => TIME,
    );
    db_query("REPLACE INTO ?:order_data ?e", $idata);
    fn_finish_payment($order_info['order_id'], $pp_response);
    fn_order_placement_routines('route', $order_info['order_id']);
}