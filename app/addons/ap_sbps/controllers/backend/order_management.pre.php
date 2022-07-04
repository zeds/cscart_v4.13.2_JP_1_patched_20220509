<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'place_order' && $action !== 'save') {
    Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : [];
    Tygh::$app['session']['cart_origin'] = Tygh::$app['session']['cart'];

    Tygh::$app['session']['customer_auth'] = isset(Tygh::$app['session']['customer_auth']) ? Tygh::$app['session']['customer_auth'] : array();
    Tygh::$app['session']['customer_auth_origin'] = Tygh::$app['session']['customer_auth'];
}