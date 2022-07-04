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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (empty($auth['user_id'])) {
    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'add') {
        if (!empty($_REQUEST['payment_info']['card_nonce'])) {
            fn_create_square_customer_card($auth['user_id'], $_REQUEST['payment_info']);
        } else {
            fn_set_notification('E', __('error'), __('addons.sd_square_payment.empty_nonce'));
        }
    } elseif ($mode == 'delete') {
        if (!empty($_REQUEST['card_id'])) {
            fn_delete_square_castomer_card($auth['user_id'], $_REQUEST['card_id']);
        } else {
            fn_set_notification('E', __('error'), __('addons.sd_square_payment.card_was_not_deleted'));
        }
    }
    return array(CONTROLLER_STATUS_REDIRECT, 'square_cards.view');
}

if ($mode == 'view') {
    fn_add_breadcrumb(__('addons.sd_square_payment.saved_cards'));
    Tygh::$app['view']->assign('customer_cards', fn_get_square_customer_saved_cards(fn_get_user_short_info($auth['user_id'])));
}
