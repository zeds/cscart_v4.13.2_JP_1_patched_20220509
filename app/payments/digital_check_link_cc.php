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

// $Id: digital_check_link_cc.php by tommy from cs-cart.jp 2016
// ペイデザイン決済（クレジットカード決済・リンク方式）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

    // ペイデザイン側で決済処理を実行した場合
    if ($mode == 'process') {

        // 正常終了した場合
        if( fn_check_payment_script('digital_check_link_cc.php', $_REQUEST['order_id']) ){

            // ユーザーID決済で利用するデータを保存
            if( !empty($_REQUEST['FUKA']) ){
                $digital_check_user_id = db_get_field("SELECT user_id FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);

                if( !empty($digital_check_user_id) && ($digital_check_user_id == $_REQUEST['FUKA']) ){
                    // ユーザーID決済に関する情報をセット
                    fn_dgtlchck_register_cc_info($digital_check_user_id);
                }
            }

            // 注文ステータスを取得
            $current_order_status = db_get_field("SELECT status FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);

            // 通知ステータスを変更
            $force_notification = array();

            // 結果通知機能によりすでに注文スタータスが「P（支払い確認済み）」の場合は通知メールは送信しない
            if($current_order_status == 'P'){
                $force_notification['C'] = false;
                $force_notification['A'] = false;
            }

            $pp_response = array();
            $pp_response['order_status'] = 'P';
            fn_finish_payment($_REQUEST['order_id'], $pp_response, $force_notification);
            fn_order_placement_routines('route', $_REQUEST['order_id'], $force_notification);

        // エラーが発生した場合
        }else{
            $pp_response["order_status"] = 'F';
            if (fn_check_payment_script('digital_check_link_cc.php', $_REQUEST['order_id'])) {
                fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
                fn_order_placement_routines('route', $_REQUEST['order_id']);
            }
        }

    // 決済処理をキャンセルした場合
    } elseif ($mode == 'cancelled') {
        $pp_response["order_status"] = 'F';
        if (fn_check_payment_script('digital_check_link_cc.php', $_REQUEST['order_id'])) {
            fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
            fn_order_placement_routines('route', $_REQUEST['order_id']);
        }
    }

} else {

    // ペイデザインに送信するパラメーターをセット
    $params = fn_dgtlchck_get_params('cc_link', $order_id, $order_info, $processor_data);

    // 接続するURLをセット
    $connection_url = PAYDESIGN_URL_CEDY;

    // この処理を入れないとペイデザインで決済後表示されるリンクでCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
    // この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
    unset(Tygh::$app['session']['cart']['processed_order_id']);

    echo <<<EOT
<html>
<body onLoad="document.charset='Shift_JIS'; document.process.submit();">
<form action="{$connection_url}" method="POST" name="process" Accept-charset="Shift_JIS">
EOT;

    foreach($params as $key => $val){
        echo '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
    }

    $msg = __('text_cc_processor_connection');
    $msg = str_replace('[processor]', __('jp_digital_check_company_name'), $msg);
    echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
}

exit;
