<?php
/**
 * Üst menü
 * @var App\Core\View $this
 * @var array $services  App\Support\ServiceCatalog::all()
 */
?>
<header class="navbar" id="navbar">
    <div class="container navbar-inner">
        <a href="<?= e(url()) ?>" class="brand" aria-label="Ana sayfa">
            <span class="brand-mark"><?= icon('code', 18) ?></span>
            <span class="brand-text"><?= e($site['domain'] ?: $site['title']) ?></span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Menüyü aç" aria-expanded="false" aria-controls="navMenu">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav-menu" id="navMenu">
            <a href="<?= e(url()) ?>" class="nav-link <?= is_active('', true) ? 'active' : '' ?>">Ana Sayfa</a>

            <!-- Hizmetler açılır menüsü -->
            <div class="nav-dropdown <?= is_active('hizmetler') ? 'active' : '' ?>">
                <button type="button" class="nav-link nav-dropdown-toggle <?= is_active('hizmetler') ? 'active' : '' ?>" aria-expanded="false" aria-haspopup="true">
                    Hizmetler <?= icon('chevron-down', 14) ?>
                </button>
                <div class="nav-dropdown-menu" role="menu">
                    <?php foreach ($services as $key => $svc): ?>
                        <a href="<?= e(url('hizmetler/' . $svc['slug'])) ?>" class="nav-dropdown-item" role="menuitem" style="--svc: <?= e($svc['color']) ?>">
                            <span class="nav-dropdown-icon"><?= icon($svc['icon'], 18) ?></span>
                            <span>
                                <strong><?= e($svc['title']) ?></strong>
                                <small><?= e($svc['tagline']) ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                    <div class="nav-dropdown-footer">
                        <a href="<?= e(url('hizmetler')) ?>">Tüm hizmetler & süreç <?= icon('arrow-right', 14) ?></a>
                    </div>
                </div>
            </div>

            <a href="<?= e(url('projeler')) ?>" class="nav-link <?= is_active('projeler') ? 'active' : '' ?>">Projeler</a>
            <a href="<?= e(url('hakkimda')) ?>" class="nav-link <?= is_active('hakkimda') ? 'active' : '' ?>">Hakkımda</a>
            <a href="<?= e(url('iletisim')) ?>" class="nav-link <?= is_active('iletisim') ? 'active' : '' ?>">İletişim</a>
            <?php if ($site['social']['github']): ?>
                <a href="<?= e($site['social']['github']) ?>" class="nav-link nav-icon" target="_blank" rel="noopener" aria-label="GitHub"><?= icon('github', 18) ?> <span class="nav-icon-text">GitHub</span></a>
            <?php endif; ?>
            <a href="<?= e(url('teklif-al')) ?>" class="btn btn-sm btn-glow <?= is_active('teklif-al') ? 'active' : '' ?>"><?= icon('send', 16) ?> Teklif Al</a>
        </nav>
    </div>
</header>
