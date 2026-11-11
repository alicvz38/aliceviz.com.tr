<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\Message;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\MailFetcher;
use App\Services\Mailer;
use App\Services\Notifier;
use RuntimeException;

/**
 * Mesajlar / e-posta sistemi
 *  - Gmail'in tüm klasörleri (Gelen, Önemli, Gönderilen, Taslak, Spam, Çöp, etiketler) otomatik aynalanır
 *  - Sitedeki iletişim formundan gelen mesajlar Gelen Kutusu'na düşer
 *  - Yanıtla, yeni e-posta, yıldızla, okundu/okunmadı, sil — hepsi AJAX
 */
class MessageController extends AdminController
{
    private const PER_PAGE = 20;
    /** Eski adres uyumluluğu: ?klasor=gelen */
    private const ALIASES = ['gelen' => 'inbox', 'gonderilen' => 'sent', 'yildizli' => 'starred', 'taslaklar' => 'drafts', 'cop' => 'trash'];

    public function index(): void
    {
        $folders = MailFetcher::folders();
        $requested = (string) $this->request->get('klasor', 'inbox');
        $folder = self::ALIASES[$requested] ?? $requested;
        if ($folder !== 'starred' && !isset($folders[$folder])) {
            $folder = 'inbox';
        }
        // Liste DataTables ile mesajlar/veri adresinden sayfa sayfa yüklenir
        $this->render('messages/index', [
            'folderKey'   => $folder,
            'search'      => trim(mb_substr((string) $this->request->get('q', ''), 0, 80)),
            'pageTitle'   => 'Mesajlar',
            'breadcrumbs' => ['Mesajlar' => null],
            'folders'     => $folders,
            'counts'      => Message::folderCounts(),
            'configured'  => Mailer::isConfigured(),
            'imapEnabled' => MailFetcher::isEnabled(),
            'imapError'   => (string) Setting::get('imap_last_error', ''),
            'imapLast'    => (int) Setting::get('imap_last_sync', '0'),
        ]);
    }

    public function show(string $id): void
    {
        $message = $this->findOrFail((int) $id);
        if (!$message->is_read) {
            // Açılan e-posta Gmail'de de okundu olsun
            try {
                MailFetcher::remoteFlag([$message], '\\Seen', true);
            } catch (\Throwable) {
            }
            $this->updateMany([$message], ['is_read' => 1]);
        }

        $this->render('messages/show', [
            'pageTitle'   => $message->subject,
            'breadcrumbs' => ['Mesajlar' => admin_url('mesajlar'), str_limit($message->subject, 40) => null],
            'message'     => $message,
            // Kişinin diğer e-postaları değil, yalnızca bu mesaja verilen yanıtlar
            'replies'     => Message::where("parent_id = ? AND id <> ? AND folder = 'sent'", [$message->id, $message->id], 'created_at ASC', 50),
            'quote'       => $message->quote_id ? QuoteRequest::find((int) $message->quote_id) : null,
            'configured'  => Mailer::isConfigured(),
            'signature'   => Notifier::signature(),
        ]);
    }

    /** Yeni e-posta / yanıt formu. ?kime=..&ad=..&konu=..&yanit={id}&teklif={id} */
    public function compose(): void
    {
        $g = fn (string $k) => trim((string) $this->request->get($k, ''));
        $parent = $g('yanit') !== '' ? Message::find((int) $g('yanit')) : null;
        $quote = $g('teklif') !== '' ? QuoteRequest::find((int) $g('teklif')) : null;

        // Taslaktan devam: Gmail taslağının alıcı, konu ve metni forma gelir
        $draft = $g('taslak') !== '' ? Message::find((int) $g('taslak')) : null;
        if ($draft && $draft->folder !== 'drafts') {
            $draft = null;
        }

        $to = $draft?->email ?: ($parent?->email ?? $quote?->email ?? $g('kime'));
        $name = $draft?->name ?? $parent?->name ?? $quote?->name ?? $g('ad');
        $subject = $draft ? ($draft->subject === '(konusuz)' ? '' : (string) $draft->subject) : $g('konu');
        if ($parent && $subject === '') {
            $subject = (str_starts_with(mb_strtolower($parent->subject), 're:') ? '' : 'Re: ') . $parent->subject;
        }
        if ($quote && $subject === '') {
            $subject = 'Teklifiniz hakkında: ' . $quote->serviceLabel();
        }

        $this->render('messages/compose', [
            'pageTitle'   => 'Yeni E-posta',
            'breadcrumbs' => ['Mesajlar' => admin_url('mesajlar'), 'Yeni E-posta' => null],
            'to'          => $to,
            'name'        => $name,
            'subject'     => $subject,
            'parent'      => $parent,
            'quote'       => $quote,
            'draftBody'   => $draft?->body,
            'configured'  => Mailer::isConfigured(),
            'signature'   => Notifier::signature(),
        ]);
    }

    /** E-postayı SMTP ile gönderir ve "Gönderilenler"e kaydeder */
    public function send(): void
    {
        $r = $this->request;
        $to = trim((string) $r->post('to', ''));
        $name = mb_substr((string) $r->post('name', ''), 0, 100);
        $subject = mb_substr((string) $r->post('subject', ''), 0, 200);
        $body = (string) $r->post('body', '');
        $parentId = (int) $r->post('parent_id', 0) ?: null;
        $quoteId = (int) $r->post('quote_id', 0) ?: null;

        $errors = [];
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $errors['to'] = 'Geçerli bir e-posta adresi girin.';
        if ($subject === '') $errors['subject'] = 'Konu zorunludur.';
        if (mb_strlen(trim($body)) < 2) $errors['body'] = 'Mesaj boş olamaz.';
        if ($errors) {
            $this->backAdmin('mesajlar/yeni', 'Lütfen işaretli alanları kontrol edin.', $errors);
        }

        $record = [
            'folder'    => 'sent',
            'name'      => $name ?: null,
            'email'     => $to,
            'subject'   => $subject,
            'body'      => $body,
            'is_read'   => 1,
            'parent_id' => $parentId,
            'quote_id'  => $quoteId,
        ];

        $record['source'] = 'panel';
        $parentMsg = $parentId ? Message::find($parentId) : null;

        // Dosyalar: toplamı küçük olanlar ek olarak, büyükler indirme bağlantısıyla gider
        \App\Services\MailAttachments::cleanup();
        $files = \App\Services\MailAttachments::pending((array) $r->post('attachments', []));
        [$inline, $links] = \App\Services\MailAttachments::plan($files);
        $mailBody = $body;
        if ($links) {
            // Düz metin sürümü (HTML göstermeyen e-posta uygulamaları için): bağlantı listesi
            $mailBody .= "\n\n—\nDosyalar (indirme bağlantısı, " . \App\Services\MailAttachments::LINK_DAYS . " gün geçerli):\n";
            foreach ($links as $f) {
                $mailBody .= '• ' . $f['name'] . ' (' . \App\Services\MailAttachments::human((int) $f['size']) . '): ' . \App\Services\MailAttachments::url($f) . "\n";
            }
        }
        // HTML sürümünde büyük dosyalar kart olarak görünür (tıklayınca iner); panelde dosyalar balonun altında listelenir
        $mailHtml = Mailer::textToHtml($body, \App\Services\MailAttachments::emailCards($links));

        try {
            $messageId = (new Mailer())->send($to, $subject, $mailBody, [
                'name'        => $name,
                'in_reply_to' => $parentMsg?->message_id ?: null,
                'html'        => $mailHtml,
                'attachments' => array_map(static fn ($f) => [
                    'path' => \App\Services\MailAttachments::absPath($f), 'name' => $f['name'], 'mime' => $f['mime'],
                ], $inline),
            ]);
        } catch (RuntimeException $e) {
            Message::create($record + ['send_status' => 'failed', 'send_error' => mb_substr($e->getMessage(), 0, 500)]);
            Response::error('E-posta gönderilemedi: ' . $e->getMessage(), [], 422);
        }

        // Message-ID saklanır: Gmail "Gönderilmiş" kopyası senkronize edilince çift kayıt oluşmaz
        $id = Message::create($record + ['send_status' => 'sent', 'message_id' => $messageId]);
        if ($files) {
            \App\Services\MailAttachments::attach($inline, $links, (int) $id);
        }
        if ($parentId) {
            Message::updateById($parentId, ['replied_at' => date('Y-m-d H:i:s'), 'is_read' => 1]);
        }
        if ($quoteId && ($q = QuoteRequest::find($quoteId)) && in_array($q->status, ['new', 'seen'], true)) {
            QuoteRequest::updateById($quoteId, ['status' => 'contacted']);
        }

        // Yanıttan sonra aynı konuşmada kal; yeni e-postada gönderilene git
        $target = $parentId ? admin_url('mesajlar/' . $parentId) : admin_url('mesajlar/' . $id);
        $this->respond('E-posta ' . $to . ' adresine gönderildi.', $target, [
            'id'   => $id,
            'html' => $this->view->partial('message-bubble', ['m' => Message::find($id)]),
        ]);
    }

    /** Ek dosya yükleme (parça parça; 256 MB'a kadar) */
    public function uploadChunk(): void
    {
        $r = $this->request;
        $file = $r->file('chunk');
        if (!$file) {
            Response::error('Dosya parçası alınamadı.');
        }
        try {
            $done = \App\Services\MailAttachments::receiveChunk(
                (string) $r->post('upload_id', ''),
                (int) $r->post('offset', 0),
                (int) $r->post('total', 0),
                (string) $r->post('name', 'dosya'),
                $file
            );
        } catch (RuntimeException $e) {
            Response::error($e->getMessage());
        }
        $this->json(['ok' => true, 'done' => $done !== null, 'file' => $done]);
    }

    /** Panelden ek indirme (gönderilmiş e-postanın dosyaları, 30 gün) */
    public function downloadAttachment(string $token): void
    {
        $row = \App\Services\MailAttachments::findAny($token);
        if (!$row) {
            $this->respond('Dosya bulunamadı ya da süresi doldu (dosyalar 30 gün saklanır).', admin_url('mesajlar'), [], 'danger');
        }
        \App\Services\MailAttachments::stream($row);
    }

    /** Gönderilmeden önce eki kaldır */
    public function discardAttachment(string $token): void
    {
        \App\Services\MailAttachments::discard($token);
        $this->json(['ok' => true]);
    }

    /**
     * DataTables sunucu taraflı veri ucu (binlerce e-posta için sayfalı, aranabilir, sıralanabilir)
     * GET mesajlar/veri?klasor=inbox&draw=1&start=0&length=25&search[value]=..&order[0][column]=4&order[0][dir]=desc
     */
    public function data(): void
    {
        $folders = MailFetcher::folders();
        $folder = (string) $this->request->get('klasor', 'inbox');
        if ($folder !== 'starred' && !isset($folders[$folder])) {
            $folder = 'inbox';
        }
        $search = trim(mb_substr((string) ($_GET['search']['value'] ?? ''), 0, 80)) ?: null;
        $start = max(0, (int) $this->request->get('start', 0));
        $length = (int) $this->request->get('length', 25);
        $length = $length < 1 || $length > 200 ? 25 : $length;

        // Sıralanabilir sütunlar: 2 = kişi, 3 = konu, 4 = tarih
        $columns = [2 => 'COALESCE(name, email)', 3 => 'subject', 4 => 'created_at'];
        $col = (int) ($_GET['order'][0]['column'] ?? 4);
        $dir = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy = ($columns[$col] ?? 'created_at') . ' ' . $dir . ', id ' . $dir;

        $total = Message::countFolder($folder);
        $filtered = $search ? Message::countFolder($folder, $search) : $total;
        $rows = Message::inFolderOrdered($folder, $search, $orderBy, $length, $start);

        $data = [];
        foreach ($rows as $m) {
            $data[] = [
                'DT_RowId'    => 'msg-' . $m->id,
                'DT_RowClass' => (!$m->isOutgoing() && !$m->is_read) ? 'unread' : '',
                'DT_RowData'  => ['id' => (int) $m->id],
                0 => $this->view->partial('message-cells', ['m' => $m, 'cell' => 'check']),
                1 => $this->view->partial('message-cells', ['m' => $m, 'cell' => 'star']),
                2 => $this->view->partial('message-cells', ['m' => $m, 'cell' => 'from', 'folder' => $folder]),
                3 => $this->view->partial('message-cells', ['m' => $m, 'cell' => 'subject']),
                4 => $this->view->partial('message-cells', ['m' => $m, 'cell' => 'date']),
                5 => $this->view->partial('message-cells', ['m' => $m, 'cell' => 'actions']),
            ];
        }

        $this->json([
            'draw'            => (int) $this->request->get('draw', 0),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
            'counts'          => Message::folderCounts(),
        ]);
    }

    /**
     * Seçilenler üzerinde toplu işlem: okundu | okunmadi | yildizla | yildizi-kaldir | sil
     * Gmail'den gelen e-postalarda değişiklik Gmail'e de yansır.
     */
    public function bulk(): void
    {
        $action = (string) $this->request->post('action', '');
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->post('ids', [])))));
        if (!$ids) {
            $this->respond('Önce en az bir e-posta seçin.', admin_url('mesajlar'), [], 'warning');
        }
        $ids = array_slice($ids, 0, 1000);
        $messages = Message::where('id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids, 'id ASC');

        try {
            switch ($action) {
                case 'okundu':
                case 'okunmadi':
                    $read = $action === 'okundu';
                    MailFetcher::remoteFlag($messages, '\\Seen', $read);
                    $this->updateMany($messages, ['is_read' => $read ? 1 : 0]);
                    $text = count($messages) . ' e-posta ' . ($read ? 'okundu' : 'okunmadı') . ' olarak işaretlendi.';
                    break;
                case 'yildizla':
                case 'yildizi-kaldir':
                    $star = $action === 'yildizla';
                    MailFetcher::remoteFlag($messages, '\\Flagged', $star);
                    $this->updateMany($messages, ['is_starred' => $star ? 1 : 0]);
                    $text = count($messages) . ' e-posta ' . ($star ? 'yıldızlandı' : 'yıldızı kaldırıldı') . '.';
                    break;
                case 'sil':
                    MailFetcher::remoteDelete($messages);
                    Message::db()->query('DELETE FROM messages WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
                    $text = count($messages) . ' e-posta silindi' . (array_filter($messages, static fn ($m) => $m->imap_uid) ? ' (posta kutusunda da Çöp Kutusu\'na taşındı).' : '.');
                    break;
                default:
                    $this->respond('Geçersiz işlem.', admin_url('mesajlar'), [], 'danger');
            }
        } catch (RuntimeException $e) {
            $this->respond('Posta kutusuyla eşitlenemedi: ' . $e->getMessage(), admin_url('mesajlar'), [], 'danger');
        }

        $this->respond($text, admin_url('mesajlar'), ['unread' => Message::countUnread(), 'counts' => Message::folderCounts()]);
    }

    /** Klasördeki tüm e-postaları okundu işaretle (posta kutusu dahil) */
    public function readAll(): void
    {
        $folders = MailFetcher::folders();
        $folder = (string) $this->request->post('klasor', 'inbox');
        if ($folder !== 'starred' && !isset($folders[$folder])) {
            $folder = 'inbox';
        }
        $db = Message::db();

        try {
            if ($folder === 'starred') {
                $messages = Message::where("is_starred = 1 AND is_read = 0 AND folder NOT IN ('trash', 'spam', 'all', 'important')");
                MailFetcher::remoteFlag($messages, '\\Seen', true);
            } else {
                MailFetcher::remoteMarkFolderRead($folder);
            }
        } catch (RuntimeException $e) {
            $this->respond('Posta kutusuyla eşitlenemedi: ' . $e->getMessage(), admin_url('mesajlar'), [], 'danger');
        }

        // Aynı e-postanın diğer klasörlerdeki kopyaları da (Tüm Postalar, Önemli...) okundu olsun
        $where = $folder === 'starred' ? "is_starred = 1 AND folder NOT IN ('trash', 'spam', 'all', 'important')" : 'folder = ?';
        $params = $folder === 'starred' ? [] : [$folder];
        $db->query(
            "UPDATE messages m JOIN (SELECT DISTINCT message_id FROM messages WHERE {$where} AND is_read = 0 AND message_id IS NOT NULL) t
             ON m.message_id = t.message_id SET m.is_read = 1",
            $params
        );
        $count = $db->query("UPDATE messages SET is_read = 1 WHERE {$where} AND is_read = 0", $params)->rowCount();

        $this->respond(
            $count ? $count . ' e-posta okundu olarak işaretlendi.' : 'Okunmamış e-posta yok.',
            admin_url('mesajlar'),
            ['unread' => Message::countUnread(), 'counts' => Message::folderCounts()],
            $count ? 'success' : 'info'
        );
    }

    /** "Spam klasörünü temizle" / "Çöp kutusunu boşalt": klasördeki tüm e-postalar Gmail dahil kalıcı silinir */
    public function emptyFolder(): void
    {
        $folder = (string) $this->request->post('klasor', '');
        if (!in_array($folder, ['spam', 'trash'], true)) {
            $this->respond('Yalnızca Spam ve Çöp Kutusu toplu temizlenebilir.', admin_url('mesajlar'), [], 'danger');
        }
        $label = $folder === 'spam' ? 'Spam klasörü' : 'Çöp kutusu';
        try {
            MailFetcher::remoteEmptyFolder($folder);
        } catch (RuntimeException $e) {
            $this->respond('Posta kutusunda temizlenemedi: ' . $e->getMessage(), admin_url('mesajlar?klasor=' . $folder), [], 'danger');
        }
        $count = Message::db()->delete('messages', 'folder = ?', [$folder]);
        $this->respond(
            $count ? $label . ' temizlendi: ' . $count . ' e-posta kalıcı olarak silindi.' : $label . ' zaten boş.',
            admin_url('mesajlar?klasor=' . $folder),
            ['unread' => Message::countUnread(), 'counts' => Message::folderCounts()],
            $count ? 'success' : 'info'
        );
    }

    /** Değişikliği seçilen e-postalara ve diğer klasörlerdeki kopyalarına uygular */
    private function updateMany(array $messages, array $set): void
    {
        $db = Message::db();
        $ids = array_map(static fn ($m) => (int) $m->id, $messages);
        $mids = array_values(array_filter(array_map(static fn ($m) => $m->message_id, $messages)));
        $col = array_key_first($set);
        $db->query("UPDATE messages SET `{$col}` = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')', array_merge([$set[$col]], $ids));
        if ($mids) {
            $db->query("UPDATE messages SET `{$col}` = ? WHERE message_id IN (" . implode(',', array_fill(0, count($mids), '?')) . ')', array_merge([$set[$col]], $mids));
        }
    }

    public function star(string $id): void
    {
        $message = $this->findOrFail((int) $id);
        $starred = $message->is_starred ? 0 : 1;
        try {
            MailFetcher::remoteFlag([$message], '\\Flagged', (bool) $starred);
        } catch (RuntimeException $e) {
            $this->respond('Posta kutusuyla eşitlenemedi: ' . $e->getMessage(), admin_url('mesajlar'), [], 'danger');
        }
        $this->updateMany([$message], ['is_starred' => $starred]);
        $this->respond($starred ? 'Yıldızlandı.' : 'Yıldız kaldırıldı.', admin_url('mesajlar'), ['starred' => (bool) $starred]);
    }

    public function markRead(string $id): void
    {
        $message = $this->findOrFail((int) $id);
        $read = $message->is_read ? 0 : 1;
        try {
            MailFetcher::remoteFlag([$message], '\\Seen', (bool) $read);
        } catch (RuntimeException $e) {
            $this->respond('Posta kutusuyla eşitlenemedi: ' . $e->getMessage(), admin_url('mesajlar'), [], 'danger');
        }
        $this->updateMany([$message], ['is_read' => $read]);
        $this->respond($read ? 'Okundu olarak işaretlendi.' : 'Okunmadı olarak işaretlendi.', admin_url('mesajlar'), [
            'read'   => (bool) $read,
            'unread' => Message::countUnread(),
        ]);
    }

    public function destroy(string $id): void
    {
        $message = $this->findOrFail((int) $id);
        $back = admin_url('mesajlar' . ($message->folder !== 'inbox' ? '?klasor=' . rawurlencode((string) $message->folder) : ''));

        // Gmail'den gelen e-posta ise Gmail'de de sil (Çöp Kutusu'na taşı)
        $inTrash = in_array($message->folder, ['trash', 'spam'], true);
        try {
            MailFetcher::deleteRemote($message);
        } catch (RuntimeException $e) {
            $this->respond('Posta kutusunda silinemedi: ' . $e->getMessage(), $back, [], 'danger');
        }

        Message::deleteById((int) $message->id);
        $text = $message->imap_uid
            ? ($inTrash ? 'E-posta kalıcı olarak silindi.' : 'E-posta Çöp Kutusu\'na taşındı.')
            : 'Mesaj silindi.';
        $this->respond($text, $back, ['unread' => Message::countUnread()]);
    }

    // ------------------------------------------------------------ SMTP ayarları

    private const SETTING_KEYS = ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'mail_from_email', 'mail_from_name', 'mail_signature'];

    public function settings(): void
    {
        $this->render('messages/settings', [
            'pageTitle'   => 'E-posta Ayarları',
            'breadcrumbs' => ['Mesajlar' => admin_url('mesajlar'), 'E-posta Ayarları' => null],
            'settings'    => Setting::allSettings(),
            'hasPass'     => Setting::get('smtp_pass', '') !== '',
        ]);
    }

    public function saveSettings(): void
    {
        $values = $this->settingInput();
        Setting::setMany($values);
        Setting::set('mail_notify', $this->request->post('mail_notify') ? '1' : '0');
        Setting::set('mail_autoreply', $this->request->post('mail_autoreply') ? '1' : '0');

        // Gelen e-postalar (IMAP)
        Setting::set('imap_enabled', $this->request->post('imap_enabled') ? '1' : '0');
        Setting::set('imap_host', mb_substr((string) $this->request->post('imap_host', 'imap.gmail.com'), 0, 190));
        Setting::set('imap_port', (string) (max(1, min(65535, (int) $this->request->post('imap_port', 993))) ?: 993));
        Setting::set('imap_filter', $this->request->post('imap_filter') === 'contacts' ? 'contacts' : 'all');
        $pass = (string) $this->request->post('smtp_pass', '');
        if ($pass !== '') {
            Setting::set('smtp_pass', $pass);
        }
        $this->respond('E-posta ayarları kaydedildi.', admin_url('mesajlar/ayarlar'));
    }

    /** Formdaki (henüz kaydedilmemiş) ayarlarla bağlanıp test e-postası gönderir */
    public function testSettings(): void
    {
        $v = $this->settingInput();
        $pass = (string) $this->request->post('smtp_pass', '') ?: (string) Setting::get('smtp_pass', '');
        $to = (string) Setting::get('contact_email', '') ?: $v['mail_from_email'] ?: $v['smtp_user'];

        try {
            $mailer = new Mailer([
                'host' => $v['smtp_host'], 'port' => $v['smtp_port'], 'secure' => $v['smtp_secure'],
                'user' => $v['smtp_user'], 'pass' => $pass,
                'from' => $v['mail_from_email'], 'name' => $v['mail_from_name'],
            ]);
            $mailer->send($to, 'Test e-postası', "Tebrikler! SMTP ayarlarınız doğru çalışıyor.\n\nBu e-posta yönetim panelinden test amacıyla gönderildi.");
        } catch (RuntimeException $e) {
            Response::error($e->getMessage());
        }
        Response::success('Bağlantı başarılı! Test e-postası ' . $to . ' adresine gönderildi.');
    }

    private function settingInput(): array
    {
        $v = [];
        foreach (self::SETTING_KEYS as $key) {
            $v[$key] = mb_substr((string) $this->request->post($key, ''), 0, $key === 'mail_signature' ? 1000 : 190);
        }
        $v['smtp_port'] = (string) max(1, min(65535, (int) $v['smtp_port'] ?: 587));
        $v['smtp_secure'] = in_array($v['smtp_secure'], ['ssl', 'tls', 'none'], true) ? $v['smtp_secure'] : 'tls';
        if ($v['mail_from_email'] !== '' && !filter_var($v['mail_from_email'], FILTER_VALIDATE_EMAIL)) {
            $this->backAdmin('mesajlar/ayarlar', 'Gönderen e-posta adresi geçersiz.', ['mail_from_email' => 'Geçerli bir e-posta girin.']);
        }
        if ($v['smtp_host'] === '') {
            $this->backAdmin('mesajlar/ayarlar', 'SMTP sunucusu zorunludur.', ['smtp_host' => 'Örn: smtp.gmail.com']);
        }
        return $v;
    }

    private function findOrFail(int $id): Message
    {
        $message = Message::find($id);
        if (!$message) {
            $this->respond('Mesaj bulunamadı.', admin_url('mesajlar'), [], 'danger');
        }
        return $message;
    }
}
