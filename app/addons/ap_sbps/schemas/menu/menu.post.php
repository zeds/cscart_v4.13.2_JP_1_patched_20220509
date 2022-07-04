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

$is_multivendor = fn_allowed_for('MULTIVENDOR');

$items = [
    'ap_sbps_manager' => [
        'href' => 'ap_sbps_credit_manager.manage',
        'position' => 900,
        'subitems' => [
            'ap_sbps_credit' => [
                'href' => 'ap_sbps_credit_manager.manage?view_type=A',
                'position' => 200
            ]
        ]
    ]
];

if (!$is_multivendor) {
    $ap_sbps_manager_sub_items =  [
        'ap_sbps_career' => [
            'href' => 'ap_sbps_career_manager.manage',
            'position' => 201
        ],
        'ap_sbps_prepaid' => [
            'href' => 'ap_sbps_prepaid_manager.manage',
            'position' => 202
        ],
        'ap_sbps_wallet' => [
            'href' => 'ap_sbps_wallet_manager.manage',
            'position' => 203
        ],
        'ap_sbps_point' => [
            'href' => 'ap_sbps_point_manager.manage',
            'position' => 204
        ],
        'ap_sbps_em' => [
            'href' => 'ap_sbps_em_manager.manage',
            'position' => 205
        ],
        'ap_sbps_offline' => [
            'href' => 'ap_sbps_offline_manager.manage?view_type=A',
            'position' => 206
        ],
        'ap_sbps_recruitc' => [
            'href' => 'ap_sbps_credit_manager.manage?view_type=R',
            'position' => 207
        ],
        'ap_sbps_recruito' => [
            'href' => 'ap_sbps_offline_manager.manage?view_type=R',
            'position' => 208
        ],
        'ap_sbps_applepay' => [
            'href' => 'ap_sbps_applepay_manager.manage',
            'position' => 209
        ],
        'ap_sbps_paypay' => [
            'href' => 'ap_sbps_paypay_manager.manage',
            'position' => 210
        ]
    ];

    $items['ap_sbps_manager']['subitems'] = array_merge($items['ap_sbps_manager']['subitems'], $ap_sbps_manager_sub_items);
}

// 継続課金アドオンがインストールされている場合
if (!empty(Registry::get('addons.subscription_payment_jp')) && Registry::get('addons.subscription_payment_jp.status') === 'A') {
    $items['ap_sbps_rb_manager'] = [
        'href' => 'ap_sbps_rb_manager.manage',
        'position' => 901,
        'subitems' => [
            'ap_sbps_credit' => [
                'href' => 'ap_sbps_rb_credit_manager.manage?view_type=A',
                'position' => 200
            ]
        ]
    ];

    if (!$is_multivendor) {
        $ap_sbps_rb_manager_sub_items =  [
            'ap_sbps_career' => [
                'href' => 'ap_sbps_rb_career_manager.manage',
                'position' => 201
            ],
            'ap_sbps_wallet' => [
                'href' => 'ap_sbps_rb_wallet_manager.manage',
                'position' => 202
            ],
            'ap_sbps_recruitc' => [
                'href' => 'ap_sbps_rb_credit_manager.manage?view_type=R',
                'position' => 203
            ]
        ];

        $items['ap_sbps_rb_manager']['subitems'] = array_merge($items['ap_sbps_rb_manager']['subitems'], $ap_sbps_rb_manager_sub_items);
    }
}

// 定期購入アドオンがインストールされている場合
if (!$is_multivendor && !empty(Registry::get('addons.ap_regular_purchases')) && Registry::get('addons.ap_regular_purchases.status') === 'A') {
    $items['ap_sbps_rp_manager'] = [
        'href' => 'ap_sbps_rp_manager.manage',
        'position' => 902,
        'subitems' => [
            'ap_sbps_credit' => [
                'href' => 'ap_sbps_rp_credit_manager.manage?view_type=A',
                'position' => 200
            ],
            'ap_sbps_career' => [
                'href' => 'ap_sbps_rp_career_manager.manage',
                'position' => 201
            ],
            'ap_sbps_wallet' => [
                'href' => 'ap_sbps_rp_wallet_manager.manage',
                'position' => 202
            ],
            'ap_sbps_recruitc' => [
                'href' => 'ap_sbps_rp_credit_manager.manage?view_type=R',
                'position' => 203
            ]
        ]
    ];
}

if (!empty($schema['central']['orders']['items'])) {
    $schema['central']['orders']['items'] = array_merge($schema['central']['orders']['items'], $items);
} else {
    $schema['central']['orders']['items'] = $items;
}

return $schema;
