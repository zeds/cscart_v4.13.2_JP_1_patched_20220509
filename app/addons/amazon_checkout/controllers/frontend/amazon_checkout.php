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
// Modified by takahashi from cs-cart.jp 2019
// 複数ショップ時に同じEメールのユーザーで決済できない問題を修正

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// POSTリクエストの場合
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // お客様がAmazonで利用しているEメールに関するバリデーションの場合
    if( $mode == 'check_email' ){
        // リクエストにEメールアドレスが含まれる場合
        if( isset($_REQUEST['email']) ){
            ///////////////////////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 BOF
            // 複数ショップ時に同じEメールのユーザーで決済できない問題を修正
            ///////////////////////////////////////////////////////////////
            // 当該Eメールアドレスを使用した会員の会員IDを取得
            $user_id = db_get_field('SELECT user_id FROM ?:users WHERE email = ?s AND company_id = ?i', $_REQUEST['email'], Registry::get('runtime.company_id'));
            ///////////////////////////////////////////////////////////////
            // Modified by takahashi from cs-cart.jp 2019 EOF
            ///////////////////////////////////////////////////////////////

            // 当該Eメールアドレスを使用した会員が存在する場合
            if( $user_id ){
                // ログイン中の会員のIDと当該Eメールアドレスを使用した会員IDが異なる場合
                if( $auth['user_id'] && $auth['user_id'] != $user_id ){
                    // 異なるユーザーの情報を利用して決済しようとしているためエラーとする
                    Tygh::$app['ajax']->assign('force_redirection', fn_url('checkout.cart?error_user=wrong_user'));
                // ログインしていない場合
                }elseif( !$auth['user_id'] ){
                    // 当該Eメールアドレスを使用した会員のステータスを取得
                    $user_status = fn_login_user($user_id);
                    // 当該Eメールアドレスを使用した会員のステータスが無効な場合
                    if( $user_status == LOGIN_STATUS_USER_DISABLED ){
                        // 無効化されたユーザーの情報を利用して決済しようとしているためエラーとする
                        Tygh::$app['ajax']->assign('force_redirection', fn_url('checkout.cart?error_user=disabled_user'));
                    }
                    // 「Amazonアカウントでお支払い」ページにリダイレクト
                    return array(CONTROLLER_STATUS_OK, 'checkout.checkout.amazon_checkout');
                }
            }
        }
        exit;

    // 注文情報の作成の場合
    }elseif( $mode == 'create_order_reference' ){
        // Ajaxリクエストの場合
        if( defined('AJAX_REQUEST') ){
            // Amazon Payのクライアントのインスタンスを作成
            $client = fn_get_client_exemplar();

            // Amazon Payのクライアントのインスタンスが存在するか、またはリクエストにAmazonリファレンスIDが含まれない場合
            if( $client !== false || !isset($_REQUEST['orderReferenceId']) || empty($_REQUEST['orderReferenceId']) ){
                // Amazonに送信するパラメータをセット
                $requestParameters = array(
                    'address_consent_token' => null,
                    'amazon_order_reference_id' => $_REQUEST['orderReferenceId']
                );
                // Amazonの注文リファレンスの詳細を取得
                $response = $client->getOrderReferenceDetails($requestParameters);

                // セッションにAmazonリファレンスIDをセット
                Tygh::$app['session']['amazon_order_reference_id'] = $_REQUEST['orderReferenceId'];

                // Amazonで管理している配送先住所情報をCS-Cartで利用可能な形に変換
                fn_prepare_amazon_shipping_address($response, Tygh::$app['session']['cart']['user_data']);

                // 「Amazonアカウントでお支払い」ページにリダイレクト
                return array(CONTROLLER_STATUS_OK, 'checkout.checkout.amazon_checkout');

            // その他の場合
            }else{
                // カートの内容ページにリダイレクト
                Tygh::$app['ajax']->assign('force_redirection', fn_url('checkout.cart'));
            }
            exit();

        // Ajaxリクエストではない場合
        }else{
            // カートの内容ページにリダイレクト
            return array(CONTROLLER_STATUS_REDIRECT, 'checkout.cart');
        }

    // 配送先住所の変更の場合
    }elseif( $mode == 'change_address' ){
        // Ajaxリクエストの場合
        if( defined('AJAX_REQUEST') ){
            // Amazon Payのクライアントのインスタンスを作成
            $client = fn_get_client_exemplar();

            // Amazon Payのクライアントのインスタンスが存在するか、またはリクエストにAmazonリファレンスIDが含まれない場合
            if( $client !== false || !isset(Tygh::$app['session']['amazon_order_reference_id']) || empty(Tygh::$app['session']['amazon_order_reference_id']) ){
                // Amazonに送信するパラメータをセット
                $requestParameters = array(
                    'address_consent_token' => null,
                    'amazon_order_reference_id' => Tygh::$app['session']['amazon_order_reference_id']
                );

                // Amazonの注文リファレンスの詳細を取得
                $response = $client->getOrderReferenceDetails($requestParameters);

                // Amazonで管理している配送先住所情報をCS-Cartで利用可能な形に変換
                fn_prepare_amazon_shipping_address($response, Tygh::$app['session']['cart']['user_data']);

                // 「Amazonアカウントでお支払い」ページにリダイレクト
                return array(CONTROLLER_STATUS_OK, 'checkout.checkout.amazon_checkout');

            // その他の場合
            }else{
                // カートの内容ページにリダイレクト
                Tygh::$app['ajax']->assign('force_redirection', fn_url('checkout.cart'));
            }
            exit();

        // Ajaxリクエストではない場合
        }else{
            // カートの内容ページにリダイレクト
            return array(CONTROLLER_STATUS_REDIRECT, 'checkout.cart');
        }
    }
}
