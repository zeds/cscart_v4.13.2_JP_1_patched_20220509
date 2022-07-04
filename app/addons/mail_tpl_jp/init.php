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

// $Id: init.php by tommy from cs-cart.jp 2017

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'delete_company',
    'delete_status_post',
    'jp_before_send_mail',
    'send_mail_pre',
    'update_company',
    'update_status_post'
);
