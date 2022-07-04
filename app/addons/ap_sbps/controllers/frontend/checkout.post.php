<?php

use Tygh\Registry;
use Tygh\SbpsCredit;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if( $mode == 'checkout' ){
    $payment_methods = fn_get_payments(array('usergroup_ids' => $auth['usergroup_ids']));

    foreach ($payment_methods as $payment_method) {
        if ($payment_method['template'] === 'views/orders/components/payments/ap_sbps_rb_cctkn.tpl') {
            Registry::get('view')->assign('rb_processor_params', unserialize($payment_method['processor_params']));
        }

        if ($payment_method['template'] === 'views/orders/components/payments/ap_sbps_rp_cctkn.tpl') {
            Registry::get('view')->assign('rp_processor_params', unserialize($payment_method['processor_params']));
        }
    }
}
