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

// $Id: kessai_navi.php by tommy from cs-cart.jp 2016
// 決済ナビ

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

    // 決済ナビ側で決済処理を実行した場合
    if ($mode == 'process') {

        // 正常終了した場合
        if($_REQUEST['result'] == 'ok'){
            if (fn_check_payment_script('kessai_navi.php', $_REQUEST['order_id'])) {
                $pp_response = array();
                $pp_response['order_status'] = fn_knv_get_order_status($_REQUEST['bkcode']);
                fn_finish_payment($_REQUEST['order_id'], $pp_response);
                fn_order_placement_routines('route', $_REQUEST['order_id']);
            }

        // エラーまたは処理キャンセルの場合
        }else{
            $pp_response["order_status"] = 'F';
            if (fn_check_payment_script('kessai_navi.php', $_REQUEST['order_id'])) {
                fn_set_notification('E', __('error'), __('jp_knv_msg_error'));

                // 管理者やお客様にはメールは送信しない
                $force_notification = array();
                $force_notification['C'] = false;
                $force_notification['A'] = false;

                fn_finish_payment($_REQUEST['order_id'], $pp_response, $force_notification);
                fn_order_placement_routines('route', $_REQUEST['order_id'], $force_notification);
            }
        }
    }
} else {

    // 決済ナビに送信するデータ用配列を初期化
    $knv_data = array();

    /////////////////////////////////////////////////////////////////////////////////
    // 接続先URL BOF
    /////////////////////////////////////////////////////////////////////////////////
    // 本番環境
    if( $processor_data['processor_params']['mode'] == 'live' ){
        $target_url = $processor_data['processor_params']['url_production'];
    // テスト環境
    }else{
        $target_url = $processor_data['processor_params']['url_test'];
    }

    // 接続先URLがセットされていない場合には、強制的にテスト環境に接続
    if($target_url == ''){
        $target_url = 'https://tst.kessai-navi.jp/mltbank/MBWebFrontPayment';
    }
    /////////////////////////////////////////////////////////////////////////////////
    // 接続先URL EOF
    /////////////////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////////////////
    // 決済ナビに送信するデータ BOF
    /////////////////////////////////////////////////////////////////////////////////
    // プロトコルバージョン
    $knv_data['p_ver'] = '0200';

    // データ作成日
    $knv_data['stdate'] = date('Ymd');

    // 加盟店取引番号（注文番号を6桁固定で送信）
    $knv_data['stran'] = fn_knv_generate_stran($order_id);

    // 加盟店コード
    $knv_data['shopid'] = Registry::get('addons.kessai_navi.shopid');

    // 加盟店サブコード
    $knv_data['cshopid'] = Registry::get('addons.kessai_navi.cshopid');

    // 取引金額（四捨五入して整数型にする）
    $knv_data['amount'] = round($order_info['total']);

    // 顧客カナ姓名
    $name_kana_info = fn_lcjp_get_name_kana($order_info);
    $lastname_kana = mb_strcut(mb_convert_kana($name_kana_info['firstname_kana'], "KVHA", 'UTF-8'), 0, 20);
    $firstname_kana = mb_strcut(mb_convert_kana($name_kana_info['lastname_kana'], "KVHA", 'UTF-8'), 0, 20);
    $lastname_kana = mb_strcut(mb_convert_kana($lastname_kana, "C", 'UTF-8'), 0, 20);
    $firstname_kana = mb_strcut(mb_convert_kana($firstname_kana, "C", 'UTF-8'), 0, 20);
    $knv_data['custm'] = $lastname_kana . $firstname_kana;

    // 顧客漢字姓名
    $lastname = mb_strcut(mb_convert_kana($order_info['b_firstname'], "KVHA", 'UTF-8'), 0, 20);
    $firstname = mb_strcut(mb_convert_kana($order_info['b_lastname'], "KVHA", 'UTF-8'), 0, 20);
    $knv_data['custmKanji']= $lastname . $firstname;

    // 電話番号
    $knv_data['tel'] = substr(preg_replace("/-/", "", mb_convert_kana($order_info['phone'],"a")), 0, 11);

    // カード番号預かり機能を利用する場合
    if( $processor_data['processor_params']['use_dbkey'] == 'Y' ){
        // DBキーを取得
        $dbkey = fn_knv_get_dbkey();
        // DBキーが存在する場合DBキーをセット
        if( !empty($dbkey) ) $knv_data['dbkey'] = $dbkey;
    }

    $linkstr = '';

    foreach($knv_data as $knv_param){
        $linkstr .= $knv_param;
    }

    $linkstr = $linkstr . Registry::get('addons.kessai_navi.hashpass');
    $linkstr = mb_convert_encoding($linkstr, 'SJIS', 'UTF-8');

    // メッセージダイジェスト
    $knv_data['schksum'] = htmlspecialchars(md5($linkstr));
    /////////////////////////////////////////////////////////////////////////////////
    // 決済ナビに送信するデータ EOF
    /////////////////////////////////////////////////////////////////////////////////

    // この処理を入れないと決済ナビで決済後表示されるリンクでCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
    // この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
    unset(Tygh::$app['session']['cart']['processed_order_id']);

    echo <<<EOT
<html>
<body onLoad="document.charset='Shift_JIS'; document.process.submit();">
<form action="{$target_url}" method="POST" name="process" Accept-charset="Shift_JIS">
EOT;
foreach($knv_data as $key => $val){
    echo '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
}
$msg = __('text_cc_processor_connection');
$msg = str_replace('[processor]', __('jp_knv_kessai_navi'), $msg);
echo <<<EOT
</form>
<div align=center>{$msg}</div>
</body>
</html>
EOT;
}
exit;
