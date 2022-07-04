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

// $Id: krnkkb_user_info.php by takahshi from cs-cart.jp 2018

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// クロネコ掛け払い取引先登録
if ($mode == 'add') {

    // 送信用にパラメータを再設定
    $buyer = fn_krnkkb_prep_params($_REQUEST['buyer']);

    // 取引先登録処理
    $type = "60";
    $order_info['user_id'] = Tygh::$app['session']['auth']['user_id'];
    $params = array();
    $params = fn_krnkkb_get_params($type, null, $order_info);
    $params = array_merge($params, fn_krnkkb_format_field_value($buyer));

    $result = fn_krnkkb_send_request($type, $params);

    // 正常終了の場合
    if( !empty($result) && $result['returnCode'] == 0 ){

        // 取引先IDを登録
        fn_krnkkb_register_buyer_info($params['cId']);

        // メッセージを表示
        fn_set_notification('N', __('notice'), __('jp_kuroneko_kakebarai_buyer_registered'));
    }
    // エラーがある場合
    else {
        $_SESSION['krnkkb_buyer'] = $_REQUEST['buyer'];

        // エラーメッセージを表示
        fn_kuroneko_kakebarai_set_err_msg($result);
    }

    return array(CONTROLLER_STATUS_OK, "krnkkb_user_info.view");
}
// クロネコ掛け払い取引先登録・照会
elseif ($mode == 'view') {
	// パン屑リストを生成
	fn_add_breadcrumb(__('jp_kuroneko_kakebarai_user_info'));

	// 掛け払い取引先登録済みか確認
    $type = "70";
    $order_info['user_id'] = Tygh::$app['session']['auth']['user_id'];
    $params = array();
    $params = fn_krnkkb_get_params($type, null, $order_info);

    $result = fn_krnkkb_send_request($type, $params);

    // 正常終了の場合
    // 審査状況が「申込書不備」、「ご利用不可」、「取消」でない場合
    if( $result['returnCode'] == 0
        && ($result['judgeStatus'] != __("jp_kuroneko_kakebarai_judge_status_nc")
            && $result['judgeStatus'] != __("jp_kuroneko_kakebarai_judge_status_no")
            && $result['judgeStatus'] != __("jp_kuroneko_kakebarai_judge_status_cn"))
    ){
        // 取引先ID
        $buyer['buyerId'] = $result['buyerId'];

        // 取引先名
        $buyer['buyerName'] = $result['buyerName'];

        // 審査状況
        $buyer['judgeStatus'] = $result['judgeStatus'];
    }
    // エラーがある場合
    else {
        // 審査状況照会が60日を過ぎてデータが削除された場合
        if( $result['errorCode'] == 'G080001' ){

            // 利用金額照会リクエストを実行
            $type = "50";
            $order_info['user_id'] = Tygh::$app['session']['auth']['user_id'];
            $params = array();
            $params = fn_krnkkb_get_params($type, null, $order_info);

            $result = fn_krnkkb_send_request($type, $params);

            // 取引先ID
            $buyer['buyerId'] = $result['buyerId'];

            // 取引先名
            $buyer['buyerName'] = $result['buyerName'];

            // 審査状況
            $buyer['judgeStatus'] = $result['useUsable'];
        }

        // ユーザー情報を取得
        $user_info = fn_get_user_info(Tygh::$app['session']['auth']['user_id']);

        // フォームの内容がある場合
        if( !empty($_SESSION['krnkkb_buyer']) ){
            // フォームに入力した内容を取得
            $buyer = $_SESSION['krnkkb_buyer'];

            // セッションを開放
            unset($_SESSION['krnkkb_buyer']);
        }
        // デフォルト値をセット
        else {
            // 代表者名(漢字・姓)
            $buyer['daikjmeiSei'] = $user_info['firstname'];

            // 代表者名(漢字・名)
            $buyer['daikjmeiMei'] = $user_info['lastname'];

            // 代表者名(カナ・姓)
            $buyer['daiknameiSei'] = fn_krnkkb_get_user_kana_name($user_info, 'L');

            // 代表者名(カナ・名)
            $buyer['daiknameiMei'] = fn_krnkkb_get_user_kana_name($user_info, 'F');

            // 郵便番号
            $buyer['ybnNo'] = str_replace('-', '', !empty($user_info['b_zipcode']) ? $user_info['b_zipcode'] : $user_info['s_zipcode']);

            // 住所（漢字）
            $buyer['Adress_state'] = !empty($user_info['b_zipcode']) ? $user_info['b_state'] : $user_info['s_state'];
            $buyer['Adress_city'] = !empty($user_info['b_zipcode']) ? $user_info['b_city'] : $user_info['s_city'];
            $buyer['Adress_address'] = !empty($user_info['b_zipcode']) ? $user_info['b_address'] : $user_info['s_address'];
            $buyer['Adress_address_2'] = !empty($user_info['b_zipcode']) ? $user_info['b_address_2'] : $user_info['s_address_2'];

            // 電話番号(代表)
            $buyer['telNo'] = !empty($user_info['b_phone']) ? $user_info['b_phone'] : $user_info['s_phone'];

            // 自宅郵便番号
            $buyer['daiYbnno'] = str_replace('-', '', !empty($user_info['b_zipcode']) ? $user_info['b_zipcode'] : $user_info['s_zipcode']);

            // 自宅住所（漢字）
            $buyer['daiAddress_state'] = !empty($user_info['b_zipcode']) ? $user_info['b_state'] : $user_info['s_state'];
            $buyer['daiAddress_city'] = !empty($user_info['b_zipcode']) ? $user_info['b_city'] : $user_info['s_city'];
            $buyer['daiAddress_address'] = !empty($user_info['b_zipcode']) ? $user_info['b_address'] : $user_info['s_address'];
            $buyer['daiAddress_address_2'] = !empty($user_info['b_zipcode']) ? $user_info['b_address_2'] : $user_info['s_address_2'];
        }

        $buyer['cId'] = fn_krnkkb_get_buyer_id($user_info['user_id'], true);

        // 取引先IDが存在しないエラーでない場合
        if( $result['errorCode'] != 'G070001' ) {
            // エラーメッセージを表示
            fn_kuroneko_kakebarai_set_err_msg($result);
        }

        // 審査状況が存在する場合
        if( !empty($result['judgeStatus']) ){
            fn_set_notification('E', __('jp_kuroneko_kakebarai_error'), __("jp_kuroneko_kakebarai_judge_status_ng", array(
                '[status]' => $result['judgeStatus']
            )));
        }

        Tygh::$app['view']->assign('states', fn_get_all_states());
    }


	Registry::get('view')->assign('buyer', $buyer);
}
