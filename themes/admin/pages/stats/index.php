<?php
/**
 * İstatistikler — günlük tekil ziyaretçi, proje incelemeleri, en çok incelenen projeler ve sayfalar.
 * Canlı: WebSocket ya da uzun bekleme (bekle) değişikliği anında bildirir (admin:counters); ikisi de yoksa 2 sn'de bir sürüm yoklanır.
 * @var App\Core\View $this
 * @var int $days
 * @var array $ranges
 * @var string $version
 * @var array $totals   today, yesterday, visitors, projects, pages
 * @var array $daily    'Y-m-d' => [site, project, page]
 * @var array $projects
 * @var array $pages
 */
use App\Controllers\Admin\StatsController;

$this->layout('main');
$diff = $totals['today'] - $totals['yesterday'];
$diffText = static fn (int $d) => $d === 0 ? 'Dünle aynı' : ($d > 0 ? '+' . $d . ' düne göre' : $d . ' düne göre');
?>
<?php $this->start('styles') ?>
<link href="<?= $this->vendor('src/plugins/src/apex/apexcharts.css') ?>" rel="stylesheet">
<style>
    .stats-range { display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
    .stats-live { display: inline-flex; align-items: center; gap: .45rem; font-size: .78rem; font-weight: 600; color: #22c55e; padding: .3rem .7rem; border-radius: 99px; background: rgba(34,197,94,.1); margin-right: .3rem; }
    .stats-live i { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 0 rgba(34,197,94,.6); animation: statsPulse 1.8s infinite; }
    .stats-live.is-off { color: #e2a03f; background: rgba(226,160,63,.1); }
    .stats-live.is-off i { background: #e2a03f; animation: none; }
    @keyframes statsPulse { 70% { box-shadow: 0 0 0 8px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); } }
    .stats-bar { height: 6px; border-radius: 99px; background: rgba(255,255,255,.06); overflow: hidden; margin-top: 6px; }
    .stats-bar span { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #22d3ee, #a855f7); transition: width .6s ease; }
    .stats-table td { vertical-align: middle; }
    .stats-table .num { font-variant-numeric: tabular-nums; font-weight: 700; color: #fff !important; }
    .stats-diff { display: block; font-size: 11.5px; color: #22c55e; margin-top: 3px; }
    .stats-diff.is-down { color: #e7515a; }
    .stats-diff.is-same { color: #888ea8; }
    .stats-table tr.stats-flash td { animation: statsFlash 2s ease; }
    @keyframes statsFlash { 0%, 30% { background: rgba(34,211,238,.14); } 100% { background: transparent; } }
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
    <span class="stats-live" id="statsLive" title="Yeni ziyaretler 1-2 saniye içinde burada görünür"><i></i> Canlı</span>
    <?php foreach ($ranges as $d => $label): ?>
        <a href="<?= e(admin_url('istatistikler?gun=' . $d)) ?>" class="btn btn-sm <?= $d === $days ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<?php $this->stop() ?>

<div class="row">
    <?php foreach ([
        ['today', 'Bugün tekil ziyaretçi', 'users', 'primary', $diffText($diff)],
        ['visitors', $ranges[$days] . ' ziyaretçi', 'trending-up', 'success', ''],
        ['projects', 'Proje incelemesi', 'folder', 'warning', ''],
        ['pages', 'Sayfa görüntüleme', 'eye', 'info', ''],
    ] as [$key, $label, $ico, $color, $sub]): ?>
        <div class="col-xl-3 col-md-6 col-12 layout-spacing">
            <div class="widget stat-card">
                <div class="stat-icon bg-soft-<?= $color ?>"><?= icon($ico) ?></div>
                <div>
                    <h3 data-odo="<?= (int) $totals[$key] ?>" data-stat="<?= $key ?>"><?= (int) $totals[$key] ?></h3>
                    <p><?= e($label) ?><?php if ($key === 'today'): ?><span class="stats-diff<?= $diff < 0 ? ' is-down' : ($diff === 0 ? ' is-same' : '') ?>" id="statsDiff"><?= e($sub) ?></span><?php endif; ?></p>
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

<div class="row" id="statsTables">
    <?= $this->partial('stats-tables', ['projects' => $projects, 'pages' => $pages]) ?>
</div>

<?php $this->start('scripts') ?>
<script src="<?= $this->vendor('src/plugins/src/apex/apexcharts.min.js') ?>"></script>
<script>
(function () {
    var cfg = {
        days: <?= (int) $days ?>,
        version: <?= json_encode($version) ?>,
        dataUrl: <?= json_encode(admin_url('istatistikler/veri?gun=' . $days)) ?>,
        versionUrl: <?= json_encode(admin_url('istatistikler/surum')) ?>,
        chart: <?= json_encode(StatsController::chart($daily), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
    };

    // ---------------------------------------------------------------- Grafik
    var many = cfg.chart.labels.length > 31, ticks = cfg.chart.labels.length > 14 ? 10 : undefined;
    var chart = new ApexCharts(document.getElementById('visitorChart'), {
        chart: { type: 'area', height: 320, background: 'transparent', foreColor: '#888ea8', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit', animations: { dynamicAnimation: { speed: 500 } } },
        theme: { mode: 'dark' },
        series: [
            { name: 'Tekil ziyaretçi', data: cfg.chart.visitors },
            { name: 'Proje incelemesi', data: cfg.chart.projects }
        ],
        xaxis: { categories: cfg.chart.labels, tickAmount: ticks, labels: { rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: function (v) { return Math.round(v); } } },
        colors: ['#22d3ee', '#a855f7'],
        stroke: { curve: 'smooth', width: 2.5 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .02, stops: [0, 90, 100] } },
        dataLabels: { enabled: false },
        markers: { size: many ? 0 : 3, strokeWidth: 0, hover: { size: 5 } },
        grid: { borderColor: 'rgba(255,255,255,.06)', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: { theme: 'dark', y: { formatter: function (v) { return v + ' kişi'; } } }
    });
    chart.render();

    // ---------------------------------------------------------------- Canlı yenileme
    var busy = false, pending = false;
    function rowValues(root) {
        var map = {};
        root.querySelectorAll('tr[data-key]').forEach(function (tr) { map[tr.dataset.key] = tr.querySelector('.num').textContent; });
        return map;
    }
    function refresh() {
        if (busy) { pending = true; return; }
        busy = true;
        Ajax.get(cfg.dataUrl).then(function (res) {
            if (!res || !res.ok) return;
            cfg.version = res.version;
            // Kartlar: kayan sayaçla
            Object.keys(res.totals).forEach(function (k) {
                var el = document.querySelector('[data-stat="' + k + '"]');
                if (el) AdminOdometer.set(el, res.totals[k]);
            });
            var d = res.totals.today - res.totals.yesterday;
            var diffEl = document.getElementById('statsDiff');
            diffEl.textContent = d === 0 ? 'Dünle aynı' : (d > 0 ? '+' + d : d) + ' düne göre';
            diffEl.className = 'stats-diff' + (d < 0 ? ' is-down' : d === 0 ? ' is-same' : '');
            // Grafik (gün dönmüşse etiketler de güncellenir)
            chart.updateOptions({ xaxis: { categories: res.chart.labels } }, false, false);
            chart.updateSeries([{ data: res.chart.visitors }, { data: res.chart.projects }]);
            // Tablolar: değişen satır kısa süre parlar
            var box = document.getElementById('statsTables'), before = rowValues(box);
            box.innerHTML = res.tables;
            box.querySelectorAll('tr[data-key]').forEach(function (tr) {
                if (before[tr.dataset.key] !== tr.querySelector('.num').textContent) tr.classList.add('stats-flash');
            });
        }).finally(function () {
            busy = false;
            if (pending) { pending = false; refresh(); }
        });
    }

    var live = !!window.adminRealtimeLive, badge = document.getElementById('statsLive');
    function setBadge() {
        badge.classList.toggle('is-off', document.hidden);
        badge.lastChild.textContent = document.hidden ? ' Duraklatıldı' : ' Canlı';
    }

    // 1) WebSocket: sunucu bir değişiklik olunca hemen bildirir
    document.addEventListener('admin:realtime', function (e) { live = !!e.detail.live; });
    document.addEventListener('admin:counters', function (e) {
        if (e.detail.stats_version && e.detail.stats_version !== cfg.version) refresh();
    });

    // 2) WebSocket yoksa (paylaşımlı hosting): sayfa açıkken 2 sn'de bir hafif sürüm yoklaması
    setInterval(function () {
        if (live || document.hidden || busy || (window.adminWaitOk && window.adminWaitOk())) return;
        Ajax.get(cfg.versionUrl).then(function (res) {
            if (res && res.ok && res.version !== cfg.version) refresh();
        });
    }, 2000);

    // Sekmeye geri dönünce bekletmeden güncelle
    document.addEventListener('visibilitychange', function () {
        setBadge();
        if (!document.hidden) refresh();
    });
    setBadge();
})();
</script>
<?php $this->stop() ?>
