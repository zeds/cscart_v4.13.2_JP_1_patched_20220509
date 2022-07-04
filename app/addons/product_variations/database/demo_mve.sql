REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (278, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (279, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (280, 50.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (281, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 75.00, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 75.00, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 75.00, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 75.00, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 75.00, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 75.00, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 75.00, 0, 1, 0);

SET @category_id = (SELECT category_id FROM cscart_categories WHERE category_id = 224 OR status = 'A' ORDER BY category_id = 224 DESC LIMIT 1);

REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (278, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (279, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (280, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (281, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (282, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (283, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (284, @category_id, 'M', 0);

UPDATE cscart_product_features SET company_id = 0;
UPDATE cscart_product_filters SET company_id = 0;

/* Modified by takahashi from cs-cart.jp 2019 BOF */
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (278, 3000, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (279, 3000, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (280, 3600, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (281, 3700, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 2399, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 2599, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 2799, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 2399, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 2599, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 2799, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 2399, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 2599, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 2799, 0, 1, 0);

UPDATE `cscart_products` SET `list_price` = 7500 WHERE `product_id` = 278;
UPDATE `cscart_products` SET `list_price` = 7500 WHERE `product_id` = 279;
UPDATE `cscart_products` SET `list_price` = 5000 WHERE `product_id` = 280;
UPDATE `cscart_products` SET `list_price` = 7500 WHERE `product_id` = 281;
UPDATE `cscart_products` SET `list_price` = 7500 WHERE `product_id` = 282;
UPDATE `cscart_products` SET `list_price` = 7500 WHERE `product_id` = 283;
UPDATE `cscart_products` SET `list_price` = 7500 WHERE `product_id` = 284;
/* Modified by takahashi from cs-cart.jp 2019 EOF */