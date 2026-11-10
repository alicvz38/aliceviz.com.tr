<?php
/**
 * Site Ayarları · GitHub
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 * @var bool $hasToken
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<div class="row">
    <div class="col-xl-8 col-12 layout-spacing">
        <form method="post" action="<?= e(admin_url('ayarlar/github')) ?>" data-ajax id="settingsForm">
            <?= csrf_field() ?>
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('github') ?> GitHub</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="github_username">GitHub Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="github_username" name="github_username" value="<?= e($s('github_username')) ?>" placeholder="alicvz38">
                        <div class="form-text">Projeler bu hesaptan içe aktarılır.</div>
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
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
            </div>
        </form>

        <div class="widget widget-content-area mt-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
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
