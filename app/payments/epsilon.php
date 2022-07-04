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

// $Id: epsilon.php by tommy from cs-cart.jp 2016
// イプシロン決済
// Modified by takahashi from cs-cart.jp 2017
// コメント欄に記録している処理結果を、支払情報に記録

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

	// イプシロン側で決済処理を実行した場合
	if ($mode == 'process') {

		// Epsilon Order Data
		$eod = fn_epsilon_get_order_info($_REQUEST['trans_code'], $_REQUEST['order_number']);

		// 正常終了した場合
		if($eod['result'] == 'success'){

			if (fn_check_payment_script('epsilon.php', $eod['order_id'])) {
				$pp_response = array();
				// 注文ステータス
				$pp_response['order_status'] = ($eod['state'] == 1) ? 'P' : 'O';

				// 支払内容をコメント欄などに追記
				fn_eplision_add_notes($eod);

				fn_finish_payment($eod['order_id'], $pp_response);
				fn_order_placement_routines('route', $eod['order_id']);
			}

		// エラーが発生し、注文番号が取得できる場合（通常はこの段階でエラーは発生しないが、念のため）
		}elseif( !empty($eod['order_id']) ){
			$pp_response["order_status"] = 'F';

			if (fn_check_payment_script('epsilon.php', $eod['order_id'])) {
				fn_set_notification('E', __('jp_text_epsilon_error'), $eod['err_msg']);
				fn_finish_payment($eod['order_id'], $pp_response); // Force user notification
				fn_order_placement_routines('route', $eod['order_id']);
			}

		// エラーが発生し、注文番号が取得できない場合（通常はこの段階でエラーは発生しないが、念のため）
		}else{
			// カートページへリダイレクト
			fn_set_notification('E', __('jp_text_epsilon_error'), $eod['err_msg']);

            $return_url = fn_url("checkout.cart", 'C', 'current');
            fn_redirect($return_url, true);
		}

	// イプシロンへの初回データ送信の時点でエラーが発生した場合
	}elseif ($mode == 'error') {

		$pp_response["order_status"] = 'F';

		if (fn_check_payment_script('epsilon.php', $_REQUEST['order_id'])) {
			fn_set_notification('E', __('jp_text_epsilon_error'), fn_epsilon_get_error_info($_REQUEST['err_msg']));
			fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
			fn_order_placement_routines('route', $_REQUEST['order_id']);
		}

	// 決済処理をキャンセルした場合
	} elseif ($mode == 'cancelled') {
		$pp_response["order_status"] = 'F';

		if (fn_check_payment_script('epsilon.php', $_REQUEST['order_id'])) {
			fn_finish_payment($_REQUEST['order_id'], $pp_response); // Force user notification
			fn_order_placement_routines('route', $_REQUEST['order_id']);
		}
	}

} else {

	// PEAR拡張モジュールの読み込み。
	require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/pear/http/Request.php');
	require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/pear/xml/Unserializer.php');

	/////////////////////////////////////////////////////////////
	// イプシロンに送信するパラメーターをセット BOF
	/////////////////////////////////////////////////////////////

	// 契約コード
	$contract_code = $processor_data['processor_params']['contract_code'];

	// 会員登録済みの決済の場合
	if( !empty($order_info['user_id']) ){
		// ユーザーIDをセット
		$user_id = (int)$order_info['user_id'];

	// ゲスト購入の場合
	}else{
		// ランダムなIDを発行
		mt_srand(microtime()*100000);
		$user_id = 'guest' . rand(10000, 99999);
	}

	// ユーザー氏名
	$user_name = $order_info['b_firstname'] . '　' . $order_info['b_lastname'];

	// メールアドレス
	$user_mail_add = $order_info['email'];

	// 商品コード（固定）
	$item_code = 'EPSILON-0001';

	// 商品名（固定）
	$item_name = __('jp_epsilon_item_name');

	// オーダー番号
	$order_number = $order_id . date('ymdHis');

	// 決済区分
	$st_code = fn_epsilon_get_st_code($processor_data['processor_params']);

	// 課金区分（とりあえず固定）
	$mission_code = 1;

	// 価格
	$item_price = round($order_info['total']);

	// 処理区分（とりあえず固定）
	$process_code = 1;

	// 予備1
	$memo1 = '';

	// 予備2
	$memo2 = '';

	// コンビニコード（とりあえず固定）
	$conveni_code = '';

	// 電話番号
	$user_tel = $order_info['phone'];

	// 氏名（カナ）（とりあえず固定）
	$user_name_kana = '';
	/////////////////////////////////////////////////////////////
	// イプシロンに送信するパラメーターをセット EOF
	/////////////////////////////////////////////////////////////


	/////////////////////////////////////////////////////////////
	// 接続するURLをセット BOF
	/////////////////////////////////////////////////////////////
	if( $processor_data['processor_params']['mode'] == 'live' ){
		// 本番環境
		$pp_connection_url = $processor_data['processor_params']['url_production'];
	}else{
		// テスト環境
		$pp_connection_url = $processor_data['processor_params']['url_test'];
	}

	// 接続先URLがセットされていない場合には、強制的にテスト環境に接続
	if($pp_connection_url == ''){
		$pp_connection_url = 'https://beta.epsilon.jp/cgi-bin/order/receive_order3.cgi';
	}
	/////////////////////////////////////////////////////////////
	// 接続するURLをセット EOF
	/////////////////////////////////////////////////////////////


	// httpリクエスト用のオプションを指定
	$option = array("timeout" => "20"); // タイムアウトの秒数指定

	// HTTP_Requestの初期化
	$request = new HTTP_Request($pp_connection_url, $option);
  
	// リクエストメソッドをセット
  	$request->setMethod(HTTP_REQUEST_METHOD_POST);

	// POSTパラメータをセット
	$request->addPostData('contract_code', $contract_code);
	$request->addPostData('user_id', $user_id);
	$request->addPostData('user_name', mb_convert_encoding($user_name, 'EUC-JP' , 'UTF-8') );
	$request->addPostData('user_mail_add', $user_mail_add);
	$request->addPostData('item_code', $item_code);
	$request->addPostData('item_name', mb_convert_encoding($item_name, 'EUC-JP' , 'UTF-8') );
	$request->addPostData('order_number', $order_number);
	$request->addPostData('st_code', $st_code);
	$request->addPostData('mission_code', $mission_code);
	$request->addPostData('item_price', $item_price);
	$request->addPostData('process_code', $process_code);
	$request->addPostData('memo1', $memo1);
	$request->addPostData('memo2', $memo2);
	$request->addPostData('xml', '1');

	// HTTPリクエスト実行
	$response = $request->sendRequest(); 

	$is_error = false;

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
			//xmlを解析
			$res_array = $unserializer->getUnserializedData();
			$xml_redirect_url = "";
			$xml_error_cd = "";
			$xml_error_msg = "";
			$xml_memo1_msg = "";
			$xml_memo2_msg = "";

			foreach($res_array['result'] as $uns_k => $uns_v){

				list($result_atr_key, $result_atr_val) = each($uns_v);

				switch ($result_atr_key) {
					case 'redirect':
						$xml_redirect_url = rawurldecode($result_atr_val);
						break;
					case 'err_code':
						$is_error = true;
						$xml_error_cd = $result_atr_val;
						break;
					case 'err_detail':
						$xml_error_msg = $result_atr_val;
						break;
					case 'memo1':
						// コンバート元の文字コードを検出するよう変更
						$xml_memo1_msg = mb_convert_encoding(urldecode($result_atr_val), "UTF-8", fn_detect_encoding(urldecode($result_atr_val), 'S'));

						break;
					case 'memo2':
						// コンバート元の文字コードを検出するよう変更
						$xml_memo1_msg = mb_convert_encoding(urldecode($result_atr_val), "UTF-8", fn_detect_encoding(urldecode($result_atr_val), 'S'));
						break;
					default:
						break;
				}
			}

		// XMLのパースに失敗した場合
		}else{
			$is_error = true;
			$xml_error_msg = urlencode(__('jp_epsilon_xml_parse_error'));
		}

	}else{
		$is_error = true;
		$xml_error_msg = urlencode(__('jp_epsilon_send_error'));
	}

	// エラーがある場合
	if($is_error){
		// 注文処理ページへリダイレクト
        $return_url = fn_url("payment_notification.error&payment=epsilon&err_msg=$xml_error_msg" . "&order_id=$order_id", AREA, 'current');
        fn_redirect($return_url, true);
    }else{

		// この処理を入れないとイプシロンで決済後表示されるリンクでCS-Cartに戻らず、CS-Cartを表示させた場合に再度同じ注文IDで決済が行われる
		// この処理を入れることにより受注処理未了の注文がずっと残るが、それよりも同一注文IDで意図しない注文処理が実行される方のリスクが高い。
		unset(Tygh::$app['session']['cart']['processed_order_id']);

		// イプシロン側の決済ページへリダイレクト
		fn_redirect($xml_redirect_url, true, true);
	}


$msg = __('text_cc_processor_connection');
$msg = str_replace('[processor]', __('jp_epsilon_company_name'), $msg);
echo <<<EOT
<html>
<body>
	<div align=center>{$msg}</div>
 </body>
</html>
EOT;
}
exit;




// 決済区分をセット
function fn_epsilon_get_st_code($processor_param)
{
	$st_array = array();

	$st_array[0] = ($processor_param['cc'] == 'true') ? '10' : '00';
	$st_array[1] = ($processor_param['cvs'] == 'true') ? '1' : '0';
	$st_array[2] = ($processor_param['jnb'] == 'true') ? '1' : '0';
	$st_array[3] = ($processor_param['rakutenbank'] == 'true') ? '1' : '0';
	$st_array[4] = '-0';
	$st_array[5] = ($processor_param['pez'] == 'true') ? '1' : '0';
	$st_array[6] = ($processor_param['wm'] == 'true') ? '1' : '0';
	$st_array[7] = '0';
	$st_array[8] = '-0';
	$st_array[9] = ($processor_param['paypal'] == 'true') ? '1' : '0';
	$st_array[10] = ($processor_param['bitcash'] == 'true') ? '1' : '0';
	$st_array[11] = ($processor_param['chocom'] == 'true') ? '1' : '0';
	$st_array[12] = '0';

	return implode('', $st_array);
}




// エラーメッセージをセット
function fn_epsilon_get_error_info($encoded_msg = '')
{
	if($encoded_msg != ''){
		// コンバート元の文字コードを検出するよう変更
		return mb_convert_encoding(urldecode($_REQUEST['err_msg']), "UTF-8", fn_detect_encoding(urldecode($_REQUEST['err_msg']), 'S'));
	}else{
		return __('jp_epsilon_general_error');
	}
}




// イプシロン側に登録された注文データを取得
function fn_epsilon_get_order_info($trans_code, $order_number ='')
{
	define('EPSILON_DATE_LENGTH', 12);

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




// 支払方法を取得
function fn_epsilon_get_payment_method($payment_code)
{
	$payment_method = '';

	switch($payment_code){
		case 1:
		case 2:
			$payment_method = __('jp_payment_cc');
			break;
		case 3:
			$payment_method = __('jp_payment_cvs');
			break;
		case 4:
			$payment_method = __('jp_payment_jnb');
			break;
		case 5:
			$payment_method = __('jp_payment_rakutenbank');
			break;
		case 7:
			$payment_method = __('jp_payment_pez');
			break;
		case 8:
			$payment_method = __('jp_payment_webmoney');
			break;
		case 11:
			$payment_method = __('jp_payment_paypal');
			break;
		case 12:
			$payment_method = __('jp_payment_bitcash');
			break;
		case 13:
			$payment_method = __('jp_payment_chocom');
			break;
		default:
			$payment_method = 'N/A';
			break;
	}

	return $payment_method;
}




// クレジットカードの決済方法を取得
function fn_epsilon_get_cc_method($card_st_code = '')
{
	switch($card_st_code){
		case 61:
			$cc_method = __('jp_payment_installment');
			break;
		case 80:
			$cc_method = __('jp_cc_revo');
			break;
		default:
			$cc_method = __('jp_cc_onetime');
			break;
	}

	return $cc_method;
}




// コンビニ名称を取得
function fn_epsilon_get_cvs_name($conveni_code)
{
	switch($conveni_code){
		case 11:
			$cvs_name = __('jp_cvs_se');
			break;
		case 21:
			$cvs_name = __('jp_cvs_fm');
			break;
		case 31:
			$cvs_name = __('jp_cvs_ls');
			break;
		case 32:
			$cvs_name = __('jp_cvs_sm');
			break;
		case 33:
			$cvs_name = __('jp_cvs_ms');
			break;
		case 35:
			$cvs_name = __('jp_cvs_ck');
			break;
		case 36:
			$cvs_name = __('jp_cvs_ts');
			break;
		default:
			$cvs_name = 'N/A';
			break;
	}

	return $cvs_name;
}




// 支払内容をコメント欄などに追記
function fn_eplision_add_notes($eod)
{
    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($eod['order_id']);

    // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
    foreach($order_ids_to_process as $order_id){

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2017 BOF
        // コメント欄に記録している処理結果を、支払情報に記録
        ///////////////////////////////////////////////
        $info['jp_epsilon_trans_method'] = fn_epsilon_get_payment_method( $eod['payment_code'] );

        switch($eod['payment_code']){
            // クレジットカード決済の場合
            case 1:
            case 2:
                // 決済方法
                $cc_method = fn_epsilon_get_cc_method($eod['cc_st_code']);

                // 支払回数
                $cc_paytime = $eod['cc_paytime'];

                if( !empty($cc_method) ){
                    $info['payment_method'] = $cc_method;
                }

                // リボ払い以外は支払回数を表示
                if( !empty($cc_paytime) && $eod['cc_st_code'] != 80){
                    $info['jp_paytimes'] = $cc_paytime . __('jp_paytimes_unit');
                }
                break;

            // コンビニ決済の場合
            case 3:

                // コンビ二名
                $cvs_name = fn_epsilon_get_cvs_name($eod['cvs_code']);

                // 受付番号
                $cvs_receipt_no = $eod['cvs_receipt_no'];

                // 支払期限
                $cvs_limit = $eod['cvs_limit'];

                // セブンイレブンの場合
                if($eod['cvs_code'] == 11){
                    // 払込票URL
                    $cvs_url = $eod['cvs_url'];
                }

                // ファミリーマートの場合
                if($eod['cvs_code'] == 21){
                    // 企業コード
                    $cvs_kigyou_code = $eod['cvs_kigyou_code'];
                }

                if( !empty($cvs_name) ){
                    $info['jp_cvs_name'] = $cvs_name;
                }

                if( !empty($cvs_receipt_no) ){
                    $info['jp_cvs_receipt_no'] = $cvs_receipt_no;
                }

                if( !empty($cvs_kigyou_code) ){
                    $info['jp_cvs_company_code'] = $cvs_kigyou_code;
                }

                if( !empty($cvs_limit) ){
                    $info['jp_cvs_limit'] = $cvs_limit;
                }

                if( !empty($cvs_url) ){
                    $info['jp_cvs_url'] = $cvs_url;
                }
                break;

            // ペイジー決済の場合
            case 7:

                // 受付番号
                $pez_receipt_no = $eod['pez_receipt_no'];

                // 企業コード
                $pez_kigyou_code = $eod['pez_kigyou_code'];

                // 支払期限
                $pez_limit = $eod['pez_limit'];

                if( !empty($pez_receipt_no) ){
                    $info['jp_pez_receipt_no'] = $pez_receipt_no;
                }

                if( !empty($pez_kigyou_code) ){
                    $info['jp_pez_company_code'] = $pez_kigyou_code;
                }

                if( !empty($pez_limit) ){
                    $info['jp_pez_limit'] = $pez_limit;
                }

                break;

            default:
                break;
        }

        // 支払情報を暗号化
        $_data = fn_encrypt_text(serialize($info));

        $valid_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);

        // 正常なフローでの処理の場合のみ追記する
        if( !empty($valid_id) ){
            // 注文データ内の支払関連情報を追加
            $insert_data = array (
                'order_id' => $order_id,
                'type' => 'P',
                'data' => $_data,
            );
            db_query("REPLACE INTO ?:order_data ?e", $insert_data);
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2017 EOF
        ///////////////////////////////////////////////
    }
}
