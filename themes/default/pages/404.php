<?php
/**
 * 404 — Sayfa bulunamadı.
 * bakimda.php tasarım dilini kullanır (yıldızlı arka plan, cam kart, renk dalgalı rozet, 3D eğim).
 * @var App\Core\View $this
 */
$this->layout('blank');
?>
<?php $this->start('styles') ?>
<link rel="stylesheet" href="<?= $this->asset('css/maintenance.css') ?>">
<link rel="stylesheet" href="<?= $this->asset('css/error.css') ?>">
<?php $this->stop() ?>

<?php $this->start('bodyClass') ?>error-page<?php $this->stop() ?>

<div class="bg-image"></div>
<div class="bg-overlay"></div>

<main class="container">
    <span class="badge">Sayfa Bulunamadı</span>

    <div class="error-code" aria-hidden="true">
        <span>4</span><span class="error-orbit"><i></i></span><span>4</span>
    </div>

    <h1 class="error-title">Kaybolmuş gibisin</h1>
    <p class="description">
        Aradığın sayfa taşınmış, silinmiş ya da hiç var olmamış olabilir.
        Ama merak etme, projelerim hâlâ yerinde.
    </p>

    <div class="error-actions">
        <a href="<?= e(url()) ?>" class="contact-box">
            <?= icon('home', 18) ?><span>Ana Sayfa</span>
        </a>
        <a href="<?= e(url('projeler')) ?>" class="contact-box">
            <?= icon('layers', 18) ?><span>Projeler</span>
        </a>
        <button type="button" class="contact-box" onclick="history.length > 1 ? history.back() : location.href='<?= e(url()) ?>'">
            <?= icon('arrow-left', 18) ?><span>Geri Dön</span>
        </button>
    </div>

    <form class="error-search" method="get" action="<?= e(url('projeler')) ?>" role="search">
        <?= icon('search', 16) ?>
        <input type="search" name="q" placeholder="Bir proje ara..." aria-label="Proje ara">
    </form>

    <?= $this->partial('social-links') ?>

    <div class="footer-text">
        &copy; <?= date('Y') ?> <a href="<?= e(url()) ?>">www.<?= e($site['domain'] ?: 'aliceviz.com.tr') ?></a> &mdash; Tüm hakları saklıdır.
    </div>
</main>

<?php $this->start('scripts') ?>
<script src="<?= $this->asset('js/maintenance.js') ?>"></script>
<?php $this->stop() ?>
