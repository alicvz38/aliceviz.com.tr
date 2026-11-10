<?php
/**
 * Hizmet kartı
 * @var string $key
 * @var array $svc
 */
?>
<article class="service-card glass" style="--svc: <?= e($svc['color']) ?>">
    <span class="service-icon"><?= icon($svc['icon'], 26) ?></span>
    <h3><?= e($svc['title']) ?></h3>
    <p><?= e($svc['intro']) ?></p>
    <ul class="check-list">
        <?php foreach (array_slice($svc['features'], 0, 4) as [, $title]): ?>
            <li><?= icon('check', 16) ?> <?= e($title) ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="service-actions">
        <a href="<?= e(url('hizmetler/' . $svc['slug'])) ?>" class="btn btn-ghost btn-sm">Detaylar <?= icon('arrow-right', 14) ?></a>
        <a href="<?= e(url('teklif-al?hizmet=' . $key)) ?>" class="btn btn-primary btn-sm"><?= icon('send', 14) ?> Teklif Al</a>
    </div>
</article>
