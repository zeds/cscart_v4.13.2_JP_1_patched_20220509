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
if (fn_allowed_for('MULTIVENDOR')) {
    $sql = "{$sql} AND status = 'N'";
}else{
    $sql = "{$sql} AND (status = 'N' OR status = 'T')";
}
$order = db_get_row($sql, $order_id);

if (empty($order)) {
    render_exit("NG, NO ORDER DATA FOUND, {$order_id}");
}

// 受信情報・支払方法チェック
if (!$sbps_link->valid_receive_data($order, $_REQUEST) || !fn_check_payment_script('ap_sbps_rb_link.php', $order['order_id'])) {
    render_exit('NG, INVALID DATA RECEIVED');
}

// 支払い方法取得
$pay_method = $_REQUEST['res_pay_method'];

// リクルートかんたん支払いの場合、支払い方法を判別するため独自pay_methodを設定
if ($pay_method === 'recruit') {
    $pay_method = 'recruitc';
}

// 処理結果ステータスに応じて処理
switch ($_REQUEST['res_result']) {
    case 'OK':
        // 注文・支払方法情報取得
        $order_info = fn_get_order_info($order_id);
        $process = fn_ap_sbps_get_pay_method_process($pay_method);
        $process = "rb_{$process}";

        // 処理情報保存
        fn_ap_sbps_set_sbps_process_info($order_id, ['tracking_id' => $_REQUEST['res_tracking_id'], 'process' => $process]);

        // 決済・注文情報保存
        fn_ap_sbps_set_sbps_payment_info($order_id, ['is_charge' => true, 'pay_method' => "rb_{$pay_method}"], $process);
        fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, $process), $process);
        fn_ap_sbps_set_sbps_order_data($order_id, $order_info);

        // 終了処理
        Tygh::$app['session']['ap_sbps_process_order'] = 'Y';
        fn_finish_payment($order_id, ['order_status' => 'P']);
        fn_order_placement_routines('route', $order_id);
        break;
    case 'CR':
    case 'CC':
    case 'CL':
        // 支払方法情報取得
        $process = fn_ap_sbps_get_pay_method_process($pay_method);
        $process = "rb_{$process}";

        // 課金状態を解約済に変更
        fn_ap_sbps_update_rb_cancel_contract($order_id, $process);
        render_exit('OK');
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