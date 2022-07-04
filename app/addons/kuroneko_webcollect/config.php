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

// $Id: config.php by tommy from cs-cart.jp 2016

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// クロネコwebコレクトで使用する購入商品名称の最大文字数（バイト数ではなく文字数）
fn_define('KRNKWC_MAXLEN_PRODUCT', 200);

// クロネコ代金後払いサービスで使用する氏名の最大文字数（バイト数ではなく文字数）
fn_define('KRNKAB_MAXLEN_NAME', 30);

// クロネコ代金後払いサービスで使用する氏名カナの最大文字数（バイト数ではなく文字数）
// 項目定義には80桁とあるが、エラー一覧には40桁とある
fn_define('KRNKAB_MAXLEN_NAME_KANA', 40);

// クロネコ代金後払いサービスで使用する電話番号の最大文字数（バイト数ではなく文字数）
fn_define('KRNKAB_MAXLEN_NAME_TELNUM', 11);

// クロネコ代金後払いサービスで使用する住所の最大文字数（バイト数ではなく文字数）
fn_define('KRNKAB_MAXLEN_ADDRESS', 25);

// クロネコ代金後払いサービスで使用する購入商品名称の最大文字数（バイト数ではなく文字数）
fn_define('KRNKAB_MAXLEN_PRODUCT', 30);

// カートコード
fn_define('KRNK_CART_CODE', 'cscart');


////////////////////////////////////////////////////////////////////////////////////////
// テスト環境接続先URL（クロネコwebコレクト） BOF
////////////////////////////////////////////////////////////////////////////////////////
// A01 : クレジット決済登録(1) 用URL（テスト環境）
define('KRNKWC_TEST_URL_CREDIT', 'https://ptwebcollect.jp/test_gateway/credit.api');

// A02 : クレジット決済登録(2) 用URL（テスト環境）
define('KRNKWC_TEST_URL_CREDIT3D', 'https://ptwebcollect.jp/test_gateway/credit3D.api');

// A03 : お預かり情報照会用URL（テスト環境）
define('KRNKWC_TEST_URL_CREDITINFOGET', 'https://ptwebcollect.jp/test_gateway/creditInfoGet.api');

// A04 : お預かり情報変更用URL（テスト環境）
define('KRNKWC_TEST_URL_CREDITINFOUPDATE', 'https://ptwebcollect.jp/test_gateway/creditInfoUpdate.api');

// A05 : お預かり情報削除用URL（テスト環境）
define('KRNKWC_TEST_URL_CREDITINFODELETE', 'https://ptwebcollect.jp/test_gateway/creditInfoDelete.api');

// A06 : クレジット決済取消用URL（テスト環境）
define('KRNKWC_TEST_URL_CREDITCANCEL', 'https://ptwebcollect.jp/test_gateway/creditCancel.api');

// A07 : クレジット金額変更用URL（テスト環境）
define('KRNKWC_TEST_URL_CREDITCHANGEPRICE', 'https://ptwebcollect.jp/test_gateway/creditChangePrice.api');

// A08 : トークン決済登録（テスト環境）
define('KRNKWC_TEST_URL_CREDITTOKEN', 'https://ptwebcollect.jp/test_gateway/creditToken.api');

// A09 : トークン決済登録 ３Ｄセキュア結果用（テスト環境）
define('KRNKWC_TEST_URL_CREDITTOKEN3D', 'https://ptwebcollect.jp/test_gateway/creditToken3D.api');

// B01 : コンビニ決済登録（セブン-イレブン）用URL（テスト環境）
define('KRNKWC_TEST_URL_CVS1', 'https://ptwebcollect.jp/test_gateway/cvs1.api');

// B02 : コンビニ決済登録（ファミリーマート）用URL（テスト環境）
define('KRNKWC_TEST_URL_CVS2', 'https://ptwebcollect.jp/test_gateway/cvs2.api');

// B03/B04/B05/B06 : コンビニ決済登録（ローソン、サークルKサンクス、ミニストップ、セイコーマート）用URL（テスト環境）
define('KRNKWC_TEST_URL_CVS3', 'https://ptwebcollect.jp/test_gateway/cvs3.api');

// C01 : 電子マネー（楽天Edy・PC）用URL（テスト環境）
define('KRNKWC_TEST_URL_EMONEY1', 'https://ptwebcollect.jp/test_gateway/e_money1.api');

// C02 : 電子マネー（楽天Edy・携帯）用URL（テスト環境）
define('KRNKWC_TEST_URL_EMONEY2', 'https://ptwebcollect.jp/test_gateway/e_money2.api');

// C03 : 電子マネー（Suica・PC）用URL（テスト環境）
define('KRNKWC_TEST_URL_EMONEY3', 'https://ptwebcollect.jp/test_gateway/e_money3.api');

// C04 : 電子マネー（Suica・携帯）用URL（テスト環境）
define('KRNKWC_TEST_URL_EMONEY4', 'https://ptwebcollect.jp/test_gateway/e_money4.api');

// C05 : 電子マネー（WAON・PC）用URL（テスト環境）
define('KRNKWC_TEST_URL_EMONEY5', 'https://ptwebcollect.jp/test_gateway/e_money5.api');

// C06 : 電子マネー（WAON・携帯）用URL（テスト環境）
define('KRNKWC_TEST_URL_EMONEY6', 'https://ptwebcollect.jp/test_gateway/e_money6.api');

// D01 : ネットバンク決済登録用URL（テスト環境）
define('KRNKWC_TEST_URL_BANK1', 'https://ptwebcollect.jp/test_gateway/bank1.api');

// E01 : 出荷情報登録用URL（テスト環境）
define('KRNKWC_TEST_URL_SHIPMENTENTRY', 'https://ptwebcollect.jp/test_gateway/shipmentEntry.api');

// E02 : 出荷情報取消用URL（テスト環境）
define('KRNKWC_TEST_URL_SHIPMENTCANCEL', 'https://ptwebcollect.jp/test_gateway/shipmentCancel.api');

// E03 : 出荷予定日変更用URL（テスト環境）
define('KRNKWC_TEST_URL_CHANGEDATE', 'https://ptwebcollect.jp/test_gateway/changeDate.api');

// E04 : 取引情報照会用URL（テスト環境）
define('KRNKWC_TEST_URL_TRADEINFO', 'https://ptwebcollect.jp/test_gateway/tradeInfo.api');

// G01 : 継続課金登録(1)用URL（テスト環境）
define('KRNKWC_TEST_URL_REGULAR', 'https://ptwebcollect.jp/test_gateway/regular.api');

// G02 : 継続課金登録(2)用URL（テスト環境）
define('KRNKWC_TEST_URL_REGULAR3D', 'https://ptwebcollect.jp/test_gateway/regular3D.api');

// G03 : 継続課金照会用URL（テスト環境）
define('KRNKWC_TEST_URL_REGULARINFO', 'https://ptwebcollect.jp/test_gateway/regularInfo.api');

// G04 : 継続課金更新用URL（テスト環境）
define('KRNKWC_TEST_URL_REGULARUPDATE', 'https://ptwebcollect.jp/test_gateway/regularUpdate.api');

// G04 : 継続課金削除用URL（テスト環境）
define('KRNKWC_TEST_URL_REGULARDELETE', 'https://ptwebcollect.jp/test_gateway/regularDelete.api');
////////////////////////////////////////////////////////////////////////////////////////
// テスト環境接続先URL（クロネコwebコレクト） EOF
////////////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////////////
// 本番環境接続先URL（クロネコwebコレクト） BOF
////////////////////////////////////////////////////////////////////////////////////////
// A01 : クレジット決済登録(1) 用URL（本番環境）
define('KRNKWC_LIVE_URL_CREDIT', 'https://api.kuronekoyamato.co.jp/api/credit');

// A02 : クレジット決済登録(2) 用URL（本番環境）
define('KRNKWC_LIVE_URL_CREDIT3D', 'https://api.kuronekoyamato.co.jp/api/credit3D');

// A03 : お預かり情報照会用URL（本番環境）
define('KRNKWC_LIVE_URL_CREDITINFOGET', 'https://api.kuronekoyamato.co.jp/api/creditInfoGet');

// A04 : お預かり情報変更用URL（本番環境）
define('KRNKWC_LIVE_URL_CREDITINFOUPDATE', 'https://api.kuronekoyamato.co.jp/api/creditInfoUpdate');

// A05 : お預かり情報削除用URL（本番環境）
define('KRNKWC_LIVE_URL_CREDITINFODELETE', 'https://api.kuronekoyamato.co.jp/api/creditInfoDelete');

// A06 : クレジット決済取消用URL（本番環境）
define('KRNKWC_LIVE_URL_CREDITCANCEL', 'https://api.kuronekoyamato.co.jp/api/creditCancel');

// A07 : クレジット金額変更用URL（本番環境）
define('KRNKWC_LIVE_URL_CREDITCHANGEPRICE', 'https://api.kuronekoyamato.co.jp/api/creditChangePrice');

// A08 : トークン決済登録（本番環境）
define('KRNKWC_LIVE_URL_CREDITTOKEN', 'https://api.kuronekoyamato.co.jp/api/creditToken');

// A09 : トークン決済登録 ３Ｄセキュア結果用（本番環境）
define('KRNKWC_LIVE_URL_CREDITTOKEN3D', 'https://api.kuronekoyamato.co.jp/api/creditToken3D');

// B01 : コンビニ決済登録（セブン-イレブン）用URL（本番環境）
define('KRNKWC_LIVE_URL_CVS1', 'https://api.kuronekoyamato.co.jp/api/cvs1');

// B02 : コンビニ決済登録（ファミリーマート）用URL（本番環境）
define('KRNKWC_LIVE_URL_CVS2', 'https://api.kuronekoyamato.co.jp/api/cvs2');

// B03/B04/B05/B06 : コンビニ決済登録（ローソン、サークルKサンクス、ミニストップ、セイコーマート）用URL（本番環境）
define('KRNKWC_LIVE_URL_CVS3', 'https://api.kuronekoyamato.co.jp/api/cvs3');

// C01 : 電子マネー（楽天Edy・PC）用URL（本番環境）
define('KRNKWC_LIVE_URL_EMONEY1', 'https://api.kuronekoyamato.co.jp/api/e_money1');

// C02 : 電子マネー（楽天Edy・携帯）用URL（本番環境）
define('KRNKWC_LIVE_URL_EMONEY2', 'https://api.kuronekoyamato.co.jp/api/e_money2');

// C03 : 電子マネー（Suica・PC）用URL（本番環境）
define('KRNKWC_LIVE_URL_EMONEY3', 'https://api.kuronekoyamato.co.jp/api/e_money3');

// C04 : 電子マネー（Suica・携帯）用URL（本番環境）
define('KRNKWC_LIVE_URL_EMONEY4', 'https://api.kuronekoyamato.co.jp/api/e_money4');

// C05 : 電子マネー（WAON・PC）用URL（本番環境）
define('KRNKWC_LIVE_URL_EMONEY5', 'https://api.kuronekoyamato.co.jp/api/e_money5');

// C06 : 電子マネー（WAON・携帯）用URL（本番環境）
define('KRNKWC_LIVE_URL_EMONEY6', 'https://api.kuronekoyamato.co.jp/api/e_money6');

// D01 : ネットバンク決済登録用URL（本番環境）
define('KRNKWC_LIVE_URL_BANK1', 'https://api.kuronekoyamato.co.jp/api/bank1');

// E01 : 出荷情報登録用URL（本番環境）
define('KRNKWC_LIVE_URL_SHIPMENTENTRY', 'https://api.kuronekoyamato.co.jp/api/shipmentEntry');

// E02 : 出荷情報取消用URL（本番環境）
define('KRNKWC_LIVE_URL_SHIPMENTCANCEL', 'https://api.kuronekoyamato.co.jp/api/shipmentCancel');

// E03 : 出荷予定日変更用URL（本番環境）
define('KRNKWC_LIVE_URL_CHANGEDATE', 'https://api.kuronekoyamato.co.jp/api/changeDate');

// E04 : 取引情報照会用URL（本番環境）
define('KRNKWC_LIVE_URL_TRADEINFO', 'https://api.kuronekoyamato.co.jp/api/tradeInfo');

// G01 : 継続課金登録(1)用URL（本番環境）
define('KRNKWC_LIVE_URL_REGULAR', 'https://api.kuronekoyamato.co.jp/api/regular');

// G02 : 継続課金登録(2)用URL（本番環境）
define('KRNKWC_LIVE_URL_REGULAR3D', 'https://api.kuronekoyamato.co.jp/api/regular3D');

// G03 : 継続課金照会用URL（本番環境）
define('KRNKWC_LIVE_URL_REGULARINFO', 'https://api.kuronekoyamato.co.jp/api/regularInfo');

// G04 : 継続課金更新用URL（本番環境）
define('KRNKWC_LIVE_URL_REGULARUPDATE', 'https://api.kuronekoyamato.co.jp/api/regularUpdate');

// G04 : 継続課金削除用URL（本番環境）
define('KRNKWC_LIVE_URL_REGULARDELETE', 'https://api.kuronekoyamato.co.jp/api/regularDelete');
////////////////////////////////////////////////////////////////////////////////////////
// 本番環境接続先URL（クロネコwebコレクト） EOF
////////////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////////////
// テスト環境接続先URL（クロネコ代金後払いサービス） BOF
////////////////////////////////////////////////////////////////////////////////////////
// 決済依頼接続先URL（テスト環境）
define('KRNKWC_TEST_URL_KAARA0010', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAARA0010APIAction_execute.action');

// 決済結果照会接続先URL（テスト環境）
define('KRNKWC_TEST_URL_KAARS0010', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAARS0010APIAction_execute.action');

// 決済取消依頼接続先URL（テスト環境）
define('KRNKWC_TEST_URL_KAACL0010', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAACL0010APIAction_execute.action');

// 出荷情報依頼接続先URL（テスト環境）
define('KRNKWC_TEST_URL_KAASL0010', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAASL0010APIAction_execute.action');

// 取引状況照会接続先URL（テスト環境）
define('KRNKWC_TEST_URL_KAAST0010', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAAST0010APIAction_execute.action');

// 請求書印字情報取得接続先URL（テスト環境）
define('KRNKWC_TEST_URL_KAASD0010', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAASD0010APIAction_execute.action');

// 請求金額変更（減額）接続先URL（テスト環境）
define('KRNKWC_TEST_URL_KAAKK0010', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAAKK0010APIAction_execute.action');

// SMS認証番号判定リクエスト送信先URL（テスト環境）
define('KRNKWC_TEST_URL_KAASA0020', 'https://atobarai-test.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAASA0020APIAction_execute.action');
////////////////////////////////////////////////////////////////////////////////////////
// テスト環境接続先URL（クロネコ代金後払いサービス） EOF
////////////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////////////
// 本番環境接続先URL（クロネコ代金後払いサービス） BOF
////////////////////////////////////////////////////////////////////////////////////////
// 決済依頼接続先URL（本番環境）
define('KRNKWC_LIVE_URL_KAARA0010', 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAARA0010APIAction_execute.action');

// 決済結果照会接続先URL（本番環境）
define('KRNKWC_LIVE_URL_KAARS0010', 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAARS0010APIAction_execute.action');

// 決済取消依頼接続先URL（本番環境）
define('KRNKWC_LIVE_URL_KAACL0010', 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAACL0010APIAction_execute.action');

// 出荷情報依頼接続先URL（本番環境）
define('KRNKWC_LIVE_URL_KAASL0010', 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAASL0010APIAction_execute.action');

// 取引状況照会接続先URL（本番環境）
define('KRNKWC_LIVE_URL_KAAST0010', 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAAST0010APIAction_execute.action');

// 請求書印字情報取得接続先URL（本番環境）
define('KRNKWC_LIVE_URL_KAASD0010', 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAASD0010APIAction_execute.action');

// 請求金額変更（減額）接続先URL（本番環境）
define('KRNKWC_LIVE_URL_KAAKK0010', 'https://atobarai.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAAKK0010APIAction_execute.action');

// SMS認証番号判定リクエスト送信先URL（本番環境）
define('KRNKWC_LIVE_URL_KAASA0020', 'https://atobarai.kuronekoyamato.co.jp/kuroneko-atobarai-api/KAASA0020APIAction_execute.action');
////////////////////////////////////////////////////////////////////////////////////////
// 本番環境接続先URL（クロネコ代金後払いサービス） EOF
////////////////////////////////////////////////////////////////////////////////////////
