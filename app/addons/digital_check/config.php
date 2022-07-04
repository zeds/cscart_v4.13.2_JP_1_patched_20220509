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

// 顧客名の最大長
define('PAYDESIGN_MAXLEN_CNAME', 20);

// 住所フィールドの最大長
define('PAYDESIGN_MAXLEN_ADDRESS', 50);

// 商品名の最大長
define('PAYDESIGN_MAXLEN_PNAME', 50);

// ペイデザイン（クレジットカード[二段階方式]決済）の接続先URL
define('PAYDESIGN_URL_CC_2STEP', 'https://www.paydesign.jp/settle/settle3/credit/settle_cr_cpt25.do');

// ペイデザイン（CybperEdy決済/クレジットカード[リンク方式]）の接続先URL
define('PAYDESIGN_URL_CEDY', 'https://www.paydesign.jp/settle/settle3/bp3.dll');
