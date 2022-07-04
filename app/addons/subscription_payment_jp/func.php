<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2009 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  acceptU  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: func.php by tommy from cs-cart.jp 2014
//
// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：fn_subscription_payment_jp_[フックポイント名]
// (2) (1)以外の関数：fn_subpay_jp_[任意の名称]

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

##########################################################################################
// START フックポイントで動作する関数
##########################################################################################

// カート内商品の種類（継続課金対象商品 or 通常商品）に応じて選択可能な支払方法を制御
function fn_subscription_payment_jp_prepare_checkout_payment_methods(&$cart, &$auth, &$payment_groups)
{
    // 注文完了ページでは実施しない
    if( Registry::get('runtime.mode') == 'complete' ) return false;

	// 指定されたオンラインおよびオフラインの継続課金用支払方法を取得
	$subpay_online_id = Registry::get('addons.subscription_payment_jp.subpay_online');
	$subpay_offline_id = Registry::get('addons.subscription_payment_jp.subpay_offline');

	// 継続課金用支払方法が指定されている場合
	if( !empty($subpay_online_id) || !empty($subpay_offline_id) ){

		// すべての有効な支払方法グループについて処理を実施
		foreach($payment_groups as $tab_key => $vals){
			foreach($vals as $key => $val){
				// カートに存在する商品が継続課金対象商品である場合
				if( fn_subpay_jp_is_subscription_products($cart['products']) ){
					// 継続課金以外の支払方法の場合
					if( !fn_subpay_jp_is_subscription($key) ){
						// 支払方法として選択できないようにする
						unset($payment_groups[$tab_key][$key]);
					}
				// カートに存在する商品が通常商品である場合
				}else{
					// 継続課金の場合
					if( fn_subpay_jp_is_subscription($key) ){
						// 支払方法として選択できないようにする
						unset($payment_groups[$tab_key][$key]);
					}
				}
			}
		}
	}
}




// 商品をカートに追加する前に既存のカート内容をすべてクリア
function fn_subscription_payment_jp_pre_add_to_cart(&$product_data, &$cart, &$auth, &$update)
{
	// カートに追加する商品が継続課金対象商品である場合
	if( fn_subpay_jp_is_subscription_products($product_data) ){
		// すでに商品がカートに存在する場合
		if( !empty($cart['products']) ){
			// すでにカートに存在する商品をクリアした旨を伝えるメッセージを表示
			fn_set_notification('N', __('notice'), __('jp_subpay_product_added_to_cart'));
		}
		// 商品をカートに追加する前に既存のカート内容をすべてクリア
		fn_clear_cart($cart);

	// カートに追加する商品が都度課金商品である場合
	}else{
		// すでに商品がカートに存在する場合
		if( !empty($cart['products']) ){
			// カートに存在する商品が継続課金対象商品である場合
			if( fn_subpay_jp_is_subscription_products($cart['products']) ){
				// 継続課金商品がカートに存在するため商品をカートに追加できない旨を伝えるメッセージを表示
				fn_set_notification('W', __('notice'), __('jp_subpay_error_add_product_to_cart'));
				exit();
			}
		}
	}
}




// カートに継続課金対象商品が入っている状態でログインした際に、前回ログアウトする時点で
// カートに入っていた商品をクリア
function fn_subscription_payment_jp_pre_extract_cart(&$cart, &$condition, &$item_types)
{
	// ログイン前に継続課金商品がカートに入っている場合
	if( !empty($cart['products']) && fn_subpay_jp_is_subscription_products($cart['products']) ){
		// ユーザーIDを取得
		$tmp_user_id = Tygh::$app['session']['auth']['user_id'];

		// ゲスト購入以外の場合
		if( !empty($tmp_user_id) ){
			// 前回ログアウトする時点でカートに入っていた商品をクリア
			//db_query("DELETE FROM ?:user_session_products WHERE user_id = ?i AND type = ?s", $tmp_user_id, 'C');
		}
	}
}




// 商品の継続課金に関する情報の保存
function fn_subscription_payment_jp_update_product_post(&$product_data, &$product_id, &$lang_code, &$create)
{
	// この処理を入れないと商品情報の一括編集時に値がリセットされてしまう。
	if (!isset($product_data['subpay_jp_subscription_product'])) {
		return false;
	}

	$product_subscription_data = array('product_id' => (int)$product_id,
					'is_subscription' => $product_data['subpay_jp_subscription_product'],
					);
	$product_subscription_desc_data = array('product_id' => (int)$product_id,
					'price_prefix' => $product_data['subpay_jp_price_prefix'],
					'price_suffix' => $product_data['subpay_jp_price_suffix'],
					'description' => $product_data['subpay_jp_description'],
					'lang_code' => DESCR_SL,
					);

	db_query("REPLACE INTO ?:jp_subscription_products ?e", $product_subscription_data);
	db_query("REPLACE INTO ?:jp_subscription_products_descriptions ?e", $product_subscription_desc_data);

	return true;
}




// 商品を削除した際にその商品の継続課金に関する情報も削除する
function fn_subscription_payment_jp_delete_product_post(&$product_id)
{
	db_query("DELETE FROM ?:jp_subscription_products WHERE product_id = ?i", $product_id);
	db_query("DELETE FROM ?:jp_subscription_products_descriptions WHERE product_id = ?i", $product_id);
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
function fn_subscription_payment_jp_install()
{
    fn_lcjp_install('subscription_payment_jp');
}
##########################################################################################
// END アドオンのインストール・アンインストール時に動作する関数
##########################################################################################




##########################################################################################
// START アドオンの設定ページで動作する関数
##########################################################################################

// 継続課金用オンライン決済サービスを指定するセレクトボックスを生成
function fn_settings_variants_addons_subscription_payment_jp_subpay_online()
{
    // 指定無し
    $variants = array('0' => __('none'));

    // 有効な支払方法をセレクトボックスの選択肢として生成
    $payment_methods = array();
    $payment_methods = fn_lcjp_get_simple_payment_methods();

    if( $payment_methods ){
        foreach($payment_methods as $key => $val){
            // 支払方法にひもづけられた決済代行サービスのIDを取得
            $processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $key);

            // 決済代行サービスのIDが0（オフライン決済）以外の場合はリストに表示
            if( !empty($processor_id) ){
                $variants[$key] = $val;
            }
        }
    }

    return $variants;
}




// 継続課金用オフライン決済サービスを指定するセレクトボックスを生成
function fn_settings_variants_addons_subscription_payment_jp_subpay_offline()
{
    // 指定無し
    $variants = array('0' => __('none'));

    // 有効な支払方法をセレクトボックスの選択肢として生成
    $payment_methods = array();
    $payment_methods = fn_lcjp_get_simple_payment_methods();

    if( $payment_methods ){
        foreach($payment_methods as $key => $val){
            // 支払方法にひもづけられた決済代行サービスのIDを取得
            $processor_id = db_get_field("SELECT processor_id FROM ?:payments WHERE payment_id = ?i", $key);

            // 決済代行サービスのIDが0（オフライン決済）の場合はリストに表示
            if( empty($processor_id) ){
                $variants[$key] = $val;
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

// 商品が継続課金の対象であるかチェック（個別商品対応）
function fn_subpay_jp_is_subscription_products_by_id($product_id = '')
{
	if( empty($product_id) ) return false;

	$is_subscription = false;

	$is_subsctiption = db_get_field("SELECT is_subscription FROM ?:jp_subscription_products WHERE product_id = ?i", $product_id);
	if($is_subsctiption == 'Y') $is_subscription = true;

	return $is_subscription;
}




// 商品が継続課金の対象であるかチェック（複数商品対応）
function fn_subpay_jp_is_subscription_products($products = array() )
{
	if(empty($products) || !is_array($products) ) return false;

	$is_subscription = false;

	foreach($products as $product){
		$is_subsctiption = db_get_field("SELECT is_subscription FROM ?:jp_subscription_products WHERE product_id = ?i", $product['product_id']);
		if($is_subsctiption == 'Y') $is_subscription = true;
	}

	return $is_subscription;
}




// 継続課金対象商品の情報を取得
function fn_subpay_jp_get_subscription_product_info($product_id, $lang_code = CART_LANGUAGE)
{
	$subscription_product_info = db_get_row("SELECT * FROM ?:jp_subscription_products LEFT JOIN ?:jp_subscription_products_descriptions ON ?:jp_subscription_products_descriptions.product_id = ?:jp_subscription_products.product_id AND ?:jp_subscription_products_descriptions.lang_code = ?s WHERE ?:jp_subscription_products.product_id = ?i", $lang_code, $product_id);

	return $subscription_product_info;
}




// 指定した支払方法が継続課金向け支払方法であるかを判定
function fn_subpay_jp_is_subscription($payment_id)
{
	$subpay_online_id = Registry::get('addons.subscription_payment_jp.subpay_online');
	$subpay_offline_id = Registry::get('addons.subscription_payment_jp.subpay_offline');

	if( $payment_id == $subpay_online_id || $payment_id == $subpay_offline_id ){
		return true;
	}else{
		return false;
	}
}
##########################################################################################
// END その他の関数
##########################################################################################
