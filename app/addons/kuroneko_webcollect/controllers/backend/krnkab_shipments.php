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

// $Id: krnkab_shipments.php by takahashi from cs-cart.jp 2020

use Tygh\Registry;
use Tygh\Shippings\Shippings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 送信データに注文IDが含まれる場合
    if( !empty($_REQUEST['order_ids']) ){

        // 処理コードに応じて処理を実行
        switch($mode){
            case 'add_shipment':    // 出荷情報登録
                foreach($_REQUEST['order_ids'] as $order_id) {
                    if( $_REQUEST['is_registered'][$order_id] == 'NO') {
                        $shipment_data['order_id'] = $order_id;
                        $shipment_data['shipping_id'] = $_REQUEST['shipping_ids'][$order_id];
                        $shipment_data['tracking_number'] = $_REQUEST['tracking_numbers'][$order_id];
                        $shipment_data['carrier'] = $_REQUEST['carriers'][$order_id];
                        if( empty($_REQUEST['delivery_services'][$order_id]) ) {
                            $shipment_data['delivery_service'] = '00';
                        }
                        else {
                            $shipment_data['delivery_service'] = $_REQUEST['delivery_services'][$order_id];
                        }
                        $shipment_data['send_slip_no'] = 'Y';
                        $shipment_data['no_silent'] = true;
                        $shipment_data['bulk_add'] = true;

                        $is_error = false;

                        if( $shipment_data['delivery_service'] == '00' ) {
                            $tracking_number = str_replace('-', '', $shipment_data['tracking_number']);
                            // 支払サービス名を取得
                            $service_name = __("jp_kuroneko_webcollect__ab");

                            // 送り状番号が空の場合
                            if( empty($tracking_number) ) {
                                $is_error = true;
                                fn_set_notification('E', $service_name, __('jp_kuroneko_webcollect_shipment_no_slipno', array('[order_id]' => $order_id)));
                            }
                            // 送り状番号が12桁でない場合
                            elseif( strlen($tracking_number) != 12 ){
                                $is_error = true;
                                fn_set_notification('E', $service_name, __('jp_kuroneko_webcollect_shipment_slipno_not_12digit', array('[order_id]' => $order_id, '[slipno]' => $tracking_number)));
                            }
                            // 送り状番号が数値でない場合
                            elseif( !ctype_digit($tracking_number) ){
                                $is_error = true;
                                fn_set_notification('E', $service_name, __('jp_kuroneko_webcollect_shipment_slipno_not_num', array('[order_id]' => $order_id, '[slipno]' => $tracking_number)));
                            }
                        }

                        // エラーがない場合
                        if( !$is_error ) {
                            // 出荷情報登録
                            fn_update_shipment($shipment_data, 0, 0, true);
                        }
                    }
                }
                break;

            case 'cancel_shipment':    // 出荷情報取消
                foreach($_REQUEST['order_ids'] as $order_id) {
                    if( $_REQUEST['is_registered'][$order_id] == 'YES') {

                        // 注文IDから配送情報IDを取得
                        $shipment_id = db_get_field("SELECT distinct shipment_id FROM ?:shipment_items WHERE order_id = ?i", $order_id);

                        fn_krnkwc_delete_shipment($shipment_id);

                        $shipment_ids[] = $shipment_id;
                    }
                }

                // 出荷情報取消
                fn_delete_shipments($shipment_ids);

                break;

            default:
                // do nothing
        }
    }
    return array(CONTROLLER_STATUS_OK, "krnkwc_cc_shipments.manage");
}

$params = $_REQUEST;


switch($mode){
    // 一覧ページ
    case 'manage':
        $params['check_for_suppliers'] = true;
        $params['company_name'] = true;

        // クロネコ代金後払いサービスで決済した注文の数を取得
        $krnkwc_total = db_get_field("SELECT COUNT(*) FROM ?:jp_krnkwc_cc_status WHERE status_code LIKE '%AB_%'");

        // クロネコ代金後払いサービスで決済した注文が存在する場合
        if( !empty($krnkwc_total) ){
            // クロネコ代金後払いサービスを用いた注文を抽出
            list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);
        // クロネコ代金後払いサービスで決済した注文が存在しない場合
        }else{
            $orders = array();
        }

        $order_info['pay_by_kuroneko_atobarai'] = 'Y';
        $carriers = Shippings::getCarriers();

        Tygh::$app['view']->assign('orders', $orders);
        Tygh::$app['view']->assign('search', $search);
        Tygh::$app['view']->assign('order_info', $order_info);
        Tygh::$app['view']->assign('carriers', $carriers);

        break;
    // その他
    default:
        // do nothing
}
