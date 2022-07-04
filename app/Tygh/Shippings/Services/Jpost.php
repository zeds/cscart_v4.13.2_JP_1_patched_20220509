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
 * ゆうパックに関する配送料金の計算
 */
class Jpost implements IService
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

        // ゆうパックは発着地が共に日本国内の場合のみ有効
        if ($origination['country'] != 'JP' || $location['country'] != 'JP'){
            if( $origination['country'] == 'JP' && empty($location['country']) && $location['country_descr'] == 'Japan' ){
                // do nothing
            }else{
                return $return;
            }
        }

        // 配送サービスや配送元・配送先所在地に関する情報をセット
        $shipping_service_info = array('carrier_code' => 'jpost',
                                    'service_code' => strtolower($this->_shipping_info['service_code']),
                                    'origination' => $origination['state'] == $location['state'] ? 'X' : $this->_getZone($origination['state']),
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
        // 都道府県コードを地帯コード('A'～'I')に変換する
        //  北海道　:'A' = 北海道
        //  東北　　:'B' = 青森県,岩手県,秋田県,山形県,宮城県,福島県
        //  関東	:'C' = 茨城県,栃木県,群馬県,埼玉県,千葉県,東京都,神奈川県,山梨県
        //  信越	:'D' = 長野県,新潟県
        //  北陸  　:'E' = 富山県,石川県,福井県
        //  東海  　:'F' = 静岡県,愛知県,三重県,岐阜県
        //  近畿  　:'G' = 滋賀県,京都府,大阪府,兵庫県,奈良県,和歌山県
        //  中国　　:'H' = 鳥取県,岡山県,島根県,広島県,山口県
        //  四国 　 :'I' = 香川県,徳島県,愛媛県,高知県
        //  九州 　 :'J' = 福岡県,佐賀県,長崎県,大分県,熊本県,宮崎県,鹿児島県
        //  沖縄 　 :'K' = 沖縄県
        $a_zonemap = array(
            '北海道'=>'A',
            '青森県'=>'B',
            '岩手県'=>'B',
            '宮城県'=>'B',
            '秋田県'=>'B',
            '山形県'=>'B',
            '福島県'=>'B',
            '茨城県'=>'C',
            '栃木県'=>'C',
            '群馬県'=>'C',
            '埼玉県'=>'C',
            '千葉県'=>'C',
            '東京都'=>'C',
            '神奈川県'=>'C',
            '新潟県'=>'D',
            '富山県'=>'E',
            '石川県'=>'E',
            '福井県'=>'E',
            '山梨県'=>'C',
            '長野県'=>'D',
            '岐阜県'=>'F',
            '静岡県'=>'F',
            '愛知県'=>'F',
            '三重県'=>'F',
            '滋賀県'=>'G',
            '京都府'=>'G',
            '大阪府'=>'G',
            '兵庫県'=>'G',
            '奈良県'=>'G',
            '和歌山県'=>'G',
            '鳥取県'=>'H',
            '島根県'=>'H',
            '岡山県'=>'H',
            '広島県'=>'H',
            '山口県'=>'H',
            '徳島県'=>'I',
            '香川県'=>'I',
            '愛媛県'=>'I',
            '高知県'=>'I',
            '福岡県'=>'J',
            '佐賀県'=>'J',
            '長崎県'=>'J',
            '熊本県'=>'J',
            '大分県'=>'J',
            '宮崎県'=>'J',
            '鹿児島県'=>'J',
            '沖縄県'=>'K'
        );
        return $a_zonemap[$state_name];
    }

    // ゆうパックのお届け時間帯を取得
    public function getJpostDeliveryTime(){
        $delivery_timetable = array('指定なし', '午前中', '12時～14時', '14時～16時', '16時～18時', '18時～20時', '20時～21時');
        return $delivery_timetable;
    }

    /**
     * Returns shipping service information
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_jpost'),
            'tracking_url' => 'http://tracking.post.japanpost.jp/service/singleSearch.do?org.apache.struts.taglib.html.TOKEN=&searchKind=S002&locale=ja&SVID=&reqCodeNo1=%s'
        );
    }
}
