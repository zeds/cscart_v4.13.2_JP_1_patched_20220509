<?php

namespace Tygh;

require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/pear/http/Request.php');

class Sbps
{
    const ITEM_ID = 'SBPS_PRODUCTS';

    protected $order_id;
    protected $processor;
    public $errors;

    /**
     * Sbps constructor.
     *
     * @param $order_id
     * @param $processor
     */
    public function __construct($order_id = null, $processor = null)
    {
        $this->order_id = $order_id;
        $this->processor = $processor;
    }

    /**
     * setter order_id
     *
     * @param $order_id
     */
    public function set_order_id($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * setter processor
     *
     * @param $processor
     */
    public function set_processor($processor)
    {
        $this->processor = $processor;
    }

    /**
     * setter data
     *
     * @param $data
     */
    public function set_data($data)
    {
        if (!empty($data['order_id'])) {
            $this->order_id = $data['order_id'];
        }

        if (!empty($data['processor'])) {
            $this->processor = $data['processor'];
        }
    }

    /**
     * 結果整形
     *
     * @param $info
     * @return array
     */
    public function format_reference($info)
    {
        $info = array_map(function($value) {
            return !empty($value) ? trim(openssl_decrypt($value, 'DES-EDE3-CBC', $this->processor['encrypt_key'], OPENSSL_ZERO_PADDING, $this->processor['init_key'])) : '';
        }, $info);

        return $info;
    }

    /**
     * 実行処理チェック
     *
     * @param $order_id
     * @param $mode
     * @param $process
     * @return bool
     */
    public function valid_mode_exec($order_id, $mode, $process)
    {
        $result = false;
        $payment_info = fn_ap_sbps_get_payment_info($order_id, $process);

        if (!empty($payment_info)) {
            $pay_method = array_key_exists('pay_method', $payment_info) ? $payment_info['pay_method'] : $process;

            // ステータス毎の可能な処理チェック
            if (!empty(SBPS_EXEC_PERMISSION[$pay_method]['status'][$payment_info['payment_status']])) {
                $status_exec_permission = array_map(function ($mode){
                    return trim($mode, '_');
                }, SBPS_EXEC_PERMISSION[$pay_method]['status'][$payment_info['payment_status']]);

                $result = in_array($mode, $status_exec_permission, true);
            }

            // 全ステータスで可能な処理チェック
            if (!$result && !empty(SBPS_EXEC_PERMISSION[$pay_method]['all'])) {
                $all_exec_permission = array_map(function ($mode){
                    return trim($mode, '_');
                }, SBPS_EXEC_PERMISSION[$pay_method]['all']);

                $result = in_array($mode, $all_exec_permission, true);
            }
        }

        return $result;
    }

    /**
     * 実行処理チェック(簡易継続)
     *
     * @param $order_id
     * @param $mode
     * @param $process
     * @return array|bool
     */
    public function valid_rb_mode_exec($order_id, $mode, $process)
    {
        $result = false;
        $payment_info = fn_ap_sbps_get_payment_info($order_id, $process);

        // 課金中で可能な処理チェック
        if ($payment_info['is_charge'] === '1') {
            if (!empty(SBPS_EXEC_PERMISSION[$payment_info['pay_method']]['charge'])) {
                $charge_exec_permission = $status_exec_permission = array_map(function ($mode){
                    return trim($mode, '_');
                }, SBPS_EXEC_PERMISSION[$payment_info['pay_method']]['charge']);

                $result = in_array($mode, $charge_exec_permission, true);
            }
        }

        if (!$result) {
            $result = $this->valid_mode_exec($order_id, $mode, $process);
        }

        return $result;
    }

    /**
     * 実行処理チェック(定期購入)
     *
     * @param $order_id
     * @param $mode
     * @param $process
     * @return array|bool
     */
    public function valid_rp_mode_exec($order_id, $mode, $process)
    {
        $result = false;
        $payment_info = fn_ap_sbps_get_payment_info($order_id, $process);

        // 継続中で可能な処理チェック
        if ($payment_info['is_canceled'] === '0') {
            if (!empty(SBPS_EXEC_PERMISSION[$payment_info['pay_method']]['purchase'])) {
                $purchase_exec_permission = $status_exec_permission = array_map(function ($mode){
                    return trim($mode, '_');
                }, SBPS_EXEC_PERMISSION[$payment_info['pay_method']]['purchase']);

                $result = in_array($mode, $purchase_exec_permission, true);
            }
        }

        if (!$result) {
            $result = $this->valid_mode_exec($order_id, $mode, $process);
        }

        return $result;
    }

    /**
     * ソフトバンク購入IDから注文IDを抽出
     *
     * @param $sbps_order_id
     * @return bool|string
     */
    public function extract_order_id($sbps_order_id)
    {
        // TODO: テスト用に[ap]付与考慮
        return substr($sbps_order_id, 2, strlen($sbps_order_id) - 14);
    }

    /**
     * 支払方法整形
     *
     * @return bool
     */
    protected function format_pay_method()
    {
        $result = false;

        if (!empty($this->processor['pay_methods'])) {
            $pay_methods = [];

            foreach ($this->processor['pay_methods'] as $pay_method => $value) {
                if ($value === 'true') {
                    $pay_methods[] = $pay_method;
                }
            }

            if (!empty($pay_methods)) {
                $result = implode(',', $pay_methods);
            }
        }

        return $result;
    }

    /**
     * 注文ID整形
     *
     * @return string
     */
    protected function format_order_id()
    {
        // TODO: テスト用に[ap]付与
        return  "ap{$this->order_id}" . date('ymdHis');
    }

    /**
     * 顧客ID整形
     *
     * @param $user_id
     * @return int|string
     */
    protected function format_cust_code($user_id)
    {
        if (!empty($user_id)) {
            // 会員登録済みの決済の場合
            $cust_code = (int)$user_id;
        } else {
            // ゲスト購入の場合
            mt_srand(microtime() * 100000);
            $cust_code = 'guest' . rand(10000, 99999);
        }

        // TODO: テスト用に[ap]付与
        return "ap_{$cust_code}";
    }

    /**
     * 商品名称整形
     *
     * @param $products
     * @return mixed
     */
    protected function format_item_name($products)
    {
        $product = reset($products);

        if (CART_LANGUAGE != 'ja' && CART_LANGUAGE != 'en') {
            // 商品名に商品コードをセット
            $product['product'] = empty($product['product_code']) ? self::ITEM_ID : $product['product_code'];
        }

        // 商品名整形
        $product_name = $this->replace_machine_dependent_chars(fn_lcjp_replace_pdc($product['product']));
        $product_name = $this->replace_disabled_chars($product_name);
        $item_name = count($products) === 1 ?  $product_name : mb_strcut($product_name, 0, 36, 'UTF-8') . ' ' . __('jp_sbps_etc');

        return str_replace('"', "'", $item_name);
    }

    /**
     * リクエスト送信
     *
     * @param $request_id
     * @param $data
     * @return array
     */
    protected function send_request($request_id, $data)
    {
        // http_request初期化
        $request = new \HTTP_Request(SBPS_API_URL[$this->processor['mode']], ['timeout' => '30']);

        // http_request設定
        $request->setBasicAuth("{$this->processor['merchant_id']}{$this->processor['service_id']}", $this->processor['hash_key']);
        $request->setMethod(HTTP_REQUEST_METHOD_POST);
        $request->addHeader('Content-Type', 'text/xml');
        $request->addRawPostData($this->create_request_xml($request_id, $this->create_request_data($data)));

        // http_request実行
        $response = $request->sendRequest();

        if (\PEAR::isError($response) || $request->getResponseCode() !== 200) {
            $this->errors = [
                'response_code' => $request->getResponseCode()
            ];
        }

        $body = $this->body_to_array($request->getResponseBody());

        if (empty($body) || $body['res_result'] !== 'OK') {
            $this->errors = [
                'response_code' => $request->getResponseCode(),
                'err_code' => !empty($body['res_err_code']) ? $body['res_err_code'] : '',
            ];
        }

        return $body;
    }

    /**
     * リクエストデータ作成
     *
     * @param $data
     * @param bool $encode
     * @return mixed
     */
    protected function create_request_data($data, $encode = true)
    {
        // リクエスト日時
        $data['request_date'] = date('YmdHis');
        $sps_hashcode = '';

        // Shift_JIS変換
        array_walk_recursive($data, function (&$value) use (&$sps_hashcode, $encode) {
            if (!is_array($value)) {
                if ($encode) {
                    $value = mb_convert_encoding($value, 'Shift_JIS', 'UTF-8');
                }

                $sps_hashcode .= $value;
            }
        });

        $hash_key = $this->processor['hash_key'];

        if ($encode) {
            $hash_key = mb_convert_encoding($this->processor['hash_key'], 'Shift_JIS', 'UTF-8');
        }

        // チェックサム
        $data['sps_hashcode'] = sha1($sps_hashcode . $hash_key);

        return $data;
    }

    /**
     * リクエスト用XMLデータ作成
     *
     * @param $request_id
     * @param $request_data
     * @return string
     */
    private function create_request_xml($request_id, $request_data)
    {
        $xml = '<?xml version="1.0" encoding="Shift_JIS"?>';
        $xml .= "<sps-api-request id=\"{$request_id}\">";

        foreach ($request_data as $key => $value) {
            $this->create_xml_element($xml, $key, $value);
        }

        $xml .= '</sps-api-request>';

        return $xml;
    }

    /**
     * XML要素作成
     *
     * @param $xml
     * @param $key
     * @param $value
     * @param string $parent_key
     */
    private function create_xml_element(&$xml, $key, $value, $parent_key = '')
    {
        if (is_array($value)) {
            if (!empty(SBPS_XML_CHILD_ELEMENTS[$parent_key])) {
                $key = SBPS_XML_CHILD_ELEMENTS[$parent_key];
            }

            $xml .= "<${key}>";

            foreach ($value as $k => $v) {
                $this->create_xml_element($xml, $k, $v, $key);
            }

            $xml .= "</${key}>";
        } else {
            // 3DES-CBCで暗号化
            if (in_array($key, SBPS_XML_3DES_ELEMENTS, true)) {
                $value = $value . str_repeat(' ', 8 - (strlen($value) % 8));
                $value = openssl_encrypt($value, 'DES-EDE3-CBC', $this->processor['encrypt_key'], OPENSSL_ZERO_PADDING, $this->processor['init_key']);
            }

            // base64エンコード
            if (in_array($key, SBPS_XML_BASE64_ENCODE_ELEMENTS, true)) {
                $value = base64_encode($value);
            }

            $xml .= "<{$key}>{$value}</{$key}>";
        }
    }

    /**
     * bodyの内容を配列に変換
     *
     * @param $body
     * @return mixed|null
     */
    private function body_to_array($body)
    {
        return empty($body) ? null : json_decode(json_encode(simplexml_load_string($body)), true);
    }

    /**
     * 機種依存文字の置き換え
     *
     * @param $string
     * @param string $encoding
     * @return string
     */
    private function replace_machine_dependent_chars($string, $encoding = 'UTF-8')
    {
        $chars = [];
        $length = mb_strlen($string, $encoding);

        foreach (range(0, $length - 1) as $index) {
            $char = mb_substr($string, $index, 1, $encoding);

            if ($char !== '?' && mb_convert_encoding($char, 'Shift_JIS', $encoding) === '?') {
                $char = '□';
            }

            $chars[] = $char;
        }

        return implode($chars);
    }

    /**
     * 使用不可文字の置き換え
     *
     * @param $string
     * @param string $encoding
     * @return string
     */
    private function replace_disabled_chars($string, $encoding = 'UTF-8')
    {
        $replace_chars = [
            '+' => '＋', '/' => '／', '―' => '-', '\\' => '□', '~' => '～', '‖' => '|', '-' => '-',
            '¢' => '□', '£' => '□', '¬' => '□', '〜' => '～'
        ];

        $string = mb_convert_kana($string, 'K', $encoding);
        $string = strtr($string, $replace_chars);

        return $string;
    }
}