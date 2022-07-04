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

// $Id: config.php by tommy from cs-cart.jp 2015

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// カード決済（テスト環境） : 取引登録用URL
define('GMOMP_TEST_URL_ENTRYTRAN', 'https://pt01.mul-pay.jp/payment/EntryTran.idPass');

// カード決済（テスト環境） : 決済実行用URL
define('GMOMP_TEST_URL_EXECTRAN', 'https://pt01.mul-pay.jp/payment/ExecTran.idPass');

// カード決済（テスト環境） : 3D認証決済実行用URL
define('GMOMP_TEST_URL_SECURETRAN', 'https://pt01.mul-pay.jp/payment/SecureTran.idPass');

// カード決済（テスト環境） : 会員登録用URL
define('GMOMP_TEST_URL_SAVEMEMBER', 'https://pt01.mul-pay.jp/payment/SaveMember.idPass');

// カード決済（テスト環境） : 会員検索用URL
define('GMOMP_TEST_URL_SEARCHMEMBER', 'https://pt01.mul-pay.jp/payment/SearchMember.idPass');

// カード決済（テスト環境） : 決済後カード登録URL
define('GMOMP_TEST_URL_TRADEDCARD', 'https://pt01.mul-pay.jp/payment/TradedCard.idPass');

// カード決済（テスト環境） : 決済取消、再オーソリ、売上確定URL
define('GMOMP_TEST_URL_ALTERTRAN', 'https://pt01.mul-pay.jp/payment/AlterTran.idPass');

// カード決済（テスト環境） : 金額変更URL
define('GMOMP_TEST_URL_CHANGETRAN', 'https://pt01.mul-pay.jp/payment/ChangeTran.idPass');

// カード決済（テスト環境） : 登録済みカード削除URL
define('GMOMP_TEST_URL_DELETECARD', 'https://pt01.mul-pay.jp/payment/DeleteCard.idPass');

// カード決済（テスト環境） : 登録済みカード照会URL
define('GMOMP_TEST_URL_SEARCHCARD', 'https://pt01.mul-pay.jp/payment/SearchCard.idPass');

// カード決済（テスト環境） : 取引照会用URL
define('GMOMP_TEST_URL_SEARCHTRAN', 'https://pt01.mul-pay.jp/payment/SearchTrade.idPass');

// コンビニ決済（テスト環境） : 取引登録用URL
define('GMOMP_TEST_URL_ENTRYTRANCVS', 'https://pt01.mul-pay.jp/payment/EntryTranCvs.idPass');

// コンビニ決済（テスト環境） : 決済実行用URL
define('GMOMP_TEST_URL_EXECTRANCVS', 'https://pt01.mul-pay.jp/payment/ExecTranCvs.idPass');

// ペイジー決済（テスト環境） : 取引登録用URL
define('GMOMP_TEST_URL_ENTRYTRANPAYEASY', 'https://pt01.mul-pay.jp/payment/EntryTranPayEasy.idPass');

// ペイジー決済（テスト環境） : 決済実行用URL
define('GMOMP_TEST_URL_EXECTRANPAYEASY', 'https://pt01.mul-pay.jp/payment/ExecTranPayEasy.idPass');


// カード決済（本番環境） : 取引登録用URL
define('GMOMP_LIVE_URL_ENTRYTRAN', 'https://p01.mul-pay.jp/payment/EntryTran.idPass');

// カード決済（本番環境） : 決済実行用URL
define('GMOMP_LIVE_URL_EXECTRAN', 'https://p01.mul-pay.jp/payment/ExecTran.idPass');

// カード決済（本番環境） : 3D認証決済実行用URL
define('GMOMP_LIVE_URL_SECURETRAN', 'https://p01.mul-pay.jp/payment/SecureTran.idPass');

// カード決済（本番環境） : 会員登録用URL
define('GMOMP_LIVE_URL_SAVEMEMBER', 'https://p01.mul-pay.jp/payment/SaveMember.idPass');

// カード決済（本番環境） : 会員検索用URL
define('GMOMP_LIVE_URL_SEARCHMEMBER', 'https://p01.mul-pay.jp/payment/SearchMember.idPass');

// カード決済（本番環境） : 決済後カード登録URL
define('GMOMP_LIVE_URL_TRADEDCARD', 'https://p01.mul-pay.jp/payment/TradedCard.idPass');

// カード決済（本番環境） : 決済取消、再オーソリ、売上確定URL
define('GMOMP_LIVE_URL_ALTERTRAN', 'https://p01.mul-pay.jp/payment/AlterTran.idPass');

// カード決済（本番環境） : 金額変更URL
define('GMOMP_LIVE_URL_CHANGETRAN', 'https://p01.mul-pay.jp/payment/ChangeTran.idPass');

// カード決済（本番環境） : 登録済みカード削除URL
define('GMOMP_LIVE_URL_DELETECARD', 'https://p01.mul-pay.jp/payment/DeleteCard.idPass');

// カード決済（本番環境） : 登録済みカード照会URL
define('GMOMP_LIVE_URL_SEARCHCARD', 'https://p01.mul-pay.jp/payment/SearchCard.idPass');

// カード決済（本番環境） : 取引照会用URL
define('GMOMP_LIVE_URL_SEARCHTRAN', 'https://p01.mul-pay.jp/payment/SearchTrade.idPass');

// コンビニ決済（本番環境） : 取引登録用URL
define('GMOMP_LIVE_URL_ENTRYTRANCVS', 'https://p01.mul-pay.jp/payment/EntryTranCvs.idPass');

// コンビニ決済（本番環境） : 決済実行用URL
define('GMOMP_LIVE_URL_EXECTRANCVS', 'https://p01.mul-pay.jp/payment/ExecTranCvs.idPass');

// ペイジー決済（本番環境） : 取引登録用URL
define('GMOMP_LIVE_URL_ENTRYTRANPAYEASY', 'https://p01.mul-pay.jp/payment/EntryTranPayEasy.idPass');

// ペイジー決済（本番環境） : 決済実行用URL
define('GMOMP_LIVE_URL_EXECTRANPAYEASY', 'https://p01.mul-pay.jp/payment/ExecTranPayEasy.idPass');
