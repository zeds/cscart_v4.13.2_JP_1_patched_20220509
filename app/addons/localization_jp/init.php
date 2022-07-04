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

fn_register_hooks(
    'calculate_cart',
    'delete_company',
    'delete_order',
    'gather_additional_product_data_post',
    'get_default_usergroups',
    'get_filter_range_name_post',
    'get_order_info',
    'get_products',
    'get_products_pre',
    'get_profile_fields',
    'get_states',
    'jp_license_auth',
    'jp_output_csv_pre',
    'jp_post_format_timestamp',
    'jp_update_kana',
    'order_placement_routines',
    'place_order',
    'post_delete_user',
    'update_company',
    'update_shipping',
    'update_user_pre',
    'generate_name_post',
    'import_post',
    'before_dispatch',
    'create_order'
);
