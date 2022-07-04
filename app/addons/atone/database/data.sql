INSERT INTO cscart_payment_processors (processor_id, processor, processor_script, processor_template, admin_template, callback, type, addon) VALUES ('9088', 'atone', 'atone.php', 'addons/atone/views/orders/components/payments/atone.tpl', 'atone.tpl', 'Y', 'B', 'atone');
ALTER TABLE cscart_users ADD user_token varchar(255);
ALTER TABLE cscart_orders ADD transaction_no varchar(255);
ALTER TABLE cscart_orders ADD tr_id varchar(255);