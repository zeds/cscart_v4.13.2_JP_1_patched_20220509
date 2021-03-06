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

use Tygh\Registry;

include_once(Registry::get('config.dir.schemas') . 'exim/qty_discounts.functions.php');
include_once(Registry::get('config.dir.schemas') . 'exim/products.functions.php');

$schema = array(
    'section' => 'products',
    'name' => __('qty_discounts'),
    'pattern_id' => 'qty_discounts',
    'key' => array('product_id'),
    'order' => 3,
    'table' => 'products',
    'permissions' => array(
        'import' => 'manage_catalog',
        'export' => 'view_catalog',
    ),
    'update_only' => true,
    'references' => array(
        'product_prices' => array(
            'reference_fields' => array('product_id' => '#key'),
            'join_type' => 'INNER',
            'alt_key' => array('lower_limit', 'usergroup_id', '#key')
        ),
    ),
    'condition' => array(
        'use_company_condition' => true,
    ),
    'range_options' => array(
        'selector_url' => 'products.manage',
        'object_name' => __('products'),
    ),
    'options' => array(
        'lang_code' => array(
            'title' => 'language',
            'type' => 'languages',
            'default_value' => array(DEFAULT_LANGUAGE),
        ),
        'price_dec_sign_delimiter' => array(
            'title' => 'price_dec_sign_delimiter',
            'description' => 'text_price_dec_sign_delimiter',
            'type' => 'input',
            'default_value' => '.'
        ),
    ),
    'export_fields' => array(
        'Product code' => array(
            'required' => true,
            'alt_key' => true,
            'db_field' => 'product_code'
        ),
        'Language' => array(
            'process_get' => array('', '#lang_code'),
            'type' => 'languages',
            'linked' => false,
            'required' => true,
            'multilang' => true
        ),
        'Price' => array(
            'table' => 'product_prices',
            'db_field' => 'price',
            'required' => true,
            'convert_put' => array('fn_exim_import_price', '#this', '@price_dec_sign_delimiter'),
            'process_get' => array('fn_exim_export_price', '#this', '@price_dec_sign_delimiter'),
            'process_put' => array('fn_qty_update_prices', '#key', '#row'),
        ),
        'Percentage discount' => array(
            'table' => 'product_prices',
            'db_field' => 'percentage_discount',
            'default' => '0',
        ),
        'Lower limit' => array(
            'table' => 'product_prices',
            'db_field' => 'lower_limit',
            'key_component' => true,
            'required' => true,
            'pre_insert' => array('fn_exim_check_discount', '#row', '#lang_code'),
        ),
    ),
);

if (fn_allowed_for('ULTIMATE')) {
    $schema['import_get_primary_object_id'] = array(
        'fill_primary_object_company_id' => array(
            'function' => 'fn_exim_apply_company',
            'args' => array('$pattern', '$alt_keys', '$object', '$skip_get_primary_object_id'),
            'import_only' => true,
        ),
    );
    $schema['import_process_data'] = array(
        'check_product_company_id' => array(
            'function' => 'fn_import_check_product_company_id',
            'args' => array('$primary_object_id', '$object', '$pattern', '$options', '$processed_data', '$processing_groups', '$skip_record'),
            'import_only' => true,
        ),
    );
}

if (!fn_allowed_for('ULTIMATE:FREE')) {
    $schema['export_fields']['User group'] = array(
        'db_field' => 'usergroup_id',
        'table' => 'product_prices',
        'key_component' => true,
        'process_get' => array('fn_exim_get_usergroup', '#this', '#lang_code'),
        'convert_put' => array('fn_exim_put_usergroup', '#this', '#lang_code'),
        'return_result' => true,
        'required' => true,
        'multilang' => true
    );
}

if (fn_allowed_for('MULTIVENDOR')) {
    if (Registry::get('runtime.company_id')) {
        $schema['import_process_data']['mve_import_check_object_id'] = [
            'function' => 'fn_mve_import_check_object_id',
            'args' => ['$primary_object_id', '$processed_data', '$skip_record'],
            'import_only' => true,
        ];
    }
}

return $schema;
