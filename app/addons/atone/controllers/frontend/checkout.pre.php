<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2009 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// $Id: checkout.pre.php by sun from andplus 2018

use Tygh\Http;
use Tygh\Registry;
use Tygh\Mailer;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'checkout') {

	//session(カートデータ)
	$ap_session = $_SESSION['cart'];

	//登録済みの事前トークンを呼び出しておく
	if(!empty($ap_session['user_data']['user_id'])) {
		$pre_token = db_get_field('SELECT user_token FROM ?:users WHERE user_id = ?i', $ap_session['user_data']['user_id']);
		if(!empty($pre_token)) {
			//事前トークンが存在するようならassign
			Tygh::$app['view']->assign('pre_token', $pre_token);
		}
	}

	//POSTした時
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     
		//事前トークンの取得
		if(!empty($_POST['pre_token']) && $ap_session['user_data']['user_id'] > 0) {
			$pre_token = $_POST['pre_token'];
			db_query('UPDATE ?:users SET user_token = ?s WHERE user_id = ?i',$pre_token,$ap_session['user_data']['user_id']);
		}
		//transaction_noの取得
		if(!empty($_REQUEST['transaction_no'])) {
			$transaction_no = $_REQUEST['transaction_no'];
			//sessionに値を格納
			$_SESSION['transaction_no'] = $transaction_no;
		}   
	}
  
	if(!empty($_REQUEST['transaction_id'])) {
		$transaction_id = $_REQUEST['transaction_id'];
		//sessionに値を格納
		$_SESSION['transaction_id'] = $transaction_id;
	}
	
    
//注文完了後、ordersテーブルにtransaction_noを格納
}elseif ($mode == 'complete') {

	if(!empty($_SESSION['transaction_no'])) {
		//transaction_no格納
		db_query('UPDATE ?:orders SET transaction_no = ?s WHERE order_id = ?i',$_SESSION['transaction_no'],$_REQUEST['order_id']);
		//終わったらsession内のtransaction_noを空に
		$_SESSION['transaction_no'] = "";
	}
	
	if(!empty($_SESSION['transaction_id'])) {
		//transaction_no格納
		db_query('UPDATE ?:orders SET tr_id = ?s WHERE order_id = ?i',$_SESSION['transaction_id'],$_REQUEST['order_id']);
		//終わったらsession内のtransaction_noを空に
		$_SESSION['transaction_id'] = "";
	}
  
  
}