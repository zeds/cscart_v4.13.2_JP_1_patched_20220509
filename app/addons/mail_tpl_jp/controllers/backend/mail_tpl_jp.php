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

// $Id: mail_tpl_jp.php by tommy from cs-cart.jp 2013

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	fn_trusted_vars('mail_template_data');

	$suffix = '.manage';

	// メールテンプレートの更新
	if ($mode == 'update') {
		fn_mtpl_update_mail_template($_REQUEST['mail_template_data'], $_REQUEST['tpl_id'], DESCR_SL);
		return array(CONTROLLER_STATUS_OK, "mail_tpl_jp.update&tpl_id=" . $_REQUEST['tpl_id']);
	}

	return array(CONTROLLER_STATUS_OK, "mail_tpl_jp" . $suffix);
}




// メールテンプレートの編集ページ
if ($mode == 'update') {

	// テンプレート変数を初期化
	$mail_tpl_var = array();

	$tpl_id = !empty($_REQUEST['tpl_id']) ? intval($_REQUEST['tpl_id']) : 0;

	$mail_template_data = fn_mtpl_get_mail_template_data($tpl_id, DESCR_SL);

	if (empty($mail_template_data)) {
		return array(CONTROLLER_STATUS_NO_PAGE);
	}

	if( ($mail_template_data['tpl_code'] != 'orders_order_notification') && strpos($mail_template_data['tpl_code'],'orders_order_notification') !== false ){
        $mail_template_data['tpl_code'] = 'orders_order_notification';
    }

	// 各メールテンプレートで利用可能なテンプレート変数が定義されたファイル名
	$filename = Registry::get('config.dir.addons') . 'mail_tpl_jp/tpl_variants/' . $mail_template_data['tpl_code'] . '.php';

	// フックポイントを設定
	fn_set_hook('get_addons_mail_tpl', $mail_template_data['tpl_code'], $filename);

	// 該当するメールテンプレートが存在する場合
	if( file_exists($filename) ){
		// 各メールテンプレートで利用可能なテンプレート変数が定義されたファイルを読み込み
		$tpl_base_data = Registry::get('view')->tpl_vars;
		$_edit_mail_tpl = true;
		require_once($filename);

		// すべてのメールテンプレートで利用可能なテンプレート変数を取得
		$mail_tpl_common_var = fn_mtpl_get_common_tpl_var($tpl_base_data);

	}

    Registry::get('view')->assign('mail_template', $mail_template_data);

    Registry::get('view')->assign('tpl_vars', $mail_tpl_var);

    Registry::get('view')->assign('tpl_common_vars', $mail_tpl_common_var);

// メールテンプレートの一覧ページ
} elseif ($mode == 'manage') {
    // 登録済みメールテンプレートと表示対象となるページ番号などを取得してSmarty変数にセット
    list($mail_templates, $search) = fn_mtpl_get_mail_templates($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    Registry::get('view')->assign('mail_templates', $mail_templates);
    Registry::get('view')->assign('search', $search);
}
