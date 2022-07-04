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

// $Id: config.php by tommy from cs-cart.jp 2014

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 顧客名の最大長
define('SMBC_MAXLEN_CNAME', 60);

// 顧客カナ名の最大長
define('SMBC_MAXLEN_CKANA', 60);

// 住所フィールドの最大長
define('SMBC_MAXLEN_ADDRESS', 50);

// 商品名の最大長
define('SMBC_MAXLEN_PNAME', 100);

// 消費税率
define('SMBC_VAT_RATE', 8);
