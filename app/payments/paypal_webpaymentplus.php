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

// $Id: paypal_webpaymentplus.php by tommy from cs-cart.jp 2013

use Tygh\Bootstrap;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Paypalからトランザクションコードが返された場合
if( !empty($_POST['pwpp_tx']) ){

	// トランザクションコードをサニタイズ
    $pwpp_tx = Bootstrap::safeInput($_POST['pwpp_tx']);

	// 注文ステータスとトランザクションコードをセット
	$pp_response = array();
	$pp_response['order_status'] = 'P';
	$pp_response['transaction_id'] = $pwpp_tx;
}
