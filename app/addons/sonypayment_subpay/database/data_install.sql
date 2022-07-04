INSERT INTO cscart_payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('ソニーペイメントサービス - 定期購入', 'sonypayment_subpay.php', 'addons/sonypayment_subpay/views/orders/components/payments/sonypayment_subpay.tpl', 'sonypayment_subpay.tpl', 'N', 'P');


CREATE TABLE IF NOT EXISTS cscart_jp_sonys_deleted_quickpay (
            user_id mediumint(8) NOT NULL,
            quickpay_id varchar(64) NOT NULL,
            mode VARCHAR(5) NOT NULL,
            PRIMARY KEY  (user_id, mode)) Engine=MyISAM DEFAULT CHARSET UTF8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonys_process_info (
            order_id mediumint(8) unsigned NOT NULL,
            process_id varchar(64) NOT NULL,
            process_pass varchar(64) NOT NULL,
            PRIMARY KEY  (order_id)) Engine=MyISAM DEFAULT CHARSET UTF8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonys_status (
            order_id mediumint(8) unsigned NOT NULL,
            status_code varchar(32) NOT NULL default '',
            PRIMARY KEY (order_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonys_kaiin_change (
			user_id mediumint(8) NOT NULL,
			change_cnt mediumint(8) NOT NULL,
			PRIMARY KEY  (user_id)) Engine=MyISAM DEFAULT CHARSET UTF8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonys_companies (
			company_id int(11) NOT NULL,
			tenant_id varchar(10) NOT NULL,
			PRIMARY KEY  (company_id)) Engine=MyISAM DEFAULT CHARSET UTF8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonys_products (
			product_id mediumint(8) unsigned NOT NULL,
			second_price decimal(12,0) NOT NULL,
			deliver_day_w varchar(255) NULL,
			deliver_day_bw varchar(255) NULL,
			deliver_day_m varchar(255) NULL,
			deliver_day_bm varchar(255) NULL,
			deliver_time varchar(255) NULL,
			start_date int(11) NOT NULL,
			end_date int(11) NOT NULL,
			PRIMARY KEY  (product_id)) Engine=MyISAM DEFAULT CHARSET UTF8;
			
CREATE TABLE IF NOT EXISTS cscart_jp_sonys_subsc_manager (
             subpay_id int(11) NOT NULL,
             user_id int(11) NOT NULL,
             user_name varchar(255) NOT NULL,
             product_id int(11) NOT NULL,
             product varchar(255) NOT NULL,
             status varchar(1) NOT NULL,
             next_payment_date int(11) NOT NULL,
             deliver_freq varchar(2) NOT NULL,
             deliver_day varchar(5) NOT NULL,
             deliver_time varchar(10) NOT NULL,
             cr_timestamp int(11) NOT NULL,
             up_timestamp int(11) NOT NULL,
             PRIMARY KEY (subpay_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            
CREATE TABLE IF NOT EXISTS cscart_jp_sonys_subsc_history (
             subpay_id int(11) NOT NULL,
             order_id mediumint(8) NOT NULL,
             PRIMARY KEY (subpay_id,order_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS cscart_jp_sonys_subsc_ship_address (
            subpay_id int(11) NOT NULL,
            s_zipcode varchar(16) CHARACTER SET utf8 DEFAULT NULL,
            s_state varchar(32) CHARACTER SET utf8 DEFAULT NULL,
            s_city varchar(255) CHARACTER SET utf8 DEFAULT NULL,
            s_address varchar(255) CHARACTER SET utf8 DEFAULT NULL,
            s_address_2 varchar(255) CHARACTER SET utf8 DEFAULT NULL,
            s_phone varchar(128) CHARACTER SET utf8 DEFAULT NULL,
            PRIMARY KEY (subpay_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=UTF8;