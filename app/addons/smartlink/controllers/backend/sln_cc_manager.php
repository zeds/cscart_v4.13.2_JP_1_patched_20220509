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

// $Id: sln_cc_manager.php by tommy from cs-cart.jp 2014

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// 送信データに注文IDが含まれる場合
	if( !empty($_REQUEST['order_ids']) ){

		// 一括処理用のコードを個別処理用のコードに変換
		$type = str_replace('bulk_', 'cc_', $mode);

		// 処理コードに応じて処理を実行
		switch($type){
			case 'cc_sales_confirm':    // 一括売上計上
			case 'cc_auth_cancel':      // 一括与信取消
			case 'cc_sales_cancel':     // 一括売上取消
				$is_valid_orders_exist = false;
				foreach ($_REQUEST['order_ids'] as $k => $v) {
					if( fn_sln_send_cc_request($v, $type) ){
						// 処理実行フラグをtrueにセット
						$is_valid_orders_exist = true;
					}
				}
				// 処理を実行できる注文が存在しない場合
				if( !$is_valid_orders_exist ){
					// 処理を実行できる注文が存在しないメッセージを表示
					fn_set_notification('E', __('jp_sln_' . $type . '_error'), __('jp_sln_cc_data_not_exists'));
				}
				return array(CONTROLLER_STATUS_REDIRECT);
				break;
			default:
				// do nothing
		}
	}
	return array(CONTROLLER_STATUS_OK, "sln_cc_manager.manage");
}

$params = $_REQUEST;

switch($mode){
	case 'cc_sales_confirm':    // 売上計上
    case 'cc_auth_cancel':      // 与信取消
    case 'cc_sales_cancel':     // 売上取消
		fn_sln_send_cc_request($_REQUEST['order_id'], $mode);
		return array(CONTROLLER_STATUS_REDIRECT);
		break;

	// 一覧ページ
	case 'manage':

		$params['check_for_suppliers'] = true;
		$params['company_name'] = true;

		// スマートリンクネットワーク（クレジットカード決済）で決済した注文の数を取得
		$sln_total = db_get_field("SELECT COUNT(*) FROM ?:jp_sln_cc_status");

		// スマートリンクネットワーク（クレジットカード決済）で決済した注文が存在する場合
		if( !empty($sln_total) ){
			// クレジットカード決済を用いた注文を抽出
			list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);
		// スマートリンクネットワーク（クレジットカード決済）で決済した注文が存在しない場合
		}else{
			$orders = array();
		}

		Registry::get('view')->assign('orders', $orders);
		Registry::get('view')->assign('search', $search);
		break;
	// その他
	default:
		// do nothing
}
