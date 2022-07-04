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


use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'details') {
    
    $order_id = (int) $_REQUEST['order_id'];
    $gmo_transaction_id = db_get_field("SELECT gmo_transaction_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if(isset($gmo_transaction_id)) {

        $order_info = fn_get_order_info($order_id, false, false);
        
        //支払方法情報取得
        $payment_id = $order_info['payment_method']['payment_id'];
        $processor_data = fn_get_payment_method_data($payment_id);
        $payment_params = $processor_data['processor_params'];
   
        //与信審査結果
        $result = fn_gmo_deferred_payment_credit($order_id,$gmo_transaction_id,$payment_params);
        Tygh::$app['view']->assign('result', $result);

    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode == 'update_details') {
        
    $order_id = (int) $_REQUEST['order_id'];
    $gmo_transaction_id = db_get_field("SELECT gmo_transaction_id FROM ?:orders WHERE order_id = ?i", $order_id);

    if (!empty($_REQUEST['update_shipping']) && isset($gmo_transaction_id)) {    
            $order_info = fn_get_order_info($order_id, false, false);  
            foreach ($_REQUEST['update_shipping'] as $shipment_group) {
                foreach($shipment_group as $shipment) {
                    fn_gmo_deferred_update_shipment($order_info,$shipment,$gmo_transaction_id);
                }
            }
                
    }
    
}
