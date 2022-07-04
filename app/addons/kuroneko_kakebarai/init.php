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

// $Id: init.php by takahashi from cs-cart.jp 2018

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
	'checkout_select_default_payment_method',
    'create_shipment',
    'get_orders',
    'get_order_info',
    'save_log'
);
