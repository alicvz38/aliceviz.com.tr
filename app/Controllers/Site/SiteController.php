<?php

namespace App\Controllers\Site;

use App\Core\Config;
use App\Core\Controller;
use App\Support\ServiceCatalog;
use App\Support\Stats;

/**
 * Ziyaretçi tarafı denetleyicilerinin tabanı.
 * Aktif tema panelden (Ayarlar > Tema) değiştirilebilir.
 */
abstract class SiteController extends Controller
{
    protected function theme(): string
    {
        $theme = (string) setting('site_theme', Config::get('theme.site', 'default'));
        return is_dir(THEME_PATH . '/' . $theme) && $theme !== 'admin' ? $theme : (string) Config::get('theme.site', 'default');
    }

    protected function boot(): void
    {
        $this->view->share('site', [
            'title'       => setting('site_title', Config::get('app.name')),
            'domain'      => setting('site_domain', 'aliceviz.com.tr'),
            'description' => setting('site_description', ''),
            'email'       => setting('contact_email', ''),
            'github'      => setting('github_username', ''),
            'about'       => setting('about_text', ''),
            'job'         => setting('cv_title', ''),
            'location'    => setting('cv_location', ''),
            'phone'       => setting('cv_phone', ''),
            // Telefon WhatsApp'a bağlıysa hazır mesajlı sohbet bağlantısı (panel > Site Ayarları)
            'whatsapp'    => setting('whatsapp_enabled', '1') === '1'
                ? whatsapp_href(setting('cv_phone', ''), setting('whatsapp_message', ''))
                : '',
            'available'   => setting('cv_available', '1') === '1',
            'avatar'      => profile_photo(), // panel ve özgeçmişle aynı fotoğraf (değişince önbellek yenilenir)
            'social'      => [
                'github'    => setting('github_username') ? 'https://github.com/' . setting('github_username') : '',
                'instagram' => setting('social_instagram', ''),
                'facebook'  => setting('social_facebook', ''),
                'x'         => setting('social_x', ''),
                'youtube'   => setting('social_youtube', ''),
                'linkedin'  => setting('social_linkedin', ''),
            ],
        ]);
        $this->view->share('services', ServiceCatalog::all());
        // Bakım modunda giriş yapmış yönetici siteyi görebilir; bunu ona belirten çubuk gösterilir
        $this->view->share('adminMaintenanceBar', \App\Core\Auth::check() && \App\Models\Setting::isMaintenance());
    }

    /** Her başarılı sayfa görüntülemesi istatistiğe yazılır (günlük tekil; AJAX, hata ve bakım sayfaları hariç) */
    protected function render(string $page, array $data = [], int $status = 200): void
    {
        if ($status === 200 && !\App\Core\Request::isAjax()) {
            Stats::page($this->request->path());
        }
        parent::render($page, $data, $status);
    }

    /** Aynı oturumdan çok sık form gönderimini engeller (saniye) */
    protected function throttle(string $key, int $seconds): ?int
    {
        $last = (int) ($_SESSION['_throttle'][$key] ?? 0);
        $wait = $last + $seconds - time();
        return $wait > 0 ? $wait : null;
    }

    protected function hit(string $key): void
    {
        $_SESSION['_throttle'][$key] = time();
    }
}
