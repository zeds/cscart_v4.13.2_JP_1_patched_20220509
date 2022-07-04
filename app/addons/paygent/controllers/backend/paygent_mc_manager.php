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

// $Id: paygent_mc_manager.php by tommy from cs-cart.jp 2016

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 送信データに注文IDが含まれる場合
    if( !empty($_REQUEST['order_ids']) ){

        // 一括処理用のコードを個別処理用のコードに変換
        $type = str_replace('bulk_', 'mc_', $mode);

        // 処理コードに応じて処理を実行
        switch($type){
            case 'mc_sales_confirm':    // 一括売上確定
            case 'mc_auth_cancel':      // 一括与信キャンセル
            case 'mc_sales_cancel':     // 一括売上キャンセル
                $is_valid_orders_exist = false;
                foreach ($_REQUEST['order_ids'] as $k => $v) {
                    if( fn_pygnt_send_cc_action($type, $v) ){
                        // 処理実行フラグをtrueにセット
                        $is_valid_orders_exist = true;
                    }
                }
                // 処理を実行できる注文が存在しない場合
                if( !$is_valid_orders_exist ){
                    // 処理を実行できる注文が存在しないメッセージを表示
                    fn_set_notification('E', __('jp_paygent_' . $type . '_error'), __('jp_paygent_mc_data_not_exists'));
                }
                return array(CONTROLLER_STATUS_REDIRECT);
                break;
            default:
                // do nothing
        }
    }
    return array(CONTROLLER_STATUS_OK, "paygent_cc_manager.manage");
}

$params = $_REQUEST;

switch($mode){

    case 'mc_sales_confirm':    // 売上確定
    case 'mc_auth_cancel':      // 与信キャンセル
    case 'mc_sales_cancel':     // 売上キャンセル
        fn_pygnt_send_cc_action($mode, $_REQUEST['order_id']);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;

    // 一覧ページ
    case 'manage':

        $params['check_for_suppliers'] = true;
        $params['company_name'] = true;

        // ペイジェント（クレジットカード決済）で決済した注文の数を取得
        $paygent_total = db_get_field("SELECT COUNT(*) FROM ?:jp_paygent_cc_status");

        // ペイジェント（クレジットカード決済）で決済した注文が存在する場合
        if( !empty($paygent_total) ){
            // クレジットカード決済を用いた注文を抽出
            list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

        // ペイジェント（クレジットカード決済）で決済した注文が存在しない場合
        }else{
            $orders = array();
        }

        Registry::get('view')->assign('orders', $orders);
        Registry::get('view')->assign('search', $search);
        break;

    // その他
    default:
        // do nothing
}
