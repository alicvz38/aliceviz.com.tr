<?php
/**
 * @var App\Core\View $this
 * @var array $stats
 * @var App\Models\Project[] $latest
 * @var array $languages
 * @var bool $maintenance
 */
$this->layout('main');
$cards = [
    ['Toplam Proje', $stats['total'], 'folder', 'primary'],
    ['Yayında', $stats['published'], 'eye', 'success'],
    ['Öne Çıkan', $stats['featured'], 'star', 'warning'],
    ['GitHub Yıldızı', $stats['stars'], 'github', 'info'],
];
$langTotal = array_sum($languages) ?: 1;
$langColor = static fn (string $lang) => (new App\Models\Project(['language' => $lang]))->languageColor();
?>
<?php $this->start('pageActions') ?>
<a href="<?= e(admin_url('projeler/ekle')) ?>" class="btn btn-primary btn-sm"><?= icon('plus', 16) ?> Yeni Proje</a>
<?php $this->stop() ?>

<?php if ($maintenance): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-arrow-right alert-icon-right alert-light-warning mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
            <span><?= icon('tool') ?> <strong>Site şu anda bakım modunda.</strong> Ziyaretçiler bakım sayfasını görüyor; siz giriş yaptığınız için siteyi normal görebilirsiniz.</span>
            <form method="post" action="<?= e(admin_url('bakim-modu')) ?>" class="m-0 js-maintenance-form" data-ajax>
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-success" data-confirm="Site yayına alınsın mı?">Siteyi Yayına Al</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($stats['unread'] || $stats['newQuotes']): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-light-info mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
            <span><?= icon('mail') ?>
                <?php if ($stats['unread']): ?><strong><?= (int) $stats['unread'] ?> okunmamış mesaj</strong><?php endif; ?>
                <?= $stats['unread'] && $stats['newQuotes'] ? ' ve ' : '' ?>
                <?php if ($stats['newQuotes']): ?><strong><?= (int) $stats['newQuotes'] ?> yeni teklif talebi</strong><?php endif; ?>
                sizi bekliyor.</span>
            <span class="d-flex gap-2">
                <?php if ($stats['unread']): ?><a href="<?= e(admin_url('mesajlar')) ?>" class="btn btn-sm btn-info">Mesajlar</a><?php endif; ?>
                <?php if ($stats['newQuotes']): ?><a href="<?= e(admin_url('teklifler?durum=new')) ?>" class="btn btn-sm btn-info">Teklifler</a><?php endif; ?>
            </span>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <?php foreach ($cards as [$label, $value, $ico, $color]): ?>
        <div class="col-xl-3 col-md-6 col-12 layout-spacing">
            <div class="widget stat-card">
                <div class="stat-icon bg-soft-<?= $color ?>"><?= icon($ico) ?></div>
                <div>
                    <h3 data-odo="<?= (int) $value ?>"><?= (int) $value ?></h3>
                    <p><?= e($label) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col-xl-8 col-12 layout-spacing">
        <div class="widget widget-content-area h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0"><?= icon('layers') ?> Son Eklenen Projeler</div>
                <a href="<?= e(admin_url('projeler')) ?>" class="btn btn-outline-primary btn-sm">Tümü</a>
            </div>
            <?php if ($latest): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Proje</th><th>Dil</th><th class="text-center">Durum</th><th class="text-end">Eklendi</th></tr></thead>
                        <tbody>
                        <?php foreach ($latest as $p): ?>
                            <tr>
                                <td><a href="<?= e(admin_url('projeler/' . $p->id . '/duzenle')) ?>"><?= e($p->title) ?></a></td>
                                <td><?php if ($p->language): ?><span class="lang-dot" style="background: <?= e($p->languageColor()) ?>"></span><?= e($p->language) ?><?php else: ?>-<?php endif; ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $p->is_published ? 'badge-light-success' : 'badge-light-dark' ?>"><?= $p->is_published ? 'Yayında' : 'Taslak' ?></span>
                                </td>
                                <td class="text-end text-muted"><?= e(time_ago($p->created_at)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Henüz proje yok. <a href="<?= e(admin_url('github')) ?>">GitHub'dan içe aktarın</a> veya <a href="<?= e(admin_url('projeler/ekle')) ?>">elle ekleyin</a>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4 col-12 layout-spacing">
        <div class="widget widget-content-area h-100">
            <div class="section-title"><?= icon('code') ?> Dil Dağılımı</div>
            <?php if ($languages): ?>
                <div class="language-bar mb-3">
                    <?php foreach ($languages as $lang => $count): ?>
                        <span style="width: <?= round($count / $langTotal * 100, 2) ?>%; background: <?= e($langColor($lang)) ?>" title="<?= e($lang) ?>"></span>
                    <?php endforeach; ?>
                </div>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($languages as $lang => $count): ?>
                        <li class="d-flex justify-content-between py-1">
                            <span><span class="lang-dot" style="background: <?= e($langColor($lang)) ?>"></span><?= e($lang) ?></span>
                            <span class="text-muted"><?= (int) $count ?> proje · %<?= round($count / $langTotal * 100) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">Yayında proje olduğunda burada dil dağılımı görünecek.</p>
            <?php endif; ?>

            <hr>
            <div class="section-title"><?= icon('zap') ?> Hızlı İşlemler</div>
            <div class="d-grid gap-2">
                <a href="<?= e(admin_url('github')) ?>" class="btn btn-outline-info"><?= icon('download', 16) ?> GitHub'dan İçe Aktar</a>
                <form method="post" action="<?= e(admin_url('github/senkronize')) ?>" class="d-grid" data-ajax data-ajax-success="reload">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-secondary"><?= icon('refresh', 16) ?> Yıldız/Fork Senkronize Et</button>
                </form>
                <a href="<?= e(admin_url('mesajlar/yeni')) ?>" class="btn btn-outline-primary"><?= icon('send', 16) ?> Yeni E-posta Gönder</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 col-12 layout-spacing">
        <div class="widget widget-content-area h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0"><?= icon('mail') ?> Son Mesajlar</div>
                <a href="<?= e(admin_url('mesajlar')) ?>" class="btn btn-outline-primary btn-sm">Gelen Kutusu</a>
            </div>
            <?php if ($latestMessages): ?>
                <ul class="mail-list">
                    <?php foreach ($latestMessages as $m): ?>
                        <li class="mail-item <?= $m->is_read ? '' : 'unread' ?>">
                            <span class="mail-avatar"><?= e($m->initials()) ?></span>
                            <a href="<?= e(admin_url('mesajlar/' . $m->id)) ?>" class="mail-body">
                                <div class="mail-from"><?= e($m->name ?: $m->email) ?></div>
                                <div class="mail-subject"><?= e($m->subject) ?></div>
                            </a>
                            <span class="mail-date"><?= e(time_ago($m->created_at)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">Henüz mesaj yok. Ziyaretçiler <a href="<?= e(url('iletisim')) ?>" target="_blank">iletişim sayfasından</a> size yazabilir.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-6 col-12 layout-spacing">
        <div class="widget widget-content-area h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0"><?= icon('inbox') ?> Son Teklif Talepleri</div>
                <a href="<?= e(admin_url('teklifler')) ?>" class="btn btn-outline-primary btn-sm">Tümü</a>
            </div>
            <?php if ($latestQuotes): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Müşteri</th><th>Hizmet</th><th class="text-center">Durum</th><th class="text-end">Tarih</th></tr></thead>
                        <tbody>
                        <?php foreach ($latestQuotes as $q): ?>
                            <tr>
                                <td><a href="<?= e(admin_url('teklifler/' . $q->id)) ?>"><?= e($q->name) ?></a></td>
                                <td><?= e($q->serviceLabel()) ?></td>
                                <td class="text-center"><span class="badge badge-light-<?= e($q->statusColor()) ?>"><?= e($q->statusLabel()) ?></span></td>
                                <td class="text-end text-muted"><?= e(time_ago($q->created_at)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Henüz teklif talebi yok. Ziyaretçiler <a href="<?= e(url('teklif-al')) ?>" target="_blank">Teklif Al</a> sayfasından talep gönderebilir.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
