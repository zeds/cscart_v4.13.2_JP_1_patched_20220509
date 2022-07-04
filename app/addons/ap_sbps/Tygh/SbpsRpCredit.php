<?php

namespace Tygh;

class SbpsRpCredit extends Sbps
{
    /**
     * 決済要求リクエスト
     *
     * @param $order_info
     * @param $payment_info
     * @param $pay_method
     * @param $mode
     * @return array
     */
    public function credit_request($order_info, $payment_info, $pay_method, $mode = 'credit')
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

        // 取引区分
        if (!empty($payment_info['dealings_type'])) {
            $data['pay_method_info']['dealings_type'] = $payment_info['dealings_type'];

            // 分割回数
            if (!empty($payment_info['divide_times']) && $payment_info['dealings_type'] === '61') {
                $data['pay_method_info']['divide_times'] = $payment_info['divide_times'];
            }
        }

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

        // 決済情報使用特定タイプ
        if (!empty($payment_info['pay_info_control_type'])) {
            $data['pay_option_manage']['pay_info_control_type'] = $payment_info['pay_info_control_type'];
        }

        // 決済支払方法使用特定タイプ
        if (!empty($payment_info['pay_info_detail_control_type'])) {
            $data['pay_option_manage']['pay_info_detail_control_type'] = $payment_info['pay_info_detail_control_type'];
        }

        // 3DES 暗号化フラグ
        $data['encrypted_flg'] = '1';

        return $this->send_request(SBPS_REQUEST_ID[$pay_method][$mode], $data);
    }

    /**
     * 購入リクエスト
     *
     * @param $tracking_id
     * @param $order_info
     * @param $pay_method
     * @return array
     */
    public function purchase_request($tracking_id, $order_info, $pay_method)
    {
        $data = [];

        // マーチャントID
        $data['merchant_id'] = $this->processor['merchant_id'];

        // サービスID
        $data['service_id'] = $this->processor['service_id'];

        // トラッキングID(申請時に発行されたものを使用)
        $data['tracking_id'] = $tracking_id;

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

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['purchase'], $data);
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
     * 解約リクエスト
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

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['cancel_contract'], $data);
    }

    /**
     * 売上要求リクエスト
     *
     * @param $tracking_id
     * @param $pay_method
     * @return array
     */
    public function sales_request($tracking_id, $pay_method)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id
        ];

        if ($pay_method !== 'recruitc') {
            $data['processing_datetime'] = date('YmdHis');
        }

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['sales'], $data);
    }

    /**
     * 取消返金要求リクエスト
     *
     * @param $tracking_id
     * @param $pay_method
     * @param array $add_data
     * @param string $mode
     * @return array
     */
    public function cancel_request($tracking_id, $pay_method, $add_data = [], $mode = 'cancel')
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
        ];

        if ($pay_method !== 'recruitc') {
            $data['processing_datetime'] = date('YmdHis');
        }

        if (!empty($add_data)) {
            $data = array_merge($data, $add_data);
        }

        return $this->send_request(SBPS_REQUEST_ID[$pay_method][$mode], $data);
    }

    /**
     * 決済結果参照要求リクエスト
     *
     * @param $tracking_id
     * @param $pay_method
     * @return array
     */
    public function reference_request($tracking_id, $pay_method)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
            'response_info_type' => '1',
            'pay_option_manage' => [
                'cardbrand_return_flg' => '1',
            ],
            'encrypted_flg' => '1',
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['reference'], $data);
    }

    /**
     * 決済結果整形
     *
     * @param $res_pay_method_info
     * @return array
     */
    public function format_cc_reference($res_pay_method_info)
    {
        $attr = $res_pay_method_info;

        if (!empty($attr['res_pay_method_info_detail'])) {
            $res_pay_method_info_detail = array_intersect_key($attr['res_pay_method_info_detail'], ['dealings_type' => '', 'divide_times' => '', 'bonus_details_times' => '']);

            unset($attr['res_pay_method_info_detail']);
            $attr = array_merge($attr, $res_pay_method_info_detail);
        }

        return $this->format_reference($attr);
    }

    /**
     * 指定モードの要求実行
     *
     * @param $mode
     * @param $tracking_id
     * @param $payment_info
     */
    public function exec_mode_request($mode, $tracking_id, $payment_info)
    {
        switch ($mode) {
            case 'cancel':
                // すでに複数回返金している場合は、複数回返金ですべて返金
                $add_data = [];
                $cancel_mode = 'cancel';

                if (!empty($payment_info['refund_rowno'])) {
                    $total = fn_ap_sbps_get_total($payment_info['order_id']);

                    $add_data['pay_option_manage'] = [
                        'amount' => round($total),
                        'refund_rowno' => $payment_info['refund_rowno'] + 1
                    ];

                    if ($payment_info['pay_method'] === 'recruitc') {
                        $cancel_mode = 'multiple_refund';
                    }
                }

                // 取消返金要求
                $this->cancel_request($tracking_id, $payment_info['pay_method'], $add_data, $cancel_mode);
                break;
            case 'sales_confirm':
                // 売上要求
                $this->sales_request($tracking_id, $payment_info['pay_method']);
                break;
            case 'cancel_contract':
                // 解約要求
                $this->cancel_contract_request($tracking_id, $payment_info['pay_method']);
            default:
        }
    }

    /**
     * 指定モード実行後のステータス取得
     *
     * @param $mode
     * @param $payment_status
     * @return string|null
     */
    public function get_exec_mode_payment_status($mode, $payment_status)
    {
        $result = null;

        switch ($mode) {
            case 'cancel':
                $result = $payment_status === SBPS_PAYMENT_STATUS_AUTH_OK ? SBPS_PAYMENT_STATUS_AUTH_CANCEL : SBPS_PAYMENT_STATUS_REFUNDED;
                break;
            case 'sales_confirm':
                $result = SBPS_PAYMENT_STATUS_SALES_CONFIRM;
                break;
            default:
        }

        return $result;
    }
}