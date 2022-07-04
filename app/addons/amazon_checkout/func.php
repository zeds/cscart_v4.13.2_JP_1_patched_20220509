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

// Modified by takahashi from cs-cart.jp 2018
// Amazon PayとCS-Cartの配送住所都道府県が違う場合に配送料（リアルタイム）がCS-Cartの都道府県で計算されてしまうバグを修正
// 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正

use Tygh\Registry;
use Tygh\Navigation\LastView;
use Tygh\Mailer;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * 「Amazonアカウントでお支払い」ページと通常の注文手続きページでの支払方法の表示・選択処理
 *
 * @param $cart
 * @param $cart_products
 * @param $auth
 */
function fn_amazon_checkout_calculate_cart_items(&$cart, $cart_products, $auth)
{
    // ショップフロントの場合
    if( AREA != 'A') {
        // 「Amazonアカウントでお支払い」ページの場合
        if (fn_is_enabled_amazon_checkout()) {
            $cart['calculate_shipping'] = true;
            $cart['payment_id'] = db_get_field('SELECT payment_id FROM ?:payments WHERE is_amazon_payment = ?s', 'Y');

        // 通常の注文手続きページの場合
        } else {
            $check_payment_id = db_get_field('SELECT payment_id FROM ?:payments WHERE payment_id = ?i AND status = ?s AND is_amazon_payment = ?s', $cart['payment_id'], 'A', 'N');
            if (empty($check_payment_id)) {
                $params = array(
                    'usergroup_ids' => $auth['usergroup_ids'],
                );
                $payments = fn_get_payments($params);
                $first_method = reset($payments);
                $cart['payment_id'] = $first_method['payment_id'];
            }
        }
    }
}




/**
 * 「Amazonアカウントでお支払い」ページと通常の注文手続きページでの支払方法の表示処理
 *
 * @param $params
 * @param $fields
 * @param $join
 * @param $order
 * @param $condition
 * @param $having
 */
function fn_amazon_checkout_get_payments($params, $fields, $join, $order, &$condition, $having)
{
    // ショップフロントの場合
    if (AREA == 'C') {
        // 「Amazonアカウントでお支払い」ページの場合
        if( fn_is_enabled_amazon_checkout() ){
            // 支払方法の抽出条件に「Amazon Payであること」を追加
            $condition[] = db_quote('?:payments.is_amazon_payment = ?s', 'Y');
        // 通常の注文手続きページの場合
        }else{
            // 支払方法の抽出条件に「Amazon Payでないこと」を追加
            $condition[] = db_quote('?:payments.is_amazon_payment = ?s', 'N');
        }
    }
}




/**
 * 支払方法の更新時の追加処理
 *
 * @param $payment_data
 * @param $payment_id
 * @param $lang_code
 * @param $certificate_file
 * @param $certificates_dir
 */
function fn_amazon_checkout_update_payment_pre($payment_data, $payment_id, $lang_code, $certificate_file, $certificates_dir)
{
    // Amazon Payを利用する支払方法である場合
    if( isset($payment_data['is_amazon_payment']) && $payment_data['is_amazon_payment'] == 'Y'){
        // 一旦Amazon Payの利用有無に「N（利用しない）」をセット
        db_query('UPDATE ?:payments SET is_amazon_payment = ?s', 'N');
    }
}




/**
 * 管理画面の注文一覧において各注文のAmazonリファレンスIDを取得
 *
 * @param $params
 * @param $orders
 */
function fn_amazon_checkout_get_orders_post(&$params, &$orders)
{
    // 管理画面の注文一覧の場合
    if( AREA != 'C' && Registry::get('runtime.controller') == 'orders' && Registry::get('runtime.mode') == 'manage' && is_array($orders) ){
        // 表示するすべての注文に対して処理を実施
        foreach($orders as $key => $order){
            // 注文の支払情報を取得
            $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order['order_id']);

            // 注文の支払情報が存在する場合
            if( !empty($payment_info) ){
                // 支払情報が暗号化されている場合は復号化して変数にセット
                if( !is_array($payment_info)) {
                    $payment_info = @unserialize(fn_decrypt_text($payment_info));
                }

                // AmazonリファレンスID が存在する場合
                if( !empty($payment_info['amazon_authorization_id']) ){
                    // AmazonリファレンスID を注文情報にセット
                    $orders[$key]['lpa_auth_id'] = $payment_info['amazon_authorization_id'];
                }
            }
        }
    }
}




/**
 * ログイン済ユーザーがAmazon Payで決済する際に、アドレスウィジェットで選択した住所情報が無視され、
 * CS-Cartに登録済みの配送先住所をベースに送料が計算される不具合を回避
 * これは、app/controllers/frontend/checkout.php の GETリクエストにおける $mode == 'checkout' の処理で
 * ログイン済みのユーザーは $cart['user_data'] = fn_get_user_info($auth['user_id'], empty($_REQUEST['profile']), $cart['profile_id']);
 * で Tygh::$app['session']['cart']['user_data'] の値が書き換えられるために発生する
 * そのため、再度アドレスウィジェットで選択した住所情報を取得し、fn_get_user_info で取得した値を上書きする必要がある。
 * FIXME :
 * 本来は他の住所情報も書き戻したほうがよいが、Amazonから完全な住所情報を取得できない模様。
 * （都道府県、国名、郵便番号くらいしか取得できていない）
 * とりあえず国名と都道府県で送料計算はできるため、とりあえず現在の仕様とする
 *
 * @param $user_id
 * @param $get_profile
 * @param $profile_id
 * @param $user_data
 */
function fn_amazon_checkout_get_user_info(&$user_id, &$get_profile, &$profile_id, &$user_data)
{
    // コントローラー名、モード名、アクション名を取得
    $_controller = Registry::get('runtime.controller');
    $_mode = Registry::get('runtime.mode');
    $_action = Registry::get('runtime.action');

    // Amazon Payを利用して注文を行う場合
    //////////////////////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 BOF
    // Amazon PayとCS-Cartの配送住所都道府県が違う場合に配送料（リアルタイム）がCS-Cartの都道府県で計算されてしまうバグを修正
    ///////////////////////////////////////////////////////////////
    if( AREA == 'C' && $_controller == 'checkout' && $_mode == 'checkout' && ($_action == 'amazon_checkout' || $_action === '') && !empty(Tygh::$app['session']['cart']['user_data']['s_state']) ) {
    //////////////////////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2018 EOF
    ///////////////////////////////////////////////////////////////

        if( fn_is_enabled_amazon_checkout() && !empty(Tygh::$app['session']['amazon_order_reference_id']) ){
            // Amazon Payのクライアントのインスタンスを作成
            $client = fn_get_client_exemplar();

            // Amazon Payのクライアントのインスタンスが存在する場合、Amazonで管理している配送先住所情報をCS-Cartで利用可能な形に変換
            if ($client !== false) {
                $requestParameters = array(
                    'address_consent_token' => null,
                    'amazon_order_reference_id' => Tygh::$app['session']['amazon_order_reference_id']
                );
                $response = $client->getOrderReferenceDetails($requestParameters);
                fn_prepare_amazon_shipping_address($response, $user_data);
            }
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
 * 【メモ】 アドオンのインストール前に実行される処理なので文言はハードコード
 */
function fn_amznlap_addon_before_install()
{
    fn_lcjp_install('amazon_checkout');

    // エラーフラグを初期化
    $is_error = false;

    // エラータイトルをセット
    $error_title = "「Amazon Pay」アドオン インストールエラー";

    // ショップフロント全体にHTTPS通信が適用されていない場合はインストールを許可しない
    if( Registry::get('settings.Security.secure_storefront') != 'Y' ){
        $is_error = true;
        fn_set_notification('E', $error_title, "<br /><strong><i>基本設定 -> セキュリティ設定 -> ショップフロントにおけるSSL通信の適用</i></strong><br /> に 「<strong>ショップフロント全体に適用</strong>」 が指定されていないためインストールできません。");
    }

    // 注文手続き時に「配送先住所」を先に表示しない場合はインストールを許可しない
    if( Registry::get('settings.Checkout.address_position') != 'shipping_first' ){
        $is_error = true;
        fn_set_notification('E', $error_title, "<br /><strong><i>基本設定 -> 注文手続き -> 住所情報の表示順</i></strong><br /> に 「<strong>配送先住所を先に表示</strong>」 が指定されていないためインストールできません。");
    }

    // エラーが発生している場合
    if($is_error){
        // インストールは行わずにアドオン一覧ページにリダイレクト
        fn_redirect('addons.manage', true);
    }
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################





##########################################################################################
// START その他の関数
##########################################################################################

/**
 * アドオンの各種設定内容を取得
 *
 * @return array
 */
function fn_prepare_pay_with_amazon_config()
{
    $params = Registry::get('addons.amazon_checkout');
    $config = array(
        'merchant_id'   => $params['merchant_id'],
        'access_key'    => $params['access_key'],
        'secret_key'    => $params['access_secret'],
        'client_id'     => $params['client_id'],
        'region'        => $params['region'],
        'currency_Code' => CART_PRIMARY_CURRENCY,
        'sandbox'       => ($params['test_mode'] == 'Y') ? true : false,
    );
    return $config;
}




/**
 * Amazon Payのクライアントのインスタンスを作成
 *
 * @return bool
 */
function fn_get_client_exemplar()
{
    // アドオンの各種設定内容を取得
    $config = fn_prepare_pay_with_amazon_config();

    // Amazon Payのクライアントクラス
    $class_name = '\Tygh\PayWithAmazon\Client';

    // Amazon Payのクライアントクラスが存在する場合
    if( class_exists($class_name) ){
        // インスタンスを作成
        $client = new $class_name($config);
        return $client;
    } 
    return false;
}




/**
 * Amazonで管理している配送先住所情報をCS-Cartで利用可能な形に変換
 *
 * @param $response
 * @param $user_data
 * @return bool
 */
///////////////////////////////////////////////////////////////////////////////////////////////////
// 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 BOF
///////////////////////////////////////////////////////////////////////////////////////////////////
function fn_prepare_amazon_shipping_address($response, &$user_data, $new_user = false)
///////////////////////////////////////////////////////////////////////////////////////////////////
// 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 EOF
///////////////////////////////////////////////////////////////////////////////////////////////////
{
    // Amazonからのレスポンスをjson形式にデコード
    $json = json_decode($response->toJson());

    // 配送先住所情報を取得
    $destination_type = $json->GetOrderReferenceDetailsResult->OrderReferenceDetails->Destination->DestinationType;
    $destination = $destination_type . 'Destination';
    $amazon_address = $json->GetOrderReferenceDetailsResult->OrderReferenceDetails->Destination->$destination;

    // Amazonの配送先住所情報に市区町村フィールドがセットされている場合
    if( isset($amazon_address->City) ){
        // CS-Cartの「市区町村」に値をセット
        $user_data['s_city'] = $amazon_address->City;

        // 「住所1」がセットされている場合にCS-Cartの「番地」フィールドにセット
        if( isset($amazon_address->AddressLine1) ){
            $user_data['s_address'] = $amazon_address->AddressLine1;
        }

        // 「住所2」がセットされている場合にCS-Cartの「ビル・建物名」フィールドにセット
        if( isset($amazon_address->AddressLine2) ){
            $user_data['s_address_2'] = $amazon_address->AddressLine2;
        }

    // Amazonの配送先住所情報に市区町村フィールドがセットされていない場合
    }else{
        // 「住所1」がセットされている場合にCS-Cartの「市区町村」フィールドにセット
        if( isset($amazon_address->AddressLine1) ){
            $user_data['s_city'] = $amazon_address->AddressLine1;
        }
        // 「住所2」がセットされている場合にCS-Cartの「番地」フィールドにセット
        if( isset($amazon_address->AddressLine2) ){
            $user_data['s_address'] = $amazon_address->AddressLine2;
        }
        // 「会社名」がセットされている場合にCS-Cartの「ビル・建物名」フィールドにセット
        if( isset($amazon_address->AddressLine3) ){
            $user_data['s_address_2'] = $amazon_address->AddressLine3;
        }
    }

    // Amazonの配送先住所情報に国名がセットされている場合
    if( isset($amazon_address->CountryCode) ){
        // CS-Cartの「国名」に値をセット
        $user_data['s_country'] = $amazon_address->CountryCode;
    }
    // Amazonの配送先住所情報に郵便番号がセットされている場合
    if( isset($amazon_address->PostalCode) ){
        // CS-Cartの「郵便番号」に値をセット
        $user_data['s_zipcode'] = $amazon_address->PostalCode;
    }
    // Amazonの配送先住所情報に都道府県がセットされている場合
    if( isset($amazon_address->StateOrRegion) ){
        $user_data['s_state'] = $amazon_address->StateOrRegion;
    }
    if( isset($amazon_address->Phone) ){
        $user_data['s_phone'] = $amazon_address->Phone;
    }

    // Amazonの配送先住所情報に配送先氏名がセットされている場合
    if( isset($amazon_address->Name) ){
        // 配送先氏名を変数にセット
        $name = $amazon_address->Name;

        // 全角スペースは半角スペースに変換
        $name = str_replace('　', ' ', $name);

        // 氏名に半角スペースが含まれる場合
        if( strpos($name, ' ') !== false ){
            // 半角スペースをデリミタとして氏名を姓と名に分割
            list($user_data['s_firstname'], $user_data['s_lastname']) = preg_split("/[\s]{1}/", $name, PREG_SPLIT_DELIM_CAPTURE);

        // 氏名に半角スペースが含まれる場合
        }else{
            // 姓と名フィールドにそれぞれ配送先氏名をセット
            $user_data['s_firstname'] = $user_data['s_lastname'] = $name;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////
    // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 BOF
    ///////////////////////////////////////////////////////////////////////////////////////////////////
    // ログイン済みでない場合、Amazonで利用しているEメールアドレスをCS-Cartで利用するEメールアドレスとして扱う
    if( !Tygh::$app['session']['auth']['user_id'] || $new_user){
    ///////////////////////////////////////////////////////////////////////////////////////////////////
    // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 EOF
    ///////////////////////////////////////////////////////////////////////////////////////////////////
        if( isset($json->GetOrderReferenceDetailsResult->OrderReferenceDetails->Buyer->Email) ){
            $user_data['email'] = $json->GetOrderReferenceDetailsResult->OrderReferenceDetails->Buyer->Email;
        }

        //請求先住所を設定
        $user_data['b_firstname'] = $user_data['s_firstname'];
        $user_data['b_lastname'] = $user_data['s_lastname'];
        $user_data['b_country'] = $user_data['s_country'];
        $user_data['b_zipcode'] = $user_data['s_zipcode'];
        $user_data['b_state'] = $user_data['s_state'];
        $user_data['b_city'] = $user_data['s_city'];
        $user_data['b_address'] = $user_data['s_address'];
        $user_data['b_address_2'] = $user_data['s_address_2'];
        $user_data['b_phone'] = $user_data['s_phone'];
    }

    return true;
}




/**
 * 配送先住所を更新
 *
 * @param $order_id
 * @param $user_data
 * @return bool
 *
 */
function fn_update_order_shipping_info($order_id, $user_data)
{
    db_query('UPDATE ?:orders SET ?u WHERE order_id = ?i', $user_data, $order_id);
    return true;
}




/**
 * Amazon Payによる支払いの有効/無効を判定
 *
 * @return bool
 */
function fn_is_enabled_amazon_checkout()
{
    return (!empty(Tygh::$app['session']['show_amazon_checkout'])) ? true : false;
}




/**
 * セッションに格納された商品情報を削除
 *
 * @return bool
 */
function fn_delete_user_session_products()
{
    db_query("DELETE FROM ?:user_session_products WHERE session_id = ?s AND type = ?s AND user_type = ?s", Tygh::$app['session']->getId(), 'C', 'U');
    return true;
}




/**
 * Amazonアカウントのデータを用いて会員登録した場合に会員登録完了メールを送信
 *
 * @param $user_id
 * @param $password
 */
function fn_amazon_checkout_send_new_user_notification($user_id, $password) 
{
    $user_data = fn_get_user_info($user_id, true);
    Mailer::sendMail(array(
        'to' => $user_data['email'],
        'from' => 'company_users_department',
        'data' => array(
            'password' => $password,
            'user_data' => $user_data,
            'amazon_checkout' => 'Y',
        ),
        'tpl' => 'profiles/create_profile.tpl',
        'company_id' => $user_data['company_id']
    ), 'C', CART_LANGUAGE);
}
##########################################################################################
// END その他の関数
##########################################################################################
