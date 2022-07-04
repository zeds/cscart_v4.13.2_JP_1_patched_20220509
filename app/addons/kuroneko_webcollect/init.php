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

// $Id: init.php by tommy from cs-cart.jp 2016

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
	'finish_payment',
    'delete_order',
    'create_shipment',
    'create_shipment_post',
    'delete_shipments',
    'get_addons_mail_tpl',
    'get_payments_post',
    'get_orders',
    'get_order_info',
    'get_shipments_info_post',
    'jp_update_shipment',
    'mail_tpl_var_shipments_shipment_products',
    'save_log',
    'set_admin_notification',
    'calculate_cart'
);
