<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Registry;
use Tygh\Enum\SquareStatuses;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update_status_code') {
        if (!empty($_REQUEST['order_id']) && !empty($action)) {
            fn_update_square_payment_status($_REQUEST['order_id'], $action);
        } else {
            fn_set_notification('E', __('error'), __('addons.sd_square_payment.incorrect_request'));
        }
    }
    return array(CONTROLLER_STATUS_REDIRECT, 'square_cc_manager.manage');
} elseif ($mode == 'manage') {
    $params = $_REQUEST;
    $params['get_square_orders'] = true;
    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);
    Tygh::$app['view']->assign(array(
        'orders' => $orders,
        'search' => $search
    ));
    
}
