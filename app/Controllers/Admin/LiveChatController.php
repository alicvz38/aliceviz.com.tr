<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Core\RememberMe;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Setting;
use App\Services\LiveChat;
use App\Support\Realtime;

/** Panel > Canlı Destek: sohbet listesi, anlık yazışma, ziyaretçi bilgileri */
class LiveChatController extends AdminController
{
    public function index(): void
    {
        LiveChat::touchAdmin();
        $this->render('chat/index', [
            'pageTitle'   => 'Canlı Destek',
            'breadcrumbs' => ['Canlı Destek' => null],
            'selected'    => (int) $this->request->get('sohbet', 0),
            'settings'    => Setting::allSettings(),
            'stats'       => ChatConversation::ratingStats(),
            'realtime'    => Realtime::isRunning(),
        ]);
    }

    /** Sohbet listesi (AJAX) */
    public function list(): void
    {
        LiveChat::touchAdmin();
        $filter = (string) $this->request->get('durum', 'open');
        $where = match ($filter) {
            'closed' => "status = 'closed'",
            'all'    => '1',
            default  => "status = 'open'",
        };
        $items = array_map(
            static fn (ChatConversation $c) => $c->toListArray(),
            ChatConversation::where($where, [], 'unread_admin > 0 DESC, last_message_at DESC', 200)
        );
        $this->json([
            'ok'     => true,
            'items'  => $items,
            'counts' => [
                'open'   => ChatConversation::count("status = 'open'"),
                'closed' => ChatConversation::count("status = 'closed'"),
                'unread' => ChatConversation::countUnread(),
            ],
            'rating' => ChatConversation::ratingStats(),
        ]);
    }

    /** Sohbetin mesajları (?after= ile yalnızca yeniler); açılan sohbet okundu sayılır */
    public function messages(string $id): void
    {
        LiveChat::touchAdmin();
        $conv = $this->findOrFail((int) $id);
        if ((int) $conv->unread_admin > 0) {
            Database::getInstance()->query('UPDATE chat_conversations SET unread_admin = 0 WHERE id = ?', [$conv->id]);
        }
        $this->json([
            'ok'           => true,
            'conversation' => $this->info($conv),
            'messages'     => ChatMessage::after((int) $conv->id, max(0, (int) $this->request->get('after', 0))),
            'typing'       => LiveChat::typing($conv->visitor_typing_at),
        ]);
    }

    public function send(string $id): void
    {
        $conv = $this->findOrFail((int) $id);
        $body = mb_substr(trim((string) $this->request->post('body', '')), 0, 4000);
        if (!$conv->isOpen()) {
            Response::error('Bu sohbet kapatıldı; kapalı sohbete mesaj gönderilemez.', [], 409);
        }
        if ($body === '') {
            Response::error('Boş mesaj gönderilemez.');
        }
        LiveChat::touchAdmin();
        $msg = LiveChat::addMessage($conv, 'admin', $body);
        $this->json(['ok' => true, 'message' => $msg->toClient()]);
    }

    public function typing(string $id): void
    {
        Database::getInstance()->query('UPDATE chat_conversations SET admin_typing_at = NOW() WHERE id = ?', [(int) $id]);
        $this->json(['ok' => true]);
    }

    public function close(string $id): void
    {
        $conv = $this->findOrFail((int) $id);
        if ($conv->isOpen()) {
            LiveChat::addMessage($conv, 'system', 'Sohbet sonlandırıldı. Görüşmeyi değerlendirebilir, yeni bir sorunuz olursa yeni sohbet başlatabilirsiniz.');
            Database::getInstance()->query("UPDATE chat_conversations SET status = 'closed', closed_at = NOW(), admin_typing_at = NULL, visitor_typing_at = NULL WHERE id = ?", [$conv->id]);
        }
        $this->respond('Sohbet kapatıldı.', admin_url('canli-destek'), ['conversation' => $this->info(ChatConversation::find((int) $conv->id))]);
    }

    public function destroy(string $id): void
    {
        $conv = $this->findOrFail((int) $id);
        $db = Database::getInstance();
        $db->delete('chat_messages', 'conversation_id = ?', [$conv->id]);
        $db->delete('chat_conversations', 'id = ?', [$conv->id]);
        $this->respond('Sohbet silindi.', admin_url('canli-destek'));
    }

    /** Canlı destek ayarları (aç/kapa, karşılama ve çevrimdışı mesajı) */
    public function settings(): void
    {
        Setting::set('chat_enabled', $this->request->post('chat_enabled') ? '1' : '0');
        Setting::set('chat_welcome', mb_substr(trim((string) $this->request->post('chat_welcome', '')), 0, 500));
        Setting::set('chat_offline', mb_substr(trim((string) $this->request->post('chat_offline', '')), 0, 500));
        $this->respond('Canlı destek ayarları kaydedildi.', admin_url('canli-destek'));
    }

    // ------------------------------------------------------------

    private function info(ChatConversation $c): array
    {
        return [
            'id'       => (int) $c->id,
            'name'     => (string) $c->name,
            'email'    => (string) $c->email,
            'status'   => (string) $c->status,
            'online'   => $c->visitorOnline(),
            'page'     => (string) $c->page_url,
            'ip'       => (string) $c->ip,
            'device'   => RememberMe::describe((string) $c->user_agent),
            'started'  => format_date($c->created_at, 'd.m.Y H:i'),
            'closed'   => $c->closed_at ? format_date($c->closed_at, 'd.m.Y H:i') : null,
            'rating'   => $c->rating !== null ? (int) $c->rating : null,
            'rating_label'   => $c->rating !== null ? ChatConversation::RATING_LABELS[(int) $c->rating] : null,
            'rating_comment' => (string) $c->rating_comment,
            'mail_url' => $c->email ? admin_url('mesajlar/yeni?' . http_build_query(['kime' => $c->email, 'ad' => $c->name, 'konu' => 'Canlı destek görüşmemiz hakkında'])) : null,
        ];
    }

    private function findOrFail(int $id): ChatConversation
    {
        $conv = ChatConversation::find($id);
        if (!$conv) {
            Response::error('Sohbet bulunamadı.', [], 404);
        }
        return $conv;
    }
}
