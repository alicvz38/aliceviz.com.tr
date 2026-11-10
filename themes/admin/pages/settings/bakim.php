<?php
/**
 * Site Ayarları · Bakım Modu
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<form method="post" action="<?= e(admin_url('ayarlar/bakim')) ?>" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row justify-content-center">
        <div class="col-xl-8 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('tool') ?> Bakım Modu</div>
                <div class="switch form-switch-custom switch-inline form-switch-warning mb-2 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="switch-label" for="maintenance_mode">Siteyi bakım moduna al</label>
                </div>
                <div class="form-text mb-3">Açıkken ziyaretçiler yalnızca bakım sayfasını görür. Panele giriş yapmışken siteyi normal görmeye devam edersiniz.</div>
                <div class="mb-3">
                    <label class="form-label" for="maintenance_message">Bakım Mesajı</label>
                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3" maxlength="1000"><?= e($s('maintenance_message')) ?></textarea>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
                    <a href="<?= e(url('bakimda.php')) ?>" target="_blank" class="btn btn-outline-warning"><?= icon('eye', 16) ?> Bakım Sayfasını Önizle</a>
                </div>
            </div>
        </div>
    </div>
</form>
