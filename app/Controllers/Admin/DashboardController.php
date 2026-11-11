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

        $last = (int) Setting::get('imap_last_sync', '0');
        $this->json([
            'ok'           => true,
            'messages'     => Message::countUnread(),
            'quotes'       => QuoteRequest::countNew(),
            'chats'        => \App\Models\ChatConversation::countUnread(),
            // Mesaj tablosunda değişiklik olduysa (yeni / silinen e-posta) Mesajlar sayfası listeyi yeniler
            'mail_version' => (string) \App\Core\Database::getInstance()->fetchColumn("SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(id), 0), '-', COALESCE(SUM(is_read), 0)) FROM messages"),
            'synced_ago'   => $last ? time_ago(date('Y-m-d H:i:s', $last)) : null,
            'progress'     => \App\Services\MailFetcher::progress(),
            'stats_version' => \App\Support\Stats::version(),
        ]);
    }
}
