<?php /** @var array $process [ikon, başlık, açıklama] */ ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Nasıl çalışıyorum?</span>
                <h2>Fikirden teslime 6 adım</h2>
            </div>
        </div>
        <ol class="process-grid">
            <?php foreach ($process as $i => [$ico, $title, $text]): ?>
                <li class="process-step glass">
                    <span class="process-num"><?= $i + 1 ?></span>
                    <span class="process-icon"><?= icon($ico, 20) ?></span>
                    <h3><?= e($title) ?></h3>
                    <p><?= e($text) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
