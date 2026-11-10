<?php

namespace App\Support;

/**
 * Sunulan hizmetler. Hizmet sayfaları, paketler ve teklif formu bu listeden beslenir.
 *
 * Panel > Teklif Talepleri > "Formu ve hizmetleri düzenle" sayfasından düzenlenir;
 * düzenlemeler `service_catalog` ayarında JSON olarak saklanır. Aşağıdaki default*()
 * değerleri ilk kurulumda ve "Varsayılanlara dön" seçildiğinde kullanılır.
 * ('price' boş bırakılırsa "Projeye özel fiyat" yazar.)
 */
final class ServiceCatalog
{
    private static ?array $data = null;

    /** Panelde kaydedilen katalog (yoksa varsayılanlar) */
    public static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }
        $defaults = [
            'services'  => self::defaultServices(),
            'process'   => self::defaultProcess(),
            'faq'       => self::defaultFaq(),
            'budgets'   => self::defaultBudgets(),
            'deadlines' => self::defaultDeadlines(),
            'form'      => [
                'title' => 'Projenizi anlatın, teklifiniz gelsin',
                'lead'  => 'Formu doldurmanız 2 dakika sürer. Talebinizi inceleyip genellikle 24 saat içinde e-posta adresinize dönüş yaparım.',
                'other' => true,
            ],
        ];
        $stored = null;
        try {
            $json = (string) setting('service_catalog', '');
            $stored = $json !== '' ? json_decode($json, true) : null;
        } catch (\Throwable) {
        }
        if (is_array($stored)) {
            foreach ($defaults as $key => $value) {
                if (isset($stored[$key]) && is_array($stored[$key]) && ($stored[$key] || in_array($key, ['faq', 'process'], true))) {
                    $defaults[$key] = $key === 'form' ? array_merge($value, $stored[$key]) : $stored[$key];
                }
            }
        }
        return self::$data = $defaults;
    }

    public static function forget(): void
    {
        self::$data = null;
    }

    public static function all(): array
    {
        return self::data()['services'];
    }

    public static function process(): array
    {
        return self::data()['process'];
    }

    public static function faq(): array
    {
        return self::data()['faq'];
    }

    public static function budgets(): array
    {
        return self::data()['budgets'];
    }

    public static function deadlines(): array
    {
        return self::data()['deadlines'];
    }

    /** Teklif formu sayfası metinleri: title, lead, other (Diğer seçeneği gösterilsin mi) */
    public static function form(): array
    {
        return self::data()['form'];
    }

    /** Hizmet / özellik ikonları için seçilebilecek ikonlar */
    public static function icons(): array
    {
        return ['globe', 'monitor', 'smartphone', 'shopping-cart', 'code', 'cpu', 'database', 'layers', 'layout', 'settings', 'zap',
            'search', 'shield', 'life-buoy', 'file-text', 'users', 'download', 'message-square', 'pen-tool', 'check-circle',
            'briefcase', 'award', 'star', 'mail', 'tool', 'terminal', 'package', 'calendar', 'clock', 'send'];
    }

    public static function defaultServices(): array
    {
        return [
            'web' => [
                'slug'     => 'web-sitesi',
                'short'    => 'Web Sitesi',
                'title'    => 'Web Sitesi Yaptır',
                'icon'     => 'globe',
                'color'    => '#38bdf8',
                'tagline'  => 'Hızlı, mobil uyumlu ve yönetim panelli web siteleri',
                'intro'    => 'Kurumsal tanıtım sitesinden e-ticarete, randevu sisteminden size özel web uygulamalarına kadar; işletmenizi internette en iyi şekilde temsil edecek, kolayca yönetebileceğiniz siteler geliştiriyorum.',
                'features' => [
                    ['smartphone', 'Mobil uyumlu tasarım', 'Telefon, tablet ve bilgisayarda kusursuz görünüm.'],
                    ['settings', 'Kolay yönetim paneli', 'İçerikleri, görselleri ve sayfaları kod bilmeden güncelleyin.'],
                    ['zap', 'Yüksek performans', 'Hızlı açılan sayfalar, optimize edilmiş görseller.'],
                    ['search', 'SEO altyapısı', 'Arama motorlarında bulunabilirlik için doğru temel.'],
                    ['shield', 'Güvenlik', 'SSL, CSRF/XSS korumaları ve güvenli şifreleme.'],
                    ['life-buoy', 'Teslim sonrası destek', 'Kurulum, eğitim ve ücretsiz destek süresi.'],
                ],
                'packages' => [
                    'baslangic' => [
                        'name'     => 'Başlangıç',
                        'price'    => '',
                        'desc'     => 'Kişisel site, portföy veya tek sayfalık tanıtım siteleri için.',
                        'items'    => ['5 sayfaya kadar', 'Mobil uyumlu tasarım', 'İletişim formu', 'Temel SEO ayarları', 'Alan adı & hosting kurulumu', '1 ay ücretsiz destek'],
                    ],
                    'kurumsal' => [
                        'name'     => 'Kurumsal',
                        'price'    => '',
                        'popular'  => true,
                        'desc'     => 'Firmanızı tanıtan, panelden yönetilen kapsamlı kurumsal site.',
                        'items'    => ['Sınırsız sayfa', 'Yönetim paneli', 'Blog / haber / referanslar', 'Çoklu dil desteği', 'Gelişmiş SEO', '3 ay ücretsiz destek'],
                    ],
                    'ozel' => [
                        'name'     => 'E-Ticaret / Özel Yazılım',
                        'price'    => '',
                        'desc'     => 'Online satış, randevu, üyelik veya size özel web uygulamaları.',
                        'items'    => ['Ürün / sipariş yönetimi', 'Sanal POS entegrasyonu', 'Üyelik & müşteri paneli', 'Özel raporlar', 'API entegrasyonları', '6 ay ücretsiz destek'],
                    ],
                ],
                'tech'     => ['PHP', 'MySQL', 'JavaScript', 'HTML5', 'CSS3', 'Bootstrap', 'AJAX', 'REST API'],
                'languages'=> ['PHP', 'JavaScript', 'TypeScript', 'HTML', 'CSS', 'Vue', 'Blade'],
            ],
            'desktop' => [
                'slug'     => 'masaustu-uygulama',
                'short'    => 'Masaüstü Uygulama',
                'title'    => 'Masaüstü Uygulama Yaptır',
                'icon'     => 'monitor',
                'color'    => '#a855f7',
                'tagline'  => 'İşinizi kolaylaştıran Windows uygulamaları',
                'intro'    => 'Stok, cari, satış, randevu, personel takibi gibi işlerinizi Excel karmaşasından kurtaran; veritabanı destekli, kullanımı kolay ve hızlı masaüstü yazılımlar geliştiriyorum.',
                'features' => [
                    ['monitor', 'Kullanıcı dostu arayüz', 'Personelinizin hemen alışacağı sade ekranlar.'],
                    ['database', 'Veritabanı desteği', 'SQLite, MySQL veya SQL Server ile güvenli kayıt.'],
                    ['file-text', 'Rapor & Excel / PDF', 'Raporları yazdırın, Excel ve PDF olarak alın.'],
                    ['users', 'Çok kullanıcılı yapı', 'Yetki seviyeleri ve ağ üzerinden ortak kullanım.'],
                    ['download', 'Kolay kurulum', 'Kurulum dosyası ve otomatik güncelleme altyapısı.'],
                    ['life-buoy', 'Eğitim & destek', 'Kullanım eğitimi ve teslim sonrası destek.'],
                ],
                'packages' => [
                    'temel' => [
                        'name'    => 'Temel Uygulama',
                        'price'   => '',
                        'desc'    => 'Tek bilgisayarda çalışan, belirli bir işi çözen küçük uygulamalar.',
                        'items'   => ['Tek kullanıcı', 'Yerel veritabanı (SQLite)', '5 ekrana kadar', 'Excel dışa aktarma', 'Kurulum dosyası', '1 ay ücretsiz destek'],
                    ],
                    'isletme' => [
                        'name'    => 'İşletme Yazılımı',
                        'price'   => '',
                        'popular' => true,
                        'desc'    => 'Stok, cari, satış veya personel takibi gibi kapsamlı süreçler.',
                        'items'   => ['Çok kullanıcılı (ağ)', 'MySQL / SQL Server', 'Yetkilendirme', 'Raporlar & yazdırma', 'Yedekleme', '3 ay ücretsiz destek'],
                    ],
                    'entegre' => [
                        'name'    => 'Entegre Çözüm',
                        'price'   => '',
                        'desc'    => 'Web paneli, mobil veya üçüncü parti sistemlerle konuşan yazılımlar.',
                        'items'   => ['Web / API entegrasyonu', 'Barkod, yazıcı, terazi desteği', 'Otomatik güncelleme', 'Bulut senkronizasyonu', 'Özel modüller', '6 ay ücretsiz destek'],
                    ],
                ],
                'tech'     => ['C#', '.NET', 'WinForms', 'WPF', 'SQLite', 'SQL Server', 'MySQL', 'Python'],
                'languages'=> ['C#', 'C++', 'C', 'Python', 'Java', 'Kotlin', 'Go', 'Rust'],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        if ($key === 'other') {
            return ['short' => 'Diğer', 'packages' => []];
        }
        return self::all()[$key] ?? null;
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::all() as $key => $service) {
            if ($service['slug'] === $slug) {
                return ['key' => $key] + $service;
            }
        }
        return null;
    }

    /** Çalışma süreci (tüm hizmetler için ortak) */
    public static function defaultProcess(): array
    {
        return [
            ['message-square', 'Ön görüşme', 'İhtiyaçlarınızı dinliyor, hedeflerinizi netleştiriyoruz.'],
            ['file-text', 'Teklif & plan', 'Kapsam, süre ve fiyatı içeren net bir teklif sunuyorum.'],
            ['pen-tool', 'Tasarım', 'Arayüz taslaklarını onayınıza sunuyorum.'],
            ['code', 'Geliştirme', 'Düzenli ara teslimlerle ilerlemeyi birlikte takip ediyoruz.'],
            ['check-circle', 'Test & teslim', 'Kurulum, eğitim ve canlıya alma.'],
            ['life-buoy', 'Destek', 'Teslim sonrası destek ve bakım.'],
        ];
    }

    public static function defaultBudgets(): array
    {
        return ['Belirlemedim', '10.000 ₺ altı', '10.000 – 25.000 ₺', '25.000 – 50.000 ₺', '50.000 – 100.000 ₺', '100.000 ₺ üzeri'];
    }

    public static function defaultDeadlines(): array
    {
        return ['Acele değil', '1 hafta içinde', '2–4 hafta', '1–2 ay', '2 aydan fazla'];
    }

    public static function defaultFaq(): array
    {
        return [
            ['Bir web sitesi / uygulama ne kadar sürede hazır olur?', 'Kapsama göre değişir: basit tanıtım siteleri genellikle 1–2 hafta, kurumsal siteler 2–4 hafta, e-ticaret ve özel yazılımlar 1–3 ay sürer. Teklifte net süre belirtirim.'],
            ['Fiyatlandırma nasıl yapılıyor?', 'Teklif formunu doldurduğunuzda ihtiyaçlarınıza göre kalem kalem fiyat çıkarırım. Gizli maliyet yoktur; ödeme genellikle başlangıç ve teslimde olmak üzere iki aşamalıdır.'],
            ['Alan adı ve hosting işlerini siz mi hallediyorsunuz?', 'Evet. İsterseniz alan adı ve hosting seçimi, satın alma ve kurulum süreçlerini sizin adınıza yönetirim. Hesaplar her zaman sizin adınıza açılır.'],
            ['Teslimden sonra içerikleri kendim güncelleyebilir miyim?', 'Evet. Tüm projeler kolay bir yönetim paneliyle teslim edilir ve kullanım eğitimi verilir.'],
            ['Kaynak kodlar bana ait mi olacak?', 'Evet, ödemesi tamamlanan projenin kaynak kodları ve tüm hakları size teslim edilir.'],
            ['Uzaktan çalışıyor musunuz?', 'Evet, Türkiye\'nin her yerinden ve yurt dışından müşterilerle online görüşmeler üzerinden çalışıyorum.'],
        ];
    }
}
