<?php

namespace Tygh;

class SbpsRbCareer extends Sbps
{
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
            'processing_datetime' => date('YmdHis')
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['cancel_contract'], $data);
    }

    /**
     * 返金要求
     *
     * @param $tracking_id
     * @param $pay_method
     * @param $cancel_target_month
     * @return array
     */
    public function refund_request($tracking_id, $pay_method, $cancel_target_month = null)
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id,
            'pay_option_manage' => [
                'cancel_target_month' => empty($cancel_target_month) ? date('Ym') : $cancel_target_month
            ]
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['refund'], $data);
    }

    /**
     * 指定モードの要求実行
     *
     * @param $mode
     * @param $tracking_id
     * @param $pay_method
     * @param $now
     */
    public function exec_mode_request($mode, $tracking_id, $pay_method, $now = null)
    {
        switch ($mode) {
            case 'cancel_contract':
                // 継続課金(簡易)解約要求
                $this->cancel_contract_request($tracking_id, $pay_method);
                break;
            case 'refund':
                // 返金要求
                $this->refund_request($tracking_id, $pay_method, empty($now) ? $now : date('Ym', $now));
                break;
            default:
        }
    }
}