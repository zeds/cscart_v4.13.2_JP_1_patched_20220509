<?php

define('AREA', 'C');
require '../../init.php';

use Tygh\SbpsLink;

// 通信元IPアドレス確認
if (empty($_REQUEST['res_result']) || !preg_match('/^61\.215\.213\.47|^61\.215\.213\.20/', $_SERVER['REMOTE_ADDR'])) {
    echo 'INVALID ACCESS!!';
    exit;
}

// 初期化
$sbps_link = new SbpsLink();

// 注文ID取得・チェック
$order_id = $sbps_link->extract_order_id($_REQUEST['order_id']);
fn_payments_set_company_id($order_id);

if (empty($order_id)){
    render_exit("NG, NO ORDER ID FOUND, {$order_id}");
}

// 注文情報取得・チェック
$sql = 'SELECT order_id, user_id, total, status FROM ?:orders WHERE order_id = ?i';
if ($_REQUEST['res_result'] !== 'PY'){
    if (fn_allowed_for('MULTIVENDOR')) {
        $sql = "{$sql} AND status = 'N'";
    }else{
        $sql = "{$sql} AND (status = 'N' OR status = 'T')";
    }
}
$order = db_get_row($sql, $order_id);

if (empty($order)) {
    render_exit("NG, NO ORDER DATA FOUND, {$order_id}");
}

// 受信情報・支払方法チェック
if (!$sbps_link->valid_receive_data($order, $_REQUEST) || !fn_check_payment_script('ap_sbps_link.php', $order['order_id'])) {
    render_exit('NG, INVALID DATA RECEIVED');
}

// 支払い方法取得
$pay_method = $_REQUEST['res_pay_method'];

// 処理結果ステータスに応じて処理
switch ($_REQUEST['res_result']) {
    case 'OK':
        // リクルートかんたん支払いの場合、支払い方法を判別するため独自pay_methodを設定
        if ($pay_method === 'recruit') {
            $pay_method = explode(',', $_REQUEST['res_payinfo_key'])[0] === 'webcvs' ? 'recruito' : 'recruitc';
        }

        // 支払方法情報取得
        $process = fn_ap_sbps_get_pay_method_process($pay_method);

        // 処理情報保存
        fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $_REQUEST['res_tracking_id'], 'process' => $process]);

        // 楽天ペイ(オンライン決済)での確定通知の場合
        if ($pay_method === 'rakuten' && ($_REQUEST['res_payinfo_key'] === 'R02' || $_REQUEST['res_payinfo_key'] === 'R03')) {
            $payment_status = $_REQUEST['res_payinfo_key'] === 'R02' ? SBPS_PAYMENT_STATUS_AUTH_OK : SBPS_PAYMENT_STATUS_SALES_CONFIRM;
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], $process);
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, $process), $process);
            render_exit('OK');
        }

        // 決済・注文情報保存
        $order_info = fn_get_order_info($order_id);
        $payment_status = $sbps_link->get_pay_method_payment_status($pay_method);

        fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status, 'pay_method' => $pay_method], $process);
        fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, $process), $process);
        fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

        // 終了処理
        Tygh::$app['session']['ap_sbps_process_order'] = 'Y';
        fn_finish_payment($order_id, ['order_status' => $sbps_link->get_pay_method_order_status($pay_method)]);
        fn_order_placement_routines('route', $order_id);
        break;
    case 'PY':
        if (in_array($pay_method, ['webcvs', 'payeasy', 'banktransfer', 'recruit'], true) && !empty($_REQUEST['res_payinfo_key'])) {
            // 決済ステータスを判定
            $total = fn_ap_sbps_get_total($order_id);
            $amount_total = explode(',', $_REQUEST['res_payinfo_key'])[2];

            $payment_status = SBPS_OFFLINE_PAYMENT_STATUS_PAID;
            if ((int)$amount_total === 0) {
                $payment_status = SBPS_OFFLINE_PAYMENT_STATUS_UNPAID;
            } elseif (round($total) > (int)$amount_total) {
                $payment_status = SBPS_OFFLINE_PAYMENT_STATUS_PARTIAL_PAID;
            } elseif (round($total) < (int)$amount_total) {
                $payment_status = SBPS_OFFLINE_PAYMENT_STATUS_OVER_PAID;
            }

            // 注文ステータスを決済ステータスに応じて変更
            $order_info = fn_get_order_info($order_id);
            $notification_type = explode(',', $_REQUEST['res_payinfo_key'])[0];

            if (in_array($notification_type, ['P', 'D'], true) && in_array($order_info['status'], ['O', 'N'], true) && in_array($payment_status, [SBPS_OFFLINE_PAYMENT_STATUS_PAID, SBPS_OFFLINE_PAYMENT_STATUS_OVER_PAID])) {
                fn_change_order_status($order_id, 'P', '', ['C' => true, 'A' => true]);
            } elseif (in_array($notification_type, ['C', 'G'], true) && $order_info['status'] === 'P' && in_array($payment_status, [SBPS_OFFLINE_PAYMENT_STATUS_UNPAID, SBPS_OFFLINE_PAYMENT_STATUS_PARTIAL_PAID])) {
                fn_change_order_status($order_id, 'O', '', ['C' => true, 'A' => true]);
            }

            // 決済情報更新
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'offline');
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'offline'), 'offline');
            render_exit('OK');
        }
        break;
    case 'CN':
        if (in_array($pay_method, ['webcvs', 'payeasy', 'banktransfer', 'recruit'], true)) {
            // 決済ステータスをキャンセルに設定
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => SBPS_OFFLINE_PAYMENT_STATUS_CANCEL], 'offline');
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'offline'), 'offline');
            render_exit('OK');
        }
        break;
    case 'NG':
        if ($pay_method === 'rakuten' && ($_REQUEST['res_payinfo_key'] === 'R02' || $_REQUEST['res_payinfo_key'] === 'R03')) {
            // 決済情報更新
            $payment_status = $_REQUEST['res_payinfo_key'] === 'R02' ? SBPS_PAYMENT_STATUS_AMOUNT_CHANGE_ERROR : SBPS_PAYMENT_STATUS_SALES_CONFIRM_ERROR;
            fn_ap_sbps_set_sbps_payment_info($order_id, ['payment_status' => $payment_status], 'wallet');
            fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, 'wallet'), 'wallet');
            render_exit('OK');
        }
        break;
    default:
        break;
}

// エラーメッセージを返す
render_exit('NG, INVALID DATA RECEIVED');

// 終了処理
function render_exit($output) {
    echo $output;
    exit;
}