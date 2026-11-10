<?php

namespace App\Controllers\Site;

use App\Core\Database;
use App\Core\Response;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Setting;
use App\Services\LiveChat;
use App\Services\Notifier;
use App\Support\Realtime;

/**
 * Canlı destek — ziyaretçi tarafı (sitenin sağ altındaki sohbet penceresi).
 * Ziyaretçi, tarayıcısındaki "ac_chat" çereziyle (HttpOnly, 30 gün) kendi sohbetine bağlanır.
 */
class LiveChatController extends SiteController
{
    private const COOKIE = 'ac_chat';

    /** Pencere açılınca / sayfa yüklenince: durum + mesajlar + WebSocket anahtarı */
    public function status(): void
    {
        $conv = $this->current();
        if ($conv && $this->request->get('acik')) {
            Database::getInstance()->query('UPDATE chat_conversations SET unread_visitor = 0, visitor_seen_at = NOW() WHERE id = ?', [$conv->id]);
            $conv = ChatConversation::find((int) $conv->id);
        }
        $this->json(['ok' => true] + $this->payload($conv, 0));
    }

    public function start(): void
    {
        if (!LiveChat::enabled()) {
            Response::error('Canlı destek şu an kapalı.', [], 403);
        }
        $r = $this->request;
        $online = LiveChat::adminOnline();
        $name = mb_substr(trim((string) $r->post('name', '')), 0, 100);
        $email = mb_substr(trim((string) $r->post('email', '')), 0, 150);
        $body = mb_substr(trim((string) $r->post('message', '')), 0, 2000);

        $errors = [];
        if (mb_strlen($name) < 2) $errors['name'] = 'Adınızı yazın.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Geçerli bir e-posta yazın.';
        if (!$online && $email === '') $errors['email'] = 'Şu an çevrimdışıyım; size dönebilmem için e-postanızı yazın.';
        if ($body === '') $errors['message'] = 'Bir mesaj yazın.';
        if ($errors) {
            Response::error('Lütfen işaretli alanları kontrol edin.', $errors);
        }
        if ((string) $r->post('website', '') !== '') {
            Response::success(''); // bot tuzağı
        }
        // Spam koruması: aynı ziyaretçi 10 sn'de bir, aynı IP saatte en fazla 10 sohbet
        if ($wait = $this->throttle('chat_start', 10)) {
            Response::error("Lütfen {$wait} saniye sonra tekrar deneyin.", [], 429);
        }
        if (ChatConversation::count('ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$r->ip()]) >= 10) {
            Response::error('Kısa sürede çok fazla sohbet başlatıldı. Lütfen biraz sonra tekrar deneyin.', [], 429);
        }

        $token = bin2hex(random_bytes(24));
        $id = ChatConversation::create([
            'token_hash'      => hash('sha256', $token),
            'name'            => $name,
            'email'           => $email ?: null,
            'status'          => 'open',
            'page_url'        => mb_substr((string) $r->post('page', ''), 0, 255) ?: null,
            'ip'              => $r->ip(),
            'user_agent'      => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'visitor_seen_at' => date('Y-m-d H:i:s'),
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
        $this->hit('chat_start');
        $this->setCookie($token, time() + 30 * 86400);

        $conv = ChatConversation::find($id);
        LiveChat::addMessage($conv, 'visitor', $body);
        if (!$online) {
            LiveChat::addMessage($conv, 'system', (string) Setting::get('chat_offline', 'Şu an çevrimdışıyım. En kısa sürede e-posta ile dönüş yapacağım.'));
        }

        // Çevrimdışıysanız e-posta ile haber verilir
        if (!$online && $email !== '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            Notifier::newMessage($name, $email, 'Canlı destek mesajı', $body, $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . admin_url('canli-destek?sohbet=' . $id));
        }

        $this->json(['ok' => true] + $this->payload(ChatConversation::find($id), 0));
    }

    public function send(): void
    {
        $conv = $this->currentOrFail();
        $body = mb_substr(trim((string) $this->request->post('body', '')), 0, 2000);
        if ($body === '') {
            Response::error('Boş mesaj gönderilemez.');
        }
        if ($this->throttle('chat_msg', 1)) {
            Response::error('Biraz yavaş lütfen 🙂', [], 429);
        }
        // Kapanan sohbete geri dönülmez: ziyaretçi yeni sohbet başlatmalıdır
        if (!$conv->isOpen()) {
            Response::error('Bu sohbet sona erdi. Yeni bir sohbet başlatabilirsiniz.', [], 409, ['closed' => true]);
        }
        $this->hit('chat_msg');
        $msg = LiveChat::addMessage($conv, 'visitor', $body);
        $this->json(['ok' => true, 'message' => $msg->toClient()]);
    }

    /** Yedek yoklama (WebSocket yoksa): yeni mesajlar + "yazıyor" + çevrimiçi durumu */
    public function poll(): void
    {
        $conv = $this->current();
        if (!$conv) {
            $this->json(['ok' => true, 'conversation' => null, 'messages' => [], 'online' => LiveChat::adminOnline()]);
        }
        $open = (bool) $this->request->get('acik');
        Database::getInstance()->query(
            'UPDATE chat_conversations SET visitor_seen_at = NOW()' . ($open ? ', unread_visitor = 0' : '') . ' WHERE id = ?',
            [$conv->id]
        );
        $this->json(['ok' => true] + $this->payload(ChatConversation::find((int) $conv->id), max(0, (int) $this->request->get('after', 0))));
    }

    public function typing(): void
    {
        $conv = $this->currentOrFail();
        if ($conv->isOpen()) {
            Database::getInstance()->query('UPDATE chat_conversations SET visitor_typing_at = NOW(), visitor_seen_at = NOW() WHERE id = ?', [$conv->id]);
        }
        $this->json(['ok' => true]);
    }

    /** Ziyaretçi sohbeti bitirir (çerez değerlendirme için kalır; "Yeni sohbet"te silinir) */
    public function end(): void
    {
        $conv = $this->currentOrFail();
        if ($conv->isOpen()) {
            LiveChat::addMessage($conv, 'system', 'Ziyaretçi sohbeti sonlandırdı.');
            Database::getInstance()->query("UPDATE chat_conversations SET status = 'closed', closed_at = NOW(), visitor_typing_at = NULL, admin_typing_at = NULL WHERE id = ?", [$conv->id]);
        }
        $this->json(['ok' => true] + $this->payload(ChatConversation::find((int) $conv->id), 0));
    }

    /** Sohbet bittikten sonra 1-5 yıldız + isteğe bağlı yorum (her sohbet bir kez) */
    public function rate(): void
    {
        $conv = $this->currentOrFail();
        $rating = (int) $this->request->post('rating', 0);
        $comment = mb_substr(trim((string) $this->request->post('comment', '')), 0, 500);

        if ($conv->isOpen()) {
            Response::error('Değerlendirme sohbet bittikten sonra yapılabilir.', [], 409);
        }
        if ($conv->rating !== null) {
            Response::error('Bu sohbeti zaten değerlendirdiniz. Teşekkürler!', [], 409);
        }
        if ($rating < 1 || $rating > 5) {
            Response::error('Lütfen 1 ile 5 arasında bir puan seçin.', ['rating' => 'Puan seçin.']);
        }

        Database::getInstance()->query(
            'UPDATE chat_conversations SET rating = ?, rating_comment = ?, rated_at = NOW() WHERE id = ? AND rating IS NULL',
            [$rating, $comment !== '' ? $comment : null, $conv->id]
        );
        LiveChat::addMessage($conv, 'system', 'Değerlendirme: ' . ChatConversation::stars($rating) . ' (' . $rating . '/5 · ' . ChatConversation::RATING_LABELS[$rating] . ')'
            . ($comment !== '' ? "\n“" . $comment . '”' : ''));

        $this->json(['ok' => true, 'message' => 'Değerlendirmeniz için teşekkürler!'] + $this->payload(ChatConversation::find((int) $conv->id), 0));
    }

    /** Biten sohbeti bırakıp yeni sohbet formuna dön */
    public function newChat(): void
    {
        $this->setCookie('', time() - 3600);
        $this->json(['ok' => true] + $this->payload(null, 0));
    }

    // ------------------------------------------------------------

    private function payload(?ChatConversation $conv, int $after): array
    {
        $online = LiveChat::adminOnline();
        $data = [
            'enabled'  => LiveChat::enabled(),
            'online'   => $online,
            'welcome'  => (string) Setting::get('chat_welcome', ''),
            'offline'  => (string) Setting::get('chat_offline', ''),
            'conversation' => null,
            'messages' => [],
            'typing'   => false,
            'unread'   => 0,
            'ws'       => null,
        ];
        if ($conv) {
            $data['conversation'] = [
                'id'     => (int) $conv->id,
                'name'   => (string) $conv->name,
                'email'  => (string) $conv->email,
                'status' => (string) $conv->status,
                'rating' => $conv->rating !== null ? (int) $conv->rating : null,
            ];
            $data['messages'] = ChatMessage::after((int) $conv->id, $after);
            $data['typing'] = LiveChat::typing($conv->admin_typing_at);
            $data['unread'] = (int) $conv->unread_visitor;
            if (Realtime::isRunning()) {
                $data['ws'] = ['port' => Realtime::port(), 'token' => Realtime::visitorToken((int) $conv->id)];
            }
        }
        return $data;
    }

    private function current(): ?ChatConversation
    {
        $token = (string) ($_COOKIE[self::COOKIE] ?? '');
        return $token !== '' ? ChatConversation::findByToken($token) : null;
    }

    private function currentOrFail(): ChatConversation
    {
        $conv = $this->current();
        if (!$conv) {
            Response::error('Sohbet bulunamadı. Lütfen yeni bir sohbet başlatın.', [], 404, ['reset' => true]);
        }
        return $conv;
    }

    private function setCookie(string $value, int $expires): void
    {
        $value === '' ? ($_COOKIE[self::COOKIE] = null) : ($_COOKIE[self::COOKIE] = $value);
        setcookie(self::COOKIE, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
