<?php

namespace Tygh;

class SbpsApplePay extends Sbps
{
    /**
     * 再与信要求リクエスト
     *
     * @param $tracking_id
     * @param $order_info
     * @return array
     */
    public function re_auth_request($tracking_id, $order_info)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
            'cust_code' => $this->format_cust_code($order_info['user_id']),
            'order_id' => $this->format_order_id(),
            'item_id' => self::ITEM_ID,
            'amount' => (int)$order_info['total']
        ];

        return $this->send_request(SBPS_REQUEST_ID['applepay']['re_auth'], $data);
    }

    /**
     * 売上要求リクエスト
     *
     * @param $tracking_id
     * @return array
     */
    public function sales_request($tracking_id)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id
        ];

        return $this->send_request(SBPS_REQUEST_ID['applepay']['sales'], $data);
    }

    /**
     * 取消返金要求リクエスト
     *
     * @param $tracking_id
     * @param array $add_data
     * @param $mode
     * @return array
     */
    public function cancel_request($tracking_id, $add_data = [], $mode = 'cancel')
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id
        ];

        if (!empty($add_data)) {
            $data = array_merge($data, $add_data);
        }

        return $this->send_request(SBPS_REQUEST_ID['applepay'][$mode], $data);
    }

    /**
     * 取消返金要求リクエスト
     *
     * @param $tracking_id
     * @param array $add_data
     * @return array
     */
    public function refund_confirm_request($tracking_id, $add_data = [])
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id
        ];

        return $this->send_request(SBPS_REQUEST_ID['applepay']['refund_comfirm'], $data);
    }

    /**
     * 決済結果参照要求リクエスト
     *
     * @param $tracking_id
     * @return array
     */
    public function reference_request($tracking_id)
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

        return $this->send_request(SBPS_REQUEST_ID['applepay']['reference'], $data);
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
            case 'refund':
                $add_data = [];

                if ($mode === 'refund' && $payment_info['payment_status'] === SBPS_PAYMENT_STATUS_PARTIAL_REFUNDED) {
                    $total = fn_ap_sbps_get_total($payment_info['order_id']);
                    $add_data['pay_option_manage'] = ['amount' => round($total),];
                }

                // 取消返金要求
                $this->cancel_request($tracking_id, $add_data, $mode);
                break;
            case 'sales_confirm':
                // 売上要求
                $this->sales_request($tracking_id);
                break;
            default:
        }
    }
}