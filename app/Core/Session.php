<?php

namespace App\Core;

/**
 * Oturum yönetimi ve tek seferlik (flash) mesajlar.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('ACSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** type: success | danger | warning | info */
    public static function flash(string $type, string $message): void
    {
        if ($type === 'success') {
            \App\Support\ActivityLog::capture($message); // panel etkinlik günlüğü (yalnızca panel POST isteklerinde yazar)
        }
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /** Form hatası sonrası eski girdileri saklar */
    public static function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirmation'], $input['current_password'], $input['_token']);
        $_SESSION['_old'] = $input;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }

    public static function clearOld(): void
    {
        unset($_SESSION['_old']);
    }
}
