<?php

namespace App\Core;

/**
 * CSRF koruması. Tüm POST formlarına csrf_field() eklenmelidir.
 */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }

    public static function verify(?string $token): bool
    {
        $stored = Session::get('_csrf');
        return is_string($token) && is_string($stored) && hash_equals($stored, $token);
    }
}
