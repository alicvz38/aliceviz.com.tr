<?php

namespace App\Core;

/**
 * "BENİ HATIRLA" — güvenli kalıcı giriş çerezi
 * ---------------------------------------------------------------
 * Çerez:  ac_remember = <seçici 24 hex>.<doğrulayıcı 64 hex>   (30 gün, HttpOnly, SameSite=Lax, HTTPS'te Secure)
 * Veritabanı (remember_tokens): seçici + doğrulayıcının SHA-256 özeti. Şifre veya oturum kimliği çerezde tutulmaz.
 *
 * - Her otomatik girişte doğrulayıcı yenilenir (eski çerez geçersizleşir).
 * - Seçici doğru ama doğrulayıcı yanlışsa çerez çalınmış olabilir → kullanıcının tüm hatırlanan cihazları kapatılır.
 * - Aynı anda açılan sekmeler için bir önceki doğrulayıcı 2 dakika daha kabul edilir.
 * - Çıkışta o cihaz, şifre değişince diğer tüm cihazlar unutulur.
 */
final class RememberMe
{
    public const COOKIE = 'ac_remember';
    private const DAYS = 30;
    private const GRACE_SECONDS = 120;

    /** Giriş sırasında "Beni hatırla" seçildiyse çağrılır */
    public static function issue(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $db = Database::getInstance();
        $db->insert('remember_tokens', [
            'user_id'      => $userId,
            'selector'     => $selector,
            'token_hash'   => hash('sha256', $validator),
            'user_agent'   => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'ip'           => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'expires_at'   => date('Y-m-d H:i:s', time() + self::DAYS * 86400),
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);
        self::setCookie($selector . '.' . $validator, time() + self::DAYS * 86400);
        self::cleanup();
    }

    /**
     * Oturum yokken çerezden kullanıcıyı tanır. Geçerliyse doğrulayıcıyı yeniler.
     * @return int|null Kullanıcı kimliği
     */
    public static function consume(): ?int
    {
        $cookie = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (!preg_match('/^([a-f0-9]{24})\.([a-f0-9]{64})$/', $cookie, $m)) {
            if ($cookie !== '') {
                self::clearCookie();
            }
            return null;
        }
        [, $selector, $validator] = $m;
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM remember_tokens WHERE selector = ? LIMIT 1', [$selector]);

        if (!$row || strtotime($row['expires_at']) < time()) {
            if ($row) {
                $db->delete('remember_tokens', 'id = ?', [$row['id']]);
            }
            self::clearCookie();
            return null;
        }

        $hash = hash('sha256', $validator);
        if (!hash_equals($row['token_hash'], $hash)) {
            // Az önce yenilenmiş bir önceki doğrulayıcı mı? (aynı anda açılan sekmeler) → kabul et, yenileme
            $inGrace = $row['prev_hash'] && hash_equals($row['prev_hash'], $hash)
                && $row['rotated_at'] && time() - strtotime($row['rotated_at']) <= self::GRACE_SECONDS;
            if (!$inGrace) {
                // Çalınmış çerez belirtisi: bu kullanıcının tüm hatırlanan cihazlarını kapat
                $db->delete('remember_tokens', 'user_id = ?', [$row['user_id']]);
                self::clearCookie();
                error_log('Beni hatırla: geçersiz doğrulayıcı, kullanıcı #' . $row['user_id'] . ' için tüm cihazlar kapatıldı. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
                return null;
            }
            return (int) $row['user_id'];
        }

        // Doğrulayıcıyı yenile (süre de uzar)
        $newValidator = bin2hex(random_bytes(32));
        $db->update('remember_tokens', [
            'token_hash'   => hash('sha256', $newValidator),
            'prev_hash'    => $row['token_hash'],
            'rotated_at'   => date('Y-m-d H:i:s'),
            'last_used_at' => date('Y-m-d H:i:s'),
            'ip'           => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent'   => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'expires_at'   => date('Y-m-d H:i:s', time() + self::DAYS * 86400),
        ], 'id = :id', ['id' => $row['id']]);
        self::setCookie($selector . '.' . $newValidator, time() + self::DAYS * 86400);

        return (int) $row['user_id'];
    }

    /** Bu cihazı unut (çıkış) */
    public static function forget(): void
    {
        $cookie = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (preg_match('/^([a-f0-9]{24})\./', $cookie, $m)) {
            try {
                Database::getInstance()->delete('remember_tokens', 'selector = ?', [$m[1]]);
            } catch (\Throwable) {
            }
        }
        self::clearCookie();
    }

    /** Kullanıcının hatırlanan tüm cihazlarını kapat ($keepCurrent: bu cihaz hariç) */
    public static function forgetAll(int $userId, bool $keepCurrent = false): int
    {
        $current = self::currentSelector();
        if ($keepCurrent && $current) {
            return Database::getInstance()->delete('remember_tokens', 'user_id = ? AND selector <> ?', [$userId, $current]);
        }
        return Database::getInstance()->delete('remember_tokens', 'user_id = ?', [$userId]);
    }

    public static function forgetDevice(int $userId, int $id): bool
    {
        $row = Database::getInstance()->fetch('SELECT selector FROM remember_tokens WHERE id = ? AND user_id = ?', [$id, $userId]);
        if (!$row) {
            return false;
        }
        Database::getInstance()->delete('remember_tokens', 'id = ?', [$id]);
        if ($row['selector'] === self::currentSelector()) {
            self::clearCookie();
        }
        return true;
    }

    /** @return array<int, array> Hesabım sayfasındaki cihaz listesi */
    public static function devices(int $userId): array
    {
        $current = self::currentSelector();
        $rows = Database::getInstance()->fetchAll(
            'SELECT id, selector, user_agent, ip, last_used_at, created_at, expires_at FROM remember_tokens WHERE user_id = ? AND expires_at > NOW() ORDER BY last_used_at DESC',
            [$userId]
        );
        foreach ($rows as &$r) {
            $r['current'] = $r['selector'] === $current;
            $r['device'] = self::describe((string) $r['user_agent']);
            unset($r['selector']);
        }
        return $rows;
    }

    public static function currentSelector(): ?string
    {
        return preg_match('/^([a-f0-9]{24})\./', (string) ($_COOKIE[self::COOKIE] ?? ''), $m) ? $m[1] : null;
    }

    /** "Chrome · Windows" */
    public static function describe(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'OPR/')     => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Safari/')  => 'Safari',
            default                       => 'Tarayıcı',
        };
        $os = match (true) {
            str_contains($ua, 'Windows')                              => 'Windows',
            str_contains($ua, 'Android')                              => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')  => 'iOS',
            str_contains($ua, 'Mac OS')                               => 'macOS',
            str_contains($ua, 'Linux')                                => 'Linux',
            default                                                   => 'Bilinmeyen cihaz',
        };
        return $browser . ' · ' . $os;
    }

    // ------------------------------------------------------------

    private static function setCookie(string $value, int $expires): void
    {
        $_COOKIE[self::COOKIE] = $value;
        if (headers_sent()) {
            return;
        }
        setcookie(self::COOKIE, $value, [
            'expires'  => $expires,
            'path'     => '/', // oturum çereziyle aynı
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearCookie(): void
    {
        unset($_COOKIE[self::COOKIE]);
        if (!headers_sent()) {
            setcookie(self::COOKIE, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    /** Süresi dolmuş kayıtları temizle */
    private static function cleanup(): void
    {
        try {
            Database::getInstance()->query('DELETE FROM remember_tokens WHERE expires_at < NOW()');
        } catch (\Throwable) {
        }
    }
}
