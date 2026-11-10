<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'username', 'email', 'password', 'last_login_at'];

    public static function findByLogin(string $login): ?self
    {
        $row = static::db()->fetch('SELECT * FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1', [$login, $login]);
        return $row ? new self($row) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $letters = array_map(static fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
        return implode('', $letters) ?: 'A';
    }
}
