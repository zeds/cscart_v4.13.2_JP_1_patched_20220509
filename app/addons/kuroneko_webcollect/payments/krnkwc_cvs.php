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

// $Id: krnkwc_cvs.php by tommy from cs-cart.jp 2016
// // クロネコwebコレクト（コンビニ（オンライン）払い）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でクロネコwebコレクトに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    // クロネコwebコレクトに送信するコンビニ（オンライン）払い用パラメータをセット
    $params = array();
    $params = fn_krnkwc_get_params('cvs', $order_id, $order_info, $processor_data);

    // コンビニ種別に応じてデータの送信先とコンビ二名をセット
    list($cvs_type, $cvs_name) = fn_krnkwc_get_cvs_info($params['function_div']);

    // クロネコwebコレクトに送信するコンビニ（オンライン）払い
    $cvs_result = fn_krnkwc_send_request($cvs_type, $params, $processor_data['processor_params']['mode']);

    // コンビニ（オンライン）払いに関するリクエスト送信が正常終了した場合
    if (!empty($cvs_result)) {

        // 結果コード
        $return_code = $cvs_result['returnCode'];

        // コンビニ（オンライン）払いが正常に完了している場合
        if ( $return_code == 0 ) {

            // 注文情報を取得
            $order_info = fn_get_order_info($order_id);

            // 注文IDと利用した支払方法がマッチした場合
            if (fn_check_payment_script('krnkwc_cvs.php', $order_id)) {
                // 注文確定処理
                $pp_response = array();
                $pp_response['order_status'] = 'O';
                fn_finish_payment($order_id, $pp_response);

                // 請求ステータスを更新
                fn_krnkwc_update_cc_status_code($order_id, 'CVS_1', $params['order_no']);

                // DBに保管する支払い情報を生成
                $cvs_result['cvs_name'] = $cvs_name;
                fn_krnkwc_format_payment_info($cvs_type, $order_id, $order_info['payment_info'], $cvs_result);

                // コンビニ決済に関するお知らせを購入者にメール送信
                fn_krnkwc_send_cvs_payment_info($cvs_type, $order_id, $order_info, $cvs_result);

                // コンビニ決済に関するメッセージを表示
                fn_set_notification('I', __('jp_kuroneko_webcollect_cvs_notification_title'),  __('jp_kuroneko_webcollect_cvs_notification_message', array('[email]' => $params['buyer_email'])));

                // 注文処理ページへリダイレクトして注文確定
                fn_order_placement_routines('route', $order_id);
            }
        // エラー処理
        } else {
            // 注文手続きページへリダイレクトしてエラーメッセージを表示
            fn_krnkwc_set_err_msg($cvs_result, __('jp_kuroneko_webcollect_cvs_error'));
            $return_url = fn_lcjp_get_error_return_url();
            fn_redirect($return_url, true);
        }

    // リクエスト送信が異常終了した場合
    }else{
        // 注文処理ページへリダイレクト
        fn_set_notification('E', __('jp_kuroneko_webcollect_cvs_error'), __('jp_kuroneko_webcollect_cvs_failed'));
        $return_url = fn_lcjp_get_error_return_url();
        fn_redirect($return_url, true);
    }
}
