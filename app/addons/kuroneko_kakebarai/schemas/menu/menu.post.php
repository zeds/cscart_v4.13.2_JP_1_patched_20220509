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

// $Id: menu.post.php by takahashi from cs-cart.jp 2016

// クロネコ掛け払いを使用する支払方法が登録されている場合
if( fn_kuroneko_kakebarai_is_payment_registered() ) {
    $schema['central']['orders']['items']['jp_kuroneko_kakebarai_name'] = array(
        'href' => 'krnkkb_manager.manage',
        'alt' => 'jp_kuroneko_kakebarai_name',
        'position' => 1000
    );
}

return $schema;
