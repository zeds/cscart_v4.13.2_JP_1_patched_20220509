<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2004 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: jp_version.php by tommy from cs-cart.jp 2016

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// CS-Cart日本語版のバージョンを表示
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
echo '<html xmlns="http://www.w3.org/1999/xhtml">';
echo '<head>';
echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
echo '<title>CS-Cart日本語版 - バージョンチェック</title>';
echo '</head>';
echo '<body>';
echo '<b>CS-Cart v' . PRODUCT_VERSION . '</b>';
if( PRODUCT_BUILD ){
echo '<b>（ビルド：' . PRODUCT_BUILD . '）</b>';
}
echo '</body>';
die();