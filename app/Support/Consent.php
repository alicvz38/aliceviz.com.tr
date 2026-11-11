<?php

namespace App\Support;

/**
 * Çerez tercihleri (ziyaretçinin kendi seçimi).
 *   Zorunlu    : oturum, güvenlik (CSRF), canlı destek — her zaman açık
 *   İstatistik : ziyaret sayımı (anonim ziyaretçi çerezi)
 *   Reklam     : Google AdSense kişiselleştirilmiş reklamlar
 *
 * Tercih "cerez_tercihi" çerezinde tutulur: "s=1&a=0" (s: istatistik, a: reklam), 6 ay geçerli.
 * Tercih yapılmamışsa: istatistik çerezsiz (günlük değişen anonim özetle) sayılır, reklamlar kişiselleştirilmez.
 */
final class Consent
{
    public const COOKIE = 'cerez_tercihi';

    private static ?array $choice = null;

    /** Ziyaretçi bir seçim yaptı mı? */
    public static function decided(): bool
    {
        return self::read() !== [];
    }

    public static function statistics(): ?bool
    {
        $c = self::read();
        return isset($c['s']) ? $c['s'] === '1' : null;
    }

    public static function ads(): bool
    {
        return (self::read()['a'] ?? '0') === '1';
    }

    private static function read(): array
    {
        if (self::$choice === null) {
            parse_str((string) ($_COOKIE[self::COOKIE] ?? ''), $c);
            self::$choice = array_intersect_key(array_map('strval', array_filter($c, 'is_string')), ['s' => 1, 'a' => 1]);
        }
        return self::$choice;
    }
}
