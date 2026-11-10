<?php

namespace App\Controllers\Admin;

use App\Core\Cache;
use App\Core\Session;
use App\Core\View;
use App\Models\Setting;
use App\Services\GithubService;
use App\Services\Uploader;
use RuntimeException;

class SettingController extends AdminController
{
    /** Formdan kabul edilen ayar anahtarları */
    private const KEYS = [
        'site_title', 'site_domain', 'site_description', 'contact_email',
        'github_username', 'social_instagram', 'social_facebook', 'social_x', 'social_youtube', 'social_linkedin',
        'maintenance_message', 'site_theme', 'whatsapp_message',
    ];

    public function index(): void
    {
        $this->render('settings/index', [
            'pageTitle'   => 'Site Ayarları',
            'breadcrumbs' => ['Site Ayarları' => null],
            'settings'    => Setting::allSettings(),
            'themes'      => View::availableThemes(),
            'hasToken'    => Setting::get('github_token') !== '',
        ]);
    }

    public function update(): void
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = mb_substr((string) $this->request->post($key, ''), 0, 2000);
        }

        if ($values['site_title'] === '') {
            $this->backAdmin('ayarlar', 'Site başlığı boş bırakılamaz.');
        }
        if ($values['contact_email'] !== '' && !filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $this->backAdmin('ayarlar', 'Geçerli bir e-posta adresi girin.');
        }
        // Telefon (özgeçmişteki telefonla aynı ayar: cv_phone)
        $phone = phone_normalize((string) $this->request->post('cv_phone', ''));
        if ($phone === false) {
            $this->backAdmin('ayarlar', 'Telefon numarası geçersiz.', ['cv_phone' => '0 (5XX) XXX XX XX biçiminde eksiksiz girin.']);
        }
        $values['cv_phone'] = (string) $phone;
        $values['whatsapp_message'] = trim(mb_substr($values['whatsapp_message'], 0, 500));
        if ($values['github_username'] !== '' && !GithubService::validUsername($values['github_username'])) {
            $this->backAdmin('ayarlar', 'GitHub kullanıcı adı geçersiz.');
        }
        foreach (['social_instagram', 'social_facebook', 'social_x', 'social_youtube', 'social_linkedin'] as $key) {
            if ($values[$key] !== '' && !preg_match('#^https?://#i', $values[$key])) {
                $this->backAdmin('ayarlar', 'Sosyal medya bağlantıları https:// ile başlamalıdır.');
            }
        }
        if (!array_key_exists($values['site_theme'], View::availableThemes())) {
            $values['site_theme'] = 'default';
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
                $this->backAdmin('ayarlar', $e->getMessage(), ['favicon' => $e->getMessage()]);
            }
        }
        Setting::set('site_favicon', $favicon);

        Setting::setMany($values);
        Setting::set('whatsapp_enabled', $this->request->post('whatsapp_enabled') ? '1' : '0');
        Setting::set('colorful_icons', $this->request->post('colorful_icons') ? '1' : '0');
        Setting::set('maintenance_mode', $this->request->post('maintenance_mode') ? '1' : '0');

        // Token yalnızca yeni değer girilirse değişir; "sil" işaretlenirse kaldırılır
        $token = (string) $this->request->post('github_token', '');
        if ($this->request->post('remove_token')) {
            Setting::set('github_token', '');
        } elseif ($token !== '') {
            Setting::set('github_token', $token);
        }

        Session::flash('success', 'Ayarlar kaydedildi.');
        $this->redirectAdmin('ayarlar');
    }

    public function clearCache(): void
    {
        $count = (new Cache())->clear();
        Session::flash('success', 'Önbellek temizlendi (' . $count . ' dosya).');
        $this->redirectAdmin('ayarlar');
    }
}
