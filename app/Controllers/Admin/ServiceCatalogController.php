<?php

namespace App\Controllers\Admin;

use App\Models\Setting;
use App\Support\ServiceCatalog;

/**
 * Teklif formu ve hizmetler düzenleyicisi.
 * Hizmet sayfaları (/hizmetler/...), paketler, bütçe / süre seçenekleri, SSS ve çalışma süreci
 * buradan düzenlenir ve `service_catalog` ayarına JSON olarak kaydedilir.
 */
class ServiceCatalogController extends AdminController
{
    public function index(): void
    {
        $this->render('services/index', [
            'pageTitle'   => 'Teklif Formu & Hizmetler',
            'breadcrumbs' => ['Teklif Talepleri' => admin_url('teklifler'), 'Teklif Formu & Hizmetler' => null],
            'catalog'     => ServiceCatalog::data(),
            'icons'       => ServiceCatalog::icons(),
            'customized'  => Setting::get('service_catalog', '') !== '',
        ]);
    }

    public function update(): void
    {
        $r = $this->request;
        $lines = static fn ($v) => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $v) ?: []), 'strlen'));
        $csv = static fn ($v) => array_values(array_filter(array_map('trim', explode(',', (string) $v)), 'strlen'));
        $icons = ServiceCatalog::icons();
        $icon = static fn ($v, $d = 'check-circle') => in_array($v, $icons, true) ? $v : $d;
        $str = static fn ($v, $max) => mb_substr(trim((string) $v), 0, $max);
        $errors = [];

        // --- Form metinleri
        $form = (array) $r->post('form', []);
        $catalog = ['form' => [
            'title' => $str($form['title'] ?? '', 150) ?: 'Projenizi anlatın, teklifiniz gelsin',
            'lead'  => $str($form['lead'] ?? '', 500),
            'other' => !empty($form['other']),
        ]];

        // --- Seçenek listeleri
        $catalog['budgets'] = array_slice(array_map(fn ($l) => $str($l, 50), $lines($r->post('budgets'))), 0, 20);
        $catalog['deadlines'] = array_slice(array_map(fn ($l) => $str($l, 50), $lines($r->post('deadlines'))), 0, 20);
        if (!$catalog['budgets']) $errors['budgets'] = 'En az bir bütçe seçeneği yazın.';
        if (!$catalog['deadlines']) $errors['deadlines'] = 'En az bir süre seçeneği yazın.';

        // --- Hizmetler (anahtar ve adres sabit kalır: web, desktop)
        $current = ServiceCatalog::data()['services'];
        $catalog['services'] = [];
        foreach ($current as $key => $old) {
            $in = (array) (($r->post('services', []))[$key] ?? []);
            $s = [
                'slug'      => $old['slug'],
                'short'     => $str($in['short'] ?? '', 40),
                'title'     => $str($in['title'] ?? '', 80),
                'icon'      => $icon($in['icon'] ?? '', $old['icon']),
                'color'     => preg_match('/^#[0-9a-f]{6}$/i', (string) ($in['color'] ?? '')) ? $in['color'] : $old['color'],
                'tagline'   => $str($in['tagline'] ?? '', 120),
                'intro'     => $str($in['intro'] ?? '', 800),
                'tech'      => array_slice($csv($in['tech'] ?? ''), 0, 20),
                'languages' => array_slice($csv($in['languages'] ?? ''), 0, 20),
                'features'  => [],
                'packages'  => [],
            ];
            if ($s['short'] === '') $errors["services[$key][short]"] = 'Kısa ad zorunludur.';
            if ($s['title'] === '') $errors["services[$key][title]"] = 'Başlık zorunludur.';

            // Özellikler: "ikon | Başlık | Açıklama" satırları
            foreach (array_slice($lines($in['features'] ?? ''), 0, 12) as $line) {
                $p = array_map('trim', explode('|', $line));
                if (count($p) === 1) {
                    $p = ['check-circle', $p[0], ''];
                } elseif (count($p) === 2) {
                    $p = in_array($p[0], $icons, true) ? [$p[0], $p[1], ''] : ['check-circle', $p[0], $p[1]];
                }
                $s['features'][] = [$icon($p[0]), $str($p[1], 80), $str($p[2] ?? '', 200)];
            }

            // Paketler (eski talepler bozulmasın diye anahtar korunur)
            $used = [];
            foreach (array_slice((array) ($in['packages'] ?? []), 0, 6) as $pkg) {
                $name = $str($pkg['name'] ?? '', 60);
                if ($name === '') {
                    continue;
                }
                $pkey = preg_match('/^[a-z0-9-]{1,40}$/', (string) ($pkg['key'] ?? '')) ? $pkg['key'] : str_slug($name);
                while (isset($used[$pkey])) {
                    $pkey .= '-2';
                }
                $used[$pkey] = true;
                $s['packages'][$pkey] = [
                    'name'    => $name,
                    'price'   => $str($pkg['price'] ?? '', 60),
                    'desc'    => $str($pkg['desc'] ?? '', 300),
                    'items'   => array_slice(array_map(fn ($l) => $str($l, 100), $lines($pkg['items'] ?? '')), 0, 15),
                    'popular' => !empty($pkg['popular']),
                ];
            }
            $catalog['services'][$key] = $s;
        }

        // --- Çalışma süreci: "ikon | Başlık | Açıklama"
        $catalog['process'] = [];
        foreach (array_slice($lines($r->post('process')), 0, 10) as $line) {
            $p = array_map('trim', explode('|', $line));
            if (count($p) < 3) {
                array_unshift($p, 'check-circle');
            }
            $catalog['process'][] = [$icon($p[0]), $str($p[1] ?? '', 60), $str($p[2] ?? '', 200)];
        }

        // --- SSS
        $catalog['faq'] = [];
        foreach (array_slice((array) $r->post('faq', []), 0, 20) as $row) {
            $q = $str($row['q'] ?? '', 200);
            $a = $str($row['a'] ?? '', 1500);
            if ($q !== '' && $a !== '') {
                $catalog['faq'][] = [$q, $a];
            }
        }

        if ($errors) {
            $this->backAdmin('teklif-formu', 'Lütfen işaretli alanları kontrol edin.', $errors);
        }

        Setting::set('service_catalog', json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        ServiceCatalog::forget();
        $this->respond('Teklif formu ve hizmetler kaydedildi. Değişiklikler sitede hemen görünür.', admin_url('teklif-formu'));
    }

    public function reset(): void
    {
        Setting::set('service_catalog', '');
        ServiceCatalog::forget();
        $this->respond('Varsayılan içeriklere dönüldü.', admin_url('teklif-formu'));
    }
}
