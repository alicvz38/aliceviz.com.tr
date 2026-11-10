<?php

namespace App\Controllers\Admin;

use App\Core\Cache;
use App\Core\Session;
use App\Core\View;
use App\Models\Setting;
use App\Services\GithubService;
use App\Services\Uploader;
use RuntimeException;

/**
 * Site Ayarları — her bölüm ayrı sayfa ve ayrı formdur; bir bölüm kaydedilirken
 * yalnızca o bölümün ayarları değişir.
 */
class SettingController extends AdminController
{
    /** Bölümler: adres => [başlık, ikon, açıklama] (sol menü ve sekmeler buradan oluşur) */
    public const SECTIONS = [
        'genel'   => ['Genel', 'globe', 'Site adı, alan adı ve açıklama'],
        'iletisim'=> ['İletişim & WhatsApp', 'phone', 'E-posta, telefon ve WhatsApp'],
        'sosyal'  => ['Sosyal Medya', 'users', 'Sosyal medya hesapları'],
        'github'  => ['GitHub', 'github', 'Kullanıcı adı, erişim token\'ı ve önbellek'],
        'gorunum' => ['Görünüm', 'image', 'Logo, favicon, tema ve renkli ikonlar'],
        'seo'     => ['SEO & Google', 'search', 'Google\'da görünüm, doğrulama kodları ve paylaşım görseli'],
        'reklam'  => ['Reklam (AdSense)', 'dollar-sign', 'Google AdSense reklamları ve ads.txt'],
        'bakim'   => ['Bakım Modu', 'tool', 'Siteyi bakıma alma ve bakım mesajı'],
    ];

    private const SOCIAL = [
        'social_instagram' => 'Instagram',
        'social_facebook'  => 'Facebook',
        'social_x'         => 'X (Twitter)',
        'social_youtube'   => 'YouTube',
        'social_linkedin'  => 'LinkedIn',
    ];

    public function index(string $section = 'genel'): void
    {
        $section = $this->section($section);
        [$label] = self::SECTIONS[$section];

        $this->render('settings/' . $section, [
            'pageTitle'   => 'Site Ayarları · ' . $label,
            'breadcrumbs' => ['Site Ayarları' => admin_url('ayarlar'), $label => null],
            'section'     => $section,
            'sections'    => self::SECTIONS,
            'settings'    => Setting::allSettings(),
            'themes'      => View::availableThemes(),
            'hasToken'    => Setting::get('github_token') !== '',
            'socials'     => self::SOCIAL,
        ]);
    }

    public function update(string $section = 'genel'): void
    {
        $section = $this->section($section);
        $back = 'ayarlar/' . $section;

        match ($section) {
            'genel'    => $this->saveGeneral($back),
            'iletisim' => $this->saveContact($back),
            'sosyal'   => $this->saveSocial($back),
            'github'   => $this->saveGithub($back),
            'gorunum'  => $this->saveAppearance($back),
            'seo'      => $this->saveSeo($back),
            'reklam'   => $this->saveAds($back),
            'bakim'    => $this->saveMaintenance(),
        };

        Session::flash('success', self::SECTIONS[$section][0] . ' ayarları kaydedildi.');
        $this->redirectAdmin($back);
    }

    public function clearCache(): void
    {
        $count = (new Cache())->clear();
        Session::flash('success', 'Önbellek temizlendi (' . $count . ' dosya).');
        $this->redirectAdmin('ayarlar/github');
    }

    /** Bilinmeyen bölüm adresi panel 404 sayfasına düşer */
    private function section(string $section): string
    {
        if (!isset(self::SECTIONS[$section])) {
            (new ErrorController($this->request))->notFound();
            exit;
        }
        return $section;
    }

    private function text(string $key, int $max = 2000): string
    {
        return trim(mb_substr((string) $this->request->post($key, ''), 0, $max));
    }

    private function flag(string $key): string
    {
        return $this->request->post($key) ? '1' : '0';
    }

    // ---------------------------------------------------------------- Bölümler

    private function saveGeneral(string $back): void
    {
        $values = [
            'site_title'       => $this->text('site_title', 150),
            'site_domain'      => $this->text('site_domain', 150),
            'site_description' => $this->text('site_description', 300),
        ];
        if ($values['site_title'] === '') {
            $this->backAdmin($back, 'Site başlığı boş bırakılamaz.', ['site_title' => 'Bu alan zorunludur.']);
        }
        Setting::setMany($values);
    }

    private function saveContact(string $back): void
    {
        $email = $this->text('contact_email', 190);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backAdmin($back, 'Geçerli bir e-posta adresi girin.', ['contact_email' => 'Geçerli bir e-posta adresi girin.']);
        }
        // Telefon (özgeçmişteki telefonla aynı ayar: cv_phone)
        $phone = phone_normalize((string) $this->request->post('cv_phone', ''));
        if ($phone === false) {
            $this->backAdmin($back, 'Telefon numarası geçersiz.', ['cv_phone' => '0 (5XX) XXX XX XX biçiminde eksiksiz girin.']);
        }
        Setting::setMany([
            'contact_email'    => $email,
            'cv_phone'         => (string) $phone,
            'whatsapp_enabled' => $this->flag('whatsapp_enabled'),
            'whatsapp_message' => $this->text('whatsapp_message', 500),
        ]);
    }

    private function saveSocial(string $back): void
    {
        $values = [];
        foreach (array_keys(self::SOCIAL) as $key) {
            $values[$key] = $this->text($key, 300);
            if ($values[$key] !== '' && !preg_match('#^https?://#i', $values[$key])) {
                $this->backAdmin($back, 'Sosyal medya bağlantıları https:// ile başlamalıdır.', [$key => 'https:// ile başlamalıdır.']);
            }
        }
        Setting::setMany($values);
    }

    private function saveGithub(string $back): void
    {
        $username = $this->text('github_username', 39);
        if ($username !== '' && !GithubService::validUsername($username)) {
            $this->backAdmin($back, 'GitHub kullanıcı adı geçersiz.', ['github_username' => 'Geçersiz kullanıcı adı.']);
        }
        Setting::set('github_username', $username);

        // Token yalnızca yeni değer girilirse değişir; "sil" işaretlenirse kaldırılır
        $token = (string) $this->request->post('github_token', '');
        if ($this->request->post('remove_token')) {
            Setting::set('github_token', '');
        } elseif ($token !== '') {
            Setting::set('github_token', $token);
        }
    }

    private function saveAppearance(string $back): void
    {
        $theme = (string) $this->request->post('site_theme', 'default');
        if (!array_key_exists($theme, View::availableThemes())) {
            $theme = 'default';
        }

        // Favicon: yeni dosya yüklenirse değişir, "varsayılan logoya dön" işaretlenirse kaldırılır
        $uploader = new Uploader();
        $favicon = (string) Setting::get('site_favicon', '');
        if ($this->request->post('remove_favicon')) {
            $uploader->deleteSiteFile($favicon);
            $favicon = '';
        }
        if ($file = $this->request->file('favicon')) {
            try {
                $new = $uploader->favicon($file);
                $uploader->deleteSiteFile($favicon);
                $favicon = $new;
            } catch (RuntimeException $e) {
                $this->backAdmin($back, $e->getMessage(), ['favicon' => $e->getMessage()]);
            }
        }

        // Logo: favicon ile aynı mantık
        $logo = (string) Setting::get('site_logo', '');
        if ($this->request->post('remove_logo')) {
            $uploader->deleteSiteFile($logo);
            $logo = '';
        }
        if ($file = $this->request->file('logo')) {
            try {
                $new = $uploader->logo($file);
                $uploader->deleteSiteFile($logo);
                $logo = $new;
            } catch (RuntimeException $e) {
                $this->backAdmin($back, $e->getMessage(), ['logo' => $e->getMessage()]);
            }
        }

        Setting::setMany([
            'site_theme'     => $theme,
            'colorful_icons' => $this->flag('colorful_icons'),
            'site_favicon'   => $favicon,
            'site_logo'      => $logo,
            'logo_show_text' => $this->flag('logo_show_text'),
        ]);
    }

    private function saveSeo(string $back): void
    {
        // Doğrulama kodu: tüm <meta ...> etiketi yapıştırılırsa içinden content değeri alınır
        $code = static function (string $raw): string {
            if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $raw, $m)) {
                $raw = $m[1];
            }
            return preg_replace('/[^A-Za-z0-9_\-]/', '', $raw);
        };

        $uploader = new Uploader();
        $og = (string) Setting::get('seo_og_image', '');
        if ($this->request->post('remove_og_image')) {
            $uploader->deleteSiteFile($og);
            $og = '';
        }
        if ($file = $this->request->file('og_image')) {
            try {
                $new = $uploader->shareImage($file);
                $uploader->deleteSiteFile($og);
                $og = $new;
            } catch (RuntimeException $e) {
                $this->backAdmin($back, $e->getMessage(), ['og_image' => $e->getMessage()]);
            }
        }

        Setting::setMany([
            'seo_home_title'          => $this->text('seo_home_title', 70),
            'seo_home_description'    => $this->text('seo_home_description', 170),
            'seo_google_verification' => $code($this->text('seo_google_verification', 300)),
            'seo_bing_verification'   => $code($this->text('seo_bing_verification', 300)),
            'seo_og_image'            => $og,
        ]);
    }

    private function saveAds(string $back): void
    {
        // AdSense'in verdiği <script> / <meta> kodunun tamamı veya yalnızca kimlik yapıştırılabilir
        $raw = $this->text('adsense_client', 2000);
        $client = '';
        if ($raw !== '') {
            if (!preg_match('/(?:ca-)?pub-(\d{10,20})/', $raw, $m)) {
                $this->backAdmin($back, 'Yayıncı kimliği bulunamadı. "ca-pub-" ile başlayan kimliği veya AdSense kodunu yapıştırın.', ['adsense_client' => 'Geçersiz yayıncı kimliği.']);
            }
            $client = 'ca-pub-' . $m[1];
        }
        $enabled = $this->flag('adsense_enabled');
        if ($enabled === '1' && $client === '') {
            $this->backAdmin($back, 'Reklamları açmak için önce yayıncı kimliğini girin.', ['adsense_client' => 'Zorunlu.']);
        }
        Setting::setMany([
            'adsense_client'  => $client,
            'adsense_enabled' => $enabled,
        ]);
    }

    private function saveMaintenance(): void
    {
        Setting::setMany([
            'maintenance_mode'    => $this->flag('maintenance_mode'),
            'maintenance_message' => $this->text('maintenance_message', 1000),
        ]);
    }
}
