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

// $Id: kuroneko_kakebarai.php by takahashi from cs-cart.jp 2018
// クロネコ代金後払いサービス

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントでクロネコ掛け払いに接続して決済手続きを実行する場合
if( $mode == 'place_order' && AREA == 'C' ){

    // 注文情報を取得
    $order_info = fn_get_order_info($order_id);

    // クロネコ代金後払いサービスに送信するパラメータをセット
    $type = "10";
    $params = array();
    $params = fn_krnkkb_get_params($type, $order_id, $order_info);

    // クロネコ掛け払いにリクエストを送信
    $kb_result = fn_krnkkb_send_request($type, $params);

    // クロネコ掛け払いに関するリクエスト送信が正常終了した場合
    if (!empty($kb_result)) {

        // 結果コード
        $return_code = $kb_result['returnCode'];

        // クロネコ掛け払いの決済依頼が正常に完了している場合
        if ( $return_code == 0  ) {

            // 注文IDと利用した支払方法がマッチした場合
            if (fn_check_payment_script('kuroneko_kakebarai.php', $order_id)) {
                // 注文確定処理
                $pp_response = array();
                $pp_response['order_status'] = 'P';
                fn_finish_payment($order_id, $pp_response);

                // 請求ステータスを更新
                fn_krnkkb_update_status_code($order_id, 'KB_1');

                // DBに保管する支払い情報を生成
                fn_krnkkb_format_payment_info($type, $order_id, $order_info['payment_info'], $kb_result);

                // 注文処理ページへリダイレクトして注文確定
                fn_order_placement_routines('route', $order_id);
            }
        // エラー処理
        } else {
            // DBに保管する支払い情報を生成
            fn_krnkkb_format_payment_info($type, $order_id, $order_info['payment_info'], $kb_result);

            // エラーメッセージを表示
            fn_kuroneko_kakebarai_set_err_msg($kb_result);

            // 注文手続きページへリダイレクトしてエラーメッセージを表示
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    // リクエスト送信が異常終了した場合
    }else{
        // 注文処理ページへリダイレクト
        fn_set_notification('E', __('jp_kuroneko_kakebarai_error'), __('jp_kuroneko_kakebarai_failed'));
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }
}
