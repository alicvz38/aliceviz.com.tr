<?php
/**
 * Site Ayarları bölüm sekmeleri (her bölüm ayrı sayfa)
 * @var array $sections  SettingController::SECTIONS
 * @var string $section  açık bölüm
 */
?>
<nav class="settings-tabs mb-4" aria-label="Ayar bölümleri">
    <?php foreach ($sections as $key => [$label, $ico, $desc]): ?>
        <a href="<?= e(admin_url($key === 'genel' ? 'ayarlar' : 'ayarlar/' . $key)) ?>"
           class="settings-tab <?= $key === $section ? 'active' : '' ?>" title="<?= e($desc) ?>"
           <?= $key === $section ? 'aria-current="page"' : '' ?>>
            <?= icon($ico, 16) ?> <span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
