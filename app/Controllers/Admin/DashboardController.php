<?php

namespace App\Controllers\Admin;

use App\Models\Message;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Setting;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $this->render('dashboard', [
            'pageTitle' => 'Gösterge Paneli',
            'stats' => [
                'total'     => Project::count(),
                'published' => Project::count('is_published = 1'),
                'featured'  => Project::count('is_featured = 1'),
                'stars'     => Project::totalStars(),
                'github'    => Project::count('github_repo IS NOT NULL'),
                'unread'    => Message::countUnread(),
                'newQuotes' => QuoteRequest::countNew(),
            ],
            'latest'       => Project::where('1', [], 'id DESC', 6),
            'languages'    => Project::languages(),
            'latestQuotes' => QuoteRequest::where('1', [], 'id DESC', 5),
            'latestMessages' => Message::where("folder = 'inbox'", [], 'id DESC', 5),
        ]);
    }

    /** Bakım modunu aç / kapat (AJAX destekli) */
    public function toggleMaintenance(): void
    {
        $on = !Setting::isMaintenance();
        Setting::set('maintenance_mode', $on ? '1' : '0');
        $this->respond(
            $on
                ? 'Bakım modu AÇILDI. Ziyaretçiler bakım sayfasını görecek. Siz giriş yaptığınız için siteyi normal görürsünüz (üstte uyarı çubuğuyla).'
                : 'Bakım modu KAPATILDI. Site yayında.',
            admin_url(),
            ['maintenance' => $on],
            $on ? 'warning' : 'success'
        );
    }

    /** Menü rozetleri için okunmamış sayıları (AJAX ile periyodik sorgulanır) */
    public function counters(): void
    {
        // Yedek: arka plan görevi (cron) çalışmıyorsa panel açıkken dakikada bir senkronize et
        \App\Services\MailFetcher::syncIfDue(60, 15);

        // Panel açık = canlı destek için çevrimiçi
        \App\Services\LiveChat::touchAdmin();

        $this->json(['ok' => true] + self::snapshot());
    }

    /**
     * Anlık bildirim (WebSocket sunucusu olmayan paylaşımlı hostingde): uzun bekleme (long polling).
     * Panel bu isteği açık tutar; sunucu Gelen Kutusu'nu IMAP IDLE ile dinler ve saniyede bir
     * veritabanını (site formu, teklif, canlı destek, istatistik) kontrol eder. Bir değişiklik olunca
     * hemen yanıt verir, panel de bağlantıyı yeniden açar. Değişiklik yoksa ~25-50 sn sonra boş döner.
     *   ?sig=...  panelin bildiği son durum (farklıysa beklemeden döner)
     */
    public function wait(): void
    {
        // Oturum dosyası kilitli kalmasın: beklerken diğer panel istekleri engellenmez
        session_write_close();
        ignore_user_abort(false);
        // Bekleme süresi: zaman sınırı uzatılabiliyorsa 50 sn, değilse sunucu sınırının biraz altı
        $max = (int) ini_get('max_execution_time');
        $limit = @set_time_limit(75) || $max <= 0 ? 50 : max(5, min(50, $max - 8));

        $sig = (string) $this->request->get('sig', '');
        $snap = self::snapshot();
        if ($sig === '' || $sig !== $snap['sig']) {
            $this->json(['ok' => true, 'waited' => 0] + $snap);
        }

        \App\Services\LiveChat::touchAdmin();
        $imap = null;
        $newMail = false;
        if (\App\Services\MailFetcher::isEnabled()) {
            try {
                // Önceki bekleme bittikten sonra (bağlantılar arası boşlukta) gelen e-posta varsa hemen alınır
                $imap = \App\Services\MailFetcher::openIdle($newMail);
            } catch (\Throwable) {
                $imap = null; // IMAP'e bağlanılamazsa yalnızca veritabanı izlenir
            }
        }

        $started = microtime(true);
        $nextDb = 0.0;
        try {
            while (!$newMail && microtime(true) - $started < $limit) {
                // 1) Gmail / posta sunucusu: yeni e-posta bildirimi (en fazla 1 sn bekler)
                if ($imap) {
                    $read = [$imap->socket()];
                    $w = $e = null;
                    if (@stream_select($read, $w, $e, 1, 0) > 0) {
                        foreach ($imap->idleRead() as $line) {
                            if (preg_match('/^\* \d+ (EXISTS|EXPUNGE)/', $line)) {
                                $newMail = true;
                            }
                        }
                    }
                } else {
                    usleep(1_000_000);
                }
                if ($newMail || connection_aborted()) {
                    break;
                }
                // 2) Veritabanı: site formu, teklif, canlı destek, istatistik, başka sekmedeki işlemler
                if (microtime(true) >= $nextDb) {
                    $nextDb = microtime(true) + 1;
                    Setting::refresh();
                    if (self::signature() !== $sig) {
                        break;
                    }
                }
            }
            if ($newMail && $imap) {
                (new \App\Services\MailFetcher())->importNewInbox($imap, 15);
                $newMail = false; // alındı
            }
        } catch (\Throwable) {
            // IMAP bağlantısı koptu: o ana kadarki durumla dön, panel yeniden bağlanır
        } finally {
            if ($imap) {
                try {
                    $imap->logout();
                } catch (\Throwable) {
                }
            }
        }

        if ($newMail) {
            try {
                (new \App\Services\MailFetcher())->syncInbox(15);
            } catch (\Throwable $e) {
                Setting::set('imap_last_error', mb_substr($e->getMessage(), 0, 300));
            }
        }
        $this->json(['ok' => true, 'waited' => round(microtime(true) - $started, 1)] + self::snapshot());
    }

    /** Rozet sayıları ve değişiklik sürümleri */
    private static function snapshot(): array
    {
        $last = (int) Setting::get('imap_last_sync', '0');
        $data = [
            'messages'      => Message::countUnread(),
            'quotes'        => QuoteRequest::countNew(),
            'chats'         => \App\Models\ChatConversation::countUnread(),
            // Mesaj tablosunda değişiklik olduysa (yeni / silinen e-posta) Mesajlar sayfası listeyi yeniler
            'mail_version'  => self::mailVersion(),
            'synced_ago'    => $last ? time_ago(date('Y-m-d H:i:s', $last)) : null,
            'progress'      => \App\Services\MailFetcher::progress(),
            'stats_version' => \App\Support\Stats::version(),
        ];
        $data['sig'] = self::signature($data);
        return $data;
    }

    /** Değişiklik imzası: bunlardan biri değişince bekleyen istek hemen döner */
    private static function signature(?array $d = null): string
    {
        $d ??= [
            'messages'      => Message::countUnread(),
            'quotes'        => QuoteRequest::countNew(),
            'chats'         => \App\Models\ChatConversation::countUnread(),
            'mail_version'  => self::mailVersion(),
            'stats_version' => \App\Support\Stats::version(),
        ];
        return md5(implode('|', [$d['messages'], $d['quotes'], $d['chats'], $d['mail_version'], $d['stats_version']]));
    }

    private static function mailVersion(): string
    {
        return (string) \App\Core\Database::getInstance()->fetchColumn("SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(id), 0), '-', COALESCE(SUM(is_read), 0)) FROM messages");
    }
}
