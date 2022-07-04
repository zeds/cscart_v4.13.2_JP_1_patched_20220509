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

// $Id: remise_cc.php by tommy from cs-cart.jp 2015
// ルミーズクレジットカード決済

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

	// ルミーズ側で決済処理を実行した場合
	if ($mode == 'process') {

		// 正常終了した場合
		if($_REQUEST['X-ERRLEVEL'] == 0){
			if (fn_check_payment_script('remise_cc.php', $_REQUEST['order_id'])) {
				$pp_response = array();
				$pp_response['order_status'] = 'P';
				fn_finish_payment($_REQUEST['order_id'], $pp_response);
				fn_order_placement_routines('route', $_REQUEST['order_id']);
			}

		// エラーが発生した場合
		}else{
			$error_info = array();
			$error_info = fn_remise_get_error_info($_REQUEST['X-ERRCODE'], $_REQUEST['X-ERRINFO']);
			$pp_response["order_status"] = 'F';
			if (fn_check_payment_script('remise_cc.php', $_REQUEST['order_id'])) {
				fn_set_notification('E', $error_info['title'], $error_info['error']);
				fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
				fn_order_placement_routines('route', $_REQUEST['order_id']);
			}
		}

	// 決済処理をキャンセルした場合
	} elseif ($mode == 'cancelled') {
		$pp_response["order_status"] = 'F';
		if (fn_check_payment_script('remise_cc.php', $_REQUEST['order_id'])) {
			fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
			fn_order_placement_routines('route', $_REQUEST['order_id']);
		}
	}

} else {

    // ルミーズに送信するデータ用配列を初期化
    $remise_data = array();

    /////////////////////////////////////////////////////////////////////////////////
    // 接続先URL BOF
    /////////////////////////////////////////////////////////////////////////////////
    // 本番環境
    if ($processor_data['processor_params']['mode'] == 'live') {
        $remise_data['URL'] = $processor_data['processor_params']['url_production'];
        // テスト環境
    } else {
        $remise_data['URL'] = $processor_data['processor_params']['url_test'];
    }

    // 接続先URLがセットされていない場合には、強制的にテスト環境に接続
    if ($remise_data['URL'] == '') {
        $remise_data['URL'] = 'https://test.remise.jp/rpgw2/pc/card/paycard.aspx';
    }
    /////////////////////////////////////////////////////////////////////////////////
    // 接続先URL EOF
    /////////////////////////////////////////////////////////////////////////////////


    /////////////////////////////////////////////////////////////////////////////////
    // ルミーズに送信するデータ BOF
    /////////////////////////////////////////////////////////////////////////////////
    // 加盟店コード
    $remise_data['SHOPCO'] = $processor_data['processor_params']['shop_code'];

    // ホスト番号
    $remise_data['HOSTID'] = $processor_data['processor_params']['host_id'];

    // 請求番号
    $remise_data['S_TORIHIKI_NO'] = $order_id . date('ymdHis');

    // 処理区分
    $remise_data['JOB'] = 'AUTH';

    // e-mail
    $remise_data['MAIL'] = $order_info['email'];

    // 商品コード
    $remise_data['ITEM'] = '0000120';

    // 金額（四捨五入して整数型にする）
    $remise_data['AMOUNT'] = round($order_info['total']);

    // 税送料（ゼロ固定）
    $remise_data['TAX'] = 0;

    // 合計金額
    $remise_data['TOTAL'] = round($order_info['total']);

    // 支払区分
    $remise_data['METHOD'] = $order_info['payment_info']['jp_remise_payment_method'];

    // 分割回数（2固定）
    $remise_data['PTIMES'] = 2;

	// 中止URL
	$remise_data['EXITURL'] = fn_lcjp_get_return_url('/jp_extras/remise_cc/cc_cancelled.php?order_id=' . $order_id . "&area=" . AREA);

    $session = Tygh::$app['session'];
    $sidckv = $session->getName() . '=' . $session->getID();
    
	// 完了テンプレートURL
	$remise_data['RETURL'] = fn_url("payment_notification.process&payment=remise_cc&order_id=$order_id&" . $sidckv, AREA, 'current');

	// オプション
	$remise_data['OPT'] = '';

	// NG完了通知URL
	$remise_data['NG_RETURL'] = fn_url("payment_notification.cancelled&payment=remise_cc&order_id=$order_id&" . $sidckv, AREA, 'current');

	// ペイクイック機能
	$remise_data['PAYQUICK'] = '';
	$remise_data['PAYQUICKID'] = '';

	if($processor_data['processor_params']['payquick'] == 'true'){

		// 顧客がペイクイック機能の利用を希望した場合
		if($order_info['payment_info']['jp_remise_use_payquick'] == 'Y'){

			$remise_data['PAYQUICK'] = 1;

			// 顧客ID
			$user_id = (int)$order_info['user_id'];

			// ペイクイックID
			$payquick_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id =?i AND payment_method =?s", $user_id, 'remise_cc');

			if( !empty($payquick_id) ){
				$remise_data['PAYQUICKID'] = $payquick_id;
			}
		}
	}

	// オプション
	$remise_data['REMARKS'] = 'A0000341';
	/////////////////////////////////////////////////////////////////////////////////
	// ルミーズに送信するデータ EOF
	/////////////////////////////////////////////////////////////////////////////////

echo <<<EOT
<html>
<body onLoad="document.process.submit();">
<form action="{$remise_data['URL']}" method="POST" name="process">
	<input type="hidden" name="SHOPCO" value="{$remise_data['SHOPCO']}" />
	<input type="hidden" name="HOSTID" value="{$remise_data['HOSTID']}" />
	<input type="hidden" name="S_TORIHIKI_NO" value="{$remise_data['S_TORIHIKI_NO']}">
	<input type="hidden" name="JOB" value="{$remise_data['JOB']}" />
	<input type="hidden" name="MAIL" value="{$remise_data['MAIL']}" />
	<input type="hidden" name="ITEM" value="{$remise_data['ITEM']}" />
	<input type="hidden" name="TOTAL" value="{$remise_data['AMOUNT']}" />
	<input type="hidden" name="TAX" value="{$remise_data['TAX']}" />
	<input type="hidden" name="AMOUNT" value="{$remise_data['TOTAL']}" />
	<input type="hidden" name="METHOD" value="{$remise_data['METHOD']}" />
	<input type="hidden" name="PTIMES" value="{$remise_data['PTIMES']}" />
	<input type="hidden" name="EXITURL" value="{$remise_data['EXITURL']}" />
	<input type="hidden" name="RETURL" value="{$remise_data['RETURL']}" />
	<input type="hidden" name="OPT" value="{$remise_data['OPT']}" />
	<input type="hidden" name="NG_RETURL" value="{$remise_data['NG_RETURL']}" />
	<input type="hidden" name="REMARKS3" value="{$remise_data['REMARKS']}" />
EOT;

// ペイクイック機能
if($remise_data['PAYQUICK'] == 1){
echo <<<EOT
	<input type="hidden" name="PAYQUICK" value="{$remise_data['PAYQUICK']}" />
EOT;
}

// ペイクイックID
if( !empty($remise_data['PAYQUICKID']) ){
echo <<<EOT
	<input type="hidden" name="PAYQUICKID" value="{$remise_data['PAYQUICKID']}" />
EOT;
}

$msg = __('text_cc_processor_connection');
$msg = str_replace('[processor]', __('jp_remise_company_name'), $msg);
echo <<<EOT
	</form>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
}
exit;




/**
 * エラーメッセージを取得
 *
 * @param $error_code
 * @param $error_info
 * @return array
 */
function fn_remise_get_error_info($error_code, $error_info)
{
	// エラーメッセージ
	$processor_error = array(
		"REMISE_ERROR_TITLE" => "クレジットカード エラー!",
		"REMISE_ERROR_H12_31003" => "カード番号に誤りがあります。カード番号を確かめて再度お手続きをお願いします。",
		"REMISE_ERROR_H12_31005" => "カード番号に誤りがあります。このカードはお取り扱いできません。",
		"REMISE_ERROR_H12_32006" => "カード番号に誤りがあります。カード番号を確かめて再度お手続きをお願いします。",
		"REMISE_ERROR_H97_22001" => "使用できないカード会社です。管理者へお問い合わせください",
		"REMISE_ERROR_H97_42101" => "実売上エラー（仮売上なし）。該当する仮売上がありません。",
		"REMISE_ERROR_H97_45003" => "取消エラー（履歴がありません）",
		"REMISE_ERROR_H97_45004" => "取消エラー（二重取り消し）。該当データは既に取り消し済みです。",
		"REMISE_ERROR_C14_G99" => "取り扱い不可（カード会社へお問い合わせください）",
		"REMISE_ERROR_G03" => "限度額オーバー（カード会社へお問い合わせください）",
		"REMISE_ERROR_G30" => "このカードは現在使用できません（カード会社へお問い合わせください）",
		"REMISE_ERROR_G56_P90" => "使用できないカード会社です。管理者へお問い合わせください",
		"REMISE_ERROR_G60" => "使用出来ないカードです",
		"REMISE_ERROR_G65" => "カード番号でエラーになりました。カード番号を確かめて再度お手続きをお願いします。",
		"REMISE_ERROR_G78" => "支払い方法でエラーになりました。他の支払い方法を選択してください。",
		"REMISE_ERROR_G83" => "有効期限エラー",
		"REMISE_ERROR_S01" => "カード会社のホストエラーです。しばらくしてから再度ご購入手続きをしてください。",
		"REMISE_ERROR_X51_X65" => "カード会社のシステムエラーです。しばらくしてから再度ご購入手続きをしてください。",
		"REMISE_ERROR_MISC" => "クレジットカードの処理中にエラーが発生しました. 入力内容を訂正しもう一度試してください。"
	);

	if($error_code == "H12" && $error_info == "310030000"){
		$error_message = $processor_error['REMISE_ERROR_H12_31003'];
	}elseif($error_code == "H12" && $error_info == "310050000"){
		$error_message = $processor_error['REMISE_ERROR_H12_31005'];
	}elseif($error_code == "H12" && $error_info == "320060000"){
		$error_message = $processor_error['REMISE_ERROR_H12_32006'];
	}elseif($error_code == "H97" && $error_info == "220010000"){
		$error_message = $processor_error['REMISE_ERROR_H97_22001'];
	}elseif($error_code == "H97" && $error_info == "421010000"){
		$error_message = $processor_error['REMISE_ERROR_H97_42101'];
	}elseif($error_code == "H97" && $error_info == "450030000"){
		$error_message = $processor_error['REMISE_ERROR_H97_45003'];
	}elseif($error_code == "H97" && $error_info == "450040000"){
		$error_message = $processor_error['REMISE_ERROR_H97_45004'];
	}elseif($error_code == "H97" && $error_info == "450050000"){
		$error_message = '';
	}elseif($error_code == "S99" && $error_info == "42S991000"){
		$error_message = '';
	}elseif($error_code == "S99" && $error_info == "42S994000"){
		$error_message = '';
	}elseif($error_code == 'C14' || $error_code == 'G12' || $error_code == 'G54' || $error_code == 'G55' || $error_code == 'G97' || $error_code == 'G99'){
		$error_message = $processor_error['REMISE_ERROR_C14_G99'];
	}elseif($error_code == 'G03'){
		$error_message = $processor_error['REMISE_ERROR_G03'];
	}elseif($error_code == 'G30'){
		$error_message = $processor_error['REMISE_ERROR_G30'];
	}elseif($error_code == 'G56' || $error_code == 'P90'){
		$error_message = $processor_error['REMISE_ERROR_G56_P90'];
	}elseif($error_code == 'G60'){
		$error_message = $processor_error['REMISE_ERROR_G60'];
	}elseif($error_code == 'G65'){
		$error_message = $processor_error['REMISE_ERROR_G65'];
	}elseif($error_code == 'G78'){
		$error_message = $processor_error['REMISE_ERROR_G78'];
	}elseif($error_code == 'G83'){
		$error_message = $processor_error['REMISE_ERROR_G83'];
	}elseif($error_code == 'S01'){
		$error_message = $processor_error['REMISE_ERROR_S01'];
	}elseif($error_code == 'X51' || $error_code == 'X53' || $error_code == 'X65'){
		$error_message = $processor_error['REMISE_ERROR_X51_X65'];
	}else{
		$error_message = $processor_error['REMISE_ERROR_MISC'];
	}

	if( strlen($error_code) > 0 )
		$error_message = $error_code . ':' . $error_message;

	return array('title' => $processor_error['REMISE_ERROR_TITLE'],
				'error' => $error_message);
}
