<?php

namespace Tygh;

class SbpsRbWallet extends Sbps
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
        ];

        return $this->send_request(SBPS_REQUEST_ID[$pay_method]['cancel_contract'], $data);
    }
}