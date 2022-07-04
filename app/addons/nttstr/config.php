<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 与信
define('NTTSTR_AUTH_OK', 50);

// 売上
define('NTTSTR_SALES_CONFIRMED', 51);

// 与信同時売上
define('NTTSTR_CAPTURE_OK', 52);

// 与信取消
define('NTTSTR_AUTH_CANCEL', 60);

// 売上取消
define('NTTSTR_SALES_CANCEL', 61);

// 商用URL
define('NTTSTR_LIVE_URL_EXECTRAN', 'https://www.chocom.net/direct/servlet/EPECredit');

// 検証URL
define('NTTSTR_TEST_URL_EXECTRAN', 'https://www.piggybank-dbg.jp/direct/servlet/EPECredit');