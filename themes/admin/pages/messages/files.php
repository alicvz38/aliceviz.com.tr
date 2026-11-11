<?php
/**
 * Gönderilen dosyalar: e-postalarla gönderilen (ve gönderilmeden kalan) dosyalar; indir / sil
 * @var App\Core\View $this
 * @var array $files   mail_attachments + e-posta bilgisi (email, subject, recipient)
 * @var array $stats   count, bytes, downloads, active
 */
use App\Services\MailAttachments;

$this->layout('main');
?>
<div class="row">
    <?php foreach ([
        ['Dosya', $stats['count'], 'paperclip', 'primary'],
        ['Duran dosya', $stats['active'], 'folder', 'success'],
        ['Kullanılan alan (MB)', (int) round($stats['bytes'] / 1048576), 'database', 'warning'],
        ['İndirme', $stats['downloads'], 'download', 'info'],
    ] as [$label, $value, $ico, $color]): ?>
        <div class="col-xl-3 col-md-6 col-12 layout-spacing">
            <div class="widget stat-card">
                <div class="stat-icon bg-soft-<?= $color ?>"><?= icon($ico) ?></div>
                <div><h3 data-odo="<?= (int) $value ?>"><?= (int) $value ?></h3><p><?= e($label) ?></p></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="section-title mb-0"><?= icon('paperclip') ?> Gönderilen Dosyalar</div>
                <?php if ($stats['active']): ?>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" form="filesForm" name="secili" value="1" class="btn btn-outline-danger btn-sm" id="deleteSelected" disabled
                                data-confirm="Seçili dosyalar silinsin mi? Alıcılardaki indirme bağlantıları da çalışmaz."><?= icon('trash', 14) ?> Seçilenleri sil</button>
                        <button type="submit" form="filesForm" name="tumu" value="1" class="btn btn-danger btn-sm"
                                data-confirm="Sunucudaki TÜM gönderilen dosyalar silinsin mi? Alıcılardaki indirme bağlantıları da çalışmaz."><?= icon('trash', 14) ?> Tümünü sil</button>
                    </div>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= e(admin_url('mesajlar/dosyalar/sure')) ?>" class="d-flex flex-wrap align-items-center gap-2 mb-2" data-ajax>
                <?= csrf_field() ?>
                <label for="days" class="small text-white mb-0">Dosyaları sakla:</label>
                <select name="days" id="days" class="form-select form-select-sm" style="width:auto" onchange="this.form.requestSubmit()">
                    <?php foreach (MailAttachments::DAY_OPTIONS as $d => $label): ?>
                        <option value="<?= $d ?>" <?= MailAttachments::days() === $d ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="small text-muted">Yeni gönderimler için geçerli. Süre dolunca dosya kendiliğinden silinir<?= MailAttachments::days() === 0 ? '; "Süresiz" seçiliyken siz silene kadar durur' : '' ?>.</span>
            </form>
            <p class="text-muted small mb-3">
                Sildiğiniz dosya sunucudan kalkar; alıcıdaki indirme bağlantısı "dosya bulunamadı" der. E-postanın kendisi silinmez.
            </p>

            <?php if (!$files): ?>
                <div class="text-center text-muted py-5"><?= icon('paperclip', 32) ?><p class="mt-2 mb-0">Henüz e-postayla dosya göndermediniz.</p></div>
            <?php else: ?>
                <form method="post" action="<?= e(admin_url('mesajlar/dosyalar/sil')) ?>" id="filesForm" data-ajax data-ajax-success="reload">
                    <?= csrf_field() ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 sent-files">
                            <thead>
                            <tr>
                                <th style="width:34px"><input type="checkbox" class="form-check-input" id="checkAll" title="Tümünü seç"></th>
                                <th>Dosya</th>
                                <th>Alıcı / E-posta</th>
                                <th class="text-end">İndirme</th>
                                <th>Durum</th>
                                <th class="text-end">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($files as $f):
                                [$ext, $color] = MailAttachments::badge((string) $f['name']);
                                $alive = $f['path'] !== '';
                                $pending = $f['message_id'] === null;
                            ?>
                                <tr class="<?= $alive ? '' : 'is-gone' ?>">
                                    <td><?php if ($alive): ?><input type="checkbox" class="form-check-input file-check" name="ids[]" value="<?= (int) $f['id'] ?>"><?php endif; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="mail-file-ext" style="background:<?= e($color) ?>"><?= e($ext) ?></span>
                                            <div style="min-width:0">
                                                <div class="text-white fw-semibold text-truncate" style="max-width:240px" title="<?= e($f['name']) ?>"><?= e($f['name']) ?></div>
                                                <small class="text-muted"><?= e(MailAttachments::human((int) $f['size'])) ?> · <?= e(format_date($f['created_at'], 'd.m.Y H:i')) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($pending): ?>
                                            <span class="text-muted small">Gönderilmedi (formda kaldı)</span>
                                        <?php else: ?>
                                            <?php if ($f['recipient']): ?><div class="small text-white fw-semibold"><?= e($f['recipient']) ?></div><?php endif; ?>
                                            <div class="small text-white"><?= e((string) $f['email']) ?></div>
                                            <a href="<?= e(admin_url('mesajlar/' . $f['message_id'])) ?>" class="small"><?= e(str_limit((string) $f['subject'], 40)) ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= (int) $f['downloads'] ?></td>
                                    <td>
                                        <?php if (!$alive): ?>
                                            <span class="badge badge-light-secondary">Silindi</span>
                                        <?php elseif ($pending): ?>
                                            <span class="badge badge-light-warning">Gönderilmedi</span>
                                        <?php else: ?>
                                            <span class="badge badge-light-success">İndirilebilir</span>
                                            <?php if ($f['expires_at']): ?><div class="small text-muted mt-1"><?= e(format_date($f['expires_at'])) ?> tarihine kadar</div><?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns justify-content-end">
                                            <?php if ($alive): ?>
                                                <a href="<?= e(admin_url('mesajlar/ek/' . $f['token'] . '/indir')) ?>" class="btn btn-outline-primary" title="İndir"><?= icon('download') ?></a>
                                                <?php if ($f['as_link']): ?>
                                                    <button type="button" class="btn btn-outline-info js-copy-link" data-link="<?= e(MailAttachments::url($f)) ?>" title="Alıcının indirme bağlantısını kopyala"><?= icon('copy') ?></button>
                                                <?php endif; ?>
                                                <button type="submit" name="tek" value="<?= (int) $f['id'] ?>" class="btn btn-outline-danger" title="Sil"
                                                        data-confirm="&quot;<?= e($f['name']) ?>&quot; silinsin mi? Alıcıdaki indirme bağlantısı da çalışmaz."><?= icon('trash') ?></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->start('scripts') ?>
<script>
(function () {
    var all = document.getElementById('checkAll'), del = document.getElementById('deleteSelected');
    var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.file-check')); };
    var sync = function () { if (del) del.disabled = !checks().some(function (c) { return c.checked; }); };
    if (all) all.addEventListener('change', function () { checks().forEach(function (c) { c.checked = all.checked; }); sync(); });
    checks().forEach(function (c) { c.addEventListener('change', sync); });
    // Alıcının indirme bağlantısını panoya kopyala
    document.querySelectorAll('.js-copy-link').forEach(function (b) {
        b.addEventListener('click', function () {
            navigator.clipboard.writeText(b.dataset.link).then(function () { Ajax.toast('İndirme bağlantısı kopyalandı.', 'success'); });
        });
    });
})();
</script>
<?php $this->stop() ?>
