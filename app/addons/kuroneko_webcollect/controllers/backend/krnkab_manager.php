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

// $Id: krnkwc_cc_manager.php by tommy from cs-cart.jp 2016

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
            case 'ab_cancel':
                $is_valid_orders_exist = false;

                foreach ($_REQUEST['order_ids'] as $k => $v) {
                    // 指定した処理を行うのに適した注文であるかを判定
                    $is_valid_order = fn_krnkwc_check_process_validity($v, $type);

                    // 指定した処理を行うのに適した注文の場合
                    if ( $is_valid_order ){
                        // 決済取消依頼
                        $is_valid_orders_exist = true;
                        fn_krnkwc_ab_cancel($v, false);
                    }
                }
                // 処理を実行できる注文が存在しない場合
                if( !$is_valid_orders_exist ){
                    // 処理を実行できる注文が存在しないメッセージを表示
                    fn_set_notification('E', __('jp_kuroneko_webcollect_' . $type . '_error'), __('jp_kuroneko_webcollect_ab_data_not_exists'));
                }
                return array(CONTROLLER_STATUS_REDIRECT);
                break;
            default:
                // do nothing
        }
    }
    return array(CONTROLLER_STATUS_OK, "krnkab_manager.manage");
}

$params = $_REQUEST;

switch($mode){
    // 決済取消
    case 'ab_cancel':
        // 決済取消依頼
        fn_krnkwc_ab_cancel($_REQUEST['order_id']);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;

    // 取引状況照会
    case 'ab_check_status':
        fn_krnkwc_get_ab_status($_REQUEST['order_id']);
        return array(CONTROLLER_STATUS_REDIRECT);
        break;

    // クロンジョブによる取引状況更新
    case 'cron_status_update_ab':
        // 登録されたクロン用パスワードを取得
        $cron_password = Registry::get('addons.kuroneko_webcollect.cron_password_ab');

        // URLに含まれるパスワードと登録されたクロン用パスワードが一致しない場合は処理を終了
        if ((!isset($_REQUEST['cron_password']) || $cron_password != $_REQUEST['cron_password']) && (!empty($cron_password))) {
            die(__('access_denied'));
        }

        // 取引状況を一括更新
        fn_krnkwc_get_mass_ab_status();
        exit();
        break;

    // 一覧ページ
    case 'manage':
        $params['check_for_suppliers'] = true;
        $params['company_name'] = true;

        // クロネコ代金後払いサービスで決済した注文の数を取得
        $krnkab_total = db_get_field("SELECT COUNT(*) FROM ?:jp_krnkwc_cc_status WHERE status_code LIKE ?s", "%AB_%");

        // クロネコ代金後払いサービスで決済した注文が存在する場合
        if( !empty($krnkab_total) ){
            // クロネコ代金後払いサービスを用いた注文を抽出
            list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_orders_per_page'), true);

        // クロネコwebコレクト（クレジットカード決済）で決済した注文が存在しない場合
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
