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

// $Id: process.php by tommy from cs-cart.jp 2015

use Tygh\Registry;

////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に BOF
////////////////////////////////////////////////
define('AREA', 'C');
define('EPSILON_DATE_LENGTH', 12);
require '../../init.php';
require_once(Registry::get('config.dir.addons') . 'localization_jp/func.php');
////////////////////////////////////////////////
// CS-Cartのクラスや関数を利用可能に EOF
////////////////////////////////////////////////

$extra_request = '';

// トランザクションコードが存在する場合
if( !empty($_REQUEST['trans_code']) ){

	$extra_request = '';

	// イプシロンからの戻りパラメータに注文番号が含まれる場合
	// （コンビニ決済・WebMoney決済以外は含まれる）
	if( !empty($_REQUEST['order_number']) ){
		$extra_request = "&amp;order_number=" . $_REQUEST['order_number'];
		// 注文IDを取得
		$order_id = substr($_REQUEST['order_number'], 0, strlen($_REQUEST['order_number']) - EPSILON_DATE_LENGTH);
	//コンビニ決済・WebMoney決済の場合
	}else{
		// Epsilon Order Data
		$eod = fn_epsilon_get_order_info($_REQUEST['trans_code']);
		// 注文IDを取得
		$order_id = $eod['order_id'];
	}

    // 注文IDから該当するcompany_idをセット
    fn_payments_set_company_id($order_id);

    // fn_urlのリダイレクトパラメータに追加する"company_id"を取得
    $company_query = fn_lcjp_get_company_query_from_order($order_id);

	// 注文処理ページへリダイレクト
    $url = fn_url("payment_notification.process&payment=epsilon&trans_code=" . $_REQUEST['trans_code'] . $extra_request . $company_query, AREA, 'current');
	fn_redirect($url);

}else{
	die('INVALID ACCESS!!');
}




/**
 * イプシロン側に登録された注文データを取得
 * app/payments/epsilon.php 内の同名関数のコピー
 *
 * @param $trans_code
 * @param string $order_number
 * @return array
 */
function fn_epsilon_get_order_info($trans_code, $order_number ='')
{
	$epsilon_order_data = array();
	$order_id = '';

	// PEAR拡張モジュールの読み込み。
	require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/pear/http/Request.php');
	require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/pear/xml/Unserializer.php');

	$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'epsilon.php' AND ?:payments.status = 'A'");
	$processor_data = fn_get_payment_method_data($payment_id);

	/////////////////////////////////////////////////////////////
	// 接続するURLをセット BOF
	/////////////////////////////////////////////////////////////
	if( $processor_data['processor_params']['mode'] == 'live' ){
		// 本番環境
		$order_conf_url = $processor_data['processor_params']['order_url_production'];
	}else{
		// テスト環境
		$order_conf_url = $processor_data['processor_params']['order_url_test'];
	}

	// 接続先URLがセットされていない場合には、強制的にテスト環境に接続
	if($order_conf_url == ''){
		$order_conf_url = "https://beta.epsilon.jp/cgi-bin/order/getsales2.cgi";
	}
	/////////////////////////////////////////////////////////////
	// 接続するURLをセット EOF
	/////////////////////////////////////////////////////////////

	// httpリクエスト用のオプションを指定
	$option = array("timeout" => "20"); // タイムアウトの秒数指定

	// HTTP_Requestの初期化
	$request = new HTTP_Request($order_conf_url, $option);

	// リクエストメソッドをセット
	$request->setMethod(HTTP_REQUEST_METHOD_POST);

	// POSTパラメータをセット
	$request->addPostData('contract_code', $processor_data['processor_params']['contract_code']);
	$request->addPostData('trans_code', $trans_code);

	// HTTPリクエスト実行
	$response = $request->sendRequest();

	// リクエスト送信が正常終了した場合
	if (!PEAR::isError($response)) {

		// 応答内容(XML)の解析
		$res_code = $request->getResponseCode();
		$res_content = $request->getResponseBody();

		// xml unserializer
		$temp_xml_res = str_replace("x-sjis-cp932", "EUC-JP", $res_content);
		$unserializer = new XML_Unserializer();
		$unserializer->setOption('parseAttributes', TRUE);
		$unseriliz_st = $unserializer->unserialize($temp_xml_res);

		if ($unseriliz_st === true) {
			// xmlを解析
			$res_array = $unserializer->getUnserializedData();

			foreach($res_array['result'] as $uns_k => $uns_v){
				list($result_atr_key, $result_atr_val) = each($uns_v);
				// コンバート元の文字コードを検出するよう変更
				$order_info[$result_atr_key] = mb_convert_encoding( urldecode($result_atr_val), 'UTF-8', fn_detect_encoding(urldecode($result_atr_val), 'S'));
			}

			if( $order_info['order_number'] ){
				// 注文IDを取得
				$order_id = substr($order_info['order_number'], 0, strlen($order_info['order_number']) - EPSILON_DATE_LENGTH);
			}

			if( $order_info['state'] ){
				// 支払状態を取得
				$state = $order_info['state'];
			}

			if( $order_info['payment_code'] ){
				// 決済方法を取得
				$payment_code = (int)$order_info['payment_code'];
			}

			$epsilon_order_data = array(
				'result' 		=> 'success',
				'order_id' 		=> $order_id,
				'state'			=> $state,
				'payment_code' => $payment_code,
			);

			// クレジットカード決済の場合の追加情報
			if($payment_code === 1 || $payment_code === 2){
				// クレジットカード決済方法
				$epsilon_order_data['cc_st_code'] = $order_info['card_st_code'];

				// 支払回数
				$epsilon_order_data['cc_paytime'] = $order_info['pay_time'];
			}

			// コンビニ決済の場合の追加情報
			if($payment_code === 3){

				// コンビ二コード
				$epsilon_order_data['cvs_code'] = $order_info['conveni_code'];

				// 受付番号
				$epsilon_order_data['cvs_receipt_no'] = $order_info['receipt_no'];

				// 受付日時
				$epsilon_order_data['cvs_receipt_date'] = $order_info['receipt_date'];

				// 支払期限
				$epsilon_order_data['cvs_limit'] = $order_info['conveni_limit'];

				// コンビニ入金日時
				$epsilon_order_data['cvs_time'] = $order_info['conveni_time'];

				// セブンイレブンの場合
				if($order_info['conveni_code'] == 11){
					$epsilon_order_data['cvs_url'] = $order_info['haraikomi_url'];
				}

				// ファミリーマートの場合
				if($order_info['conveni_code'] == 21){
					// 企業コード
					$epsilon_order_data['cvs_kigyou_code'] = $order_info['kigyou_code'];
				}

			}

			// ペイジー決済の場合の追加情報
			if($payment_code === 7){

				// 受付番号
				$epsilon_order_data['pez_receipt_no'] = $order_info['receipt_no'];

				// 企業コード
				$epsilon_order_data['pez_kigyou_code'] = $order_info['kigyou_code'];

				// 受付日時
				$epsilon_order_data['pez_receipt_date'] = urldecode($order_info['receipt_date']);

				// 支払期限
				$epsilon_order_data['pez_limit'] = urldecode($order_info['conveni_limit']);

				// コンビニ入金日時
				$epsilon_order_data['pez_time'] = urldecode($order_info['conveni_time']);

			}

			return $epsilon_order_data;

		}else{

			if( !empty($order_number) ){
				// 注文IDを取得
				$order_id = substr($order_number, 0, strlen($order_number) - EPSILON_DATE_LENGTH);
			}

			$epsilon_order_data = array(
				'result'		=> 'error',
				'order_id' 	=> $order_id,
				'err_msg'	=> __('jp_epsilon_xml_parse_error')
			);

			return $epsilon_order_data;
		}

	}else{

		if( !empty($order_number) ){
			// 注文IDを取得
			$order_id = substr($order_number, 0, strlen($order_number) - EPSILON_DATE_LENGTH);
		}


		$epsilon_order_data = array(
			'result'		=> 'error',
			'order_id' 		=> $order_id,
			'err_msg'	=> __('jp_epsilon_xml_parse_error')
		);

		return $epsilon_order_data;
	}
}
