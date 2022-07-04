<?php

namespace Tygh;

class SbpsLink extends Sbps
{
    /**
     * 購入リクエストデータ作成
     *
     * @param $order_info
     * @param $attr
     * @return mixed
     */
    public function create_purchase_request_data($order_info, $attr)
    {
        $data = [];

        // 支払方法
        $pay_method = $this->format_pay_method();
        if (!empty($pay_method)) {
            $data['pay_method'] = $pay_method;
        }

        // マーチャントID
        $data['merchant_id'] = $this->processor['merchant_id'];

        // サービスID
        $data['service_id'] = $this->processor['service_id'];

        // 顧客ID
        $data['cust_code'] = $this->format_cust_code($order_info['user_id']);

        // 購入ID
        $data['order_id'] = $this->format_order_id();

        // 商品ID
        $data['item_id'] = self::ITEM_ID;

        // 商品名称
        if (!empty($order_info['products'])) {
            $data['item_name'] = $this->format_item_name($order_info['products']);
        }

        // 金額（税込）
        $data['amount'] = round($order_info['total']);

        // 購入タイプ
        if (isset($attr['pay_type'])) {
            $data['pay_type'] = $attr['pay_type'];
        }

        // 自動課金タイプ
        if (isset($attr['auto_charge_type'])) {
            $data['auto_charge_type'] = $attr['auto_charge_type'];
        }

        // サービスタイプ : 売上(購入)固定
        $data['service_type'] = '0';

        // 決済区分
        if (isset($attr['div_settele'])) {
            $data['div_settele'] = $attr['div_settele'];
        }

        // キャンペーンタイプ
        if (isset($this->processor['camp_type'])) {
            $data['camp_type'] = $this->processor['camp_type'];
        }

        // 顧客利用端末タイプ : PC固定
        $data['terminal_type'] = '0';

        // 決済完了時URL
        if (isset($attr['success_url'])) {
            $data['success_url'] = $attr['success_url'];
        }

        // 決済キャンセル時URL
        if (isset($attr['cancel_url'])) {
            $data['cancel_url'] = $attr['cancel_url'];
        }

        // エラー時URL
        if (isset($attr['error_url'])) {
            $data['error_url'] = $attr['error_url'];
        }

        // 決済通知用CGI
        if (isset($attr['pagecon_url'])) {
            $data['pagecon_url'] = $attr['pagecon_url'];
        }

        return $this->create_request_data($data, false);
    }

    /**
     * 接続URL取得
     *
     * @return string
     */
    public function get_connection_url()
    {
        $connection_url = SBPS_PURCHASE_REQUEST_URL[$this->processor['mode']];
        return !empty($connection_url) ? $connection_url : 'https://stbfep.sps-system.com/Extra/BuyRequestAction.do';
    }

    /**
     * 支払い方法毎の決済ステータス取得
     *
     * @param $pay_method
     * @return int|string
     */
    public function get_pay_method_payment_status($pay_method)
    {
        $result = SBPS_PAYMENT_STATUS_SALES_CONFIRM;

        // 指定売上が指定できる支払い方法の場合、ステータスを「与信OK」に変更
        if (in_array($pay_method, SBPS_PAY_METHOD_SPECIFIED_SALES, true)) {
            $result = SBPS_PAYMENT_STATUS_UNKNOWN_OK;
        }

        // オフラインの支払い方法の場合、ステータスを「未入金」に変更
        if (in_array($pay_method, SBPS_PAY_METHOD_PROCESS['offline'], true)) {
            $result = SBPS_OFFLINE_PAYMENT_STATUS_UNPAID;
        }

        return $result;
    }

    /**
     * 支払い方法毎の注文ステータス取得
     *
     * @param $pay_method
     * @return int|string
     */
    public function get_pay_method_order_status($pay_method)
    {
        $result = 'O';

        if (in_array($pay_method, SBPS_PAY_METHOD_ORDER_STATUS_P, 'true')) {
            $result = 'P';
        }

        return $result;
    }

    /**
     * 受信データチェック
     *
     * @param $order
     * @param $receive_data
     * @return bool
     */
    function valid_receive_data($order, $receive_data)
    {
        // 処理結果ステータス
        if (empty($receive_data['res_result'])) {
            return false;
        }

        // 金額チェック
        if (isset($receive_data['amount']) && (round($order['total']) != $receive_data['amount'])) {
            return false;
        }

        // ユーザーIDチェック(ゲストは除外するように対応)
        if (strpos($receive_data['cust_code'], 'guest') === false && ("ap_{$order['user_id']}" != $receive_data['cust_code'])) {
            return false;
        }

        // マーチャントIDチェック
        $processor_data = fn_ap_sbps_get_processor_data($order['order_id']);
        if ($receive_data['merchant_id'] !== $processor_data['processor_params']['merchant_id']) {
            return false;
        }

        return true;
    }
}