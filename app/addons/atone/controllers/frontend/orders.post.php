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

// $Id: orders.post.php from andplus 2020

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'details') {
	
	$repays = Tygh::$app['view']->getTemplateVars('repays');
	//除外する決済のprocessor_idを設定
	$repays[] = '9088';
	
	Tygh::$app['view']->assign('repays', $repays);

}