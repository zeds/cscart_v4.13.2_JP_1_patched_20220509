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
// 「Amazonアカウントでお支払い」ページと通常の注文手続きページの切り替え用ブロック

$schema['amazon_checkout_button'] = array (
    'templates' => array (
        'addons/amazon_checkout/blocks/amazon_button.tpl' => array(),
    ),
    'wrappers' => 'blocks/wrappers',
);

return $schema;
