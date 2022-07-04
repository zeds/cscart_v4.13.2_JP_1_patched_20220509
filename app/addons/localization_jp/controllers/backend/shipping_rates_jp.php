<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2009 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: shipping_rates_jp.php by tommy from cs-cart.jp 2015
// 基本設定 -> 配送設定 の廃止に伴い処理を変更

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


if ($mode == 'update') {
    // 送料情報を更新する場合
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if(!empty($_POST['carrier']) && !empty($_POST['service']) && !empty($_POST['zone'])) {
            // 送料を更新するショップ（出品者）のIDを抽出条件に加える
            $_condition = fn_get_company_condition('?:jp_shipping_rates.company_id');

			$jp_carrier_rates = db_get_row("SELECT ?:jp_shipping_rates.* FROM ?:jp_shipping_rates WHERE ?:jp_shipping_rates.carrier_code = ?s AND ?:jp_shipping_rates.service_code = ?s AND ?:jp_shipping_rates.zone_id = ?i ?p", $_POST['carrier'], $_POST['service'], $_POST['zone'], $_condition);

            $old_rates = unserialize($jp_carrier_rates['shipping_rates']);

			$new_rates = array();
			if(!empty($old_rates)) {
				foreach($old_rates as $key => $val) {
					$new_rate_detail = array();
					foreach($val['rates'] as $detail_key => $detail_val) {
						if($_POST['carrier'] == 'jpems') {
							$post_name = $val['size'] . '_' . ($val['weight'] * 100) . '_' . $detail_key;
						} else {
							$post_name = $val['size'] . '_' . $val['weight'] . '_' . $detail_key;
						}
						$new_rate_detail[$detail_key] = (int)$_POST[$post_name];
					}
					$new_rates[$key] = array('size' =>  $val['size'], 'weight' =>  $val['weight'], 'rates' => $new_rate_detail);
				}
				db_query("UPDATE ?:jp_shipping_rates SET shipping_rates = ?s WHERE rate_id = ?i", serialize($new_rates), $jp_carrier_rates['rate_id']);
				$suffix = "&carrier=" . $_POST['carrier'] . "&" . $_POST['carrier'] . "_service_id=" . $_POST['service'] . "&" . $_POST['carrier'] . "_shipping_zone=" . $_POST['zone'];
			}
		}else{
            fn_set_notification('E', __('error'), __("jp_shipping_select_service_and_origin"));
        }
	}
	return array(CONTROLLER_STATUS_OK, "shipping_rates_jp.manage$suffix");
}
//
// OUPUT routines
//
if ($mode == 'manage') {
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$default_carrier = empty($_REQUEST['carrier']) ? '' : $_REQUEST['carrier'];
		$default_service = empty($_REQUEST[$default_carrier . '_service_id']) ? '' : $_REQUEST[$default_carrier . '_service_id'];
		$default_zone = empty($_REQUEST[$default_carrier . '_shipping_zone']) ? '' : $_REQUEST[$default_carrier . '_shipping_zone'];
		if(!empty($default_carrier)) {
			$jp_carrier_name = db_get_row("SELECT ?:jp_carriers.carrier_code, ?:jp_carriers.carrier_name FROM ?:jp_carriers WHERE ?:jp_carriers.carrier_code = ?s AND ?:jp_carriers.lang_code = ?s", $default_carrier, CART_LANGUAGE);

			$jp_carrier_services_name = db_get_row("SELECT ?:jp_carrier_services.* FROM ?:jp_carrier_services WHERE ?:jp_carrier_services.carrier_code = ?s AND ?:jp_carrier_services.service_code = ?s AND ?:jp_carrier_services.lang_code = ?s", $default_carrier, $default_service, CART_LANGUAGE);

			$jp_carrier_zone_name = db_get_row("SELECT ?:jp_carrier_zones.* FROM ?:jp_carrier_zones WHERE ?:jp_carrier_zones.zone_id = ?i", $default_zone);

            // 表示するショップ（出品者）のIDを抽出条件に加える
            $_condition = fn_get_company_condition('?:jp_shipping_rates.company_id');

			//ロードボタンが押されたら
			$jp_carrier_rates = db_get_row("SELECT ?:jp_shipping_rates.* FROM ?:jp_shipping_rates WHERE ?:jp_shipping_rates.carrier_code = ?s AND ?:jp_shipping_rates.service_code = ?s AND ?:jp_shipping_rates.zone_id = ?i ?p", $default_carrier, $default_service, $default_zone, $_condition);

			$zone_rates = unserialize($jp_carrier_rates['shipping_rates']);

			// EMS 対応
			if($default_carrier != 'jpems') {
				$current_zones = db_get_array("SELECT ?:jp_carrier_zones.* FROM ?:jp_carrier_zones WHERE ?:jp_carrier_zones.lang_code = ?s AND ?:jp_carrier_zones.carrier_code = ?s", CART_LANGUAGE, $default_carrier);
			} else {
				// 重量変換
				foreach($zone_rates as $_key => $_val) {
					$zone_rates[$_key]['weight'] = ($_val['weight'] * 100);
				}
				$current_zones = db_get_array("SELECT ?:jp_carrier_zones.* FROM ?:jp_carrier_zones WHERE ?:jp_carrier_zones.lang_code = ?s AND ?:jp_carrier_zones.carrier_code = ?s AND ?:jp_carrier_zones.sort_order <> 99", CART_LANGUAGE, $default_carrier);
			}

			if(!empty($jp_carrier_rates)) {
				// 地域ごとの配送料金入れ替え
				foreach($zone_rates as &$zrdata) {
					$work_rate = array();
					foreach($current_zones as $czdata) {
						$work_rate[$czdata['zone_code']] = $zrdata['rates'][$czdata['zone_code']];
					}
					$zrdata['rates'] = $work_rate;
					reset($current_zones);
				}
				reset($current_zones);
				Registry::get('view')->assign('jp_carrier_name', $jp_carrier_name['carrier_name']);
				Registry::get('view')->assign('jp_carrier_services_name', $jp_carrier_services_name['service_name']);
				Registry::get('view')->assign('jp_carrier_zone_name', $jp_carrier_zone_name['zone_name']);
				Registry::get('view')->assign('jp_carrier_rates', $jp_carrier_rates);
				Registry::get('view')->assign('zone_rates', $zone_rates);
				Registry::get('view')->assign('current_zones', $current_zones);
				Registry::get('view')->assign('current_zones_count', count($current_zones) + 2);
				Registry::get('view')->assign('default_carrier', $default_carrier);
				Registry::get('view')->assign('default_service', $default_service);
				Registry::get('view')->assign('default_zone', $default_zone);
				if(!empty($_REQUEST['result_ids'])) {
                    $file_content = trim(Registry::get('view')->fetch('addons/localization_jp/views/shipping_rates_jp/ajax_manage.tpl', false));
                    $file_content_array = ["ajax_service_zone_rate"=>$file_content];
                    Registry::get('ajax')->assign("html", $file_content_array);
					exit();
				}
			}
		}
	}

	$jp_carriers = db_get_hash_array("SELECT carrier_code, carrier_name FROM ?:jp_carriers WHERE lang_code = ?s ORDER BY carrier_id", 'carrier_code', CART_LANGUAGE);

	// Set tabs for carriers
	if (!empty($jp_carriers)) {
		foreach ($jp_carriers as $k => $v) {
			if(empty($default_carrier)) $default_carrier = $v['carrier_code'];
			Registry::set('navigation.tabs.' . $k, array (
				'title' => $v['carrier_name'],
				'js' => true
			));
		}
	}
	Registry::get('view')->assign('jp_carriers', $jp_carriers);
	// [/Page sections]

	$jp_carrier_zones = db_get_hash_multi_array("SELECT ?:jp_carrier_zones.* FROM ?:jp_carrier_zones WHERE ?:jp_carrier_zones.lang_code = ?s  ORDER BY ?:jp_carrier_zones.carrier_code, ?:jp_carrier_zones.sort_order", array('carrier_code'), CART_LANGUAGE);

	$jp_carrier_services = db_get_hash_multi_array("SELECT ?:jp_carrier_services.* FROM ?:jp_carrier_services WHERE ?:jp_carrier_services.lang_code = ?s ORDER BY ?:jp_carrier_services.carrier_code, ?:jp_carrier_services.sort_order", array('carrier_code'), CART_LANGUAGE);

	Registry::get('view')->assign('default_carrier', $default_carrier);

	Registry::get('view')->assign('jp_carrier_services', $jp_carrier_services);

	Registry::get('view')->assign('jp_carrier_zones', $jp_carrier_zones);

	Registry::get('view')->assign('settings_title', __('jp_shipping_rates_setting'));

	Registry::get('view')->assign('carrier_id', $default_carrier);
}
