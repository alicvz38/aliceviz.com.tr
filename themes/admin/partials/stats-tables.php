<?php
/**
 * İstatistikler: en çok incelenen projeler ve sayfalar (canlı yenilemede yeniden çizilir)
 * @var array $projects
 * @var array $pages
 */
use App\Support\Stats;

$maxProject = max(1, (int) ($projects[0]['views'] ?? 1));
$maxPage = max(1, (int) ($pages[0]['views'] ?? 1));
?>
<div class="col-xl-6 col-12 layout-spacing">
    <div class="widget widget-content-area h-100">
        <div class="section-title"><?= icon('folder') ?> En çok incelenen projeler</div>
        <?php if (!$projects): ?>
            <p class="text-muted mb-0">Bu dönemde henüz proje incelemesi yok.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table stats-table mb-0">
                    <thead><tr><th style="width:44px">#</th><th>Proje</th><th class="text-end">Bugün</th><th class="text-end">Toplam</th></tr></thead>
                    <tbody>
                    <?php foreach ($projects as $i => $p): ?>
                        <tr data-key="p<?= (int) $p['id'] ?>">
                            <td><span class="stats-rank r<?= $i + 1 ?>"><?= $i + 1 ?></span></td>
                            <td>
                                <a href="<?= e(url('projeler/' . $p['slug'])) ?>" target="_blank" rel="noopener" class="text-white"><?= e($p['title']) ?></a>
                                <?php if (!$p['is_published']): ?><span class="badge badge-light-secondary ms-1">Yayında değil</span><?php endif; ?>
                                <div class="stats-bar"><span style="width:<?= round($p['views'] / $maxProject * 100) ?>%"></span></div>
                            </td>
                            <td class="text-end"><?= (int) $p['today'] ?></td>
                            <td class="text-end num"><?= (int) $p['views'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="col-xl-6 col-12 layout-spacing">
    <div class="widget widget-content-area h-100">
        <div class="section-title"><?= icon('file-text') ?> En çok görüntülenen sayfalar</div>
        <?php if (!$pages): ?>
            <p class="text-muted mb-0">Bu dönemde henüz sayfa görüntülemesi yok.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table stats-table mb-0">
                    <thead><tr><th style="width:44px">#</th><th>Sayfa</th><th class="text-end">Bugün</th><th class="text-end">Toplam</th></tr></thead>
                    <tbody>
                    <?php foreach ($pages as $i => $p): ?>
                        <tr data-key="s<?= e(md5((string) $p['ref'])) ?>">
                            <td><span class="stats-rank r<?= $i + 1 ?>"><?= $i + 1 ?></span></td>
                            <td>
                                <a href="<?= e(url($p['ref'])) ?>" target="_blank" rel="noopener" class="text-white"><?= e(Stats::pageLabel((string) $p['ref'])) ?></a>
                                <div class="stats-bar"><span style="width:<?= round($p['views'] / $maxPage * 100) ?>%"></span></div>
                            </td>
                            <td class="text-end"><?= (int) $p['today'] ?></td>
                            <td class="text-end num"><?= (int) $p['views'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
