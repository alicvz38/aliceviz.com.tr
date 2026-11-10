<?php

namespace App\Core;

use App\Models\User;

/**
 * Yönetici kimlik doğrulama. Basit kaba kuvvet koruması içerir.
 */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    private static ?User $user = null;

    public static function attempt(string $login, string $password): bool
    {
        if (self::isLocked()) {
            return false;
        }

        $user = User::findByLogin($login);
        if (!$user || !password_verify($password, (string) $user->password)) {
            self::registerFailure();
            return false;
        }

        if (password_needs_rehash((string) $user->password, PASSWORD_DEFAULT)) {
            User::updateById((int) $user->id, ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        self::login($user);
        Session::forget('_login_attempts');
        Session::forget('_login_locked_until');
        User::updateById((int) $user->id, ['last_login_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /** Oturumu başlatır (şifreyle giriş veya "Beni hatırla" çerezi) */
    private static function login(User $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user->id);
        self::$user = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): ?User
    {
        if (self::$user === null && ($id = self::id())) {
            self::$user = User::find($id);
        }
        // Oturum yoksa "Beni hatırla" çerezini dene (tarayıcı kapanıp açıldığında)
        if (self::$user === null && !self::$rememberTried && PHP_SAPI !== 'cli') {
            self::$rememberTried = true;
            try {
                $id = isset($_COOKIE[RememberMe::COOKIE]) ? RememberMe::consume() : null;
                $user = $id ? User::find($id) : null;
                if ($user) {
                    self::login($user);
                    Session::set('_remembered', true);
                }
            } catch (\Throwable) {
                // Veritabanı hazır değilse (ör. kurulum) sessizce geç
            }
        }
        return self::$user;
    }

    private static bool $rememberTried = false;

    /** Bu oturum "Beni hatırla" çereziyle mi açıldı? */
    public static function viaRemember(): bool
    {
        return (bool) Session::get('_remembered', false);
    }

    public static function logout(): void
    {
        RememberMe::forget();
        self::$user = null;
        Session::destroy();
    }

    public static function isLocked(): bool
    {
        return (int) Session::get('_login_locked_until', 0) > time();
    }

    public static function lockRemaining(): int
    {
        return max(0, (int) Session::get('_login_locked_until', 0) - time());
    }

    private static function registerFailure(): void
    {
        $attempts = (int) Session::get('_login_attempts', 0) + 1;
        Session::set('_login_attempts', $attempts);
        if ($attempts >= self::MAX_ATTEMPTS) {
            Session::set('_login_locked_until', time() + self::LOCK_SECONDS);
            Session::set('_login_attempts', 0);
        }
    }
}
