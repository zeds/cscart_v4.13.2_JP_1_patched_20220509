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

// $Id: shipments.pre.php by takahashi from cs-cart.jp 2018

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 配送情報が削除された場合にクロネコヤマト向け配送情報レコードも削除する
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'm_delete' && !empty($_REQUEST['shipment_ids'])) {
        $krnkkb_shipment_ids = $_REQUEST['shipment_ids'];
        foreach($krnkkb_shipment_ids as $krnkkb_shipment_id){
            fn_krnkkb_delete_shipment($krnkkb_shipment_id);
        }
    }elseif($mode == 'delete' && !empty($_REQUEST['shipment_ids']) && is_array($_REQUEST['shipment_ids'])) {
        $krnkkb_shipment_ids = $_REQUEST['shipment_ids'];
        foreach($krnkkb_shipment_ids as $krnkkb_shipment_id){
            fn_krnkkb_delete_shipment($krnkkb_shipment_id);
        }
    }
}
