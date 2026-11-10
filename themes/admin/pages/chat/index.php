<?php
/**
 * Panel > Canlı Destek: sohbet listesi, anlık yazışma, ziyaretçi bilgileri ve ayarlar
 * @var App\Core\View $this
 * @var int $selected
 * @var array $settings
 * @var bool $realtime
 */
$this->layout('main');
$s = static fn (string $k, string $d = '') => $settings[$k] ?? $d;
?>
<?php $this->start('pageActions') ?>
<div class="d-flex align-items-center gap-2">
    <span class="badge <?= $realtime ? 'badge-light-success' : (App\Support\Realtime::installed() ? 'badge-light-warning' : 'badge-light-info') ?>" title="<?= $realtime ? 'Mesajlar WebSocket ile anında iletiliyor' : (App\Support\Realtime::installed() ? 'Canlı sunucu kapalı: mesajlar birkaç saniyede bir kontrol ediliyor' : 'Mesajlar birkaç saniyede bir otomatik kontrol ediliyor') ?>">
        <?= $realtime ? 'Anlık (WebSocket)' : (App\Support\Realtime::installed() ? 'Yoklama modu' : 'Otomatik yenileme') ?>
    </span>
    <a href="<?= e(url()) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('external-link', 16) ?> Sitede gör</a>
</div>
<?php $this->stop() ?>

<?php if (!$realtime && App\Support\Realtime::installed()): ?>
    <div class="alert alert-light-warning mb-4">
        <?= icon('zap') ?> Canlı sunucu kapalı; sohbet yine çalışır ama mesajlar 2-3 saniye gecikmeyle gelir.
        Anlık iletim için <code>realtime\baslat.bat</code> dosyasını çalıştırın.
    </div>
<?php endif; ?>

<!-- Memnuniyet özeti -->
<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area chat-stats" id="chatStats">
            <div class="chat-stats-main">
                <span class="chat-stats-avg" id="statAvg"><?= $stats['avg'] !== null ? number_format($stats['avg'], 1, ',', '') : '–' ?></span>
                <div>
                    <div class="chat-stats-stars" id="statStars"><?= $stats['avg'] !== null ? App\Models\ChatConversation::stars((int) round($stats['avg'])) : '☆☆☆☆☆' ?></div>
                    <small class="text-muted"><span id="statCount"><?= (int) $stats['count'] ?></span> değerlendirme · ortalama memnuniyet</small>
                </div>
            </div>
            <div class="chat-stats-dist" id="statDist">
                <?php for ($n = 5; $n >= 1; $n--): $pct = $stats['count'] ? round($stats['dist'][$n] / $stats['count'] * 100) : 0; ?>
                    <div class="chat-dist-row" data-star="<?= $n ?>">
                        <span><?= $n ?> ★</span>
                        <div class="progress"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
                        <small><?= (int) $stats['dist'][$n] ?></small>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<div class="row chat-admin" id="chatApp" data-base="<?= e(admin_url('canli-destek')) ?>" data-selected="<?= (int) $selected ?>">
    <!-- Sohbetler -->
    <div class="col-xl-3 col-lg-4 col-12 layout-spacing">
        <div class="widget widget-content-area chat-list-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="section-title mb-0"><?= icon('message-square') ?> Sohbetler</div>
            </div>
            <div class="btn-group btn-group-sm w-100 mb-2" role="group" id="chatFilter">
                <button type="button" class="btn btn-outline-primary active" data-filter="open">Açık <span class="badge bg-dark ms-1" data-count="open">0</span></button>
                <button type="button" class="btn btn-outline-primary" data-filter="closed">Kapalı <span class="badge bg-dark ms-1" data-count="closed">0</span></button>
                <button type="button" class="btn btn-outline-primary" data-filter="all">Tümü</button>
            </div>
            <input type="search" class="form-control form-control-sm mb-2" id="chatSearch" placeholder="Ad veya e-posta ara...">
            <ul class="chat-list" id="chatList"><li class="chat-list-empty">Yükleniyor...</li></ul>
        </div>
    </div>

    <!-- Yazışma -->
    <div class="col-xl-6 col-lg-8 col-12 layout-spacing">
        <div class="widget widget-content-area chat-window" id="chatWindow">
            <div class="chat-empty" id="chatEmpty">
                <?= icon('message-square', 48) ?>
                <h5>Bir sohbet seçin</h5>
                <p class="text-muted mb-0">Ziyaretçiler sitenin sağ altındaki düğmeden size yazdığında burada görünür.</p>
            </div>
            <div class="chat-active d-none" id="chatActive">
                <div class="chat-window-head">
                    <span class="mail-avatar" id="chatAvatar">?</span>
                    <div class="flex-grow-1 min-w-0">
                        <strong id="chatName" class="d-block text-white text-truncate"></strong>
                        <small id="chatState" class="text-muted"></small>
                    </div>
                    <form method="post" id="chatCloseForm" data-ajax data-ajax-success="none" class="m-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-warning btn-sm" data-confirm-title="Sohbeti kapat" data-confirm-variant="warning" data-confirm-ok="Kapat"
                                data-confirm="Sohbet kapatılacak. Ziyaretçi yeniden yazarsa tekrar açılır."><?= icon('check', 14) ?> Kapat</button>
                    </form>
                    <form method="post" id="chatDeleteForm" data-ajax data-ajax-success="none" class="m-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger btn-sm" title="Sil" data-confirm-title="Sohbeti sil" data-confirm-variant="danger"
                                data-confirm="Sohbet ve tüm mesajları kalıcı olarak silinecek."><?= icon('trash', 14) ?></button>
                    </form>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-typing-admin d-none" id="chatTypingAdmin"><span></span><span></span><span></span> yazıyor...</div>
                <div class="chat-closed-note d-none" id="chatClosedNote">
                    <div><?= icon('check-circle', 16) ?> <strong>Bu sohbet kapatıldı.</strong> <span class="text-muted">Kapalı sohbete mesaj gönderilemez; ziyaretçi yeni bir sohbet başlatabilir.</span></div>
                    <div class="chat-closed-rating" id="chatClosedRating"></div>
                </div>
                <form class="chat-reply" id="chatReply">
                    <textarea id="chatReplyInput" class="form-control" rows="2" placeholder="Yanıtınızı yazın... (Enter: gönder, Shift+Enter: yeni satır)"></textarea>
                    <button type="submit" class="btn btn-primary"><?= icon('send', 16) ?></button>
                </form>
                <div class="chat-quick" id="chatQuick">
                    <button type="button" class="btn btn-sm btn-outline-secondary">Merhaba! Size nasıl yardımcı olabilirim?</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary">Projenizi biraz daha anlatır mısınız?</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary">Detaylı teklif için e-posta adresinizi paylaşır mısınız?</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary">Teşekkür ederim, iyi günler!</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ziyaretçi bilgileri + ayarlar -->
    <div class="col-xl-3 col-12 layout-spacing">
        <div class="widget widget-content-area mb-4 d-none" id="chatInfo">
            <div class="section-title"><?= icon('user') ?> Ziyaretçi</div>
            <dl class="quote-facts mb-0">
                <dt>E-posta</dt><dd id="infoEmail">-</dd>
                <dt>Başladığı sayfa</dt><dd id="infoPage">-</dd>
                <dt>Cihaz</dt><dd id="infoDevice">-</dd>
                <dt>IP</dt><dd id="infoIp">-</dd>
                <dt>Başlangıç</dt><dd id="infoStarted">-</dd>
                <dt class="d-none" id="infoClosedT">Bitiş</dt><dd class="d-none" id="infoClosed">-</dd>
                <dt class="d-none" id="infoRatingT">Değerlendirme</dt><dd class="d-none" id="infoRating">-</dd>
            </dl>
            <a href="#" id="infoMail" class="btn btn-outline-primary btn-sm w-100 mt-1 d-none"><?= icon('mail', 14) ?> E-posta ile yaz</a>
        </div>

        <form method="post" action="<?= e(admin_url('canli-destek/ayarlar')) ?>" class="widget widget-content-area" data-ajax data-ajax-success="none">
            <?= csrf_field() ?>
            <div class="section-title"><?= icon('settings') ?> Ayarlar</div>
            <div class="switch form-switch-custom switch-inline form-switch-success mb-3 d-block">
                <input class="switch-input" type="checkbox" role="switch" id="chat_enabled" name="chat_enabled" value="1" <?= $s('chat_enabled', '1') === '1' ? 'checked' : '' ?>>
                <label class="switch-label" for="chat_enabled">Sitede canlı destek düğmesini göster</label>
            </div>
            <div class="mb-3">
                <label class="form-label" for="chat_welcome">Karşılama mesajı</label>
                <textarea class="form-control" id="chat_welcome" name="chat_welcome" rows="3" maxlength="500"><?= e($s('chat_welcome')) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="chat_offline">Çevrimdışı mesajı</label>
                <textarea class="form-control" id="chat_offline" name="chat_offline" rows="3" maxlength="500"><?= e($s('chat_offline')) ?></textarea>
                <div class="form-text">Panel kapalıyken ziyaretçiye gösterilir; ziyaretçiden e-posta istenir ve size e-posta bildirimi gider.</div>
            </div>
            <button class="btn btn-primary w-100"><?= icon('check', 16) ?> Kaydet</button>
        </form>
    </div>
</div>

<?php $this->start('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var app = document.getElementById('chatApp');
    var base = app.dataset.base;
    var $ = function (id) { return document.getElementById(id); };
    var current = parseInt(app.dataset.selected, 10) || 0;
    var filter = 'open';
    var items = [];
    var seen = {}, lastId = 0, typingTimer = null, lastTypingSent = 0, pollTimer = null, listTimer = null;

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function stars(n) { return '★★★★★'.slice(0, n) + '☆☆☆☆☆'.slice(0, 5 - n); }

    // Memnuniyet özetini güncelle
    function renderStats(r) {
        if (!r) return;
        $('statAvg').textContent = r.avg !== null ? String(r.avg).replace('.', ',') : '–';
        $('statStars').textContent = r.avg !== null ? stars(Math.round(r.avg)) : '☆☆☆☆☆';
        $('statCount').textContent = r.count;
        document.querySelectorAll('#statDist .chat-dist-row').forEach(function (row) {
            var n = r.dist[row.dataset.star] || 0;
            row.querySelector('.progress-bar').style.width = (r.count ? Math.round(n / r.count * 100) : 0) + '%';
            row.querySelector('small').textContent = n;
        });
    }
    function initials(n) { return (n || '?').trim().split(/\s+/).slice(0, 2).map(function (p) { return p.charAt(0).toLocaleUpperCase('tr'); }).join(''); }

    // ------------------------------------------------------------ Liste
    function renderList() {
        var q = $('chatSearch').value.toLocaleLowerCase('tr');
        var list = items.filter(function (c) { return !q || (c.name + ' ' + c.email).toLocaleLowerCase('tr').indexOf(q) > -1; });
        $('chatList').innerHTML = list.length ? list.map(function (c) {
            return '<li class="chat-list-item' + (c.id === current ? ' active' : '') + (c.unread ? ' unread' : '') + '" data-id="' + c.id + '">' +
                '<span class="mail-avatar">' + esc(initials(c.name)) + (c.online ? '<i class="chat-online"></i>' : '') + '</span>' +
                '<span class="chat-list-text"><span class="d-flex justify-content-between gap-2"><strong class="text-truncate">' + esc(c.name) + '</strong><small class="text-muted">' + esc(c.time) + '</small></span>' +
                '<span class="d-flex justify-content-between gap-2"><small class="text-truncate">' + (c.last_from === 'admin' ? 'Siz: ' : '') + esc(c.last) + '</small>' +
                (c.unread ? '<span class="badge badge-danger">' + c.unread + '</span>'
                    : (c.rating ? '<span class="chat-list-rating" title="' + c.rating + '/5">' + stars(c.rating) + '</span>'
                    : (c.status === 'closed' ? '<span class="badge badge-light-dark">kapalı</span>' : ''))) + '</span></span></li>';
        }).join('') : '<li class="chat-list-empty">' + (filter === 'open' ? 'Açık sohbet yok.' : 'Sohbet bulunamadı.') + '</li>';
    }
    function loadList() {
        return Ajax.get(base + '/liste?durum=' + filter).then(function (res) {
            if (!res.ok) return;
            items = res.items;
            document.querySelectorAll('#chatFilter [data-count]').forEach(function (b) { b.textContent = res.counts[b.dataset.count]; });
            renderStats(res.rating);
            renderList();
        });
    }
    $('chatList').addEventListener('click', function (e) {
        var li = e.target.closest('.chat-list-item');
        if (li) open(parseInt(li.dataset.id, 10));
    });
    $('chatSearch').addEventListener('input', renderList);
    document.querySelectorAll('#chatFilter [data-filter]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#chatFilter [data-filter]').forEach(function (x) { x.classList.toggle('active', x === b); });
            filter = b.dataset.filter;
            loadList();
        });
    });

    // ------------------------------------------------------------ Sohbet
    function addMessage(m) {
        if (seen[m.id]) return;
        seen[m.id] = true;
        lastId = Math.max(lastId, m.id);
        var row = document.createElement('div');
        row.className = 'chat-line ' + (m.sender === 'admin' ? 'me' : (m.sender === 'system' ? 'system' : 'them'));
        row.innerHTML = '<div class="chat-line-bubble"></div>' + (m.sender !== 'system' ? '<time>' + esc(m.time) + ' · ' + esc(m.date) + '</time>' : '');
        row.firstChild.textContent = m.body;
        $('chatMessages').appendChild(row);
        $('chatMessages').scrollTop = $('chatMessages').scrollHeight;
        if (m.sender === 'visitor') showTyping(false);
    }
    // Sistem notu (ziyaretçi bitirdi / değerlendirdi) gelince başlık ve bilgi kartını tazele
    document.addEventListener('admin:chat', function (e) {
        if (e.detail.type === 'chat' && e.detail.message.sender === 'system' && e.detail.message.conv === current) {
            setTimeout(fetchMessages, 300);
        }
    });
    function showTyping(on) {
        $('chatTypingAdmin').classList.toggle('d-none', !on);
        clearTimeout(typingTimer);
        if (on) typingTimer = setTimeout(function () { $('chatTypingAdmin').classList.add('d-none'); }, 4500);
    }
    function setInfo(c) {
        $('chatName').textContent = c.name;
        $('chatAvatar').textContent = initials(c.name);
        $('chatState').innerHTML = (c.online ? '<span class="text-success">● Çevrimiçi</span>' : '○ Çevrimdışı') + (c.status === 'closed' ? ' · <span class="text-warning">Kapalı</span>' : '');
        $('infoEmail').innerHTML = c.email ? '<a href="mailto:' + esc(c.email) + '">' + esc(c.email) + '</a>' : '<span class="text-muted">belirtmedi</span>';
        $('infoPage').textContent = c.page || '-';
        $('infoDevice').textContent = c.device || '-';
        $('infoIp').textContent = c.ip || '-';
        $('infoStarted').textContent = c.started;
        $('infoMail').classList.toggle('d-none', !c.mail_url);
        if (c.mail_url) $('infoMail').href = c.mail_url;
        $('chatCloseForm').action = base + '/' + c.id + '/kapat';
        // Kapalı sohbet salt okunur: yanıt alanı gizlenir
        var closed = c.status === 'closed';
        $('chatCloseForm').classList.toggle('d-none', closed);
        $('chatReply').classList.toggle('d-none', closed);
        $('chatQuick').classList.toggle('d-none', closed);
        $('chatClosedNote').classList.toggle('d-none', !closed);
        if (closed) showTyping(false);
        var ratingHtml = c.rating
            ? '<span class="chat-closed-stars">' + stars(c.rating) + '</span> <strong>' + c.rating + '/5 · ' + esc(c.rating_label) + '</strong>' + (c.rating_comment ? '<div class="text-muted mt-1">“' + esc(c.rating_comment) + '”</div>' : '')
            : '<span class="text-muted">Henüz değerlendirilmedi.</span>';
        $('chatClosedRating').innerHTML = closed ? ratingHtml : '';
        ['infoClosedT', 'infoClosed'].forEach(function (id) { $(id).classList.toggle('d-none', !c.closed); });
        $('infoClosed').textContent = c.closed || '-';
        ['infoRatingT', 'infoRating'].forEach(function (id) { $(id).classList.toggle('d-none', !closed); });
        $('infoRating').innerHTML = closed ? ratingHtml : '';
        $('chatDeleteForm').action = base + '/' + c.id + '/sil';
    }
    function fetchMessages() {
        if (!current) return Promise.resolve();
        var id = current;
        return Ajax.get(base + '/' + id + '/mesajlar?after=' + lastId).then(function (res) {
            if (id !== current) return;
            if (!res.ok) { Ajax.showMessages(res); close(); loadList(); return; }
            setInfo(res.conversation);
            res.messages.forEach(addMessage);
            if (res.typing) showTyping(true);
            var it = items.find(function (c) { return c.id === id; });
            if (it && it.unread) { it.unread = 0; renderList(); }
        });
    }
    function open(id) {
        current = id;
        seen = {}; lastId = 0;
        $('chatMessages').innerHTML = '';
        $('chatEmpty').classList.add('d-none');
        $('chatActive').classList.remove('d-none');
        $('chatInfo').classList.remove('d-none');
        history.replaceState(null, '', base + '?sohbet=' + id);
        renderList();
        fetchMessages().then(function () { $('chatReplyInput').focus(); });
        schedule();
    }
    function close() {
        current = 0;
        $('chatEmpty').classList.remove('d-none');
        $('chatActive').classList.add('d-none');
        $('chatInfo').classList.add('d-none');
        history.replaceState(null, '', base);
    }

    // Yanıt gönder
    function send(text) {
        text = (text || '').trim();
        if (!text || !current) return;
        Ajax.post(base + '/' + current + '/gonder', { body: text }).then(function (res) {
            if (res.ok) { addMessage(res.message); loadList(); return; }
            Ajax.showMessages(res);
            if (res.status === 409) fetchMessages(); // sohbet bu arada kapanmış: salt okunur görünüme geç
        });
    }
    $('chatReply').addEventListener('submit', function (e) {
        e.preventDefault();
        send($('chatReplyInput').value);
        $('chatReplyInput').value = '';
    });
    $('chatReplyInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('chatReply').requestSubmit(); }
    });
    $('chatReplyInput').addEventListener('input', function () {
        if (current && Date.now() - lastTypingSent > 2500 && this.value.trim()) {
            lastTypingSent = Date.now();
            Ajax.post(base + '/' + current + '/yaziyor', {});
        }
    });
    $('chatQuick').addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (b) send(b.textContent);
    });

    // Kapat / sil
    $('chatCloseForm').addEventListener('ajax:success', function (e) { e.preventDefault(); Ajax.showMessages(e.detail); fetchMessages(); loadList(); });
    $('chatDeleteForm').addEventListener('ajax:success', function (e) { e.preventDefault(); Ajax.showMessages(e.detail); close(); loadList(); });

    // ------------------------------------------------------------ Anlık güncelleme
    // WebSocket olayları (admin.js'den): yeni mesaj / yazıyor
    var beep = function () {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)(), o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = 740; g.gain.setValueAtTime(0.0001, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.1, ctx.currentTime + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);
            o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + 0.32);
        } catch (err) {}
    };
    document.addEventListener('admin:chat', function (e) {
        var d = e.detail;
        if (d.type === 'chat') {
            if (d.message.conv === current) fetchMessages(); // okundu da işaretlenir
            if (d.message.sender === 'visitor') {
                if (d.message.conv !== current || document.hidden) beep();
                if (d.message.conv !== current) Ajax.toast((d.name || 'Ziyaretçi') + ': ' + d.message.body.slice(0, 80), 'info', 6000);
            }
            loadList();
        }
        if (d.type === 'typing' && d.conv === current) showTyping(true);
    });

    // Yedek: WebSocket yoksa kısa aralıklarla kontrol
    function schedule() {
        clearTimeout(pollTimer);
        pollTimer = setTimeout(function () {
            if (!window.adminRealtimeLive && !document.hidden) fetchMessages();
            schedule();
        }, window.adminRealtimeLive ? 30000 : 2500);
    }
    function scheduleList() {
        clearTimeout(listTimer);
        listTimer = setTimeout(function () {
            if (!document.hidden) loadList();
            scheduleList();
        }, window.adminRealtimeLive ? 20000 : 5000); // çevrimiçi göstergeleri için seyrek de olsa yenile
    }

    loadList().then(function () { if (current) open(current); });
    schedule();
    scheduleList();
});
</script>
<?php $this->stop() ?>
