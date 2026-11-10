<?php
/**
 * Takvim — panel etkinlik günlüğü (otomatik). Equation FullCalendar 5 tasarımı.
 * @var App\Core\View $this
 * @var array $categories  ActivityLog::CATEGORIES
 * @var array $counts      kategori => toplam kayıt
 * @var int $today
 * @var int $week
 * @var int $total
 */
$this->layout('main');
?>
<?php $this->start('styles') ?>
<link href="<?= $this->vendor('src/plugins/src/fullcalendar/fullcalendar.min.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor('src/plugins/css/light/fullcalendar/custom-fullcalendar.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor('src/plugins/css/dark/fullcalendar/custom-fullcalendar.css') ?>" rel="stylesheet">
<?php $this->stop() ?>

<div class="row">
    <?php foreach ([['Bugün', $today, 'clock', 'primary'], ['Son 7 gün', $week, 'calendar', 'success'], ['Toplam kayıt', $total, 'layers', 'warning']] as [$label, $value, $ico, $color]): ?>
        <div class="col-xl-4 col-md-4 col-12 layout-spacing">
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
        <div class="widget widget-content-area calendar-widget">
            <div class="calendar-filters mb-3" id="calendarFilters">
                <?php foreach ($categories as $key => [$label, $color, $ico]): ?>
                    <label class="cal-filter cal-filter-<?= e($color) ?>">
                        <input type="checkbox" value="<?= e($key) ?>" checked>
                        <span><?= icon($ico, 14) ?> <?= e($label) ?> <b><?= (int) ($counts[$key] ?? 0) ?></b></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="calendar" id="activityCalendar"></div>
            <div class="form-text mt-3">Paneldeki işlemler otomatik olarak takvime eklenir. Bir güne tıklayınca o günün dökümü sağda görünür.</div>
        </div>
    </div>

    <div class="col-xl-4 col-12 layout-spacing">
        <div class="widget widget-content-area day-log">
            <div class="section-title"><?= icon('list') ?> <span id="dayLogTitle">Bugün</span></div>
            <ol class="day-log-list" id="dayLogList"><li class="day-log-empty">Yükleniyor...</li></ol>
        </div>
    </div>
</div>

<!-- Kayıt ayrıntısı -->
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><span class="badge" id="activityModalBadge"></span> <span class="text-muted ms-1" id="activityModalTime"></span></p>
                <p class="mb-0" id="activityModalDetail"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-dark" data-bs-dismiss="modal">Kapat</button>
                <a href="#" class="btn btn-primary" id="activityModalLink"><?= icon('external-link', 16) ?> İlgili sayfaya git</a>
            </div>
        </div>
    </div>
</div>

<?php $this->start('scripts') ?>
<script src="<?= $this->vendor('src/plugins/src/fullcalendar/fullcalendar.min.js') ?>"></script>
<script src="<?= $this->vendor('src/plugins/src/fullcalendar/locales/tr.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var feed = <?= json_encode(admin_url('takvim/olaylar'), JSON_UNESCAPED_SLASHES) ?>;
    var filters = document.querySelectorAll('#calendarFilters input');
    var selected = function () { return [].slice.call(filters).filter(function (i) { return i.checked; }).map(function (i) { return i.value; }).join(','); };
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var ymd = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
    var modal = window.bootstrap ? new bootstrap.Modal(document.getElementById('activityModal')) : null;
    var currentDay = new Date();

    function showEvent(p, title) {
        document.getElementById('activityModalTitle').textContent = title;
        var badge = document.getElementById('activityModalBadge');
        badge.className = 'badge badge-light-' + p.color; badge.textContent = p.label;
        document.getElementById('activityModalTime').textContent = p.date + ' · ' + p.time;
        document.getElementById('activityModalDetail').textContent = p.detail || '';
        var link = document.getElementById('activityModalLink');
        link.classList.toggle('d-none', !p.url); if (p.url) link.href = p.url;
        if (modal) modal.show();
    }

    // Sağdaki günlük: seçili günün işlemleri saat sırasıyla
    function loadDay(date) {
        currentDay = date;
        var next = new Date(date); next.setDate(next.getDate() + 1);
        var isToday = ymd(date) === ymd(new Date());
        document.getElementById('dayLogTitle').textContent = isToday ? 'Bugün' : date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long' });
        var list = document.getElementById('dayLogList');
        fetch(feed + '?start=' + ymd(date) + 'T00:00:00&end=' + ymd(next) + 'T00:00:00&kategori=' + selected(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                if (!rows.length) { list.innerHTML = '<li class="day-log-empty">Bu gün kayıtlı işlem yok.</li>'; return; }
                list.innerHTML = rows.slice().reverse().map(function (e) {
                    var p = e.extendedProps;
                    return '<li class="day-log-item day-log-' + p.color + '" data-id="' + e.id + '">'
                        + '<span class="day-log-time">' + esc(p.time) + '</span>'
                        + '<span class="day-log-body"><strong>' + esc(e.title) + '</strong>'
                        + '<small>' + esc(p.label) + (p.detail ? ' · ' + esc(p.detail) : '') + '</small></span></li>';
                }).join('');
                [].forEach.call(list.querySelectorAll('.day-log-item'), function (li, i) {
                    var e = rows.slice().reverse()[i];
                    li.addEventListener('click', function () { showEvent(e.extendedProps, e.title); });
                });
            })
            .catch(function () { list.innerHTML = '<li class="day-log-empty">Kayıtlar yüklenemedi.</li>'; });
    }

    var calendar = new FullCalendar.Calendar(document.getElementById('activityCalendar'), {
        locale: 'tr',
        initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
        buttonText: { today: 'Bugün', month: 'Ay', week: 'Hafta', list: 'Liste' },
        height: 'auto',
        dayMaxEvents: 3,
        moreLinkContent: function (arg) { return '+' + arg.num + ' işlem daha'; },
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        displayEventEnd: false,
        nowIndicator: true,
        noEventsContent: 'Bu aralıkta kayıtlı işlem yok',
        events: function (info, success, failure) {
            fetch(feed + '?start=' + encodeURIComponent(info.startStr) + '&end=' + encodeURIComponent(info.endStr) + '&kategori=' + selected(), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); }).then(success).catch(failure);
        },
        eventClick: function (info) { info.jsEvent.preventDefault(); showEvent(info.event.extendedProps, info.event.title); },
        dateClick: function (info) { loadDay(info.date); },
        navLinks: true,
        navLinkDayClick: function (date) { loadDay(date); }
    });
    calendar.render();
    loadDay(new Date());

    [].forEach.call(filters, function (f) {
        f.addEventListener('change', function () { calendar.refetchEvents(); loadDay(currentDay); });
    });
});
</script>
<?php $this->stop() ?>
