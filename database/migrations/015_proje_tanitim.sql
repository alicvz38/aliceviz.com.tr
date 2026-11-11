-- Proje tanıtım sayfası (/projeler/{slug}/tanitim): sıralı ekran görüntüleri, başlık ve açıklama
CREATE TABLE IF NOT EXISTS `project_shots` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id`  INT UNSIGNED NOT NULL,
    `image`       VARCHAR(255) NOT NULL,
    `title`       VARCHAR(150) NOT NULL DEFAULT '',
    `caption`     VARCHAR(1000) NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `project_shots_project_idx` (`project_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Başlangıç görselleri (uploads/projects/tanitim/...). Proje yoksa satır eklenmez.
INSERT INTO `project_shots` (`project_id`, `image`, `title`, `caption`, `sort_order`)
SELECT p.`id`, s.`image`, s.`title`, s.`caption`, s.`sort_order`
FROM `projects` p
JOIN (
    SELECT 'tablo-studyo' AS slug, 'uploads/projects/tanitim/tablo-studyo/01-magaza.jpg' AS image, 'Mağaza ana sayfası' AS title,
           'Temperli cam tablolar gerçek oda fotoğraflarında, cam yansımasıyla birlikte gösterilir. Ziyaretçi tablonun duvarda nasıl duracağını görerek seçer.' AS caption, 1 AS sort_order
    UNION ALL SELECT 'tablo-studyo', 'uploads/projects/tanitim/tablo-studyo/02-koleksiyon.jpg', 'Şekle göre koleksiyon',
           'Yatay, dikey, kare ve yuvarlak tablolar ayrı listelenir, her kartta ürünün kaç farklı oda görünümüyle sunulduğu yazar.', 2
    UNION ALL SELECT 'tablo-studyo', 'uploads/projects/tanitim/tablo-studyo/03-urun-sayfasi.jpg', 'Ürün sayfası',
           'Aynı tablo yedi farklı oda fotoğrafında, perspektif ve cam yansıması OpenCV ile otomatik hesaplanır. Sepet, favoriler ve değerlendirmeler aynı sayfada.', 3
    UNION ALL SELECT 'tablo-studyo', 'uploads/projects/tanitim/tablo-studyo/04-satici-paneli.jpg', 'Satıcı paneli',
           'Kayıtlı odalar, ürünler, bekleyen siparişler, toplam satış ve üyeler tek ekranda. Hızlı işlemlerle yeni ürün ve oda sunumu hazırlanır.', 4
    UNION ALL SELECT 'tablo-studyo', 'uploads/projects/tanitim/tablo-studyo/05-urun-yonetimi.jpg', 'Ürün yönetimi',
           'Ürünleri arama, tipe göre filtreleme, mağazada yayına alma ve düzenleme. Bir odanın yansıması değişince o odadaki bütün ürün görselleri kendiliğinden yenilenir.', 5
    UNION ALL SELECT 'creator-suite', 'uploads/projects/tanitim/creator-suite/01-recording-studio.jpg', 'Recording Studio: kayıt ve canlı yayın',
           'Sahneler ve kaynaklar (ekran, oyun, pencere, kamera, video, tarayıcı, yazı), ses karıştırıcı, tek tıkla kayıt ve canlı yayın, tekrar kaydı ve Windows 11''de sanal kamera.', 1
    UNION ALL SELECT 'creator-suite', 'uploads/projects/tanitim/creator-suite/02-video-editor.jpg', 'Video Editor: zaman çizelgesi',
           'Çok kanallı zaman çizelgesi, geçişler, efektler, altyazı ve ses kanalları. Recording Studio kayıtları tek tıkla düzenleyiciye aktarılır, dışa aktarma FFmpeg ile yapılır.', 2
    UNION ALL SELECT 'creator-suite', 'uploads/projects/tanitim/creator-suite/03-game-tools.jpg', 'Game Tools: nişangâh ve FPS göstergesi',
           'Oyunların üzerinde ayrıntılı ayarlanabilen nişangâh ve hazır profiller, FPS ve donanım göstergesi, performans testi, nişan antrenmanı ve yayın araçları.', 3
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/01-ana-sayfa.jpg', 'Ana sayfa',
           'Bağımlılıksız PHP 8 ile yazılmış portföy sitesi: GitHub projeleri, hizmetler ve teklif formu. Sayılar kilometre sayacı gibi kayarak yükselir.', 1
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/02-hizmet-sayfasi.jpg', 'Hizmet sayfaları',
           'Web sitesi ve masaüstü uygulama hizmetleri, özellikler, paketler ve fiyat aralıkları panelden düzenlenir.', 2
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/03-projeler.jpg', 'Projeler',
           'GitHub''dan içe aktarılan projeler, arama, Web / Uygulama sekmeleri ve dil filtresi AJAX ile sayfa yenilenmeden çalışır.', 3
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/04-proje-sayfasi.jpg', 'Proje sayfası',
           'Kapak görseli, GitHub bilgileri ve depodaki README, README''deki bağlantılar GitHub adresine çevrilerek gösterilir.', 4
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/05-ozgecmis.jpg', 'Özgeçmiş',
           'CV tarzı Hakkımda sayfası, tek A4 sayfasına sığan yazdırma / PDF çıktısı ve WhatsApp''a bağlı telefon.', 5
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/06-teklif-formu.jpg', 'Teklif formu',
           'Hizmet, paket, bütçe ve süre seçilerek gönderilen teklif talepleri panelde takip edilir.', 6
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/07-panel.jpg', 'Yönetim paneli',
           'Equation tabanlı panel: projeler, mesajlar, teklifler, canlı destek ve özgeçmiş, panel adresi gizli tutulur.', 7
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/08-takvim.jpg', 'Takvim',
           'Paneldeki her işlem ve GitHub commit''leri takvime kendiliğinden düşer, günün dökümü saat sırasıyla listelenir.', 8
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/09-gorunum-ayarlari.jpg', 'Görünüm ayarları',
           'Logo, favicon, tema ve renkli ikonlar panelden değiştirilir, Site Ayarları bölümlere ayrılmıştır.', 9
    UNION ALL SELECT 'aliceviz-com-tr', 'uploads/projects/tanitim/aliceviz-com-tr/10-seo-ayarlari.jpg', 'SEO & Google',
           'Google sonuç önizlemesi, Search Console doğrulaması, site haritası ve paylaşım görseli, AdSense de panelden bağlanır.', 10
) s ON s.slug = p.`slug`;
