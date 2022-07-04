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

// $Id: krnkwc_cvs_manager.php by tommy from cs-cart.jp 2016

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$params = $_REQUEST;


switch($mode){

    // 取引状況照会
    case 'wc_check_status':
        fn_krnkwc_get_trade_info($_REQUEST['order_id']);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;

    // 一覧ページ
    case 'manage':
        $params['check_for_suppliers'] = true;
        $params['company_name'] = true;

        // クロネコwebコレクト（コンビニ払い）で決済した注文の数を取得
        $krnkwc_total = db_get_field("SELECT COUNT(*) FROM ?:jp_krnkwc_cc_status WHERE status_code LIKE '%CVS_%'");

        // クロネコwebコレクト（コンビニ払い）で決済した注文が存在する場合
        if( !empty($krnkwc_total) ){
            // （コンビニ払い）を用いた注文を抽出
            list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);
        // クロネコwebコレクト（コンビニ払い）で決済した注文が存在しない場合
        }else{
            $orders = array();
        }

        Tygh::$app['view']->assign('orders', $orders);
        Tygh::$app['view']->assign('search', $search);
        break;
    // その他
    default:
        // do nothing
}
