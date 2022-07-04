<?php
// $Id: func.php by takahashi from cs-cart.jp 2017

use Tygh\Registry;

include_once(Registry::get('config.dir.addons') . "paygent/lib/connect_module/autoload.php");
use PaygentModule\System\PaygentB2BModule;

class paygent_php7_init
{
    public function getModule() {
        return new PaygentB2BModule();
    }
}
?>
