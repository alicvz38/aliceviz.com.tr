# Web Sitem — Kurulum ve Çalıştırma

GitHub projelerini yayınlayan, nesne tabanlı (OOP) PHP portföy sitesi.
Koyu tema · Tema parçalama sistemi · Equation (vertical-dark-menu) yönetim paneli · Bakım modu

---

## 1. Gereksinimler

- **PHP 8.1+** (pdo_mysql, fileinfo, curl eklentileri — XAMPP'te hazır gelir)
- **MySQL / MariaDB**
- **Apache + mod_rewrite** (XAMPP'te açık gelir)

## 2. Yerel sunucuda çalıştırma (XAMPP)

1. **XAMPP'i kurun:** https://www.apachefriends.org → Windows için **PHP 8.2** sürümünü indirip kurun (varsayılan klasör `C:\xampp`).
2. **XAMPP Control Panel**'i açın → **Apache** ve **MySQL** satırlarında **Start**'a basın (ikisi de yeşil olmalı).
3. **htdocs'u boşaltın:** `C:\xampp\htdocs` içinde XAMPP'in kendi örnek dosyaları (`index.php`, `dashboard`, `img`, `applications.html`, `bitnami.css`, `favicon.ico` vb.) vardır. Bunları silin veya başka bir yere taşıyın.
   > phpMyAdmin bu klasörde değildir, silmeniz ona zarar vermez: http://localhost/phpmyadmin çalışmaya devam eder.
4. **Dosyaları kopyalayın:** `Web Sitem` klasörünün **içindekileri** doğrudan `C:\xampp\htdocs` içine kopyalayın. Sonuç şöyle olmalı:
   ```
   C:\xampp\htdocs\
   ├── .htaccess          (gizli dosya olabilir, mutlaka kopyalayın)
   ├── index.php
   ├── bakimda.php
   ├── install.php
   ├── app\  config\  routes\  themes\  storage\  uploads\  database\
   └── equation-v2.2.0\   (panel teması buradan yüklenir)
   ```
5. **Veritabanı ayarları:** `config/config.php` dosyasını açın. XAMPP'in varsayılanı zaten girili:
   ```php
   'host' => '127.0.0.1', 'name' => 'websitem', 'user' => 'root', 'pass' => '',
   ```
   (Veritabanını elle oluşturmanıza gerek yok, kurulum sihirbazı oluşturur.)
6. **Kurulumu başlatın:** Tarayıcıda açın → **http://localhost/install.php**
   Ad Soyad, kullanıcı adı, e-posta, şifre ve (isteğe bağlı) GitHub kullanıcı adınızı girip **Kurulumu Başlat**'a basın.
7. **Panele giriş:** **http://localhost/admin**

| Adres | Ne açılır |
|---|---|
| http://localhost/ | Site (bakım modu açıkken bakım sayfası) |
| http://localhost/projeler | Proje listesi |
| http://localhost/bakimda.php | Bakım sayfası (her zaman) |
| http://localhost/admin | Yönetim paneli |

> Site, kurulduğu klasörü kendisi algılar. İleride bir alt klasöre (ör. `htdocs\websitem`) taşırsanız kodda değişiklik gerekmez; adresler `http://localhost/websitem/...` olur.

> Site ilk kurulumda **bakım modunda** başlar. Panelde oturum açtığınız sürece siteyi normal görürsünüz; ziyaretçiler bakım sayfasını görür. Hazır olunca üst menüdeki **"Bakım Modu Açık"** düğmesiyle siteyi yayına alın.

### İlk adımlar (panel)
1. **Site Ayarları** → GitHub kullanıcı adı, e-posta, sosyal medya bağlantıları.
2. **GitHub** menüsü → depolarınız listelenir → istediklerinizi seçip **İçe Aktar**.
3. **Projeler** → açıklama, kapak görseli, "öne çıkar" ve sıralama ayarları.
4. Üst menüden **bakım modunu kapatın**.

### Sık karşılaşılan sorunlar
- **Apache başlamıyor (port 80 dolu):** Skype/IIS kapatın veya XAMPP > Apache > Config > httpd.conf içinde `Listen 80` → `Listen 8080` yapıp `http://localhost:8080` kullanın.
- **localhost açılınca XAMPP karşılama sayfası geliyor:** htdocs'taki XAMPP örnek dosyaları silinmemiştir (3. adım). Tarayıcı önbelleği yüzünden olabilir; Ctrl+F5 ile yenileyin.
- **Sayfalar 404 veriyor:** `.htaccess` dosyasının kopyalandığından emin olun (gizli dosya olabilir).
- **"GitHub API istek sınırına ulaşıldı":** Ayarlar'a bir GitHub token ekleyin (github.com → Settings → Developer settings → Personal access tokens).

## 3. Canlı sunucuya yükleme

1. Tüm dosyaları hosting'in `public_html` klasörüne yükleyin (`equation-v2.2.0` için yalnızca `html/src` ve `html/layouts/vertical-dark-menu` yeterli).
2. `config/config.php` → hosting veritabanı bilgileri ve `'debug' => false`.
3. `siteadresi/install.php` ile kurulumu yapın, ardından `install.php`'yi silin.
4. `storage/` ve `uploads/` klasörlerinin yazılabilir olduğundan emin olun.

---

## 4. Yeni özellikler

| Adres | Ne açılır |
|---|---|
| /hakkimda | CV tarzı özgeçmiş (yazdırılabilir / PDF) — Panel › **Özgeçmiş (CV)** |
| /hizmetler, /hizmetler/web-sitesi, /hizmetler/masaustu-uygulama | Hizmet sayfaları (metinler: `app/Support/ServiceCatalog.php`) |
| /teklif-al | Teklif formu — Panel › **Teklif Talepleri** |
| /iletisim | İletişim formu — Panel › **Mesajlar** |

**E-posta (SMTP):** Panel › Mesajlar › **E-posta Ayarları**. Gmail için: Google Hesabı › Güvenlik › 2 Adımlı Doğrulama'yı açın › *Uygulama şifreleri* › 16 haneli şifreyi "Şifre" alanına yazın (sunucu `smtp.gmail.com`, port 587, TLS). "Test E-postası Gönder" ile deneyin.

**Gmail senkronizasyonu:** Gmail'in tüm klasörleri (Gelen, Önemli, Gönderilen, Taslak, Spam, Çöp, Tüm Postalar, etiketler) panele aynalanır; okundu / yıldız / silme iki yönlüdür. Arka planda çalışması için iki yol var:
- **Canlı sunucu (önerilen, anlık):** `realtime\baslat.bat` — WebSocket sunucusu (ws://127.0.0.1:8090) Gmail'i IMAP IDLE ile dinler, yeni e-postayı ~1 sn içinde panele iletir. Bilgisayar açılınca kendiliğinden başlaması için `realtime\otomatik-baslat-kur.bat`. Durdurmak: `realtime\durdur.bat`.
- **Dakikalık görev:** `cron\gorevi-kur.bat` (Windows Görev Zamanlayıcı). Hostingde: `* * * * * php /yol/cron/mail-sync.php`.
- İkisi de çalışmıyorsa panel açıkken 30 sn'de bir kontrol edilir.
- Canlıda WebSocket için VPS gerekir (paylaşımlı hosting sürekli çalışan süreçlere izin vermez); `config/config.php > realtime` ayarını ve wss:// yönlendirmesini yapın.

**Telefon maskesi:** `<input data-mask="phone">` → `0 (5XX) XXX XX XX` (`assets/js/mask.js`). Sunucuda `phone_normalize()` ile doğrulanır.

**Tablolar:** Panel tabloları Equation DataTables kullanır: `<table data-datatable>` veya `AdminDT('#tablo', {...})`.

**AJAX sistemi:** `assets/js/ajax.js`. Bir formu AJAX'lı yapmak için `<form ... data-ajax>` yeterlidir; sunucu tarafında `$this->respond(...)`, `Response::success/error` veya mevcut `redirect()` otomatik JSON döner.

**Veritabanı güncellemeleri:** `database/migrations/` içine yeni `.sql` dosyası ekleyin; ilk istekte otomatik çalışır.

## 5. Klasör yapısı

```
├── index.php              Ön denetleyici — tüm istekler buradan geçer
├── bakimda.php            Bakım sayfası (tema sistemini kullanır)
├── install.php            Kurulum sihirbazı
├── config/config.php      Veritabanı, tema, panel ayarları
├── routes/web.php         Tüm adresler (rotalar)
├── database/schema.sql    Tablolar
├── app/
│   ├── Core/              Çekirdek sınıflar
│   │   ├── Router.php       Yönlendirici (grup + middleware)
│   │   ├── View.php         TEMA PARÇALAMA SİSTEMİ
│   │   ├── Controller.php   Taban denetleyici
│   │   ├── Model.php        Active Record taban modeli
│   │   ├── Database.php     PDO (Singleton)
│   │   ├── Auth / Session / Csrf / Request / Cache / Config
│   ├── Controllers/
│   │   ├── Site/            Ana sayfa, projeler, bakım, 404
│   │   └── Admin/           Giriş, panel, projeler, GitHub, ayarlar, hesap
│   ├── Models/            Project, Setting, User
│   ├── Middleware/        Auth, Guest, Csrf, Maintenance
│   ├── Services/          GithubService, Uploader
│   └── Support/Icon.php   Feather ikonları
├── themes/
│   ├── default/           Ziyaretçi teması (koyu)
│   └── admin/             Panel teması (Equation vertical-dark-menu)
├── storage/cache/         GitHub önbelleği
└── uploads/projects/      Yüklenen kapak görselleri
```

## 6. Tema parçalama sistemi

Her tema aynı yapıdadır:

```
themes/{tema}/
├── theme.php       Tema bilgisi (ad, sürüm, vendor yolu)
├── layouts/        İskeletler: main.php, blank.php
├── partials/       Parçalar: head, navbar, footer, project-card, social-links...
├── pages/          Sayfalar: home, projects, project-detail, maintenance, 404
└── assets/         css / js
```

Bir sayfa dosyasında:

```php
<?php $this->layout('main'); ?>                         // hangi iskelet

<?= $this->partial('project-card', ['project' => $p]) ?> // parça ekle

<?php $this->start('scripts') ?>                        // iskelete bölüm gönder
<script src="<?= $this->asset('js/ozel.js') ?>"></script>
<?php $this->stop() ?>
```

İskelet dosyasında `<?= $this->section('content') ?>`, `<?= $this->section('scripts') ?>` ile bölümler yerleştirilir.

**Yeni tema:** `themes/default` klasörünü `themes/yeni-tema` olarak kopyalayın, `theme.php` içindeki adı değiştirin → Panel › Site Ayarları › Site Teması'ndan seçin.

**Panele yeni menü:** `app/Controllers/Admin/AdminController.php` → `menu()` dizisine bir satır ekleyin.

**Yeni sayfa:** `routes/web.php`'ye rota ekleyin → denetleyicide `$this->render('sayfa-adi', [...])` → `themes/default/pages/sayfa-adi.php` oluşturun.
