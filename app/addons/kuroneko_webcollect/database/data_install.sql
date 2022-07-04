INSERT INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9232, 'クロネコwebコレクト（コンビニ（オンライン）払い）', 'krnkwc_cvs.php', 'addons/kuroneko_webcollect/views/orders/components/payments/krnkwc_cvs.tpl', 'krnkwc_cvs.tpl', 'N', 'P');

INSERT INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9233, 'クロネコwebコレクト（クレジットカード払い・トークン方式）', 'krnkwc_cctkn.php', 'addons/kuroneko_webcollect/views/orders/components/payments/krnkwc_cctkn.tpl', 'krnkwc_cctkn.tpl', 'N', 'P');

INSERT INTO ?:payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('クロネコwebコレクト（登録済みクレジットカード払い・トークン方式）', 'krnkwc_ccrtn.php', 'addons/kuroneko_webcollect/views/orders/components/payments/krnkwc_ccrtn.tpl', 'krnkwc_ccrtn.tpl', 'N', 'P');

INSERT INTO ?:payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES (9240, 'クロネコ代金後払いサービス', 'krnkab.php', 'addons/kuroneko_webcollect/views/orders/components/payments/krnkab.tpl', 'krnkab.tpl', 'N', 'P');

CREATE TABLE IF NOT EXISTS ?:jp_krnkwc_cc_status (order_id mediumint(8) unsigned NOT NULL, status_code varchar(32) NOT NULL default '', order_no mediumtext NOT NULL, PRIMARY KEY (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ?:jp_krnkwc_shipments (shipment_id mediumint(8) unsigned NOT NULL, tracking_url mediumtext NOT NULL, PRIMARY KEY (`shipment_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ?:jp_krnkwc_all_shipments (order_id mediumint(8) unsigned NOT NULL, tracking_number	varchar(255) NOT NULL, carrier varchar(255) NOT NULL, delivery_service_code varchar(2) NOT NULL, 	timestamp	int(11) NOT NULL, PRIMARY KEY (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ?:jp_krnkwc_3dsecure (order_id mediumint(8) unsigned NOT NULL, three_d_token	varchar(255) NOT NULL, order_no varchar(255) NOT NULL, auth_key varchar(255) NOT NULL, PRIMARY KEY (order_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ?:jp_krnkwc_notes (user_id mediumint(8) unsigned NOT NULL, notes	text NOT NULL, PRIMARY KEY (user_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;