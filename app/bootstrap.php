<?php
/**
 * Uygulama başlatıcı: sabitler, otomatik yükleyici, ayarlar ve oturum.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('THEME_PATH', ROOT_PATH . '/themes');
define('STORAGE_PATH', ROOT_PATH . '/storage');

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    exit('Bu uygulama PHP 8.1 veya üzeri gerektirir. Mevcut sürüm: ' . PHP_VERSION);
}

// PSR-4 benzeri otomatik yükleyici: App\Core\Router -> app/Core/Router.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/helpers.php';

// config.php GitHub'a yüklenmez; yeni kurulumda yoksa örnek ayarlarla başla
App\Core\Config::load(is_file(ROOT_PATH . '/config/config.php')
    ? ROOT_PATH . '/config/config.php'
    : ROOT_PATH . '/config/config.example.php');

date_default_timezone_set((string) App\Core\Config::get('app.timezone', 'UTC'));

if (App\Core\Config::get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

App\Core\Session::start();
