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

// $Id: init.php by tommy from cs-cart.jp 2015

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'change_order_status',
    'delete_order',
    'finish_payment',
    'get_orders',
    'get_payments_post',
    'order_placement_routines',
    'post_delete_user',
    'save_log',
    'set_admin_notification'
);
