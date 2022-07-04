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

// $Id: localization_jp.php by takahashi from cs-cart.jp 2020

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $lcjp = $_REQUEST['lcjp'];

    foreach($lcjp as $key=>$value){
        db_query("REPLACE INTO ?:jp_settings(name, value) values(?s, ?s)", $key, $value);
    }

    fn_set_notification('N', __('notice'), __('jp_notice_settings_save_complete'));

	return array(CONTROLLER_STATUS_OK, "localization_jp.manage");
}

if ($mode == 'manage') {
    $lcjp_data = db_get_array("SELECT name, value FROM ?:jp_settings");

    foreach($lcjp_data as $data){
        $lcjp_settings[$data['name']] = $data['value'];
    }

    Registry::get('view')->assign('lcjp', $lcjp_settings);
}
