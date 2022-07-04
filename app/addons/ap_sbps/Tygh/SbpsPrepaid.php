<?php

namespace Tygh;

class SbpsPrepaid extends Sbps
{
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
            'processing_datetime' => date('YmdHis'),
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['cancel'], $data);
    }
}