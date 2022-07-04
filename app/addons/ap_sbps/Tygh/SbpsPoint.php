<?php

namespace Tygh;

class SbpsPoint extends Sbps
{
    /**
     * 返金要求リクエスト
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

        if ($pay_method === 'saisonpoint') {
            $data['processing_datetime'] = date('YmdHis');
        }

        if ($pay_method === 'tpoint') {
            $data['encrypted_flg'] = '1';
        }

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['cancel'], $data);
    }
}