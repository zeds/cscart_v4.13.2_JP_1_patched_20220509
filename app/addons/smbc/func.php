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

// $Id: func.php by tommy from cs-cart.jp 2017
//
// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_smbc_[フックポイント名]
// (2) (1)以外の関数：fn_smbcks_[任意の名称]
//
// 2017/09 : トークン決済（シングルユース方式）に対応

use Tygh\Registry;

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

/**
 * クレジットカード情報を登録済みの会員に対してのみ登録済みカード決済を表示
 *
 * @param $params
 * @param $payments
 */
function fn_smbc_get_payments_post(&$params, &$payments)
{
	fn_lcjp_filter_payments($payments, 'smbc_ccreg.tpl', 'smbc_ccreg');
}




/**
 * クレジット請求管理ページにソート順「請求ステータス」を追加
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $get_totals
 * @param $lang_code
 */
function fn_smbc_pre_get_orders(&$params, &$fields, &$sortings, &$get_totals, &$lang_code)
{
	// クレジット請求管理ページにソート順「請求ステータス」を追加
	if( Registry::get('runtime.controller') == 'smbc_cc_manager' && Registry::get('runtime.mode') == 'manage'){
		$sortings['cc_status'] = "?:jp_smbc_cc_status.status_code";
	}
}




/**
 * クレジット請求管理ページおける注文情報の抽出・表示
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_smbc_get_orders(&$params, &$fields, &$sortings, &$condition, &$join, &$group)
{
	// クレジット請求管理ページの場合
	if( Registry::get('runtime.controller') == 'smbc_cc_manager' && Registry::get('runtime.mode') == 'manage'){
		// カード決済および登録済カードにより支払われた注文のみ抽出
		$processor_ids = array(9040, 9044, 9046);
		$smbc_cc_payments = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (?a)", $processor_ids);
		$smbc_cc_payments = implode(',', $smbc_cc_payments);
		$condition .= " AND ?:orders.payment_id IN ($smbc_cc_payments)";

		// 各注文にひもづけられたクレジット請求ステータスコードを抽出
		$fields[] = "?:jp_smbc_cc_status.status_code as cc_status_code";
		$join .= " LEFT JOIN ?:jp_smbc_cc_status ON ?:jp_smbc_cc_status.order_id = ?:orders.order_id";
	}
}




/**
 * 注文情報削除時にクレジット決済の請求ステータスを削除
 *
 * @param $order_id
 */
function fn_smbc_delete_order(&$order_id)
{
	db_query("DELETE FROM ?:jp_smbc_cc_status WHERE order_id = ?i", $order_id);
}




/**
 * CS-Cartマルチ決済では注文時に最初に割り当てられた注文ステータスの情報を支払情報から削除する
 * 【解説】
 * 決済代行サービスを利用した注文の場合、$pp_response["order_status"] にて注文後に割り当てる
 * 注文ステータスを指定している。
 * $pp_response["order_status"] が指定されている場合、関数「fn_finish_payment」にて呼び出される
 * 関数「fn_update_order_payment_info」により、注文時に最初に割り当てられた注文ステータスが
 * 支払情報に強制的に書き込まれる。
 * この情報は後から注文ステータスを変更しても書き換わらないため、混乱を避けるためCS-Cartマルチ決済
 * では注文完了時に支払情報から注文ステータスに関する記述を削除する。
 *
 * @param $order_id
 * @param $pp_response
 * @param $force_notification
 */
function fn_smbc_finish_payment(&$order_id, &$pp_response, &$force_notification)
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
			case '9040':
			case '9041':
			case '9042':
			case '9043':
			case '9044':
			case '9045':
            case '9046':
            case '9047':
				// 支払情報が暗号化されている場合は復号化して変数にセット
				if( !is_array($payment_info)) {
					$info = @unserialize(fn_decrypt_text($payment_info));
				}else{
					// 支払情報を変数にセット
					$info = $payment_info;
				}

				// 支払情報から注文ステータスに関する記述を削除
				unset($info['order_status']);

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




/**
 * 商品の継続課金に関する情報の保存
 *
 * @param $product_data
 * @param $product_id
 * @param $lang_code
 * @param $create
 * @return bool
 */
function fn_smbc_update_product_post(&$product_data, &$product_id, &$lang_code, &$create)
{
	// この処理を入れないと商品情報の一括編集時に値がリセットされてしまう。
	if (!isset($product_data['smbc_rb_duration_type'])) {
		return false;
	}

	// 継続課金の設定値を格納する配列を初期化
	$subscription_data = array();

	// 請求金額（初回）の設定値を格納する配列を初期化 
	$first_payment_data = array();

	// 請求金額（初回）の適用有無
	$is_first_payment_enabled = $product_data['smbc_rb_enable_1st_payment'];

	// 請求金額（初回）の金額
	$first_payment_amount = $product_data['smbc_rb_1st_payment_amount'];

	// 課金開始タイミング
	$charge_timing = $product_data['smbc_rb_charge_timing'];

	// 請求方法
	$duration_type = $product_data['smbc_rb_duration_type'];

	// 継続課金の設定値をセット
	$subscription_data = array(
							'product_id' => (int)$product_id,
							'charge_timing' => $charge_timing,
							'duration_type' => $duration_type
							);

	// 請求金額（初回）を適用する場合
	if( $is_first_payment_enabled == 'Y' ){
		// 請求金額（初回）の設定値をセット
		$first_payment_data = array('enable_1st_payment' => $is_first_payment_enabled,
									'first_payment_amount' => $first_payment_amount);

		// 配列をマージ
		$subscription_data = array_merge($subscription_data, $first_payment_data);
	}

	// CS-Cartマルチ決済継続課金の設定値をDBに書き込み
	db_query("REPLACE INTO ?:jp_smbc_rb_products ?e", $subscription_data);

	return true;
}




/**
 * 商品を削除した際にその商品の継続課金に関する情報も削除する
 *
 * @param $product_id
 */
function fn_smbc_delete_product_post(&$product_id)
{
	db_query("DELETE FROM ?:jp_smbc_rb_products WHERE product_id = ?i", $product_id);
}




/**
 * SMBCカード決済または登録済みカード決済については、減額処理以外は注文編集を許可しない
 *
 * @param $total
 * @param $cart
 */
function fn_smbc_allow_place_order(&$total, &$cart)
{
    // 注文編集以外の場合は処理を終了
	if(AREA != 'A' || Registry::get('runtime.controller') != 'order_management' || Registry::get('runtime.mode') != 'place_order' ||  Registry::get('runtime.action') == 'save' ) return true;

    // 処理に必要なパラメータが存在しない場合は処理を終了
    if( empty($cart['payment_id']) || empty($cart['order_id']) || empty($cart['total']) ) return true;

    // SMBCカード決済または登録済みカード決済以外の決済方法の場合は処理を終了
    $chg_payment_method_data = fn_get_payment_method_data($cart['payment_id']);
    $chg_processor_id = $chg_payment_method_data['processor_id'];
    if($chg_processor_id != '9040' && $chg_processor_id != '9044' && $chg_processor_id != '9046') return true;

    // 注文編集のの実施可否フラグを初期化
    $flg_changeable = false;

    // 注文IDを取得
    $order_id = $cart['order_id'];

    // 子注文の存在有無をチェック
    $is_parent_order = db_get_field("SELECT is_parent_order FROM ?:orders WHERE order_id = ?i", $order_id);

    // 子注文が存在しない場合
    if($is_parent_order == 'N'){
        // 与信済金額を取得
        $org_amount = fn_smbcks_get_auth_amount($order_id);

        // 与信済金額が存在する場合
        if( !empty($org_amount) ){
            // 変更後の金額
            $chg_amount = (int)$cart['total'];

            // 編集後の注文金額が編集前よりも小さい（減額処理）場合
            if($org_amount > $chg_amount){
                // 編集前の注文で利用された決済方法を取得
                $org_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
                $org_payment_method_data = fn_get_payment_method_data($org_payment_id);
                $org_processor_id = $org_payment_method_data['processor_id'];

                // 編集前後で同じ決済方法が選択されている場合
                if( !empty($org_processor_id) && ($org_processor_id == $chg_processor_id) ){

                    // 注文データからクレジット請求ステータスを取得
                    $cc_status = db_get_field("SELECT status_code FROM ?:jp_smbc_cc_status WHERE order_id = ?i", $order_id);

                    // ステータスコードが存在する場合
                    if( !empty($cc_status) ){
                        // 特定のステータスコードを持つ注文のみ利用額変更処理を許可
                        switch($cc_status){
                            case 'AUTH_OK':             // 与信
                            case 'SALES_CONFIRMED':    // 売上確定
                                $flg_changeable = true;
                                break;
                            default:
                                // do nothing;
                        }
                    }
                }
            }
        }
    }

    // 注文編集のの実施可否フラグがfalseの場合
    if(!$flg_changeable){
        // エラーメッセージを表示して注文情報の更新を中断
        fn_set_notification('E', __('error'), __('jp_smbc_cc_order_not_saved'));
        $cart['shipping_failed'] = true;
    }
}

/**
 * 言語変数とトークン決済用のprocessor_idを追加してメッセージを出す
 *
 * @param $user_data
 */
function fn_smbc_set_admin_notification(&$user_data)
{
    // トークン決済用のprocessor_idが存在するか確認する
    $tokenId =  db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'smbc_cctkn.php'");
    $tokenId_rb =  db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'smbc_rbtkn.php'");

    // トークン決済用のprocessor_idが存在しない場合
    if(empty($tokenId) || empty($tokenId_rb)){
        try {
            // インストール済みの言語を取得
            $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');

            // 言語変数の追加
            $lang_variables = array(
                array('name' => 'jp_smbc_api_key', 'value' => 'トークン変換APIキー'),
                array('name' => 'jp_smbc_token_enabled', 'value' => 'CS-Cartマルチ決済 (SMBCファイナンスサービス) において<br />トークンを利用したクレジットカード決済、カード継続課金がご利用いただけるようになりました。'),
                array('name' => 'error_validator_cc_check_length_jp', 'value' => 'カード番号が正しくありません'),
                array('name' => 'error_validator_cc_exp_jp', 'value' => '有効期限が不正です'),
            );

            foreach ($languages as $lc => $_v) {
                foreach ($lang_variables as $k1 => $v1) {
                    if (!empty($v1['name'])) {
                        preg_match("/(^[a-zA-z0-9][a-zA-Z0-9_]*)/", $v1['name'], $matches);
                        if (strlen($matches[0]) == strlen($v1['name'])) {
                            $v1['lang_code'] = $lc;
                            db_query("REPLACE INTO ?:language_values ?e", $v1);
                        }
                    }
                }
            }
            // トークン決済用のprocessor_idを追加
            db_query("REPLACE INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9046, 'SMBCファイナンスサービス（カード決済・トークン決済）', 'smbc_cctkn.php', 'views/orders/components/payments/smbc_cctkn.tpl', 'smbc_cctkn.tpl', 'N', 'P')");
            db_query("REPLACE INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9047, 'SMBCファイナンスサービス（カード継続課金・トークン決済）', 'smbc_rbtkn.php', 'views/orders/components/payments/smbc_rbtkn.tpl', 'smbc_rbtkn.tpl', 'N', 'P')");

            // トークン決済利用可能のメッセージを表示
            fn_set_notification('I', __('notice'), __('jp_smbc_token_enabled'));
        }
        catch (Exception $e){
            // エラー発生(Service Unavailableメッセージを出さない)
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
function fn_smbc_install()
{
    fn_set_notification('E', __('error'), 'CS-Cartマルチ決済 (SMBCファイナンスサービス)アドオンは、SMBC様新規受付中止のためインストールできません。');
    fn_uninstall_addon('smbc', false);
    fn_redirect('addons.manage', true);

    fn_lcjp_install('smbc');
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
function fn_settings_variants_addons_smbc_pending_status() {

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
 *
 * @param $type
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 * @return array
 */
function fn_smbcks_get_params($type, $order_id, $order_info, $processor_data)
{
	$params = array();

	// バージョン
	$params['version'] = fn_smbcks_get_version($type);

	// 契約コード
	$params['shop_cd'] = ($type == 'rb') ? Registry::get('addons.smbc.rb_shop_cd') : Registry::get('addons.smbc.shop_cd');

	// 収納企業コード
	$params['syuno_co_cd'] = ($type == 'rb') ? Registry::get('addons.smbc.rb_syuno_co_cd') : Registry::get('addons.smbc.syuno_co_cd');

	// 拠点コード
	$kyoten_cd = ($type == 'rb') ? Registry::get('addons.smbc.rb_kyoten_cd') : Registry::get('addons.smbc.kyoten_cd');
	if( !empty($kyoten_cd) ){
		$params['kyoten_cd'] = Registry::get('addons.smbc.kyoten_cd');
	}

	// ショップパスワード
	$params['shop_pwd'] = ($type == 'rb') ? Registry::get('addons.smbc.rb_shop_pwd') : Registry::get('addons.smbc.shop_pwd');

	// 請求番号（契約補助番号）
	$params['shoporder_no'] = fn_smbcks_get_shoporder_no($order_id);

	// 会員登録済みユーザーによる注文の場合
	if( !empty($order_info['user_id']) ){
		// 顧客番号
		$params['bill_no'] = fn_smbcks_get_bill_no($order_info['user_id']);
	// 継続課金商品をゲスト購入した場合
	}elseif( $type == 'rb' ){
		// 現在時刻（マイクロ秒）を整数化したものを一意の顧客番号とする
		$params['bill_no'] = sprintf("%014d", str_replace('.', '', (string)microtime(true)));
	}

	// 顧客名
	$params['bill_name'] = fn_smbcks_format_bill_name($order_info);

	// 顧客カナ名
	$params['bill_kana'] = fn_smbcks_format_bill_kana($order_info);

	// クレジットカード決済以外の場合
	if( $type != 'cc' && $type != 'ccreg' && $type != 'rb' ){
		// 顧客郵便番号
		$params['bill_zip'] =  preg_replace("/[^0-9]+/", '', $order_info['b_zipcode']);

		// 顧客住所
		fn_smbcks_format_bill_adr($params, $order_info);
	}

	// 顧客電話番号
	$bill_phone = fn_smbcks_format_bill_phone($order_info['phone']);

	// 電話番号のフォーマットが正しくない場合
	if(!$bill_phone){
		// 注文処理ページへリダイレクト
		fn_set_notification('E', __('error'), __('jp_smbc_tel_invalid'));
		$return_url = fn_lcjp_get_error_return_url();
		fn_redirect($return_url, true);
	// 電話番号のフォーマットが正しい場合
	}else{
		// 顧客電話番号
		$params['bill_phon'] = $bill_phone;
	}

	// 顧客メールアドレス
	$params['bill_mail'] = $order_info['email'];

	// 顧客メールアドレス区分
	$params['bill_mail_kbn'] = fn_smbcks_is_mobile_email($order_info['email']);

	// クレジットカード継続課金以外の場合
	if( $type != 'rb' ){

		// 請求金額
		$params['seikyuu_kingaku'] = round($order_info['total']);

		// 内消費税
		$params['shouhi_tax'] = fn_smbcks_calc_shouhi_tax($order_info);

		// 成約日
		$params['seiyaku_date'] = date('Ymd');

		// 商品１の商品名
		$params['goods_name_1'] = fn_smbcks_format_product_name($order_info);

		// 商品１の単価
		$params['unit_price_1'] = round($order_info['total']);

		// 商品１の数量
		$params['quantity_1'] = '1';

		// 請求内容（漢字）
		$params['seikyuu_name'] = '購入代金';

		// 請求内容（カナ）
		$params['seikyuu_kana'] = 'コウニュウダイキン';
	}

	// 支払方法別の送信パラメータをセット
	fn_smbcks_get_specific_params($params, $type, $order_id, $order_info, $processor_data);

	return $params;
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
function fn_smbcks_get_specific_params(&$params, $type, $order_id, $order_info, $processor_data)
{
	switch($type){
		// クレジットカード決済
		case 'cc':
		case 'ccreg':

			// 決済手段区分
			$params['bill_method'] = '05';

			// 決済種類コード
			$params['kessai_id'] = '0501';

			// 通常のクレジットカード決済の場合
			if( $type == 'cc' ){
			    if(!empty($processor_data['processor_params']['api_key'])) {
                    // トークン（シングルユース方式）
                    $params['token_single'] = $order_info['payment_info']['token_single'];
                }
                else {
                    // クレジットカード番号（数値以外の値は削除）
                    $card_number = mb_ereg_replace('[^0-9]', '', $order_info['payment_info']['card_number']);
                    $params['card_no'] = $card_number;

                    // クレジットカード有効期限
                    $params['card_yukokigen'] = $order_info['payment_info']['expiry_month'] . $order_info['payment_info']['expiry_year'];
                }
			}

			// クレジットカード支払区分
			if( $order_info['payment_info']['jp_cc_method'] == '61' ){
				$params['shiharai_kbn'] = $order_info['payment_info']['jp_cc_installment_times'];
			}else{
				$params['shiharai_kbn'] = $order_info['payment_info']['jp_cc_method'];
			}

			// セキュリティコードによる認証を行う場合
			if( $processor_data['processor_params']['use_cvv'] == 'true' ){
				// セキュリティコード
				$params['security_cd'] = $order_info['payment_info']['cvv2'];

                // Twigmo3でセキュリティコードが入力された場合
                if($order_info['payment_info']['cvv_twg']){
                    $params['security_cd'] = $order_info['payment_info']['cvv_twg'];
                }
			}
			break;

		// クレジットカード継続課金
		case 'rb':

			// 決済手段区分
			$params['bill_method'] = '05';

			// 決済種類コード
			$params['kessai_id'] = '0501';

            if(!empty($processor_data['processor_params']['rb_api_key'])) {
                // トークン（シングルユース方式）
                $params['token_single'] = $order_info['payment_info']['token_single'];
            }
            else {
                // クレジットカード番号（数値以外の値は削除）
                $card_number = mb_ereg_replace('[^0-9]', '', $order_info['payment_info']['card_number']);
                $params['card_no'] = $card_number;

                // クレジットカード有効期限
                $params['card_yukokigen'] = $order_info['payment_info']['expiry_month'] . $order_info['payment_info']['expiry_year'];
            }

			// カート内商品の最初の1つを取得（継続課金はカート内に1商品しか投入できない）
			$product_info = reset(array_slice($order_info['products'], 0, 1));

			$rb_product_info = fn_smbcks_get_rb_product_info($product_info['product_id']);

			// 請求開始年月
			$params['seikyuu_kaishi_ym'] = fn_smbcks_get_seikyuu_kaishi_ym($product_info['product_id']);

			// 請求終了年月
			$params['seikyuu_shuryo_ym'] = '999912';

			// 請求金額（初回）
			if( fn_is_1st_payment_enabled($product_info['product_id']) ){
				$params['seikyuu_kingaku1'] = (int)$rb_product_info['first_payment_amount'];
			}

			// 請求金額（2回目以降）
			$params['seikyuu_kingaku2'] = round($order_info['total']);

			// 請求方法
			$params['seikyuu_hoho'] = (int)$rb_product_info['duration_type'];

			break;

		// コンビニ決済（受付番号）
		case 'cvs':

			// 決済手段区分
			$params['bill_method'] = '03';

			// 決済種類コード
			$params['kessai_id'] = $order_info['payment_info']['jp_smbc_cvs_cnvkind'];

			break;

		// 銀行振込
		case 'bnk':

			// 決済手段区分
			$params['bill_method'] = '06';

			// 決済種類コード
			$params['kessai_id'] = '0601';

			break;

		// 払込票
		case 'ps':

			// 決済手段区分
			$params['bill_method'] = '20';

			// 決済種類コード
			$params['kessai_id'] = '2001';

			// 利用年月
			$params['riyou_nengetsu'] = date('Ym');

			// 請求年月
			$params['seikyuu_nengetsu'] = date('Ym');

			// 発行区分
			$params['hakkou_kbn'] = $processor_data['processor_params']['hakkou_kbn'];

			// 払込票を代行発行する場合
			if( $params['hakkou_kbn'] == '2' ){
				// 郵送先区分
				$params['yuusousaki_kbn'] = $processor_data['processor_params']['yuusousaki_kbn'];
			}
			break;

		default:
			// do nothing
			break;
	}
}




/**
 * 3Dセキュア用送信パラメータをセット
 *
 * @param $request
 * @return array
 */
function fn_smbcks_get_3dsecure_params($request)
{
	// SMBCファイナンスサービスに送信するパラメータをセット
	$params = array();
	// バージョン
	$params['version'] = fn_smbcks_get_version('3dsecure');
	// 決済手段区分
	$params['bill_method'] = '05';
	// 決済種類コード
	$params['kessai_id'] = '0501';
	// 契約コード
	$params['shop_cd'] = Registry::get('addons.smbc.shop_cd');
	// 収納企業コード
	$params['syuno_co_cd'] = Registry::get('addons.smbc.syuno_co_cd');
	// 拠点コード
	$kyoten_cd = Registry::get('addons.smbc.kyoten_cd');
	if( !empty($kyoten_cd) ){
		$params['kyoten_cd'] = Registry::get('addons.smbc.kyoten_cd');
	}
	// ショップパスワード
	$params['shop_pwd'] = Registry::get('addons.smbc.shop_pwd');
	// 請求番号
	$params['shoporder_no'] = $request['MD'];
	// セッションID
	$params['sessionid'] = $request['smbcsess'];
	// PaRes
	$params['pares'] = $request['PaRes'];

	return $params;
}




/**
 * DBに保管する支払情報をフォーマット
 *
 * @param $type
 * @param $order_id
 * @param $payment_info
 * @param $smbc_results
 * @param bool $flg_comments
 */
function fn_smbcks_format_payment_info($type, $order_id, $payment_info, $smbc_results, $flg_comments = false)
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
        $order_comments .= __('jp_smbc_'. $type . '_info') . "\n";
        /////////////////////////////////////////////////////////
        // 追記用コメントの初期化 EOF
        /////////////////////////////////////////////////////////

        // クレジットカード決済における分割払い判定フラグを初期化
        $flg_cc_installment = false;

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
                            case 1:
                                $info[$key] = __('jp_cc_onetime');
                                break;
                            // 分割
                            case 61:
                                $info[$key] = __('jp_cc_installment');
                                $flg_cc_installment = true;
                                break;
                            // リボ払い
                            case 80:
                                $info[$key] = __('jp_cc_revo');
                                break;
                            // ボーナス一括
                            case 91:
                                $info[$key] = __('jp_cc_bonus_onetime');
                                break;
                            // Twigmo経由でのカード分割払い
                            case 2:
                            case 3:
                            case 5:
                            case 6:
                            case 10:
                            case 12:
                            case 15:
                            case 18:
                            case 20:
                            case 24:
                                $info[$key] = __('jp_cc_installment') . '(' . $val . __('jp_paytimes_unit') . ')';
                                break;
                            default:
                                // do nothing
                                break;
                        }
                        break;

                    case "jp_cc_installment_times":
                        // 支払回数には末尾に「回」を追記
                        if( $info['jp_cc_method'] == 61 || $flg_cc_installment ){
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
                    case "jp_smbc_cvs_cnvkind":

                        // コンビニ名および払出関連情報を取得
                        $cvs_info = fn_smbcks_get_haraidashi_info($val);

                        // コンビ二名
                        if( !empty($cvs_info['kessai_name']) ){
                            $info[$key] = $cvs_info['kessai_name'];
                            $order_comments .= __('jp_smbc_cvs_cnvkind') . ' : ' . $info[$key] . "\n";
                        }

                        // 払出関連情報
                        if( is_array($cvs_info['haraidashi_info']) ){
                            foreach($cvs_info['haraidashi_info'] as $key => $val){
                                $info[$val] = $smbc_results["haraidashi_no$key"];
                                $order_comments .= __($val) . ' : ' . $info[$val] . "\n";
                            }
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
        // 請求番号
        if( !empty($smbc_results['shoporder_no']) ){
            $info['jp_smbc_shoporder_no'] = $smbc_results['shoporder_no'];
        }

        // 決済受付番号
        if( !empty($smbc_results['kessai_no']) ){
            $info['jp_smbc_kessai_no'] = $smbc_results['kessai_no'];
        }

        // 決済受付日時
        if( !empty($smbc_results['kessai_date']) && !empty($smbc_results['kessai_time']) ){
            $info['jp_smbc_kessai_time'] = fn_smbcks_format_date($smbc_results['kessai_date'], $smbc_results['kessai_time']);
        }

        // 支払期限日
        if( !empty($smbc_results['shiharai_date']) ){
            $info['jp_cvs_limit'] = fn_smbcks_format_date($smbc_results['shiharai_date']);
            $order_comments .= __('jp_cvs_limit') . ' : ' . $info['jp_cvs_limit'] . "\n";
        }
        ////////////////////////////////////////////////////////////////////
        // 共通項目 EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // クレジットカード BOF
        ////////////////////////////////////////////////////////////////////
        if( $type == 'cc' ){
            // オーソリが正常に完了した場合
            if( strcmp($smbc_results['rescd'], '000000') === 0){
                // オーソリ金額
                $total = db_get_field("SELECT total FROM ?:orders WHERE ?:orders.order_id = ?i", $order_id);
                $info['jp_smbc_cc_auth_amount'] = round($total);
            }
        }

        // クレジットカード会社名
        if( !empty($smbc_results['hishimuke_kaishacd']) ){
            $info['jp_smbc_cc_company'] = fn_smbcks_get_cc_company($smbc_results['hishimuke_kaishacd']);
        }

        // オーソリ処理日
        if( !empty($smbc_results['yoshin_jikko_date']) ){
            $info['jp_smbc_cc_yoshin_jikko_date'] = fn_smbcks_format_date($smbc_results['yoshin_jikko_date']);
        }

        // クレジットカード会社の承認番号
        if( !empty($smbc_results['shonin_no']) ){
            $info['jp_smbc_cc_shonin_no'] = $smbc_results['shonin_no'];
        }
        ////////////////////////////////////////////////////////////////////
        // クレジットカード EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // クレジットカード（継続課金） BOF
        ////////////////////////////////////////////////////////////////////
        if( $type == 'rb' ){
            // オーソリが正常に完了した場合
            if( strcmp($smbc_results['rescd'], '000000') === 0){
                // オーソリ金額
                $total = db_get_field("SELECT total FROM ?:orders WHERE ?:orders.order_id = ?i", $order_id);
                $info['jp_smbc_cc_auth_amount'] = round($total);
            }
        }
        ////////////////////////////////////////////////////////////////////
        // クレジットカード（継続課金） EOF
        ////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////
        // 銀行振込 BOF
        ////////////////////////////////////////////////////////////////////
        // 銀行名
        if( !empty($smbc_results['bank_name']) ){
            $info['jp_smbc_bnk_name'] = $smbc_results['bank_name'];
            $order_comments .= __('jp_smbc_bnk_name') . ' : ' . $info['jp_smbc_bnk_name'] . "\n";
        }

        // 支店名
        if( !empty($smbc_results['branch_name']) ){
            $info['jp_smbc_bnk_branch'] = $smbc_results['branch_name'];
            $order_comments .= __('jp_smbc_bnk_branch') . ' : ' . $info['jp_smbc_bnk_branch'] . "\n";
        }

        // 口座種別
        if( !empty($smbc_results['kouza_shubetsu']) ){
            if( $smbc_results['kouza_shubetsu'] == '1' ){
                $bnk_account_type = __('jp_smbc_bnk_account_saving');
            }else{
                $bnk_account_type = __('jp_smbc_bnk_account_checking');
            }

            $info['jp_smbc_bnk_account_type'] = $bnk_account_type;
            $order_comments .= __('jp_smbc_bnk_account_type') . ' : ' . $info['jp_smbc_bnk_account_type'] . "\n";
        }

        // 口座番号
        if( !empty($smbc_results['kouza_no']) ){
            $info['jp_smbc_bnk_account_no'] = $smbc_results['kouza_no'];
            $order_comments .= __('jp_smbc_bnk_account_no') . ' : ' . $info['jp_smbc_bnk_account_no'] . "\n";
        }

        // 口座名義人
        if( !empty($smbc_results['kouza_name']) ){
            $info['jp_smbc_bnk_account_name'] = mb_convert_kana($smbc_results['kouza_name'], 'RNASKV', 'UTF-8');
            $order_comments .= __('jp_smbc_bnk_account_name') . ' : ' . $info['jp_smbc_bnk_account_name'] . "\n";
        }
        ////////////////////////////////////////////////////////////////////
        // 銀行振込 EOF
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
 * カード決済エラー時に、注文データに格納されていた支払情報のうち、SMBCカード決済
 * に関するデータのみ残す
 *
 * @param $order_id
 * @param array $payment_info
 * @param $results
 * @return bool
 */
function fn_smbcks_filter_payment_info($order_id)
{
	// 注文IDが存在しない場合は処理を終了
	if (empty($order_id)) return false;

	// 処理対象となる注文ID群を取得
	$order_ids_to_process = fn_lcjp_get_order_ids_to_process($order_id);

	// 処理対象となる注文ID群を格納する配列にセットされたすべての注文に対して処理を実施
	foreach($order_ids_to_process as $order_id){
		// 注文データ内の支払関連情報を取得
		$payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

		// 支払情報が存在する場合
		if (!empty($payment_info)) {
			// 支払情報が暗号化されている場合は復号化して変数にセット
			if (!is_array($payment_info)) {
				$info = @unserialize(fn_decrypt_text($payment_info));
			} else {
				// 支払情報を変数にセット
				$info = $payment_info;
			}

			// 支払情報が存在する場合
			if (!empty($info)) {
				////////////////////////////////////////////////////////////////////
				// 必要に応じて既存の支払情報を変換 BOF
				////////////////////////////////////////////////////////////////////
				foreach ($info as $key => $val) {
					switch ($key) {
						// カード決済に関する情報のみ保持
						case 'jp_cc_method':
						case 'jp_cc_installment_times':
						case 'jp_smbc_shoporder_no':
						case 'jp_smbc_kessai_no':
						case 'jp_smbc_kessai_time':
						case 'jp_smbc_cc_auth_amount':
						case 'jp_smbc_cc_company':
						case 'jp_smbc_cc_yoshin_jikko_date':
						case 'jp_smbc_cc_shonin_no':
						case 'jp_smbc_cc_status':
							// do nothing
							break;

						// 一時的に保存されたカード番号などの情報はすべて削除
						default:
							unset($info[$key]);
							break;
					}
				}
				////////////////////////////////////////////////////////////////////
				// 必要に応じて既存の支払情報を変換 EOF
				////////////////////////////////////////////////////////////////////

				// 支払情報を暗号化
				$_data = fn_encrypt_text(serialize($info));

				// 注文データ内の支払関連情報を上書き
				db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);
			}
		}
	}
}




/**
 * SMBCファイナンスサービスにリクエストを送信
 *
 * @param $params
 * @param $processor_data
 * @param string $action
 * @return array
 */
function fn_smbcks_send_request($params, $processor_data, $action = 'checkout')
{
	// PEAR拡張モジュールの読み込み。
	require_once(Registry::get('config.dir.addons') . 'localization_jp/lib/pear/http/Request.php');

	switch($action){
		// 決済手続き
		case 'checkout':
			// 本番環境の場合
			if( $processor_data['processor_params']['mode'] == 'live' ){
				$target_url = 'https://www.paymentstation.jp/cooperation/sf/at/ksuketsukeinforeg/uketsukeInfoRegInit.do';
			//  テスト環境の場合
			}else{
				$target_url = 'https://www.paymentstation.jp/cooperationtest/sf/at/ksuketsukeinforeg/uketsukeInfoRegInit.do';
			}
			break;

		// 決済手続き（継続課金）
		case 'rb':
			// 本番環境の場合
			if( $processor_data['processor_params']['mode'] == 'live' ){
				$target_url = 'https://www.paymentstation.jp/cooperation/sf/cd/skuinfokt/skuinfoKakutei.do';
			//  テスト環境の場合
			}else{
				$target_url = 'https://www.paymentstation.jp/cooperationtest/sf/cd/skuinfokt/skuinfoKakutei.do';
			}
			break;

		// カード情報お預かり機能
		case 'ccreg':
			// 本番環境の場合
			if( $processor_data['processor_params']['mode'] == 'live' ){
				$target_url = 'https://www.paymentstation.jp/cooperation/sf/at/idkanri/begin.do';
			//  テスト環境の場合
			}else{
				$target_url = 'https://www.paymentstation.jp/cooperationtest/sf/at/idkanri/begin.do';
			}
			break;

		// 3Dセキュア決済時のSMBCファイナンスサービスへの送信
		case '3dsecure':
			// 本番環境の場合
			if( $processor_data['processor_params']['mode'] == 'live' ){
				$target_url = 'https://www.paymentstation.jp/cooperation/sf/cd/creditinfo/threeDResultReg.do';
			//  テスト環境の場合
			}else{
				$target_url = 'https://www.paymentstation.jp/cooperationtest/sf/cd/creditinfo/threeDResultReg.do';
			}
			break;

		// クレジット請求管理
		case 'cc_manage':
			// 本番環境の場合
			if( $processor_data['processor_params']['mode'] == 'live' ){
				$target_url = 'https://www.paymentstation.jp/cooperation/sf/cd/skuinfokt/skuinfoKakutei.do';
			//  テスト環境の場合
			}else{
				$target_url = 'https://www.paymentstation.jp/cooperationtest/sf/cd/skuinfokt/skuinfoKakutei.do';
			}
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

	// 文字コードをShift_JISに変換してPOSTデータに追加
	foreach($params as $key => $val){
        // 機種依存文字をJIS基本漢字に変換
        $val = fn_lcjp_replace_pdc($val);
		$request->addPostData($key, mb_convert_encoding($val, 'SJIS', 'UTF-8'));
	}

	// HTTPリクエスト実行
	$response = $request->sendRequest();

	return array('response' => $response, 'request' => $request);
}




/**
 * SMBCファイナンスサービスに送信するバージョン情報を取得
 *
 * @param $type
 * @return string
 */
function fn_smbcks_get_version($type)
{
    switch($type){
        // 3Dセキュア認証後
        case '3dsecure':
            return  '211';
            break;

        // カード決済請求確定
        case 'cc_sales_confirm':
            return '212';
            break;

        // 与信取り消し
        case 'cc_auth_cancel':
            return '215';
            break;

        // 減額（与信一部取消）
        case 'cc_change':
            return '216';
            break;

        // 減額（売上一部取消）
        case 'cc_change_sales':
            return '218';
            break;

        // 売上取り消し
        case 'cc_sales_cancel':
            return '217';
            break;

        // 継続課金
        case 'rb':
            return '240';
            break;

        // その他
        default:
            return '210';
    }
}




/**
 * メールアドレスの種類（PC/携帯）を判定
 *
 * @param $email
 * @return int
 */
function fn_smbcks_is_mobile_email($email)
{
	// 携帯電話向けEメールアドレスの場合
	if( preg_match("/@(docomo|softbank|disney|ezweb|[dhtkrsnqc]\.vodafone|pdx|d[kij]\.pdx|wm\.pdx)\.ne\.jp$/i", $email) ||
		preg_match("/@(jp-[dhtkrsnqc]\.ne\.jp|i\.softbank\.jp|willcom\.com|[a-z]+\.biz\.ezweb\.ne\.jp)$/i", $email) ){
      	return 1;

	// PC向けEメールアドレスの場合
	}else {
		return 0;
	}
}




/**
 * 17桁の請求番号を抽出・生成
 *
 * @param $order_id
 * @param string $mode
 * @return bool|string
 */
function fn_smbcks_get_shoporder_no($order_id, $mode = 'create')
{
	// 請求番号を抽出する場合
	if($mode == 'retriev'){

		$payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);

		if( !empty($payment_info) ){
			// 支払情報が暗号化されている場合は復号化して変数にセット
			if( !is_array($payment_info)) {
				$info = @unserialize(fn_decrypt_text($payment_info));
			}else{
				// 支払情報を変数にセット
				$info = $payment_info;
			}
			// 請求番号が存在する場合
			if( !empty($info['jp_smbc_shoporder_no']) ){
				// 請求番号を返す
				return $info['jp_smbc_shoporder_no'];
			}
		}
		return false;

	// 請求番号を生成する場合
	}else{
		// 請求番号を生成
		return sprintf("%07d", (int)$order_id) . (string)time();
	}
}




/**
 * 14桁の顧客番号を生成
 *
 * @param $user_id
 * @return string
 */
function fn_smbcks_get_bill_no($user_id)
{
	return sprintf("%014d", $user_id);
}




/**
 * 顧客名を生成
 *
 * @param $order_info
 * @return string
 */
function fn_smbcks_format_bill_name($order_info)
{
	$bill_name = mb_convert_kana($order_info['b_firstname'] . '　' . $order_info['b_lastname'], 'RNASKV', 'UTF-8');
	$bill_name = mb_strimwidth($bill_name, 0 , SMBC_MAXLEN_CNAME, '', 'UTF-8');

	return $bill_name;
}




/**
 * 顧客カナ名を生成する
 *
 * @param $order_info
 * @return string
 */
function fn_smbcks_format_bill_kana($order_info)
{
	$bill_kana = '';

	// 顧客カナ名のフィールド番号を取得
	$familyname_kana_b = (int)Registry::get('addons.localization_jp.jp_familyname_kana_b');
	$firstname_kana_b = (int)Registry::get('addons.localization_jp.jp_firstname_kana_b');

	if( !empty($familyname_kana_b) && !empty($firstname_kana_b) ){
		// 顧客カナ名
		$bill_kana = mb_convert_kana($order_info['fields'][$familyname_kana_b] . $order_info['fields'][$firstname_kana_b], 'rnaskh', 'UTF-8');
		$bill_kana = mb_strimwidth($bill_kana, 0 , SMBC_MAXLEN_CKANA, '', 'UTF-8');
	}

	return $bill_kana;
}




/**
 * 顧客住所をセット
 *
 * @param $params
 * @param $order_info
 */
function fn_smbcks_format_bill_adr(&$params, $order_info)
{
	// 顧客住所1
	$bill_adr_1 =  mb_convert_kana($order_info['b_state'] . $order_info['b_city'], 'RNASKV', 'UTF-8');
	$bill_adr_1 = mb_strimwidth($bill_adr_1, 0 , SMBC_MAXLEN_ADDRESS, '', 'UTF-8');
	$params['bill_adr_1'] = $bill_adr_1;

	// 顧客住所2
	if( !empty($order_info['b_address']) ){
		$bill_adr_2 =  mb_convert_kana($order_info['b_address'], 'RNASKV', 'UTF-8');
		$bill_adr_2 = mb_strimwidth($bill_adr_2, 0 , SMBC_MAXLEN_ADDRESS, '', 'UTF-8');
		$params['bill_adr_2'] = $bill_adr_2;
	}

	// 顧客住所3
	if( !empty($order_info['b_address_2']) ){
		$bill_adr_3 =  mb_convert_kana($order_info['b_address_2'], 'RNASKV', 'UTF-8');
		$bill_adr_3 = mb_strimwidth($bill_adr_3, 0 , SMBC_MAXLEN_ADDRESS, '', 'UTF-8');
		$params['bill_adr_3'] = $bill_adr_3;
	}
}




/**
 * 消費税額を算出
 *
 * @param $order_info
 * @return float
 */
function fn_smbcks_calc_shouhi_tax($order_info)
{
	$shouhi_tax = 0;

	if( !empty($order_info['taxes']) ){

		foreach($order_info['taxes'] as $tax){
			if( !empty($tax['tax_subtotal']) ){
				$shouhi_tax += $tax['tax_subtotal'];
			}
		}
	}

	// CS-Cartで税金が設定されていない場合には自動で消費税額を計算する
	if( empty($shouhi_tax) ){
		$shouhi_tax = $order_info['total'] * ( SMBC_VAT_RATE / (100 + SMBC_VAT_RATE) );
	}

	return round($shouhi_tax);
}




/**
 * SMBCファイナンスサービスからの戻り値を配列
 *
 * @param $res_content
 * @return string
 */
function fn_smbcks_get_result_array($res_content)
{
	$smbc_results = array();

	// 改行コードをセパレータとして戻り値を配列化
	$result_array_brs = explode("\n", $res_content);

	// 等号をセパレータとして戻り値を配列化
	foreach( $result_array_brs as $result_array_br ){
		if(!empty($result_array_br)){
			$result_array_eq = explode("=", $result_array_br, 2);
			$smbc_results[$result_array_eq[0]] = trim(mb_convert_encoding($result_array_eq[1], 'UTF-8', 'SJIS'));
		}
	}

	return $smbc_results;
}




/**
 * SMBCファイナンスサービス側に送信する商品名をフォーマット
 * 購入商品が複数存在する場合は1つの商品のみ記載
 *
 * @param $order_info
 * @return string
 */
function fn_smbcks_format_product_name($order_info)
{
	$smbc_product_name = '';
	$smbc_etc = __('jp_smbc_etc');

	// 商品データが存在する場合
	if (!empty($order_info['products'])) {

		$product_info = reset(array_slice($order_info['products'], 0, 1));
		// 商品名をすべて全角に変換
		$_product_name = mb_convert_kana($product_info['product'], 'ASK', 'UTF-8');

		// 商品数が１つの場合
		if( count($order_info['products']) == 1 ){
			// 商品名を指定した長さのみ取得
			$smbc_product_name = mb_strcut($_product_name, 0, SMBC_MAXLEN_PNAME, 'UTF-8');

		// 商品数が２つ以上の場合は最初の商品名に「など」を追加する
		}elseif( count($order_info['products']) > 1 ){
			// 追記用文字の長さを取得
			$smbc_etc_length = strlen($smbc_etc);
			$smbc_product_name_length = SMBC_MAXLEN_PNAME - $smbc_etc_length;

			// 配列の最初の商品名に「など」を追記した文字列を取得
			$smbc_product_name = mb_strcut($_product_name, 0, $smbc_product_name_length, 'UTF-8') . $smbc_etc;
		}

	// 商品データがない場合は一律「お買い上げ商品」とする（通常発生しないが、念のため）
	}else{
		$smbc_product_name = __('jp_smbc_item_name');
	}

	return $smbc_product_name;
}




/**
 * SMBCファイナンスサービスに送信する電話番号をフォーマット
 *
 * @param null $bill_phone
 * @return bool|mixed
 */
function fn_smbcks_format_bill_phone($bill_phone = null)
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
 * 日付データを読みやすくフォーマット
 *
 * @param $date
 * @param string $time
 * @return bool|string
 */
function fn_smbcks_format_date($date, $time = '')
{
	if( !empty($time) ){
        $time = sprintf('%06d', (int)$time);
		$smbc_date = date("Y/m/d H:i", strtotime((int)$date . $time));
	}else{
		$smbc_date = date("Y/m/d ", strtotime((int)$date));
	}

	return $smbc_date;
}




/**
 * 決済種類コードに応じて決済種類名と払出番号にひもづけられた名称を取得
 *
 * @param $kessai_id
 * @return array
 */
function fn_smbcks_get_haraidashi_info($kessai_id)
{
	$haraidashi_info = array();

	switch($kessai_id){
		// セブンイレブン
		case '0301':
			$haraidashi_info['kessai_name'] = __('jp_cvs_se');
			$haraidashi_info['haraidashi_info'][1] = 'jp_smbc_cvs_haraikomi_no';
			$haraidashi_info['haraidashi_info'][2] = 'jp_cvs_url';
			break;
		// ローソン/ミニストップ
		case '0302':
			$haraidashi_info['kessai_name'] = __('jp_cvs_ls') . ' / ' . __('jp_cvs_ms');
			$haraidashi_info['haraidashi_info'][1] = 'jp_cvs_receipt_no';
			break;
		// セイコーマート
		case '0303':
			$haraidashi_info['kessai_name'] = __('jp_cvs_sm');
			$haraidashi_info['haraidashi_info'][1] = 'jp_cvs_receipt_no';
			break;
		// ファミリーマート
		case '0304':
			$haraidashi_info['kessai_name'] = __('jp_cvs_fm');
			$haraidashi_info['haraidashi_info'][1] = 'jp_cvs_company_code';
			$haraidashi_info['haraidashi_info'][2] = 'order_id';
			break;
		// サークルK/サンクス
		case '0305':
			$haraidashi_info['kessai_name'] = __('jp_cvs_ck') . ' / ' . __('jp_cvs_ts');
			$haraidashi_info['haraidashi_info'][1] = 'jp_smbc_cvs_online_kessai_no';
			break;
		default:
			// do nothing
			break;
	}

	return $haraidashi_info;
}




/**
 * 入金通知データのバリデーション
 *
 * @param $request
 * @return bool
 */
function fn_smbcks_validate_notification($request)
{
	$is_valid = true;

	// バージョンが 310（入金通知）以外はエラー
	if( empty($request['version']) || $request['version'] != '310' ) $is_valid = false;

	// 契約コードが一致しない場合はエラー
	if( empty($request['shop_cd']) || $request['shop_cd'] != Registry::get('addons.smbc.shop_cd') ) $is_valid = false;

	// 収納企業コードが一致しない場合はエラー
	if( empty($request['syuno_co_cd']) || $request['syuno_co_cd'] != Registry::get('addons.smbc.syuno_co_cd') ) $is_valid = false;

	// 請求番号がセットされていない場合はエラー
	if( empty($request['shoporder_no']) ) $is_valid = false;

	// 結果コードが正常終了以外の場合はエラー
	if( empty($request['rescd']) ) $is_valid = false;

	return $is_valid;
}




/**
 * 請求番号からCS-Cartの注文IDを取得
 *
 * @param $shoporder_no
 * @return int|string
 */
function fn_smbcks_get_order_id($shoporder_no)
{
	// 請求番号の先頭7桁を抽出（末尾10桁はUNIXタイムスタンプのため除外）
	$shoporder_no = substr($shoporder_no, 0, 7); 

	// 請求番号を整数化（プレースホルダとして付与された 0 を削除）
	$shoporder_no = (int)$shoporder_no;

	return $shoporder_no;
}




/**
 * カード決済および登録済みカード決済の決済ステータス（または減額処理後の与信金額）をDBに保存
 *
 * @param $order_id
 * @param $status_code
 * @param string $type
 */
function fn_smbcks_update_cc_status_code($order_id, $status_code, $type = '')
{
    //////////////////////////////////////////////////////////////////////
    // カード決済および登録済みカード決済の決済ステータスをDBに保存 BOF
    //////////////////////////////////////////////////////////////////////
    $_data = array (
        'order_id' => $order_id,
        'status_code' => $status_code,
    );
    db_query("REPLACE INTO ?:jp_smbc_cc_status ?e", $_data);
    //////////////////////////////////////////////////////////////////////
    // カード決済および登録済みカード決済の決済ステータスをDBに保存 EOF
    //////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////
    // クレジット請求ステータスを注文詳細に表示 BOF
    //////////////////////////////////////////////////////////////////////
    // 注文データ内の支払関連情報を取得
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

    // 注文データ内に支払関連情報が存在する場合
    if( !empty($payment_info) ){
        $flg_payment_info_exists = true;

        // 支払情報が暗号化されている場合は復号化して変数にセット
        if( !is_array($payment_info)) {
            $info = @unserialize(fn_decrypt_text($payment_info));
        }else{
            // 支払情報を変数にセット
            $info = $payment_info;
        }

        // 注文データ内の支払関連情報が存在しない場合
    }else{
        $flg_payment_info_exists = false;
        $info = array();
    }

    $info['jp_smbc_cc_status'] = fn_smbcks_get_cc_status_name($status_code);

    // 減額処理（与信一部取消または売上一部取消）の場合
    if($type == 'cc_change' || $type == 'cc_change_sales'){
        // 減額後の与信金額を注文情報に記録
        $order_total = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);
        $info['jp_smbc_cc_auth_amount'] = (int)$order_total;
    }

    // 支払情報を暗号化
    $_data = fn_encrypt_text(serialize($info));

    // 注文データ内の支払関連情報が存在する場合
    if( $flg_payment_info_exists ){
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
    //////////////////////////////////////////////////////////////////////
    // クレジット請求ステータスを注文詳細に表示 EOF
    //////////////////////////////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////
// 各支払方法で共通の処理 EOF
/////////////////////////////////////////////////////////////////////////////////////




/////////////////////////////////////////////////////////////////////////////////////
// クレジットカード決済 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * テストモードで運用中にガラケー経由でクレジットカード決済が選択された場合のバリデーション
 *
 * @param $card_number
 * @return bool
 */
function fn_smbcks_cc_validate_test_cc(&$card_number) 
{
	// カード番号が入力されていない場合、エラーとみなす
	if( empty($card_number) ) return false;

	// 入力されたカード番号から数値以外の値を削除
	$card_number = mb_ereg_replace("[^0-9]", '', $card_number);

	// カード番号に数値が入力されていない場合、エラーとみなす
	if( strlen($card_number) == 0 ) return false;

	return true;
}




/**
 * クレジットカードお預かり機能で送信するパラメータをセット
 *
 * @param $action
 * @param $kmt_kok_id
 * @param string $kessai_no
 * @return array
 */
function fn_smbcks_get_ccreg_params($action, $kmt_kok_id, $kessai_no = '')
{
	$params = array();

	// バージョン
	$params['version'] = '214';

	// 契約コード
	$params['shop_cd'] = Registry::get('addons.smbc.shop_cd');

	// 収納企業コード
	$params['syuno_co_cd'] = Registry::get('addons.smbc.syuno_co_cd');

	// 拠点コード
	$kyoten_cd = Registry::get('addons.smbc.kyoten_cd');
	if( !empty($kyoten_cd) ){
		$params['kyoten_cd'] = Registry::get('addons.smbc.kyoten_cd');
	}

	// 認証パスワード
	$params['auth_pwd'] = Registry::get('addons.smbc.auth_pwd');

	// 加盟店顧客ID
	$params['kmt_kok_id'] = $kmt_kok_id;

	switch($action){
		// 登録（更新）
		case 'register':
			// 処理区分
			$params['shori_kbn'] = '01';

			// 決済手段区分
			$params['bill_method'] = '05';

			// 識別番号
			$params['shikibetsu_no'] = '1';

			// 決済受付番号
			$params['kessai_no'] = $kessai_no;
			break;

		// 照会
		case 'check':
			// 処理区分
			$params['shori_kbn'] = '02';
			break;

		// 削除
		case 'delete':
			// 処理区分
			$params['shori_kbn'] = '03';
			break;

		default:
			// do nothing
			break;
	}

	return $params;
}




/**
 * クレジット会社名を取得
 *
 * @param $hishimuke_kaishacd
 * @return string
 */
function fn_smbcks_get_cc_company($hishimuke_kaishacd)
{
	switch($hishimuke_kaishacd){
		case '2S100350000':
			return 'クレディセゾン';
			break;
		case '2s500010000':
			return '三菱ＵＦＪニコス（旧ニコス）';
			break;
		case '2s585880000':
			return 'セディナ(旧セントラルファイナンス)';
			break;
		case '2s591100000':
			return 'ジャックス';
			break;
		case '2s598800000':
			return 'オリコ';
			break;
		case '2s598800000':
			return 'ポケットカード';
			break;
		case '2s598800000':
			return 'UCS';
			break;
		case '2S630460000':
			return 'イオンクレジット';
			break;
		case '2s773340000':
			return 'トヨタファイナンス';
			break;
		case '2a996600000':
			return 'シティカードジャパン（ダイナース）';
			break;
		case '2a996610000':
			return 'JCB';
			break;
		case '2a996620000':
			return '三菱UFJニコス（旧DC）';
			break;
		case '2a996630000':
			return '三井住友カード';
			break;
		case '2a996640000':
			return '三菱UFJニコス（旧UFJ）';
			break;
		case '15250  0000':
			return 'UCカード';
			break;
		case '2s598750000':
			return '楽天KC';
			break;
		case '29598760000':
			return 'ライフ';
			break;
		case '2s596810000':
			return 'アプラス';
			break;
		case '2s631410000':
			return 'セディナ（旧OMC）';
			break;
		default:
			return 'N/A';
			break;
	}

	return 'N/A';
}




/**
 * 登録済みクレジットカード情報の取得
 *
 * @param $user_id
 * @return array|bool
 */
function fn_smbcks_get_registered_card_info($user_id)
{
	if( empty($user_id) ) return false;

	$registered_card = false;

	// 支払方法に関するデータを取得
	$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'smbc_ccreg.php' AND ?:payments.status = 'A'");
	$processor_data = fn_get_processor_data($payment_id);

	// 加盟店顧客IDを取得
	$kmt_kok_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'smbc_ccreg');

	if( !empty($kmt_kok_id) ){
		$ccreg_params = fn_smbcks_get_ccreg_params('check', $kmt_kok_id);

		// 登録済みクレジットカード情報の照会
		$ccreg_return_val = fn_smbcks_send_request($ccreg_params, $processor_data, 'ccreg');
		$ccreg_response = $ccreg_return_val['response'];
		$ccreg_request = $ccreg_return_val['request'];

		// リクエスト送信が正常終了した場合
		if (!PEAR::isError($ccreg_response)) {

			// 応答内容の解析  
			$ccreg_res_code = $ccreg_request->getResponseCode();
			$ccreg_res_content = $ccreg_request->getResponseBody();

			// SMBCファイナンスサービスから受信した登録済クレジットカード情報を配列に格納
			$smbc_ccreg_results = fn_smbcks_get_result_array($ccreg_res_content);

			// 登録済みクレジットカード情報の照会でエラーが発生している場合
			if( strcmp($smbc_ccreg_results['rescd'], '000000') !== 0 ){
				// エラーメッセージを表示
				fn_set_notification('E', __('jp_smbc_ccreg_error'), __('jp_smbc_ccreg_register_failed') . '<br />' . $smbc_ccreg_results['rescd'] . ' : ' . $smbc_ccreg_results['res']);

				// CS-CartのDB上から登録済みカード決済用レコードを削除
				db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'smbc_ccreg');

			// 登録済みクレジットカード情報の照会が正常に終了した場合
			}else{
				if( !empty($smbc_ccreg_results['crd_cnt']) ){
					$registered_card = array('card_number' => $smbc_ccreg_results['crd_info_b_1'],
											'card_valid_term' => $smbc_ccreg_results['crd_info_c_1']
											);

				// CS-CartのDBにだけクレジットカード情報が登録されている場合
				}else{
					// CS-CartのDB上から登録済みカード決済用レコードを削除
					db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'smbc_ccreg');
				}
			}
		}
	}

	return $registered_card;

}




/**
 * 登録済みクレジットカード情報の削除
 *
 * @param $user_id
 */
function fn_smbcks_delete_card_info($user_id)
{
	// 支払方法に関するデータを取得
	$payment_id = db_get_field("SELECT ?:payments.payment_id FROM ?:payments LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payment_processors.processor_script = 'smbc_ccreg.php' AND ?:payments.status = 'A'");
	$processor_data = fn_get_processor_data($payment_id);

	// 加盟店顧客IDを取得
	$kmt_kok_id = db_get_field("SELECT quickpay_id FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'smbc_ccreg');

	// 加盟店顧客IDが存在する場合
	if( !empty($kmt_kok_id) ){
		$ccreg_params = fn_smbcks_get_ccreg_params('delete', $kmt_kok_id);

		// 登録済みクレジットカード情報の削除
		$ccreg_return_val = fn_smbcks_send_request($ccreg_params, $processor_data, 'ccreg');
		$ccreg_response = $ccreg_return_val['response'];
		$ccreg_request = $ccreg_return_val['request'];

		// リクエスト送信が正常終了した場合
		if (!PEAR::isError($ccreg_response)) {

			// 応答内容の解析  
			$ccreg_res_code = $ccreg_request->getResponseCode();
			$ccreg_res_content = $ccreg_request->getResponseBody();

			// SMBCファイナンスサービスから受信したクレジットカード削除情報を配列に格納
			$smbc_ccreg_results = fn_smbcks_get_result_array($ccreg_res_content);

			// 登録済みクレジットカード情報の削除でエラーが発生している場合
			if( strcmp($smbc_ccreg_results['rescd'], '000000') !== 0 ){
				// エラーメッセージを表示
				fn_set_notification('E', __('jp_smbc_ccreg_delete_error'), __('jp_smbc_ccreg_delete_failed') . '<br />' . $smbc_ccreg_results['rescd'] . ' : ' . $smbc_ccreg_results['res']);

			// 登録済みクレジットカード情報が削除できた場合
			}else{
				fn_set_notification('N', __('notice'), __('jp_smbc_ccreg_delete_success'));
			}
		}

		// CS-CartのDB上からも登録済みカード決済用レコードを削除
		db_query("DELETE FROM ?:jp_cc_quickpay WHERE user_id = ?i AND payment_method = ?s", $user_id, 'smbc_ccreg');

	// 加盟店顧客IDが存在しない場合
	}else{
		// エラーメッセージを表示
		fn_set_notification('E', __('jp_smbc_ccreg_delete_error'), __('jp_smbc_ccreg_delete_failed'));
	}

}




/**
 * クレジットカード情報を登録
 *
 * @param $user_id
 * @param $processor_data
 * @param $kessai_no
 */
function fn_smbcks_register_cc_info($user_id, $processor_data, $kessai_no)
{
	$ccreg_params = fn_smbcks_get_ccreg_params('register', fn_smbcks_get_bill_no($user_id), $kessai_no);

	// クレジットカード情報お預かり
	$ccreg_return_val = fn_smbcks_send_request($ccreg_params, $processor_data, 'ccreg');
	$ccreg_response = $ccreg_return_val['response'];
	$ccreg_request = $ccreg_return_val['request'];

	// リクエスト送信が正常終了した場合
	if (!PEAR::isError($ccreg_response)) {

		// 応答内容の解析  
		$ccreg_res_code = $ccreg_request->getResponseCode();
		$ccreg_res_content = $ccreg_request->getResponseBody();

		// SMBCファイナンスサービスから受信した請求情報を配列に格納
		$smbc_ccreg_results = fn_smbcks_get_result_array($ccreg_res_content);

		// クレジットカード情報お預かりでエラーが発生している場合
		if( strcmp($smbc_ccreg_results['rescd'], '000000') !== 0){
			// エラーメッセージを表示
			fn_set_notification('E', __('jp_smbc_ccreg_error'), __('jp_smbc_ccreg_register_failed') . '<br />' . $smbc_ccreg_results['rescd'] . ' : ' . $smbc_ccreg_results['res']);

		// クレジットカード情報お預かりが正常に終了した場合
		}else{
			$_data = array('user_id' => $user_id,
						'payment_method' => 'smbc_ccreg', 
						'quickpay_id' => $smbc_ccreg_results['kmt_kok_id'],
						);
			if( (int)$_data['user_id'] == (int)$_data['quickpay_id'] ){
				db_query("REPLACE INTO ?:jp_cc_quickpay ?e", $_data);
			}
		}
	}
}




/**
 * 利用額変更処理が可能な注文であるかを判定
 *
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 * @return bool
 */
function fn_smbcks_cc_is_changeable($order_id, $order_info, $processor_data)
{
    // 利用額変更可否フラグを初期化
    $flg_changeable = false;
    $change_type = '';

    // 子注文の存在有無をチェック
    $is_parent_order = db_get_field("SELECT is_parent_order FROM ?:orders WHERE order_id = ?i", $order_id);

    // 子注文が存在しない場合
    if($is_parent_order == 'N'){

        // 与信済金額を取得
        $org_amount = fn_smbcks_get_auth_amount($order_id);

        // 与信済金額が存在する場合
        if( !empty($org_amount) ){
            // 変更後の金額
            $chg_amount = (int)$order_info['total'];

            // 編集後の注文金額が編集前よりも小さい（減額処理）場合
            if($org_amount > $chg_amount){
                // 編集前の注文で利用された決済方法を取得
                $org_payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
                $org_payment_method_data = fn_get_payment_method_data($org_payment_id);
                $org_processor_id = $org_payment_method_data['processor_id'];

                // 編集前後で同じ決済方法が選択されている場合
                if( !empty($org_processor_id) && $org_processor_id == $processor_data['processor_id'] ){

                    // 注文データからクレジット請求ステータスを取得
                    $cc_status = db_get_field("SELECT status_code FROM ?:jp_smbc_cc_status WHERE order_id = ?i", $order_id);

                    // ステータスコードが存在する場合
                    if( !empty($cc_status) ){
                        // 特定のステータスコードを持つ注文のみ利用額変更処理を許可
                        switch($cc_status){
                            case 'AUTH_OK':           // 与信
                                $flg_changeable = true;
                                $change_type = 'cc_change';
                                break;
                            case 'SALES_CONFIRMED':
                                $flg_changeable = true;
                                $change_type = 'cc_change_sales';
                            default:
                                // do nothing;
                        }
                    }
                }
            }
        }
    }

    return array($flg_changeable, $change_type);
}



/**
 * 与信済金額を取得
 *
 * @param $order_id
 * @return bool|int
 */
function fn_smbcks_get_auth_amount($order_id)
{
    // 注文データ内の支払関連情報を取得
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, 'P');

    // 注文データ内の支払関連情報が存在する場合
    if (!empty($payment_info)) {
        // 支払情報が暗号化されている場合は復号化して変数にセット
        if (!is_array($payment_info)) {
            $info = @unserialize(fn_decrypt_text($payment_info));
        } else {
            // 支払情報を変数にセット
            $info = $payment_info;
        }
        if (!empty($info['jp_smbc_cc_auth_amount']) && (int)$info['jp_smbc_cc_auth_amount'] > 0) {
            return (int)$info['jp_smbc_cc_auth_amount'];
        }
    }

    return false;
}
/////////////////////////////////////////////////////////////////////////////////////
//  クレジットカード決済 EOF
/////////////////////////////////////////////////////////////////////////////////////





/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * クレジット請求ステータス名を取得
 *
 * @param $cc_status
 * @return string
 */
function fn_smbcks_get_cc_status_name($cc_status)
{
	$cc_status_name = '';

	switch($cc_status){
		// 与信OK
		case 'AUTH_OK':
			$cc_status_name = __('jp_smbc_cc_auth_ok');
			break;
		// 与信NG
		case 'AUTH_NG':
			$cc_status_name = __('jp_smbc_cc_auth_ng');
			break;
		// 与信取消
		case 'AUTH_CANCELLED':
			$cc_status_name = __('jp_smbc_cc_auth_cancel');
			break;
		// 請求確定
		case 'SALES_CONFIRMED':
			$cc_status_name = __('jp_smbc_cc_sales_confirm');
			break;
		// 売上取消
		case 'SALES_CANCELLED':
			$cc_status_name = __('jp_smbc_cc_sales_cancel');
			break;
	}

	return $cc_status_name;
}




/**
 * 注文データ内に格納されたクレジット請求ステータスを更新
 *
 * @param $order_id
 * @param string $type
 */
function fn_smbcks_update_cc_status( $order_id, $type = 'cc_sales_confirm' )
{
    // クレジット請求ステータスを初期化
    $status_code = '';

    // 処理内容に応じてセットする値を変更
    switch($type){
        // 請求確定
        case 'cc_sales_confirm':
            $status_code = 'SALES_CONFIRMED';
            $msg = __('jp_smbc_cc_sales_completed');
            break;
        // 減額処理（与信一部取消）
        case 'cc_change':
            $status_code = 'AUTH_OK';
            $msg = __('jp_smbc_cc_auth_changed');
            break;
        // 減額処理（売上一部取消）
        case 'cc_change_sales':
            $status_code = 'SALES_CONFIRMED';
            $msg = __('jp_smbc_cc_auth_changed');
            break;
        // 与信取消
        case 'cc_auth_cancel':
            $status_code = 'AUTH_CANCELLED';
            $msg = __('jp_smbc_cc_auth_cancelled');
            break;
        // 売上取消
        case 'cc_sales_cancel':
            $status_code = 'SALES_CANCELLED';
            $msg = __('jp_smbc_cc_sales_cancelled');
            break;
        // その他
        default:
            // do nothing
    }

    // クレジット請求ステータスが設定されている場合
    if( !empty($status_code) ){
        // クレジット請求ステータスを更新
        fn_smbcks_update_cc_status_code($order_id, $status_code, $type);
        // 処理完了メッセージを表示
        fn_set_notification('N', __('information'), $msg, 'K');
    }
}




/**
 * クレジット請求確定・減額処理・与信取消・売上取消処理を実行
 *
 * @param $order_id
 * @param string $type
 * @param string $org_amount
 * @return bool
 */
function fn_smbcks_send_cc_request( $order_id, $type = 'cc_sales_confirm', $org_amount = '' )
{
    // 指定した処理を行うのに適した注文であるかを判定
    $is_valid_order = fn_smbcks_check_process_validity($order_id, $type);

    // 注文情報から請求番号を抽出
    $shoporder_no = fn_smbcks_get_shoporder_no((int)$order_id, 'retriev');

    // 請求番号が存在しない場合
    if ( empty($shoporder_no) || !$is_valid_order ){
        return false;
    }

    $params = array();

    // 当該注文の支払方法に関する情報を取得
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);

    // バージョン
    $params['version'] = fn_smbcks_get_version($type);

    // 決済手段区分
    $params['bill_method'] = '05';

    // 決済種類コード
    $params['kessai_id'] = '0501';

    // 契約コード
    $params['shop_cd'] = Registry::get('addons.smbc.shop_cd');

    // 収納企業コード
    $params['syuno_co_cd'] = Registry::get('addons.smbc.syuno_co_cd');

    // 拠点コード
    $kyoten_cd = Registry::get('addons.smbc.kyoten_cd');
    if( !empty($kyoten_cd) ){
        $params['kyoten_cd'] = Registry::get('addons.smbc.kyoten_cd');
    }

    // ショップパスワード
    $params['shop_pwd'] = Registry::get('addons.smbc.shop_pwd');

    // 請求番号
    $params['shoporder_no'] = $shoporder_no;

    // 請求金額
    $total = db_get_field("SELECT total FROM ?:orders WHERE ?:orders.order_id = ?i", $order_id);

    // 減額処理（与信一部取消まはた売上一部取消の場合）
    if( ($type == 'cc_change' || $type == 'cc_change_sales') && !empty($org_amount) ){
        $params['seikyuu_kingaku'] = $org_amount;
        $params['torikeshi_kingaku'] = round($total);
		// 注文情報を取得
		$tmp_order_info = fn_get_order_info($order_id);
		// 取消後内消費税を取得
		$params['torikeshi_shouhi_tax'] = fn_smbcks_calc_shouhi_tax($tmp_order_info);
	}else{
        $params['seikyuu_kingaku'] = round($total);
    }

    // クレジット請求管理情報送信
    $return_val = fn_smbcks_send_request($params, $processor_data, 'cc_manage');
    $response = $return_val['response'];
    $request = $return_val['request'];

    // クレジット請求管理情報送信が正常終了した場合
    if (!PEAR::isError($response)) {

        // 応答内容の解析
        $res_code = $request->getResponseCode();
        $res_content = $request->getResponseBody();

        // SMBCファイナンスサービスから受信した情報を配列に格納
        $smbc_results = fn_smbcks_get_result_array($res_content);

        // エラーが発生している場合
        if( strcmp($smbc_results['rescd'], '000000') !== 0){
            // エラーメッセージを表示
            $is_valid_order = false;
            fn_smbcks_get_cc_error_message($type, $order_id, $smbc_results);

            // クレジット請求管理が正常終了した場合
        }else{
            // CS-Cart注文情報内の請求ステータスと請求ステータスコードを更新
            fn_smbcks_update_cc_status($order_id, $type);
        }

        // クレジット請求管理情報送信が異常終了した場合
    }else{
        // データ通信エラーメッセージをセット
        $is_valid_order = false;
        fn_set_notification('E', __('jp_smbc_cc_sales_confirm_error'), '<br />' . __('jp_smbc_cc_communication_error'), 'K');
    }
    return $is_valid_order;
}




/**
 * 指定した処理を行うのに適した注文であるかを判定
 *
 * @param $order_id
 * @param $type
 * @return bool
 */
function fn_smbcks_check_process_validity( $order_id, $type )
{
    // 注文データからクレジット請求ステータスを取得
    $cc_status = db_get_field("SELECT status_code FROM ?:jp_smbc_cc_status WHERE order_id = ?i", $order_id);

    switch($type){
        // 請求確定
        case 'cc_sales_confirm':
            // 与信取消
        case 'cc_auth_cancel':
            if( $cc_status == 'AUTH_OK' ) return true;
            break;
        // 減額処理（与信一部取消）
        case 'cc_change':
            if( $cc_status == 'AUTH_OK' ) return true;
            break;
        // 減額処理（売上一部取消）
        case 'cc_change_sales':
            if( $cc_status == 'SALES_CONFIRMED' ) return true;
            break;
        // 売上取消
        case 'cc_sales_cancel':
            if( $cc_status == 'SALES_CONFIRMED' ) return true;
            break;
        // その他
        default:
            // do nothing
    }

    return false;
}




/**
 * クレジット請求に関する処理に応じたエラーメッセージを取得
 *
 * @param $type
 * @param $order_id
 * @param $smbc_results
 */
function fn_smbcks_get_cc_error_message($type, $order_id, $smbc_results)
{
    $is_diplay = true;

    switch($type){
        // 請求確定時のエラー
        case 'cc_sales_confirm':
            $title = __('jp_smbc_cc_sales_confirm_error');
            $msg = str_replace('[oid]', $order_id, __('jp_smbc_cc_sales_confirm_failed'));
            break;
        // 減額処理（与信一部取消または売上一部取消）時のエラー
        case 'cc_change':
        case 'cc_change_sales':
            $title = __('jp_smbc_cc_auth_change_error');
            $msg = str_replace('[oid]', $order_id, __('jp_smbc_cc_auth_change_failed'));
            break;
        // 与信取消時のエラー
        case 'cc_auth_cancel':
            $title = __('jp_smbc_cc_auth_cancel_error');
            $msg = str_replace('[oid]', $order_id, __('jp_smbc_cc_auth_cancel_failed'));
            break;
        // 売上取消時のエラー
        case 'cc_sales_cancel':
            $title = __('jp_smbc_cc_sales_cancel_error');
            $msg = str_replace('[oid]', $order_id, __('jp_smbc_cc_sales_cancel_failed'));
            break;
        default:
            $is_diplay = false;
    }

    if(	$is_diplay ){
        // エラーメッセージを表示
        fn_set_notification('E', $title, '<br />' . $msg . '<br />' . $smbc_results['rescd'] . ' : ' . $smbc_results['res'], 'K');
    }

}
/////////////////////////////////////////////////////////////////////////////////////
// クレジット請求管理 EOF
/////////////////////////////////////////////////////////////////////////////////////





/////////////////////////////////////////////////////////////////////////////////////
// Twigmo用関数 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * カード決済で利用可能な支払区分を取得
 *
 * @return array
 */
function fn_smbcks_tw_get_cc_methods()
{
	// CS-Cartマルチ決済（カード決済）の設定データを取得
	$_payment_id = db_get_field("SELECT payment_id FROM ?:payments WHERE template = ?s", 'views/orders/components/payments/smbc_cc.tpl');
	$_payment_data = fn_get_payment_method_data($_payment_id);

	$variants = array();

	// １回払い
	if( $_payment_data['processor_params']['shiharai_kbn'][1] == 'true' ){
		$variants[] = array (
			'variant_id' => 1,
			'variant_name' => 1,
			'description' => __('jp_cc_onetime')
			);
	}
	// 分割払い
	// 【メモ】TwigmoではオリジナルJavascriptを使用してonClickイベントで支払回数欄の表示・非表示ができない
	// そのため利用可能なすべての分割回数をリストに表示する
	if( $_payment_data['processor_params']['shiharai_kbn'][61] == 'true' && !empty($_payment_data['processor_params']['paycount']) ){
		foreach( $_payment_data['processor_params']['paycount'] as $paycount_key => $paycount ){
            if($paycount == 'true'){
                $variants[] = array (
                    'variant_id' => $paycount_key,
                    'variant_name' => $paycount_key,
                    'description' => __('jp_cc_installment') . '(' . $paycount_key . __('jp_paytimes_unit') . ')'
                    );
            }
		}
	}
	// ボーナス払い
	if( $_payment_data['processor_params']['shiharai_kbn'][91] == 'true' ){
		$variants[] = array (
			'variant_id' => 91,
			'variant_name' => 91,
			'description' => __('jp_cc_bonus_onetime')
			);
	}
	// リボ払い
	if( $_payment_data['processor_params']['shiharai_kbn'][80] == 'true' ){
		$variants[] = array (
			'variant_id' => 80,
			'variant_name' => 80,
			'description' => __('jp_cc_revo')
			);
	}

	return $variants;
}




/**
 * コンビニ受付番号決済で利用可能なコンビ二名を取得
 *
 * @return array
 */
function fn_smbcks_tw_get_cvs_list()
{
	// CS-Cartマルチ決済（コンビニ受付番号決済）の設定データを取得
	$_payment_id = db_get_field("SELECT payment_id FROM ?:payments WHERE template = ?s", 'views/orders/components/payments/smbc_cvs.tpl');
	$_payment_data = fn_get_payment_method_data($_payment_id);

	$variants = array();

	if( $_payment_data['processor_params']['cnvkind']['0301'] == 'true' ){
		$variants[] = array (
			'variant_id' => '0301',
			'variant_name' => '0301',
			'description' => __('jp_cvs_se')
			);
	}
	if( $_payment_data['processor_params']['cnvkind']['0302'] == 'true' ){
		$variants[] = array (
			'variant_id' => '0302',
			'variant_name' => '0302',
			'description' => __('jp_cvs_ls') . ' / ' . __('jp_cvs_ms')
			);
	}
	if( $_payment_data['processor_params']['cnvkind']['0303'] == 'true' ){
		$variants[] = array (
			'variant_id' => '0303',
			'variant_name' => '0303',
			'description' => __('jp_cvs_sm')
			);
	}
	if( $_payment_data['processor_params']['cnvkind']['0304'] == 'true' ){
		$variants[] = array (
			'variant_id' => '0304',
			'variant_name' => '0304',
			'description' => __('jp_cvs_fm')
			);
	}
	if( $_payment_data['processor_params']['cnvkind']['0305'] == 'true' ){
		$variants[] = array (
			'variant_id' => '0305',
			'variant_name' => '0305',
			'description' => __('jp_cvs_ck') . ' / ' . __('jp_cvs_ts')
			);
	}

	return $variants;
}




/**
 * 登録済カード決済で登録済みカード番号を取得
 * 【メモ】Twigmoでは支払方法のフィールドにテキストを単純表示することができない
 * そのため、選択肢が１つしかないセレクトボックスとして登録済カード番号を表示する
 *
 * @return array
 */
function fn_smbcks_tw_get_registered_card_number()
{
	$tmp_auth = Tygh::$app['session']['auth'];

	$registered_card_info = fn_smbcks_get_registered_card_info($tmp_auth['user_id']);
	$registered_card_number = $registered_card_info['card_number'];

	$variants[] = array (
		'variant_id' => 'registered_card_number',
		'variant_name' => 'registered_card_number',
		'description' => $registered_card_number,
		);

	return $variants;
}




/**
 * 登録済みカード決済で利用可能な支払区分を取得
 *
 * @return array
 */
function fn_smbcks_tw_get_ccreg_methods()
{
	// CS-Cartマルチ決済（登録済みカード決済）の設定データを取得
	$_payment_id = db_get_field("SELECT payment_id FROM ?:payments WHERE template = ?s", 'views/orders/components/payments/smbc_ccreg.tpl');
	$_payment_data = fn_get_payment_method_data($_payment_id);

	$variants = array();

	// １回払い
	if( $_payment_data['processor_params']['shiharai_kbn'][1] == 'true' ){
		$variants[] = array (
			'variant_id' => 1,
			'variant_name' => 1,
			'description' => __('jp_cc_onetime')
			);
	}
	// 分割払い
	// 【メモ】TwigmoではオリジナルJavascriptを使用してonClickイベントで支払回数欄の表示・非表示ができない
	// そのため利用可能なすべての分割回数をリストに表示する
	if( $_payment_data['processor_params']['shiharai_kbn'][61] == 'true' && !empty($_payment_data['processor_params']['paycount']) ){
		foreach( $_payment_data['processor_params']['paycount'] as $paycount_key => $paycount ){
            if($paycount == 'true'){
                $variants[] = array (
                    'variant_id' => $paycount_key,
                    'variant_name' => $paycount_key,
                    'description' => __('jp_cc_installment') . '(' . $paycount_key . __('jp_paytimes_unit') . ')'
                    );
            }
		}
	}
	// ボーナス払い
	if( $_payment_data['processor_params']['shiharai_kbn'][91] == 'true' ){
		$variants[] = array (
			'variant_id' => 91,
			'variant_name' => 91,
			'description' => __('jp_cc_bonus_onetime')
			);
	}
	// リボ払い
	if( $_payment_data['processor_params']['shiharai_kbn'][80] == 'true' ){
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
function fn_smbcks_tw_confirm_card_register()
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
/////////////////////////////////////////////////////////////////////////////////////
// Twigmo用関数 EOF
/////////////////////////////////////////////////////////////////////////////////////





/////////////////////////////////////////////////////////////////////////////////////
// 継続課金 BOF
/////////////////////////////////////////////////////////////////////////////////////

/**
 * 課金開始年月を取得
 *
 * @param $product_id
 * @return string
 */
function fn_smbcks_get_seikyuu_kaishi_ym($product_id)
{
	// 課金開始タイミングを取得（翌月/翌々月/3ヶ月後）
	$charge_timing = db_get_field("SELECT charge_timing FROM ?:jp_smbc_rb_products WHERE product_id = ?i", $product_id);

	// 現在の年月を取得
	$this_year = date('Y');
	$this_month = date('m');

	// 課金開始年月を取得
	$seikyuu_kaishi_ym = date("Y", mktime(0, 0, 0, $this_month + $charge_timing, 1, $this_year)) . date("m", mktime(0, 0, 0, $this_month + $charge_timing, 1, $this_year));

	return $seikyuu_kaishi_ym;
}




/**
 * 初回に通常と異なる金額を課金するかどうかチェック
 *
 * @param $product_id
 * @return bool
 */
function fn_is_1st_payment_enabled($product_id)
{
	// 注文商品における継続課金情報を取得
	$is_1st_payment_enabled = db_get_field("SELECT enable_1st_payment FROM ?:jp_smbc_rb_products WHERE product_id = ?i", $product_id);

	// 請求金額（初回）が有効化されている場合
	if( $is_1st_payment_enabled == 'Y' ){
		return true;
	// 請求金額（初回）が無効の場合
	}else{
		return false;
	}
}




/**
 * CS-Cartマルチ決済継続課金対象商品の情報を取得
 *
 * @param $product_id
 * @return array
 */
function fn_smbcks_get_rb_product_info($product_id)
{
	$smbc_rb_product_info = db_get_row("SELECT * FROM ?:jp_smbc_rb_products WHERE product_id = ?i", $product_id);

	return $smbc_rb_product_info;
}
##########################################################################################
// END その他の関数
##########################################################################################
