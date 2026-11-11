<?php
/**
 * Proje tanıtımı: ekran görüntüleri sırayla, başlık ve açıklamalarıyla. Tıklanınca tam ekran galeri (app.js).
 * @var App\Core\View $this
 * @var App\Models\Project $project
 * @var App\Models\ProjectShot[] $shots
 */
$this->layout('main');
?>
<section class="page-header showcase-header">
    <div class="container">
        <a href="<?= e($project->url()) ?>" class="link-back"><?= icon('arrow-left', 16) ?> <?= e($project->title) ?></a>
        <p class="eyebrow">Proje tanıtımı</p>
        <h1><?= e($project->title) ?></h1>
        <?php if ($project->summary): ?><p class="lead"><?= e($project->summary) ?></p><?php endif; ?>
        <div class="hero-actions">
            <?php if ($project->github_url): ?>
                <a href="<?= e($project->github_url) ?>" class="btn btn-primary" target="_blank" rel="noopener"><?= icon('github', 18) ?> GitHub'da İncele</a>
            <?php endif; ?>
            <?php if ($project->demo_url): ?>
                <a href="<?= e($project->demo_url) ?>" class="btn btn-ghost" target="_blank" rel="noopener"><?= icon('external-link', 18) ?> Canlı Demo</a>
            <?php endif; ?>
            <a href="<?= e($project->url()) ?>" class="btn btn-ghost"><?= icon('file-text', 18) ?> Proje Ayrıntıları</a>
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <ol class="showcase" data-gallery>
            <?php foreach ($shots as $i => $shot): ?>
                <li class="showcase-item <?= $i % 2 ? 'is-reverse' : '' ?>">
                    <button type="button" class="showcase-image glass" data-gallery-item="<?= $i ?>"
                            data-src="<?= e($shot->imageUrl()) ?>" data-title="<?= e($shot->title) ?>" data-caption="<?= e((string) $shot->caption) ?>"
                            aria-label="<?= e($shot->title ?: 'Görseli büyüt') ?>">
                        <img src="<?= e($shot->imageUrl()) ?>" alt="<?= e($shot->title) ?>" loading="<?= $i < 2 ? 'eager' : 'lazy' ?>">
                        <span class="showcase-zoom"><?= icon('external-link', 16) ?> Büyüt</span>
                    </button>
                    <div class="showcase-text">
                        <span class="showcase-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <?php if ($shot->title): ?><h2><?= e($shot->title) ?></h2><?php endif; ?>
                        <?php if ($shot->caption): ?><p><?= nl2br(e($shot->caption)) ?></p><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="cta-box glass showcase-cta">
            <div>
                <h3>Benzer bir proje mi düşünüyorsunuz?</h3>
                <p>Web sitesi veya masaüstü uygulamanız için ücretsiz teklif alın.</p>
            </div>
            <a href="<?= e(url('teklif-al')) ?>" class="btn btn-primary"><?= icon('send', 18) ?> Teklif Al</a>
        </div>
    </div>
</section>

<!-- Tam ekran galeri -->
<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-label="Görsel galerisi">
    <button type="button" class="lightbox-close" data-lb="close" aria-label="Kapat">×</button>
    <button type="button" class="lightbox-nav is-prev" data-lb="prev" aria-label="Önceki"><?= icon('arrow-left', 22) ?></button>
    <figure class="lightbox-figure">
        <img id="lightboxImage" alt="">
        <figcaption><strong id="lightboxTitle"></strong><span id="lightboxCaption"></span><small id="lightboxCount"></small></figcaption>
    </figure>
    <button type="button" class="lightbox-nav is-next" data-lb="next" aria-label="Sonraki"><?= icon('arrow-right', 22) ?></button>
</div>
