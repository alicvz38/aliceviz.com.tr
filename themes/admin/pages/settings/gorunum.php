<?php
/**
 * Site Ayarları · Görünüm (logo, favicon, tema, renkli ikonlar)
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 * @var array $themes
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
$fav = custom_favicon();
$favUrl = $fav['url'] ?? shared_asset('img/favicon.svg');
$logo = custom_logo();
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<form method="post" action="<?= e(admin_url('ayarlar/gorunum')) ?>" enctype="multipart/form-data" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('image') ?> Logo</div>
                <div class="row align-items-center">
                    <div class="col-lg-5 mb-3 mb-lg-0">
                        <div class="logo-demo">
                            <span class="logo-demo-mark" id="logoDefault" <?= $logo ? 'hidden' : '' ?>><?= icon('code', 18) ?></span>
                            <img src="<?= e($logo ?? '') ?>" id="logoPreview" class="logo-demo-img <?= $logo ? '' : 'd-none' ?>" alt="Logo">
                            <span class="logo-demo-text" id="logoDemoText" <?= $logo && ($settings['logo_show_text'] ?? '1') !== '1' ? 'hidden' : '' ?>><?= e($s('site_domain') ?: $s('site_title') ?: 'Site') ?></span>
                        </div>
                        <div class="form-text mt-2">Sitenin üst menüsünde, alt bilgide, panelin sol menüsünde ve giriş sayfasında böyle görünür.</div>
                    </div>
                    <div class="col-lg-7">
                        <input type="file" class="form-control form-control-sm" id="logo" name="logo" accept="image/png,image/webp,image/jpeg,image/gif" data-preview="#logoPreview" data-hide="#logoDefault">
                        <div class="form-text">Şeffaf arka planlı PNG önerilir; 34 px yüksekliğe ölçeklenir (en fazla 180 px genişlik). PNG, WEBP, JPG veya GIF — en fazla 1 MB.<?= $logo ? '' : ' Şu an varsayılan </> logosu kullanılıyor.' ?></div>
                        <div class="switch form-switch-custom switch-inline form-switch-primary mt-2 d-block">
                            <input class="switch-input" type="checkbox" role="switch" id="logo_show_text" name="logo_show_text" value="1" <?= ($settings['logo_show_text'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <label class="switch-label" for="logo_show_text">Logonun yanında alan adını göster</label>
                        </div>
                        <div class="form-text">Logonuzda zaten yazı varsa kapatın.</div>
                        <?php if ($logo): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                                <label class="form-check-label" for="remove_logo">Varsayılan logoya dön</label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-12 layout-spacing">
            <div class="widget widget-content-area h-100">
                <div class="section-title"><?= icon('layers') ?> Site Teması</div>
                <?php foreach ($themes as $slug => $info): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="site_theme" id="theme_<?= e($slug) ?>" value="<?= e($slug) ?>" <?= ($s('site_theme') ?: 'default') === $slug ? 'checked' : '' ?>>
                        <label class="form-check-label" for="theme_<?= e($slug) ?>">
                            <strong><?= e($info['name'] ?? $slug) ?></strong> <small class="text-muted">v<?= e($info['version'] ?? '1.0') ?></small><br>
                            <small class="text-muted"><?= e($info['description'] ?? '') ?></small>
                        </label>
                    </div>
                <?php endforeach; ?>
                <div class="form-text">Yeni tema eklemek için <code>themes/default</code> klasörünü kopyalayın.</div>
                <hr class="my-3">
                <div class="switch form-switch-custom switch-inline form-switch-primary mb-1 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="colorful_icons" name="colorful_icons" value="1" <?= ($settings['colorful_icons'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="switch-label" for="colorful_icons">Renkli ikonlar</label>
                </div>
                <div class="form-text">Hizmet özellikleri ve çalışma süreci ikonları her biri farklı renkte görünür. Kapatınca hizmetin kendi tek rengine döner.</div>
            </div>
        </div>

        <div class="col-xl-6 col-12 layout-spacing">
            <div class="widget widget-content-area h-100">
                <div class="section-title"><?= icon('image') ?> Favicon (sekme ikonu)</div>
                <div class="favicon-demo mb-3">
                    <img src="<?= e($favUrl) ?>" id="faviconPreview" class="favicon-preview" alt="Favicon">
                    <div class="favicon-tab"><img src="<?= e($favUrl) ?>" alt=""><span><?= e($s('site_title') ?: 'Site') ?></span></div>
                </div>
                <input type="file" class="form-control form-control-sm" id="favicon" name="favicon" accept="image/png,image/x-icon,image/webp,image/jpeg" data-preview="#faviconPreview">
                <div class="form-text">Kare bir PNG önerilir (en az 180×180 px). PNG, ICO, WEBP veya JPG — en fazla 1 MB.<?= $fav ? '' : ' Şu an varsayılan logo kullanılıyor.' ?></div>
                <?php if ($fav): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="remove_favicon" name="remove_favicon" value="1">
                        <label class="form-check-label" for="remove_favicon">Varsayılan logoya dön</label>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 layout-spacing">
            <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
        </div>
    </div>
</form>
