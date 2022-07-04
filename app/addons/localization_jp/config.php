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

// CS-Cart日本語サイトのURL
define('JP_URL_CSCART_WEBSITE', 'http://www.cs-cart.jp/');

// CS-Cart日本語サイトのURL
define('JP_URL_CSCART_CONTACT', JP_URL_CSCART_WEBSITE . 'send-message.html');

// CS-CartマーケットのURL
define('JP_URL_CSCART_MARKET', 'http://store.cs-cart.jp');

// ゼウスに送信する注文IDと顧客IDのセパレータ
define('ZEUS_SEPARATOR', '_ZS_');
