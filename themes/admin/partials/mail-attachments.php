<?php
/**
 * E-posta ekleri: sürükle-bırak / dosya seç, parça parça yükleme (ilerleme çubuğuyla), kaldırma.
 * Yüklenen her dosya forma "attachments[]" olarak eklenir; gönderirken sunucu küçükleri ek,
 * büyükleri indirme bağlantısı yapar (App\Services\MailAttachments).
 * Kullanım: <form ...> ... $this->partial('mail-attachments') ... </form>
 */
use App\Services\MailAttachments;

$uid = 'att' . bin2hex(random_bytes(3));
?>
<div class="mail-att mb-3" id="<?= $uid ?>"
     data-upload="<?= e(admin_url('mesajlar/ek')) ?>"
     data-discard="<?= e(admin_url('mesajlar/ek/')) ?>"
     data-chunk="<?= (int) MailAttachments::chunkSize() ?>"
     data-max="<?= (int) MailAttachments::MAX_FILE ?>"
     data-max-files="<?= (int) MailAttachments::MAX_FILES ?>"
     data-inline="<?= MailAttachments::ALL_AS_CARDS ? 0 : (int) MailAttachments::INLINE_TOTAL ?>">
    <label class="mail-att-drop">
        <input type="file" multiple hidden>
        <?= icon('paperclip', 16) ?> <b>Dosya ekle</b> <span class="text-muted">ya da buraya sürükleyin · dosya başına en fazla <?= e(MailAttachments::human(MailAttachments::MAX_FILE)) ?></span>
    </label>
    <div class="mail-att-list"></div>
    <div class="mail-att-note text-muted small" hidden></div>
</div>
<style>
    .mail-att-drop { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; padding: .65rem .9rem; border: 1px dashed #3b3f5c; border-radius: 10px; cursor: pointer; font-size: .85rem; color: #bfc9d4; transition: .15s; margin: 0; }
    .mail-att-drop:hover, .mail-att.is-over .mail-att-drop { border-color: #4361ee; background: rgba(67,97,238,.08); color: #fff; }
    .mail-att-list { display: grid; gap: .45rem; margin-top: .5rem; }
    .mail-att-item { display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: .6rem; padding: .5rem .7rem; border: 1px solid #1b2e4b; border-radius: 10px; background: #0e1726; font-size: .84rem; }
    .mail-att-item .nm { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #e0e6ed; }
    .mail-att-item .sz { color: #888ea8; white-space: nowrap; }
    .mail-att-item .bar { grid-column: 1 / -1; height: 4px; border-radius: 99px; background: rgba(255,255,255,.07); overflow: hidden; }
    .mail-att-item .bar span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #22d3ee, #4361ee); transition: width .25s; }
    .mail-att-item.is-done .bar { display: none; }
    .mail-att-item.is-error { border-color: rgba(231,81,90,.5); }
    .mail-att-item.is-error .sz { color: #e7515a; }
    .mail-att-item .rm { background: none; border: 0; color: #888ea8; padding: 0 .2rem; cursor: pointer; font-size: 1.1rem; line-height: 1; }
    .mail-att-item .rm:hover { color: #e7515a; }
    .mail-att-item .tag { font-size: .7rem; padding: .1rem .45rem; border-radius: 99px; background: rgba(34,211,238,.12); color: #22d3ee; white-space: nowrap; }
    .mail-att-item .tag.link { background: rgba(226,160,63,.14); color: #e2a03f; }
</style>
<script>
(function () {
    var box = document.getElementById('<?= $uid ?>');
    var form = box.closest('form');
    var input = box.querySelector('input[type=file]'), list = box.querySelector('.mail-att-list'), note = box.querySelector('.mail-att-note');
    var CHUNK = +box.dataset.chunk, MAX = +box.dataset.max, MAXN = +box.dataset.maxFiles, INLINE = +box.dataset.inline;
    var queue = [], active = 0, items = [];

    function human(b) { return b >= 1048576 ? (b / 1048576).toFixed(b >= 10485760 ? 0 : 1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function uid() { var a = new Uint8Array(16); crypto.getRandomValues(a); return Array.prototype.map.call(a, function (x) { return ('0' + x.toString(16)).slice(-2); }).join(''); }
    function toast(m, t) { if (window.Ajax) Ajax.toast(m, t || 'danger'); }

    // Gönderimde hangileri ek, hangileri bağlantı olacak (sunucudaki kuralın aynısı: küçükten büyüğe)
    function refreshPlan() {
        var done = items.filter(function (it) { return it.token; }).sort(function (a, b) { return a.size - b.size; });
        var sum = 0, links = 0;
        done.forEach(function (it) {
            var inline = sum + it.size <= INLINE;
            if (inline) sum += it.size; else links++;
            it.el.querySelector('.tag').className = 'tag' + (inline ? '' : ' link');
            it.el.querySelector('.tag').textContent = inline ? 'Ek' : 'E-postada kart';
        });
        note.hidden = !links;
        note.textContent = links ? 'Dosyalar e-postanın içinde kart olarak görünür; alıcı karta tıklayınca indirir<?= MailAttachments::days() ? ' (' . MailAttachments::days() . ' gün geçerli)' : '' ?>.' : '';
    }

    function add(files) {
        Array.prototype.forEach.call(files, function (f) {
            if (items.length >= MAXN) { toast('En fazla ' + MAXN + ' dosya ekleyebilirsiniz.'); return; }
            if (f.size > MAX) { toast(f.name + ': dosya en fazla ' + human(MAX) + ' olabilir.'); return; }
            if (!f.size) { toast(f.name + ': dosya boş.'); return; }
            var el = document.createElement('div');
            el.className = 'mail-att-item';
            el.innerHTML = '<span class="tag">Yükleniyor</span><span class="nm" title="' + esc(f.name) + '">' + esc(f.name) + '</span><span class="sz">' + human(f.size) + '</span>'
                + '<button type="button" class="rm" title="Kaldır" aria-label="Kaldır">×</button><div class="bar"><span></span></div>';
            list.appendChild(el);
            var it = { file: f, el: el, size: f.size, id: uid(), token: null, cancelled: false };
            items.push(it);
            el.querySelector('.rm').addEventListener('click', function () { remove(it); });
            queue.push(it);
        });
        next();
    }

    function remove(it) {
        it.cancelled = true;
        if (it.token) {
            Ajax.post(box.dataset.discard + it.token + '/sil', {});
            var h = form.querySelector('input[name="attachments[]"][value="' + it.token + '"]');
            if (h) h.remove();
        }
        it.el.remove();
        items = items.filter(function (x) { return x !== it; });
        refreshPlan();
    }

    function next() {
        if (active || !queue.length) return;
        var it = queue.shift();
        if (it.cancelled) return next();
        active = 1;
        upload(it, 0);
    }

    function upload(it, offset, tries) {
        if (it.cancelled) { active = 0; return next(); }
        var fd = new FormData();
        fd.append('chunk', it.file.slice(offset, offset + CHUNK), 'parca');
        fd.append('upload_id', it.id);
        fd.append('offset', offset);
        fd.append('total', it.file.size);
        fd.append('name', it.file.name);
        Ajax.post(box.dataset.upload, fd).then(function (res) {
            if (!res.ok) throw new Error(res.message || 'Yüklenemedi');
            var sent = Math.min(offset + CHUNK, it.file.size);
            it.el.querySelector('.bar span').style.width = Math.round(sent / it.file.size * 100) + '%';
            if (res.done) {
                it.token = res.file.token;
                it.el.classList.add('is-done');
                var h = document.createElement('input');
                h.type = 'hidden'; h.name = 'attachments[]'; h.value = it.token;
                form.appendChild(h);
                refreshPlan();
                active = 0;
                return next();
            }
            upload(it, sent, 0);
        }).catch(function (err) {
            // Bağlantı koptuysa aynı parçayı birkaç kez yeniden dene
            if ((tries || 0) < 3 && !it.cancelled) return setTimeout(function () { upload(it, offset, (tries || 0) + 1); }, 1500);
            it.el.classList.add('is-error');
            it.el.querySelector('.sz').textContent = err.message || 'Yüklenemedi';
            it.el.querySelector('.tag').textContent = 'Hata';
            active = 0;
            next();
        });
    }

    input.addEventListener('change', function () { add(input.files); input.value = ''; });
    box.addEventListener('dragover', function (e) { e.preventDefault(); box.classList.add('is-over'); });
    box.addEventListener('dragleave', function () { box.classList.remove('is-over'); });
    box.addEventListener('drop', function (e) { e.preventDefault(); box.classList.remove('is-over'); add(e.dataTransfer.files); });

    // Yükleme bitmeden gönderilmesin
    form.addEventListener('submit', function (e) {
        if (active || queue.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            toast('Dosyalar yükleniyor, bitince gönderebilirsiniz.', 'warning');
        }
    }, true);
    // Yanıt gönderilince (sayfada kalınan formda) listeyi temizle
    form.addEventListener('ajax:success', function () {
        items.forEach(function (it) { it.el.remove(); });
        items = [];
        form.querySelectorAll('input[name="attachments[]"]').forEach(function (h) { h.remove(); });
        refreshPlan();
    });
})();
</script>
