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
// Modified by takahashi from cs-cart.jp 2018
// お客様コメントが管理画面に表示されないバグを修正
// 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正

// Modified by takahashi from cs-cart.jp 2019
// 送料無料キャンペーン金額が「Amazonアカウントのデータを用いて会員登録」時にAmazon Payの合計注文金額に適用されないバグを修正

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// POSTリクエストの場合
if( $_SERVER['REQUEST_METHOD'] == 'POST' ){

    // 注文の実行の場合
    if( $mode == 'place_order' ){
        // 「Amazonアカウントでお支払い」の場合
        if( $action == 'amazon_checkout'){

            // カートに関するセッション変数が存在する場合
            if( Tygh::$app['session']['cart'] ){
                // カートに関するデータがPOSTされた場合
                if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
                    // 配送方法が指定された場合
                    if( isset($_REQUEST['shipping_ids']) ){
                        // 指定された配送方法のIDをセット
                        foreach($_REQUEST['shipping_ids'] as $_group_key => $_shipping_id){
                            // 指定された配送方法においてお届け時間帯が指定されている場合
                            if( isset($_REQUEST['delivery_time_selected_' . $_group_key . '_' . $_shipping_id]) ){
                                Tygh::$app['session']['delivery_time_selected'][$_group_key][$_shipping_id] = $_REQUEST['delivery_time_selected_' . $_group_key . '_' . $_shipping_id];
                            }else{
                                // 指定された配送方法においてお届け時間帯が指定されていない場合 お届け時間帯用のセッション変数を解放する
                                unset(Tygh::$app['session']['delivery_time_selected'][$_group_key][$_shipping_id]);
                            }
                            // 指定された配送方法においてお届け希望日が指定されている場合
                            if( isset($_REQUEST['delivery_date_selected_' . $_group_key . '_' . $_shipping_id]) ){
                                Tygh::$app['session']['delivery_date_selected'][$_group_key][$_shipping_id] = $_REQUEST['delivery_date_selected_' . $_group_key . '_' . $_shipping_id];
                            }else{
                                // 指定された配送方法においてお届け希望日が指定されていない場合 配送日のセッション変数を解放する
                                unset(Tygh::$app['session']['delivery_date_selected'][$_group_key][$_shipping_id]);
                            }
                        }
                    }
                }
            }

            // 処理結果を格納する変数を初期化
            $result = false;

            // Amazon Payのクライアントのインスタンスを作成
            $client = fn_get_client_exemplar();

            // Amazon Payのクライアントのインスタンスが存在するか、またはリクエストにAmazonリファレンスIDが含まれない場合
            if( $client !== false || !isset(Tygh::$app['session']['amazon_order_reference_id']) || empty(Tygh::$app['session']['amazon_order_reference_id']) ){

                ///////////////////////////////////////////////////////////////////////////////////////////////////
                // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 BOF
                ///////////////////////////////////////////////////////////////////////////////////////////////////
                // 非ログインユーザーを判定するフラグを初期化
                $new_user = false;

                // 会員ログインしていない場合
                if( empty($auth['user_id']) ){
                    // 非ログインユーザーと見なす
                    $new_user = true;
                }

                // 非ログインユーザーでかつ、「Amazonアカウントのデータを用いて会員登録」がチェックされている場合
                if( $new_user && isset($_REQUEST['register']) && $_REQUEST['register'] == 'Y' ){

                    // ダミー会員データ
                    $user_data = array(
                        'firstname' => '性',
                        'lastname' => '名',
                        'email' => 'xxx@xxx.xxx'
                    );

                    // ダミー会員登録処理
                    list($user_id, $profile_id) = fn_update_user(0, $user_data, $auth, false, false);

                    // ダミー会員でログイン
                    fn_login_user($user_id);

                    ///////////////////////////////////////////////////////////////////////////////////////////////////
                    // 送料無料キャンペーン金額が「Amazonアカウントのデータを用いて会員登録」時にAmazon Payの合計注文金額に適用されないバグを修正 BOF
                    ///////////////////////////////////////////////////////////////////////////////////////////////////
                    // Amazonに送信するパラメータをセット
                    $requestParameters = array(
                        'address_consent_token' => null,
                        'amazon_order_reference_id' => Tygh::$app['session']['amazon_order_reference_id']
                    );

                    // Amazonの注文リファレンスの詳細を取得
                    $response = $client->getOrderReferenceDetails($requestParameters);

                    // Amazonで管理している配送先住所情報をCS-Cartで利用可能な形に変換
                    fn_prepare_amazon_shipping_address($response, Tygh::$app['session']['cart']['user_data']);

                    fn_calculate_cart_content(Tygh::$app['session']['cart'], $auth);
                    ///////////////////////////////////////////////////////////////////////////////////////////////////
                    // 送料無料キャンペーン金額が「Amazonアカウントのデータを用いて会員登録」時にAmazon Payの合計注文金額に適用されないバグを修正 EOF
                    ///////////////////////////////////////////////////////////////////////////////////////////////////
                }
                ///////////////////////////////////////////////////////////////////////////////////////////////////
                // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 EOF
                ///////////////////////////////////////////////////////////////////////////////////////////////////

                // 注文に関する各種データをAmazonに送信
                $response = $client->setOrderReferenceDetails(array(
                    'amount' => Tygh::$app['session']['cart']['total'],
                    'currency_code' => strtoupper(CART_PRIMARY_CURRENCY),
                    'amazon_order_reference_id' => Tygh::$app['session']['amazon_order_reference_id'],
                    'platform_id' => 'AVJINOL9U0ZXF',
                    'store_name' => Registry::get('settings.Company.company_name')
                ));

                // Amazonに送信した注文データに問題がないか確認
                $response = $client->confirmOrderReference(array(
                    'amazon_order_reference_id' => Tygh::$app['session']['amazon_order_reference_id'],
                    'mws_auth_token' => null
                ));

                // Amazonにおける注文処理が正常に終了している場合
                if( $client->success ){
                    // 処理結果を格納する変数にtrueをセット
                    $result = true;

                    // Amazonにおける注文情報を取得
                    $response = $client->getOrderReferenceDetails(array(
                        'address_consent_token' => null,
                        'amazon_order_reference_id' => Tygh::$app['session']['amazon_order_reference_id']
                    ));

                    // ユーザー情報を初期化
                    $user_data = array();

                    ///////////////////////////////////////////////////////////////////////////////////////////////////
                    // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 BOF
                    ///////////////////////////////////////////////////////////////////////////////////////////////////
                    // Amazonで管理している配送先住所情報をCS-Cartで利用可能な形に変換
                    fn_prepare_amazon_shipping_address($response, $user_data, $new_user);
                    ///////////////////////////////////////////////////////////////////////////////////////////////////
                    // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 EOF
                    ///////////////////////////////////////////////////////////////////////////////////////////////////

                    // 非ログインユーザーでかつ、「Amazonアカウントのデータを用いて会員登録」がチェックされている場合
                    if( $new_user && isset($_REQUEST['register']) && $_REQUEST['register'] == 'Y' ){
                        // ログインパスワードを生成
                        $user_data['password1'] = $user_data['password2'] = $password = fn_generate_password();

                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // 連絡先・請求先・配送先の姓名フリガナフィールドに固定値をセット BOF
                        // 【メモ】 Amazonでは配送先住所の姓名フリガナフィールドが存在しない
                        // そのため、Amazonのアカウント情報を用いてCS-Cartの会員登録を行う場合は姓名フリガナフィールドに
                        // 固定値（セイフリガナ、メイフリガナ）をセットする
                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // 日本語版アドオンのステータスを取得
                        $lcjp_addon_status = Registry::get('addons.localization_jp.status');

                        // 日本語版アドオンが有効化されている場合
                        if( !empty($lcjp_addon_status) &&  $lcjp_addon_status == 'A'){
                            // 連絡先、請求先、配送先の姓名フリガナフィールドのIDを取得
                            $familyname_kana_c = (int)Registry::get('addons.localization_jp.jp_familyname_kana_c');
                            $firstname_kana_c = (int)Registry::get('addons.localization_jp.jp_firstname_kana_c');
                            $familyname_kana_b = (int)Registry::get('addons.localization_jp.jp_familyname_kana_b');
                            $firstname_kana_b = (int)Registry::get('addons.localization_jp.jp_firstname_kana_b');
                            $familyname_kana_s = (int)Registry::get('addons.localization_jp.jp_familyname_kana_s');
                            $firstname_kana_s = (int)Registry::get('addons.localization_jp.jp_firstname_kana_s');

                            // 連絡先、請求先、配送先の姓名フリガナフィールドの設定情報を取得
                            $familyname_kana_c_data = db_get_row("SELECT * FROM ?:profile_fields WHERE field_type = ?s AND section = ?s AND field_id = ?s", 'I', 'C', $familyname_kana_c);
                            $firstname_kana_c_data = db_get_row("SELECT * FROM ?:profile_fields WHERE field_type = ?s AND section = ?s AND field_id = ?s", 'I', 'C', $firstname_kana_c);
                            $familyname_kana_b_data = db_get_row("SELECT * FROM ?:profile_fields WHERE field_type = ?s AND section = ?s AND field_id = ?s", 'I', 'B', $familyname_kana_b);
                            $firstname_kana_b_data = db_get_row("SELECT * FROM ?:profile_fields WHERE field_type = ?s AND section = ?s AND field_id = ?s", 'I', 'B', $firstname_kana_b);
                            $familyname_kana_s_data = db_get_row("SELECT * FROM ?:profile_fields WHERE field_type = ?s AND section = ?s AND field_id = ?s", 'I', 'S', $familyname_kana_s);
                            $firstname_kana_s_data = db_get_row("SELECT * FROM ?:profile_fields WHERE field_type = ?s AND section = ?s AND field_id = ?s", 'I', 'S', $firstname_kana_s);

                            // 連絡先住所の姓フリガナを表示する設定の場合
                            if( $familyname_kana_c_data['profile_show'] == 'Y' || $familyname_kana_c_data['checkout_show'] == 'Y' ){
                                // 連絡先住所の姓フリガナに固定値（セイフリガナ）をセット
                                $user_data['fields'][$familyname_kana_c] = __("amzn_familyname_kana");
                            }

                            // 連絡先住所の名フリガナを表示する設定の場合
                            if( $firstname_kana_c_data['profile_show'] == 'Y' || $firstname_kana_c_data['checkout_show'] == 'Y' ){
                                // 連絡先住所の名フリガナに固定値（名フリガナ）をセット
                                $user_data['fields'][$firstname_kana_c] = __("amzn_firstname_kana");
                            }

                            // 請求先住所の姓フリガナを表示する設定の場合
                            if( $familyname_kana_b_data['profile_show'] == 'Y' || $familyname_kana_b_data['checkout_show'] == 'Y' ){
                                // 請求先住所の姓フリガナに固定値（セイフリガナ）をセット
                                $user_data['fields'][$familyname_kana_b] = __("amzn_familyname_kana");
                            }

                            // 請求先住所の名フリガナを表示する設定の場合
                            if( $firstname_kana_b_data['profile_show'] == 'Y' || $firstname_kana_b_data['checkout_show'] == 'Y' ){
                                // 請求先住所の名フリガナに固定値（名フリガナ）をセット
                                $user_data['fields'][$firstname_kana_b] = __("amzn_firstname_kana");
                            }

                            // 配送先住所の姓フリガナを表示する設定の場合
                            if( $familyname_kana_s_data['profile_show'] == 'Y' || $familyname_kana_s_data['checkout_show'] == 'Y' ){
                                // 配送先住所の姓フリガナに固定値（セイフリガナ）をセット
                                $user_data['fields'][$familyname_kana_s] = __("amzn_familyname_kana");
                            }

                            // 配送先住所の名フリガナを表示する設定の場合
                            if( $firstname_kana_s_data['profile_show'] == 'Y' || $firstname_kana_s_data['checkout_show'] == 'Y' ){
                                // 配送先住所の名フリガナに固定値（名フリガナ）をセット
                                $user_data['fields'][$firstname_kana_s] = __("amzn_firstname_kana");
                            }
                        }
                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // 連絡先・請求先・配送先の姓名フリガナフィールドに固定値をセット EOF
                        ///////////////////////////////////////////////////////////////////////////////////////////////////

                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 BOF
                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // 新規会員登録処理が正常終了した場合
                        if( list($user_id, $profile_id) = fn_update_user($user_id, $user_data, $auth, false, false) ){
                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // 会員登録済用キャンペーン金額がAmazon Payの合計注文金額に適用されないバグを修正 EOF
                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                            // Amazonアカウントのデータを用いて会員登録した場合に会員登録完了メールを送信
                            fn_amazon_checkout_send_new_user_notification($user_id, $password);

                            // セッションに格納された商品情報を削除
                            fn_delete_user_session_products();
                            // 商品の購入に関するセッション変数の内容をユーザーIDに紐付け
                            fn_save_cart_content(Tygh::$app['session']['cart'], $user_id);
                            // 新規登録した会員としてログイン
                            fn_login_user($user_id);

                        // 新規会員登録処理に失敗した場合
                        }else{
                            // 処理結果を格納する変数にfalseをセット
                            $result = false;
                        }
                    }

                    // 処理が正常終了している場合
                    if( $result ){
                        // パラメータに配送情報をセット
                        $params = array(
                            'shipping_ids' => $_REQUEST['shipping_ids']
                        );

                        // 処理開始時に非ログインユーザーであった場合
                        if( $new_user ){
                            // パラメータにユーザー情報をセット
                            $params['user_data'] = $user_data;
                            // パラメータに注文ステップの情報をセット
                            $params['update_step'] = 'step_two';
                            // 配送先住所に関するセッション変数をクリア
                            unset(Tygh::$app['session']['order_shipping_info']);

                        // 処理開始時に会員ログイン済みであった場合
                        }else{
                            // パラメータに注文ステップの情報をセット
                            $params['update_step'] = 'step_three';
                            // 配送先住所に関するセッション変数にユーザー情報をセット
                            Tygh::$app['session']['order_shipping_info'] = $user_data;
                        }

                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // お客様コメントが管理画面に表示されないバグを修正 BOF
                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        $params['customer_notes'] = $_REQUEST['customer_notes'];
                        ///////////////////////////////////////////////////////////////////////////////////////////////////
                        // お客様コメントが管理画面に表示されないバグを修正 EOF
                        ///////////////////////////////////////////////////////////////////////////////////////////////////

                        // 注文処理におけるステップを更新
                        list($result, ) = fn_checkout_update_steps(Tygh::$app['session']['cart'], $auth, $params);
                    }
                }
            }

            // 処理に失敗している場合
            if( !$result ){
                // 「Amazonアカウントでお支払い」ページにリダイレクト
                return array(CONTROLLER_STATUS_REDIRECT, 'checkout.checkout.amazon_checkout');
            }
        }
    }

// GETリクエストの場合
}else{
    // 「Amazonアカウントでお支払い」ページへのリダイレクトを判定する変数を初期化
    $show_amazon_checkout = false;

    // 注文手続きページの場合
    if( $mode == 'checkout' ){
        // Ajaxリクエストではない場合
        if( !defined('AJAX_REQUEST') ){
            // カートの中身が存在せず、リクエストパラメータに check_amount が存在する場合
            if( !Tygh::$app['session']['cart']['amount'] && !empty($_REQUEST['check_amount']) ){
                // 「Amazonアカウントでお支払い」ページへリダイレクト
                return array(CONTROLLER_STATUS_REDIRECT, 'checkout.checkout.amazon_checkout');
            }

            // 「Amazonアカウントでお支払い」ページの表示が指定されている場合
            if( $action == 'amazon_checkout' ){
                // 「Amazonアカウントでお支払い」ページへのリダイレクトを判定する変数に true をセット
                $show_amazon_checkout = true;
            }
            // セッション変数に「Amazonアカウントでお支払い」ページへのリダイレクトを判定する変数をセット
            Tygh::$app['session']['show_amazon_checkout'] = $show_amazon_checkout;

        // Ajaxリクエストの場合
        }else{
            // セッション変数 show_amazon_checkout の内容に基づき「Amazonアカウントでお支払い」ページへのリダイレクトを判定する変数に値をセット
            $show_amazon_checkout = (empty(Tygh::$app['session']['show_amazon_checkout'])) ? false : true;
        }

        // Smarty変数に「Amazonアカウントでお支払い」ページへのリダイレクトを判定する変数の内容をセット
        Tygh::$app['view']->assign('show_amazon_checkout', $show_amazon_checkout);

    // 注文手続き以外のページの場合
    }else{
        // Ajaxリクエストではない場合
        if( !defined('AJAX_REQUEST') ){
            // セッション変数 show_amazon_checkout に false をセット
            Tygh::$app['session']['show_amazon_checkout'] = false;
            // セッション変数 amazon_order_reference_id をクリア
            unset(Tygh::$app['session']['amazon_order_reference_id']);
        }

        // 「カートの内容」ページにおいてユーザーがエラー判定されている場合
        if( $mode == 'cart' && !empty($_REQUEST['error_user']) ){
            // エラーの種類を取得
            $error_type = $_REQUEST['error_user'];

            // ログイン中のユーザーとは異なるユーザーのメールアドレスでAmazonにログインしようとしている場合
            if( $error_type == 'wrong_user' ){
                // エラーメッセージを表示
                fn_set_notification('E', __('error'), __('amazon_error_user'));

            // CS-Cart上で無効化されたユーザーのメールアドレスでAmazonにログインしようとしている場合
            }elseif( $error_type == 'disabled_user' ){
                // エラーメッセージを表示
                fn_set_notification('E', __('error'), __('error_account_disabled'));
            }

            // 強制的にAmazonアカウントからログアウトするフラグをセット
            Tygh::$app['view']->assign('amazon_force_logout', 'Y');
        }
    }
}