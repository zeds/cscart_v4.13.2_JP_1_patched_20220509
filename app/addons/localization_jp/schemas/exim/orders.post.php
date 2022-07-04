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

include_once(Registry::get('config.dir.addons') . 'localization_jp/schemas/exim/orders.functions.php');

$schema['export_fields']['Delivery timing'] = array (
    'linked' => false,
    'export_only' => true,
    'process_get' => array('fn_exim_localization_jp_get_delivery_data', '#key', 'T'),
);

$schema['export_fields']['Delivery date'] = array (
	'linked' => false,
	'export_only' => true,
	'process_get' => array('fn_exim_localization_jp_get_delivery_data', '#key', 'D'),
);

$schema['export_fields']['Tracking number'] = array (
	'linked' => false,
	'export_only' => true,
	'process_get' => array('fn_exim_localization_jp_get_delivery_data', '#key', 'N'),
);

return $schema;
