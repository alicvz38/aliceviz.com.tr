<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Models\Message;
use App\Models\QuoteRequest;
use App\Models\Setting;

/**
 * Panel denetleyicilerinin tabanı. Equation (vertical-dark-menu) temasını kullanır.
 */
abstract class AdminController extends Controller
{
    protected function theme(): string
    {
        return (string) Config::get('theme.admin', 'admin');
    }

    protected function boot(): void
    {
        // Takvim / etkinlik günlüğü: bu istekteki işlem başarılı olursa otomatik kaydedilir
        \App\Support\ActivityLog::prepare($this->request);
        $this->view->share('authUser', Auth::user());
        $this->view->share('siteTitle', setting('site_title', Config::get('app.name')));
        $this->view->share('maintenance', Setting::isMaintenance());
        $this->view->share('menu', $this->menu());
        $this->view->share('breadcrumbs', []);
    }

    /**
     * Sol menü. "path" panel önekinden sonraki yoldur.
     * Yeni bir menü eklemek için bu diziye bir satır eklemeniz yeterlidir.
     */
    protected function menu(): array
    {
        $unread = Message::countUnread();
        $newQuotes = QuoteRequest::countNew();

        return [
            ['heading' => 'GENEL'],
            ['label' => 'Gösterge Paneli', 'icon' => 'home', 'path' => '', 'exact' => true],
            ['label' => 'Takvim', 'icon' => 'calendar', 'path' => 'takvim'],
            ['label' => 'İstatistikler', 'icon' => 'bar-chart-2', 'path' => 'istatistikler'],

            ['heading' => 'MÜŞTERİLER'],
            ['label' => 'Canlı Destek', 'icon' => 'message-square', 'path' => 'canli-destek', 'badge' => \App\Models\ChatConversation::countUnread(), 'badge_id' => 'badge-chat'],
            ['label' => 'Mesajlar', 'icon' => 'mail', 'path' => 'mesajlar', 'badge' => $unread, 'badge_id' => 'badge-messages', 'children' => [
                ['label' => 'Gelen Kutusu', 'path' => 'mesajlar', 'exact' => true],
                ['label' => 'Yeni E-posta', 'path' => 'mesajlar/yeni', 'exact' => true],
                ['label' => 'E-posta Ayarları', 'path' => 'mesajlar/ayarlar', 'exact' => true],
            ]],
            ['label' => 'Teklif Talepleri', 'icon' => 'inbox', 'path' => 'teklifler', 'badge' => $newQuotes, 'badge_id' => 'badge-quotes', 'children' => [
                ['label' => 'Tüm Talepler', 'path' => 'teklifler', 'exact' => true],
                ['label' => 'Teklif Formu & Hizmetler', 'path' => 'teklif-formu', 'exact' => true],
            ]],

            ['heading' => 'İÇERİK'],
            ['label' => 'Projeler', 'icon' => 'folder', 'path' => 'projeler', 'children' => [
                ['label' => 'Tüm Projeler', 'path' => 'projeler', 'exact' => true],
                ['label' => 'Yeni Proje', 'path' => 'projeler/ekle', 'exact' => true],
            ]],
            ['label' => 'GitHub', 'icon' => 'github', 'path' => 'github'],
            ['label' => 'Özgeçmiş (CV)', 'icon' => 'briefcase', 'path' => 'ozgecmis'],

            ['heading' => 'SİSTEM'],
            ['label' => 'Site Ayarları', 'icon' => 'settings', 'path' => 'ayarlar', 'children' => array_map(
                static fn (string $key, array $sec) => ['label' => $sec[0], 'path' => $key === 'genel' ? 'ayarlar' : 'ayarlar/' . $key, 'exact' => true],
                array_keys(SettingController::SECTIONS),
                SettingController::SECTIONS
            )],
            ['label' => 'Hesabım', 'icon' => 'user', 'path' => 'hesabim'],
            ['label' => 'Siteyi Görüntüle', 'icon' => 'external-link', 'url' => url(), 'target' => '_blank'],
        ];
    }

    protected function adminPath(string $path = ''): string
    {
        return trim(Config::get('admin.prefix', 'admin') . '/' . $path, '/');
    }

    protected function redirectAdmin(string $path = ''): never
    {
        redirect(admin_url($path));
    }

    protected function backAdmin(string $path, string $error, array $errors = []): never
    {
        $this->back($this->adminPath($path), $error, $errors);
    }
}
