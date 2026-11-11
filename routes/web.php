<?php
/**
 * Rota tanımları
 * @var App\Core\Router $router
 *
 * POST rotaları otomatik olarak CSRF korumasından geçer.
 * Tüm POST uç noktaları hem klasik form hem AJAX (assets/js/ajax.js) ile çalışır.
 */

use App\Controllers\Admin;
use App\Controllers\Site;
use App\Core\Config;

// Arama motorları (veritabanından otomatik)
$router->get('robots.txt', [Site\SeoController::class, 'robots']);
$router->get('sitemap.xml', [Site\SeoController::class, 'sitemap']);
$router->get('ads.txt', [Site\SeoController::class, 'adsTxt']);
// E-postadaki büyük dosyaların indirme bağlantısı (30 gün geçerli)
$router->get('dosya/{token}', [Site\FileController::class, 'download']);

// ------------------------------------------------------------
// ZİYARETÇİ TARAFI (bakım modu kontrolünden geçer)
// ------------------------------------------------------------
$router->group('', ['maintenance'], function ($r) {
    $r->get('', [Site\HomeController::class, 'index']);
    $r->get('projeler', [Site\ProjectController::class, 'index']);
    $r->get('projeler/{slug}', [Site\ProjectController::class, 'show']);
    $r->get('projeler/{slug}/tanitim', [Site\ProjectController::class, 'showcase']);

    $r->get('hakkimda', [Site\AboutController::class, 'index']);
    $r->get('cerez-politikasi', [Site\PageController::class, 'cookies']);

    $r->get('hizmetler', [Site\ServiceController::class, 'index']);
    $r->get('hizmetler/{slug}', [Site\ServiceController::class, 'show']);

    $r->get('teklif-al', [Site\QuoteController::class, 'index']);
    $r->post('teklif-al', [Site\QuoteController::class, 'store']);

    $r->get('iletisim', [Site\ContactController::class, 'index']);
    $r->post('iletisim', [Site\ContactController::class, 'store']);

    // Canlı destek (sağ alttaki sohbet penceresi)
    $r->get('destek/durum', [Site\LiveChatController::class, 'status']);
    $r->get('destek/mesajlar', [Site\LiveChatController::class, 'poll']);
    $r->post('destek/baslat', [Site\LiveChatController::class, 'start']);
    $r->post('destek/gonder', [Site\LiveChatController::class, 'send']);
    $r->post('destek/yaziyor', [Site\LiveChatController::class, 'typing']);
    $r->post('destek/bitir', [Site\LiveChatController::class, 'end']);
    $r->post('destek/degerlendir', [Site\LiveChatController::class, 'rate']);
    $r->post('destek/yeni', [Site\LiveChatController::class, 'newChat']);
});

// ------------------------------------------------------------
// YÖNETİM PANELİ
// ------------------------------------------------------------
$admin = Config::get('admin.prefix', 'admin');

$router->group($admin, ['guest'], function ($r) {
    $r->get('giris', [Admin\AuthController::class, 'loginForm']);
    $r->post('giris', [Admin\AuthController::class, 'login']);
});

$router->group($admin, ['auth'], function ($r) {
    $r->post('cikis', [Admin\AuthController::class, 'logout']);

    $r->get('', [Admin\DashboardController::class, 'index']);
    $r->get('takvim', [Admin\CalendarController::class, 'index']);
    $r->get('istatistikler', [Admin\StatsController::class, 'index']);
    $r->get('istatistikler/veri', [Admin\StatsController::class, 'data']);
    $r->get('istatistikler/surum', [Admin\StatsController::class, 'version']);
    $r->get('takvim/olaylar', [Admin\CalendarController::class, 'events']);
    $r->get('takvim/esitle', [Admin\CalendarController::class, 'sync']);
    $r->post('bakim-modu', [Admin\DashboardController::class, 'toggleMaintenance']);
    $r->get('bildirimler', [Admin\DashboardController::class, 'counters']);
    $r->get('bekle', [Admin\DashboardController::class, 'wait']); // anlık bildirim (uzun bekleme)

    $r->get('projeler', [Admin\ProjectController::class, 'index']);
    $r->get('projeler/ekle', [Admin\ProjectController::class, 'create']);
    $r->post('projeler/kaydet', [Admin\ProjectController::class, 'store']);
    $r->get('projeler/{id}/duzenle', [Admin\ProjectController::class, 'edit']);
    $r->post('projeler/{id}/guncelle', [Admin\ProjectController::class, 'update']);
    // Proje tanıtım görselleri (sitede /projeler/{slug}/tanitim)
    $r->get('projeler/{id}/tanitim', [Admin\ProjectShotController::class, 'index']);
    $r->post('projeler/{id}/tanitim/yukle', [Admin\ProjectShotController::class, 'upload']);
    $r->post('projeler/{id}/tanitim/sirala', [Admin\ProjectShotController::class, 'reorder']);
    $r->post('projeler/{id}/tanitim/{shot}/guncelle', [Admin\ProjectShotController::class, 'update']);
    $r->post('projeler/{id}/tanitim/{shot}/sil', [Admin\ProjectShotController::class, 'destroy']);
    $r->post('projeler/{id}/sil', [Admin\ProjectController::class, 'destroy']);
    $r->post('projeler/{id}/durum', [Admin\ProjectController::class, 'toggle']);
    $r->post('projeler/{id}/tur', [Admin\ProjectController::class, 'setKind']);
    $r->post('projeler/{id}/senkronize', [Admin\ProjectController::class, 'sync']);

    $r->get('github', [Admin\GithubController::class, 'index']);
    $r->post('github/ice-aktar', [Admin\GithubController::class, 'import']);
    $r->post('github/senkronize', [Admin\GithubController::class, 'syncAll']);

    // Özgeçmiş (CV)
    $r->get('ozgecmis', [Admin\ResumeController::class, 'index']);
    $r->post('ozgecmis/profil', [Admin\ResumeController::class, 'profile']);
    $r->post('ozgecmis/kaydet', [Admin\ResumeController::class, 'save']);
    $r->post('ozgecmis/sirala', [Admin\ResumeController::class, 'reorder']);
    $r->post('ozgecmis/{id}/sil', [Admin\ResumeController::class, 'destroy']);

    // Teklif talepleri
    $r->get('teklifler', [Admin\QuoteController::class, 'index']);
    $r->get('teklifler/{id}', [Admin\QuoteController::class, 'show']);
    $r->post('teklifler/{id}/guncelle', [Admin\QuoteController::class, 'update']);
    $r->post('teklifler/{id}/sil', [Admin\QuoteController::class, 'destroy']);

    // Canlı destek
    $r->get('canli-destek', [Admin\LiveChatController::class, 'index']);
    $r->get('canli-destek/liste', [Admin\LiveChatController::class, 'list']);
    $r->post('canli-destek/ayarlar', [Admin\LiveChatController::class, 'settings']);
    $r->get('canli-destek/{id}/mesajlar', [Admin\LiveChatController::class, 'messages']);
    $r->post('canli-destek/{id}/gonder', [Admin\LiveChatController::class, 'send']);
    $r->post('canli-destek/{id}/yaziyor', [Admin\LiveChatController::class, 'typing']);
    $r->post('canli-destek/{id}/kapat', [Admin\LiveChatController::class, 'close']);
    $r->post('canli-destek/{id}/sil', [Admin\LiveChatController::class, 'destroy']);

    // Teklif formu & hizmetler düzenleyicisi
    $r->get('teklif-formu', [Admin\ServiceCatalogController::class, 'index']);
    $r->post('teklif-formu', [Admin\ServiceCatalogController::class, 'update']);
    $r->post('teklif-formu/sifirla', [Admin\ServiceCatalogController::class, 'reset']);

    // Mesajlar / e-posta
    $r->get('mesajlar', [Admin\MessageController::class, 'index']);
    $r->get('mesajlar/yeni', [Admin\MessageController::class, 'compose']);
    $r->get('mesajlar/veri', [Admin\MessageController::class, 'data']);
    $r->post('mesajlar/toplu', [Admin\MessageController::class, 'bulk']);
    $r->post('mesajlar/tumunu-oku', [Admin\MessageController::class, 'readAll']);
    $r->post('mesajlar/klasoru-bosalt', [Admin\MessageController::class, 'emptyFolder']);
    $r->post('mesajlar/gonder', [Admin\MessageController::class, 'send']);    $r->get('mesajlar/ayarlar', [Admin\MessageController::class, 'settings']);
    $r->post('mesajlar/ayarlar', [Admin\MessageController::class, 'saveSettings']);
    $r->post('mesajlar/ayarlar/test', [Admin\MessageController::class, 'testSettings']);
    $r->post('mesajlar/ek', [Admin\MessageController::class, 'uploadChunk']);
    $r->post('mesajlar/ek/{token}/sil', [Admin\MessageController::class, 'discardAttachment']);
    $r->get('mesajlar/{id}', [Admin\MessageController::class, 'show']);
    $r->post('mesajlar/{id}/yildiz', [Admin\MessageController::class, 'star']);
    $r->post('mesajlar/{id}/okundu', [Admin\MessageController::class, 'markRead']);
    $r->post('mesajlar/{id}/sil', [Admin\MessageController::class, 'destroy']);

    // Site Ayarları: her bölüm ayrı sayfa (genel, iletisim, sosyal, github, gorunum, bakim)
    $r->get('ayarlar', [Admin\SettingController::class, 'index']);
    $r->post('ayarlar', [Admin\SettingController::class, 'update']);
    $r->post('ayarlar/onbellek', [Admin\SettingController::class, 'clearCache']);
    $r->get('ayarlar/{bolum}', [Admin\SettingController::class, 'index']);
    $r->post('ayarlar/{bolum}', [Admin\SettingController::class, 'update']);

    $r->get('hesabim', [Admin\AccountController::class, 'index']);
    $r->post('hesabim', [Admin\AccountController::class, 'update']);
    $r->post('hesabim/fotograf', [Admin\AccountController::class, 'photo']);
    $r->post('hesabim/fotograf/sil', [Admin\AccountController::class, 'removePhoto']);
    $r->post('hesabim/cihazlar/{id}/sil', [Admin\AccountController::class, 'forgetDevice']);
    $r->post('hesabim/cihazlar/digerleri', [Admin\AccountController::class, 'forgetOthers']);
});
