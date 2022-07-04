<?php

namespace Tygh;

class SbpsCareer extends Sbps
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
                $result = $payment_status === SBPS_PAYMENT_STATUS_SALES_CONFIRM ? SBPS_PAYMENT_STATUS_REFUNDED : SBPS_PAYMENT_STATUS_UNKNOWN_CANCEL;
                break;
            case 'sales_confirm':
                $result = SBPS_PAYMENT_STATUS_SALES_CONFIRM;
                break;
            default:
        }

        return $result;
    }
}