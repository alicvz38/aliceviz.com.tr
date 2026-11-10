<?php
/**
 * Site Ayarları · SEO & Google
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
$og = $s('seo_og_image') !== '' && is_file(ROOT_PATH . '/' . $s('seo_og_image')) ? url($s('seo_og_image')) : null;
$defaultTitle = ($settings['site_title'] ?? '') . (!empty($settings['cv_title']) ? ' — ' . $settings['cv_title'] : '');
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<form method="post" action="<?= e(admin_url('ayarlar/seo')) ?>" enctype="multipart/form-data" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row justify-content-center">
        <div class="col-xl-8 col-12 layout-spacing">

            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('search') ?> Google'da Ana Sayfa</div>
                <div class="mb-3">
                    <label class="form-label" for="seo_home_title">Başlık <small class="text-muted">(en fazla 60-70 karakter)</small></label>
                    <input type="text" class="form-control" id="seo_home_title" name="seo_home_title" value="<?= e($s('seo_home_title')) ?>" maxlength="70" placeholder="<?= e($defaultTitle) ?>">
                    <div class="form-text">Google sonuçlarında mavi başlık olarak görünür. Boş bırakılırsa: <em><?= e($defaultTitle) ?></em></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="seo_home_description">Açıklama <small class="text-muted">(en fazla 160 karakter)</small></label>
                    <textarea class="form-control" id="seo_home_description" name="seo_home_description" rows="2" maxlength="170" placeholder="<?= e($settings['site_description'] ?? '') ?>"><?= e($s('seo_home_description')) ?></textarea>
                    <div class="form-text">Başlığın altındaki gri yazı. Boş bırakılırsa Genel ayarlardaki site açıklaması kullanılır. Ziyaretçinin arayacağı kelimeleri (ör. "web sitesi yaptırma", "masaüstü uygulama") doğal bir cümleyle kullanın.</div>
                </div>
                <div class="seo-preview">
                    <small><?= e(preg_replace('#^https?://#', '', absolute_url())) ?></small>
                    <strong id="seoPrevTitle"><?= e($s('seo_home_title') ?: $defaultTitle) ?></strong>
                    <span id="seoPrevDesc"><?= e($s('seo_home_description') ?: ($settings['site_description'] ?? '')) ?></span>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('check-circle') ?> Arama Motoru Doğrulama</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="seo_google_verification">Google Search Console kodu</label>
                        <input type="text" class="form-control" id="seo_google_verification" name="seo_google_verification" value="<?= e($s('seo_google_verification')) ?>" placeholder="ör. AbC123... veya &lt;meta ...&gt;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="seo_bing_verification">Bing Webmaster kodu <small class="text-muted">(isteğe bağlı)</small></label>
                        <input type="text" class="form-control" id="seo_bing_verification" name="seo_bing_verification" value="<?= e($s('seo_bing_verification')) ?>">
                    </div>
                </div>
                <div class="form-text mb-2">Search Console "HTML etiketi" doğrulamasında verilen <code>&lt;meta ...&gt;</code> satırının tamamını yapıştırabilirsiniz; kod otomatik ayıklanır.</div>
                <div class="seo-links">
                    <span>Site haritası: <a href="<?= e(absolute_url('sitemap.xml')) ?>" target="_blank"><?= e(absolute_url('sitemap.xml')) ?></a></span>
                    <span>Robots: <a href="<?= e(absolute_url('robots.txt')) ?>" target="_blank"><?= e(absolute_url('robots.txt')) ?></a></span>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('image') ?> Paylaşım Görseli</div>
                <img src="<?= e($og ?? shared_asset('img/og-image.jpg')) ?>" id="ogPreview" class="og-preview mb-3" alt="Paylaşım görseli">
                <input type="file" class="form-control form-control-sm" id="og_image" name="og_image" accept="image/png,image/webp,image/jpeg" data-preview="#ogPreview">
                <div class="form-text">Site WhatsApp, LinkedIn, Facebook veya X'te paylaşıldığında görünen resim. 1200×630 px önerilir; PNG, WEBP veya JPG — en fazla 1 MB. Proje sayfalarında projenin kendi kapağı kullanılır.<?= $og ? '' : ' Şu an varsayılan görsel kullanılıyor.' ?></div>
                <?php if ($og): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="remove_og_image" name="remove_og_image" value="1">
                        <label class="form-check-label" for="remove_og_image">Varsayılan görsele dön</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
            </div>
        </div>
    </div>
</form>
