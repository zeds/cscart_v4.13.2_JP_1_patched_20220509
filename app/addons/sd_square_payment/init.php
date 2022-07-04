<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'get_user_short_info_pre',
    'change_order_status',
    'get_orders',
    'get_orders_post',
    'delete_order',
    'calculate_cart_items'
);