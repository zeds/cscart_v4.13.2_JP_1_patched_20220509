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

// $Id: krnkkb_manager.php by takahashi from cs-cart.jp 2016

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 送信データに注文IDが含まれる場合
    if( !empty($_REQUEST['order_ids']) ){

        // 一括処理用のコードを個別処理用のコードに変換
        $type = str_replace('bulk_', '', $mode);

        // 処理コードに応じて処理を実行
        switch($type){
            // 一括決済取消
            case 'kb_cancel':
                $is_valid_orders_exist = false;

                foreach ($_REQUEST['order_ids'] as $k => $v) {
                    // 決済取消依頼

                    $status_code = db_get_field("SELECT status_code FROM ?:jp_krnkkb_status WHERE order_id = ?i", $v);

                    if($status_code == 'KB_1') {
                        $is_valid_orders_exist = true;
                        fn_krnkkb_cancel($v);
                    }
                }
                // 処理を実行できる注文が存在しない場合
                if( !$is_valid_orders_exist ){
                    // 処理を実行できる注文が存在しないメッセージを表示
                    fn_set_notification('E', __('jp_kuroneko_kakebarai_error'), __('jp_kuroneko_kakebarai_data_not_exists'));
                }
                return array(CONTROLLER_STATUS_REDIRECT);
                break;
            default:
                // do nothing
        }
    }
    return array(CONTROLLER_STATUS_OK, "krnkkb_manager.manage");
}

$params = $_REQUEST;

switch($mode){
    // 決済取消
    case 'kb_cancel':
        // 決済取消依頼
        fn_krnkkb_cancel($_REQUEST['order_id']);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;

    // 一覧ページ
    case 'manage':
        $params['check_for_suppliers'] = true;
        $params['company_name'] = true;

        // クロネコ掛け払いで決済した注文の数を取得
        $krnkkb_total = db_get_field("SELECT COUNT(*) FROM ?:jp_krnkkb_status");

        // クロネコ掛け払いで決済した注文が存在する場合
        if( !empty($krnkkb_total) ){
            // クロネコ掛け払いを用いた注文を抽出
            list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

        // クロネコ掛け払いで決済した注文が存在しない場合
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
