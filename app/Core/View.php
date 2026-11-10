<?php

namespace App\Core;

use RuntimeException;

/**
 * TEMA PARÇALAMA SİSTEMİ
 * ---------------------------------------------------------------
 * Her tema şu yapıdadır:
 *   themes/{tema}/theme.php      -> Tema bilgileri (ad, sürüm, vendor yolu...)
 *   themes/{tema}/layouts/       -> Ana iskeletler (main.php, blank.php ...)
 *   themes/{tema}/partials/      -> Parçalar (head, header, footer, sidebar ...)
 *   themes/{tema}/pages/         -> Sayfa içerikleri
 *   themes/{tema}/assets/        -> CSS / JS / görseller
 *
 * Sayfa dosyası içinde kullanılabilecek yöntemler:
 *   $this->layout('main');                 // Hangi iskelet kullanılacak
 *   $this->partial('project-card', [...]); // Parça ekle
 *   $this->start('scripts'); ... $this->stop(); // Bölüm tanımla
 *   $this->section('scripts');             // Bölümü iskelette yazdır
 *   $this->asset('css/style.css');         // Tema varlık adresi
 *   $this->vendor('src/bootstrap/...');    // Harici şablon (Equation) adresi
 */
final class View
{
    private string $theme;
    private string $path;
    private array $info;
    private array $shared = [];
    private array $sections = [];
    private array $sectionStack = [];
    private ?string $layout = null;

    public function __construct(string $theme)
    {
        $theme = preg_replace('/[^a-z0-9_\-]/i', '', $theme);
        $path = THEME_PATH . '/' . $theme;
        if (!is_dir($path)) {
            throw new RuntimeException("Tema bulunamadı: {$theme}");
        }
        $this->theme = $theme;
        $this->path = $path;
        $info = is_file($path . '/theme.php') ? require $path . '/theme.php' : [];
        $this->info = is_array($info) ? $info : [];
    }

    public function name(): string
    {
        return $this->theme;
    }

    public function info(string $key, mixed $default = null): mixed
    {
        return $this->info[$key] ?? $default;
    }

    /** Tüm görünümlerde kullanılacak ortak değişken */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function render(string $page, array $data = []): string
    {
        $this->layout = null;
        $content = $this->renderFile('pages/' . $page, $data);

        if ($this->layout === null) {
            return $content;
        }

        $this->sections['content'] ??= $content;
        $layout = $this->layout;
        $this->layout = null;
        return $this->renderFile('layouts/' . $layout, $data);
    }

    public function layout(string $name): void
    {
        $this->layout = $name;
    }

    public function partial(string $name, array $data = []): string
    {
        return $this->renderFile('partials/' . $name, $data);
    }

    public function start(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function stop(): void
    {
        $name = array_pop($this->sectionStack);
        if ($name === null) {
            throw new RuntimeException('stop() çağrısı start() olmadan yapıldı.');
        }
        $this->sections[$name] = ($this->sections[$name] ?? '') . ob_get_clean();
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return !empty($this->sections[$name]);
    }

    /** themes/{tema}/assets/... adresini verir */
    public function asset(string $file): string
    {
        $full = $this->path . '/assets/' . ltrim($file, '/');
        $version = is_file($full) ? '?v=' . filemtime($full) : '';
        return url('themes/' . $this->theme . '/assets/' . ltrim($file, '/')) . $version;
    }

    /** theme.php içindeki "vendor" yoluna göre harici şablon dosyası adresi */
    public function vendor(string $file): string
    {
        return url(trim((string) $this->info('vendor', ''), '/') . '/' . ltrim($file, '/'));
    }

    private function renderFile(string $file, array $data): string
    {
        $file = str_replace(['..', '\\'], ['', '/'], $file);
        $full = $this->path . '/' . $file . '.php';
        if (!is_file($full)) {
            throw new RuntimeException("Görünüm dosyası bulunamadı: {$this->theme}/{$file}.php");
        }

        extract(array_merge($this->shared, $data), EXTR_SKIP);
        ob_start();
        try {
            include $full;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /** Kurulu temaları listeler (yönetim paneli için) */
    public static function availableThemes(array $exclude = ['admin']): array
    {
        $themes = [];
        foreach (glob(THEME_PATH . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $slug = basename($dir);
            if (in_array($slug, $exclude, true)) {
                continue;
            }
            $info = is_file($dir . '/theme.php') ? require $dir . '/theme.php' : [];
            $themes[$slug] = is_array($info) ? $info : [];
        }
        return $themes;
    }
}
