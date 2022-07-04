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

// Modified by tommy from cs-cart.jp 2016

namespace Tygh\Shippings\Services;

use Tygh\Shippings\IService;
use Tygh\Http;
use Tygh\Registry;

/**
 * 福山通運に関する配送料金の計算
 */
class Fukutsu implements IService
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

        // 福山通運は発着地が共に日本国内の場合のみ有効
        if ($origination['country'] != 'JP' || $location['country'] != 'JP'){
            if( $origination['country'] == 'JP' && empty($location['country']) && $location['country_descr'] == 'Japan' ){
                // do nothing
            }else{
                return $return;
            }
        }

        // 福山通運は沖縄発着便は対応外
        if ($origination['state'] == '沖縄県' || $location['state'] == '沖縄県') {
            return $return;
        }

        // 配送サービスや配送元・配送先所在地に関する情報をセット
        $shipping_service_info = array('carrier_code' => 'fukutsu',
                                    'service_code' => strtolower($this->_shipping_info['service_code']),
                                    'origination' => $this->_getZone($origination['state']),
                                    'destination' => $this->_getZone($location['state']),
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
        // 都道府県コードを地帯コード('A'～'K')に変換する
        //  北海道　:'A' = 北海道
        //  北東北　:'B' = 青森県,岩手県,秋田県
        //  南東北　:'C' = 宮城県,山形県,福島県
        //  関東	:'D' = 茨城県,栃木県,群馬県,埼玉県,千葉県,東京都,神奈川県,山梨県
        //  信越	:'E' = 長野県,新潟県
        //  北陸	:'F' = 富山県,石川県,福井県
        //  中部	:'G' = 岐阜県,静岡県,愛知県,三重県
        //  関西  　:'H' = 滋賀県,京都府,大阪府,兵庫県,奈良県,和歌山県
        //  中国  　:'I' = 鳥取県,島根県,岡山県,広島県,山口県
        //  四国  　:'J' = 徳島県,香川県,愛媛県,高知県
        //  北九州　　:'K' = 福岡県,佐賀県,長崎県,大分県
        //  南九州　　:'L' = 熊本県,宮崎県,鹿児島県
        $a_zonemap = array(
            '北海道'=>'A',
            '青森県'=>'B',
            '岩手県'=>'B',
            '宮城県'=>'C',
            '秋田県'=>'B',
            '山形県'=>'C',
            '福島県'=>'C',
            '茨城県'=>'D',
            '栃木県'=>'D',
            '群馬県'=>'D',
            '埼玉県'=>'D',
            '千葉県'=>'D',
            '東京都'=>'D',
            '神奈川県'=>'D',
            '新潟県'=>'E',
            '富山県'=>'F',
            '石川県'=>'F',
            '福井県'=>'F',
            '山梨県'=>'D',
            '長野県'=>'E',
            '岐阜県'=>'G',
            '静岡県'=>'G',
            '愛知県'=>'G',
            '三重県'=>'G',
            '滋賀県'=>'H',
            '京都府'=>'H',
            '大阪府'=>'H',
            '兵庫県'=>'H',
            '奈良県'=>'H',
            '和歌山県'=>'H',
            '鳥取県'=>'I',
            '島根県'=>'I',
            '岡山県'=>'I',
            '広島県'=>'I',
            '山口県'=>'I',
            '徳島県'=>'J',
            '香川県'=>'J',
            '愛媛県'=>'J',
            '高知県'=>'J',
            '福岡県'=>'K',
            '佐賀県'=>'K',
            '長崎県'=>'K',
            '熊本県'=>'L',
            '大分県'=>'K',
            '宮崎県'=>'L',
            '鹿児島県'=>'L'
        );
        return $a_zonemap[$state_name];
    }

    // 福山通運のお届け時間帯を取得
    public function getFukutsuDeliveryTime(){
        $delivery_timetable = array();
        return $delivery_timetable;
    }
}
