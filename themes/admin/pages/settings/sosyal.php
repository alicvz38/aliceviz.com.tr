<?php
/**
 * Site Ayarları · Sosyal Medya
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 * @var array $socials  anahtar => etiket
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<form method="post" action="<?= e(admin_url('ayarlar/sosyal')) ?>" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row justify-content-center">
        <div class="col-xl-8 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('users') ?> Sosyal Medya</div>
                <div class="row">
                    <?php foreach ($socials as $key => $label): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label>
                            <input type="url" class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($s($key)) ?>" placeholder="https://">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text mb-3">Boş bırakılan hesaplar sitede gösterilmez. GitHub ikonu <a href="<?= e(admin_url('ayarlar/github')) ?>">GitHub</a> ayarındaki kullanıcı adından otomatik oluşur.</div>
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
            </div>
        </div>
    </div>
</form>
