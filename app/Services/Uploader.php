<?php

namespace App\Services;

use App\Core\Config;
use RuntimeException;

/**
 * Güvenli görsel yükleme: MIME türü dosya içeriğinden doğrulanır, rastgele ad verilir.
 */
class Uploader
{
    public function image(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Dosya yüklenemedi (hata kodu: ' . ($file['error'] ?? '?') . ').');
        }
        if ($file['size'] > (int) Config::get('upload.max_size')) {
            throw new RuntimeException('Görsel en fazla ' . round(Config::get('upload.max_size') / 1048576, 1) . ' MB olabilir.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = Config::get('upload.allowed', []);
        if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Yalnızca JPG, PNG, WEBP veya GIF görseller yüklenebilir.');
        }

        $dir = trim((string) Config::get('upload.dir'), '/');
        $absDir = ROOT_PATH . '/' . $dir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }

        $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $absDir . '/' . $name)) {
            throw new RuntimeException('Dosya kaydedilemedi. Klasör izinlerini kontrol edin.');
        }
        return $dir . '/' . $name;
    }

    /** Yalnızca upload klasöründeki dosyaları siler */
    public function delete(?string $relative): void
    {
        if (!$relative || preg_match('#^https?://#', $relative)) {
            return;
        }
        $dir = realpath(ROOT_PATH . '/' . trim((string) Config::get('upload.dir'), '/'));
        $file = realpath(ROOT_PATH . '/' . ltrim($relative, '/'));
        if ($dir && $file && str_starts_with($file, $dir) && is_file($file)) {
            @unlink($file);
        }
    }

    /** Sekme ikonu (favicon): PNG, ICO, WEBP veya JPG, en fazla 1 MB → uploads/site/ */
    public function favicon(array $file): string
    {
        return $this->siteImage($file, 'favicon', [
            'image/png' => 'png', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico',
            'image/webp' => 'webp', 'image/jpeg' => 'jpg',
        ], 'Favicon için PNG, ICO, WEBP veya JPG görsel yükleyin.');
    }

    /** Site logosu: PNG, WEBP, JPG veya GIF, en fazla 1 MB → uploads/site/ */
    public function logo(array $file): string
    {
        return $this->siteImage($file, 'logo', [
            'image/png' => 'png', 'image/webp' => 'webp', 'image/jpeg' => 'jpg', 'image/gif' => 'gif',
        ], 'Logo için PNG, WEBP, JPG veya GIF görsel yükleyin.');
    }

    /** Paylaşım görseli (Open Graph): PNG, WEBP veya JPG, en fazla 1 MB → uploads/site/ */
    public function shareImage(array $file): string
    {
        return $this->siteImage($file, 'paylasim', [
            'image/png' => 'png', 'image/webp' => 'webp', 'image/jpeg' => 'jpg',
        ], 'Paylaşım görseli için PNG, WEBP veya JPG görsel yükleyin.');
    }

    /** uploads/site/ altına içeriği doğrulanmış görsel kaydeder (SVG kabul edilmez: betik içerebilir) */
    private function siteImage(array $file, string $prefix, array $allowed, string $typeError): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Dosya yüklenemedi (hata kodu: ' . ($file['error'] ?? '?') . ').');
        }
        if ($file['size'] > 1048576) {
            throw new RuntimeException('Dosya en fazla 1 MB olabilir.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException($typeError);
        }
        $absDir = ROOT_PATH . '/uploads/site';
        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }
        $name = $prefix . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $absDir . '/' . $name)) {
            throw new RuntimeException('Dosya kaydedilemedi. Klasör izinlerini kontrol edin.');
        }
        return 'uploads/site/' . $name;
    }

    /** Yalnızca uploads/site klasöründeki dosyaları siler */
    public function deleteSiteFile(?string $relative): void
    {
        if (!$relative) {
            return;
        }
        $dir = realpath(ROOT_PATH . '/uploads/site');
        $file = realpath(ROOT_PATH . '/' . ltrim($relative, '/'));
        if ($dir && $file && str_starts_with($file, $dir . DIRECTORY_SEPARATOR) && is_file($file)) {
            @unlink($file);
        }
    }
}
