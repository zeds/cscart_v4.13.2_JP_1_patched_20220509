<?php

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// トークン取得JSのURL
const SBPS_TOKEN_JS_URL = [
    'test' => 'https://stbtoken.sps-system.com/sbpstoken/com_sbps_system_token.js',
    'live' => 'https://token.sps-system.com/sbpstoken/com_sbps_system_token.js'
];

// APIのURL
const SBPS_API_URL = [
    'test' => 'https://stbfep.sps-system.com/api/xmlapi.do',
    'live' => 'https://api.sps-system.com/api/xmlapi.do'
];

// 購入要求接続先URL
const SBPS_PURCHASE_REQUEST_URL = [
    'test' => 'https://stbfep.sps-system.com/f01/FepBuyInfoReceive.do',
    'live' => 'https://fep.sps-system.com/f01/FepBuyInfoReceive.do'
];

// XML
const SBPS_XML_CHILD_ELEMENTS = ['dtls' => 'dtl'];
const SBPS_XML_3DES_ELEMENTS = ['dealings_type', 'divide_times'];
const SBPS_XML_BASE64_ENCODE_ELEMENTS = ['item_name', 'free1', 'free2', 'free3', 'dtl_item_name'];

// 決済ステータス
const SBPS_PAYMENT_STATUS = ['1', '2', '3', '4', '5', '6', '10', '11', '20', '21', '22', '90', '91', '92'];
const SBPS_PAYMENT_STATUS_AUTH_OK = '1';
const SBPS_PAYMENT_STATUS_SALES_CONFIRM = '2';
const SBPS_PAYMENT_STATUS_AUTH_CANCEL = '3';
const SBPS_PAYMENT_STATUS_REFUNDED = '4';
const SBPS_PAYMENT_STATUS_PARTIAL_REFUNDED = '5';
const SBPS_PAYMENT_STATUS_REFUND_REQUESTED = '6';
const SBPS_PAYMENT_STATUS_UNKNOWN_OK = '10';
const SBPS_PAYMENT_STATUS_UNKNOWN_CANCEL = '11';
const SBPS_PAYMENT_STATUS_AUTH_ACCEPTED = '20';
const SBPS_PAYMENT_STATUS_SALES_CONFIRM_ACCEPTED = '21';
const SBPS_PAYMENT_STATUS_AMOUNT_CHANGE_ACCEPTED = '22';
const SBPS_PAYMENT_STATUS_AUTH_ERROR = '90';
const SBPS_PAYMENT_STATUS_SALES_CONFIRM_ERROR = '91';
const SBPS_PAYMENT_STATUS_AMOUNT_CHANGE_ERROR = '92';

// 決済ステータス(オフライン)
const SBPS_OFFLINE_PAYMENT_STATUS = ['1', '2', '3', '4', '9'];
const SBPS_OFFLINE_PAYMENT_STATUS_UNPAID = '1';
const SBPS_OFFLINE_PAYMENT_STATUS_PARTIAL_PAID = '2';
const SBPS_OFFLINE_PAYMENT_STATUS_PAID = '3';
const SBPS_OFFLINE_PAYMENT_STATUS_OVER_PAID = '4';
const SBPS_OFFLINE_PAYMENT_STATUS_CANCEL = '9';

// 決済ステータス(PayPay)
const SBPS_PAYPAY_PAYMENT_STATUS = ['0', '1', '2', '3', '4', '5', '9'];
const SBPS_PAYPAY_PAYMENT_STATUS_PROCESSING = '0';
const SBPS_PAYPAY_PAYMENT_STATUS_AUTH_OK = '1';
const SBPS_PAYPAY_PAYMENT_STATUS_SALES_PROCESSING = '2';
const SBPS_PAYPAY_PAYMENT_STATUS_SALES_CONFIRM = '3';
const SBPS_PAYPAY_PAYMENT_STATUS_AUTH_CANCEL = '4';
const SBPS_PAYPAY_PAYMENT_REFUNDED = '5';
const SBPS_PAYPAY_PAYMENT_PROCESS_ERROR = '9';

// 継続課金ステータス
const RB_STATUS_CHARGE = '1';
const RB_STATUS_CANCEL_CONTRACT = '2';

// 支払方法
const SBPS_PAY_METHODS = [
    'credit', 'credit3d', 'unionpay', 'webcvs', 'payeasy', 'banktransfer', 'cyberedy', 'mobileedy', 'suica', 'webmoney',
    'netcash', 'bitcash', 'prepaid', 'docomo', 'auone', 'yahoowallet', 'yahoowalletdg', 'rakuten',
    'recruit', 'alipay', 'paypal', 'netmile', 'softbank2', 'linepay', 'tpoint', 'applepay', 'paypay'
];

// 支払方法(継続課金)
const SBPS_RB_PAY_METHODS = [
    'credit', 'credit3d', 'docomo', 'auone', 'yahoowalletdg', 'rakuten', 'recruit'
];

// 支払方法(定期購入)
const SBPS_RP_PAY_METHODS = [
    'docomo', 'auone','softbank2', 'rakuten', 'recruit'
];

// 指定売上を選択できる支払い方法
const SBPS_PAY_METHOD_SPECIFIED_SALES = [
    'credit', 'credit3d', 'docomo', 'auone', 'softbank2', 'recruitc', 'yahoowallet', 'linepay', 'applepay', 'paypay'
];

// 支払い確認済み(P)になる支払い方法
const SBPS_PAY_METHOD_ORDER_STATUS_P = [
    'credit', 'credit3d', 'unionpay', 'cyberedy', 'mobileedy', 'suica', 'webmoney', 'netcash', 'bitcash', 'prepaid',
    'docomo', 'auone', 'softbank', 'yahoowallet', 'yahoowalletdg', 'rakuten', 'recruitc', 'alipay', 'paypal', 'netmile',
    'mysoftbank', 'softbank2', 'saisonpoint', 'linepay', 'tpoint', 'applepay', 'paypay'
];

// 支払い方法毎の振り分け
const SBPS_PAY_METHOD_PROCESS = [
    'credit' => ['credit', 'credit3d', 'unionpay', 'recruitc'],
    'career' => ['docomo', 'auone', 'softbank', 'mysoftbank', 'softbank2'],
    'prepaid' => ['webmoney', 'netcash', 'bitcash', 'prepaid'],
    'wallet' => ['yahoowallet', 'yahoowalletdg', 'rakuten', 'alipay', 'paypal', 'linepay'],
    'point' => ['netmile', 'saisonpoint', 'tpoint'],
    'em' => ['cyberedy', 'mobileedy', 'suica'],
    'applepay' => ['applepay'],
    'offline' => ['webcvs', 'payeasy', 'banktransfer', 'recruito'],
    'paypay' => ['paypay']
];

// コントローラー毎のテーブル情報
const SBPS_MANGER_CONTROLLER_TABLE_DATA = [
    'ap_sbps_credit_manager' =>[
        'table' => 'sbps_credit_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status', 'pay_method'],
        'view_type' => [
            'A' => ['credit', 'credit3d', 'unionpay'],
            'R' => ['recruitc']
        ]
    ],
    'ap_sbps_career_manager' =>[
        'table' => 'sbps_career_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status', 'pay_method']
    ],
    'ap_sbps_prepaid_manager' =>[
        'table' => 'sbps_prepaid_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status', 'pay_method']
    ],
    'ap_sbps_point_manager' =>[
        'table' => 'sbps_point_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status', 'pay_method']
    ],
    'ap_sbps_wallet_manager' =>[
        'table' => 'sbps_wallet_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status', 'pay_method']
    ],
    'ap_sbps_em_manager' =>[
        'table' => 'sbps_em_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status', 'pay_method']
    ],
    'ap_sbps_offline_manager' =>[
        'table' => 'sbps_offline_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status', 'pay_method'],
        'view_type' => [
            'A' => ['webcvs', 'payeasy', 'banktransfer'],
            'R' => ['recruito']
        ]
    ],
    'ap_sbps_applepay_manager' =>[
        'table' => 'sbps_applepay_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status']
    ],
    'ap_sbps_paypay_manager' =>[
        'table' => 'sbps_paypay_payment_info',
        'sorting_fields' => ['payment_status'],
        'fields' => ['payment_status']
    ],
    'ap_sbps_rb_credit_manager' =>[
        'table' => 'sbps_rb_credit_payment_info',
        'sorting_fields' => ['is_charge', 'canceled_at'],
        'fields' => ['is_charge', 'canceled_at', 'pay_method'],
        'view_type' => [
            'A' => ['rb_credit', 'rb_credit3d'],
            'R' => ['rb_recruitc']
        ]
    ],
    'ap_sbps_rb_career_manager' =>[
        'table' => 'sbps_rb_career_payment_info',
        'sorting_fields' => ['is_charge', 'canceled_at', 'refunded_at'],
        'fields' => ['is_charge', 'canceled_at', 'refunded_at', 'pay_method']
    ],
    'ap_sbps_rb_wallet_manager' =>[
        'table' => 'sbps_rb_wallet_payment_info',
        'sorting_fields' => ['is_charge', 'canceled_at'],
        'fields' => ['is_charge', 'canceled_at', 'pay_method']
    ],
    'ap_sbps_rp_credit_manager' =>[
        'table' => 'sbps_rp_credit_payment_info',
        'sorting_fields' => ['payment_status', 'canceled_at', 'is_canceled'],
        'fields' => ['payment_status', 'pay_method', 'canceled_at', 'is_canceled'],
        'view_type' => [
            'A' => ['rp_credit', 'rp_credit3d'],
            'R' => ['rp_recruitc']
        ]
    ],
    'ap_sbps_rp_career_manager' =>[
        'table' => 'sbps_rp_career_payment_info',
        'sorting_fields' => ['payment_status', 'canceled_at', 'is_canceled'],
        'fields' => ['payment_status', 'pay_method', 'canceled_at', 'is_canceled']
    ],
    'ap_sbps_rp_wallet_manager' =>[
        'table' => 'sbps_rp_wallet_payment_info',
        'sorting_fields' => ['payment_status', 'canceled_at', 'is_canceled'],
        'fields' => ['payment_status', 'pay_method', 'canceled_at', 'is_canceled']
    ]
];

// 実行許可
const SBPS_EXEC_PERMISSION = [
    'credit' => [
        'all' => ['update_payment_info'],
        'status' => [
            '1' => ['sales_confirm', 'cancel', '_re_auth'],
            '2' => ['cancel', '_re_auth'],
            '10' => ['sales_confirm', 'cancel', '_re_auth']
        ]
    ],
    'credit3d' => [
        'all' => ['update_payment_info'],
        'status' => [
            '1' => ['sales_confirm', 'cancel', '_re_auth'],
            '2' => ['cancel', '_re_auth'],
            '10' => ['sales_confirm', 'cancel', '_re_auth']
        ]
    ],
    'unionpay' => [
        'status' => [
            '2' => ['cancel', '_multiple_refund']
        ]
    ],
    'recruitc' => [
        'status' => [
            '2' => ['cancel', '_multiple_refund'],
            '10' => ['sales_confirm', 'cancel', '_multiple_refund']
        ]
    ],
    'docomo' => [
        'status' => [
            '2' => ['cancel'],
            '10' => ['sales_confirm', 'cancel']
        ]
    ],
    'softbank2' => [
        'status' => [
            '2' => ['cancel'],
            '10' => ['sales_confirm', 'cancel']
        ]
    ],
    'auone' => [
        'status' => [
            '2' => ['cancel'],
            '10' => ['sales_confirm', 'cancel']
        ]
    ],
    'prepaid' => [
        'status' => [
            '2' => ['cancel']
        ]
    ],
    'yahoowallet' => [
        'status' => [
            '2' => ['refund', '_cancel'],
            '10' => ['sales_confirm', 'cancel']
        ]
    ],
    'rakuten' => [
        'status' => [
            '2' => ['cancel'],
            '10' => ['sales_confirm', 'cancel']
        ]
    ],
    'alipay' => [
        'status' => [
            '2' => ['cancel']
        ]
    ],
    'paypal' => [
        'status' => [
            '2' => ['cancel']
        ]
    ],
    'linepay' => [
        'status' => [
            '2' => ['cancel'],
            '10' => ['sales_confirm', 'cancel'],
        ]
    ],
    'saisonpoint' => [
        'status' => [
            '2' => ['cancel']
        ]
    ],
    'tpoint' => [
        'status' => [
            '2' => ['cancel']
        ]
    ],
    'webcvs' => [
        'all' => ['update_payment_info'],
    ],
    'applepay' => [
        'all' => ['update_payment_info'],
        'status' => [
            '1' => ['sales_confirm', 'cancel', '_re_auth'],
            '2' => ['refund', '_cancel', '_multiple_refund', '_re_auth'],
            '5' => ['refund', '_cancel', '_multiple_refund', '_re_auth'],
        ]
    ],
    'paypay' => [
        'all' => ['update_payment_info'],
        'status' => [
            '1' => ['sales_confirm', 'cancel'],
            '3' => ['cancel', '_partial_refund'],
            '10' => ['sales_confirm', 'cancel']
        ]
    ],
    'rb_credit' => [
        'charge' => ['cancel_contract']
    ],
    'rb_credit3d' => [
        'charge' => ['cancel_contract']
    ],
    'rb_recruitc' => [
        'charge' => ['cancel_contract']
    ],
    'rb_docomo' => [
        'all' => ['refund'],
        'charge' => ['cancel_contract']
    ],
    'rb_auone' => [
        'all' => ['refund'],
        'charge' => ['cancel_contract']
    ],
    'rp_credit' => [
        'purchase' => ['cancel_contract'],
        'all' => ['update_payment_info'],
        'status' => [
            '1' => ['sales_confirm', 'cancel', '_re_auth'],
            '2' => ['cancel', '_re_auth']
        ]
    ],
    'rp_credit3d' => [
        'purchase' => ['cancel_contract'],
        'all' => ['update_payment_info'],
        'status' => [
            '1' => ['sales_confirm', 'cancel', '_re_auth'],
            '2' => ['cancel', '_re_auth']
        ]
    ],
    'rp_recruitc' => [
        'purchase' => ['cancel_contract'],
        'status' => [
            '1' => ['sales_confirm', 'cancel'],
            '2' => ['cancel', '_multiple_refund'],
        ]
    ],
    'rp_softbank2' => [
        'purchase' => ['cancel_contract'],
        'status' => [
            '1' => ['sales_confirm', 'cancel'],
            '2' => ['cancel'],
        ]
    ],
    'rp_docomo' => [
        'purchase' => ['cancel_contract'],
        'status' => [
            '2' => ['cancel'],
        ]
    ],
    'rp_auone' => [
        'purchase' => ['cancel_contract'],
        'status' => [
            '1' => ['sales_confirm', 'cancel'],
            '2' => ['cancel'],
        ]
    ],
    'rp_rakuten' => [
        'purchase' => ['cancel_contract'],
        'status' => [
            '1' => ['sales_confirm', 'cancel'],
            '2' => ['cancel'],
        ]
    ],
];

// リクエストID
const SBPS_REQUEST_ID = [
    'credit' => [
        'credit' => 'ST01-00131-101',
        'confirm' => 'ST02-00101-101',
        'sales' => 'ST02-00201-101',
        'cancel' => 'ST02-00303-101',
        're_auth' => 'ST01-00133-101',
        'reference' => 'MG01-00101-101'
    ],
    'credit3d' => [
        'credit' => 'ST01-00131-101',
        'confirm' => 'ST02-00101-101',
        'sales' => 'ST02-00201-101',
        'cancel' => 'ST02-00303-101',
        're_auth' => 'ST01-00133-101',
        'reference' => 'MG01-00101-101'
    ],
    'unionpay' => [
        'cancel' => 'ST02-00308-514',
    ],
    'recruitc' => [
        'sales' => 'ST02-00202-309',
        'cancel' => 'ST02-00306-309',
        'multiple_refund' => 'ST02-00308-309',
    ],
    'docomo' => [
        'sales' => 'ST02-00201-401',
        'cancel' => 'ST02-00303-401'
    ],
    'auone' => [
        'sales' => 'ST02-00201-402',
        'cancel' => 'ST02-00303-402'
    ],
    'softbank2' => [
        'sales' => 'ST02-00201-405',
        'cancel' => 'ST02-00303-405'
    ],
    'prepaid' => [
        'cancel' => 'ST02-00303-513'
    ],
    'yahoowallet' => [
        'sales' => 'ST02-00201-304',
        'cancel' => 'ST02-00301-304',
        'refund' => 'ST02-00303-304'
    ],
    'rakuten' => [
        'sales' => 'ST02-00201-305',
        'cancel' => 'ST02-00306-305',
        'amount_change' => 'ST02-00502-305'
    ],
    'alipay' => [
        'cancel' => 'ST02-00306-510'
    ],
    'paypal' => [
        'cancel' => 'ST02-00303-306'
    ],
    'linepay' => [
        'sales' => 'ST02-00202-310',
        'cancel' => 'ST02-00306-310',
    ],
    'saisonpoint' => [
        'cancel' => 'ST02-00303-805'
    ],
    'webcvs' => [
        'reference' => 'MG01-00101-701'
    ],
    'tpoint' => [
        'cancel' => 'MG01-00101-806'
    ],
    'applepay' => [
        'sales' => 'ST02-00201-601',
        'cancel' => 'ST02-00301-601',
        'refund' => 'ST02-00401-601',
        'refund_confirm' => 'ST02-00501-601',
        're_auth' => 'ST01-00601-601'
    ],
    'paypay' => [
        'sales' => 'ST02-00201-311',
        'cancel' => 'ST02-00303-311',
        'reference' => 'MG01-00101-311'
    ],
    'rb_credit' => [
        'credit' => 'ST01-00132-101',
        'confirm' => 'ST02-00101-101',
        'cancel_contract' => 'ST02-00302-101',
    ],
    'rb_credit3d' => [
        'credit' => 'ST01-00132-101',
        'confirm' => 'ST02-00101-101',
        'cancel_contract' => 'ST02-00302-101',
    ],
    'rb_recruitc' => [
        'cancel_contract' => 'ST02-00302-309',
    ],
    'rb_docomo' => [
        'cancel_contract' => 'ST02-00302-401',
        'refund' => 'ST02-00303-401'
    ],
    'rb_auone' => [
        'cancel_contract' => 'ST02-00302-402',
        'refund' => 'ST02-00303-402'
    ],
    'rb_yahoowalletdg' => [
        'cancel_contract' => 'ST02-00302-307',
    ],
    'rp_credit' => [
        'credit' => 'ST01-00131-101',
        'confirm' => 'ST02-00101-101',
        'sales' => 'ST02-00201-101',
        'cancel' => 'ST02-00303-101',
        're_auth' => 'ST01-00133-101',
        'reference' => 'MG01-00101-101'
    ],
    'rp_credit3d' => [
        'credit' => 'ST01-00131-101',
        'confirm' => 'ST02-00101-101',
        'sales' => 'ST02-00201-101',
        'cancel' => 'ST02-00303-101',
        're_auth' => 'ST01-00133-101',
        'reference' => 'MG01-00101-101'
    ],
    'rp_recruitc' => [
        'purchase' => 'ST01-00104-309',
        'cancel_contract' => 'ST02-00309-309',
        'sales' => 'ST02-00202-309',
        'cancel' => 'ST02-00306-309',
        'multiple_refund' => 'ST02-00308-309',
    ],
    'rp_softbank2' => [
        'purchase' => 'ST01-00104-405',
        'sales' => 'ST02-00201-405',
        'cancel' => 'ST02-00303-405'
    ],
    'rp_docomo' => [
        'purchase' => 'ST01-00104-401',
        'cancel_contract' => 'ST02-00309-401',
        'cancel' => 'ST02-00303-401'
    ],
    'rp_auone' => [
        'purchase' => 'ST01-00104-402',
        'cancel_contract' => 'ST02-00309-402',
        'sales' => 'ST02-00201-402',
        'cancel' => 'ST02-00303-402'
    ],
    'rp_rakuten' => [
        'purchase' => 'ST01-00104-305',
        'cancel_contract' => 'ST02-00309-305',
        'sales' => 'ST02-00201-305',
        'cancel' => 'ST02-00306-305',
    ],
];