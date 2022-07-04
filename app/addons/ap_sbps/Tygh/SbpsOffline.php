<?php

namespace Tygh;

class SbpsOffline extends Sbps
{
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
            'encrypted_flg' => '1',
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['reference'], $data);
    }
}