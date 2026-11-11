<?php

namespace App\Support;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Setting;
use Throwable;

/**
 * Ziyaret istatistikleri: günlük ve tekil.
 * Aynı ziyaretçi gün içinde aynı sayfaya / projeye tekrar baksa da sayı artmaz.
 *
 * Ziyaretçi kimliği:
 *   - İstatistik çerezine izin verdiyse: rastgele anonim çerez ("ziyaretci"), IP değişse de aynı kişi sayılır
 *   - Seçim yapmadıysa: IP + tarayıcı bilgisinin günlük değişen tek yönlü özeti (IP adresi saklanmaz)
 *   - İstatistiği reddettiyse: hiç sayılmaz
 * Yönetici (panel girişi açık) ve arama motoru botları sayılmaz.
 */
final class Stats
{
    private const VISITOR_COOKIE = 'ziyaretci';

    /** Sayılan sayfa yolu adları (panelde gösterim için) */
    public const PAGE_LABELS = [
        ''            => 'Ana Sayfa',
        'projeler'    => 'Projeler',
        'hakkimda'    => 'Hakkımda',
        'hizmetler'   => 'Hizmetler',
        'teklif-al'   => 'Teklif Al',
        'iletisim'    => 'İletişim',
        'cerez-politikasi' => 'Çerez Politikası',
    ];

    private static ?string $visitor = null;
    private static bool $siteCounted = false;

    /** Bu istek sayılmalı mı? */
    public static function enabled(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || Consent::statistics() === false) {
            return false;
        }
        if (Auth::check()) {
            return false;
        }
        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python|headless|lighthouse|monitor|uptime|scan/', $ua)) {
            return false;
        }
        // Tarayıcının arka planda ön yüklemesi sayılmaz
        $purpose = strtolower((string) ($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? ''));
        return !str_contains($purpose, 'prefetch');
    }

    /** Sayfa görüntülemesi (+ günlük tekil ziyaretçi) */
    public static function page(string $path): void
    {
        $path = mb_substr(trim($path, '/'), 0, 190);
        self::count('page', $path);
    }

    /** Proje incelemesi (detay ve tanıtım sayfası aynı proje sayılır) */
    public static function project(int $projectId): void
    {
        self::count('project', (string) $projectId);
    }

    private static function count(string $kind, string $ref): void
    {
        if (!self::enabled()) {
            return;
        }
        try {
            $db = Database::getInstance();
            $day = date('Y-m-d');
            $visitor = self::visitor();
            $keys = [[$kind, $ref]];
            if (!self::$siteCounted) {
                $keys[] = ['site', ''];
                self::$siteCounted = true;
            }
            foreach ($keys as [$k, $r]) {
                $new = $db->query('INSERT IGNORE INTO stat_seen (day, kind, ref, visitor) VALUES (?, ?, ?, ?)', [$day, $k, $r, $visitor])->rowCount();
                if ($new) {
                    $db->query('INSERT INTO stat_daily (day, kind, ref, views) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE views = views + 1', [$day, $k, $r]);
                }
            }
            // Dünün tekillik kayıtlarına gerek yok (ara sıra temizlenir)
            if (random_int(1, 50) === 1) {
                $db->query('DELETE FROM stat_seen WHERE day < ?', [$day]);
            }
        } catch (Throwable) {
            // İstatistik yazılamasa bile sayfa açılır
        }
    }

    private static function visitor(): string
    {
        if (self::$visitor !== null) {
            return self::$visitor;
        }
        // Çerez izni varsa anonim kalıcı kimlik
        if (Consent::statistics() === true) {
            $id = (string) ($_COOKIE[self::VISITOR_COOKIE] ?? '');
            if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
                $id = bin2hex(random_bytes(16));
                setcookie(self::VISITOR_COOKIE, $id, [
                    'expires'  => time() + 365 * 86400,
                    'path'     => '/',
                    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => false, // ziyaretçi istatistiği reddederse tarayıcı tarafında silinebilsin
                    'samesite' => 'Lax',
                ]);
            }
            return self::$visitor = md5('c' . $id . date('Y-m-d'));
        }
        // Çerezsiz: günlük değişen gizli anahtarla IP + tarayıcı özeti (geri çevrilemez, IP saklanmaz)
        $salt = (string) Setting::get('stats_salt', '');
        if ($salt === '') {
            $salt = bin2hex(random_bytes(16));
            Setting::set('stats_salt', $salt);
        }
        $raw = date('Y-m-d') . '|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '');
        return self::$visitor = substr(hash_hmac('sha256', $raw, $salt), 0, 32);
    }

    // ------------------------------------------------------------------ Panel sorguları

    /** Gün gün tekil ziyaretçi ve proje incelemesi: ['2026-11-01' => ['site' => 12, 'project' => 5], ...] */
    public static function daily(int $days): array
    {
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $out[date('Y-m-d', strtotime($from . " +$i days"))] = ['site' => 0, 'project' => 0, 'page' => 0];
        }
        $rows = Database::getInstance()->fetchAll(
            "SELECT day, kind, SUM(views) v FROM stat_daily WHERE day >= ? AND kind IN ('site','project','page') GROUP BY day, kind",
            [$from]
        );
        foreach ($rows as $r) {
            if (isset($out[$r['day']])) {
                $out[$r['day']][$r['kind']] = (int) $r['v'];
            }
        }
        return $out;
    }

    /** En çok incelenen projeler (dönem içinde) */
    public static function topProjects(int $days, int $limit = 20): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT p.id, p.title, p.slug, p.kind, p.is_published, SUM(d.views) views,
                    SUM(CASE WHEN d.day = ? THEN d.views ELSE 0 END) today
             FROM stat_daily d JOIN projects p ON p.id = d.ref
             WHERE d.kind = 'project' AND d.day >= ?
             GROUP BY p.id, p.title, p.slug, p.kind, p.is_published ORDER BY views DESC, p.title LIMIT " . (int) $limit,
            [date('Y-m-d'), date('Y-m-d', strtotime('-' . ($days - 1) . ' days'))]
        );
    }

    /** En çok görüntülenen sayfalar (dönem içinde) */
    public static function topPages(int $days, int $limit = 20): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT ref, SUM(views) views, SUM(CASE WHEN day = ? THEN views ELSE 0 END) today
             FROM stat_daily WHERE kind = 'page' AND day >= ? GROUP BY ref ORDER BY views DESC LIMIT " . (int) $limit,
            [date('Y-m-d'), date('Y-m-d', strtotime('-' . ($days - 1) . ' days'))]
        );
    }

    /** Toplamlar: bugün, dün, dönem */
    public static function totals(int $days): array
    {
        $db = Database::getInstance();
        $sum = static fn (string $kind, string $from, ?string $to = null) => (int) $db->fetchColumn(
            'SELECT IFNULL(SUM(views), 0) FROM stat_daily WHERE kind = ? AND day >= ?' . ($to ? ' AND day <= ?' : ''),
            $to ? [$kind, $from, $to] : [$kind, $from]
        );
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        return [
            'today'      => $sum('site', $today),
            'yesterday'  => $sum('site', $yesterday, $yesterday),
            'visitors'   => $sum('site', $from),
            'projects'   => $sum('project', $from),
            'pages'      => $sum('page', $from),
        ];
    }

    /**
     * Değişiklik sürümü: bugünkü sayımlardan biri artınca değişir (canlı yenileme için tek hafif sorgu).
     * Gün dönünce de değişir; panel yeni günü kendiliğinden gösterir.
     */
    public static function version(): string
    {
        $day = date('Y-m-d');
        return $day . '-' . (string) Database::getInstance()->fetchColumn(
            "SELECT CONCAT(COUNT(*), '-', IFNULL(SUM(views), 0)) FROM stat_daily WHERE day = ?",
            [$day]
        );
    }

    /** Sayfa yolunu okunur ada çevirir */
    public static function pageLabel(string $path): string
    {
        if (isset(self::PAGE_LABELS[$path])) {
            return self::PAGE_LABELS[$path];
        }
        if (preg_match('#^projeler/([^/]+)(/tanitim)?$#', $path, $m)) {
            return 'Proje: ' . $m[1] . (isset($m[2]) ? ' (tanıtım)' : '');
        }
        if (preg_match('#^hizmetler/([^/]+)$#', $path, $m)) {
            return 'Hizmet: ' . (ServiceCatalog::find($m[1])['title'] ?? $m[1]);
        }
        return '/' . $path;
    }
}
