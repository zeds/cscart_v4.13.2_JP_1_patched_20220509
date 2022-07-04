INSERT INTO cscart_payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('ソニーペイメントサービス - キャリア決済（都度決済）', 'sonypayment_carrier_ep.php', 'addons/sonypayment_carrier/views/orders/components/payments/sonypayment_carrier_ep.tpl', 'sonypayment_carrier_ep.tpl', 'N', 'P');

INSERT INTO cscart_payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('ソニーペイメントサービス - キャリア決済（継続課金）', 'sonypayment_carrier_rb.php', 'addons/sonypayment_carrier/views/orders/components/payments/sonypayment_carrier_rb.tpl', 'sonypayment_carrier_rb.tpl', 'N', 'P');

CREATE TABLE IF NOT EXISTS cscart_jp_sonyc_process_info (order_id mediumint(8) unsigned NOT NULL, process_id varchar(64) NOT NULL, process_pass varchar(64) NOT NULL, PRIMARY KEY  (`order_id`)) Engine=MyISAM DEFAULT CHARSET UTF8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonyc_status (order_id mediumint(8) unsigned NOT NULL, status_code varchar(32) NOT NULL default '', PRIMARY KEY (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonyc_rb_products (product_id mediumint(8) unsigned NOT NULL, carrier_cd char(2) NOT NULL DEFAULT "", first_payment_day int NOT NULL DEFAULT 0, payment_day int NOT NULL DEFAULT 0, PRIMARY KEY (`product_id`, `carrier_cd`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;