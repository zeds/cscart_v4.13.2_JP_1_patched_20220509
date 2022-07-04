<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// Modified by tommy from cs-cart.jp 2017

use Tygh\Bootstrap;
use Tygh\Debugger;
use Tygh\Helpdesk;
use Tygh\Registry;

// Register autoloader
$this_dir = dirname(__FILE__);
$classLoader = require($this_dir . '/app/lib/vendor/autoload.php');
$classLoader->add('Tygh', $this_dir . '/app');
class_alias('\Tygh\Tygh', 'Tygh');
class_alias('\Tygh\Enum\ReceiverSearchMethods', '\Tygh\Enum\RecipientSearchMethods');

// Prepare environment and process request vars
list($_REQUEST, $_SERVER, $_GET, $_POST) = Bootstrap::initEnv($_GET, $_POST, $_SERVER, $this_dir);

// Get config data
$config = require(DIR_ROOT . '/config.php');

///////////////////////////////////////////////////////////////
// Modified for Japanese Ver by tommy from cs-cart.jp 2017 BOF
// CS-Cart日本語版のバージョン情報を表示
///////////////////////////////////////////////////////////////
// パラメータに「version」が含まれる場合
if (isset($_REQUEST['version'])) {
    //.管理画面へのアクセスの場合
    if( AREA == 'A' ){
        // 日本語版のバージョン情報表示用ファイルが存在する場合
        if( file_exists(dirname(__FILE__) . '/app/addons/localization_jp/lib/jp_version.php') ){
            // 日本語版のバージョン情報表示用ファイルを読み込み
            require dirname(__FILE__) . '/app/addons/localization_jp/lib/jp_version.php';
        // 日本語版のバージョン情報表示用ファイルが存在しない場合
        }else{
            // 通常のバージョン情報を表示
            die(PRODUCT_NAME . ' <b>' . PRODUCT_VERSION . ' ' . (PRODUCT_STATUS != '' ? (' (' . PRODUCT_STATUS . ')') : '') . (PRODUCT_BUILD != '' ? (' ' . PRODUCT_BUILD) : '') . '</b>');
        }
    // ショップフロントへのアクセスの場合
    }else{
        // パラメータ "version" に値がセットされている場合は、バージョンチェック以外の目的で
        // 使用されていると見なす（例：SMBCファイナンスサービスによる入金通知）
        if( empty($_REQUEST['version']) ){
            die('Access not permitted');
        }
    }
}
///////////////////////////////////////////////////////////////
// Modified for Japanese Ver by tommy from cs-cart.jp 2017 EOF
///////////////////////////////////////////////////////////////

Debugger::init(false, $config);

// Start debugger log
Debugger::checkpoint('Before init');

// Callback: verifies if https works
if (isset($_REQUEST['check_https'])) {
    die(defined('HTTPS') ? 'OK' : '');
}

// Check if software is installed
if ($config['db_host'] == '%DB_HOST%') {
    ///////////////////////////////////////////////////////////////
    // Modified for Japanese Ver by tommy from cs-cart.jp 2017 BOF
    // インストール開始案内を日本語化
    ///////////////////////////////////////////////////////////////
    //die(PRODUCT_NAME . ' is <b>not installed</b>. Please click here to start the installation process: <a href="install/">[install]</a>');
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    echo '<html xmlns="http://www.w3.org/1999/xhtml">';
    echo '<head>';
    echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
    echo '<title>CS-Cart日本語版インストール</title>';
    echo '</head>';
    echo '<body>';
    echo PRODUCT_NAME_JP . 'は<b>インストールされていません</b>。 右のリンクをクリックしてインストールを開始してください: <a href="install/">[インストール]</a>';
    echo '</body>';
    die();
    ///////////////////////////////////////////////////////////////
    // Modified for Japanese Ver by tommy from cs-cart.jp 2017 EOF
    ///////////////////////////////////////////////////////////////
}

// Load core functions
$fn_list = array(
    'fn.database.php',
    'fn.users.php',
    'fn.categories.php',
    'fn.features.php',
    'fn.filters.php',
    'fn.options.php',
    'fn.product_files.php',
    'fn.products.php',
    'fn.cms.php',
    'fn.cart.php',
    'fn.locations.php',
    'fn.common.php',
    'fn.fs.php',
    'fn.images.php',
    'fn.init.php',
    'fn.control.php',
    'fn.search.php',
    'fn.promotions.php',
    'fn.log.php',
    'fn.companies.php',
    'fn.addons.php',
    'fn.exim.php',
    'fn.order_management.php'
);

$fn_list[] = 'fn.' . strtolower(PRODUCT_EDITION) . '.php';

foreach ($fn_list as $file) {
    require($config['dir']['functions'] . $file);
}

Registry::set('config', $config);
unset($config);

$application = Tygh\Tygh::createApplication();
$application['class_loader'] = $classLoader;

// Register service providers
$application->register(new Tygh\Providers\DatabaseProvider());
$application->register(new Tygh\Providers\SessionProvider());
$application->register(new Tygh\Providers\AddonLoaderProvider());
$application->register(new Tygh\Providers\MailerProvider());
$application->register(new Tygh\Providers\TwigProvider());
$application->register(new Tygh\Providers\EnvironmentProvider());
$application->register(new Tygh\Providers\TemplateProvider());
$application->register(new Tygh\Providers\CommonProvider());
$application->register(new Tygh\Providers\ServerEnvironmentProvider(), [
    'server.env.ini_vars' => [
        'post_max_size'       => Bootstrap::getIniParam('post_max_size', true),
        'upload_max_filesize' => Bootstrap::getIniParam('upload_max_filesize', true),
        'disable_functions'   => Bootstrap::getIniParam('disable_functions', true),
        'safe_mode'           => Bootstrap::getIniParam('safe_mode'),
    ],
]);
$application->register(new Tygh\Providers\BackupperProvider());
$application->register(new Tygh\Providers\LockProvider());
$application->register(new Tygh\Providers\NotificationsCenterProvider());
$application->register(new Tygh\Providers\EventDispatcherProvider());
$application->register(new Tygh\Providers\VendorServicesProvider());
$application->register(new Tygh\Providers\LocationProvider());
$path = isset($_SERVER['REQUEST_URI'])
    ? $_SERVER['REQUEST_URI']
    : '';
$requested_url = REAL_HOST . $path;
$application->register(new Tygh\Providers\StorefrontProvider($requested_url, $_REQUEST));
$application->register(new Tygh\Providers\MarketplaceProvider());

if (isset($_REQUEST['version'])
    && AREA === 'A'
    && defined('ACCOUNT_TYPE')
    && ACCOUNT_TYPE === 'admin'
) {
    Helpdesk::getSoftwareInformation();
}

register_shutdown_function(['\\Tygh\\Registry', 'save']);

fn_init_stack(
    array('fn_init_error_handler'),
    array('fn_init_unmanaged_addons')
);

if (defined('API')) {
    fn_init_stack(
        array('fn_init_api')
    );
}

fn_init_stack(
    ['fn_init_crypt'],
    ['fn_init_imagine'],
    ['fn_init_archiver'],
    ['fn_init_storage'],
    ['fn_init_ua'],
    ['fn_init_redirect_to_regional_storefront', &$_REQUEST, $requested_url],
    ['fn_init_http_params_by_storefront', &$_REQUEST, $requested_url]
);

fn_init_stack(
    [function() use ($application) {
        $application['session']->init();
    }],
    ['fn_init_ajax'],
    ['fn_init_company_id', &$_REQUEST],
    ['fn_check_cache', $_REQUEST],
    ['fn_init_settings'],
    ['fn_init_addons'],
    ['fn_get_route', &$_REQUEST],
    ['fn_simple_ultimate', &$_REQUEST]
);

if (!Registry::get('config.tweaks.disable_localizations') && !fn_allowed_for('ULTIMATE:FREE')) {
    fn_init_stack(array('fn_init_localization', &$_REQUEST));
}

fn_init_stack(
    ['fn_init_language', &$_REQUEST],
    ['fn_init_currency', &$_REQUEST],
    ['fn_init_company_data', $_REQUEST],
    ['fn_init_full_path', $_REQUEST],
    ['fn_init_layout', &$_REQUEST],
    ['fn_init_user'],
    ['fn_init_templater'],
    ['fn_init_http_content_security']
);

// Run INIT
fn_init($_REQUEST);
