<?php /** @var array $faq [soru, cevap] */ ?>
<section class="section pt-0">
    <div class="container narrow">
        <div class="section-head">
            <div>
                <span class="eyebrow">Sıkça sorulanlar</span>
                <h2>Merak ettikleriniz</h2>
            </div>
        </div>
        <div class="faq-list">
            <?php foreach ($faq as $i => [$q, $a]): ?>
                <details class="faq-item glass" <?= $i === 0 ? 'open' : '' ?>>
                    <summary><?= e($q) ?> <?= icon('chevron-down', 18) ?></summary>
                    <p><?= e($a) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
