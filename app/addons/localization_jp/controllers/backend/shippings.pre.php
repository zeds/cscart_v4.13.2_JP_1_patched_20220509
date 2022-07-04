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

// $Id: shippings.pre.php by ari from cs-cart.jp 2015

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// 配送方法の更新時にその配送方法のお届け日指定データも更新する。
	if ($mode == 'update') {
		$_data = array();

		$_data = array('shipping_id' => (int)$_REQUEST['shipping_id'],
							'delivery_id' => !empty($_REQUEST['delivery_id']) ? (int)$_REQUEST['delivery_id'] : '',
							'delivery_status' => !empty($_REQUEST['delivery_status']) ? (int)$_REQUEST['delivery_status'] : '',
							'delivery_from' => !empty($_REQUEST['delivery_from']) ? (int)$_REQUEST['delivery_from'] : '',
							'delivery_to' => !empty($_REQUEST['delivery_to']) ? (int)$_REQUEST['delivery_to'] : '',
							'include_holidays' => !empty($_REQUEST['include_holidays']) ? (int)$_REQUEST['include_holidays'] : ''
							);

		// お届け日を表示する場合は日数を必須とする
		if( $_data['delivery_status'] == 1 && $_data['delivery_to'] == 0 ) {
			fn_set_notification('E', __('error'), __('jp_delivery_date_not_specified'));
		}

		db_query('REPLACE INTO ?:jp_delivery_date ?e', $_data);
	}

} else {

	// 配送方法の更新時にお届け日指定データを取得
	if ($mode == 'update') {

		$delivery_date = db_get_row("SELECT * FROM ?:jp_delivery_date WHERE shipping_id = ?i LIMIT 1", $_REQUEST['shipping_id']);

		// 新規登録直後は、shipping_idが「0」でお届け日データが設定されているのでshipping_idを正しい数値に変更
		if( empty($delivery_date) ) {
			db_query("UPDATE ?:jp_delivery_date SET shipping_id = ?i WHERE shipping_id = 0", $_REQUEST['shipping_id']);
			$delivery_date = db_get_row("SELECT * FROM ?:jp_delivery_date WHERE shipping_id = ?i LIMIT 1", $_REQUEST['shipping_id']);
		}

		if(!empty($delivery_date)) {
            Registry::get('view')->assign('delivery_date', $delivery_date);
		}
	}

	// 配送方法が削除された場合、その配送方法のお届け日指定データも削除する。
	if ($mode == 'delete') {
        if (!empty($_REQUEST['shipping_id'])) {
			db_query("DELETE FROM ?:jp_delivery_date WHERE shipping_id = ?i", $_REQUEST['shipping_id']);
		}
	}
}
