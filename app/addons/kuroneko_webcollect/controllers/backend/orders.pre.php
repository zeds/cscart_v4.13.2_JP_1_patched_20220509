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

// Modified by tommy from cs-cart.jp 2016

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$params = $_REQUEST;

if ($mode == 'details') {

    // 支払方法に紐付けられた決済代行サービスの情報を取得
    $krnk_processor_data = fn_krnkwc_get_processor_data_by_order_id((int)$_REQUEST['order_id']);

    // 決済代行サービスを利用した注文の場合
    if( !empty($krnk_processor_data['processor_script']) ){
        // 決済用スクリプトファイル名を取得
        $krnk_processor_script = $krnk_processor_data['processor_script'];

        switch($krnk_processor_script){
            // クロネコwebコレクトの場合
            case 'krnkwc_cc.php':
            case 'krnkwc_ccreg.php':
            case 'krnkwc_cvs.php':
            case 'krnkwc_cctkn.php':
            case 'krnkwc_ccrtn.php':
                // 取引情報を照会
                $trade_info = fn_krnkwc_get_trade_info((int)$_REQUEST['order_id']);

                // 取引状況を取得できた場合
                if( isset($trade_info['statusInfo']) ){
                    // 取引状況名を取得
                    $wc_status_name = fn_krnkwc_wc_get_status_info_name($trade_info['statusInfo']);
                    // クロネコwebコレクトの取引状況をセット
                    if( !empty( $wc_status_name ) ) Tygh::$app['view']->assign('wc_status_name', $wc_status_name);
                }
                break;

            // クロネコ代金後払いサービスの場合
            case 'krnkab.php':
                // クロネコ代金後払いサービスの取引状況と警報情報を取得
                $_REQUEST['order_id'] = empty($_REQUEST['order_id']) ? 0 : $_REQUEST['order_id'];
                $ab_order_info = fn_krnkwc_get_ab_status($_REQUEST['order_id']);

                // クロネコ代金後払いサービスの取引状況と警報情報をセット
                if (!empty($ab_order_info['ab_order_status'])) Tygh::$app['view']->assign('ab_order_status', $ab_order_info['ab_order_status']);
                if (!empty($ab_order_info['ab_warning_info'])) Tygh::$app['view']->assign('ab_warning_info', $ab_order_info['ab_warning_info']);
                break;

            // その他の場合
            default:
                // do nothing
        }
    }
}