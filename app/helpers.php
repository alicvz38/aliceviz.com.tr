<?php
/**
 * Görünümlerde ve denetleyicilerde kullanılan kısa yardımcı fonksiyonlar.
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\Setting;

/** HTML çıktısını güvenli hale getirir */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Site içi adres: url('projeler') -> /Web Sitem/projeler */
function url(string $path = ''): string
{
    $base = Request::basePath();
    $path = ltrim($path, '/');
    $encodedBase = implode('/', array_map('rawurlencode', explode('/', $base)));
    return $encodedBase . '/' . $path;
}

/** Yönetim paneli adresi: admin_url('projeler') -> /admin/projeler */
function admin_url(string $path = ''): string
{
    return url(trim(Config::get('admin.prefix', 'admin') . '/' . ltrim($path, '/'), '/'));
}

/**
 * Yönlendirme. İstek AJAX ise yönlendirmek yerine JSON döner
 * (flash mesajları + hedef adres) — bkz. App\Core\Response::fromFlash()
 */
function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : (str_starts_with($path, '/') ? $path : url($path));
    if (Request::isAjax()) {
        \App\Core\Response::fromFlash($target);
    }
    header('Location: ' . $target, true, 302);
    exit;
}

function csrf_field(): string
{
    return Csrf::field();
}

function csrf_token(): string
{
    return Csrf::token();
}

/** Tüm temaların ortak kullandığı /assets klasöründeki dosyanın adresi */
function shared_asset(string $file): string
{
    $file = ltrim($file, '/');
    $full = ROOT_PATH . '/assets/' . $file;
    return url('assets/' . $file) . (is_file($full) ? '?v=' . filemtime($full) : '');
}

/**
 * Türkiye telefon numarasını doğrular ve tek biçime getirir (assets/js/mask.js ile aynı desen).
 *   "+90 532 123 45 67", "05321234567", "532 123 4567"  ->  "0 (532) 123 45 67"
 * @return string|null|false  boş girdi: null · geçersiz: false · geçerli: biçimli numara
 */
function phone_normalize(?string $value): string|null|false
{
    $digits = preg_replace('/\D/', '', (string) $value);
    if ($digits === '') {
        return null;
    }
    if (str_starts_with($digits, '90') && strlen($digits) > 10) {
        $digits = substr($digits, 2);
    }
    $digits = ltrim($digits, '0');
    // 10 hane; alan kodu 2, 3, 4 (sabit hat), 5 (cep) veya 8 (444/850 vb.) ile başlar
    if (strlen($digits) !== 10 || !preg_match('/^[234589]/', $digits)) {
        return false;
    }
    return sprintf('0 (%s) %s %s %s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 2), substr($digits, 8, 2));
}

/** tel: bağlantısı için uluslararası biçim: "0 (532) 123 45 67" -> "+905321234567" */
function phone_href(?string $value): string
{
    $digits = ltrim(preg_replace('/\D/', '', (string) $value), '0');
    if (str_starts_with($digits, '90') && strlen($digits) > 10) {
        $digits = substr($digits, 2);
    }
    return $digits !== '' ? 'tel:+90' . $digits : '';
}

/**
 * WhatsApp sohbet bağlantısı: "0 (532) 123 45 67" -> "https://wa.me/905321234567?text=..."
 * Telefonda WhatsApp uygulamasını, bilgisayarda WhatsApp Web'i açar.
 */
function whatsapp_href(?string $value, string $text = ''): string
{
    $digits = ltrim(preg_replace('/\D/', '', (string) $value), '0');
    if (str_starts_with($digits, '90') && strlen($digits) > 10) {
        $digits = substr($digits, 2);
    }
    if ($digits === '') {
        return '';
    }
    return 'https://wa.me/90' . $digits . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/** "2023-04" -> "Nisan 2023" */
function month_label(?string $ym): string
{
    if (!$ym || !preg_match('/^(\d{4})(?:-(\d{1,2}))?/', $ym, $m)) {
        return (string) $ym;
    }
    $months = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $month = isset($m[2]) ? (int) $m[2] : 0;
    return ($month >= 1 && $month <= 12 ? $months[$month] . ' ' : '') . $m[1];
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::old($key, $default);
}

function setting(string $key, mixed $default = ''): mixed
{
    static $dbFailed = false;
    if ($dbFailed) {
        return $default;
    }
    try {
        return Setting::get($key, $default);
    } catch (\Throwable) {
        $dbFailed = true; // Veritabanı yoksa (ör. kurulum öncesi) varsayılanlarla devam et
        return $default;
    }
}

/** Feather ikonunu SVG olarak döndürür */
function icon(string $name, int $size = 24, string $class = ''): string
{
    return \App\Support\Icon::svg($name, $size, $class);
}

/** Türkçe karakter destekli kısa ad (slug) üretir */
function str_slug(string $text): string
{
    $map = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'I' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
    $text = strtr($text, $map);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-') ?: 'proje';
}

function str_limit(?string $text, int $limit = 120): string
{
    $text = trim((string) $text);
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit)) . '…' : $text;
}

/** "3 gün önce" biçiminde tarih */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }
    $diff = time() - strtotime($datetime);
    return match (true) {
        $diff < 60        => 'az önce',
        $diff < 3600      => floor($diff / 60) . ' dakika önce',
        $diff < 86400     => floor($diff / 3600) . ' saat önce',
        $diff < 2592000   => floor($diff / 86400) . ' gün önce',
        $diff < 31536000  => floor($diff / 2592000) . ' ay önce',
        default           => floor($diff / 31536000) . ' yıl önce',
    };
}

function format_date(?string $datetime, string $format = 'd.m.Y'): string
{
    return $datetime ? date($format, strtotime($datetime)) : '-';
}

/** Mevcut yol verilen öneki içeriyor mu? (menü aktifliği için) */
function is_active(string $prefix, bool $exact = false): bool
{
    $path = (new Request())->path();
    $prefix = trim($prefix, '/');
    return $exact ? $path === $prefix : ($path === $prefix || str_starts_with($path, $prefix . '/'));
}
