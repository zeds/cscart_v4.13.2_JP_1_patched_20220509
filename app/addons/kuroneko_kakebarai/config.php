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

// $Id: config.php by takahashi from cs-cart.jp 2018


////////////////////////////////////////////////////////////////////////////////////////
// テスト環境接続先URL（クロネコ掛け払い） BOF
////////////////////////////////////////////////////////////////////////////////////////
// 決済登録API
define('KRNKKB_TEST_URL_AN010API', 'http://demo.yamato-credit-finance.jp/kuroneko-anshin/AN010APIAction.action');

// 決済取消API
define('KRNKKB_TEST_URL_AN020API', 'https://demo.yamato-credit-finance.jp/kuroneko-anshin/AN020APIAction.action');

// 出荷登録API
define('KRNKKB_TEST_URL_AN030API', 'https://demo.yamato-credit-finance.jp/kuroneko-anshin/AN030APIAction.action');

// 出荷取消API
define('KRNKKB_TEST_URL_AN040API', 'https://demo.yamato-credit-finance.jp/kuroneko-anshin/AN040APIAction.action');

// 利用金額照会API
define('KRNKKB_TEST_URL_AN050API', 'https://demo.yamato-credit-finance.jp/kuroneko-anshin/AN050APIAction.action');

// 取引先登録API
define('KRNKKB_TEST_URL_AN060API', 'https://demo.yamato-credit-finance.jp/kuroneko-anshin/AN060APIAction.action');

// 審査状況照会API
define('KRNKKB_TEST_URL_AN070API', 'https://demo.yamato-credit-finance.jp/kuroneko-anshin/AN070APIAction.action');
////////////////////////////////////////////////////////////////////////////////////////
// テスト環境接続先URL（クロネコ掛け払い） EOF
////////////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////////////
// 本番環境接続先URL（クロネコ掛け払い） BOF
////////////////////////////////////////////////////////////////////////////////////////
// 決済登録API
define('KRNKKB_LIVE_URL_AN010API', 'https://yamato-credit-finance.jp/kuroneko-anshin/AN010APIAction.action');

// 決済取消API
define('KRNKKB_LIVE_URL_AN020API', 'https://yamato-credit-finance.jp/kuroneko-anshin/AN020APIAction.action');

// 出荷登録API
define('KRNKKB_LIVE_URL_AN030API', 'https://yamato-credit-finance.jp/kuroneko-anshin/AN030APIAction.action');

// 出荷取消API
define('KRNKKB_LIVE_URL_AN040API', 'https://yamato-credit-finance.jp/kuroneko-anshin/AN040APIAction.action');

// 利用金額照会API
define('KRNKKB_LIVE_URL_AN050API', 'https://yamato-credit-finance.jp/kuroneko-anshin/AN050APIAction.action');

// 取引先登録API
define('KRNKKB_LIVE_URL_AN060API', 'https://yamato-credit-finance.jp/kuroneko-anshin/AN060APIAction.action');

// 審査状況照会API
define('KRNKKB_LIVE_URL_AN070API', 'https://yamato-credit-finance.jp/kuroneko-anshin/AN070APIAction.action');
////////////////////////////////////////////////////////////////////////////////////////
// 本番環境接続先URL（クロネコ掛け払い） EOF
////////////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////////////
// 項目定義 BOF
////////////////////////////////////////////////////////////////////////////////////////
// 全角文字
const KRNKKB_FULL_CHAR = array(
    'sMei' => 30,
    'shitenMei' => 30,
    'sMeikana' => 60,
    'shitenMeikana' => 60,
    'Adress' => 50,
    'daikjmeiSei' => 14,
    'daikjmeiMei' => 14,
    'daiknameiSei' => 29,
    'daiknameiMei' => 29,
    'daiAddress' => 50,
    'szHonknjmei' => 30,
    'szHonknamei' => 60,
    'szAddress' => 50,
    'szDaikjmei_sei' => 14,
    'szDaikjmei_mei' => 14,
    'szDaiknamei_sei' => 29,
    'szDaiknamei_mei' => 29,
    'sqAddress' => 50,
    'sofuKnjnam' => 30,
    'sofuTntnam' => 25,
    'syz' => 10,
);



// 半角文字
const KRNKKB_HALF_CHAR = array(
    'telNo' => 13,
    'keitaiNo' => 13,
    'setsurituNgt' => 6,
    'szTelno' => 13,
    'kmsTelno' => 13,
);




// 半角数字
const KRNKKB_HALF_NUM = array(
    'ybnNo' => 7,
    'shk' => 5,
    'nsyo' => 5,
    'kmssyainsu' => 5,
    'daiYbnno' => 7,
    'szYbnno' => 7,
    'sqYbnno' => 7,
);



//
define('KRNKKB_MAX_LEN_SHOHINMEI', 60);
////////////////////////////////////////////////////////////////////////////////////////
// 項目定義 EOF
////////////////////////////////////////////////////////////////////////////////////////