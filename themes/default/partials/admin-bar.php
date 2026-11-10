<?php
/**
 * Bakım modu açıkken yalnızca giriş yapmış yöneticiye görünen uyarı çubuğu.
 * Ziyaretçiler bu sırada bakım sayfasını görür.
 */
if (empty($adminMaintenanceBar)) return;
?>
<div class="admin-bar" role="status">
    <div class="container admin-bar-inner">
        <span><?= icon('tool', 16) ?> <strong>Bakım modu açık.</strong> Ziyaretçiler bakım sayfasını görüyor; siz yönetici olarak giriş yaptığınız için siteyi normal görüyorsunuz.</span>
        <span class="admin-bar-actions">
            <a href="<?= e(url('bakimda.php')) ?>" target="_blank" class="btn btn-sm btn-ghost"><?= icon('eye', 14) ?> Bakım sayfasını gör</a>
            <form method="post" action="<?= e(admin_url('bakim-modu')) ?>" data-ajax data-ajax-success="reload" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-primary" data-confirm-title="Siteyi yayına al" data-confirm-variant="info" data-confirm-ok="Evet, yayına al" data-confirm="Bakım modu kapatılacak ve site herkese açılacak."><?= icon('globe', 14) ?> Siteyi yayına al</button>
            </form>
            <a href="<?= e(admin_url()) ?>" class="btn btn-sm btn-ghost"><?= icon('settings', 14) ?> Panel</a>
        </span>
    </div>
</div>
