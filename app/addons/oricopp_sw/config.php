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

// HTTP POST送信先URL
define('OPPSW_URL_POST', 'https://pay.veritrans.co.jp/web1/commodityRegist.action');

// VTW決済サイトURL
define('OPPSW_URL_PAYMENT', 'https://pay.veritrans.co.jp/web1/deviceCheck.action');

// 商品IDの最大長
define('OPPSW_COMMODITY_ID_MAXLEN', 15);
