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

// $Id: func.php by tommy from cs-cart.jp 2016
//
// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_digital_check_[フックポイント名]
// (2) (1)以外の関数：fn_dgtlchck_[任意の名称]

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * カード情報登録済ユーザーのみ支払方法に「ユーザーID決済」を表示
 *
 * @param $params
 * @param $payments
 */
function fn_digital_check_get_payments_post(&$params, &$payments)
{
	fn_lcjp_filter_payments($payments, 'digital_check_uid.tpl', 'digital_check_uid');
}




/**
 * ペイデザイン決済では注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 *  【解説】
 *  決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 *  注文ステータスを指定している。
 *  $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 *  関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 *  支払情報に強制的に書き込まれる。
 *  この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためペイデザイン決済
 *  では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 * @return bool
 */
function fn_digital_check_finish_payment(&$order_id, &$pp_response, &$force_notification)
{
	// 注文データ内の支払関連情報を取得
	$payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');


	// 注文データ内の支払関連情報が存在する場合
	if( !empty($payment_info) ){

		// 決済代行サービスのIDを取得
		$payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
		if( empty($payment_id) ) return false;
		$payment_method_data = fn_get_payment_method_data($payment_id);
		if( empty($payment_method_data) ) return false;
		$processor_id = $payment_method_data['processor_id'];
		if( empty($processor_id) ) return false;

		switch($processor_id){
			case '9080':
			case '9081':
			case '9082':
			case '9083':
			case '9084':
			case '9085':
				// 支払情報が暗号化されている場合は復号化して変数にセット
				if( !is_array($payment_info)) {
					$info = @unserialize(fn_decrypt_text($payment_info));
				}else{
					// 支払情報を変数にセット
					$info = $payment_info;
				}

				// 支払情報から注文ステータスに関する記述を削除
				unset($info['order_status']);

				// カード情報への登録有無に関する記述を削除
				unset($info['use_uid']);

				// 支払情報を暗号化
				$_data = fn_encrypt_text(serialize($info));

				// 注文データ内の支払関連情報を上書き
				db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);
				break;
			default:
				// do nothing
		}
	}
}
##########################################################################################
// END フックポイントで動作する関数
##########################################################################################





##########################################################################################
// START アドオンのインストール・アンインストール時に動作する関数
##########################################################################################
/**
 * アドオンのインストール時の処理
 */
function fn_digital_check_install()
{
    fn_lcjp_install('digital_check');
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################





##########################################################################################
// START アドオンの設定ページで動作する関数
##########################################################################################


/**
 * 注文金額と入金金額が相違した注文に割り当てる注文ステータスのリストを生成
 *
 * @return array
 */
function fn_settings_variants_addons_digital_check_pending_status()
{
	// 配列を初期化
	$variants = array();

	// 指定可能な注文ステータスを初期化
	$order_statuses = array();

	// 注文ステータスのコードと名称を取得
	$order_statuses = db_get_array("SELECT ?:statuses.status_id, ?:statuses.status, ?:status_descriptions.description FROM ?:statuses LEFT JOIN ?:status_descriptions ON ?:statuses.status_id = ?:status_descriptions.status_id WHERE ?:statuses.type = 'O' AND ?:status_descriptions.lang_code = ?s", DESCR_SL);

	// 在庫が減少する注文ステータスのみリストに表示する
	if($order_statuses){
		foreach($order_statuses as $order_status) {
			$inventory_setting = db_get_field("SELECT value FROM ?:status_data WHERE param = 'inventory' AND status_id = ?i", $order_status['status_id']);
			if($inventory_setting == 'D'){
				$variants[$order_status['status']] = $order_status['description'];
			}
		}
	}
	return $variants;
}
##########################################################################################
//  END  アドオンの設定ページで動作する関数
##########################################################################################





##########################################################################################
// START その他の関数
##########################################################################################

/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 各支払方法共通の送信パラメータをセット
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 * @return array
 */
function fn_dgtlchck_get_params($type, $order_id, $order_info, $processor_data)
{
	$params = array();

	// 加盟店コード
	$params['IP'] = $processor_data['processor_params']['ip'];

	// 取引コード
	$params['SID'] = fn_dgtlchck_get_sid($order_id);

	// 商品名1
	$params['N1'] = fn_dgtlchck_format_product_name($order_info);

	// 金額1
	$params['K1'] = round($order_info['total']);

	// 「ユーザーID決済」、「クレジットカード決済の決済要求」以外は「決済方法」「メールアドレス」をセット
	if( $type != 'uid' && $type != 'cc_2step_process' ){
		// メールアドレス
		$params['MAIL'] = fn_dgtlchck_get_email($type, $order_info);

		// 決済方法
		$params['STORE'] = fn_dgtlchck_get_store($type, $order_info);
	}

	// 支払方法別の送信パラメータをセット
	fn_dgtlchck_get_specific_params($params, $type, $order_id, $order_info, $processor_data);

	return $params;
}




/**
 * 16桁の取引コードを生成
 *
 * @param $order_id
 * @param string $mode
 * @return string
 */
function fn_dgtlchck_get_sid($order_id, $mode = 'create')
{
	// 取引コードを生成
	return sprintf("%06d", (int)$order_id) . (string)time();
}




/**
 * ペイデザイン側に送信する商品名をフォーマット
 * 購入商品が複数存在する場合は1つの商品のみ記載
 *
 * @param $order_info
 * @return string|void
 */
function fn_dgtlchck_format_product_name($order_info)
{
	$digital_check_product_name = '';
	$digital_check_etc = __('jp_digital_check_etc');

	// 商品データが存在する場合
	if (!empty($order_info['products'])) {
		$first_product_data = array_slice($order_info['products'], 0, 1);
		$product_info = reset($first_product_data);

		// 商品数が１つの場合
		if( count($order_info['products']) == 1 ){
			// 商品名を指定した長さのみ取得
			$digital_check_product_name = mb_strimwidth($product_info['product'], 0 , PAYDESIGN_MAXLEN_PNAME, '', 'UTF-8');

		// 商品数が２つ以上の場合は最初の商品名に「など」を追加する
		}elseif( count($order_info['products']) > 1 ){
			// 追記用文字の長さを取得
			$digital_check_etc_length = strlen($digital_check_etc);
			$digital_check_product_name_length = PAYDESIGN_MAXLEN_PNAME - $digital_check_etc_length;

			// 配列の最初の商品名に「など」を追記した文字列を取得
			$digital_check_product_name = mb_strimwidth($product_info['product'], 0 , $digital_check_product_name_length, '', 'UTF-8') . $digital_check_etc;
		}

	// 商品データがない場合は一律「お買い上げ商品」とする（通常発生しないが、念のため）
	}else{
		$digital_check_product_name = __('jp_digital_check_item_name');
	}

	return $digital_check_product_name;
}




/**
 * メールアドレスをセット
 *
 * @param $type
 * @param $order_info
 * @return mixed
 */
function fn_dgtlchck_get_email($type, $order_info)
{
	switch($type){
		case 'medy':	// MobileEdy
			return $order_info['payment_info']['medy_email'];
			break;
		default:		// その他の決済方法
			return $order_info['email'];
	}
}




/**
 * ペイデザインに送信する決済方法を取得
 *
 * @param $type
 * @param $order_info
 * @return bool|string
 */
function fn_dgtlchck_get_store($type, $order_info)
{
	switch($type){
		// カード決済
		case 'cc_2step_auth':
		case 'cc_link':
			return '51';
			break;

		// コンビニ決済
		case 'cvs':
			// コンビ二名に応じた決済方法をセット
			if( !empty($order_info['payment_info']['jp_digital_check_cvs_cnvkind']) ){
				return $order_info['payment_info']['jp_digital_check_cvs_cnvkind'];
			}else{
				return false;
			}
			break;

		// MobileEdy
		case 'medy':
			return '65';
			break;

		// CyberEdy
		case 'cedy':
			return '66';
			break;

		// その他
		default:
			// do nothing
	}
}




/**
 * 支払方法別の送信パラメータをセット
 *
 * @param $params
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 */
function fn_dgtlchck_get_specific_params(&$params, $type, $order_id, $order_info, $processor_data)
{
	switch($type){
		// クレジットカード決済申込要求
		case 'cc_2step_auth':

			// 決済確定
			$params['KAKUTEI'] = (int)$processor_data['processor_params']['with_capture'];

			// 会員登録済みユーザーによる注文で、ユーザーがユーザーID決済の利用を希望した場合
			if( !empty($order_info['user_id']) &&  $processor_data['processor_params']['use_uid'] == 'true' && $order_info['payment_info']['use_uid'] == 'true'){
				// ユーザーID
				$params['IP_USER_ID'] = Registry::get('addons.digital_check.uid_prefix') . $order_info['user_id'];

				// 付加情報
				$params['FUKA'] = $order_info['user_id'];
			}

			// 支払区分
			$params['PAYMODE'] = $order_info['payment_info']['jp_cc_method'];

			// 分割払いの場合
			if( $order_info['payment_info']['jp_cc_method'] == '61' ){
				// 分割回数
				$params['INCOUNT'] = $order_info['payment_info']['jp_cc_installment_times'];
			}

			// 完了URL（第4引数のデリミタを明示的に '&' で指定しないとSEOアドオン利用時に正しくリダイレクトできず404エラーになる）
			$params['OKURL'] = fn_url("payment_notification.process&payment=digital_check_cc_2step&order_id=$order_id", AREA, 'current', '&');

			// 戻りURL（第4引数のデリミタを明示的に '&' で指定しないとSEOアドオン利用時に正しくリダイレクトできず404エラーになる）a
			$params['RT'] = fn_url("payment_notification.cancelled&payment=digital_check_cc_2step&order_id=$order_id", AREA, 'current', '&');

			// 購入者漢字氏名（姓）
			$params['NAME1'] = fn_dgtlchck_format_name1($order_info);

			// 購入者漢字氏名（名）
			// ※ 姓名は1項目として「購入者漢字氏名（姓）」にまとめてセットするのでこの項目は全角スペースをセット
			$params['NAME2'] = "　";

			break;

		// クレジットカード決済（リンク方式）
		case 'cc_link':
			// 決済確定
			$params['KAKUTEI'] = (int)$processor_data['processor_params']['with_capture'];

			// 会員登録済みユーザーによる注文で、ユーザーがユーザーID決済の利用を希望した場合
			if( !empty($order_info['user_id']) &&  $processor_data['processor_params']['use_uid'] == 'true' && $order_info['payment_info']['use_uid'] == 'true'){
				// ユーザーID
				$params['IP_USER_ID'] = Registry::get('addons.digital_check.uid_prefix') . $order_info['user_id'];
				// 付加情報
				$params['FUKA'] = $order_info['user_id'];
			}

			// 現在の作業エリアを取得
			$area = AREA;

			// 完了URL（第4引数のデリミタを明示的に '&' で指定しないとSEOアドオン利用時に正しくリダイレクトできず404エラーになる）
			$params['OKURL'] = fn_url("payment_notification.process&payment=digital_check_link_cc&order_id=$order_id", AREA, 'current', '&');

			// 戻りURL（第4引数のデリミタを明示的に '&' で指定しないとSEOアドオン利用時に正しくリダイレクトできず404エラーになる）
			$params['RT'] = fn_url("payment_notification.cancelled&payment=digital_check_link_cc&order_id=$order_id", AREA, 'current', '&');

			// 購入者漢字氏名（姓）
			$params['NAME1'] = fn_dgtlchck_format_name1($order_info);

			// 購入者漢字氏名（名）
			// ※ 姓名は1項目として「購入者漢字氏名（姓）」にまとめてセットするのでこの項目は全角スペースをセット
			$params['NAME2'] = "　";

			break;

		// ユーザーID決済
		case 'uid':

			// 加盟店パスワード
			$params['PASS'] = $processor_data['processor_params']['ip_password'];

			// ユーザーID
			$params['IP_USER_ID'] = fn_dgtlchck_get_ip_user_id($order_info['user_id']);

			// 決済確定
            $params['KAKUTEI'] = (int)$processor_data['processor_params']['with_capture'];

			break;

		// コンビニ決済
		case 'cvs':

			// 購入者漢字氏名（姓）
			$params['NAME1'] = fn_dgtlchck_format_name1($order_info);

			// 購入者漢字氏名（名）
			// ※ 姓名は1項目として「購入者漢字氏名（姓）」にまとめてセットするのでこの項目は全角スペースをセット
			$params['NAME2'] = "　";

			// 購入者郵便番号1
			$params['YUBIN1'] = preg_replace("/[^0-9]+/", '', $order_info['b_zipcode']);

			// 顧客電話番号をフォーマット
			$tel = fn_dgtlchck_format_tel($order_info['phone']);

			// 電話番号のフォーマットが正しくない場合
			if(!$tel){
				// 注文処理ページへリダイレクト
				fn_set_notification('E', __('error'), __('jp_digital_check_tel_invalid'));
				$return_url = fn_lcjp_get_error_return_url();
				fn_redirect($return_url, true);
			// 電話番号のフォーマットが正しい場合
			}else{
				// 購入者電話番号
				$params['TEL'] = $tel;
			}

			// 購入者住所（ADR1、ADR2）をセット
			fn_dgtlchck_format_adr($params, $order_info);

			break;

		// CyberEdy決済
		case 'cedy':

			// 完了URL（第4引数のデリミタを明示的に '&' で指定しないとSEOアドオン利用時に正しくリダイレクトできず404エラーになる）
			$params['OKURL'] = fn_url("payment_notification.process&payment=digital_check_cedy&order_id=$order_id", AREA, 'current', '&');

			// 戻りURL（第4引数のデリミタを明示的に '&' で指定しないとSEOアドオン利用時に正しくリダイレクトできず404エラーになる）
			$params['RT'] = fn_url("payment_notification.cancelled&payment=digital_check_cedy&order_id=$order_id", AREA, 'current', '&');

			break;

		default:
			// do nothing
			break;
	}
}




/**
 * クレジットカード決済の決済要求送信パラメータをセット
 *
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 * @param $digital_check_results
 * @return array
 */
function fn_dgtlchck_get_params_cc_2step($order_id, $order_info, $processor_data, $digital_check_results)
{
	$params = array();

	// 決済要求コード
	$params['settle_req_crypt'] = $digital_check_results[3];

	// 決済要求シーケンス
	$params['settle_seq'] = $digital_check_results[4];

	// クレジットカード番号（数値以外の値は削除）
	$params['CARDNO'] = mb_ereg_replace('[^0-9]', '', $order_info['payment_info']['card_number']);

	// 有効期限
	$params['EXP'] = $order_info['payment_info']['expiry_year'] . $order_info['payment_info']['expiry_month'];

	// セキュリティコードによる認証を行う場合
	if( $processor_data['processor_params']['use_cvv'] == 'true' ){
		// セキュリティコード
		$params['csc'] = $order_info['payment_info']['cvv2'];

        // Twigmo3でセキュリティコードが入力された場合
        if($order_info['payment_info']['cvv_twg']){
            $params['csc'] = $order_info['payment_info']['cvv_twg'];
        }
	}

	return $params;
}




/**
 * 顧客名を生成
 *
 * @param $order_info
 * @return string
 */
function fn_dgtlchck_format_name1($order_info)
{
	$name1 = mb_convert_kana($order_info['b_firstname'] . '　' . $order_info['b_lastname'], 'RNASKV', 'UTF-8');
	$name1 = mb_strimwidth($name1, 0 , PAYDESIGN_MAXLEN_CNAME, '', 'UTF-8');

	return $name1;
}




/**
 * ペイデザインに送信する電話番号をフォーマット
 *
 * @param null $bill_phone
 * @return bool|mixed
 */
function fn_dgtlchck_format_tel($bill_phone = null)
{
	// CS-Cartに登録されている電話番号について、数値以外の値を取り除く
	$bill_phone_no = preg_replace("/[^0-9]+/", '', mb_convert_kana(mb_strimwidth($bill_phone, 0, 13, '', 'UTF-8'), "a", 'UTF-8'));

	// 数値以外の値を取り除いた電話番号の長さを取得
	$bill_phone_length = strlen($bill_phone_no);

	// 電話番号が9桁未満、もしくは12桁以上の場合、エラーを返す
	if( $bill_phone_length < 9 || $bill_phone_length > 11 ){
		return false;
	}

	return $bill_phone_no;
}




/**
 * 購入者住所をセット
 *
 * @param $params
 * @param $order_info
 */
function fn_dgtlchck_format_adr(&$params, $order_info)
{
	// 購入者住所1
	$adr_1 = mb_convert_kana($order_info['b_state'] . $order_info['b_city'], 'RNASKV', 'UTF-8');
	$adr_1 = mb_strimwidth($adr_1, 0 , PAYDESIGN_MAXLEN_ADDRESS, '', 'UTF-8');
	$params['ADR1'] = $adr_1;

	// 購入者住所2
	if( !empty($order_info['b_address']) ){
		$adr_2 =  mb_convert_kana($order_info['b_address'], 'RNASKV', 'UTF-8');

		if( !empty($order_info['b_address_2']) ){
			$adr_additonal = mb_convert_kana($order_info['b_address_2'], 'RNASKV', 'UTF-8');
		}else{
			$adr_additonal = '';
		}

		$adr_2 = mb_strimwidth($adr_2 . ' ' . $adr_additonal, 0 , PAYDESIGN_MAXLEN_ADDRESS, '', 'UTF-8');

		$params['ADR2'] = $adr_2;
	}
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $digital_check_results
 * @param bool|false $flg_comments
 * @return bool
 */
function fn_dgtlchck_format_payment_info($type, $order_id, $payment_info, $digital_check_results, $flg_comments = false)
{
    // 注文IDが存在しない場合は処理を終了
    if( empty($order_id) ) return false;

    // 処理対象となる注文ID群を取得
    $order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

    // 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
    foreach($order_ids_to_process as $order_id){

        // 支払情報がすでに存在する場合
        if( !empty($payment_info) ){
            // 支払情報が暗号化されている場合は復号化して変数にセット
            if( !is_array($payment_info)) {
                $info = @unserialize(fn_decrypt_text($payment_info));
            }else{
                // 支払情報を変数にセット
                $info = $payment_info;
            }
        }

        /////////////////////////////////////////////////////////
        // 追記用コメントの初期化 BOF
        /////////////////////////////////////////////////////////
        // 既存のコメントを取得
        $order_comments = db_get_field("SELECT notes FROM ?:orders WHERE order_id = ?i", $order_id);

        // 既存のコメントが存在する場合、改行を追加
        if($order_comments != ''){
            $order_comments .= "\n\n";
        }

        // 見出し
        $order_comments .= __('jp_digital_check_'. $type . '_info') . "\n";
        /////////////////////////////////////////////////////////
        // 追記用コメントの初期化 EOF
        /////////////////////////////////////////////////////////

        // 支払情報がすでに存在する場合
        if( !empty($info) ){
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 BOF
            ////////////////////////////////////////////////////////////////////
            foreach($info as $key => $val){
                switch($key){
                    ////////////////////////////////////////////////////////////////////
                    // クレジットカード決済 BOF
                    ////////////////////////////////////////////////////////////////////
                    // 支払方法はコードに対応した支払方法に変換
                    case "jp_cc_method":
                        switch($val){
                            // 一括
                            case 10:
                                $info[$key] = __('jp_cc_onetime');
                                break;
                            // ボーナス一括
                            case 21:
                                $info[$key] = __('jp_digital_check_cc_bonus');
                                break;
                            // ボーナス併用
                            case 31:
                                $info[$key] = __('jp_digital_check_cc_bonus_combination');
                                break;
                            // 分割
                            case 61:
                                $info[$key] = __('jp_cc_installment');
                                break;
                            // リボ払い
                            case 80:
                                $info[$key] = __('jp_cc_revo');
                                break;
                            default:
                                // do nothing
                                break;
                        }
                        break;

                    case "jp_cc_installment_times":
                        // 支払回数の末尾に「回」を追記
                        if( $info['jp_cc_method'] == 61 || $info['jp_cc_method'] == __('jp_cc_installment') ){
                            $info[$key] = $info[$key] . __('jp_paytimes_unit');
                        }else{
                            unset($info[$key]);
                        }
                        break;
                    ////////////////////////////////////////////////////////////////////
                    // クレジットカード決済 BOF
                    ////////////////////////////////////////////////////////////////////

                    ////////////////////////////////////////////////////////////////////
                    // コンビニ決済（受付番号） BOF
                    ////////////////////////////////////////////////////////////////////
                    // コンビニコードは対応したコンビ二名に変換
                    case "jp_digital_check_cvs_cnvkind":

                        // コンビニ名を取得
                        $cvs_info = fn_dgtlchck_get_cvs_info($val);

                        // コンビ二コードをコンビ二名に変換
                        if( !empty($cvs_info) ){
                            $info[$key] = $cvs_info['cvs_name'];
                            $order_comments .= __('jp_digital_check_cvs_cnvkind') . ' : ' . $cvs_info['cvs_name'] . "\n";
                        }
                        break;
                    ////////////////////////////////////////////////////////////////////
                    // コンビニ決済（受付番号） EOF
                    ////////////////////////////////////////////////////////////////////

                    // 一時的に保存されたカード番号などの情報はすべて削除
                    default:
                        unset($info[$key]);
                        break;
                }
            }
            ////////////////////////////////////////////////////////////////////
            // 必要に応じて既存の支払情報を変換 EOF
            ////////////////////////////////////////////////////////////////////
        }

        ////////////////////////////////////////////////////////////////////
        // 共通項目 BOF
        ////////////////////////////////////////////////////////////////////
        // 取引コード
        if( !empty($digital_check_results[1]) ){
            $info['jp_digital_check_sid'] = $digital_check_results[1];
        }
        ////////////////////////////////////////////////////////////////////
        // 共通項目 EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // コンビニ決済 BOF
        ////////////////////////////////////////////////////////////////////
        if( $type == 'cvs' ){
            // 支払情報
            if( !empty($cvs_info['payment_info']) && !empty($digital_check_results[3]) ){
                // ファミリーマートの場合
                if( $info['jp_digital_check_cvs_cnvkind'] == __('jp_cvs_fm') ){
                    $cvs_invoice_info = explode('-', $digital_check_results[3], 2);

                    // 企業コード
                    $info[$cvs_info['payment_info']] = $cvs_invoice_info[0];
                    $order_comments .= __($cvs_info['payment_info']) . ' : ' . $cvs_invoice_info[0] . "\n";

                    if( !empty($cvs_invoice_info[1]) ){
                        // 注文番号
                        $info['jp_digital_check_cvs_fm_order_no'] = $cvs_invoice_info[1];
                        $order_comments .= __('jp_digital_check_cvs_fm_order_no') . ' : ' . $cvs_invoice_info[1] . "\n";
                    }
                // その他のコンビニの場合
                }else{
                    // 支払情報
                    $info[$cvs_info['payment_info']] = $digital_check_results[3];
                    $order_comments .= __($cvs_info['payment_info']) . ' : ' . $digital_check_results[3] . "\n";
                }
            }

            // 払込票URL
            if( !empty($digital_check_results[6]) ){
                // 払込票URL
                $info['jp_cvs_url'] = $digital_check_results[6];
                $order_comments .= __('jp_cvs_url') . ' : ' . $digital_check_results[6] . "\n";
            }

            // 支払期限
            if( !empty($digital_check_results[4]) ){
                // 支払期限
                $info['jp_cvs_limit'] = $digital_check_results[4];
            }
        }
        ////////////////////////////////////////////////////////////////////
        // コンビニ決済 EOF
        ////////////////////////////////////////////////////////////////////

        // 支払情報を暗号化
        $_data = fn_encrypt_text(serialize($info));

        // 注文データ内の支払関連情報の有無をチェック
        $tmp_order_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

        // 注文データ内の支払関連情報が存在する場合
        if( !empty($tmp_order_id) ){
            // 注文データ内の支払関連情報を上書き
            db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);

        // 注文データ内の支払関連情報が存在しない場合
        }else{
            // 注文データ内の支払関連情報を追加
            $insert_data = array (
                'order_id' => $order_id,
                'type' => 'P',
                'data' => $_data,
            );
            db_query("REPLACE INTO ?:order_data ?e", $insert_data);
        }

        // 指定した場合のみコメント欄に支払情報を追記する
        if( $flg_comments ){
            $valid_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);

            // 正常なフローでの処理の場合のみ追記する
            if( !empty($valid_id) ){
                $data = array('notes' => $order_comments);
                db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $data, $order_id);
            }
        }
    }
}




/**
 * ペイデザインに各種データを送信
 *
 * @param $type
 * @param $params
 * @return array
 */
function fn_dgtlchck_send_request($type, $params)
{
	// PEAR拡張モジュールの読み込み。
	require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/pear/http/Request.php');

	switch($type){
		case 'cvs':		// コンビニ決済
		case 'medy':	// MobileEdy決済
			$target_url = 'https://www.paydesign.jp/settle/settle2/ubp3.dll';
			break;

		case 'cc_2step_auth':	// クレジットカード決済
			$target_url = 'https://www.paydesign.jp/settle/settle2/ubp25.dll';
			break;

		case 'uid':		// ユーザーID決済
			$target_url = 'https://www.paydesign.jp/settle/settlex/credit2.dll';
			break;

		default:
			// do nothing
			break;
	}

	// httpリクエスト用のオプションを指定
	$option = array("timeout" => "30"); // タイムアウトの秒数指定

	// HTTP_Requestの初期化
	$request = new HTTP_Request($target_url, $option);

	// リクエストメソッドをセット
  	$request->setMethod(HTTP_REQUEST_METHOD_POST);

	// ヘッダ情報をセット
	$request->addHeader("Content-Type", "application/x-www-form-urlencoded");

	// ペイデザインに送信するパラメータについて文字コードをSJISに変換
	$params = fn_dgtlchck_format_params($params);

	// 各パラメータをPOSTデータに追加
	foreach($params as $key => $val){
		$request->addPostData($key, $val);
	}

	// HTTPリクエスト実行
	$response = $request->sendRequest();

	return array('response' => $response, 'request' => $request);
}




/**
 * ペイデザインに送信するパラメータについて文字コードをSJISに変換する
 *
 * @param $params
 * @param string $type
 * @return array
 */
function fn_dgtlchck_format_params($params, $type = '')
{
	// ペイデザインに送信するパラメータが存在する場合
	if( !empty($params) && is_array($params) ){
		foreach($params as $key => $val){
			// 文字コードをSJISに変換
			$params[$key] = mb_convert_encoding($val, 'SJIS', 'UTF-8');
		}
	}

	return $params;
}




/**
 * ペイデザインからの戻り値を配列化
 *
 * @param $res_content
 * @return array|string
 */
function fn_dgtlchck_get_result_array($res_content)
{
	$digital_check_results = '';

	// 改行コードをセパレータとして戻り値を配列化
	if( !empty($res_content) ){
		$digital_check_results = explode("\n", $res_content);
		foreach($digital_check_results as $key => $val){
			$digital_check_results[$key] = trim($val);
		}
	}

	return $digital_check_results;
}




/**
 * 日付データを読みやすくフォーマット
 *
 * @param $date
 * @param string $time
 * @return bool|string
 */
function fn_dgtlchck_format_date($date, $time = '')
{
	$digital_check_date = '';

	$digital_check_date = date("Y/m/d H:i:s", strtotime((int)$date . $time));

	return $digital_check_date;
}




/**
 * コンビニ名と払出番号にひもづけられた名称を取得
 *
 * @param $cvs_id
 * @return array
 */
function fn_dgtlchck_get_cvs_info($cvs_id)
{
	$cvs_info = array();

	switch($cvs_id){
		// Loppi決済（ローソン・セイコーマート・ミニストップ）
		case 1:
			$cvs_info['cvs_name'] = __('jp_cvs_ls') . ' / ' . __('jp_cvs_sm') . ' / ' . __('jp_digital_check_cvs_ms');
			$cvs_info['payment_info'] = 'jp_cvs_receipt_no';
			break;

		// Seven決済（セブンイレブン）
		case 2:
			$cvs_info['cvs_name'] = __('jp_cvs_se');
			$cvs_info['payment_info'] = 'jp_digital_check_cvs_haraikomi_no';
			break;

		// FAMIMA決済（ファミリーマート）
		case 3:
			$cvs_info['cvs_name'] = __('jp_cvs_fm');
			$cvs_info['payment_info'] = 'jp_cvs_company_code';
			break;

		// オンライン決済（サークルKサンクス / デイリーヤマザキ / ヤマザキデイリー / スリーエフ）
		case 73:
			$cvs_info['cvs_name'] = __('jp_cvs_ck') . ' / ' . __('jp_cvs_ts') . ' / ' . __('jp_cvs_dy') . ' / ' . __('jp_cvs_yd') . ' / ' . __('jp_digital_check_cvs_3f');
			$cvs_info['payment_info'] = 'jp_digital_check_cvs_online_kessai_no';
			break;

		default:
			// do nothing
			break;
	}

	return $cvs_info;
}




/**
 * 入金通知データのバリデーション
 *
 * @param $request
 * @return bool
 */
function fn_dgtlchck_validate_notification($request)
{
	$is_valid = true;

	// 必要なパラメータが存在しない場合はエラー
	if( empty($request['SEQ']) || empty($request['DATE']) || empty($request['TIME']) || empty($request['SID']) || empty($request['KINGAKU']) || empty($request['CVS']) ){
		$is_valid = false;
	}

	return $is_valid;
}




/**
 * 取引コードからCS-Cartの注文IDを取得
 *
 * @param $sid
 * @return int|string
 */
function fn_dgtlchck_get_order_id($sid)
{
	// 取引コードの先頭6桁を抽出（末尾10桁はUNIXタイムスタンプのため除外）
	$sid = substr($sid, 0, 6);

	// 取引コードを整数化（プレースホルダとして付与された 0 を削除）
	$sid = (int)$sid;

	return $sid;
}




/**
 * ペイデザインから取得したエラーメッセージをUTF-8エンコーディング
 *
 * @param string $msg
 * @return bool|string
 */
function fn_dgtlchck_encode_err_msg($msg = '')
{
	if( !empty($msg) ){
		return mb_convert_encoding($msg, 'UTF-8', 'SJIS');
	}else{
		return false;
	}
}
/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// ユーザーID決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 登録済みのペイデザインユーザーIDを取得
 *
 * @param $user_id
 * @return array
 */
function fn_dgtlchck_get_ip_user_id($user_id)
{
	// ユーザーID決済に関するデータを取得
	$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'digital_check_uid.php' AND ?:payments.status = 'A'");
	$processor_data = fn_get_processor_data($payment_id);

	// ユーザーIDを取得
	$ip_user_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'digital_check_uid');

	return $ip_user_id;
}




/**
 * 登録済みのペイデザインユーザーIDを削除
 *
 * @param $user_id
 */
function fn_dgtlchck_delete_card_info($user_id)
{
	// 登録済みカード決済用レコードを削除
	db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'digital_check_uid');

	fn_set_notification('N', __('notice'), __('jp_digital_check_uid_delete_success'));
}




/**
 * クレジットカード情報を登録
 *
 * @param $user_id
 */
function fn_dgtlchck_register_cc_info($user_id)
{
	$_data = array('user_id' => $user_id,
				'payment_method' => 'digital_check_uid',
				'quickpay_id' => Registry::get('addons.digital_check.uid_prefix') . $user_id,
				);
	db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);
}
/////////////////////////////////////////////////////////////////////////////////////
//  ユーザーID決済 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// Twigmo用関数 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * カード決済で利用可能な支払区分を取得
 *
 * @return array
 */
function fn_dgtlchck_tw_get_cc_methods()
{
    // ペイデザイン（カード決済）の設定データを取得
    $_payment_id = db_get_field("SELECT payment_id FROM ?:payments WHERE template = ?s", 'views/orders/components/payments/digital_check_cc_2step.tpl');
    $_payment_data = fn_get_payment_method_data($_payment_id);

    $variants = array();

    // １回払い
    if( $_payment_data['processor_params']['paymode'][10] == 'true' ){
        $variants[] = array (
            'variant_id' => 10,
            'variant_name' => 10,
            'description' => __('jp_cc_onetime')
        );
    }
    // 分割払い
    // 【メモ】TwigmoではオリジナルJavascriptを使用してonClickイベントで支払回数欄の表示・非表示ができない
    // そのため利用可能なすべての分割回数をリストに表示する
    if( $_payment_data['processor_params']['paymode'][61] == 'true' && !empty($_payment_data['processor_params']['incount']) ){
        foreach( $_payment_data['processor_params']['incount'] as $incount_key => $incount ){
            if($incount == 'true'){
                $variants[] = array (
                    'variant_id' => $incount_key,
                    'variant_name' => $incount_key,
                    'description' => __('jp_cc_installment') . '(' . $incount_key . __('jp_paytimes_unit') . ')'
                );
            }
        }
    }
    // ボーナス一括払い
    if( $_payment_data['processor_params']['paymode'][21] == 'true' ){
        $variants[] = array (
            'variant_id' => 21,
            'variant_name' => 21,
            'description' => __('jp_cc_bonus_onetime')
        );
    }
    // ボーナス併用払い
    if( $_payment_data['processor_params']['paymode'][31] == 'true' ){
        $variants[] = array (
            'variant_id' => 31,
            'variant_name' => 31,
            'description' => __('jp_digital_check_cc_bonus_combination')
        );
    }
    // リボ払い
    if( $_payment_data['processor_params']['paymode'][80] == 'true' ){
        $variants[] = array (
            'variant_id' => 80,
            'variant_name' => 80,
            'description' => __('jp_cc_revo')
        );
    }

    return $variants;
}




/**
 * カード決済でカード情報の登録有無を確認するリストを取得
 *
 * @return array
 */
function fn_dgtlchck_tw_confirm_card_register()
{
    $variants[] = array (
        'variant_id' => 'register_yes',
        'variant_name' => 'true',
        'description' => __('yes'),
    );

    $variants[] = array (
        'variant_id' => 'register_no',
        'variant_name' => 'false',
        'description' => __('no'),
    );

    return $variants;
}




/**
 * コンビニ決済で利用可能なコンビ二名を取得
 *
 * @return array
 */
function fn_dgtlchck_tw_get_cvs_list()
{
    // ペイデザイン（コンビニ決済）の設定データを取得
    $_payment_id = db_get_field("SELECT payment_id FROM ?:payments WHERE template = ?s", 'views/orders/components/payments/digital_check_cvs.tpl');
    $_payment_data = fn_get_payment_method_data($_payment_id);

    $variants = array();

    if( $_payment_data['processor_params']['cnvkind']['1'] == 'true' ){
        $variants[] = array (
            'variant_id' => '1',
            'variant_name' => '1',
            'description' => __('jp_cvs_ls') . ' / ' . __('jp_cvs_sm') . ' / ' . __('jp_cvs_ms')
        );
    }
    if( $_payment_data['processor_params']['cnvkind']['2'] == 'true' ){
        $variants[] = array (
            'variant_id' => '2',
            'variant_name' => '2',
            'description' => __('jp_cvs_se')
        );
    }
    if( $_payment_data['processor_params']['cnvkind']['3'] == 'true' ){
        $variants[] = array (
            'variant_id' => '3',
            'variant_name' => '3',
            'description' => __('jp_cvs_fm')
        );
    }
    if( $_payment_data['processor_params']['cnvkind']['73'] == 'true' ){
        $variants[] = array (
            'variant_id' => '73',
            'variant_name' => '73',
            'description' => __('jp_cvs_ck') . ' / ' . __('jp_cvs_ts') . ' / ' . __('jp_cvs_dy') . ' / ' . __('jp_cvs_yd') . ' / ' . __('jp_digital_check_cvs_3f')
        );
    }

    return $variants;
}
/////////////////////////////////////////////////////////////////////////////////////
// Twigmo用関数 EOF
/////////////////////////////////////////////////////////////////////////////////////

##########################################################################################
// END その他の関数
##########################################################################################
