<?php

namespace App\Core;

/**
 * Gelen HTTP isteğini temsil eder.
 */
final class Request
{
    private static ?string $basePath = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * İstek AJAX ile mi geldi? (ajax.js "X-Requested-With" ve "Accept: application/json" gönderir)
     */
    public static function isAjax(): bool
    {
        $with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        return $with === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Sitenin kurulu olduğu alt klasör. Örn: localhost/Web Sitem/ -> "/Web Sitem"
     */
    public static function basePath(): string
    {
        if (self::$basePath === null) {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
            self::$basePath = $dir === '.' ? '' : $dir;
        }
        return self::$basePath;
    }

    /** Alt klasör çıkarılmış yol. Örn: "projeler/ornek-proje" */
    public function path(): string
    {
        $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = self::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = preg_replace('#^/index\.php#', '', $uri);
        return trim($uri, '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function all(): array
    {
        return array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $_POST);
    }

    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        return ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $file : null;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
