<?php

if ( !defined('BOOTSTRAP') ) { die('Access denied'); }

fn_register_hooks(
    'lcjp_check_payment_post',
    'create_order',
    'pre_get_orders',
    'get_orders',
    'get_payments_post',
    'order_placement_routines',
    'get_payment_processors_post',
    'replicate_order_post',
    'regular_purchase_cancel_contract_pre'
);
