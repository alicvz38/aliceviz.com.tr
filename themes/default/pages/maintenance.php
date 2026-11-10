<?php
/**
 * Bakım sayfası — orijinal bakimda.php tasarımı.
 * Metinler ve bağlantılar panelden (Site Ayarları) yönetilir.
 * @var App\Core\View $this
 * @var string $message
 */
$this->layout('blank');
?>
<?php $this->start('styles') ?>
<link rel="stylesheet" href="<?= $this->asset('css/maintenance.css') ?>">
<?php $this->stop() ?>

<?php $this->start('bodyClass') ?>maintenance-page<?php $this->stop() ?>

<div class="bg-image"></div>
<div class="bg-overlay"></div>

<main class="container">
    <span class="badge">Sitemiz Şu Anda Bakımda</span>
    <h1 class="brand-name"><?= e($site['domain'] ?: $site['title']) ?></h1>
    <p class="description"><?= nl2br(e($message)) ?></p>

    <?php if ($site['email']): ?>
        <button id="copyBtn" class="contact-box" data-email="<?= e($site['email']) ?>">
            <svg viewBox="0 0 24 24">
                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
            </svg>
            <span>İletişime Geçin</span>
        </button>
    <?php endif; ?>

    <!-- Sosyal Medya Bağlantıları -->
    <?= $this->partial('social-links') ?>

    <div class="footer-text">
        &copy; <?= date('Y') ?> <a href="https://www.<?= e($site['domain'] ?: 'aliceviz.com.tr') ?>">www.<?= e($site['domain'] ?: 'aliceviz.com.tr') ?></a> &mdash; Tüm hakları saklıdır.
    </div>
</main>

<!-- Toast Bildirim Elemanı -->
<?= $this->partial('toast') ?>

<?php $this->start('scripts') ?>
<script src="<?= $this->asset('js/maintenance.js') ?>"></script>
<?php $this->stop() ?>
