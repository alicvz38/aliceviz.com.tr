<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Setting;
use App\Support\Realtime;

/**
 * Canlı destek ortak işlemleri.
 * Mesajlar veritabanına yazılır; WebSocket sunucusu (realtime/server.php) yeni mesajları
 * ~0,5 sn içinde iki tarafa iletir. Sunucu kapalıysa site ve panel kısa aralıklarla kontrol eder.
 */
final class LiveChat
{
    public static function enabled(): bool
    {
        return Setting::get('chat_enabled', '1') === '1';
    }

    /** Yönetici çevrimiçi mi? (panel açık: WebSocket bağlı veya son 90 sn içinde panelde) */
    public static function adminOnline(): bool
    {
        $status = Realtime::status();
        if ($status && (int) ($status['clients'] ?? 0) > 0) {
            return true;
        }
        // Önbellek yerine doğrudan veritabanından (uzun süre çalışan canlı sunucu da güncel değeri görsün)
        $seen = (int) Database::getInstance()->fetchColumn("SELECT `value` FROM `settings` WHERE `key` = 'chat_admin_seen'");
        return time() - $seen < 90;
    }

    /** Panel her açıldığında / bildirim kontrolünde çağrılır */
    public static function touchAdmin(): void
    {
        $last = (int) Setting::get('chat_admin_seen', '0');
        if (time() - $last >= 20) {
            Setting::set('chat_admin_seen', (string) time());
        }
    }

    public static function addMessage(ChatConversation $conv, string $sender, string $body): ChatMessage
    {
        $id = ChatMessage::create(['conversation_id' => $conv->id, 'sender' => $sender, 'body' => $body]);
        $db = Database::getInstance();
        if ($sender === 'visitor') {
            $db->query('UPDATE chat_conversations SET unread_admin = unread_admin + 1, last_message_at = NOW(), visitor_typing_at = NULL, visitor_seen_at = NOW() WHERE id = ?', [$conv->id]);
        } elseif ($sender === 'admin') {
            // Not: kapalı sohbet yeniden açılmaz (gönderimden önce denetleyiciler engeller)
            $db->query('UPDATE chat_conversations SET unread_visitor = unread_visitor + 1, unread_admin = 0, last_message_at = NOW(), admin_typing_at = NULL WHERE id = ?', [$conv->id]);
        } else {
            $db->query('UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?', [$conv->id]);
        }
        return ChatMessage::find($id);
    }

    /** "Yazıyor..." durumu: son 5 sn */
    public static function typing(?string $at): bool
    {
        return $at !== null && time() - strtotime($at) <= 5;
    }
}
