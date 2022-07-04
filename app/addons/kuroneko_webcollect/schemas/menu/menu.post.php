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

// Modified by takahashi from cs-cart.jp 2019
// 出荷情報登録仕様変更対応（他社配送）

// Modified by takahashi from cs-cart.jp 2020
// ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）

// クロネコwebコレクト（カード払いまたは登録済みカード払い）を使用する支払方法が登録されている場合
if( fn_krnkwc_is_payment_registered('cc') ){
    $schema['central']['orders']['items']['jp_kuroneko_webcollect_service_name_wc_cc'] = array(
        'href' => 'krnkwc_cc_manager.manage',
        'alt' => 'jp_kuroneko_webcollect_service_name_wc_cc',
        'position' => 900
    );

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // 出荷情報登録仕様変更対応（他社配送）
    ///////////////////////////////////////////////
    // 出荷情報登録画面
    $schema['central']['orders']['items']['jp_kuroneko_webcollect_service_name_wc_cc_shipments'] = array(
        'href' => 'krnkwc_cc_shipments.manage',
        'alt' => 'jp_kuroneko_webcollect_service_name_wc_cc_shipments',
        'position' => 1200
    );
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////
}

// クロネコwebコレクト（コンビニ払い）を使用する支払方法が登録されている場合
if( fn_krnkwc_is_payment_registered('cvs') ){
    $schema['central']['orders']['items']['jp_kuroneko_webcollect_service_name_wc_cvs'] = array(
        'href' => 'krnkwc_cvs_manager.manage',
        'alt' => 'jp_kuroneko_webcollect_service_name_wc_cvs',
        'position' => 1000
    );
}

// クロネコ代金後払いサービスを使用する支払方法が登録されている場合
if( fn_krnkwc_is_payment_registered('ab') ) {
    $schema['central']['orders']['items']['jp_kuroneko_webcollect_ab_name'] = array(
        'href' => 'krnkab_manager.manage',
        'alt' => 'jp_kuroneko_webcollect_ab_name',
        'position' => 1100
    );

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 BOF
    // ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い）
    ///////////////////////////////////////////////
    // 出荷情報登録画面
    $schema['central']['orders']['items']['jp_kuroneko_webcollect_service_name_wc_ab_shipments'] = array(
        'href' => 'krnkab_shipments.manage',
        'alt' => 'jp_kuroneko_webcollect_service_name_wc_ab_shipments',
        'position' => 1300
    );
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2020 EOF
    ///////////////////////////////////////////////
}

return $schema;
