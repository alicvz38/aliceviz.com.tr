<?php
/**
 * Üst menü (Equation header)
 * @var App\Core\View $this
 * @var App\Models\User $authUser
 * @var bool $maintenance
 */
?>
<!--  BEGIN NAVBAR  -->
<div class="header-container">
    <header class="header navbar navbar-expand-sm expand-header">

        <a href="javascript:void(0);" class="sidebarCollapse" aria-label="Menü"><?= icon('menu') ?></a>

        <div class="search-animated toggle-search">
            <?= icon('search') ?>
            <form class="form-inline search-full form-inline search" role="search" method="get" action="<?= e(admin_url('projeler')) ?>">
                <div class="search-bar">
                    <input type="text" name="q" class="form-control search-form-control ml-lg-auto" placeholder="Projelerde ara..." value="<?= e($_GET['q'] ?? '') ?>">
                    <?= icon('x', 24, 'search-close') ?>
                </div>
            </form>
            <span class="badge badge-secondary">Ctrl + /</span>
        </div>

        <ul class="navbar-item flex-row ms-lg-auto ms-0 align-items-center">

            <li class="nav-item me-2 d-none d-md-block">
                <form method="post" action="<?= e(admin_url('bakim-modu')) ?>" class="m-0 js-maintenance-form" data-ajax>
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm <?= $maintenance ? 'btn-warning' : 'btn-success' ?> maintenance-btn"
                            title="<?= $maintenance ? 'Bakım modunu kapat ve siteyi yayına al' : 'Siteyi bakım moduna al' ?>"
                            data-confirm-on="Site bakım moduna alınsın mı? Ziyaretçiler bakım sayfasını görecek."
                            data-confirm-off="Site yayına alınsın mı?"
                            data-confirm="<?= $maintenance ? 'Site yayına alınsın mı?' : 'Site bakım moduna alınsın mı? Ziyaretçiler bakım sayfasını görecek.' ?>">
                        <?= icon($maintenance ? 'tool' : 'globe', 16) ?>
                        <span><?= $maintenance ? 'Bakım Modu Açık' : 'Site Yayında' ?></span>
                    </button>
                </form>
            </li>

            <li class="nav-item">
                <a href="<?= e(admin_url('mesajlar')) ?>" class="nav-link position-relative" title="Mesajlar">
                    <?= icon('mail') ?>
                    <?php $unreadCount = App\Models\Message::countUnread(); ?>
                    <span class="navbar-mail-badge <?= $unreadCount ? '' : 'd-none' ?>" id="navbar-badge-messages"><?= $unreadCount ? ($unreadCount > 999 ? '999+' : $unreadCount) : '' ?></span>                </a>
            </li>

            <li class="nav-item">
                <a href="<?= e(url()) ?>" target="_blank" class="nav-link" title="Siteyi görüntüle"><?= icon('external-link') ?></a>
            </li>

            <!-- app.js tema düğmesini arar; panel her zaman koyu temada kalır -->
            <li class="nav-item theme-toggle-item d-none">
                <a href="javascript:void(0);" class="nav-link theme-toggle"></a>
            </li>

            <li class="nav-item dropdown user-profile-dropdown order-lg-0 order-1">
                <a href="javascript:void(0);" class="nav-link dropdown-toggle user" id="userProfileDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar-container">
                        <div class="avatar avatar-sm avatar-indicators avatar-online">
                            <span class="avatar-title rounded-circle"><?= e($authUser?->initials() ?? 'A') ?></span>
                        </div>
                    </div>
                </a>

                <div class="dropdown-menu position-absolute" aria-labelledby="userProfileDropdown">
                    <div class="user-profile-section">
                        <div class="media mx-auto">
                            <div class="emoji me-2">&#x1F44B;</div>
                            <div class="media-body">
                                <h5><?= e($authUser?->name) ?></h5>
                                <p>@<?= e($authUser?->username) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-item">
                        <a href="<?= e(admin_url('hesabim')) ?>"><?= icon('user') ?> <span>Hesabım</span></a>
                    </div>
                    <div class="dropdown-item">
                        <a href="<?= e(admin_url('ayarlar')) ?>"><?= icon('settings') ?> <span>Site Ayarları</span></a>
                    </div>
                    <div class="dropdown-item">
                        <form method="post" action="<?= e(admin_url('cikis')) ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn-logout"><?= icon('log-out') ?> <span>Çıkış Yap</span></button>
                        </form>
                    </div>
                </div>
            </li>
        </ul>
    </header>
</div>
<!--  END NAVBAR  -->
