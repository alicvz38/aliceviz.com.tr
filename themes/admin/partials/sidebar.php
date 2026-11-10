<?php
/**
 * Sol menü — öğeler AdminController::menu() içinden gelir.
 * @var App\Core\View $this
 * @var array $menu
 */
$prefix = (string) App\Core\Config::get('admin.prefix', 'admin');
$isActive = static fn (array $item) => isset($item['path']) && is_active(trim($prefix . '/' . $item['path'], '/'), !empty($item['exact']));
?>
<!--  BEGIN SIDEBAR  -->
<div class="sidebar-wrapper sidebar-theme">
    <nav id="sidebar">
        <div class="navbar-nav theme-brand flex-row text-center">
            <div class="nav-logo">
                <div class="nav-item theme-logo">
                    <a href="<?= e(admin_url()) ?>" aria-label="Panel ana sayfa">
                        <!-- Sitedeki logo ile aynı (themes/default navbar .brand-mark) -->
                        <span class="site-brand-mark"><?= icon('code', 18) ?></span>
                    </a>
                </div>
                <div class="nav-item theme-text">
                    <a href="<?= e(admin_url()) ?>" class="nav-link"><?= e(setting('site_domain', '') ?: $siteTitle) ?></a>
                </div>
            </div>
            <div class="nav-item sidebar-toggle">
                <div class="btn-toggle sidebarCollapse"><?= icon('chevrons-left') ?></div>
            </div>
        </div>
        <div class="shadow-bottom"></div>

        <ul class="list-unstyled menu-categories" id="accordionExample">
            <?php foreach ($menu as $i => $item): ?>
                <?php if (isset($item['heading'])): ?>
                    <li class="menu menu-heading">
                        <div class="heading"><?= icon('minus') ?><span><?= e($item['heading']) ?></span></div>
                    </li>
                    <?php continue; ?>
                <?php endif; ?>

                <?php
                $children = $item['children'] ?? [];
                $active = $isActive($item) || (bool) array_filter($children, $isActive);
                $id = 'menu-' . $i;
                // Okunmamış sayısı rozeti (AJAX ile düzenli olarak güncellenir — admin.js)
                $badge = isset($item['badge_id'])
                    ? '<span class="badge badge-danger menu-badge ' . (!empty($item['badge']) ? '' : 'd-none') . '" id="' . e($item['badge_id']) . '">' . (int) ($item['badge'] ?? 0) . '</span>'
                    : '';
                ?>

                <?php if ($children): ?>
                    <li class="menu <?= $active ? 'active' : '' ?>">
                        <a href="#<?= $id ?>" data-bs-toggle="collapse" aria-expanded="<?= $active ? 'true' : 'false' ?>" class="dropdown-toggle">
                            <div><?= icon($item['icon']) ?><span><?= e($item['label']) ?></span></div>
                            <div class="d-flex align-items-center gap-1"><?= $badge ?><?= icon('chevron-right') ?></div>
                        </a>
                        <ul class="collapse submenu list-unstyled <?= $active ? 'show' : '' ?>" id="<?= $id ?>" data-bs-parent="#accordionExample">
                            <?php foreach ($children as $child): ?>
                                <li class="<?= $isActive($child) ? 'active' : '' ?>">
                                    <a href="<?= e(admin_url($child['path'])) ?>"> <?= e($child['label']) ?> </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="menu <?= $active ? 'active' : '' ?>">
                        <a href="<?= e($item['url'] ?? admin_url($item['path'])) ?>" <?= !empty($item['target']) ? 'target="' . e($item['target']) . '"' : '' ?> aria-expanded="<?= $active ? 'true' : 'false' ?>" class="dropdown-toggle">
                            <div><?= icon($item['icon']) ?><span><?= e($item['label']) ?></span></div>
                            <?= $badge ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>
<!--  END SIDEBAR  -->
