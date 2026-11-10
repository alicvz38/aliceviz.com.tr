<?php
/**
 * Hizmetler genel sayfası
 * @var App\Core\View $this
 * @var array $services
 * @var array $process
 * @var array $faq
 */
$this->layout('main');
?>
<section class="page-header text-center">
    <div class="container">
        <span class="eyebrow">Hizmetler</span>
        <h1>Web sitesi ve masaüstü <span class="gradient-text">uygulama yaptırın</span></h1>
        <p class="lead mx-auto">İşletmeniz için hızlı, güvenli ve kolay yönetilen yazılımlar geliştiriyorum. İhtiyacınızı anlatın, size özel ücretsiz teklif hazırlayayım.</p>
        <div class="hero-actions">
            <a href="<?= e(url('teklif-al')) ?>" class="btn btn-primary"><?= icon('send', 18) ?> Ücretsiz Teklif Al</a>
            <a href="<?= e(url('projeler')) ?>" class="btn btn-ghost"><?= icon('layers', 18) ?> Örnek Çalışmalar</a>
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="service-grid">
            <?php foreach ($services as $key => $svc): ?>
                <?= $this->partial('service-card', ['key' => $key, 'svc' => $svc]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?= $this->partial('process', ['process' => $process]) ?>
<?= $this->partial('faq', ['faq' => $faq]) ?>
<?= $this->partial('cta') ?>
