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

// $Id: pwpp_return.php by tommy from cs-cart.jp 2013

use Tygh\Bootstrap;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if( $mode == 'process' ){

	// Paypalから戻されたトランザクションIDを取得
    $tx = Bootstrap::safeInput($_REQUEST['tx']);

	if( !empty($tx) ){

		// 支払処理を行うために遷移するURL
		$pwpp_process_url = fn_url('checkout.place_order', AREA, 'current');

echo <<<EOT
<html>
<body onLoad="document.process.submit();">
<form action="{$pwpp_process_url}" method="POST" name="process">
<input type="hidden" name="pwpp_tx" value="$tx" />
</form>
</body>
</html>
EOT;

		exit();
	}
}
