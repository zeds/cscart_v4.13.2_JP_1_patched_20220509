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
//
// $Id: orders.functions.php by ari from cs-cart.jp 2013
//

// 注文データエクスポート時に日本国内配送のお届け希望日時をエクスポートする
function fn_exim_localization_jp_get_delivery_data($order_id, $key) {
	$res = '';
	switch($key) {
		case 'T':
			// お届け希望時間
			$_get_column = 'delivery_timing';
		break;
		case 'D':
			// お届け希望日
			$_get_column = 'delivery_date';
		break;
		case 'N':
			// 追跡番号
			list($shipments) = fn_get_shipments_info(array('order_id' => $order_id, 'advanced_info' => true));
			$multi_shipping = (count($shipments) > 1 ? true : false);
			if(!empty($shipments)) {
				foreach($shipments as $shipment) {
					$_buf = '';
					// 複数データの場合カンマで区切る
					if($res != '') {
						$res .= ',';
					}
					if($multi_shipping) {
						$_buf .= $shipment['shipping'] . ':' . $shipment['tracking_number'];
					} else {
						$_buf .= $shipment['tracking_number'];
					}
					$res .= $_buf;
				}
			}
			return $res;
		break;
	}
	$order_data = fn_get_order_info($order_id, false);
	if(!empty($order_data['shipping'])) {
		// 複数の配送サービスがあるか
		$multi_shipping = (count($order_data['shipping']) > 1 ? true : false);
		foreach($order_data['shipping'] as $shipping) {
			$_buf = '';
			if(!empty($shipping[$_get_column])) {
				// 複数データの場合カンマで区切る
				if($res != '') {
					$res .= ',';
				}
				if($multi_shipping) {
					// 複数データの場合配送サービス名を入れる
					$_buf .= $shipping['shipping'] . ':' . $shipping[$_get_column];
				} else {
					$_buf .= $shipping[$_get_column];
				}
			}
			$res .= $_buf;
		}
	}
	return $res;
}
