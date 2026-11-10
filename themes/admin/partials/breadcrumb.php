<?php
/**
 * @var array $breadcrumbs  ['Başlık' => 'adres' | null]
 */
?>
<div class="page-meta d-flex flex-wrap justify-content-between align-items-center gap-2">
    <nav class="breadcrumb-style-one" aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(admin_url()) ?>">Panel</a></li>
            <?php foreach ($breadcrumbs as $label => $link): ?>
                <?php if ($link): ?>
                    <li class="breadcrumb-item"><a href="<?= e($link) ?>"><?= e($label) ?></a></li>
                <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($label) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?= $this->section('pageActions') ?>
</div>
