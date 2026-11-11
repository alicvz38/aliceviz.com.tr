<?php
/**
 * Proje tanıtım görselleri: yükleme, başlık / açıklama düzenleme, sürükle-bırak sıralama, silme
 * @var App\Core\View $this
 * @var App\Models\Project $project
 * @var App\Models\ProjectShot[] $shots
 */
$this->layout('main');
$base = 'projeler/' . $project->id . '/tanitim';
?>
<?php $this->start('pageActions') ?>
<div class="d-flex gap-2">
    <?php if ($shots && $project->is_published): ?>
        <a href="<?= e(url('projeler/' . $project->slug . '/tanitim')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('eye', 16) ?> Sitede Gör</a>
    <?php endif; ?>
    <a href="<?= e(admin_url('projeler/' . $project->id . '/duzenle')) ?>" class="btn btn-outline-primary btn-sm"><?= icon('arrow-left', 16) ?> Projeye Dön</a>
</div>
<?php $this->stop() ?>
<?php $this->start('styles') ?>
<link href="<?= $this->vendor('src/plugins/src/drag-and-drop/dragula/dragula.css') ?>" rel="stylesheet">
<?php $this->stop() ?>

<div class="row justify-content-center">
    <div class="col-xl-10 col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="section-title"><?= icon('upload') ?> Görsel Yükle</div>
            <form method="post" action="<?= e(admin_url($base . '/yukle')) ?>" enctype="multipart/form-data" class="shot-upload">
                <?= csrf_field() ?>
                <input type="file" class="form-control" name="gorseller[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                <button type="submit" class="btn btn-primary"><?= icon('upload', 16) ?> Yükle</button>
            </form>
            <div class="form-text">Birden fazla ekran görüntüsünü aynı anda seçebilirsiniz (en fazla 20, her biri en fazla 2 MB). Başlık dosya adından önerilir; yükledikten sonra düzenleyin. Görseller sitede <strong><?= e(url('projeler/' . $project->slug . '/tanitim')) ?></strong> sayfasında bu sırayla gösterilir.</div>
        </div>

        <div class="widget widget-content-area mt-4">
            <div class="section-title"><?= icon('image') ?> Tanıtım Görselleri <span class="badge badge-light-primary ms-1"><?= count($shots) ?></span></div>
            <?php if (!$shots): ?>
                <p class="text-muted mb-0">Henüz tanıtım görseli yok. Yüklediğiniz ilk görselle birlikte sitede tanıtım sayfası ve proje sayfasında "Tanıtımı İncele" düğmesi görünür.</p>
            <?php else: ?>
                <div class="form-text mb-3">Sırayı değiştirmek için satırı <?= icon('menu', 14) ?> tutamağından sürükleyin; bırakınca kaydedilir.</div>
                <ol class="shot-list" id="shotList">
                    <?php foreach ($shots as $i => $shot): ?>
                        <li class="shot-row" data-id="<?= (int) $shot->id ?>">
                            <span class="drag-handle" title="Sürükleyerek sırala"><?= icon('menu', 18) ?></span>
                            <a href="<?= e($shot->imageUrl()) ?>" target="_blank" class="shot-thumb"><img src="<?= e($shot->imageUrl()) ?>" alt="" loading="lazy"></a>
                            <form method="post" action="<?= e(admin_url($base . '/' . $shot->id . '/guncelle')) ?>" data-ajax class="shot-fields">
                                <?= csrf_field() ?>
                                <input type="text" class="form-control" name="title" value="<?= e($shot->title) ?>" maxlength="150" placeholder="Başlık">
                                <textarea class="form-control" name="caption" rows="2" maxlength="1000" placeholder="Açıklama (isteğe bağlı)"><?= e((string) $shot->caption) ?></textarea>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm"><?= icon('check', 14) ?> Kaydet</button>
                                </div>
                            </form>
                            <form method="post" action="<?= e(admin_url($base . '/' . $shot->id . '/sil')) ?>" data-ajax data-ajax-success="none" class="js-delete m-0 shot-delete">
                                <?= csrf_field() ?>
                                <button class="btn-icon" title="Sil" data-confirm-variant="danger" data-confirm-title="Görseli sil" data-confirm="Bu tanıtım görseli silinsin mi?"><?= icon('trash') ?></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->start('scripts') ?>
<script src="<?= $this->vendor('src/plugins/src/drag-and-drop/dragula/dragula.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('shotList');
    // Silinen satır listeden kaldırılır
    document.addEventListener('ajax:success', function (e) {
        var form = e.target.closest && e.target.closest('.shot-delete');
        if (form) { var row = form.closest('.shot-row'); if (row) row.remove(); }
    });
    if (!list || typeof dragula !== 'function') return;
    var drake = dragula([list], {
        moves: function (el, source, handle) { return !!(handle && handle.closest('.drag-handle')); },
        mirrorContainer: document.body
    });
    drake.on('drop', function () {
        var ids = Array.prototype.map.call(list.querySelectorAll('.shot-row'), function (r) { return r.dataset.id; });
        Ajax.post(<?= json_encode(admin_url($base . '/sirala')) ?>, { ids: ids }).then(function (res) {
            if (res.ok) Ajax.toast('Sıralama kaydedildi', 'success', 1500);
            else { Ajax.showMessages(res); setTimeout(function () { location.reload(); }, 1200); }
        });
    });
});
</script>
<?php $this->stop() ?>
