<?php
/**
 * Panel 404 sayfası — menü ve üst bar yerinde kalır
 * @var App\Core\View $this
 * @var string $path
 */
$this->layout('main');
?>
<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area admin-404">
            <div class="admin-404-code" aria-hidden="true">
                <span>4</span><span class="admin-404-mark"><?= icon('code', 44) ?></span><span>4</span>
            </div>
            <h2>Bu panel sayfası bulunamadı</h2>
            <p class="text-muted">
                <code><?= e($path) ?></code> adresinde bir sayfa yok. Bağlantı hatalı olabilir
                ya da sayfa taşınmış veya silinmiş olabilir.
            </p>

            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <a href="<?= e(admin_url()) ?>" class="btn btn-primary"><?= icon('home', 16) ?> Gösterge Paneli</a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary"><?= icon('arrow-left', 16) ?> Geri Dön</a>
            </div>

            <div class="admin-404-links">
                <div class="text-muted small mb-2">Sık kullanılanlar</div>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= e(admin_url('mesajlar')) ?>" class="btn btn-sm btn-outline-info"><?= icon('mail', 14) ?> Mesajlar</a>
                    <a href="<?= e(admin_url('teklifler')) ?>" class="btn btn-sm btn-outline-info"><?= icon('inbox', 14) ?> Teklif Talepleri</a>
                    <a href="<?= e(admin_url('projeler')) ?>" class="btn btn-sm btn-outline-info"><?= icon('folder', 14) ?> Projeler</a>
                    <a href="<?= e(admin_url('ozgecmis')) ?>" class="btn btn-sm btn-outline-info"><?= icon('briefcase', 14) ?> Özgeçmiş</a>
                    <a href="<?= e(admin_url('ayarlar')) ?>" class="btn btn-sm btn-outline-info"><?= icon('settings', 14) ?> Site Ayarları</a>
                </div>
            </div>
        </div>
    </div>
</div>
