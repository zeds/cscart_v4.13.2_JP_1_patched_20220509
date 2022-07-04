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

// $Id: config.php by takahashi from cs-cart.jp 2019

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// AES 暗号化・復号化メソッド
define('SONYS_AES_METHOD', 'aes-128-cbc');

// 定期購入ステータス: 継続
define('SONYS_SUBSC_STATUS_A', 'A');

// 定期購入ステータス: 休止
define('SONYS_SUBSC_STATUS_P', 'P');

// 定期購入ステータス: 解約
define('SONYS_SUBSC_STATUS_D', 'D');