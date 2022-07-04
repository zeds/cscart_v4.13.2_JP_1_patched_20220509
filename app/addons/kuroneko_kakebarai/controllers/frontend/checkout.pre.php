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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'checkout') {
    $user_id = Tygh::$app['session']['auth']['user_id'];

    // 利用状況
    $useUsable = fn_krnkkb_check_use_Usable();

    // ログイン中でクロネコ掛け払い取引先」のユーザーグループに所属している場合
    // 利用金額照会の結果がある場合
    if($user_id && $user_id > 0 && fn_kuroneko_kakebarai_is_kakebarai_user($user_id) && !empty($useUsable)) {
        // 支払方法にクロネコ掛け払い（有効）が登録されていない
        // または、利用状況が「利用可」でない場合
        if (!fn_kuroneko_kakebarai_is_payment_registered('A')
          || $useUsable != __("jp_kuroneko_kakebarai_use_usable_ok")) {

            // 「クロネコ掛け払いはご利用できません」メッセージを表示
            $title = __('jp_kuroneko_kakebarai_error');
            $status = $useUsable;
            if(!fn_kuroneko_kakebarai_is_payment_registered('A')){
                $status = __("jp_kuroneko_kakebarai_no_valid_payment_method");
            }

            fn_set_notification('E', $title, __("jp_kuroneko_kakebarai_judge_status_ng", array(
                '[status]' => $status
            )));
        }
        // クロネコ掛け払いが利用可能な場合
        else {
            // 利用金額照会
            $type = "50";
            $order_info['user_id'] = Tygh::$app['session']['auth']['user_id'];
            $params = array();
            $params = fn_krnkkb_get_params($type, null, $order_info);

            $result = fn_krnkkb_send_request($type, $params);

            fn_set_notification('N', __('notice'), __('jp_kuroneko_kakebarai_usage_notice', array(
                '[useUsable]' => $useUsable,
                '[useOverLimit]' => number_format($result['useOverLimit']) . __("jp_japanese_yen"),
                '[usePayment]' => number_format($result['usePayment']) . __("jp_japanese_yen")
            )));
        }
    }
}