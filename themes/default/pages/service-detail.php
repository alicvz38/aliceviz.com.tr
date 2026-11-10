<?php
/**
 * Hizmet detayı (web sitesi / masaüstü uygulama)
 * @var App\Core\View $this
 * @var array $service
 * @var App\Models\Project[] $examples
 * @var array $process
 * @var array $faq
 */
$this->layout('main');
?>
<section class="page-header service-hero" style="--svc: <?= e($service['color']) ?>">
    <div class="container">
        <a href="<?= e(url('hizmetler')) ?>" class="link-back"><?= icon('arrow-left', 16) ?> Tüm hizmetler</a>
        <div class="service-hero-inner">
            <span class="service-icon lg"><?= icon($service['icon'], 34) ?></span>
            <div>
                <span class="eyebrow"><?= e($service['tagline']) ?></span>
                <h1><?= e($service['title']) ?></h1>
                <p class="lead"><?= e($service['intro']) ?></p>
                <div class="hero-actions">
                    <a href="<?= e(url('teklif-al?hizmet=' . $service['key'])) ?>" class="btn btn-primary"><?= icon('send', 18) ?> Ücretsiz Teklif Al</a>
                    <a href="#paketler" class="btn btn-ghost"><?= icon('layers', 18) ?> Paketleri Gör</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="feature-grid">
            <?php foreach ($service['features'] as [$ico, $title, $text]): ?>
                <div class="feature glass" style="--svc: <?= e($service['color']) ?>">
                    <span class="feature-icon"><?= icon($ico, 20) ?></span>
                    <h3><?= e($title) ?></h3>
                    <p><?= e($text) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section pt-0" id="paketler">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Paketler</span>
                <h2>İhtiyacınıza uygun başlangıç noktası</h2>
            </div>
        </div>
        <div class="package-grid">
            <?php foreach ($service['packages'] as $pkey => $pkg): ?>
                <article class="package glass <?= !empty($pkg['popular']) ? 'popular' : '' ?>">
                    <?php if (!empty($pkg['popular'])): ?><span class="package-badge">En çok tercih edilen</span><?php endif; ?>
                    <h3><?= e($pkg['name']) ?></h3>
                    <p class="package-desc"><?= e($pkg['desc']) ?></p>
                    <div class="package-price"><?= $pkg['price'] !== '' ? e($pkg['price']) : 'Projeye özel fiyat' ?></div>
                    <ul class="check-list">
                        <?php foreach ($pkg['items'] as $it): ?><li><?= icon('check', 16) ?> <?= e($it) ?></li><?php endforeach; ?>
                    </ul>
                    <a href="<?= e(url('teklif-al?' . http_build_query(['hizmet' => $service['key'], 'paket' => $pkey]))) ?>" class="btn <?= !empty($pkg['popular']) ? 'btn-primary' : 'btn-ghost' ?>">Bu paket için teklif al</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="tech-box glass">
            <span class="eyebrow">Kullandığım teknolojiler</span>
            <div class="tag-list">
                <?php foreach ($service['tech'] as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($examples): ?>
<section class="section pt-0">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Örnek çalışmalar</span>
                <h2>İlgili projelerim</h2>
            </div>
            <a href="<?= e(url('projeler')) ?>" class="link-arrow">Tüm projeler <?= icon('arrow-right', 16) ?></a>
        </div>
        <div class="project-grid">
            <?php foreach ($examples as $project): ?>
                <?= $this->partial('project-card', ['project' => $project]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?= $this->partial('process', ['process' => $process]) ?>
<?= $this->partial('faq', ['faq' => $faq]) ?>
<?= $this->partial('cta') ?>
