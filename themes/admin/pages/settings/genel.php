<?php
/**
 * Site Ayarları · Genel
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<form method="post" action="<?= e(admin_url('ayarlar/genel')) ?>" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-xl-8 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('globe') ?> Genel</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="site_title">Site Başlığı / Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="site_title" name="site_title" value="<?= e($s('site_title')) ?>" maxlength="150" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="site_domain">Alan Adı</label>
                        <input type="text" class="form-control" id="site_domain" name="site_domain" value="<?= e($s('site_domain')) ?>" maxlength="150" placeholder="aliceviz.com.tr">
                        <div class="form-text">Sitenin sol üstündeki logoda ve alt bilgide görünür.</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="site_description">Site Açıklaması</label>
                        <input type="text" class="form-control" id="site_description" name="site_description" value="<?= e($s('site_description')) ?>" maxlength="300">
                        <div class="form-text">Ana sayfada ve arama motorlarında görünen kısa tanıtım.</div>
                    </div>
                </div>
                <div class="form-text mb-3">
                    "Hakkımda" yazısı ve özgeçmiş bilgileri <a href="<?= e(admin_url('ozgecmis')) ?>">Özgeçmiş (CV)</a> sayfasından düzenlenir.
                </div>
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
            </div>
        </div>
    </div>
</form>
