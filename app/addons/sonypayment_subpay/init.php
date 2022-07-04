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

// $Id: init.php by takahashi from cs-cart.jp 2019

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'change_order_status',
    'checkout_select_default_payment_method',
    'delete_order',
    'finish_payment',
    'get_orders',
    'get_payments_post',
    'post_delete_user',
    'save_log',
    'update_product_post',
    'set_admin_notification'
);
