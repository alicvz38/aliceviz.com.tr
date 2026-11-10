<?php
/**
 * Gmail senkronizasyonu — arka planda her dakika çalışır.
 * ---------------------------------------------------------------
 * Windows (XAMPP): Görev Zamanlayıcı'da "WebSitem Gmail Senkronizasyonu" görevi
 *   C:\xampp\php\php-win.exe C:\xampp\htdocs\cron\mail-sync.php
 *
 * Linux hosting (cPanel > Cron Jobs), her dakika:
 *   * * * * * /usr/local/bin/php /home/KULLANICI/public_html/cron/mail-sync.php >/dev/null 2>&1
 *
 * Bu dosya tarayıcıdan çalıştırılamaz (.htaccess ile engellidir).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Yalnızca komut satırından çalıştırılabilir.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\Setting;
use App\Services\MailFetcher;

try {
    (new App\Core\Migrator())->run();

    if (!is_file(STORAGE_PATH . '/installed.lock') || !MailFetcher::isEnabled()) {
        exit(0);
    }

    $added = (new MailFetcher())->sync(50);
    if (in_array('-v', $argv ?? [], true)) {
        echo date('H:i:s') . " — {$added} yeni e-posta\n";
    }
} catch (Throwable $e) {
    try {
        Setting::set('imap_last_error', mb_substr($e->getMessage(), 0, 300));
    } catch (Throwable) {
    }
    error_log('Gmail senkronizasyonu: ' . $e->getMessage());
    if (in_array('-v', $argv ?? [], true)) {
        echo 'HATA: ' . $e->getMessage() . "\n";
    }
    exit(1);
}
