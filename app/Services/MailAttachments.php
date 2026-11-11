<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

/**
 * Panelden gönderilen e-postaların dosya ekleri.
 *
 *  - Dosyalar tarayıcıdan parça parça yüklenir (hostingin yükleme sınırına takılmadan 256 MB'a kadar).
 *  - Gönderirken toplamı küçük olanlar e-postaya EK olarak konur (çoğu posta sunucusu ~25 MB üstünü reddeder).
 *  - Büyükler sunucuda saklanır, e-postaya güvenli İNDİRME BAĞLANTISI eklenir (30 gün geçerli, sonra silinir).
 *  - Dosyalar storage/mail-files altında durur (web'den doğrudan erişilemez; yalnızca bağlantı anahtarıyla).
 */
final class MailAttachments
{
    public const MAX_FILE = 256 * 1024 * 1024;   // dosya başına en fazla 256 MB
    public const MAX_FILES = 20;
    public const INLINE_TOTAL = 18 * 1024 * 1024; // ek olarak gönderilecek toplam (base64 ile ~24 MB olur)
    public const LINK_DAYS = 30; // varsayılan saklama süresi
    /** Seçilebilir saklama süreleri (gün => etiket); 0 = süresiz */
    public const DAY_OPTIONS = [7 => '7 gün', 30 => '30 gün', 90 => '90 gün', 180 => '6 ay', 365 => '1 yıl', 0 => 'Süresiz'];

    /** Panelden ayarlanan saklama süresi (gün); 0 = süresiz */
    public static function days(): int
    {
        $d = (int) \App\Models\Setting::get('mail_file_days', (string) self::LINK_DAYS);
        return array_key_exists($d, self::DAY_OPTIONS) ? $d : self::LINK_DAYS;
    }

    /** Yeni gönderimin son kullanma zamanı (süresizse null) */
    public static function expiry(): ?string
    {
        return self::days() > 0 ? date('Y-m-d H:i:s', strtotime('+' . self::days() . ' days')) : null;
    }
    /** true: küçük dosyalar da ek olarak değil, e-postanın içinde indirme kartı olarak gider */
    public const ALL_AS_CARDS = true;

    public static function dir(string $sub = ''): string
    {
        $dir = STORAGE_PATH . '/mail-files' . ($sub !== '' ? '/' . $sub : '');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
            @file_put_contents(STORAGE_PATH . '/mail-files/.htaccess', "Require all denied\n");
        }
        return $dir;
    }

    /** Sunucunun izin verdiği parça boyutu (upload_max_filesize / post_max_size'ın biraz altı, en fazla 8 MB) */
    public static function chunkSize(): int
    {
        $limit = min(self::bytes((string) ini_get('upload_max_filesize')), self::bytes((string) ini_get('post_max_size')));
        $limit = $limit > 0 ? $limit : 2 * 1024 * 1024;
        return max(256 * 1024, min(8 * 1024 * 1024, (int) ($limit * 0.8)));
    }

    /**
     * Bir parçayı kaydeder; son parçada dosyayı tamamlar.
     * @return array|null Tamamlandıysa ['token','name','size']
     */
    public static function receiveChunk(string $uploadId, int $offset, int $total, string $name, array $file): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $uploadId)) {
            throw new RuntimeException('Geçersiz yükleme.');
        }
        if ($total <= 0 || $total > self::MAX_FILE) {
            throw new RuntimeException('Dosya en fazla ' . self::human(self::MAX_FILE) . ' olabilir.');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            throw new RuntimeException('Dosya parçası yüklenemedi (hata ' . ($file['error'] ?? '?') . ').');
        }
        $part = self::dir('tmp') . '/' . $uploadId . '.part';
        $have = is_file($part) ? (int) filesize($part) : 0;
        $len = (int) $file['size'];
        if ($have === $offset + $len) {
            // Aynı parça yeniden gönderildi (bağlantı kopması): zaten yazılmış
        } elseif ($have !== $offset) {
            throw new RuntimeException('Yükleme sırası bozuldu, dosyayı yeniden ekleyin.');
        } else {
            $in = fopen((string) $file['tmp_name'], 'rb');
            $out = fopen($part, 'ab');
            if (!$in || !$out) {
                throw new RuntimeException('Dosya sunucuya yazılamadı.');
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
        }
        clearstatcache(true, $part);
        $size = (int) filesize($part);
        if ($size > self::MAX_FILE) {
            @unlink($part);
            throw new RuntimeException('Dosya en fazla ' . self::human(self::MAX_FILE) . ' olabilir.');
        }
        if ($size < $total) {
            return null; // devam ediyor
        }

        // Tamamlandı: kalıcı yere taşı
        $token = bin2hex(random_bytes(16));
        $dir = self::dir(date('Y-m'));
        $path = $dir . '/' . $token . '.bin';
        if (!@rename($part, $path)) {
            throw new RuntimeException('Dosya kaydedilemedi.');
        }
        $name = self::safeName($name);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        Database::getInstance()->insert('mail_attachments', [
            'token' => $token,
            'name'  => $name,
            'path'  => substr($path, strlen(STORAGE_PATH) + 1),
            'size'  => $size,
            'mime'  => mb_substr($mime, 0, 120),
        ]);
        return ['token' => $token, 'name' => $name, 'size' => $size, 'human' => self::human($size)];
    }

    /** Henüz gönderilmemiş yüklemeler (formdaki ekler) */
    public static function pending(array $tokens): array
    {
        $tokens = array_values(array_unique(array_filter(array_map('strval', $tokens), static fn ($t) => preg_match('/^[a-f0-9]{32}$/', $t))));
        if (!$tokens) {
            return [];
        }
        return Database::getInstance()->fetchAll(
            'SELECT * FROM mail_attachments WHERE message_id IS NULL AND token IN (' . implode(',', array_fill(0, count($tokens), '?')) . ') ORDER BY size ASC LIMIT ' . self::MAX_FILES,
            $tokens
        );
    }

    /**
     * Hangileri ek, hangileri bağlantı? Küçükten büyüğe: toplam sınırı aşmayanlar ek olur.
     * @return array{0: array, 1: array} [ekler, bağlantılar]
     */
    public static function plan(array $rows): array
    {
        // Tüm dosyalar e-postanın içinde kart olarak (tıklayınca iner); e-postanın altına ayrıca ek konmaz
        if (self::ALL_AS_CARDS) {
            return [[], array_values($rows)];
        }
        $inline = $links = [];
        $sum = 0;
        foreach ($rows as $r) {
            if ($sum + (int) $r['size'] <= self::INLINE_TOTAL) {
                $inline[] = $r;
                $sum += (int) $r['size'];
            } else {
                $links[] = $r;
            }
        }
        return [$inline, $links];
    }

    public static function absPath(array $row): string
    {
        return STORAGE_PATH . '/' . $row['path'];
    }

    public static function url(array $row): string
    {
        return site_origin() . url('dosya/' . $row['token']);
    }

    /** Gönderildikten sonra: e-postaya bağla. Hepsi 30 gün saklanır (bağlantılar alıcı için, ekler panelden indirmek için) */
    public static function attach(array $inline, array $links, int $messageId): void
    {
        $db = Database::getInstance();
        $expires = self::expiry();
        foreach ($inline as $r) {
            $db->update('mail_attachments', ['message_id' => $messageId, 'as_link' => 0, 'expires_at' => $expires], 'id = :id', ['id' => $r['id']]);
        }
        foreach ($links as $r) {
            $db->update('mail_attachments', ['message_id' => $messageId, 'as_link' => 1, 'expires_at' => $expires], 'id = :id', ['id' => $r['id']]);
        }
    }

    /** Henüz gönderilmemiş bir eki kaldır */
    public static function discard(string $token): void
    {
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM mail_attachments WHERE token = ? AND message_id IS NULL', [$token]);
        if ($row) {
            @unlink(self::absPath($row));
            $db->delete('mail_attachments', 'id = ?', [$row['id']]);
        }
    }

    public static function forMessage(int $messageId): array
    {
        return Database::getInstance()->fetchAll('SELECT * FROM mail_attachments WHERE message_id = ? ORDER BY id', [$messageId]);
    }

    /** Panel: tüm dosyalar (gönderilenler + formda kalanlar), e-posta bilgisiyle */
    public static function all(int $limit = 500): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT a.*, m.email, m.name AS recipient, m.subject
             FROM mail_attachments a LEFT JOIN messages m ON m.id = a.message_id
             ORDER BY a.id DESC LIMIT ' . (int) $limit
        );
    }

    public static function stats(): array
    {
        $r = Database::getInstance()->fetch(
            "SELECT COUNT(*) AS c, COALESCE(SUM(CASE WHEN path <> '' THEN size ELSE 0 END), 0) AS b,
                    COALESCE(SUM(downloads), 0) AS d, COALESCE(SUM(path <> ''), 0) AS a FROM mail_attachments"
        );
        return ['count' => (int) $r['c'], 'bytes' => (int) $r['b'], 'downloads' => (int) $r['d'], 'active' => (int) $r['a']];
    }

    /**
     * Dosyaları sunucudan siler (kayıt "silindi" olarak kalır; alıcıdaki bağlantı çalışmaz).
     * @param int[]|null $ids null: tümü
     * @return int Silinen dosya sayısı
     */
    public static function remove(?array $ids): int
    {
        $db = Database::getInstance();
        if ($ids !== null) {
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (!$ids) {
                return 0;
            }
            $rows = $db->fetchAll("SELECT * FROM mail_attachments WHERE path <> '' AND id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
        } else {
            $rows = $db->fetchAll("SELECT * FROM mail_attachments WHERE path <> ''");
        }
        foreach ($rows as $r) {
            @unlink(self::absPath($r));
            if ($r['message_id'] === null) {
                $db->delete('mail_attachments', 'id = ?', [$r['id']]); // gönderilmemiş: kayıt da gider
            } else {
                $db->update('mail_attachments', ['path' => '', 'expires_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $r['id']]);
            }
        }
        return count($rows);
    }

    /** İndirme bağlantısı (süresi dolmamış) */
    public static function findLink(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $row = Database::getInstance()->fetch(
            'SELECT * FROM mail_attachments WHERE token = ? AND as_link = 1 AND (expires_at IS NULL OR expires_at > NOW())',
            [$token]
        );
        return $row && $row['path'] !== '' && is_file(self::absPath($row)) ? $row : null;
    }

    /** Süresi dolan bağlantıları, gönderilmemiş (yarım kalmış) yüklemeleri ve parçaları siler */
    public static function cleanup(): void
    {
        $db = Database::getInstance();
        $old = $db->fetchAll(
            "SELECT * FROM mail_attachments WHERE (expires_at < NOW() AND path <> '')
                OR (message_id IS NULL AND created_at < NOW() - INTERVAL 1 DAY) LIMIT 200"
        );
        foreach ($old as $r) {
            if ($r['path'] !== '') {
                @unlink(self::absPath($r));
            }
            if ($r['message_id'] === null) {
                $db->delete('mail_attachments', 'id = ?', [$r['id']]);
            } else {
                $db->update('mail_attachments', ['path' => ''], 'id = :id', ['id' => $r['id']]); // kayıt kalır: "süresi doldu"
            }
        }
        foreach (glob(self::dir('tmp') . '/*.part') ?: [] as $p) {
            if (filemtime($p) < time() - 86400) {
                @unlink($p);
            }
        }
    }

    /** Panelde indirme (yönetici; ek ya da bağlantı fark etmez, süresi dolmamışsa) */
    public static function findAny(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $row = Database::getInstance()->fetch('SELECT * FROM mail_attachments WHERE token = ?', [$token]);
        return $row && $row['path'] !== '' && is_file(self::absPath($row)) ? $row : null;
    }

    /** Dosyayı indirme olarak gönderir (tarayıcı açmaya çalışmaz; içerik sitenin adresinde çalıştırılmaz) */
    public static function stream(array $row): never
    {
        $path = self::absPath($row);
        $name = (string) $row['name'];
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', str_replace('"', '', $name));
        @set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    /** Dosya türü rozeti: uzantı ve renk (PDF kırmızı, ZIP sarı, Word mavi ...) */
    public static function badge(string $name): array
    {
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $groups = [
            '#e5484d' => ['pdf'],
            '#f5a524' => ['zip', 'rar', '7z', 'tar', 'gz'],
            '#2f6fed' => ['doc', 'docx', 'odt', 'rtf', 'txt'],
            '#1f9d55' => ['xls', 'xlsx', 'csv', 'ods'],
            '#f76b15' => ['ppt', 'pptx', 'odp', 'key'],
            '#8e4ec6' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'psd', 'ai'],
            '#d6409f' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
            '#12a594' => ['mp3', 'wav', 'ogg', 'm4a'],
            '#6e56cf' => ['exe', 'msi', 'apk', 'dmg', 'iso'],
        ];
        $color = '#64748b';
        foreach ($groups as $c => $list) {
            if (in_array($ext, $list, true)) {
                $color = $c;
                break;
            }
        }
        return [$ext !== '' ? strtoupper(mb_substr($ext, 0, 4)) : 'DOSYA', $color];
    }

    /**
     * E-postanın içinde görünen dosya kartları (Gmail'deki Drive dosyaları gibi): tür rozeti, ad, boyut, "İndir".
     * Tablo + satır içi stil: e-posta uygulamalarının hepsinde aynı görünür. Karta tıklayınca indirme başlar.
     */
    public static function emailCards(array $links): string
    {
        if (!$links) {
            return '';
        }
        $until = self::expiry();
        $h = '<div style="margin-top:26px;padding-top:18px;border-top:1px solid rgba(255,255,255,.12)">'
            . '<div style="font-size:13px;color:#94a3b8;margin-bottom:10px">&#128206; <b style="color:#e2e8f0">' . count($links) . ' dosya</b> &middot; ' . ($until ? date('d.m.Y', strtotime($until)) . ' tarihine kadar indirilebilir' : 'karta tıklayarak indirebilirsiniz') . '</div>';
        foreach ($links as $f) {
            [$ext, $color] = self::badge((string) $f['name']);
            $url = htmlspecialchars(self::url($f), ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars((string) $f['name'], ENT_QUOTES, 'UTF-8');
            $btn = '<span style="display:inline-block;padding:8px 14px;border-radius:8px;background:#6366f1;background-image:linear-gradient(135deg,#22d3ee,#a855f7);color:#ffffff;font:bold 13px Arial,Helvetica,sans-serif;white-space:nowrap">&#11015; İndir</span>';
            $h .= '<a href="' . $url . '" style="display:block;text-decoration:none;color:inherit;margin-bottom:8px">'
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #2a3550;border:1px solid rgba(255,255,255,.13);border-radius:12px;background:#0f172a;background:rgba(255,255,255,.06);border-collapse:separate" bgcolor="#0f172a">'
                . '<tr>'
                . '<td width="64" class="fc-ico" style="padding:12px 0 12px 12px;vertical-align:middle">'
                . '<div style="width:46px;height:46px;border-radius:8px;background:' . $color . ';color:#ffffff;font:bold 12px/46px Arial,Helvetica,sans-serif;text-align:center;letter-spacing:.5px">' . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') . '</div>'
                . '</td>'
                . '<td class="fc-info" style="padding:12px;vertical-align:middle;font-family:Arial,Helvetica,sans-serif">'
                . '<div style="font-size:14px;font-weight:bold;color:#f1f5f9;word-break:break-word;overflow-wrap:anywhere">' . $name . '</div>'
                . '<div style="font-size:12px;color:#94a3b8;margin-top:3px">' . self::human((int) $f['size']) . '</div>'
                . '</td>'
                // Masaüstü: düğme yanda (telefonda gizlenir)
                . '<td width="90" align="right" class="fc-btn" style="padding:12px 14px 12px 0;vertical-align:middle">' . $btn . '</td>'
                . '</tr>'
                // Telefon: düğme dosya adının altında, tam genişlik (masaüstünde gizli; ekran kuralı açar)
                . '<tr class="fc-row-m" style="display:none;mso-hide:all"><td colspan="3" style="padding:0 12px 12px;mso-hide:all">'
                . '<div style="display:block;text-align:center;padding:10px 14px;border-radius:8px;background:#6366f1;background-image:linear-gradient(135deg,#22d3ee,#a855f7);color:#ffffff;font:bold 14px Arial,Helvetica,sans-serif">&#11015; İndir</div>'
                . '</td></tr>'
                . '</table></a>';
        }
        return $h . '</div>';
    }

    public static function human(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, $bytes >= 10485760 ? 0 : 1, ',', '.') . ' MB';
        }
        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    private static function safeName(string $name): string
    {
        // Geçersiz UTF-8 (bozuk karakterli ad) önce temizlenir; aksi halde düzenli ifade boş döner
        if (!mb_check_encoding($name, 'UTF-8')) {
            $name = mb_convert_encoding($name, 'UTF-8', 'Windows-1254');
        }
        $name = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\:*?"<>|]+/u', '_', basename(str_replace('\\', '/', $name))));
        $name = mb_substr($name, -150);
        return $name !== '' && $name !== '.' && $name !== '..' ? $name : 'dosya';
    }

    private static function bytes(string $v): int
    {
        $v = trim($v);
        $n = (float) $v;
        return (int) match (strtolower(substr($v, -1))) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
