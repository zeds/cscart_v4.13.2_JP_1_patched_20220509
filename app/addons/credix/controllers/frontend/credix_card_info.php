<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2004 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: credix_card_info.php by tommy from cs-cart.jp 2014

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// 登録済みカード情報の削除
if ($mode == 'delete') {
	fn_crdx_delete_qc_info($auth['user_id']);
	return array(CONTROLLER_STATUS_REDIRECT, "credix_card_info.view");

// 登録済みカード情報の確認
} elseif ($mode == 'view') {
	// パン屑リストを生成
	fn_add_breadcrumb(__('jp_credix_qc_registered_card'));

	// 登録済みのQuick Charge用ID（sendid）を取得
	$qc_info = fn_crdx_get_sendid($auth['user_id']);

    // Quick Charge用ID（sendid）が登録済みの場合
    if( !empty($qc_info) ){
        $qc_exists = 'Y';
    }else{
        $qc_exists = 'N';
    }

    Registry::get('view')->assign('qc_exists', $qc_exists);
}
