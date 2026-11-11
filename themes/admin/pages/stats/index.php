<?php
/**
 * İstatistikler — günlük tekil ziyaretçi, proje incelemeleri, en çok incelenen projeler ve sayfalar
 * @var App\Core\View $this
 * @var int $days
 * @var array $ranges
 * @var array $totals   today, yesterday, visitors, projects, pages
 * @var array $daily    'Y-m-d' => [site, project, page]
 * @var array $projects
 * @var array $pages
 */
use App\Support\Stats;

$this->layout('main');
$maxProject = max(1, (int) ($projects[0]['views'] ?? 1));
$maxPage = max(1, (int) ($pages[0]['views'] ?? 1));
$chart = [
    'labels'   => array_map(static fn ($d) => date('d.m', strtotime($d)), array_keys($daily)),
    'visitors' => array_column(array_values($daily), 'site'),
    'projects' => array_column(array_values($daily), 'project'),
];
$diff = $totals['today'] - $totals['yesterday'];
?>
<?php $this->start('styles') ?>
<link href="<?= $this->vendor('src/plugins/src/apex/apexcharts.css') ?>" rel="stylesheet">
<style>
    .stats-range { display: flex; gap: .4rem; flex-wrap: wrap; }
    .stats-bar { height: 6px; border-radius: 99px; background: rgba(255,255,255,.06); overflow: hidden; margin-top: 6px; }
    .stats-bar span { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #22d3ee, #a855f7); }
    .stats-table td { vertical-align: middle; }
    .stats-table .num { font-variant-numeric: tabular-nums; font-weight: 700; color: #fff; }
    .stats-rank { width: 28px; height: 28px; border-radius: 8px; display: inline-grid; place-items: center; font-weight: 700; font-size: .8rem; background: rgba(255,255,255,.06); }
    .stats-rank.r1 { background: rgba(251,191,36,.18); color: #fbbf24; }
    .stats-rank.r2 { background: rgba(203,213,225,.15); color: #cbd5e1; }
    .stats-rank.r3 { background: rgba(217,119,6,.18); color: #f59e0b; }
    .stats-note { font-size: .8rem; color: #888ea8; }
    #visitorChart { min-height: 320px; }
</style>
<?php $this->stop() ?>

<?php $this->start('pageActions') ?>
<div class="stats-range">
    <?php foreach ($ranges as $d => $label): ?>
        <a href="<?= e(admin_url('istatistikler?gun=' . $d)) ?>" class="btn btn-sm <?= $d === $days ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<?php $this->stop() ?>

<div class="row">
    <?php foreach ([
        ['Bugün tekil ziyaretçi', $totals['today'], 'users', 'primary', $diff === 0 ? 'Dünle aynı' : ($diff > 0 ? '+' . $diff . ' düne göre' : $diff . ' düne göre')],
        [$ranges[$days] . ' ziyaretçi', $totals['visitors'], 'trending-up', 'success', 'Her gün tekil sayılır'],
        ['Proje incelemesi', $totals['projects'], 'folder', 'warning', $ranges[$days]],
        ['Sayfa görüntüleme', $totals['pages'], 'eye', 'info', $ranges[$days]],
    ] as [$label, $value, $ico, $color, $sub]): ?>
        <div class="col-xl-3 col-md-6 col-12 layout-spacing">
            <div class="widget stat-card">
                <div class="stat-icon bg-soft-<?= $color ?>"><?= icon($ico) ?></div>
                <div>
                    <h3 data-odo="<?= (int) $value ?>"><?= (int) $value ?></h3>
                    <p><?= e($label) ?></p>
                    <small class="stats-note"><?= e($sub) ?></small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="section-title mb-0"><?= icon('bar-chart-2') ?> Günlük ziyaretçi ve proje incelemesi</div>
                <span class="stats-note">Aynı kişi gün içinde tekrar baksa da bir kez sayılır. Siz (panel girişi açıkken) ve arama motoru botları sayılmaz.</span>
            </div>
            <div id="visitorChart"></div>
        </div>
    </div>
</div>

<div class="row">
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
                            <tr>
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
                            <tr>
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
</div>

<?php $this->start('scripts') ?>
<script src="<?= $this->vendor('src/plugins/src/apex/apexcharts.min.js') ?>"></script>
<script>
(function () {
    var data = <?= json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var many = data.labels.length > 31;
    new ApexCharts(document.getElementById('visitorChart'), {
        chart: { type: 'area', height: 320, background: 'transparent', foreColor: '#888ea8', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
        theme: { mode: 'dark' },
        series: [
            { name: 'Tekil ziyaretçi', data: data.visitors },
            { name: 'Proje incelemesi', data: data.projects }
        ],
        xaxis: { categories: data.labels, tickAmount: many ? 12 : undefined, labels: { rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: function (v) { return Math.round(v); } } },
        colors: ['#22d3ee', '#a855f7'],
        stroke: { curve: 'smooth', width: 2.5 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .02, stops: [0, 90, 100] } },
        dataLabels: { enabled: false },
        markers: { size: many ? 0 : 3, strokeWidth: 0, hover: { size: 5 } },
        grid: { borderColor: 'rgba(255,255,255,.06)', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: { theme: 'dark', y: { formatter: function (v) { return v + ' kişi'; } } }
    }).render();
})();
</script>
<?php $this->stop() ?>
