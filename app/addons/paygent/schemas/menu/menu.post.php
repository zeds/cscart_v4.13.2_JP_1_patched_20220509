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

// $Id: menu.post.php by tommy from cs-cart.jp 2016

// ペイジェント（カード払いまたは登録済みカード払い）を使用する支払方法が登録されている場合
if( fn_pygnt_is_payment_registered('cc') ){
    $schema['central']['orders']['items']['jp_paygent_service_name_cc'] = array(
        'href' => 'paygent_cc_manager.manage',
        'alt' => 'jp_paygent_service_name_cc',
        'position' => 900
    );
}


// ペイジェント（多通貨カード払いまたは多通貨登録済みカード払い）を使用する支払方法が登録されている場合
if( fn_pygnt_is_payment_registered('mc') ){
    $schema['central']['orders']['items']['jp_paygent_service_name_mc'] = array(
        'href' => 'paygent_mc_manager.manage',
        'alt' => 'jp_paygent_service_name_mc',
        'position' => 1000
    );
}

return $schema;
