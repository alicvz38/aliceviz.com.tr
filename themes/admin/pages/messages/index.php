<?php
/**
 * Mesajlar — Gmail klasörleri (otomatik senkronize) + DataTable (sunucu taraflı) + toplu işlemler
 * @var App\Core\View $this
 * @var array $folders  MailFetcher::folders()
 * @var array $counts   Message::folderCounts()
 * @var string $folderKey
 * @var string $search
 * @var bool $configured
 * @var bool $imapEnabled
 * @var string $imapError
 * @var int $imapLast
 */
$this->layout('main');
$icons = ['inbox' => 'inbox', 'important' => 'bookmark', 'sent' => 'send', 'drafts' => 'file-text', 'spam' => 'alert-triangle', 'trash' => 'trash', 'all' => 'layers'];
// Her klasörde "okunmamış/toplam" (0 olsa bile gösterilir; okunmamış varsa kırmızı)
$countBadge = static function (string $key) use ($counts): string {
    $c = $counts[$key] ?? ['total' => 0, 'unread' => 0];
    $u = (int) $c['unread'];
    $t = (int) $c['total'];
    $f = static fn (int $n) => number_format($n, 0, ',', '.');
    return '<span class="folder-count' . ($u > 0 ? ' has-unread' : '') . '" data-count="' . e($key) . '" title="' . $f($u) . ' okunmamış / ' . $f($t) . ' e-posta">'
        . '<b>' . $f($u) . '</b><i>/</i>' . $f($t) . '</span>';
};
$system = array_filter($folders, static fn ($f) => $f['special'] !== null);
$labels = array_filter($folders, static fn ($f) => $f['special'] === null);
$progress = App\Services\MailFetcher::progress();
?>
<?php $this->start('pageActions') ?>
<a href="<?= e(admin_url('mesajlar/yeni')) ?>" class="btn btn-primary btn-sm"><?= icon('edit', 16) ?> Yeni E-posta</a>
<?php $this->stop() ?>

<?php if ($imapEnabled && $imapError): ?>
    <div class="alert alert-light-danger mb-4">
        <?= icon('alert-triangle') ?> <strong>E-posta senkronizasyonu hatası:</strong> <?= e($imapError) ?>
        — <a href="<?= e(admin_url('mesajlar/ayarlar')) ?>" class="alert-link">Ayarları kontrol edin</a>
    </div>
<?php endif; ?>

<?php if (!$configured): ?>
    <div class="alert alert-light-warning mb-4">
        <?= icon('alert-triangle') ?> <strong>E-posta hesabı henüz bağlanmadı.</strong>
        Site mesajları yine burada toplanır; posta kutusu klasörleriniz ve e-posta gönderimi için
        <a href="<?= e(admin_url('mesajlar/ayarlar')) ?>" class="alert-link">E-posta Ayarları</a>'nı doldurun.
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-3 col-lg-4 col-12 layout-spacing">
        <div class="widget widget-content-area">
            <a href="<?= e(admin_url('mesajlar/yeni')) ?>" class="btn btn-primary w-100 mb-3"><?= icon('edit', 16) ?> Yeni E-posta</a>

            <div class="list-group mail-folders" id="mailFolders">
                <?php foreach ($system as $key => $f): ?>
                    <a href="<?= e(admin_url('mesajlar?klasor=' . rawurlencode($key))) ?>" data-folder="<?= e($key) ?>" data-label="<?= e($f['label']) ?>" class="list-group-item list-group-item-action <?= $folderKey === $key ? 'active' : '' ?>">
                        <span><?= icon($icons[$key] ?? 'folder') ?><?= e($f['label']) ?></span>
                        <?= $countBadge($key) ?>
                    </a>
                    <?php if ($key === 'inbox'): ?>
                        <a href="<?= e(admin_url('mesajlar?klasor=starred')) ?>" data-folder="starred" data-label="Yıldızlı" class="list-group-item list-group-item-action <?= $folderKey === 'starred' ? 'active' : '' ?>">
                            <span><?= icon('star') ?>Yıldızlı</span>
                            <?= $countBadge('starred') ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($labels): ?>
                    <div class="mail-folders-heading">Etiketler</div>
                    <?php foreach ($labels as $key => $f): ?>
                        <a href="<?= e(admin_url('mesajlar?klasor=' . rawurlencode($key))) ?>" data-folder="<?= e($key) ?>" data-label="<?= e($f['label']) ?>" class="list-group-item list-group-item-action <?= $folderKey === $key ? 'active' : '' ?>">
                            <span><?= icon('tag') ?><?= e($f['label']) ?></span>
                            <?= $countBadge($key) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="form-text mt-2 px-1 folder-count-hint">Sayılar: <b class="text-danger">okunmamış</b> / toplam</div>

                <a href="<?= e(admin_url('mesajlar/ayarlar')) ?>" class="list-group-item list-group-item-action mt-2"><span><?= icon('settings') ?>E-posta Ayarları</span></a>
            </div>

            <div class="form-text mt-3 d-flex align-items-center gap-2" id="syncInfo">
                <?php if ($imapEnabled): ?>
                    <span class="sync-dot"></span>
                    <span class="badge badge-light-success live-only" title="WebSocket canlı bağlantı: yeni e-postalar anında görünür">Canlı</span>
                    <span>Posta kutusuyla otomatik senkronize<?= $imapLast ? ' · ' . e(time_ago(date('Y-m-d H:i:s', $imapLast))) : '' ?></span>
                <?php else: ?>
                    <a href="<?= e(admin_url('mesajlar/ayarlar')) ?>">E-posta senkronizasyonunu açın</a>
                <?php endif; ?>
            </div>
            <div class="sync-progress mt-2 <?= $imapEnabled && !$progress['done'] ? '' : 'd-none' ?>" id="syncProgress">
                <div class="form-text mb-1">Eski e-postalar aktarılıyor: <strong id="syncProgressText"><?= (int) $progress['imported'] ?> / <?= (int) $progress['total'] ?></strong></div>
                <div class="progress" style="height:5px"><div class="progress-bar" id="syncProgressBar" style="width: <?= $progress['total'] ? min(100, round($progress['imported'] / $progress['total'] * 100)) : 0 ?>%"></div></div>
            </div>
        </div>
    </div>

    <div class="col-xl-9 col-lg-8 col-12 layout-spacing">
        <div class="widget widget-content-area">
            <!-- Toplu işlem çubuğu -->
            <div class="mail-toolbar">
                <h5 class="mail-toolbar-title" id="folderTitle"><?= e($folderKey === 'starred' ? 'Yıldızlı' : ($folders[$folderKey]['label'] ?? 'Gelen Kutusu')) ?></h5>
                <div class="mail-toolbar-actions">
                    <span class="text-muted small d-none" id="selectionInfo"></span>
                    <div class="btn-group btn-group-sm d-none" id="bulkActions" role="group" aria-label="Seçilenler">
                        <button type="button" class="btn btn-outline-secondary" data-bulk="okundu" title="Okundu yap"><?= icon('eye', 14) ?> Okundu</button>
                        <button type="button" class="btn btn-outline-secondary" data-bulk="okunmadi" title="Okunmadı yap"><?= icon('eye-off', 14) ?> Okunmadı</button>
                        <button type="button" class="btn btn-outline-secondary" data-bulk="yildizla" title="Yıldızla"><?= icon('star', 14) ?></button>
                        <button type="button" class="btn btn-outline-secondary" data-bulk="yildizi-kaldir" title="Yıldızı kaldır"><?= icon('star-fill', 14) ?></button>
                        <button type="button" class="btn btn-outline-danger" data-bulk="sil" title="Seçilenleri sil"><?= icon('trash', 14) ?> Sil</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="readAllBtn"><?= icon('check', 14) ?> Tümünü okundu işaretle</button>
                    <!-- Yalnızca Spam ve Çöp Kutusu'nda görünür -->
                    <button type="button" class="btn btn-sm btn-danger <?= $folderKey === 'spam' ? '' : 'd-none' ?>" data-empty="spam"><?= icon('trash', 14) ?> Spam klasörünü temizle</button>
                    <button type="button" class="btn btn-sm btn-danger <?= $folderKey === 'trash' ? '' : 'd-none' ?>" data-empty="trash"><?= icon('trash', 14) ?> Çöp kutusunu boşalt</button>
                </div>
            </div>
            <div class="form-text mb-2 <?= in_array($folderKey, ['trash', 'spam'], true) ? '' : 'd-none' ?>" id="trashNote">Buradan silinen e-postalar posta kutusundan da <strong>kalıcı olarak</strong> silinir.</div>

            <table id="mailTable" class="table mail-table dt-table-hover" style="width:100%">
                <thead>
                <tr>
                    <th class="no-sort" style="width:28px">
                        <div class="form-check form-check-primary m-0"><input class="form-check-input" type="checkbox" id="checkAll" title="Bu sayfadakilerin tümünü seç"></div>
                    </th>
                    <th class="no-sort" style="width:28px"></th>
                    <th style="width:24%">Kişi</th>
                    <th>Konu</th>
                    <th style="width:70px">Tarih</th>
                    <th class="no-sort text-end" style="width:70px"></th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<?php $this->start('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var base = <?= json_encode(admin_url('mesajlar')) ?>;
    var folder = <?= json_encode($folderKey) ?>;
    var selected = new Set();
    var lastVersion = null;

    var table = AdminDT('#mailTable', {
        serverSide: true,
        processing: true,
        searchDelay: 400,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[4, 'desc']],
        search: { search: <?= json_encode((string) $search) ?> },
        ajax: {
            url: base + '/veri',
            data: function (d) { d.klasor = folder; },
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        },
        columnDefs: [
            { targets: [0, 1, 5], orderable: false },
            { targets: [0, 1], className: 'mail-col-tight' },
            { targets: 5, className: 'text-end' }
        ],
        language: { sEmptyTable: 'Bu klasörde e-posta yok', sZeroRecords: 'Aramanıza uygun e-posta bulunamadı' }
    });

    // Klasör sayıları: "okunmamış/toplam" — 0 olsa da görünür, okunmamış varsa kırmızı
    function fmt(n) { return (n || 0).toLocaleString('tr-TR'); }
    function updateCounts(counts) {
        if (!counts) return;
        document.querySelectorAll('.folder-count[data-count]').forEach(function (b) {
            var c = counts[b.dataset.count] || { total: 0, unread: 0 };
            b.innerHTML = '<b>' + fmt(c.unread) + '</b><i>/</i>' + fmt(c.total);
            b.classList.toggle('has-unread', c.unread > 0);
            b.title = fmt(c.unread) + ' okunmamış / ' + fmt(c.total) + ' e-posta';
        });
    }

    // Spam / Çöp kutusunu boşalt (posta kutusundan da kalıcı silinir)
    document.querySelectorAll('[data-empty]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.dataset.empty;
            var badge = document.querySelector('.folder-count[data-count="' + key + '"]');
            var total = badge ? badge.textContent.split('/')[1] : '';
            var name = key === 'spam' ? 'Spam klasöründeki' : 'Çöp kutusundaki';
            Ajax.confirm(
                name + ' ' + (total ? total + ' ' : 'tüm ') + 'e-posta posta kutusundan da kalıcı olarak silinecek. Bu işlem geri alınamaz.',
                { title: key === 'spam' ? 'Spam klasörünü temizle' : 'Çöp kutusunu boşalt', ok: 'Evet, kalıcı olarak sil', danger: true }
            ).then(function (ok) {
                if (!ok) return;
                btn.disabled = true;
                btn.classList.add('btn-loading');
                Ajax.post(base + '/klasoru-bosalt', { klasor: key }).then(function (res) {
                    btn.disabled = false;
                    btn.classList.remove('btn-loading');
                    Ajax.showMessages(res);
                    if (res.ok) {
                        selected.clear();
                        if (res.data) updateCounts(res.data.counts);
                        table.ajax.reload();
                    }
                });
            });
        });
    });
    table.on('xhr.dt', function (e, settings, json) { if (json) updateCounts(json.counts); });

    // Seçim (sayfa değişse de korunur)
    function refreshSelection() {
        var n = selected.size;
        document.getElementById('bulkActions').classList.toggle('d-none', !n);
        var info = document.getElementById('selectionInfo');
        info.classList.toggle('d-none', !n);
        info.textContent = n + ' seçili';
        var boxes = document.querySelectorAll('#mailTable tbody .mail-check');
        var all = boxes.length && Array.prototype.every.call(boxes, function (c) { return c.checked; });
        document.getElementById('checkAll').checked = !!all;
    }
    table.on('draw.dt', function () {
        document.querySelectorAll('#mailTable tbody .mail-check').forEach(function (c) {
            c.checked = selected.has(c.value);
            c.closest('tr').classList.toggle('selected', c.checked);
        });
        refreshSelection();
    });
    document.getElementById('mailTable').addEventListener('change', function (e) {
        if (e.target.id === 'checkAll') {
            document.querySelectorAll('#mailTable tbody .mail-check').forEach(function (c) {
                c.checked = e.target.checked;
                c.closest('tr').classList.toggle('selected', c.checked);
                c.checked ? selected.add(c.value) : selected.delete(c.value);
            });
        } else if (e.target.classList.contains('mail-check')) {
            e.target.checked ? selected.add(e.target.value) : selected.delete(e.target.value);
            e.target.closest('tr').classList.toggle('selected', e.target.checked);
        }
        refreshSelection();
    });

    // Klasör değiştir
    document.querySelectorAll('#mailFolders [data-folder]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('#mailFolders [data-folder]').forEach(function (x) { x.classList.toggle('active', x === a); });
            folder = a.dataset.folder;
            document.getElementById('folderTitle').textContent = a.dataset.label;
            var note = document.getElementById('trashNote');
            if (note) note.classList.toggle('d-none', folder !== 'trash' && folder !== 'spam');
            // Temizle / boşalt düğmesi yalnızca kendi klasöründe
            document.querySelectorAll('[data-empty]').forEach(function (b) { b.classList.toggle('d-none', b.dataset.empty !== folder); });
            selected.clear();
            history.pushState({ folder: folder }, '', base + (folder === 'inbox' ? '' : '?klasor=' + encodeURIComponent(folder)));
            table.page(0).ajax.reload();
        });
    });
    window.addEventListener('popstate', function () {
        folder = new URLSearchParams(location.search).get('klasor') || 'inbox';
        document.querySelectorAll('#mailFolders [data-folder]').forEach(function (x) { x.classList.toggle('active', x.dataset.folder === folder); });
        document.querySelectorAll('[data-empty]').forEach(function (b) { b.classList.toggle('d-none', b.dataset.empty !== folder); });
        var note = document.getElementById('trashNote');
        if (note) note.classList.toggle('d-none', folder !== 'trash' && folder !== 'spam');
        table.page(0).ajax.reload();
    });

    // Toplu işlemler
    document.querySelectorAll('[data-bulk]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action = btn.dataset.bulk, ids = Array.from(selected);
            var go = function () {
                btn.disabled = true;
                Ajax.post(base + '/toplu', { action: action, ids: ids }).then(function (res) {
                    btn.disabled = false;
                    Ajax.showMessages(res);
                    if (res.ok) { selected.clear(); table.ajax.reload(null, false); }
                });
            };
            if (action !== 'sil') return go();
            var permanent = folder === 'trash' || folder === 'spam';
            Ajax.confirm(
                ids.length + ' e-posta ' + (permanent ? 'posta kutusundan kalıcı olarak silinecek.' : 'silinecek ve Çöp Kutusu\'na taşınacak.'),
                { title: 'Seçilenleri sil', ok: 'Evet, ' + ids.length + ' e-postayı sil', danger: true }
            ).then(function (ok) { if (ok) go(); });
        });
    });

    // Tümünü okundu işaretle (bu klasörde)
    document.getElementById('readAllBtn').addEventListener('click', function () {
        var btn = this;
        var label = document.querySelector('#mailFolders [data-folder="' + folder + '"]');
        Ajax.confirm(
            '"' + (label ? label.dataset.label : 'Bu klasör') + '" içindeki tüm e-postalar okundu olarak işaretlenecek (posta kutusu dahil).',
            { title: 'Tümünü okundu işaretle', ok: 'Evet, işaretle', variant: 'info' }
        ).then(function (ok) {
            if (!ok) return;
            btn.disabled = true;
            btn.classList.add('btn-loading');
            Ajax.post(base + '/tumunu-oku', { klasor: folder }).then(function (res) {
                btn.disabled = false;
                btn.classList.remove('btn-loading');
                Ajax.showMessages(res);
                if (res.ok) table.ajax.reload(null, false);
                var nb = document.getElementById('navbar-badge-messages');
                if (nb && res.data) { nb.textContent = res.data.unread; nb.classList.toggle('d-none', !res.data.unread); }
            });
        });
    });

    // Satır içi yıldız / okundu / sil -> tabloyu yerinde yenile
    document.addEventListener('ajax:success', function (e) {
        var f = e.target;
        if (f.classList && (f.classList.contains('js-star') || f.classList.contains('js-read') || f.classList.contains('js-delete'))) {
            if (f.classList.contains('js-delete')) Ajax.showMessages(e.detail);
            table.ajax.reload(null, false);
        }
    });

    // Canlı güncelleme: yeni e-posta geldiğinde (WebSocket veya yoklama) listeyi yenile
    document.addEventListener('admin:counters', function (e) {
        var d = e.detail;
        var info = document.querySelector('#syncInfo span:last-child');
        if (info && d.synced_ago) info.textContent = 'Posta kutusuyla otomatik senkronize · ' + d.synced_ago;
        if (d.progress) {
            var p = d.progress;
            document.getElementById('syncProgress').classList.toggle('d-none', !!p.done);
            document.getElementById('syncProgressText').textContent = p.imported + ' / ' + p.total;
            document.getElementById('syncProgressBar').style.width = (p.total ? Math.min(100, Math.round(p.imported / p.total * 100)) : 0) + '%';
        }
        if (lastVersion !== null && d.mail_version !== lastVersion && selected.size === 0) {
            table.ajax.reload(null, false);
        }
        lastVersion = d.mail_version;
    });
});
</script>
<?php $this->stop() ?>
