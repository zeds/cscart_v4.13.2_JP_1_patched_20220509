<?php

namespace Tygh;

class SbpsPaypay extends Sbps
{
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

        return $this->send_request(SBPS_REQUEST_ID['paypay']['sales'], $data);
    }

    /**
     * 取消返金要求リクエスト
     *
     * @param $tracking_id
     * @param array $add_data
     * @return array|null
     */
    public function cancel_request($tracking_id, $add_data = [])
    {
        $data = [
            'merchant_id' => $this->processor['merchant_id'],
            'service_id' => $this->processor['service_id'],
            'tracking_id' => $tracking_id
        ];

        if (!empty($add_data)) {
            $data = array_merge($data, $add_data);
        }

        return $this->send_request(SBPS_REQUEST_ID['paypay']['cancel'], $data);
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
            'encrypted_flg' => '1',
        ];

        return $this->send_request(SBPS_REQUEST_ID['paypay']['reference'], $data);
    }

    /**
     * 指定モードの要求実行
     *
     * @param $mode
     * @param $tracking_id
     */
    public function exec_mode_request($mode, $tracking_id)
    {
        switch ($mode) {
            case 'cancel':
                $this->cancel_request($tracking_id);
                break;
            case 'sales_confirm':
                $this->sales_request($tracking_id);
                break;
            default:
        }
    }
}