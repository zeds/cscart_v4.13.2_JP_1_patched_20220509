<?php

// *** 関数名の命名ルール ***
// 混乱を避けるため、フックポイントで動作する関数とその他の命名ルールを明確化する。
// (1) init.phpで定義ししたフックポイントで動作する関数：ap_final_confirmation_[フックポイント名]
// (2) addons.xmlで定義した設定項目で動作する関数：fn_settings_variants_addons_ap_final_confirmation_[アイテムID]
// (3) (1)以外の関数：fn_[アドオンの名称]_[任意の名称]

use Tygh\Registry;
use Tygh\SbpsRpCareer;
use Tygh\SbpsRpCredit;
use Tygh\SbpsRpWallet;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * lcjp_check_payment_post hook
 *
 * @param $payment
 * @param $pr_script
 * @param $result
 */
function fn_ap_sbps_lcjp_check_payment_post($payment, $pr_script, &$result)
{
    if (in_array($pr_script, ['ap_sbps_cctkn.php', 'ap_sbps_rb_cctkn.php', 'ap_sbps_rp_cctkn.php'])) {
        $result = true;
    }
}

/**
 * create_order hook
 *
 * @param $order
 */
function fn_ap_sbps_create_order(&$order)
{
    // 継続課金アドオンがインストールされていない場合、処理終了
    if (empty(Registry::get('addons.subscription_payment_jp')) || Registry::get('addons.subscription_payment_jp.status') !== 'A') {
        return;
    }

    // 継続課金商品の場合
    if (fn_subpay_jp_is_subscription_products($order['products'])) {
        $attr = [
            'is_subscription' => true,
            'rb_status' => RB_STATUS_CHARGE,
        ];

        $order = array_merge($order, $attr);
    }
}

/**
 * pre_get_orders hook
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $get_totals
 * @param $lang_code
 */
function fn_ap_sbps_pre_get_orders($params, $fields, &$sortings, $get_totals, $lang_code)
{
    $table_data = SBPS_MANGER_CONTROLLER_TABLE_DATA[Registry::get('runtime.controller')];

    if (Registry::get('runtime.mode') === 'manage' && !empty($table_data['sorting_fields'])) {
        $table = $table_data['table'];

        foreach ($table_data['sorting_fields'] as $sorting_field) {
            $sortings[$sorting_field] = "?:{$table}.{$sorting_field}";
        }
    }
}

/**
 * get_orders hook
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $group
 */
function fn_ap_sbps_get_orders($params, &$fields, $sortings, &$condition, &$join, $group)
{
    $table_data = SBPS_MANGER_CONTROLLER_TABLE_DATA[Registry::get('runtime.controller')];

    // 継続課金アドオンがインストールされていない場合、フィールド、ソート条件追加
    if (!empty(Registry::get('addons.subscription_payment_jp')) && Registry::get('addons.subscription_payment_jp.status') === 'A') {
        // 取得するフィールド追加
        $fields = array_merge($fields, [
            '?:orders.is_subscription',
            '?:orders.rb_status',
        ]);

        // ソート条件追加
        $sortings = array_merge($sortings, [
            '?:orders.rb_status'
        ]);
    }

    if (Registry::get('runtime.mode') === 'manage' && !empty($table_data)) {
        $table = $table_data['table'];

        // 対象の注文情報のみ取得
        $sql = "SELECT order_id FROM ?:{$table}";

        // view_typeの指定がある場合
        if (!empty($table_data['view_type'])) {
            if (!empty($table_data['view_type'][$params['view_type']])) {
                $sql .= db_quote(' WHERE pay_method IN (?a)', $table_data['view_type'][$params['view_type']]);
            } else {
                $sql .= ' WHERE 1=0';
            }
        }

        $order_ids = db_get_fields($sql);
        if (!empty($order_ids)) {
            $condition .= db_quote(' AND ?:orders.order_id IN (?a)', is_array($order_ids) ? $order_ids : [$order_ids]);
        } else {
            $condition .= db_quote(' AND 1=0');
        }

        // 取得するフィールド追加
        if (!empty($table_data['fields'])) {
            $add_fields = array_map(function($field) use ($table) {
                return "?:{$table}.{$field} as {$field}";
            }, $table_data['fields']);
            $fields = array_merge($fields, $add_fields);
        }

        // 検索条件追加
        if (!empty($params['payment_status'])) {
            $condition .= db_quote(" AND ?:{$table}.payment_status IN (?a)", $params['payment_status']);
        }
        if (!empty($params['is_charge'])) {
            $condition .= db_quote(" AND ?:{$table}.is_charge IN (?a)", $params['is_charge']);
        }

        // テーブル結合
        $join .= " LEFT JOIN ?:{$table} ON ?:{$table}.order_id = ?:orders.order_id";
    }
}

/**
 * get_payments_post hook
 *
 * @param $params
 * @param $payments
 */
function fn_ap_sbps_get_payments_post($params, &$payments)
{
    $delete_processor_ids = [];

    if (AREA == 'C') {
        $user_id = !empty(Tygh::$app['session']['auth']['user_id']) ? Tygh::$app['session']['auth']['user_id'] : null;

        // マーケットプレイスの場合
        if (fn_allowed_for('MULTIVENDOR')) {
            $delete_processor_ids = array_merge($delete_processor_ids, ['9247', '9244', '9245', '9246']);
        }

        // ソフトバンク・ペイメント・サービス（登録済みカード決済)の除外
        if (empty($user_id) || !fn_ap_sbps_valid_quickpay($user_id)) {
            $delete_processor_ids = array_merge($delete_processor_ids, ['9242']);
        }

        // 継続課金アドオンがインストールされていない、または、無効になっている場合
        if (empty(Registry::get('addons.subscription_payment_jp')) || Registry::get('addons.subscription_payment_jp.status') !== 'A') {
            $delete_processor_ids = array_merge($delete_processor_ids, ['9243', '9244']);
        }

        // 定期購入アドオンがインストールされていない、または、無効になっている場合、
        if (empty(Registry::get('addons.ap_regular_purchases')) || Registry::get('addons.ap_regular_purchases.status') !== 'A') {
            $delete_processor_ids = array_merge($delete_processor_ids, ['9245', '9246']);
        }
    } elseif (AREA == 'A' && Registry::get('runtime.controller') === 'order_management') {
        if (Registry::get('runtime.mode') === 'add') {
            $delete_processor_ids = ['9247', '9241', '9242', '9243', '9244', '9245', '9246'];
        } elseif(Registry::get('runtime.mode') === 'update') {
            $payment_id = Tygh::$app['session']['cart']['payment_id'];

            if (!empty($payment_id)) {
                $payments = array_filter($payments, function ($payment) use ($payment_id) {
                    return $payment['payment_id'] === $payment_id || !in_array($payment['processor_id'], ['9247', '9241', '9242', '9243', '9244', '9245', '9246'], true);
                });
            }
        }
    }

    if (!empty($delete_processor_ids)) {
        $delete_processor_ids = array_unique($delete_processor_ids);

        $payments = array_filter($payments, function ($payment) use ($delete_processor_ids) {
            return !in_array($payment['processor_id'], $delete_processor_ids, true);
        });
    }
}

/**
 * order_placement_routines hook
 *
 * @param $order_id
 * @param $force_notification
 * @param $order_info
 */
function fn_ap_sbps_order_placement_routines($order_id, $force_notification, $order_info)
{
    if (!empty(Tygh::$app['session']['ap_sbps_process_order']) && Tygh::$app['session']['ap_sbps_process_order'] === 'Y') {
        unset(Tygh::$app['session']['ap_sbps_process_order']);

        // 注文情報から決済代行業者のIDを取得
        $processor_id = $order_info['payment_method']['processor_id'];

        // 決済代行業者を使った決済の場合
        if (!empty($processor_id) && $processor_id > 0){

            // 決済代行業者のスクリプトファイル名を取得
            $processor_script = db_get_field('SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);

            // 決済代行業者のスクリプトファイル名がSBPSで利用するものと同一の場合、「OK」を出力
            if(in_array($processor_script, ['ap_sbps_link.php', 'ap_sbps_rb_link.php', 'ap_sbps_rp_link.php'], true)){
                echo 'OK';
                exit;
            }
        }
    }
}

/**
 * get_payment_processors_post hook
 *
 * @param $lang_code
 * @param $processors
 */
function fn_ap_sbps_get_payment_processors_post($lang_code, &$processors)
{
    $delete_processor_ids = [];

    // マーケットプレイスの場合
    if (fn_allowed_for('MULTIVENDOR')) {
        $delete_processor_ids = array_merge($delete_processor_ids, [9247, 9244, 9245, 9246]);
    }

    // 継続課金アドオンがインストールされていない、または、無効になっている場合
    if (empty(Registry::get('addons.subscription_payment_jp')) || Registry::get('addons.subscription_payment_jp.status') !== 'A') {
        $delete_processor_ids = array_merge($delete_processor_ids, [9243, 9244]);
    }

    // 定期購入アドオンがインストールされていない、または、無効になっている場合、
    if (empty(Registry::get('addons.ap_regular_purchases')) || Registry::get('addons.ap_regular_purchases.status') !== 'A') {
        $delete_processor_ids = array_merge($delete_processor_ids, [9245, 9246]);
    }

    if (!empty($delete_processor_ids)) {
        $delete_processor_ids = array_unique($delete_processor_ids);

        foreach ($delete_processor_ids as $delete_processor_id) {
            unset($processors[$delete_processor_id]);
        }
    }
}

/**
 * replicate_order_post hook
 *
 * @param $order_id
 * @param $new_order_id
 */
function fn_ap_sbps_replicate_order_post($order_id, $new_order_id)
{
    $process_info = fn_ap_sbps_get_process_info($order_id);

    if (!empty($process_info)) {
        // 処理情報作成
        db_query('INSERT INTO ?:sbps_process_info ?e', [
            'order_id' => $new_order_id,
            'master_order_id' => !empty($process_info['master_order_id']) ? $process_info['master_order_id'] : $order_id,
            'process' => $process_info['process']
        ]);

        // 決済情報作成
        $payment_info = fn_ap_sbps_get_payment_info($order_id, $process_info['process']);
        db_query("INSERT INTO ?:sbps_{$process_info['process']}_payment_info ?e", [
            'order_id' => $new_order_id,
            'pay_method' => $payment_info['pay_method']
        ]);

        // 注文情報を更新
        fn_ap_sbps_set_order_data($new_order_id, ['pay_method' => $payment_info['pay_method']], $process_info['process']);
    }
}

/**
 * regular_purchase_cancel_contract_pre hook
 *
 * @param $order_id
 */
function fn_ap_sbps_regular_purchase_cancel_contract_pre($order_id)
{
    $process_info = fn_ap_sbps_get_process_info($order_id);

    // 処理情報が取得できない場合、処理終了
    if (empty($process_info)) {
        fn_set_notification('E', __('error'), __('sbps_error_rp_cancel_contract'));
        fn_redirect("orders.details?order_id={$order_id}", true);
    }

    $pay_method = fn_ap_sbps_get_pay_method($order_id, $process_info['process']);

    // クレジット決済、ソフトバンクまとめて支払い(B)以外は解約要求
    if (!in_array($pay_method, ['rp_credit', 'rp_credit3d', 'rp_softbank2'], true)) {
        $sbps_obj = null;

        $processor_data = fn_ap_sbps_get_processor_data($order_id);
        $process = $process_info['process'];

        switch ($process) {
            case 'rp_career':
                $sbps_obj = new SbpsRpCareer($order_id, $processor_data['processor_params']);
                break;
            case 'rp_credit':
                $sbps_obj = new SbpsRpCredit($order_id, $processor_data['processor_params']);
                break;
            case 'rp_wallet':
                $sbps_obj = new SbpsRpWallet($order_id, $processor_data['processor_params']);
                break;
            default:
                fn_set_notification('E', __('error'), __('sbps_error_rp_cancel_contract'));
                fn_redirect("orders.details?order_id={$order_id}", true);
        }

        $master_order_id = !empty($process_info['master_order_id']) ? $process_info['master_order_id'] : $order_id;
        $master_tracking_id = fn_ap_sbps_get_master_tracking_id($master_order_id);

        $sbps_obj->cancel_contract_request($master_tracking_id, $pay_method);
        if (!empty($sbps_obj->errors)) {
            fn_set_notification('E', __('error'), __('sbps_error_rp_cancel_contract'));
            fn_redirect("orders.details?order_id={$order_id}", true);
        }
    }

    fn_ap_sbps_update_rp_cancel_contract($order_id, $process_info);
}


/**
 * 注文情報存在確認
 *
 * @param $order_id
 * @param $add_sql
 * @return bool
 */
function fn_ap_sbps_exists_order($order_id, $add_sql = '')
{
    $sql = 'SELECT order_id FROM ?:orders WHERE order_id = ?i';

    if (!empty($add_sql)) {
        $sql = "{$sql}{$add_sql}";
    }

    $order = db_get_array($sql, $order_id);
    return !empty($order);
}

/**
 * 決済情報存在確認
 *
 * @param $order_id
 * @param $process
 * @return bool
 */
function fn_ap_sbps_exists_payment_info($order_id, $process)
{
    $table = "sbps_{$process}_payment_info";

    $payment_info = db_get_array("SELECT * FROM ?:{$table} WHERE order_id = ?i", $order_id);
    return !empty($payment_info);
}

/**
 * 注文情報のユーザーID取得
 *
 * @param $order_id
 * @return array
 */
function fn_ap_sbps_get_order_user_id($order_id)
{
    return db_get_field('SELECT user_id FROM ?:orders WHERE order_id = ?i', $order_id);
}

/**
 * 購入用ID取得
 *
 * @param $order_id
 * @return array
 */
function fn_ap_sbps_get_tracking_id($order_id)
{
    return db_get_field('SELECT tracking_id FROM ?:sbps_process_info WHERE order_id = ?i', $order_id);
}

/**
 * 購入用ID(定期購入)取得
 *
 * @param $order_id
 * @return array
 */
function fn_ap_sbps_get_master_tracking_id($order_id)
{
    return db_get_field('SELECT master_tracking_id FROM ?:sbps_process_info WHERE order_id = ?i', $order_id);
}

/**
 * 定期購入申請時のトラッキングID取得
 *
 * @param $order_id
 * @return array
 */
function fn_ap_sbps_get_application_tracking_id($order_id)
{
    return db_get_field('SELECT application_tracking_id FROM ?:sbps_process_info WHERE order_id = ?i', $order_id);
}

/**
 * 合計金額取得
 *
 * @param $order_id
 * @return array
 */
function fn_ap_sbps_get_total($order_id)
{
    return db_get_field('SELECT total FROM ?:sbps_order_data WHERE order_id = ?i', $order_id);
}

/**
 * 手続方法取得
 *
 * @param $order_id
 * @return array
 */
function fn_ap_sbps_get_process($order_id)
{
    return db_get_field('SELECT process FROM ?:sbps_process_info WHERE order_id = ?i', $order_id);
}

/**
 * 支払い方法取得
 *
 * @param $order_id
 * @param string $process
 * @return string|null
 */
function fn_ap_sbps_get_pay_method($order_id, $process = '')
{
    $pay_method = null;

    if (empty($process)) {
        $process = db_get_field('SELECT process FROM ?:sbps_process_info WHERE order_id = ?i', $order_id);
    }

    if (!empty($process)) {
        $payment_info = fn_ap_sbps_get_payment_info($order_id, $process);
        $pay_method = array_key_exists('pay_method', $payment_info) ? $payment_info['pay_method'] : $process;
    }

    return $pay_method;
}

/**
 * 複数回返金用の枝番取得
 *
 * @param $order_id
 * @param $process
 * @return array
 */
function fn_ap_sbps_get_refund_rowno($order_id, $process)
{
    $table = "sbps_{$process}_payment_info";
    return db_get_field("SELECT refund_rowno FROM ?:{$table} WHERE order_id = ?i", $order_id);
}

/**
 * 処理情報取得
 *
 * @param $order_id
 * @return array|null
 */
function fn_ap_sbps_get_process_info($order_id)
{
    return db_get_row('SELECT * FROM ?:sbps_process_info WHERE order_id = ?i', $order_id);
}

/**
 * 決済情報取得
 *
 * @param $order_id
 * @param string $process
 * @return array|null
 */
function fn_ap_sbps_get_payment_info($order_id, $process)
{
    $table = "sbps_{$process}_payment_info";
    return db_get_row("SELECT * FROM ?:{$table} WHERE order_id = ?i", $order_id);
}

/**
 * 注文情報取得
 *
 * @param $order_id
 * @return null|array
 */
function fn_ap_sbps_get_order_data($order_id)
{
    return db_get_row('SELECT * FROM ?:sbps_order_data WHERE order_id = ?i', $order_id);
}

/**
 * 支払方法情報取得
 *
 * @param $order_id
 * @return array
 */
function fn_ap_sbps_get_processor_data($order_id)
{
    $payment_id = db_get_field('SELECT payment_id FROM ?:orders WHERE order_id = ?i', $order_id);
    return fn_get_payment_method_data($payment_id);
}

/**
 * カード預かりの支払方法情報取得
 *
 * @param $user_id
 * @return array|null
 */
function fn_ap_sbps_get_quickpay_payment_method_data($user_id)
{
    $result = null;

    $quickpay_data = db_get_array('SELECT * FROM ?:sbps_quickpay_data WHERE user_id = ?i', $user_id);

    if (!empty($quickpay_data)) {
        $payment_method_data = fn_get_payment_method_data($quickpay_data[0]['payment_id']);

        if (!empty($payment_method_data) && $payment_method_data['status'] === 'A') {
            $result = $payment_method_data;
        }
    }

    return $result;
}

/**
 * 購入方法毎の処理方法取得
 *
 * @param $pay_method
 * @return int|string|null
 */
function fn_ap_sbps_get_pay_method_process($pay_method)
{
    $result = null;

    foreach (SBPS_PAY_METHOD_PROCESS as $process => $pay_methods) {
        if (in_array($pay_method, $pay_methods)) {
            $result = $process;
            break;
        }
    }

    return $result;
}

/**
 * 処理情報作成・更新
 *
 * @param $order_id
 * @param $attr
 */
function fn_ap_sbps_set_sbps_process_info($order_id, $attr)
{
    if (!empty($order_id)) {
        $process_info = db_get_row('SELECT order_id FROM ?:sbps_process_info WHERE order_id = ?i', $order_id);

        if (empty($process_info)) {
            db_query('INSERT INTO ?:sbps_process_info ?e', array_merge(['order_id' => $order_id], $attr));
        } else {
            db_query('UPDATE ?:sbps_process_info SET ?u WHERE order_id = ?i', $attr, $order_id);
        }
    }
}

/**
 * 決済情報作成・更新
 *
 * @param $order_id
 * @param $attr
 * @param $process
 */
function fn_ap_sbps_set_sbps_payment_info($order_id, $attr, $process)
{
    $table = "sbps_{$process}_payment_info";

    $sbps_payment_info = db_get_field("SELECT order_id FROM ?:{$table} WHERE order_id = ?i", $order_id);
    if (!empty($sbps_payment_info)) {
        db_query("UPDATE ?:{$table} SET ?u WHERE order_id = ?i", $attr, $order_id);
    } else {
        db_query("INSERT INTO ?:{$table} ?e", array_merge(['order_id' => $order_id], $attr));
    }
}

/**
 * 注文情報作成・更新
 *
 * @param $order_id
 * @param $attr
 * @param $process
 */
function fn_ap_sbps_set_order_data($order_id, $attr, $process)
{
    $format_attr = [];
    $date_format = Registry::get('settings.Appearance.date_format') . ' ' . Registry::get('settings.Appearance.time_format');

    // 不要な情報を削除
    $unset_keys = [
        'order_id', 'order_status', 'recognized_no', 'commit_status', 'refund_rowno', 'bonus_details_times', 'order_date'
    ];

    foreach ($unset_keys as $key) {
        if (array_key_exists($key, $attr)) {
            unset($attr[$key]);
        }
    }

    // 情報整形
    foreach ($attr as $key => $value) {
        // すでに整形済みのデータの場合
        if (preg_match('/^sbps_/', $key)) {
            if (!array_key_exists(ltrim($key, 'sbps_'), $attr)) {
                $format_attr[$key] = $value;
            }
            continue;
        }

        $format_key = "sbps_{$key}";

        switch ($key) {
            case 'is_canceled':
            case 'is_charge':
                if ($value === true || $value === '1') {
                    $format_attr[$format_key] =  __("{$format_key}_1");
                } else {
                    $format_attr[$format_key] =  __("{$format_key}_0");
                }
                break;
            case 'canceled_at':
            case 'refunded_at':
                $format_attr[$format_key] = empty($value) ? '-' : fn_date_format($value, $date_format);
                break;
            case 'payment_status':
                if (in_array($process, ['offline', 'paypay'], true)) {
                    $format_attr[$format_key] = __("sbps_{$process}_payment_status_{$value}");
                } else {
                    $format_attr[$format_key] = __("{$format_key}_{$value}");
                }
                break;
            case 'cc_company_code':
            case 'divide_times':
            case 'tracking_id':
            case 'order_date':
                if (!empty($value)) {
                    $format_attr[$format_key] = $value;
                }
                break;
            default:
                $format_attr[$format_key] = __("{$format_key}_{$value}");
        }
    }

    $order_data = db_get_field('SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = ?s', $order_id, 'P');
    if (!empty($order_data)) {
        db_query('UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = \'P\'', fn_encrypt_text(serialize($format_attr)), $order_id);
    } else {
        db_query('INSERT INTO ?:order_data ?e', ['order_id' => $order_id, 'type' => 'P', 'data' => fn_encrypt_text(serialize($format_attr))]);
    }
}

/**
 * カード預かり情報作成・更新
 *
 * @param $user_id
 * @param $payment_id
 */
function fn_ap_sbps_set_quickpay_data($user_id, $payment_id)
{
    db_query('REPLACE INTO ?:sbps_quickpay_data ?e', ['user_id' => $user_id, 'payment_id' => $payment_id]);
}

/**
 * 注文情報作成・更新
 *
 * @param $order_id
 * @param $order_info
 */
function fn_ap_sbps_set_sbps_order_data($order_id, $order_info)
{
    if (!empty($order_id)) {
        $attr = [
            'stored_subtotal_discount' => 'Y',
            'subtotal_discount' => $order_info['subtotal_discount'],
            'stored_tax' => 'Y',
        ];

        // 商品情報
        if (!empty($order_info['products'])) {
            foreach ($order_info['products'] as $item_id => $product) {
                $attr['cart_products'][$item_id] = [
                    'stored_price' => 'Y',
                    'price' => $product['original_price'],
                    'stored_discount' => 'Y',
                    'discount' => !empty($product['discount']) ? $product['discount'] : 0,
                    'amount' => $product['amount'],
                    'product_id' => $product['product_id'],
                    'object_id' => $item_id,
                ];

                if (!empty($product['product_options'])) {
                    foreach ($product['product_options'] as $option_id => $product_option) {
                        $attr['cart_products'][$item_id]['product_options'][$option_id] = $product_option['value'];
                    }
                }
            }
        }

        // 税金情報
        if(!empty($order_info['taxes'])) {
            foreach ($order_info['taxes'] as $id => $tax) {
                $attr['taxes'][$id] = $tax['rate_value'];
            }
        }

        // 配送情報
        if (!empty($order_info['shipping'])) {
            foreach ($order_info['shipping'] as $shipping) {
                $attr['stored_shipping'][] = ['Y'];
                $attr['stored_shipping_cost'][] = [$shipping['rate']];
                $attr['shipping_ids'][] = $shipping['shipping_id'];
            }
        }

        db_query('REPLACE INTO ?:sbps_order_data ?e', ['order_id' => $order_id, 'data' => fn_encrypt_text(serialize($attr)), 'total' => $order_info['total']]);
    }
}

/**
 * 継続課金解約時の更新処理
 *
 * @param $order_id
 * @param $process
 */
function fn_ap_sbps_update_rb_cancel_contract($order_id, $process)
{
    // 決済情報を解約済みに変更
    $update_attr = ['is_charge' => false, 'canceled_at' => time()];
    db_query("UPDATE ?:sbps_{$process}_payment_info SET ?u WHERE order_id IN (?a)", $update_attr, $order_id);

    // 注文情報更新
    db_query('UPDATE ?:orders SET rb_status = ?i WHERE order_id = ?i', RB_STATUS_CANCEL_CONTRACT, $order_id);
    fn_ap_sbps_set_order_data($order_id, fn_ap_sbps_get_payment_info($order_id, $process), $process);
}

/**
 * 定期購入解約時の更新処理
 *
 * @param $order_id
 * @param $process_info
 */
function fn_ap_sbps_update_rp_cancel_contract($order_id, $process_info)
{
    // 更新対象の注文ID取得
    $master_order_id = !empty($process_info['master_order_id']) ? $process_info['master_order_id'] : $order_id;
    $target_order_ids = db_get_fields('SELECT order_id FROM ?:sbps_process_info WHERE master_order_id = ?i', $master_order_id);

    if (empty($target_order_ids)) {
        $target_order_ids = [];
    }

    $target_order_ids[] = $master_order_id;

    // 決済情報を解約済みに変更
    $update_attr = ['is_canceled' => true, 'canceled_at' => time()];
    db_query("UPDATE ?:sbps_{$process_info['process']}_payment_info SET ?u WHERE order_id IN (?a)", $update_attr, $target_order_ids);

    // 注文情報更新
    $order_datas = db_get_hash_single_array('SELECT order_id, data FROM ?:order_data WHERE order_id IN (?a) AND type = \'P\'', ['order_id', 'data'], $target_order_ids);
    foreach ($target_order_ids as $target_order_id) {
        // 定期購入のステータスをキャンセルに変更
        db_query('UPDATE ?:orders SET rp_status = ?i WHERE order_id = ?i', RP_STATUS_CANCEL_CONTRACT, $target_order_id);

        // order_dataを更新
        $data = unserialize(fn_decrypt_text($order_datas[$target_order_id]));
        fn_ap_sbps_set_order_data($target_order_id, array_merge($data, ['is_canceled' => true, 'canceled_at' => $update_attr['canceled_at']]), $process_info['process']);
    }
}

/**
 * 複数回返金用の枝番インクリメント
 *
 * @param $order_id
 * @param $process
 */
function fn_ap_sbps_increment_refund_rowno($order_id, $process)
{
    $table = "sbps_{$process}_payment_info";
    db_query("UPDATE ?:{$table} SET refund_rowno = refund_rowno + 1 WHERE order_id = ?i", $order_id);
}

/**
 * カード預かりの支払方法有効チェック
 *
 * @param $user_id
 * @return bool
 */
function fn_ap_sbps_valid_quickpay($user_id)
{
    $result = false;

    $quickpay_data = db_get_array('SELECT * FROM ?:sbps_quickpay_data WHERE user_id = ?i', $user_id);

    if (!empty($quickpay_data)) {
        $payment_method_data = fn_get_payment_method_data($quickpay_data[0]['payment_id']);
        $result = !empty($payment_method_data) && $payment_method_data['status'] === 'A';
    }

    return $result;
}

/**
 * 注文情報ロールバック
 *
 * @param $order_id
 */
function fn_ap_sbps_rollback_order($order_id)
{
    $data = db_get_field('SELECT `data` FROM ?:sbps_order_data WHERE order_id = ?i', $order_id);

    if (!empty($data)) {
        $data = unserialize(fn_decrypt_text($data));

        Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart_origin']) ? Tygh::$app['session']['cart_origin'] : [];
        $cart = & Tygh::$app['session']['cart'];

        Tygh::$app['session']['customer_auth'] = isset(Tygh::$app['session']['customer_auth_origin']) ? Tygh::$app['session']['customer_auth_origin'] : [];
        $customer_auth = & Tygh::$app['session']['customer_auth'];

        fn_update_cart_by_data($cart, $data, $customer_auth);

        if (!empty($data['shipping_ids'])) {
            fn_checkout_update_shipping($cart, $_REQUEST['shipping_ids']);
        }

        if (empty($cart['stored_shipping'])) {
            $cart['calculate_shipping'] = true;
        }

        fn_calculate_cart_content($cart, $customer_auth);
        fn_update_payment_surcharge($cart, $customer_auth);

        fn_place_order($cart, $customer_auth);
    }
}

/**
 * install関数
 */
function fn_ap_sbps_install()
{
    fn_lcjp_install('ap_sbps');

    // SBPS処理情報用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_process_info;');
    db_query('CREATE TABLE ?:sbps_process_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `transaction_id` CHAR(32),
      `tracking_id` CHAR(14),
      `master_tracking_id` CHAR(14),
      `master_order_id` MEDIUMINT(8) unsigned,
      `process` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`)) 
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報用(クレジット)テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_credit_payment_info');
    db_query('CREATE TABLE ?:sbps_credit_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `recognized_no` CHAR(7),
      `commit_status` CHAR(1),
      `payment_status` CHAR(2),
      `refund_rowno` MEDIUMINT(8) unsigned NOT NULL,
      `pay_method` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報用(キャリア)テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_career_payment_info');
    db_query('CREATE TABLE ?:sbps_career_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(プリペイド)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_prepaid_payment_info');
    db_query('CREATE TABLE ?:sbps_prepaid_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報用(ウォレット)テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_wallet_payment_info');
    db_query('CREATE TABLE ?:sbps_wallet_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(ポイント)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_point_payment_info');
    db_query('CREATE TABLE ?:sbps_point_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(電子マネー)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_em_payment_info');
    db_query('CREATE TABLE ?:sbps_em_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報用(オフライン)テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_offline_payment_info');
    db_query('CREATE TABLE ?:sbps_offline_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報用(ApplePay)テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_applepay_payment_info');
    db_query('CREATE TABLE ?:sbps_applepay_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `recognized_no` CHAR(7),
      `payment_status` CHAR(2),
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報用(PayPay)テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_paypay_payment_info');
    db_query('CREATE TABLE ?:sbps_paypay_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(簡易継続クレジット)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_rb_credit_payment_info');
    db_query('CREATE TABLE ?:sbps_rb_credit_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `is_charge` BOOLEAN DEFAULT FALSE,
      `pay_method` VARCHAR(255) NOT NULL,
      `canceled_at` INT(11) unsigned,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(簡易継続キャリア)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_rb_career_payment_info');
    db_query('CREATE TABLE ?:sbps_rb_career_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `is_charge` BOOLEAN DEFAULT FALSE,
      `pay_method` VARCHAR(255) NOT NULL,
      `canceled_at` INT(11) unsigned,
      `refunded_at` INT(11) unsigned,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(簡易継続ウォレット)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_rb_wallet_payment_info');
    db_query('CREATE TABLE ?:sbps_rb_wallet_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `is_charge` BOOLEAN DEFAULT FALSE,
      `pay_method` VARCHAR(255) NOT NULL,
      `canceled_at` INT(11) unsigned,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(定期購入クレジット)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_rp_credit_payment_info');
    db_query('CREATE TABLE ?:sbps_rp_credit_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `recognized_no` CHAR(7),
      `commit_status` CHAR(1),
      `payment_status` CHAR(2),
      `refund_rowno` MEDIUMINT(8) unsigned NOT NULL,
      `pay_method` VARCHAR(255) NOT NULL,
      `is_canceled` BOOLEAN DEFAULT FALSE,
      `canceled_at` INT(11) unsigned,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(定期購入キャリア)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_rp_career_payment_info');
    db_query('CREATE TABLE ?:sbps_rp_career_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      `is_canceled` BOOLEAN DEFAULT FALSE,
      `canceled_at` INT(11) unsigned,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS決済情報(定期購入ウォレット)用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_rp_wallet_payment_info');
    db_query('CREATE TABLE ?:sbps_rp_wallet_payment_info (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_status` CHAR(2),
      `pay_method` VARCHAR(255) NOT NULL,
      `is_canceled` BOOLEAN DEFAULT FALSE,
      `canceled_at` INT(11) unsigned,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPS注文情報用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_order_data');
    db_query('CREATE TABLE ?:sbps_order_data (
      `order_id` MEDIUMINT(8) unsigned NOT NULL,
      `data` LONGBLOB NOT NULL,
      `total` DECIMAL(12,2) NOT NULL,
      PRIMARY KEY (`order_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // SBPSカード預かり情報用テーブル作成
    db_query('DROP TABLE IF EXISTS ?:sbps_quickpay_data');
    db_query('CREATE TABLE ?:sbps_quickpay_data (
      `user_id` MEDIUMINT(8) unsigned NOT NULL,
      `payment_id` MEDIUMINT(8) unsigned NOT NULL,
      PRIMARY KEY (`user_id`))
      ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );

    // ordersテーブルにフィールド追加
    db_query('ALTER TABLE `?:orders` ADD `is_subscription` BOOLEAN NOT NULL DEFAULT FALSE AFTER `user_id`');
    db_query('ALTER TABLE `?:orders` ADD `rb_status` INT(1) NOT NULL DEFAULT 0 AFTER `is_subscription`');

    // 支払い方法追加(リンク決済)
    db_query('REPLACE INTO ?:payment_processors ?e', [
        'processor_id' => 9247,
        'processor' => 'SBペイメントサービス (リンク決済)',
        'processor_script' => 'ap_sbps_link.php',
        'processor_template' => 'views/orders/components/payments/ap_sbps_link.tpl',
        'admin_template' => 'ap_sbps_link.tpl',
        'callback' => 'N',
        'type' => 'P'
    ]);

    // 支払い方法追加(クレジットカード・トークン決済)
    db_query('REPLACE INTO ?:payment_processors ?e', [
        'processor_id' => 9241,
        'processor' => 'SBペイメントサービス (クレジットカード・トークン決済)',
        'processor_script' => 'ap_sbps_cctkn.php',
        'processor_template' => 'views/orders/components/payments/ap_sbps_cctkn.tpl',
        'admin_template' => 'ap_sbps_cctkn.tpl',
        'callback' => 'N',
        'type' => 'P'
    ]);

    // 支払い方法追加(クレジットカード・登録済みカード決済)
    db_query('REPLACE INTO ?:payment_processors ?e', [
        'processor_id' => 9242,
        'processor' => 'SBペイメントサービス (クレジットカード・登録済みカード決済)',
        'processor_script' => 'ap_sbps_ccreg.php',
        'processor_template' => 'views/orders/components/payments/ap_sbps_ccreg.tpl',
        'admin_template' => 'ap_sbps_ccreg.tpl',
        'callback' => 'N',
        'type' => 'P'
    ]);

    // 支払い方法追加(クレジットカード・継続課金)
    db_query('REPLACE INTO ?:payment_processors ?e', [
        'processor_id' => 9243,
        'processor' => 'SBペイメントサービス (クレジットカード・継続課金)',
        'processor_script' => 'ap_sbps_rb_cctkn.php',
        'processor_template' => 'views/orders/components/payments/ap_sbps_rb_cctkn.tpl',
        'admin_template' => 'ap_sbps_rb_cctkn.tpl',
        'callback' => 'N',
        'type' => 'P'
    ]);

    // 支払い方法追加(リンク決済・継続課金)
    db_query('REPLACE INTO ?:payment_processors ?e', [
        'processor_id' => 9244,
        'processor' => 'SBペイメントサービス (リンク決済・継続課金)',
        'processor_script' => 'ap_sbps_rb_link.php',
        'processor_template' => 'views/orders/components/payments/ap_sbps_rb_link.tpl',
        'admin_template' => 'ap_sbps_rb_link.tpl',
        'callback' => 'N',
        'type' => 'P'
    ]);

    // 支払い方法追加(リンク決済・定期購入)
    db_query('REPLACE INTO ?:payment_processors ?e', [
        'processor_id' => 9245,
        'processor' => 'SBペイメントサービス (リンク決済・定期購入)',
        'processor_script' => 'ap_sbps_rp_link.php',
        'processor_template' => 'views/orders/components/payments/ap_sbps_rp_link.tpl',
        'admin_template' => 'ap_sbps_rp_link.tpl',
        'callback' => 'N',
        'type' => 'P'
    ]);

    // 支払い方法追加(クレジットカード・定期購入)
    db_query('REPLACE INTO ?:payment_processors ?e', [
        'processor_id' => 9246,
        'processor' => 'SBペイメントサービス (クレジットカード・定期購入)',
        'processor_script' => 'ap_sbps_rp_cctkn.php',
        'processor_template' => 'views/orders/components/payments/ap_sbps_rp_cctkn.tpl',
        'admin_template' => 'ap_sbps_rp_cctkn.tpl',
        'callback' => 'N',
        'type' => 'P'
    ]);
}

/**
 * uninstall関数
 */
function fn_ap_sbps_uninstall()
{
    // ordersテーブルに追加したフィールドを削除
    db_query('ALTER TABLE `?:orders` DROP `is_subscription`');
    db_query('ALTER TABLE `?:orders` DROP `rb_status`');

    // 支払い方法削除
    db_query('DELETE FROM ?:payment_processors WHERE processor_script LIKE ?s', '%ap_sbps%');
}
