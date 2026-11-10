<?php
/**
 * @var App\Core\View $this
 * @var array $settings
 * @var array $themes
 * @var bool $hasToken
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
?>
<form method="post" action="<?= e(admin_url('ayarlar')) ?>" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-xl-8 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('globe') ?> Genel</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="site_title">Site Başlığı / Ad Soyad</label>
                        <input type="text" class="form-control" id="site_title" name="site_title" value="<?= e($s('site_title')) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="site_domain">Alan Adı</label>
                        <input type="text" class="form-control" id="site_domain" name="site_domain" value="<?= e($s('site_domain')) ?>" placeholder="aliceviz.com.tr">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="site_description">Site Açıklaması</label>
                        <input type="text" class="form-control" id="site_description" name="site_description" value="<?= e($s('site_description')) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="contact_email">İletişim E-postası</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= e($s('contact_email')) ?>">
                        <div class="form-text">Sitede görünür; yeni mesaj / teklif bildirimleri bu adrese gelir.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="cv_phone">Telefon</label>
                        <input type="tel" class="form-control" id="cv_phone" name="cv_phone" data-mask="phone" value="<?= e($s('cv_phone')) ?>">
                        <div class="form-text">İletişim sayfasında, alt bilgide ve özgeçmişte görünür. Boş bırakılırsa gösterilmez.</div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="form-text">
                            "Hakkımda" yazısı ve özgeçmiş bilgileri artık
                            <a href="<?= e(admin_url('ozgecmis')) ?>">Özgeçmiş (CV)</a> sayfasından düzenlenir.
                        </div>
                    </div>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('github') ?> GitHub</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="github_username">GitHub Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="github_username" name="github_username" value="<?= e($s('github_username')) ?>" placeholder="aliceviz">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="github_token">Kişisel Erişim Token'ı <small class="text-muted">(isteğe bağlı)</small></label>
                        <input type="password" class="form-control" id="github_token" name="github_token" autocomplete="new-password" placeholder="<?= $hasToken ? '•••••••• (kayıtlı — değiştirmek için yazın)' : 'ghp_...' ?>">
                        <div class="form-text">Token olmadan saatte 60 istek hakkı vardır. Yalnızca "public_repo" okuma izni yeterlidir.</div>
                        <?php if ($hasToken): ?>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="remove_token" name="remove_token" value="1">
                                <label class="form-check-label" for="remove_token">Kayıtlı token'ı sil</label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('user') ?> Sosyal Medya</div>
                <div class="row">
                    <?php foreach (['social_instagram' => 'Instagram', 'social_facebook' => 'Facebook', 'social_x' => 'X (Twitter)', 'social_youtube' => 'YouTube', 'social_linkedin' => 'LinkedIn'] as $key => $label): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="<?= $key ?>"><?= $label ?></label>
                            <input type="url" class="form-control" id="<?= $key ?>" name="<?= $key ?>" value="<?= e($s($key)) ?>" placeholder="https://">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text">Boş bırakılan hesaplar sitede gösterilmez. GitHub ikonu kullanıcı adından otomatik oluşur.</div>
            </div>
        </div>

        <div class="col-xl-4 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('tool') ?> Bakım Modu</div>
                <div class="switch form-switch-custom switch-inline form-switch-warning mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="switch-label" for="maintenance_mode">Siteyi bakım moduna al</label>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="maintenance_message">Bakım Mesajı</label>
                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?= e($s('maintenance_message')) ?></textarea>
                </div>
                <a href="<?= e(url('bakimda.php')) ?>" target="_blank" class="btn btn-outline-warning btn-sm w-100"><?= icon('eye', 16) ?> Bakım Sayfasını Önizle</a>
            </div>

            <div class="widget widget-content-area mt-4">
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
            </div>

            <div class="widget widget-content-area mt-4">
                <button type="submit" class="btn btn-primary w-100"><?= icon('check', 16) ?> Ayarları Kaydet</button>
            </div>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-xl-8 col-12 layout-spacing">
        <div class="widget widget-content-area d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="section-title mb-1"><?= icon('trash-cache') ?> Önbellek</div>
                <small class="text-muted">GitHub depo listeleri ve README'ler 1 saat önbellekte tutulur. Değişiklikleri hemen görmek için temizleyin.</small>
            </div>
            <form method="post" action="<?= e(admin_url('ayarlar/onbellek')) ?>" class="m-0" data-ajax data-ajax-success="none">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger btn-sm">Önbelleği Temizle</button>
            </form>
        </div>
    </div>
</div>
