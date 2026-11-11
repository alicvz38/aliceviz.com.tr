<?php
/**
 * Özgeçmiş (CV) yönetimi — profil + kalemler. Ekleme/düzenleme modal pencerede AJAX ile yapılır.
 * @var App\Core\View $this
 * @var array<string, App\Models\ResumeItem[]> $items
 * @var array $settings
 */
$this->layout('main');
$types = App\Models\ResumeItem::TYPES;
$s = static fn (string $k) => $settings[$k] ?? '';
$avatar = profile_photo(); // Hesabım'daki ve panel üst çubuğundaki fotoğrafla aynı
?>
<?php $this->start('pageActions') ?>
<a href="<?= e(url('hakkimda')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('eye', 16) ?> Sitede Gör</a>
<?php $this->stop() ?>

<div class="row">
    <!-- Profil -->
    <div class="col-xl-4 col-12 layout-spacing">
        <form method="post" action="<?= e(admin_url('ozgecmis/profil')) ?>" enctype="multipart/form-data" data-ajax data-ajax-success="none" id="profileForm" class="widget widget-content-area">
            <?= csrf_field() ?>
            <div class="section-title"><?= icon('user') ?> Profil</div>

            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?= e($avatar) ?>" id="avatarPreview" class="resume-avatar <?= $avatar ? '' : 'd-none' ?>" alt="Fotoğraf">
                <span class="resume-avatar <?= $avatar ? 'd-none' : '' ?>" id="avatarPlaceholder"><?= e(mb_substr((string) ($settings['site_title'] ?? 'A'), 0, 1)) ?></span>
                <div class="flex-grow-1">
                    <input type="file" class="form-control form-control-sm" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp" data-preview="#avatarPreview">
                    <div class="form-text">Kare fotoğraf önerilir (en fazla 2 MB).</div>
                    <?php if ($avatar): ?>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar" value="1">
                            <label class="form-check-label small" for="remove_avatar">Fotoğrafı kaldır</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="cv_title">Unvan</label>
                <input type="text" class="form-control" id="cv_title" name="cv_title" value="<?= e($s('cv_title')) ?>" placeholder="Full Stack Yazılım Geliştirici">
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label" for="cv_location">Konum</label>
                    <input type="text" class="form-control" id="cv_location" name="cv_location" value="<?= e($s('cv_location')) ?>" placeholder="İstanbul">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label" for="cv_phone">Telefon <small class="text-muted">(isteğe bağlı)</small></label>
                    <input type="tel" class="form-control" id="cv_phone" name="cv_phone" data-mask="phone" value="<?= e($s('cv_phone')) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="about_text">Hakkımda (özet)</label>
                <textarea class="form-control" id="about_text" name="about_text" rows="6"><?= e($s('about_text')) ?></textarea>
                <div class="form-text">Ana sayfada ve özgeçmiş sayfasının başında gösterilir.</div>
            </div>
            <div class="switch form-switch-custom switch-inline form-switch-success mb-3 d-block">
                <input class="switch-input" type="checkbox" role="switch" id="cv_available" name="cv_available" value="1" <?= $s('cv_available') === '1' ? 'checked' : '' ?>>
                <label class="switch-label" for="cv_available">"Yeni projelere açığım" rozeti göster</label>
            </div>
            <button type="submit" class="btn btn-primary w-100"><?= icon('check', 16) ?> Profili Kaydet</button>
        </form>
    </div>

    <!-- Kalemler -->
    <div class="col-xl-8 col-12">
        <?php foreach ($types as $type => [$label, $ico]): ?>
            <div class="widget widget-content-area resume-section mb-4" data-type="<?= e($type) ?>">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="section-title mb-0"><?= icon($ico) ?> <?= e($label) ?></div>
                        <small class="text-muted drag-hint"><?= icon('menu', 12) ?> simgesinden tutup sürükleyerek sıralayın</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary js-add-item" data-type="<?= e($type) ?>"><?= icon('plus', 14) ?> Ekle</button>
                </div>
                <div class="resume-list">
                    <?php foreach ($items[$type] as $item): ?>
                        <?= $this->partial('resume-row', ['item' => $item]) ?>
                    <?php endforeach; ?>
                </div>
                <div class="resume-empty <?= $items[$type] ? 'd-none' : '' ?>">Henüz kayıt yok.</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Ekle / düzenle penceresi -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= e(admin_url('ozgecmis/kaydet')) ?>" class="modal-content" data-ajax id="itemForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <input type="hidden" name="type" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalTitle">Kayıt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"><?= icon('x') ?></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="f_title" data-label="title">Başlık</label> <span class="text-danger">*</span>
                        <input type="text" class="form-control" id="f_title" name="title" maxlength="150" required>
                    </div>
                    <div class="col-md-6 mb-3" data-show="experience education certificate skill">
                        <label class="form-label" for="f_org" data-label="org">Kurum</label>
                        <input type="text" class="form-control" id="f_org" name="organization" maxlength="150" list="skillGroups">
                        <datalist id="skillGroups"><option value="Backend"><option value="Frontend"><option value="Masaüstü"><option value="Veritabanı"><option value="Araçlar"></datalist>
                    </div>
                    <div class="col-md-6 mb-3" data-show="experience education">
                        <label class="form-label" for="f_location">Konum</label>
                        <input type="text" class="form-control" id="f_location" name="location" maxlength="100">
                    </div>
                    <div class="col-md-3 col-6 mb-3" data-show="experience education certificate">
                        <label class="form-label" for="f_start">Başlangıç</label>
                        <input type="month" class="form-control" id="f_start" name="start_date">
                    </div>
                    <div class="col-md-3 col-6 mb-3" data-show="experience education">
                        <label class="form-label" for="f_end">Bitiş</label>
                        <input type="month" class="form-control" id="f_end" name="end_date">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="f_current" name="is_current" value="1">
                            <label class="form-check-label small" for="f_current">Devam ediyor</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3" data-show="skill language">
                        <label class="form-label" for="f_level">Seviye: <strong id="levelValue">50</strong>%</label>
                        <input type="range" class="form-range" id="f_level" name="level" min="0" max="100" step="5" value="50">
                    </div>
                    <div class="col-md-6 mb-3" data-show="certificate">
                        <label class="form-label" for="f_url">Doğrulama bağlantısı</label>
                        <input type="url" class="form-control" id="f_url" name="url" placeholder="https://">
                    </div>
                    <div class="col-12 mb-3" data-show="experience education certificate language">
                        <label class="form-label" for="f_desc" data-label="desc">Açıklama</label>
                        <textarea class="form-control" id="f_desc" name="description" rows="6"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-dark" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php $this->start('styles') ?>
<link href="<?= $this->vendor('src/plugins/src/drag-and-drop/dragula/dragula.css') ?>" rel="stylesheet">
<?php $this->stop() ?>

<?php $this->start('scripts') ?>
<script src="<?= $this->vendor('src/plugins/src/drag-and-drop/dragula/dragula.min.js') ?>"></script>
<script>
// Sürükle-bırak ile sıralama (Equation'daki Dragula). Bırakınca yeni sıra hemen kaydedilir.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof dragula !== 'function') return;
    var drake = dragula(Array.prototype.slice.call(document.querySelectorAll('.resume-list')), {
        // Yalnızca ≡ tutma yerinden sürüklenir (Düzenle / Sil düğmeleri yanlışlıkla kaydırmasın)
        moves: function (el, source, handle) { return !!(handle && handle.closest('.drag-handle')); },
        // Kayıt yalnızca kendi bölümünde taşınır (yetenek "Diller"e bırakılamaz)
        accepts: function (el, target, source) { return target === source; },
        mirrorContainer: document.body
    });
    drake.on('drop', function (el, target) {
        var section = target.closest('.resume-section');
        var ids = Array.prototype.map.call(target.querySelectorAll('.resume-row'), function (r) { return r.dataset.id; });
        Ajax.post(<?= json_encode(admin_url('ozgecmis/sirala')) ?>, { type: section.dataset.type, ids: ids }).then(function (res) {
            if (res.ok) {
                Ajax.toast('Sıralama kaydedildi', 'success', 1500);
            } else {
                Ajax.showMessages(res);
                setTimeout(function () { location.reload(); }, 1200); // kaydedilemediyse doğru sırayı yeniden göster
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var TYPES = <?= json_encode(array_map(static fn ($t) => ['label' => $t[0], 'title' => $t[2], 'org' => $t[3]], $types), JSON_UNESCAPED_UNICODE) ?>;
    var modalEl = document.getElementById('itemModal');
    var modal = new bootstrap.Modal(modalEl);
    var form = document.getElementById('itemForm');
    var level = document.getElementById('f_level');
    var current = document.getElementById('f_current');
    var end = document.getElementById('f_end');

    level.addEventListener('input', function () { document.getElementById('levelValue').textContent = level.value; });
    current.addEventListener('change', function () { end.disabled = current.checked; });

    function open(type, item) {
        item = item || {};
        form.reset();
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.ajax-field-error').forEach(function (el) { el.remove(); });
        form.elements.id.value = item.id || '';
        form.elements.type.value = type;
        document.getElementById('itemModalTitle').textContent = TYPES[type].label + (item.id ? ' — Düzenle' : ' — Ekle');
        form.querySelector('[data-label="title"]').textContent = TYPES[type].title;
        form.querySelector('[data-label="org"]').textContent = TYPES[type].org;
        form.querySelector('[data-label="desc"]').textContent = type === 'language' ? 'Seviye açıklaması (örn. C1, akıcı)' : 'Açıklama';
        form.querySelectorAll('[data-show]').forEach(function (el) {
            var show = el.dataset.show.split(' ').indexOf(type) > -1;
            el.classList.toggle('d-none', !show);
            el.querySelectorAll('input, textarea').forEach(function (i) { i.disabled = !show; });
        });
        form.elements.title.value = item.title || '';
        form.elements.organization.value = item.organization || '';
        form.elements.location.value = item.location || '';
        form.elements.start_date.value = item.start_date || '';
        form.elements.end_date.value = item.end_date || '';
        form.elements.is_current.checked = item.is_current == 1;
        end.disabled = end.closest('[data-show]').classList.contains('d-none') || current.checked;
        form.elements.description.value = item.description || '';
        form.elements.url.value = item.url || '';
        level.value = item.level != null ? item.level : (type === 'language' ? 60 : 70);
        document.getElementById('levelValue').textContent = level.value;
        modal.show();
        setTimeout(function () { form.elements.title.focus(); }, 300);
    }

    document.querySelectorAll('.js-add-item').forEach(function (btn) {
        btn.addEventListener('click', function () { open(btn.dataset.type); });
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-edit-item');
        if (!btn) return;
        var row = btn.closest('.resume-row');
        var item = JSON.parse(row.dataset.item);
        open(item.type, item);
    });

    // Kaydedilince satırı ekle / değiştir, pencereyi kapat
    form.addEventListener('ajax:success', function (e) {
        e.preventDefault();
        var d = e.detail.data;
        var section = document.querySelector('.resume-section[data-type="' + d.type + '"]');
        var tmp = document.createElement('div');
        tmp.innerHTML = d.html.trim();
        var row = tmp.firstElementChild;
        var existing = section.querySelector('.resume-row[data-id="' + d.id + '"]');
        if (existing) existing.replaceWith(row); else section.querySelector('.resume-list').appendChild(row);
        section.querySelector('.resume-empty').classList.add('d-none');
        modal.hide();
        Ajax.showMessages(e.detail);
    });

    // Silinince boş mesajını güncelle
    document.addEventListener('ajax:removed', function () {
        document.querySelectorAll('.resume-section').forEach(function (s) {
            s.querySelector('.resume-empty').classList.toggle('d-none', !!s.querySelector('.resume-row'));
        });
    });

    // Profil fotoğrafı
    document.getElementById('profileForm').addEventListener('ajax:success', function (e) {
        var d = e.detail.data || {};
        var img = document.getElementById('avatarPreview');
        document.getElementById('avatar').value = '';
        var rm = document.getElementById('remove_avatar');
        if (rm) rm.checked = false;
        img.classList.toggle('d-none', !d.avatar);
        document.getElementById('avatarPlaceholder').classList.toggle('d-none', !!d.avatar);
        if (d.avatar) img.src = d.avatar;
    });
    document.getElementById('avatar').addEventListener('change', function () {
        document.getElementById('avatarPlaceholder').classList.add('d-none');
    });
});
</script>
<?php $this->stop() ?>
