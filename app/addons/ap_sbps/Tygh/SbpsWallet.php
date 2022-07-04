<?php

namespace Tygh;

class SbpsWallet extends Sbps
{
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

        if (in_array($pay_method, ['yahoowallet', 'linepay'], true)) {
            $data['processing_datetime'] = date('YmdHis');
        }

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['sale'], $data);
    }

    /**
     * 取消返金要求リクエスト
     *
     * @param $tracking_id
     * @param $pay_method
     * @param $mode
     * @return array
     */
    public function cancel_request($tracking_id, $pay_method, $mode = 'cancel')
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
        ];

        if (in_array($pay_method, ['yahoowallet', 'paypal'], true)) {
            $data['processing_datetime'] = date('YmdHis');
        }

        return $this->send_request(SBPS_REQUEST_ID[$pay_method][$mode], $data);
    }

    /**
     * 金額変更要求リクエスト
     *
     * @param $tracking_id
     * @param $pay_method
     * @param $amount
     * @return array
     */
    public function amount_change_request($tracking_id, $pay_method, $amount)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
            'pay_option_manage' => [
                'amount' => $amount
            ]
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['amount_change'], $data);
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
            case 'refund':
                // 取消返金要求
                $this->cancel_request($tracking_id, $pay_method, $mode);
                break;
            case 'sales_confirm':
                // 売上要求
                $this->sales_request($tracking_id, $pay_method);
                break;
            default:
        }
    }

    /**
     * 指定モード実行後のステータス取得
     *
     * @param $mode
     * @param $payment_info
     * @return string|null
     */
    public function get_exec_mode_payment_status($mode, $payment_info)
    {
        $result = null;

        switch ($mode) {
            case 'cancel':
                if ($payment_info['status'] === SBPS_PAYMENT_STATUS_SALES_CONFIRM || $payment_info['pay_method'] === 'yahoowallet') {
                    $result = SBPS_PAYMENT_STATUS_REFUNDED;
                } else {
                    $result = SBPS_PAYMENT_STATUS_UNKNOWN_CANCEL;
                }
                break;
            case 'refund':
                $result = SBPS_PAYMENT_STATUS_REFUNDED;
                break;
            case 'sales_confirm':
                $result = $payment_info['pay_method'] === 'rakuten' ? SBPS_PAYMENT_STATUS_SALES_CONFIRM_ACCEPTED : SBPS_PAYMENT_STATUS_SALES_CONFIRM;
                break;
            default:
        }

        return $result;
    }
}