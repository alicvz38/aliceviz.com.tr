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
}
