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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// キャリア決済（テスト環境） : オンライン取引
define('SONYC_TEST_URL_OCAC010', 'https://www.test.e-scott.jp/online/cac/OCAC010.do');


// キャリア決済（本番環境） : オンライン取引
define('SONYC_LIVE_URL_OCAC010', 'https://www.e-scott.jp/online/cac/OCAC010.do');

// AES 暗号化・復号化ベクトル
define('SONYC_AES_IV', 'vsermgtwv38257fe');

// AES 暗号化・復号化メソッド
define('SONYC_AES_METHOD', 'aes-128-cbc');