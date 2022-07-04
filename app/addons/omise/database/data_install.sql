INSERT INTO cscart_payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('omise（カード決済）', 'omise_cc.php', 'addons/omise/views/orders/components/payments/omise_cc.tpl', 'omise_cc.tpl', 'N', 'P');

INSERT INTO cscart_payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('omise（登録済みカード決済）', 'omise_ccreg.php', 'addons/omise/views/orders/components/payments/omise_ccreg.tpl', 'omise_ccreg.tpl', 'N', 'P');

CREATE TABLE IF NOT EXISTS cscart_jp_omise_cc_status (order_id mediumint(8) unsigned NOT NULL, status_code varchar(32) NOT NULL DEFAULT '', access_id varchar(32) NOT NULL DEFAULT '', access_pass varchar(32) NOT NULL DEFAULT '', auth_timestamp int(11) unsigned NOT NULL DEFAULT '0', capture_timestamp int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS cscart_jp_omise_deleted_quickpay (user_id mediumint(8) NOT NULL, quickpay_id varchar(64) NOT NULL, PRIMARY KEY (user_id)) Engine=MyISAM DEFAULT CHARSET UTF8;
