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

// $Id: sonys_subsc_manager.php by takahashi from cs-cart.jp 2019

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$params = $_REQUEST;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch($mode) {
        // 一括決済処理
        case 'bulk_process':
            $is_valid_subsc_exist = false;
            asort($params['subpay_ids']);

            foreach ($params['subpay_ids'] as $k => $v) {
                if(fn_sonys_process($v)){
                    $is_valid_subsc_exist = true;
                }
            }
            // 処理を実行できる定期購入が存在しない場合
            if (!$is_valid_subsc_exist) {
                // 処理を実行できる定期購入が存在しないメッセージを表示
                fn_set_notification('E', __('error'), __('jp_sonys_subsc_data_not_exists'));
            }

            return array(CONTROLLER_STATUS_OK, "sonys_subsc_manager.manage");
            break;

        case 'bulk_sales_confirm':    // 一括売上計上
        case 'bulk_auth_cancel':      // 一括与信取消
        case 'bulk_sales_cancel':     // 一括売上取消
            // 送信データに注文IDが含まれる場合
            if (!empty($params['order_ids'])) {

            // 一括処理用のコードを個別処理用のコードに変換
            $type = str_replace('bulk_', 'cc_', $mode);

            // 処理コードに応じて処理を実行
            switch ($type) {
                case 'cc_sales_confirm':    // 一括売上計上
                case 'cc_auth_cancel':      // 一括与信取消
                case 'cc_sales_cancel':     // 一括売上取消
                    $is_valid_orders_exist = false;
                    foreach ($params['order_ids'] as $k => $v) {
                        if (fn_sonys_send_cc_request($v, $type)) {
                            // 処理実行フラグをtrueにセット
                            $is_valid_orders_exist = true;
                        }
                    }
                    // 処理を実行できる注文が存在しない場合
                    if (!$is_valid_orders_exist) {
                        // 処理を実行できる注文が存在しないメッセージを表示
                        fn_set_notification('E', __('jp_sonys_' . $type . '_error'), __('jp_sonys_data_not_exists'));
                    }
                    return array(CONTROLLER_STATUS_REDIRECT);
                    break;
                default:
                    // do nothing
            }
        }
        return array(CONTROLLER_STATUS_OK, "sonys_subsc_manager.details");
        break;

        case 'subsc_update':
            $subsc_info = $_REQUEST['subsc_info'];
            fn_sonys_update_subsc_manager($subsc_info);

            return array(CONTROLLER_STATUS_OK, "sonys_subsc_manager.details?subpay_id=" . $subsc_info['subpay_id']);
            break;
    }
}

switch($mode){
    // 決済処理
	case 'cc_process':
		fn_sonys_process($params['subpay_id']);
		return array(CONTROLLER_STATUS_REDIRECT);
		break;

	// 一覧ページ
	case 'manage':

	    // 定期購入ステータス
        $subsc_status = array(
            'A'	=>	__('jp_sonys_subsc_status_a'),
            'P'	=>	__('jp_sonys_subsc_status_p'),
            'D'	=>	__('jp_sonys_subsc_status_d'),
            'E'	=>	__('jp_sonys_subsc_status_e'),
        );

        $subscriptions = array();

		// 定期購入の数を取得
		$sonys_subsc_total = db_get_field("SELECT COUNT(*) FROM ?:jp_sonys_subsc_manager");

		// 定期購入で決済した注文が存在する場合
		if( !empty($sonys_subsc_total) ){
			// 定期購入の情報を抽出
			list($subscriptions, $search) = fn_get_sonys_subsc_data($params);
		}

		Registry::get('view')->assign('subscriptions', $subscriptions);
		Registry::get('view')->assign('search', $search);
        Registry::get('view')->assign('subsc_status', $subsc_status);

		break;

    // ステータス更新
    case 'status_update':
        fn_sonys_subsc_status_update($params['subpay_id'], $params['status_to']);
        fn_set_notification('N', __('notice'), __('jp_sonys_subsc_status_update', array("[subpay_id]"=>$params['subpay_id'], "[status]"=>__("jp_sonys_subsc_status_" . strtolower($params['status_to'])))));
        return array(CONTROLLER_STATUS_REDIRECT);

        break;

        // 履歴
    case 'details':

        // 定期購入ステータス
        $subsc_status = array(
            'A'	=>	__('jp_sonys_subsc_status_a'),
            'P'	=>	__('jp_sonys_subsc_status_p'),
            'D'	=>	__('jp_sonys_subsc_status_d'),
            'E'	=>	__('jp_sonys_subsc_status_e'),
        );

        // ソニーペイメント 定期購入で決済した注文の数を取得
        if( !empty($params['subpay_id']) ) {
            $sonys_total = db_get_field("SELECT COUNT(*) FROM ?:jp_sonys_subsc_history WHERE subpay_id = ?i", $params['subpay_id']);
        }
        else{
            $sonys_total = db_get_field("SELECT COUNT(*) FROM ?:jp_sonys_subsc_history");
        }

        // ソニーペイメント 定期購入で決済した注文が存在する場合
        if( !empty($sonys_total) ){
            // クレジットカード決済を用いた注文を抽出
            list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);
            // スマートリンクネットワーク（クレジットカード決済）で決済した注文が存在しない場合
        }else{
            $orders = array();
        }

        $subsc_params['subpay_id'] = $params['subpay_id'];

        list($subscriptions, $subsc_search) = fn_get_sonys_subsc_data($subsc_params);

        Registry::get('view')->assign('orders', $orders);
        Registry::get('view')->assign('search', $search);
        Registry::get('view')->assign('subscriptions', $subscriptions[0]);
        Registry::get('view')->assign('subsc_status', $subsc_status);
        break;

    case 'cc_sales_confirm':    // 売上計上
    case 'cc_auth_cancel':      // 与信取消
    case 'cc_sales_cancel':     // 売上取消
        fn_sonys_send_cc_request($params['order_id'], $mode);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;

	// その他
	default:
		// do nothing
}
