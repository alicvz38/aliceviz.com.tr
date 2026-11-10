<?php

namespace App\Support;

use App\Core\Config;

/**
 * Canlı bildirim (WebSocket) yardımcıları.
 *  - Panel, sunucuya imzalı bir anahtarla (token) bağlanır; yalnızca giriş yapmış yönetici kabul edilir.
 *  - Sunucu çalışıyor mu? storage/realtime.json "kalp atışı" dosyasına bakılır.
 */
final class Realtime
{
    private const TTL = 43200; // anahtar 12 saat geçerli (sayfa yenilendikçe yenilenir)

    public static function host(): string
    {
        return (string) Config::get('realtime.host', '127.0.0.1');
    }

    public static function port(): int
    {
        return (int) Config::get('realtime.port', 8090);
    }

    /** Gizli imza anahtarı (ilk kullanımda storage/realtime.key olarak oluşturulur) */
    private static function key(): string
    {
        $file = STORAGE_PATH . '/realtime.key';
        if (!is_file($file)) {
            file_put_contents($file, bin2hex(random_bytes(32)));
        }
        return trim((string) file_get_contents($file));
    }

    public static function token(int $userId): string
    {
        $exp = time() + self::TTL;
        return $userId . '.' . $exp . '.' . hash_hmac('sha256', $userId . '.' . $exp, self::key());
    }

    /** @return int|null Geçerliyse kullanıcı kimliği */
    public static function verify(string $token): ?int
    {
        if (!preg_match('/^(\d+)\.(\d+)\.([a-f0-9]{64})$/', $token, $m)) {
            return null;
        }
        if ((int) $m[2] < time() || !hash_equals(hash_hmac('sha256', $m[1] . '.' . $m[2], self::key()), $m[3])) {
            return null;
        }
        return (int) $m[1];
    }

    /** Canlı destek ziyaretçisi için anahtar: yalnızca kendi sohbetinin mesajlarını alabilir */
    public static function visitorToken(int $conversationId): string
    {
        $exp = time() + self::TTL;
        return 'v.' . $conversationId . '.' . $exp . '.' . hash_hmac('sha256', 'v.' . $conversationId . '.' . $exp, self::key());
    }

    /** @return int|null Geçerliyse sohbet kimliği */
    public static function verifyVisitor(string $token): ?int
    {
        if (!preg_match('/^v\.(\d+)\.(\d+)\.([a-f0-9]{64})$/', $token, $m)) {
            return null;
        }
        if ((int) $m[2] < time() || !hash_equals(hash_hmac('sha256', 'v.' . $m[1] . '.' . $m[2], self::key()), $m[3])) {
            return null;
        }
        return (int) $m[1];
    }

    /**
     * Bu kurulumda WebSocket sunucusu var mı? (realtime/ klasörü yalnızca yerel/VPS kurulumunda bulunur;
     * paylaşımlı hostinge yüklenmez, orada sistem kendiliğinden yoklama ile çalışır.)
     */
    public static function installed(): bool
    {
        return is_file(ROOT_PATH . '/realtime/server.php');
    }

    public static function statusFile(): string
    {
        return STORAGE_PATH . '/realtime.json';
    }

    /** @return array|null Sunucu çalışıyorsa durum bilgisi */
    public static function status(): ?array
    {
        $file = self::statusFile();
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        if (!is_array($data) || time() - (int) ($data['heartbeat'] ?? 0) > 30) {
            return null;
        }
        return $data;
    }

    public static function isRunning(): bool
    {
        return self::status() !== null;
    }
}
