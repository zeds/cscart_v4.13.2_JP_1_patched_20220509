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

// $Id: paygent_ccreg.php by tommy from cs-cart.jp 2016
// ペイジェント（コンビニ決済）

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ショップフロントもしくは注文の編集でペイジェントに接続して決済手続きを再実行する場合
if( ($mode == 'place_order' || $mode == 'process' || $mode == 'repay') && (AREA == 'C' || (AREA == 'A' && Registry::get('runtime.action') != 'save')) ){

    // 処理タイプを初期化
    $type = 'cvs';

    // ペイジェントに送信する取引登録用パラメータをセット
    $params = array();
    $params = fn_pygnt_get_params($type, $order_id, $order_info, $processor_data);

    // 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
    $p = '';
    fn_pygnt_initialize_module($p);

    // ペイジェントに要求を送信
    $result = fn_pygnt_send_params($p, $params);

    // データ送信においてエラーが発生した場合
    if( !($result === true) ) {
        // 通信エラー処理
        fn_pygnt_handle_process_error($result);

    // データ送信が正常に終了した場合
    }else{
        // 処理結果を取得
        $resultStatus = $p->getResultStatus();

        // 処理にエラーが発生している場合
        if( $resultStatus == 1 ) {
            // レスポンスコードを取得
            $responseCode = $p->getResponseCode();

            // レスポンス詳細を取得
            $responseDetail = mb_convert_encoding($p->getResponseDetail(), 'UTF-8', 'Shift-JIS');

            // 注文手続きページにリダイレクトしてエラーメッセージを表示
            fn_pygnt_handle_process_error($responseCode, $responseDetail);

        // コンビニ収納依頼が正常終了した場合
        }else{

            // CS-Cart側でのコンビニ決済情報登録に必要なデータが存在する場合
            if( $p->hasResNext() ) {
                $res_array = $p->resNext();

                if( !empty($res_array) ){
                    foreach($res_array as $key => $val){
                        $res_array[$key] = mb_convert_encoding($val, 'UTF-8', 'Shift-JIS');
                    }
                }

            // CS-Cart側でのコンビニ決済情報登録に必要なデータが存在しない場合
            }else{
                $res_array = array();
            }

            // 注文IDと利用した支払方法がマッチした場合
            if( fn_check_payment_script('paygent_cvs.php', $order_id) ){

                // コメント欄にコンビニ決済情報をセット
                fn_pygnt_cvs_set_comments($order_id, $payment_info, $res_array, $params);

                // DBに保管する支払い情報を生成
                fn_pygnt_format_payment_info('cvs', $order_id, $payment_info, $res_array);

                // コンビニ決済に関するメッセージを表示
                fn_set_notification('I', __('jp_paygent_cvs_notification_title'),  __('jp_paygent_cvs_notification_message'));

                // 注文処理ページへリダイレクト
                $pp_response = array();

                // セブンイレブンの後払いのみ注文ステータスに"P"をセット。残りは"O"をセット
                if( !empty($params['sales_type']) && $params['sales_type'] == 3 ){
                    $pp_response['order_status'] = 'P';
                }else{
                    $pp_response['order_status'] = 'O';
                }

                fn_finish_payment($order_id, $pp_response);
                fn_order_placement_routines('route', $order_id);

            // 注文IDと利用した支払方法がマッチしなかった場合
            }else{
                // 表示するエラー内容を生成
                $responseinfo = __("jp_paygent_payment_unmatch_error");

                // 注文手続きページにリダイレクトしてエラーメッセージを表示
                fn_pygnt_handle_process_error($responseinfo);
            }
        }
    }
}
