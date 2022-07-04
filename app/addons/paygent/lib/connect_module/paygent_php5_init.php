<?php
// $Id: func.php by takahashi from cs-cart.jp 2017

use Tygh\Registry;

set_include_path(Registry::get('config.dir.addons') . 'paygent/lib/connect_module/');
include_once("jp/co/ks/merchanttool/connectmodule/entity/ResponseDataFactory.php");
include_once("jp/co/ks/merchanttool/connectmodule/system/PaygentB2BModule.php");

class paygent_php5_init
{
    public function getModule() {
        return new PaygentB2BModule();
    }
}
?>
