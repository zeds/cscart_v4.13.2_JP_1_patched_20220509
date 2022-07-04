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

// $Id: companies.post.php by takahashi from cs-cart.jp 2019

use Tygh\Registry;

if (!fn_allowed_for('MULTIVENDOR')) {
    return;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // company_id
    $company_id = $_REQUEST['company_id'];

    // tenant_id
    $tenant_id = $_REQUEST['tenant_id'];

    // tenant_id が存在する場合は登録・更新
    if( !empty($tenant_id) ) {
        db_query("REPLACE INTO ?:jp_sln_companies VALUES(?i, ?s)", $company_id, $tenant_id);
    }
}

if ($mode == 'update') {
    if($auth['user_type'] == 'A') {
        // ソニーペイメントタブの設定
        Registry::set('navigation.tabs.smartlink', array(
            'title' => __('jp_sln_sonypayment'),
            'js' => true,
        ));

        // company_id
        $company_id = $_REQUEST['company_id'];

        // tenant_id
        $tenant_id = db_get_field("SELECT tenant_id FROM ?:jp_sln_companies WHERE company_id = ?i", $company_id);

        Tygh::$app['view']->assign('tenant_id', $tenant_id);
        Tygh::$app['view']->assign('smartlink', true);
    }
}
