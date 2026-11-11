<?php

namespace App\Models;

use App\Core\Model;

/**
 * Mesajlar: iletişim formundan gelenler (inbox) ve panelden gönderilen e-postalar (sent).
 */
class Message extends Model
{
    protected static string $table = 'messages';
    protected static array $fillable = [
        'folder', 'name', 'email', 'phone', 'phone_code', 'phone_verified_at', 'subject', 'body', 'is_read', 'is_starred', 'replied_at',
        'parent_id', 'quote_id', 'send_status', 'send_error', 'ip', 'message_id', 'imap_uid', 'source', 'created_at',
    ];

    /** Giden e-posta mı (Gönderilenler / Taslaklar)? "email" alanı bu durumda alıcıdır */
    public function isOutgoing(): bool
    {
        return in_array($this->folder, ['sent', 'drafts'], true) || $this->send_status === 'sent';
    }

    /** Klasör başına toplam / okunmamış sayıları */
    public static function folderCounts(): array
    {
        $out = [];
        foreach (static::db()->fetchAll('SELECT folder, COUNT(*) AS total, SUM(is_read = 0) AS unread FROM messages GROUP BY folder') as $r) {
            $out[$r['folder']] = ['total' => (int) $r['total'], 'unread' => (int) $r['unread']];
        }
        $starred = static::db()->fetch("SELECT COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread FROM messages WHERE is_starred = 1 AND folder NOT IN ('trash', 'spam', 'all', 'important')");
        $out['starred'] = ['total' => (int) $starred['total'], 'unread' => (int) $starred['unread']];
        return $out;
    }

    /** Mesajın kaynağı: site formu mu, Gmail'den mi geldi? */
    public function sourceLabel(): string
    {
        return match ((string) $this->source) {
            'email' => 'E-posta',
            'panel' => 'Panel',
            default => 'Site formu',
        };
    }

    public static function countUnread(): int
    {
        try {
            return static::count("folder = 'inbox' AND is_read = 0");
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return self[] */
    public static function inFolder(string $folder, ?string $search = null, int $limit = 20, int $offset = 0): array
    {
        [$where, $params] = self::folderFilter($folder, $search);
        return static::where($where, $params, 'created_at DESC, id DESC', $limit, $offset);
    }

    /** @return self[] DataTables için sıralı sayfa ($orderBy koddan gelir, kullanıcı girdisi değildir) */
    public static function inFolderOrdered(string $folder, ?string $search, string $orderBy, int $limit, int $offset): array
    {
        [$where, $params] = self::folderFilter($folder, $search);
        return static::where($where, $params, $orderBy, $limit, $offset);
    }

    public static function countFolder(string $folder, ?string $search = null): int
    {
        [$where, $params] = self::folderFilter($folder, $search);
        return static::count($where, $params);
    }

    private static function folderFilter(string $folder, ?string $search): array
    {
        if ($folder === 'starred') {
            // Yıldızlı: Çöp, Spam ve kopya klasörler (Tüm Postalar, Önemli) hariç
            $where = "is_starred = 1 AND folder NOT IN ('trash', 'spam', 'all', 'important')";
            $params = [];
        } else {
            $where = 'folder = ?';
            $params = [$folder];
        }
        if ($search) {
            $where .= ' AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR body LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }
        return [$where, $params];
    }

    public static function recentFromIp(string $ip): int
    {
        return static::count("folder = 'inbox' AND ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)", [$ip]);
    }

    /** Aynı kişiyle olan yazışmalar (bu mesaj hariç) */
    public function thread(): array
    {
        return static::where('email = ? AND id <> ?', [$this->email, $this->id], 'created_at ASC', 30);
    }

    public function initials(): string
    {
        $name = trim((string) ($this->name ?: $this->email));
        $parts = preg_split('/\s+/u', $name) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $out .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $out ?: '?';
    }
}
