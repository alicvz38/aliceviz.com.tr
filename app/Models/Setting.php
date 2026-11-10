<?php

namespace App\Models;

use App\Core\Model;

/**
 * Anahtar / değer biçiminde site ayarları.
 */
class Setting extends Model
{
    protected static string $table = 'settings';
    protected static string $primaryKey = 'key';

    private static ?array $cache = null;

    public static function allSettings(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (static::db()->fetchAll('SELECT `key`, `value` FROM `settings`') as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        }
        return self::$cache;
    }

    /** Önbelleği boşaltır (uzun süre çalışan süreçler güncel ayarları okusun diye) */
    public static function refresh(): void
    {
        self::$cache = null;
    }

    public static function get(string $key, mixed $default = ''): mixed
    {
        $all = self::allSettings();
        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::db()->query(
            'INSERT INTO `settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set((string) $key, $value === null ? null : (string) $value);
        }
    }

    public static function isMaintenance(): bool
    {
        return self::get('maintenance_mode', '0') === '1';
    }
}
