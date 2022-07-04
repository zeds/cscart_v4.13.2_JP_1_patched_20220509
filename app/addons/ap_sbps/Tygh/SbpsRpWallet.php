<?php

namespace Tygh;

class SbpsRpWallet extends Sbps
{
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
            'tracking_id' => $tracking_id,
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['sales'], $data);
    }

    /**
     * 取消返金要求リクエスト
     *
     * @param $tracking_id
     * @param $pay_method
     * @return array
     */
    public function cancel_request($tracking_id, $pay_method)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['cancel'], $data);
    }

    /**
     * 指定モードの要求実行
     *
     * @param $mode
     * @param $tracking_id
     * @param $pay_method
     */
    public function exec_mode_request($mode, $tracking_id, $pay_method)
    {
        switch ($mode) {
            case 'cancel':
                // 取消返金要求
                $this->cancel_request($tracking_id, $pay_method);
                break;
            case 'sales_confirm':
                // 売上要求
                $this->sales_request($tracking_id, $pay_method);
                break;
            case 'cancel_contract':
                // 解約要求
                $this->cancel_contract_request($tracking_id, $pay_method);
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