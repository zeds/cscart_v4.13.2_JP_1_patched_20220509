INSERT INTO ?:payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('クロネコ掛け払い', 'kuroneko_kakebarai.php', 'addons/kuroneko_kakebarai/views/orders/components/payments/kuroneko_kakebarai.tpl', 'kuroneko_kakebarai.tpl', 'N', 'P');

CREATE TABLE ?:jp_krnkkb_status (order_id mediumint(8) unsigned NOT NULL, status_code varchar(32) NOT NULL default '', PRIMARY KEY (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

