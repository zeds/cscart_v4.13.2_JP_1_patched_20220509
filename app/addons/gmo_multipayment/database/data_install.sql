INSERT INTO cscart_payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9201, 'PGマルチペイメントサービス（プロトコルタイプ・登録済みカード決済）', 'gmo_multipayment_ccreg.php', 'addons/gmo_multipayment/views/orders/components/payments/gmo_multipayment_ccreg.tpl', 'gmo_multipayment_ccreg.tpl', 'N', 'P');

INSERT INTO cscart_payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9202, 'PGマルチペイメントサービス（プロトコルタイプ・コンビニ決済）', 'gmo_multipayment_cvs.php', 'addons/gmo_multipayment/views/orders/components/payments/gmo_multipayment_cvs.tpl', 'gmo_multipayment_cvs.tpl', 'N', 'P');

INSERT INTO cscart_payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9203, 'PGマルチペイメントサービス（プロトコルタイプ・ペイジー決済）', 'gmo_multipayment_payeasy.php', 'views/orders/components/payments/cc_outside.tpl', 'gmo_multipayment_payeasy.tpl', 'N', 'P');

/* トークン決済に対応 */
INSERT INTO cscart_payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9204, 'PGマルチペイメントサービス（プロトコルタイプ・カード決済・トークン決済）', 'gmo_multipayment_cctkn.php', 'addons/gmo_multipayment/views/orders/components/payments/gmo_multipayment_cctkn.tpl', 'gmo_multipayment_cctkn.tpl', 'N', 'P');

CREATE TABLE IF NOT EXISTS cscart_jp_gmomp_cc_status (order_id mediumint(8) unsigned NOT NULL, status_code varchar(32) NOT NULL DEFAULT '', access_id varchar(32) NOT NULL DEFAULT '', access_pass varchar(32) NOT NULL DEFAULT '', auth_timestamp int(11) unsigned NOT NULL DEFAULT '0', capture_timestamp int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;
