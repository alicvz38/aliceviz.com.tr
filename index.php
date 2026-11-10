<?php
/**
 * Ön denetleyici (Front Controller)
 * Tüm istekler .htaccess ile bu dosyaya yönlendirilir.
 */

require __DIR__ . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Request;
use App\Core\Router;

// Kurulum yapılmadıysa kurulum sihirbazına yönlendir
if (!is_file(STORAGE_PATH . '/installed.lock')) {
    redirect('install.php');
}

// Yeni veritabanı güncellemeleri varsa uygula (database/migrations)
try {
    (new App\Core\Migrator())->run();
} catch (Throwable $e) {
    if (Config::get('app.debug')) {
        throw $e;
    }
    error_log((string) $e);
}

$router = new Router();
require ROOT_PATH . '/routes/web.php';

try {
    $router->dispatch(new Request());
} catch (Throwable $e) {
    http_response_code(500);
    if (Config::get('app.debug')) {
        echo '<pre style="background:#0b0f19;color:#f8fafc;padding:20px;white-space:pre-wrap">';
        echo '<b style="color:#f43f5e">' . e(get_class($e)) . ':</b> ' . e($e->getMessage()) . "\n\n";
        echo e($e->getFile()) . ':' . $e->getLine() . "\n\n" . e($e->getTraceAsString());
        echo '</pre>';
    } else {
        error_log((string) $e);
        echo 'Beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
    }
}
