<?php

namespace App\Support;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use Throwable;

/**
 * Panel etkinlik günlüğü (Takvim) — tamamen otomatik.
 *
 * Paneldeki her POST işlemi başarı mesajı ürettiğinde ("Proje eklendi", "Ayarlar kaydedildi"...)
 * o mesaj, işlemin yapıldığı adres ve ilgili kaydın adı ile birlikte activity_log tablosuna yazılır.
 * Bağlantı noktaları: AdminController::boot() -> prepare(), Session::flash('success') ve
 * Response::json(ok) -> capture(). Yeni eklenen panel özellikleri de kendiliğinden günlüğe düşer.
 */
final class ActivityLog
{
    /** Kategori => [başlık, renk (takvim), ikon] */
    public const CATEGORIES = [
        'proje'   => ['Projeler', 'primary', 'folder'],
        'icerik'  => ['İçerik', 'success', 'file-text'],
        'musteri' => ['Müşteriler', 'warning', 'users'],
        'eposta'  => ['E-posta', 'info', 'mail'],
        'ayar'    => ['Ayarlar', 'secondary', 'settings'],
        'oturum'  => ['Oturum', 'dark', 'user'],
    ];

    /** Günlüğe yazılmayan sık / önemsiz işlemler */
    private const SKIP = [
        '#^canli-destek/\d+/(gonder|yaziyor)$#',
        '#^mesajlar/\d+/(yildiz|okundu)$#',
        '#^mesajlar/ayarlar/test$#',
        '#^cikis$#',
    ];

    private static ?array $context = null;
    private static array $written = [];

    /** İstek başında: hangi işlem, hangi kayıt (silinmeden önce adı alınır) */
    public static function prepare(Request $request): void
    {
        if (self::$context !== null || !$request->isPost()) {
            return;
        }
        $prefix = trim((string) Config::get('admin.prefix', 'admin'), '/');
        $path = $request->path();
        if ($path !== $prefix && !str_starts_with($path, $prefix . '/')) {
            return;
        }
        $path = trim(substr($path, strlen($prefix)), '/');
        foreach (self::SKIP as $pattern) {
            if (preg_match($pattern, $path)) {
                return;
            }
        }

        try {
            self::$context = [
                'path'     => $path,
                'category' => self::category($path),
                'subject'  => self::subject($path),
                'detail'   => self::detail($path, $request),
                'url'      => self::link($path),
                'ip'       => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            ];
        } catch (Throwable) {
            self::$context = null;
        }
    }

    /** Başarı mesajı üretildiğinde günlüğe yaz (aynı istekte aynı mesaj bir kez) */
    public static function capture(string $message): void
    {
        $message = trim($message);
        if (self::$context === null || $message === '' || isset(self::$written[$message])) {
            return;
        }
        self::$written[$message] = true;

        $c = self::$context;
        $title = rtrim($message, '.!');
        if ($c['subject'] && mb_stripos($title, $c['subject']) === false) {
            $title .= ': ' . $c['subject'];
        }
        try {
            Database::getInstance()->insert('activity_log', [
                'user_id'  => Auth::id(),
                'category' => $c['category'],
                'title'    => mb_substr($title, 0, 255),
                'detail'   => $c['detail'] !== null ? mb_substr($c['detail'], 0, 500) : null,
                'url'      => $c['url'],
                'path'     => mb_substr($c['path'], 0, 255),
                'ip'       => $c['ip'],
            ]);
        } catch (Throwable) {
            // Günlük yazılamasa bile asıl işlem etkilenmez
        }
    }

    private static function category(string $path): string
    {
        $first = explode('/', $path)[0];
        return match ($first) {
            'projeler', 'github'                 => 'proje',
            'ozgecmis', 'teklif-formu'           => 'icerik',
            'teklifler', 'canli-destek'          => 'musteri',
            'mesajlar'                           => 'eposta',
            'giris'                              => 'oturum',
            default                              => 'ayar',
        };
    }

    /** {id} içeren adreslerde kaydın okunabilir adı */
    private static function subject(string $path): ?string
    {
        $db = Database::getInstance();
        $map = [
            '#^projeler/(\d+)/#'     => ['SELECT title AS n FROM projects WHERE id = ?', null],
            '#^teklifler/(\d+)/#'    => ['SELECT name AS n FROM quote_requests WHERE id = ?', null],
            '#^ozgecmis/(\d+)/#'     => ['SELECT title AS n FROM resume_items WHERE id = ?', null],
            '#^canli-destek/(\d+)/#' => ['SELECT IFNULL(NULLIF(name, \'\'), CONCAT(\'Ziyaretçi #\', id)) AS n FROM chat_conversations WHERE id = ?', null],
            '#^mesajlar/(\d+)/#'     => ['SELECT IFNULL(NULLIF(subject, \'\'), \'(konusuz)\') AS n FROM messages WHERE id = ?', null],
        ];
        foreach ($map as $pattern => [$sql]) {
            if (preg_match($pattern, $path, $m)) {
                $row = $db->fetch($sql, [(int) $m[1]]);
                return $row['n'] ?? null;
            }
        }
        return null;
    }

    /** Formdan ek bilgi (kime giden e-posta, içe aktarılan depolar...) */
    private static function detail(string $path, Request $r): ?string
    {
        return match (true) {
            $path === 'projeler/kaydet'  => ($t = trim((string) $r->post('title', ''))) !== '' ? 'Proje: ' . $t : null,
            $path === 'github/ice-aktar' => ($repos = array_filter((array) $r->post('repos', []))) ? 'Depolar: ' . implode(', ', array_map('strval', $repos)) : null,
            $path === 'mesajlar/gonder'  => 'Kime: ' . trim((string) $r->post('to', '')) . (($s = trim((string) $r->post('subject', ''))) !== '' ? ' · Konu: ' . $s : ''),
            $path === 'ozgecmis/kaydet'  => ($t = trim((string) $r->post('title', ''))) !== '' ? $t : null,
            default                      => null,
        };
    }

    /** Takvimde tıklanınca gidilecek panel sayfası */
    private static function link(string $path): string
    {
        return match (true) {
            (bool) preg_match('#^projeler/(\d+)/sil$#', $path)          => 'projeler',
            (bool) preg_match('#^projeler/(\d+)/#', $path, $m)          => 'projeler/' . $m[1] . '/duzenle',
            (bool) preg_match('#^teklifler/(\d+)/guncelle$#', $path, $m) => 'teklifler/' . $m[1],
            str_starts_with($path, 'ayarlar/') && $path !== 'ayarlar/onbellek' => $path,
            $path === 'ayarlar/onbellek'                               => 'ayarlar/github',
            $path === 'bakim-modu'                                     => 'ayarlar/bakim',
            $path === 'giris'                                          => '',
            default                                                    => explode('/', $path)[0],
        };
    }

    // ---------------------------------------------------------------- Takvim sorguları

    /** Tarih aralığındaki kayıtlar (FullCalendar olay listesi için) */
    public static function between(string $start, string $end, array $categories = []): array
    {
        $sql = 'SELECT id, category, title, detail, url, created_at FROM activity_log WHERE created_at >= ? AND created_at < ?';
        $params = [$start, $end];
        $categories = array_values(array_intersect($categories, array_keys(self::CATEGORIES)));
        if ($categories) {
            $sql .= ' AND category IN (' . implode(',', array_fill(0, count($categories), '?')) . ')';
            array_push($params, ...$categories);
        }
        return Database::getInstance()->fetchAll($sql . ' ORDER BY created_at ASC, id ASC LIMIT 3000', $params);
    }
}
