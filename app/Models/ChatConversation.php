<?php

namespace App\Models;

use App\Core\Model;

/** Canlı destek sohbeti */
class ChatConversation extends Model
{
    protected static string $table = 'chat_conversations';
    protected static array $fillable = [
        'token_hash', 'name', 'email', 'status', 'rating', 'rating_comment', 'rated_at', 'page_url', 'ip', 'user_agent', 'unread_admin', 'unread_visitor',
        'admin_typing_at', 'visitor_typing_at', 'visitor_seen_at', 'last_message_at', 'closed_at',
    ];

    public static function findByToken(string $token): ?self
    {
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            return null;
        }
        return static::findBy('token_hash', hash('sha256', $token));
    }

    /** Okunmamış mesajı olan açık sohbet sayısı (menü rozeti) */
    public static function countUnread(): int
    {
        try {
            return static::count('unread_admin > 0');
        } catch (\Throwable) {
            return 0;
        }
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** Değerlendirme etiketleri */
    public const RATING_LABELS = [1 => 'Çok kötü', 2 => 'Kötü', 3 => 'İdare eder', 4 => 'İyi', 5 => 'Mükemmel'];

    public static function stars(int $rating): string
    {
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

    /** Memnuniyet istatistikleri: ortalama, sayı, yıldız dağılımı */
    public static function ratingStats(): array
    {
        $rows = static::db()->fetchAll('SELECT rating, COUNT(*) AS n FROM chat_conversations WHERE rating IS NOT NULL GROUP BY rating');
        $dist = array_fill(1, 5, 0);
        $sum = 0;
        $count = 0;
        foreach ($rows as $r) {
            $dist[(int) $r['rating']] = (int) $r['n'];
            $sum += (int) $r['rating'] * (int) $r['n'];
            $count += (int) $r['n'];
        }
        return ['avg' => $count ? round($sum / $count, 1) : null, 'count' => $count, 'dist' => $dist];
    }

    /** Ziyaretçi son 40 sn içinde görüldüyse çevrimiçi */
    public function visitorOnline(): bool
    {
        return $this->visitor_seen_at && time() - strtotime((string) $this->visitor_seen_at) < 40 && $this->isOpen();
    }

    public function toListArray(): array
    {
        $last = ChatMessage::where('conversation_id = ?', [$this->id], 'id DESC', 1)[0] ?? null;
        return [
            'id'        => (int) $this->id,
            'name'      => (string) $this->name,
            'email'     => (string) $this->email,
            'status'    => (string) $this->status,
            'rating'    => $this->rating !== null ? (int) $this->rating : null,
            'unread'    => (int) $this->unread_admin,
            'online'    => $this->visitorOnline(),
            'last'      => $last ? str_limit(preg_replace('/\s+/', ' ', (string) $last->body), 60) : '',
            'last_from' => $last?->sender,
            'time'      => $this->last_message_at ? format_date($this->last_message_at, date('Y-m-d') === date('Y-m-d', strtotime((string) $this->last_message_at)) ? 'H:i' : 'd.m') : '',
        ];
    }
}
