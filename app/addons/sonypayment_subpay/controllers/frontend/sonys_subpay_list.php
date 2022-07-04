<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2004 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: sonys_card_info.php by takahashi from cs-cart.jp 2019

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$params = $_REQUEST;

if ($mode == 'view') {

    $params['user_id'] = $auth['user_id'];

	// パン屑リストを生成
	fn_add_breadcrumb(__('jp_sonys_subpay_list'));

    // 定期購入ステータス
    $subsc_status = array(
        'A'	=>	__('jp_sonys_subsc_status_a'),
        'P'	=>	__('jp_sonys_subsc_status_p'),
        'D'	=>	__('jp_sonys_subsc_status_d'),
    );

    $subscriptions = array();

    // 定期購入の数を取得
    $sonys_subsc_total = db_get_field("SELECT COUNT(*) FROM ?:jp_sonys_subsc_manager WHERE user_id = ?i", $params['user_id']);

    // 定期購入で決済した注文が存在する場合
    if( !empty($sonys_subsc_total) ){
        // 定期購入の情報を抽出
        list($subscriptions, $search) = fn_get_sonys_subsc_data($params);
    }

    Registry::get('view')->assign('subscriptions', $subscriptions);
    Registry::get('view')->assign('search', $search);
    Registry::get('view')->assign('subsc_status', $subsc_status);

} elseif ($mode == 'status_update' ) {
    // ステータスを変更
    fn_sonys_subsc_status_update($params['subpay_id'], $params['status_to']);
    fn_set_notification('N', __('notice'), __('jp_sonys_subsc_status_update', array("[subpay_id]"=>$params['subpay_id'], "[status]"=>__("jp_sonys_subsc_status_" . strtolower($params['status_to'])))));

    return array(CONTROLLER_STATUS_REDIRECT, "sonys_subpay_list.view");

} elseif ($mode == 'change_ship_address'){

    // パン屑リストを生成
    fn_add_breadcrumb(__('jp_sonys_subsc_change_ship_addr'));

    $subpay_ship_addrs = db_get_array("SELECT * FROM ?:jp_sonys_subsc_ship_address WHERE subpay_id = ?i", $params['subpay_id']);

    Registry::get('view')->assign('states', fn_get_all_states());
    Registry::get('view')->assign('subpay_id', $params['subpay_id']);
    Registry::get('view')->assign('subpay_ship_addr', $subpay_ship_addrs[0]);

}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 BOF
// 配送先住所更新対応
///////////////////////////////////////////////
elseif ($mode == 'update_ship_address'){

    // 上記住所を全ての定期購入に適用がチェックされている場合（同じユーザーの配送先住所を更新）
    if ($params['subpay_ship_addr']['same_addr_for_all'] == 'Y') {

        $user_subpay_ids = db_get_fields("SELECT subpay_id FROM ?:jp_sonys_subsc_manager WHERE user_id = (SELECT user_id FROM ?:jp_sonys_subsc_manager WHERE subpay_id = ?i)", $params['subpay_ship_addr']['subpay_id']);

        foreach($user_subpay_ids as $user_subpay_id) {

            $ship_data = array(
                'subpay_id' => $user_subpay_id,
                's_zipcode' => $params['subpay_ship_addr']['s_zipcode'],
                's_state' => $params['subpay_ship_addr']['s_state'],
                's_city' => $params['subpay_ship_addr']['s_city'],
                's_address' => $params['subpay_ship_addr']['s_address'],
                's_address_2' => $params['subpay_ship_addr']['s_address_2'],
                's_phone' => $params['subpay_ship_addr']['s_phone'],
            );

            db_query("REPLACE INTO ?:jp_sonys_subsc_ship_address ?e", $ship_data);
        }

    }
    // 上記住所を全ての定期購入に適用がチェックされていない場合（指定した定期購入の配送先住所を更新）
    else {
        $ship_data = array(
            'subpay_id' => $params['subpay_ship_addr']['subpay_id'],
            's_zipcode' => $params['subpay_ship_addr']['s_zipcode'],
            's_state' => $params['subpay_ship_addr']['s_state'],
            's_city' => $params['subpay_ship_addr']['s_city'],
            's_address' => $params['subpay_ship_addr']['s_address'],
            's_address_2' => $params['subpay_ship_addr']['s_address_2'],
            's_phone' => $params['subpay_ship_addr']['s_phone'],
        );

        db_query("REPLACE INTO ?:jp_sonys_subsc_ship_address ?e", $ship_data);
    }

    fn_set_notification('N', __('notice'), __('jp_sonys_subsc_ship_addr_update_notice'));

    return array(CONTROLLER_STATUS_REDIRECT, "sonys_subpay_list.change_ship_address&subpay_id=" . $params['subpay_ship_addr']['subpay_id']);
}
///////////////////////////////////////////////
// Modified by takahashi from cs-cart.jp 2020 EOF
///////////////////////////////////////////////