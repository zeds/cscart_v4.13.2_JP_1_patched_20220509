<?php

/* * *************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 * ***************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 * ************************************************************************** */

$items = [
    'ap_sbps_credit_manager' => [
        'permissions' => true
    ],
    'ap_sbps_rb_credit_manager' => [
        'permissions' => true
    ]
];

if (!empty($schema['controllers'])) {
    $schema['controllers'] = array_merge($schema['controllers'], $items);
} else {
    $schema['controllers'] = $items;
}

return $schema;
