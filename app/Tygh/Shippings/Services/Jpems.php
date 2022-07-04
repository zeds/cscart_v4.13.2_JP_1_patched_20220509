<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Shippings\Services;

use Tygh\Shippings\IService;
use Tygh\Http;
use Tygh\Registry;

/**
 * EMSに関する配送料金の計算
 */
class Jpems implements IService
{
    /**
     * Availability multithreading in this module
     *
     * @var array $_allow_multithreading
     */
    private $_allow_multithreading = false;

    private function _getRates($response)
    {
        // 本配送方法では利用しない関数のため単純に true を返す
        return true;
    }

    /**
     * Checks if shipping service allows to use multithreading
     *
     * @return bool true if allow
     */
    public function allowMultithreading()
    {
        return $this->_allow_multithreading;
    }

    /**
     * Sets data to internal class variable
     *
     * @param array $shipping_info
     */
    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;
    }

     /**
     * Gets shipping cost and information about possible errors
     *
     * @param  string $resonse Reponse from Shipping service server
     * @return array  Shipping cost and errors
     */
    public function processResponse($response)
    {
        // 発送元に関するデータを取得
        $origination = $this->_shipping_info['package_info']['origination'];

        // 発送先に関するデータを取得
        $location = $this->_shipping_info['package_info']['location'];

        // 送料情報を初期化
        $return = array(
            'cost' => false,
            'error' => false,
        );

        // EMSは出発地が日本国内、配送先が海外の場合にのみ有効
        if ($origination['country'] != 'JP' || $location['country'] == 'JP'){
            return $return;
        }

        // 配送サービスや配送元・配送先所在地に関する情報をセット
        $shipping_service_info = array('carrier_code' => 'jpems',
                                    'service_code' => strtolower($this->_shipping_info['service_code']),
                                    'origination' => 'Z',
                                    'destination' => $this->_getZone($location['country']),
                                    'company_id' => fn_lcjp_get_company_id_by_shipping_id($this->_shipping_info['shipping_id'])
        );

        // 配送重量をセット
        $shipping_weight = $this->_shipping_info['package_info']['W'];

        // 送料を取得
        $shipping_rate = fn_lcjp_get_shipping_rate($shipping_service_info, $shipping_weight);

        // 送料が設定されている場合
        if( is_numeric($shipping_rate) && $shipping_rate >= 0 ){
            // 送料情報をセット
            $return = array(
                'cost' => $shipping_rate,
                'error' => false,
            );
        }

        return $return;
    }

    /**
     * Implementation does not need
     *
     * @return null
     */
    public function processErrors($response)
    {
        return null;
    }

    /**
     * Prepare request information
     *
     * @return array Prepared data
     */
    public function getRequestData()
    {
        // 本配送方法では利用しない関数のため単純に true を返す
        return true;
    }

    /**
     * Process simple request to shipping service server
     *
     * @return string Server response
     */
    public function getSimpleRates()
    {
        // 外部接続不要のため単純に true を返す
        return true;
    }

    private function _getZone($state_name)
    {
        // 国コードを地帯コード('A'～'D')に変換する
        //  'A' = 第1地帯（アジア）
        //  'B' = 第2地帯（オセアニア・北米・中米・中近東）
        //  'C' = 第3地帯（ヨーロッパ）
        //  'D' = 第4地帯（南米・アフリカ）
        $a_zonemap = array(
            'AE'=>'B',
            'AR'=>'D',
            'AT'=>'C',
            'AU'=>'B',
            'AZ'=>'C',
            'BB'=>'B',
            'BD'=>'A',
            'BE'=>'C',
            'BG'=>'C',
            'BH'=>'B',
            'BN'=>'A',
            'BR'=>'D',
            'BT'=>'A',
            'BW'=>'D',
            'BY'=>'C',
            'CA'=>'B',
            'CH'=>'C',
            'CI'=>'D',
            'CL'=>'D',
            'CN'=>'A',
            'CO'=>'D',
            'CR'=>'B',
            'CU'=>'B',
            'CY'=>'B',
            'CZ'=>'C',
            'DE'=>'C',
            'DJ'=>'D',
            'DK'=>'C',
            'DZ'=>'D',
            'EC'=>'D',
            'EE'=>'C',
            'EG'=>'D',
            'ES'=>'C',
            'ET'=>'D',
            'FI'=>'C',
            'FJ'=>'B',
            'FR'=>'C',
            'GA'=>'D',
            'GB'=>'C',
            'GH'=>'D',
            'GR'=>'C',
            'GU'=>'A',
            'HK'=>'A',
            'HN'=>'B',
            'HR'=>'C',
            'HU'=>'C',
            'ID'=>'A',
            'IE'=>'C',
            'IL'=>'B',
            'IN'=>'A',
            'IR'=>'B',
            'IS'=>'C',
            'IT'=>'C',
            'JM'=>'B',
            'JO'=>'B',
            'KE'=>'D',
            'KH'=>'A',
            'KP'=>'A',
            'KR'=>'A',
            'KW'=>'B',
            'LA'=>'A',
            'LI'=>'C',
            'LK'=>'A',
            'LT'=>'C',
            'LU'=>'C',
            'LV'=>'C',
            'MA'=>'D',
            'MG'=>'D',
            'MK'=>'C',
            'MM'=>'A',
            'MN'=>'A',
            'MO'=>'A',
            'MP'=>'A',
            'MT'=>'C',
            'MU'=>'D',
            'MV'=>'A',
            'MX'=>'B',
            'MY'=>'A',
            'NC'=>'B',
            'NG'=>'D',
            'NL'=>'C',
            'NO'=>'C',
            'NP'=>'A',
            'NZ'=>'B',
            'OM'=>'B',
            'PA'=>'B',
            'PE'=>'D',
            'PG'=>'B',
            'PH'=>'A',
            'PK'=>'A',
            'PL'=>'C',
            'PT'=>'C',
            'PY'=>'D',
            'QA'=>'B',
            'RO'=>'C',
            'RU'=>'C',
            'RW'=>'D',
            'SA'=>'B',
            'SB'=>'B',
            'SD'=>'D',
            'SE'=>'C',
            'SG'=>'A',
            'SI'=>'C',
            'SK'=>'C',
            'SN'=>'D',
            'SV'=>'B',
            'SY'=>'B',
            'TG'=>'D',
            'TH'=>'A',
            'TN'=>'D',
            'TR'=>'B',
            'TT'=>'B',
            'TW'=>'A',
            'TZ'=>'D',
            'UA'=>'C',
            'UG'=>'D',
            'US'=>'B',
            'UY'=>'D',
            'VE'=>'D',
            'VN'=>'A',
            'ZA'=>'D',
            'ZW'=>'D'
        );

        return $a_zonemap[$state_name];
    }

    /**
     * Returns shipping service information
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_jpems'),
            'tracking_url' => 'http://tracking.post.japanpost.jp/service/singleSearch.do?searchKind=S004&locale=en&reqCodeNo1=%s'
        );
    }
}
