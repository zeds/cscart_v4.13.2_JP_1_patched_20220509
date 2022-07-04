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

// $Id: checkout.pre.php by tommy from cs-cart.jp 2017
// 「お届け日」「お届け時間帯」の指定に対応
// 注文手続き中に「メイン」プロフィールの登録が完了していない場合は、新しいプロフィールを追加するためのリンクを表示させない

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// カートに関するセッション変数が存在する場合
if( Tygh::$app['session']['cart'] ){
    // カートに関するデータがPOSTされた場合
    if( $_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET'){
		// 配送方法が指定された場合
		if( isset($_REQUEST['shipping_ids']) ){
            // 指定された配送方法のIDをセット
			foreach($_REQUEST['shipping_ids'] as $_group_key => $_shipping_id){
                // 指定された配送方法においてお届け時間帯が指定されている場合
				if( isset($_REQUEST['delivery_time_selected_' . $_group_key . '_' . $_shipping_id]) ){
					Tygh::$app['session']['delivery_time_selected'][$_group_key][$_shipping_id] = $_REQUEST['delivery_time_selected_' . $_group_key . '_' . $_shipping_id];
				}else{
					// 指定された配送方法においてお届け時間帯が指定されていない場合 お届け時間帯用のセッション変数を解放する
					unset(Tygh::$app['session']['delivery_time_selected'][$_group_key][$_shipping_id]);
				}
				// 指定された配送方法においてお届け希望日が指定されている場合
				if( isset($_REQUEST['delivery_date_selected_' . $_group_key . '_' . $_shipping_id]) ){
					Tygh::$app['session']['delivery_date_selected'][$_group_key][$_shipping_id] = $_REQUEST['delivery_date_selected_' . $_group_key . '_' . $_shipping_id];
				}else{
					// 指定された配送方法においてお届け希望日が指定されていない場合 配送日のセッション変数を解放する
					unset(Tygh::$app['session']['delivery_date_selected'][$_group_key][$_shipping_id]);
				}
			}
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    // 注文完了時に届け時間帯・お届け日に関するセッション変数をクリア
    if($mode == 'complete'){
        if( isset(Tygh::$app['session']['delivery_time_selected']) ) unset(Tygh::$app['session']['delivery_time_selected']);
        if( isset(Tygh::$app['session']['delivery_date_selected']) ) unset(Tygh::$app['session']['delivery_date_selected']);
    }

    // 注文手続き中に「メイン」プロフィールの登録が完了していない場合は、新しいプロフィールを追加するためのリンクを表示させない
    if($mode == 'checkout'){
        if (!empty($auth['user_id'])) {
            $tmp_profiles = db_get_array("SELECT * FROM ?:user_profiles WHERE user_id = ?i", $auth['user_id']);
            $tmp_cnt_profiles = count($tmp_profiles);

            if( $tmp_cnt_profiles > 1 ){
                Tygh::$app['view']->assign('jp_is_profile_set', 'Y');
            }else{
                $tmp_profile = array_shift($tmp_profiles);
                if( empty($tmp_profile['b_firstname']) && empty($tmp_profile['b_lastname']) && empty($tmp_profile['s_firstname']) && empty($tmp_profile['s_lastname']) ){
                    Tygh::$app['view']->assign('jp_is_profile_set', 'N');
                }else{
                    Tygh::$app['view']->assign('jp_is_profile_set', 'Y');
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || ($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['switch_payment_method'] == 'Y')) {
    if ($mode == 'checkout') {
        // 新チェックアウト方式に対応
        if (!empty($_REQUEST['litecheckout_state'])) {
            Tygh::$app['session']['cart']['user_data']['email'] = $_REQUEST['user_data']['email'];

            $jp_checkout_fullname_mode = fn_lcjp_get_jp_settings('jp_checkout_fullname_mode');

            if ($jp_checkout_fullname_mode != 'jp_checkout_fullname_no') {
                $s_fullname = explode(' ', $_REQUEST['user_data']['s_fullname']);
                if (!empty($s_fullname[1])) {
                    $s_firstname = $s_fullname[0];
                    $s_lastname = $s_fullname[1];
                } else {
                    $s_fullname = explode('　', $_REQUEST['user_data']['s_fullname']);
                    $s_firstname = $s_fullname[0];
                    $s_lastname = $s_fullname[1];
                }
                Tygh::$app['session']['cart']['user_data']['s_firstname'] = $s_firstname;
                Tygh::$app['session']['cart']['user_data']['s_lastname'] = $s_lastname;
            } else {
                Tygh::$app['session']['cart']['user_data']['s_firstname'] = $_REQUEST['user_data']['s_firstname'];
                Tygh::$app['session']['cart']['user_data']['s_lastname'] = $_REQUEST['user_data']['s_lastname'];
            }
            Tygh::$app['session']['cart']['user_data']['s_phone'] = $_REQUEST['user_data']['s_phone'];
            Tygh::$app['session']['cart']['user_data']['s_zipcode'] = $_REQUEST['user_data']['s_zipcode'];
            Tygh::$app['session']['cart']['user_data']['s_address'] = $_REQUEST['user_data']['s_address'];
            Tygh::$app['session']['cart']['user_data']['s_address_2'] = $_REQUEST['user_data']['s_address_2'];
            Tygh::$app['session']['cart']['user_data']['ship_to_another'] = $_REQUEST['user_data']['ship_to_another'];
            Tygh::$app['session']['cart']['user_data']['s_state'] = $_REQUEST['litecheckout_state'];
            Tygh::$app['session']['cart']['user_data']['s_city'] = $_REQUEST['litecheckout_city'];

            if (!empty($_REQUEST['ship_to_another'])) {
                if ($jp_checkout_fullname_mode != 'jp_checkout_fullname_no') {
                    $b_fullname = explode(' ', $_REQUEST['user_data']['b_fullname']);
                    if (!empty($b_fullname[1])) {
                        $b_firstname = $b_fullname[0];
                        $b_lastname = $b_fullname[1];
                    } else {
                        $b_fullname = explode('　', $_REQUEST['user_data']['b_fullname']);
                        $b_firstname = $b_fullname[0];
                        $b_lastname = $b_fullname[1];
                    }
                    Tygh::$app['session']['cart']['user_data']['b_firstname'] = $b_firstname;
                    Tygh::$app['session']['cart']['user_data']['b_lastname'] = $b_lastname;
                } else {
                    Tygh::$app['session']['cart']['user_data']['b_firstname'] = $_REQUEST['user_data']['b_firstname'];
                    Tygh::$app['session']['cart']['user_data']['b_lastname'] = $_REQUEST['user_data']['b_lastname'];;
                }
                Tygh::$app['session']['cart']['user_data']['b_phone'] = $_REQUEST['user_data']['b_phone'];
                Tygh::$app['session']['cart']['user_data']['b_zipcode'] = $_REQUEST['user_data']['b_zipcode'];
                Tygh::$app['session']['cart']['user_data']['b_address'] = $_REQUEST['user_data']['b_address'];
                Tygh::$app['session']['cart']['user_data']['b_address_2'] = $_REQUEST['user_data']['b_address_2'];
                Tygh::$app['session']['cart']['user_data']['b_state'] = $_REQUEST['user_data']['b_state'];
                Tygh::$app['session']['cart']['user_data']['b_city'] = $_REQUEST['user_data']['b_city'];
            }
        }
    }
}
