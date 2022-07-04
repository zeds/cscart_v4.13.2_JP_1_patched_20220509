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

use Tygh\Registry;
use Tygh\Enum\SquareStatuses;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 支払に関する通知処理を表す定数が定義されている場合、処理を終了
if (defined('PAYMENT_NOTIFICATION')) {
    return;
}

// 支払に関するデータを格納する変数を初期化
$pp_response = array();

// SquareのロケーションIDを取得
$location_id = Registry::get('addons.sd_square_payment.location_id');

// 処理を識別するためのプレフィックスを取得
$prefix = Registry::get('addons.sd_square_payment.idempotency_prefix');

// 注文IDを取得
$order_id = $order_info['order_id'];

// トランザクションの新規作成有無を判定するフラグを初期化
$create_transaction = true;

// Squareの請求ステータスに関する情報を取得
$transaction_info = db_get_row('SELECT * FROM ?:jp_square_cc_status WHERE order_id = ?i', $order_id);

// Squareの請求ステータスに関する情報が存在する場合（= すでに1度決済が行われている注文の場合）
if (!empty($transaction_info)) {
    // トランザクションの新規作成は行わない
    $create_transaction = false;

    // オリジナルの注文金額から変更後の注文金額をマイナス
    $refund_amount = $transaction_info['original_total'] - $order_info['total'];

    // 請求ステータスが「売上確定済」の場合
    if ($transaction_info['status_code'] == SquareStatuses::CAPTURE) {

        // オリジナルの注文金額から変更後の注文金額がプラス（減額処理）の場合
        if ($refund_amount > 0) {
            // 返金処理を実施
            fn_square_refund_transaction($transaction_info['transaction_id'], $refund_amount);
        // オリジナルの注文金額から変更後の注文金額がマイナス（増額処理）または金額に変更がない場合場合
        }else{
            // エラーメッセージを表示
            fn_set_notification('E', __('warning'), __('square_msg_data_not_sent_capture'));
        }
    // 請求ステータスが「与信済」の場合
    }elseif($transaction_info['status_code'] == SquareStatuses::AUTH){
        // オリジナルの注文金額から変更後の注文金額がプラス（減額処理）の場合
        if ($refund_amount > 0) {
            // 警告メッセージを表示
            fn_set_notification('E', __('warning'), __('square_msg_data_not_sent_auth_after_capture'));
        // オリジナルの注文金額から変更後の注文金額がマイナス（増額処理）または金額に変更がない場合場合
        }else{
            // 警告メッセージを表示
            fn_set_notification('E', __('warning'), __('square_msg_data_not_sent_auth_over_amount'));
        }
    }

    $pp_response['order_status'] = $order_info['status'];
    $pp_response['transaction_id'] = $transaction_info['transaction_id'];
}


if ($create_transaction) {
    $idempotency_key = $prefix . (($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id) . '_' . TIME;

    $amount = fn_prepare_amount_for_square($order_info['total']);

    $request_params = array(
        'idempotency_key' => $idempotency_key,
        'amount_money' => array(
            'amount' => $amount, 
            'currency' => CART_PRIMARY_CURRENCY
        ),
        'shipping_address' => array(
            'address_line_1' => !empty($order_info['s_address']) ? $order_info['s_address'] : '',
            'address_line_2' => !empty($order_info['s_address_2']) ? $order_info['s_address_2'] : '',
            'locality' => !empty($order_info['s_city']) ? $order_info['s_city'] : '',
            'administrative_district_level_1' => !empty($order_info['s_state']) ? $order_info['s_state'] : '',
            'postal_code' => !empty($order_info['s_zipcode']) ? $order_info['s_zipcode'] : '',
            'country' => !empty($order_info['s_country']) ? $order_info['s_country'] : ''
        ),
        'billing_address' => array(
            'address_line_1' => !empty($order_info['b_address']) ? $order_info['b_address'] : '',
            'address_line_2' => !empty($order_info['b_address_2']) ? $order_info['b_address_2'] : '',
            'locality' => !empty($order_info['b_city']) ? $order_info['b_city'] : '',
            'administrative_district_level_1' => !empty($order_info['b_state']) ? $order_info['b_state'] : '',
            'postal_code' => !empty($order_info['b_zipcode']) ? $order_info['b_zipcode'] : '',
            'country' => !empty($order_info['b_country']) ? $order_info['b_country'] : ''
        ),
        'buyer_email_address' => $order_info['email'],
        'reference_id' => $order_id
    );

    if (!empty($processor_data['processor_params']['only_auth']) && $processor_data['processor_params']['only_auth'] == 'Y') {
        $request_params['delay_capture'] = true;
        $pp_response['delay_capture'] = true;
    }

    $skip_payment = false;

    if (AREA == 'C') {
        if (empty($order_info['user_id'])) {
            $user_info = array();
        } else {
            $user_info = fn_get_user_short_info($order_info['user_id']);
            if (empty($user_info['square_id'])) {
                $user_info['square_id'] = fn_create_square_customer($user_info);
            }
            if (!empty($order_info['payment_info']['card_nonce'])) {
                list($card_id, $error_message) = fn_create_square_customer_card($order_info['user_id'], $order_info['payment_info'], false);
                if ($card_id) {
                    $order_info['payment_info']['card_id'] = $card_id;
                } else {
                    $pp_response['order_status'] = 'F';
                    $pp_response['reason_text'] = $error_message;
                    $skip_payment = true;
                    unset($order_info['payment_info']['card_nonce']);
                }
            }
        }
    }

    if (!empty($order_info['payment_info']['card_id']) && !empty($order_info['user_id'])) {
        $request_params['customer_id'] = $user_info['square_id'];
        $request_params['customer_card_id'] = $order_info['payment_info']['card_id'];
    } elseif (!empty($order_info['payment_info']['card_nonce'])) {
        $request_params['card_nonce'] = $order_info['payment_info']['card_nonce'];
    }

    if (!$skip_payment) {
        try {
            fn_init_square_api();
            $api = new \SquareConnect\Api\TransactionsApi();
            $response = $api->charge($location_id, $request_params);
            $transaction = $response->getTransaction();
            $transaction_id = $transaction->getId();

            $pp_response['order_status'] = 'P';
            $pp_response['idempotency_key'] = $idempotency_key;
            $pp_response['transaction_id'] = $transaction_id;

            fn_update_jp_square_cc_status(array(
                'order_id' => $order_info['order_id'],
                'status_code' => (!empty($pp_response['delay_capture'])) ? SquareStatuses::AUTH : SquareStatuses::CAPTURE,
                'transaction_id' => $pp_response['transaction_id'],
                'original_total' => $order_info['total'],
                'timestamp' => TIME
            ));
        } catch (SquareConnect\ApiException $e) {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = fn_square_catch_errors($e, $results, false);
        }
    }
}

fn_finish_payment($order_info['order_id'], $pp_response, $force_notification);
fn_order_placement_routines('route', $order_info['order_id'], $force_notification);
