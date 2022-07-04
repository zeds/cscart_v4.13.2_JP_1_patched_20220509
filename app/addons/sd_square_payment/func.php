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

use Tygh\Registry;
use Tygh\Enum\SquareStatuses;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


/**
 * アドオンのインストール時に実行する処理
 */
function fn_sd_square_payment_install()
{
    fn_lcjp_install('sd_square_payment');

    // アドオンをアンインストール時に実行する処理を実行
    fn_sd_square_payment_uninstall();

    // 「payment_processors」テーブルにSquare関連のレコードを追加
    db_query('INSERT INTO ?:payment_processors ?e', array(
        'processor' => 'Square Connect',
        'processor_script' => 'sd_square_payment.php',
        'processor_template' => 'addons/sd_square_payment/views/orders/components/payments/square.tpl',
        'admin_template' => 'square.tpl',
        'callback' => 'N',
        'type' => 'P',
        'addon' => 'sd_square_payment'
    ));
}




/**
 * アドオンのアンインストール時に実行する処理
 */
function fn_sd_square_payment_uninstall()
{
    // 「payment_processors」テーブルからSquare関連のレコードを削除
    db_query('DELETE FROM ?:payment_processors WHERE processor_script = ?s', 'sd_square_payment.php');
}




/**
 * Squareの processor_id を取得
 *
 * @return array
 */
function fn_sd_square_payment_get_processor_id()
{
    return db_get_field('SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s', 'sd_square_payment.php');
}




/**
 * Square API実行の初期化
 */
function fn_init_square_api()
{
    // square_autoload.php を1度だけ読み込み
    static $only_one = true;
    if ($only_one) {
        require_once(dirname(__FILE__) . '/lib/square_autoload.php');
        $only_one = false;
    }

    // 新サンドボックスURLに対応
    $app_id = Registry::get('addons.sd_square_payment.app_id');
    $api_url = 'squareup.com';
    // サンドボックスの場合
    if(strpos($app_id,'sandbox') !== false){
        $api_url = 'squareupsandbox.com';
    }
    define("SQUARE_API_URL", $api_url);

    // 登録したアクセストークンをセット
    $access_token = Registry::get('addons.sd_square_payment.token_id');

    // アクセストークンが指定されている場合
    if (!empty($access_token)) {
        // Square APIとの通信を初期化
        SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
    }
}




/**
 * Squareにユーザー情報を登録
 *
 * @param $user_data
 * @return bool|string
 */
function fn_create_square_customer($user_data)
{
    $result = false;

    // Squareにユーザー情報を登録
    try {
        // Square API実行の初期化
        fn_init_square_api();
        $api = new SquareConnect\Api\CustomersApi();

        // メールアドレスとユーザーIDをセット
        $response = $api->createCustomer(array(
            'email_address' => $user_data['email'],
            'reference_id' => $user_data['user_id'],
        ));

        // メールアドレスとユーザーIDをキーにSquareに登録済みのユーザー情報を抽出
        $customer = $response->getCustomer();
        // 抽出したユーザーのIDを取得
        $result = $customer->getId();

        // CS-Cartに登録されたユーザー情報を更新
        db_query('REPLACE INTO ?:users_square_id ?e', array(
            'app_id' => Registry::get('addons.sd_square_payment.app_id'),
            'customer_id' => $user_data['user_id'],
            'square_id' => $result
        ));
    // エラー処理
    } catch (SquareConnect\ApiException $e) {
        fn_square_catch_errors($e, $result);
    }

    // 結果を返す
    return $result;
    
}




/**
 * カード情報の登録
 *
 * @param $user_id
 * @param $payment_info
 * @return bool|string
 */
function fn_create_square_customer_card($user_id, $payment_info, $show_notification = true)
{
    // 戻り値を初期化
    $result = false;
    $error_message = '';
    // カード情報の登録
    try {
        // Square API実行の初期化
        fn_init_square_api();
        // ユーザーの情報を取得
        $user_data = fn_get_user_short_info($user_id);
        // Square APIのインスタンスを初期化
        $api = new SquareConnect\Api\CustomersApi();
        // ユーザーのカード情報を登録
        $response = $api->createCustomerCard($user_data['square_id'], array(
            'card_nonce' => $payment_info['card_nonce'],
            'cardholder_name' => $payment_info['cardholder_name']
        ));

        // 登録したカード情報を取得
        $card = $response->getCard();
        // 登録したカードのIDを取得
        $result = $card->getId();
        // カード情報の登録完了メッセージを表示
        fn_set_notification('N', __('notice'), __('addons.sd_square_payment.card_was_saved'));
    // エラー処理
    } catch (SquareConnect\ApiException $e) {
        $error_message = fn_square_catch_errors($e, $result, $show_notification);
    }
    return array($result, $error_message);
}




/**
 * Squareに送信する決済金額を整形
 *
 * @param int $amount
 * @return float|int
 */
function fn_prepare_amount_for_square($amount = 0)
{
    // ベース通貨の小数点以下の桁数を取得
    $decimals = Registry::get('currencies.' . CART_PRIMARY_CURRENCY . '.decimals');

    // 10をベース通貨の小数点以下の桁数で累乗
    $coef = pow(10, $decimals);

    // 金額 x 累乗した数値を返す
    return $amount * $coef;
}




/**
 * 返金処理（キャンセル or 減額）の実施
 *
 * @param $transaction_id
 * @param $amount
 * @return bool
 */
function fn_square_refund_transaction($transaction_id, $amount)
{
    // トランザクションIDが指定されていないか、決済金額が0円以下の場合
    if (empty($transaction_id) || $amount <= 0) {
        // 処理を終了
        return false;
    }
    $result = true;

    // 返金処理の実施
    try {
        // Square API実行の初期化
        fn_init_square_api();

        // Square APIのインスタンスを初期化
        $api = new SquareConnect\Api\TransactionsApi();

        // ロケーションIDを取得
        $location_id = Registry::get('addons.sd_square_payment.location_id');

        // ロケーションIDとトランザクションIDをキーとして、トランザクションの情報を取得
        $transaction = $api->retrieveTransaction($location_id, $transaction_id)->getTransaction();

        // オリジナルの決済金額を取得
        $original_total = db_get_field('SELECT original_total FROM ?:jp_square_cc_status WHERE transaction_id = ?s', $transaction_id);

        // 減額後の金額を取得
        $new_amount = $original_total - $amount;

        // 減額後の金額をセット
        $jp_square = array(
            'original_total' => max($new_amount, 0)
        );

        // 減額（減額後の金額が0より大きい）の場合
        if ($new_amount > 0) {
            // 返金ステータス = 減額
            $state = 'partial';
        // キャンセル（減額後の金額が0）の場合
        } else {
            // 返金ステータス = 全額返金
            $state = 'full';
            // ステータスコードに「返金」をセット
            $jp_square['status_code'] = SquareStatuses::REFUND;
        }

        // プレフィックスを取得
        $prefix = Registry::get('addons.sd_square_payment.idempotency_prefix');

        // 識別用キーを取得
        $idempotencyKey = $prefix . 'refund' . $amount . '_' . TIME;

        // キャンセル（全額返金）の場合
        if ($state == 'full') {
            // 返金理由をセット
            $refund_reason = __('addons.sd_square_payment.cancel_order');
        // 減額（一部返金）の場合
        } else {
            // 返金理由をセット
            $refund_reason = __('addons.sd_square_payment.partial_refund');
        }

        // 返金の実施
        $api->createRefund($location_id, $transaction->getId(), array(
            'tender_id' => $transaction->getTenders()[0]->getId(),
            'amount_money' => array(
                'amount' => fn_prepare_amount_for_square($amount),
                'currency' => CART_PRIMARY_CURRENCY
            ),
            'idempotency_key' => $idempotencyKey,
            'reason' => $refund_reason
        ));

        // 返金完了メッセージを表示
        fn_set_notification('N', __('notice'), __('addons.sd_square_payment.refunded') . ': ' . CART_PRIMARY_CURRENCY . $amount);

        // CS-Cart上の決済ステータスを更新
        db_query('UPDATE ?:jp_square_cc_status SET ?u WHERE transaction_id = ?s', $jp_square, $transaction_id);

    // エラー処理
    } catch (SquareConnect\ApiException $e) {
        fn_square_catch_errors($e, $result);
    }
}




/**
 * 与信取消
 *
 * @param $transaction_id
 * @return bool
 */
function fn_square_void_transaction($transaction_id)
{
    // トランザクションIDが指定されていない場合、処理を終了
    if (empty($transaction_id)) {
        return false;
    }
    $result = true;

    // 与信の取消
    try {
        // Square API実行の初期化
        fn_init_square_api();

        // Square APIのインスタンスを初期化
        $api = new SquareConnect\Api\TransactionsApi();

        // ロケーションIDを取得
        $location_id = Registry::get('addons.sd_square_payment.location_id');

        // 与信の取消
        $response = $api->voidTransaction($location_id, $transaction_id);

        // 与信取消完了メッセージを表示
        fn_set_notification('N', __('notice'), __('addons.sd_square_payment.voided'));

        // CS-Cartで管理している決済ステータスを更新
        db_query('UPDATE ?:jp_square_cc_status SET status_code = ?s WHERE transaction_id = ?s', SquareStatuses::VOID, $transaction_id);

    // エラー処理
    } catch (SquareConnect\ApiException $e) {
        fn_square_catch_errors($e, $result);
    }

    // 結果を返す
    return $result;
}




/**
 * 売上確定処理の実施
 *
 * @param $transaction_id
 * @return bool
 */
function fn_square_capture_transaction($transaction_id)
{
    // トランザクションIDが指定されていない場合、処理を終了
    if (empty($transaction_id)) {
        return false;
    }

    // 返り値を初期化
    $result = true;

    // 売上確定
    try {
        // Square API実行の初期化
        fn_init_square_api();

        // Square APIのインスタンスを初期化
        $api = new SquareConnect\Api\TransactionsApi();

        // ロケーションIDを取得
        $location_id = Registry::get('addons.sd_square_payment.location_id');

        // 売上確定
        $response = $api->captureTransaction($location_id, $transaction_id);

        // 売上確定完了メッセージを表示
        fn_set_notification('N', __('notice'), __('addons.sd_square_payment.captured'));

        // CS-Cart上の決済ステータスを更新
        db_query('UPDATE ?:jp_square_cc_status SET status_code = ?s WHERE transaction_id = ?s', SquareStatuses::CAPTURE, $transaction_id);

    // エラー処理
    } catch (SquareConnect\ApiException $e) {
        fn_square_catch_errors($e, $result);
    }
    return $result;
}




/**
 * @param $order_id
 * @param $action
 * @return bool
 */
function fn_update_square_payment_status($order_id, $action)
{
    // 注文IDまたは処理のタイプが指定されていない場合、処理を終了
    if (empty($order_id) || empty($action)) {
        return false;
    }

    // 注文データを取得
    $order_data = db_get_row('SELECT * FROM ?:jp_square_cc_status WHERE order_id = ?i', $order_id);

    // 現在の注文ステータスを取得
    $current_status = $order_data['status_code'];

    // 実行可能な後続処理を取得
    $actions = SquareStatuses::getActions($current_status);

    // 実行可能な後続処理が存在しない場合
    if (empty($actions[$action])) {
        // エラーメッセージを表示して処理を終了
        fn_set_notification('E', __('error'), __('addons.sd_square_payment.incorrect_request'));
    // 実行可能な後続処理が存在する場合
    } else {
        // 与信から売上確定への変更の場合
        if ($action == SquareStatuses::CAPTURE) {
            // 注文合計金額を取得
            $order_total = db_get_field('SELECT total FROM ?:orders WHERE order_id = ?i', $order_id);

            // オリジナルの注文金額から変更後の注文金額をマイナス
            $refund_amount = $order_data['original_total'] - $order_total;

            // 売上確定処理を実施
            fn_square_capture_transaction($order_data['transaction_id']);

            // オリジナルの注文金額から変更後の注文金額がプラスの場合
            if ($refund_amount > 0) {
                // 返金処理を実施
                fn_square_refund_transaction($order_data['transaction_id'], $refund_amount);
            }

        // 与信取消の場合
        } elseif ($action == SquareStatuses::VOID) {
            // 与信取消
            fn_square_void_transaction($order_data['transaction_id']);

        // 返金の場合
        } elseif ($action == SquareStatuses::REFUND) {
            // 返金
            fn_square_refund_transaction($order_data['transaction_id'], $order_data['original_total']);
        }
    }
}




/**
 * 登録済みカードの削除
 *
 * @param $user_id
 * @param $card_id
 * @return bool
 */
function fn_delete_square_castomer_card($user_id, $card_id)
{
    // ユーザーIDまたはカードIDが指定されていない場合、処理を終了
    if (empty($user_id) || empty($card_id)) {
        return false;
    }

    // 返り値を初期化
    $result = false;

    // 登録済みカードの削除
    try {
        // Square API実行の初期化
        fn_init_square_api();

        // ユーザー情報の取得
        $user_data = fn_get_user_short_info($user_id);

        // Square APIのインスタンスを初期化
        $api = new SquareConnect\Api\CustomersApi();

        // 登録済みカードの削除
        $api->deleteCustomerCard($user_data['square_id'], $card_id);

        // カード情報の削除完了メッセージを表示
        fn_set_notification('N', __('notice'), __('addons.sd_square_payment.card_was_deleted'));

    // エラー処理
    } catch (SquareConnect\ApiException $e) {
        fn_square_catch_errors($e, $result);
    }

    // 返り値を返す
    return $result;
}




/**
 * 登録済みカード情報の取得
 *
 * @param $user_data
 * @return array|bool
 */
function fn_get_square_customer_saved_cards($user_data)
{
    // 返り値を初期化
    $results = false;

    // ユーザーのSquare IDが指定されていない場合
    if (empty($user_data['square_id'])) {
        // Squareにユーザー情報を登録
        $user_data['square_id'] = fn_create_square_customer($user_data);
    }

    // Square IDが指定されている場合
    if (!empty($user_data['square_id'])) {
        try {
            // Square API実行の初期化
            fn_init_square_api();

            // Square APIのインスタンスを初期化
            $api = new SquareConnect\Api\CustomersApi();

            // カード情報のの取得
            $response = $api->retrieveCustomer($user_data['square_id']);
            $customer_info = $response->getCustomer();
            $cards = $customer_info->getCards();

            // カード情報が存在する場合
            if (!empty($cards)) {
                // 登録済みの全てのカードを配列に格納
                foreach ($cards as $card) {
                    $card_info = (array)json_decode($card->__toString());
                    $results[] = $card_info;
                }
            }

        // エラー処理
        } catch (SquareConnect\ApiException $e) {
            fn_square_catch_errors($e, $results);
        }
    }

    // 登録済みカード情報を返す
    return $results;
}




/**
 * エラー処理
 *
 * @param $exeption
 * @param $results
 * @param bool $show_notification
 * @return string
 */
function fn_square_catch_errors($exeption, &$results, $show_notification = true)
{
    // 戻り値を初期化
    $results = false;

    // エラーに関する情報を取得
    $errors = $exeption->getResponseBody();

    // エラーメッセージを初期化
    $error_message = '';

    // エラーに関する情報が存在する場合
    if (!empty($errors->errors)) {
        // エラーメッセージを取得
        foreach ($errors->errors as $e) {
            $error_message .= $e->detail . ' ';
        }
    }

    // エラーメッセージが取得できなかった場合
    if (empty($error_message)) {
        // デフォルトのエラーメッセージをセット
        $error_message = __('addons.sd_square_payment.default_error_message');
    }

    $error_message = fn_sd_square_convert_error_message($error_message);

    // エラーメッセージを表示する場合
    if ($show_notification && !empty($error_message)) {
        // エラーメッセージを表示
        fn_set_notification('E', __('error'), $error_message);
    }

    // エラーメッセージを返す
    return $error_message;
}




/**
 * エラーメッセージを日本語に変換
 *
 * @param $error_message
 * @return string
 */
function fn_sd_square_convert_error_message($error_message)
{
    // メッセージ前後に存在する余白を削除
    $error_message = trim($error_message);

    // Squareから戻された英文のメッセージに応じて日本語に変換
    switch($error_message){
        // CVVに関するエラー
        case 'Card verification code check failed.':
            $error_message = __('square_msg_cvv_check_failed');
            break;

        // 有効期限に関するエラー
        case 'Invalid card expiration date.':
            $error_message = __('square_msg_exp_not_valid');
            break;

        default:
            // do nothing
    }

    return $error_message;
}




/**
 * CS-Cart上で管理している各注文の決済ステータスを登録・更新
 *
 * @param $data
 */
function fn_update_jp_square_cc_status($data)
{
    // 注文IDが指定されている場合
    if (!empty($data['order_id'])) {
        // CS-Cart上で管理している各注文の決済ステータスを登録・更新
        db_query('REPLACE INTO ?:jp_square_cc_status ?e', $data);
    }
}



//* HOOKS */

/**
 * 【フックポイント : get_user_short_info_pre】
 * ユーザー情報の取得時にSquare IDも取得
 *
 * @param $user_id
 * @param $fields
 * @param $condition
 * @param $join
 * @param $group_by
 */
function fn_sd_square_payment_get_user_short_info_pre($user_id, &$fields, $condition, &$join, $group_by)
{
    // ユーザー情報の取得時に当該ユーザーのSquare IDも取得
    $join .= db_quote(' LEFT JOIN ?:users_square_id ON ?:users_square_id.customer_id = ?:users.user_id AND ?:users_square_id.app_id = ?s', Registry::get('addons.sd_square_payment.app_id'));
    $fields[] = '?:users_square_id.square_id';
}


/**
 * 【フックポイント : change_order_status】
 * 注文キャンセル時に与信取消または売上取消を実行
 *
 * @param $status_to
 * @param $status_from
 * @param $order_info
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 */
function fn_sd_square_payment_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{
    // 注文キャンセルの場合
    if ($status_to == STATUS_CANCELED_ORDER) {
        // 当該注文の決済ステータスを取得
        $square_data = db_get_row('SELECT * FROM ?:jp_square_cc_status WHERE order_id = ?i', $order_info['order_id']);

        // トランザクションIDが存在する場合
        if (!empty($square_data['transaction_id'])) {
            // 与信済みの場合
            if ($square_data['status_code'] == SquareStatuses::AUTH) {
                // 与信取消
                fn_square_void_transaction($square_data['transaction_id']);
            // 売上確定済みの場合
            } elseif ($square_data['status_code'] == SquareStatuses::CAPTURE) {
                // 売上取消
                fn_square_refund_transaction($square_data['transaction_id'], $square_data['original_total']);
            }
        }
    }
}




/**
 * 【フックポイント : calculate_cart_items】
 * カート内容の計算時にSquare関連の情報も取得
 *
 * @param $cart
 * @param $cart_products
 * @param $auth
 */
function fn_sd_square_payment_calculate_cart_items(&$cart, $cart_products, $auth)
{
    // 注文の編集の場合
    if (defined('ORDER_MANAGEMENT') && !empty($cart['order_id'])) {
        // 注文IDを$cart['square_order_id']にセット
        $cart['square_order_id'] = db_get_field('SELECT order_id FROM ?:jp_square_cc_status WHERE order_id = ?i', $cart['order_id']);
    }
}




/**
 * 【フックポイント : get_orders】
 * 注文情報の取得時にSquare決済を利用した注文のみを取得するクエリを生成
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_sd_square_payment_get_orders($params, &$fields, $sortings, &$condition, &$join, $group)
{
    // Square決済を利用した注文を取得する場合
    if (!empty($params['get_square_orders'])) {

        // Square決済が登録された payment_id を取得
        $payment_ids = db_get_fields('SELECT a.payment_id FROM ?:payments as a LEFT JOIN ?:payment_processors as b ON a.processor_id = b.processor_id WHERE b.processor_script = ?s', 'sd_square_payment.php');

        // Square決済を利用した注文のIDを取得
        $order_ids = db_get_fields('SELECT order_id FROM ?:jp_square_cc_status');

        // Square決済を利用した注文のみを取得するクエリを生成
        $condition .= db_quote(" AND (?:orders.payment_id IN (?n) AND ?:orders.order_id IN (?n))", $payment_ids, $order_ids);
        $fields[] = '?:jp_square_cc_status.status_code';
        $join .= " LEFT JOIN ?:jp_square_cc_status ON ?:jp_square_cc_status.order_id = ?:orders.order_id";
    }
}




/**
 * 【フックポイント : get_orders_post】
 * 注文情報の取得時にSquare決済に関する情報を取得して注文情報にセット
 *
 * @param $params
 * @param $orders
 */
function fn_sd_square_payment_get_orders_post($params, &$orders)
{
    // Square決済を利用した注文を取得する場合
    if (!empty($params['get_square_orders'])) {
        // Square決済に関する情報を取得して注文情報にセット
        foreach ($orders as &$order) {
            $order['square_status'] = SquareStatuses::getDescription($order['status_code']);
            $order['square_actions'] = SquareStatuses::getActions($order['status_code']);
        }
    }
}




/**
 * 【フックポイント : delete_order】
 * 注文情報の削除時に jp_square_cc_status 内の関連データも削除
 *
 * @param $order_id
 */
function fn_sd_square_payment_delete_order($order_id)
{
    // 注文情報の削除時に jp_square_cc_status 内の関連データも削除
    db_query('DELETE FROM ?:jp_square_cc_status WHERE order_id = ?i', $order_id);
}