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

// $Id: remise_csp.php by tommy from cs-cart.jp 2015
// ルミーズマルチ決済

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


if (defined('PAYMENT_NOTIFICATION')) {

	// ルミーズ側で決済処理を実行した場合
	if ($mode == 'process') {

		// 正常終了した場合
		if ($_REQUEST['X-R_CODE'] == "0:0000") {

			if (fn_check_payment_script('remise_csp.php', $_REQUEST['order_id'])) {
				$return_order_id = (int)$_REQUEST['order_id'];
				$order_comments = db_get_field("SELECT notes FROM ?:orders WHERE order_id = $return_order_id");
				fn_remise_set_cvs_data($return_order_id, $_REQUEST, $order_comments);
				$pp_response = array();
				$pp_response['order_status'] = 'O';
				fn_finish_payment($_REQUEST['order_id'], $pp_response);
				fn_order_placement_routines('route', $_REQUEST['order_id']);
			}

		// エラーが発生した場合
		}else{
			$pp_response["order_status"] = 'F';
			if (fn_check_payment_script('remise_csp.php', $_REQUEST['order_id'])) {
				fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
				fn_order_placement_routines('route', $_REQUEST['order_id']);
			}
		}

	// 決済処理をキャンセルした場合
	} elseif ($mode == 'cancelled') {
		$pp_response["order_status"] = 'F';
		if (fn_check_payment_script('remise_csp.php', $_REQUEST['order_id'])) {
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
	if( $processor_data['processor_params']['mode'] == 'live' ){
		$remise_data['URL'] = $processor_data['processor_params']['url_production'];
	// テスト環境
	}else{
		$remise_data['URL'] = $processor_data['processor_params']['url_test'];
	}

	// 接続先URLがセットされていない場合には、強制的にテスト環境に接続
	if($remise_data['URL'] == ''){
		$remise_data['URL'] = 'https://test.remise.jp/rpgw2/pc/cvs/paycvs.aspx';
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

	// 姓名
	$remise_data['NAME1'] = fn_remise_get_sjis_strcut($order_info['b_firstname']);
	$remise_data['NAME2'] = fn_remise_get_sjis_strcut($order_info['b_lastname']);

	// 姓名カナ
	$name_kana_info = fn_lcjp_get_name_kana($order_info);
	$remise_data['KANA1'] = fn_remise_get_sjis_strcut($name_kana_info['firstname_kana'],true);
	$remise_data['KANA2'] = fn_remise_get_sjis_strcut($name_kana_info['lastname_kana'],true);

	// 郵便番号
	$remise_data['YUBIN1'] = preg_replace("/-/", "", mb_convert_kana(substr($order_info['b_zipcode'], 0, 3), "a", 'UTF-8'));

	// 請求先住所
	$billing_address = mb_convert_kana($order_info['b_state'] . $order_info['b_city'] . $order_info['b_address']. $order_info['b_address_2'], "KVHAS", 'UTF-8');
	if(strlen($billing_address) <= 50){
		$remise_data['ADD1'] = $billing_address;
		$remise_data['ADD2'] = '';
		$remise_data['ADD3'] = '';
	}elseif (strlen($billing_address) <= 100){    
		$remise_data['ADD1'] = mb_strcut($billing_address, 0, 50, 'UTF-8');
		$remise_data['ADD2'] = mb_strcut($billing_address, 26, 50, 'UTF-8');
		$remise_data['ADD3'] = '';
	}else{
		$remise_data['ADD1'] = mb_strcut($billing_address, 0, 50, 'UTF-8');
		$remise_data['ADD2'] = mb_strcut($billing_address, 26, 50, 'UTF-8');
		$remise_data['ADD3'] = mb_strcut($billing_address, 51, 100, 'UTF-8');
	}

	// 電話番号
	$remise_data['TEL'] = substr(preg_replace("/-/", "", mb_convert_kana($order_info['phone'],"a")), 0, 11);

	// メールアドレス
	$remise_data['MAIL'] = mb_convert_kana($order_info['email'], "a");

	// 請求金額
	$remise_data['TOTAL'] = round($order_info['total']);

	// 外税分消費税（ゼロ固定）
	$remise_data['TAX'] = 0;

	// 支払期限
	$remise_data['S_PAYDATE'] = date('Ymd', mktime(0, 0, 0, date('m'), date('d') + $processor_data['processor_params']['s_paydate'], date('Y')));

	// 成約日
	$remise_data['SEIYAKUDATE'] = date('Ymd');

	// 明細品名1（半角スペース不可）
	$remise_data['MNAME_01'] = trim(mb_convert_kana(__('jp_remise_goods_name'), "KVHA", 'UTF-8'));

	// 明細金額1
	$remise_data['MSUM_01'] = round($order_info['total']);

	// 中止URL
	$remise_data['EXITURL'] = fn_lcjp_get_return_url('/jp_extras/remise_csp/csp_cancelled.php?order_id=' . $order_id . "&area=" . AREA);

    $session = Tygh::$app['session'];
    $sidckv = $session->getName() . '=' . $session->getID();
    
	// 完了テンプレートURL
	$remise_data['RETURL'] = fn_url("payment_notification.process&payment=remise_csp&order_id=$order_id&" . $sidckv, AREA, 'current');

	// オプション
	$remise_data['OPT'] = '';

	// NG完了通知URL
	$remise_data['NG_RETURL'] = fn_url("payment_notification.cancelled&payment=remise_csp&order_id=$order_id&" . $sidckv, AREA, 'current');

	// オプション
	$remise_data['REMARKS'] = 'A0000341';
	/////////////////////////////////////////////////////////////////////////////////
	// ルミーズに送信するデータ EOF
	/////////////////////////////////////////////////////////////////////////////////

echo <<<EOT
<html>
<body onLoad="org=document.charset; document.charset='Shift_JIS'; document.process.submit(); document.charset=org;">
<form action="{$remise_data['URL']}" method="POST" name="process" Accept-charset="Shift_JIS">
	<input type="hidden" name="SHOPCO" value="{$remise_data['SHOPCO']}" />
	<input type="hidden" name="HOSTID" value="{$remise_data['HOSTID']}" />
	<input type="hidden" name="S_TORIHIKI_NO" value="{$remise_data['S_TORIHIKI_NO']}">
	<input type="hidden" name="NAME1" value="{$remise_data['NAME1']}">
	<input type="hidden" name="NAME2" value="{$remise_data['NAME2']}">
	<input type="hidden" name="KANA1" value="{$remise_data['KANA1']}">
	<input type="hidden" name="KANA2" value="{$remise_data['KANA2']}">
	<input type="hidden" name="YUBIN1" value="{$remise_data['YUBIN1']}">
	<input type="hidden" name="ADD1" value="{$remise_data['ADD1']}">
	<input type="hidden" name="ADD2" value="{$remise_data['ADD2']}">
	<input type="hidden" name="ADD3" value="{$remise_data['ADD3']}">
	<input type="hidden" name="TEL" value="{$remise_data['TEL']}">
	<input type="hidden" name="MAIL" value="{$remise_data['MAIL']}">
	<input type="hidden" name="TAX" value="{$remise_data['TAX']}">
	<input type="hidden" name="TOTAL" value="{$remise_data['TOTAL']}" />
	<input type="hidden" name="S_PAYDATE" value="{$remise_data['S_PAYDATE']}" />
	<input type="hidden" name="SEIYAKUDATE" value="{$remise_data['SEIYAKUDATE']}" />
	<input type="hidden" name="MNAME_01" value="{$remise_data['MNAME_01']}" />
	<input type="hidden" name="MSUM_01" value="{$remise_data['MSUM_01']}" />
	<input type="hidden" name="EXITURL" value="{$remise_data['EXITURL']}" />
	<input type="hidden" name="RETURL" value="{$remise_data['RETURL']}" />
	<input type="hidden" name="OPT" value="{$remise_data['OPT']}" />
	<input type="hidden" name="NG_RETURL" value="{$remise_data['NG_RETURL']}" />
	<input type="hidden" name="REMARKS3" value="{$remise_data['REMARKS']}" />
EOT;

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




function fn_remise_set_cvs_data($return_order_id, $remise_csv_response, $order_comments){

	$cvs_info = fn_remise_get_cvs_info($remise_csv_response['X-PAY_CSV']);

	if($order_comments != ''){
		$order_comments = $order_comments . "\n";
	}

	$order_comments = $order_comments . 
							__('jp_remise_cvs_info') . "\n" .
							__('jp_remise_cvs_name') . ' : ' . $cvs_info['cvs_name'] . "\n" .
							$cvs_info['cvs_x_pay1'] . ' : ' . $remise_csv_response['X-PAY_NO1'] . "\n" .
							$cvs_info['cvs_x_pay2'] . ' : ' . $remise_csv_response['X-PAY_NO2'];


	$data = array('notes' => $order_comments);

    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($return_order_id);

    // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
    foreach($order_ids_to_process as $order_id){
        $valid_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);

        // 正常なフローでの処理の場合のみ追記する
        if( !empty($valid_id) ){
            db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id);
        }
    }
}




function fn_remise_get_cvs_info($remise_csv_code){

	$remise_csv_info = array();

	switch($remise_csv_code){
		case 'D001':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_se'),
						'cvs_x_pay1' => __('jp_cvs_payment_number'),
						'cvs_x_pay2' => __('jp_cvs_url')
				);
			break;
		case 'D002':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_ls'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D015':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_sm'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D405':
			$remise_csv_info = 
				array('cvs_name' => __('jp_payment_pez'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D003':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_ts'),
						'cvs_x_pay1' => __('jp_cvs_payment_online_payment_number'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D004':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_ck'),
						'cvs_x_pay1' => __('jp_cvs_payment_online_payment_number'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D005':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_ms'),
						'cvs_x_pay1' => __('jp_cvs_payment_online_payment_number'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D010':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_dy'),
						'cvs_x_pay1' => __('jp_cvs_payment_online_payment_number'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D011':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_yd'),
						'cvs_x_pay1' => __('jp_cvs_payment_online_payment_number'),
						'cvs_x_pay2' => __('jp_cvs_payment_instruction_url')
				);
			break;
		case 'D030':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_fm'),
						'cvs_x_pay1' => __('jp_cvs_company_code'),
						'cvs_x_pay2' => __('order_id')
				);
			break;
		case 'D401':
			$remise_csv_info = 
				array('cvs_name' => __('jp_payment_cyberedy'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_url')
				);
			break;
		case 'D404':
			$remise_csv_info = 
				array('cvs_name' => __('jp_payment_rakutenbank'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_url')
				);
			break;
		case 'D406':
			$remise_csv_info = 
				array('cvs_name' => __('jp_payment_jnb'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_url')
				);
			break;
		case 'D451':
			$remise_csv_info = 
				array('cvs_name' => __('jp_payment_webmoney'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_url')
				);
			break;
		case 'D452':
			$remise_csv_info = 
				array('cvs_name' => __('jp_payment_bitcash'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_url')
				);
			break;
		case 'D453':
			$remise_csv_info =
				array('cvs_name' => __('jp_payment_jcb_premo'),
					'cvs_x_pay1' => __('jp_cvs_receipt_no'),
					'cvs_x_pay2' => __('jp_cvs_url')
				);
			break;
		case 'P901':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_payment_slip'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_payment_barcode')
				);
			break;
		case 'P902':
			$remise_csv_info = 
				array('cvs_name' => __('jp_cvs_payment_slip_jpost'),
						'cvs_x_pay1' => __('jp_cvs_receipt_no'),
						'cvs_x_pay2' => __('jp_cvs_payment_barcode')
				);
			break;
	}

	return $remise_csv_info;
}




/**
 * 文字列をSJISベースで規定の文字数にカットする
 *
 * @param $str
 * @param bool|false $kana
 * @param int $length
 * @return string
 */
function fn_remise_get_sjis_strcut($str, $kana = false, $length = 20)
{
	if($kana){
		return mb_convert_kana(mb_convert_encoding(mb_strcut(mb_convert_encoding(mb_convert_kana($str, "KVHA", 'UTF-8'), 'SJIS', 'UTF-8'), 0, $length, 'SJIS'), 'UTF-8', 'SJIS'), "C", 'UTF-8');
	}else{
		return mb_convert_encoding(mb_strcut(mb_convert_encoding(mb_convert_kana($str, "KVHA", 'UTF-8'), 'SJIS', 'UTF-8'), 0, $length, 'SJIS'), 'UTF-8', 'SJIS');
	}
}
