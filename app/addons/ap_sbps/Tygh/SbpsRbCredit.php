<?php

namespace Tygh;

class SbpsRbCredit extends Sbps
{
    /**
     * 決済要求リクエスト
     *
     * @param $order_info
     * @param $payment_info
     * @param $pay_method
     * @return array
     */
    public function credit_request($order_info, $payment_info, $pay_method)
    {
        $data = [];

        // マーチャントID
        $data['merchant_id'] = $this->processor['merchant_id'];

        // サービスID
        $data['service_id'] = $this->processor['service_id'];

        // トラッキングID
        if (!empty($payment_info['tracking_id'])) {
            $data['tracking_id'] = $payment_info['tracking_id'];
        }

        // 顧客ID
        $data['cust_code'] = $this->format_cust_code($order_info['user_id']);

        // 購入ID
        $data['order_id'] = $this->format_order_id();

        // 商品ID
        $data['item_id'] = self::ITEM_ID;

        // 商品名称
        if (!empty($order_info['products'])) {
            $data['item_name'] = $this->format_item_name($order_info['products']);
        }

        // 金額（税込）
        $data['amount'] = round($order_info['total']);

        // トークン
        if (!empty($payment_info['token'])) {
            $data['pay_option_manage']['token'] = $payment_info['token'];
        }

        // トークンキー
        if (!empty($payment_info['token_key'])) {
            $data['pay_option_manage']['token_key'] = $payment_info['token_key'];
        }

        // クレジットカード情報保存フラグ
        if (isset($payment_info['cust_manage_flg'])) {
            $data['pay_option_manage']['cust_manage_flg'] = $payment_info['cust_manage_flg'];
        }

        // クレジットカードブランド返却フラグ
        $data['pay_option_manage']['cardbrand_return_flg'] = '1';

        // 決済区分 : 前払い固定
        $data['monthly_charge']['div_settele'] = '0';

        // キャンペーンタイプ
        $data['monthly_charge']['camp_type'] = $this->processor['camp_type'];

        // 3DES 暗号化フラグ
        $data['encrypted_flg'] = '1';

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['credit'], $data);
    }

    /**
     * 確定要求リクエスト
     *
     * @param $tracking_id
     * @param $pay_method
     * @return array
     */
    public function confirm_request($tracking_id, $pay_method)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
            'processing_datetime' => date('YmdHis'),
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['confirm'], $data);
    }

    /**
     * 継続課金(簡易)解約要求
     *
     * @param $tracking_id
     * @param $pay_method
     * @return array
     */
    public function cancel_contract_request($tracking_id, $pay_method)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
        ];

        if (in_array($pay_method, ['credit', 'credit3d'])) {
            $data['processing_datetime'] = date('YmdHis');
        }

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['cancel_contract'], $data);
    }
}