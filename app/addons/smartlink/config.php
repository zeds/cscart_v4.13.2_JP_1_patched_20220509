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

// ユーザー漢字氏名の最大長
define('SLN_MAXLEN_NAME_KANJI', 40);

// ユーザーカナ氏名の最大長
define('SLN_MAXLEN_NAME_KANA', 40);

// 収納代行サービスのリダイレクトURL（テスト環境）
define('SLN_DAIKO_REDIRECT_URL_TEST', 'https://link.kessai.info/JLPCT/JLPcon');

// 収納代行サービスのリダイレクトURL（本番環境）
define('SLN_DAIKO_REDIRECT_URL_LIVE', 'http://link.kessai.info/JLP/JLPcon');

// AES 暗号化・復号化メソッド
define('SLN_AES_METHOD', 'aes-128-cbc');