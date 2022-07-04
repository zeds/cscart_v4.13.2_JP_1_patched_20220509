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

// Modified by tommy from cs-cart.jp 2017

$php_value = phpversion();
if (version_compare($php_value, '5.6.0') == -1) {
    ///////////////////////////////////////////////////////////////
    // Modified for Japanese Ver by tommy from cs-cart.jp 2017 BOF
    // PHPのバージョンに関するエラーメッセージを日本語化
    ///////////////////////////////////////////////////////////////
    //echo 'Currently installed PHP version (' . $php_value . ') is not supported. Minimal required PHP version is  5.6.0.';
    //die();
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    echo '<html xmlns="http://www.w3.org/1999/xhtml">';
    echo '<head>';
    echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
    echo '<title>CS-Cart日本語版インストール</title>';
    echo '</head>';
    echo '<body>';
    echo 'CS-Cartはお使いのPHPのバージョン (' . $php_value . ') ではご利用いただけません。 PHP 5.6.0 以上をご利用ください。';
    echo '</body>';
    die();
    ///////////////////////////////////////////////////////////////
    // Modified for Japanese Ver by tommy from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////////////////////
}

@include('run.php');

define('INSTALLER_STARTED', true);
