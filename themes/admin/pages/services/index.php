<?php
/**
 * Teklif formu & hizmetler düzenleyicisi
 * @var App\Core\View $this
 * @var array $catalog  ServiceCatalog::data()
 * @var string[] $icons
 * @var bool $customized
 */
$this->layout('main');
$f = $catalog['form'];
$featureText = static fn (array $features) => implode("\n", array_map(static fn ($x) => implode(' | ', array_filter([$x[0], $x[1], $x[2] ?? ''], 'strlen')), $features));
?>
<?php $this->start('pageActions') ?>
<div class="d-flex gap-2">
    <a href="<?= e(url('teklif-al')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('eye', 16) ?> Teklif Formu</a>
    <a href="<?= e(url('hizmetler')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('external-link', 16) ?> Hizmetler Sayfası</a>
</div>
<?php $this->stop() ?>

<form method="post" action="<?= e(admin_url('teklif-formu')) ?>" data-ajax data-ajax-success="none" id="catalogForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-xl-8 col-12">

            <!-- Hizmetler -->
            <div class="widget widget-content-area layout-spacing">
                <div class="section-title"><?= icon('layers') ?> Hizmetler</div>
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <?php $i = 0; foreach ($catalog['services'] as $key => $s): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $i++ === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#svc-<?= e($key) ?>" type="button" role="tab">
                                <?= icon($s['icon'], 14) ?> <?= e($s['short']) ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content">
                    <?php $i = 0; foreach ($catalog['services'] as $key => $s): $n = "services[$key]"; ?>
                        <div class="tab-pane fade <?= $i++ === 0 ? 'show active' : '' ?>" id="svc-<?= e($key) ?>" role="tabpanel">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Kısa ad (menü, form)</label>
                                    <input type="text" class="form-control" name="<?= $n ?>[short]" value="<?= e($s['short']) ?>" maxlength="40">
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Başlık</label>
                                    <input type="text" class="form-control" name="<?= $n ?>[title]" value="<?= e($s['title']) ?>" maxlength="80">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Slogan</label>
                                    <input type="text" class="form-control" name="<?= $n ?>[tagline]" value="<?= e($s['tagline']) ?>" maxlength="120">
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <label class="form-label">İkon</label>
                                    <select class="form-select" name="<?= $n ?>[icon]">
                                        <?php foreach ($icons as $ic): ?><option value="<?= e($ic) ?>" <?= $s['icon'] === $ic ? 'selected' : '' ?>><?= e($ic) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <label class="form-label">Renk</label>
                                    <input type="color" class="form-control form-control-color w-100" name="<?= $n ?>[color]" value="<?= e($s['color']) ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Tanıtım metni</label>
                                    <textarea class="form-control" name="<?= $n ?>[intro]" rows="3" maxlength="800"><?= e($s['intro']) ?></textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Özellikler <small class="text-muted">— her satıra bir tane: <code>ikon | Başlık | Açıklama</code></small></label>
                                    <textarea class="form-control font-monospace small" name="<?= $n ?>[features]" rows="6"><?= e($featureText($s['features'])) ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teknolojiler <small class="text-muted">(virgülle)</small></label>
                                    <input type="text" class="form-control" name="<?= $n ?>[tech]" value="<?= e(implode(', ', $s['tech'])) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Örnek proje dilleri <small class="text-muted">(virgülle)</small></label>
                                    <input type="text" class="form-control" name="<?= $n ?>[languages]" value="<?= e(implode(', ', $s['languages'])) ?>">
                                    <div class="form-text">Hizmet sayfasında bu dillerdeki projeleriniz "örnek çalışma" olarak gösterilir.</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                                <h6 class="mb-0 text-white">Paketler</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary js-add-package" data-service="<?= e($key) ?>"><?= icon('plus', 14) ?> Paket ekle</button>
                            </div>
                            <div class="package-list" data-service="<?= e($key) ?>">
                                <?php $p = 0; foreach ($s['packages'] as $pkey => $pkg): ?>
                                    <?= $this->partial('package-row', ['service' => $key, 'index' => $p++, 'pkey' => $pkey, 'pkg' => $pkg]) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SSS -->
            <div class="widget widget-content-area layout-spacing">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0"><?= icon('message-square') ?> Sıkça Sorulan Sorular</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addFaq"><?= icon('plus', 14) ?> Soru ekle</button>
                </div>
                <div id="faqList">
                    <?php foreach ($catalog['faq'] as $j => [$q, $a]): ?>
                        <div class="faq-row ajax-item">
                            <div class="flex-grow-1">
                                <input type="text" class="form-control mb-2" name="faq[<?= $j ?>][q]" value="<?= e($q) ?>" placeholder="Soru">
                                <textarea class="form-control" name="faq[<?= $j ?>][a]" rows="2" placeholder="Cevap"><?= e($a) ?></textarea>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm js-remove-row" title="Kaldır"><?= icon('trash', 14) ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-12">
            <div class="widget widget-content-area layout-spacing">
                <div class="section-title"><?= icon('file-text') ?> Teklif Formu</div>
                <div class="mb-3">
                    <label class="form-label" for="form_title">Sayfa başlığı</label>
                    <input type="text" class="form-control" id="form_title" name="form[title]" value="<?= e($f['title']) ?>" maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="form_lead">Açıklama</label>
                    <textarea class="form-control" id="form_lead" name="form[lead]" rows="3" maxlength="500"><?= e($f['lead']) ?></textarea>
                </div>
                <div class="switch form-switch-custom switch-inline form-switch-primary mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="form_other" name="form[other]" value="1" <?= !empty($f['other']) ? 'checked' : '' ?>>
                    <label class="switch-label" for="form_other">"Diğer" hizmet seçeneğini göster</label>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="budgets">Bütçe seçenekleri <small class="text-muted">(her satıra bir)</small></label>
                    <textarea class="form-control" id="budgets" name="budgets" rows="6"><?= e(implode("\n", $catalog['budgets'])) ?></textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label" for="deadlines">Süre seçenekleri <small class="text-muted">(her satıra bir)</small></label>
                    <textarea class="form-control" id="deadlines" name="deadlines" rows="5"><?= e(implode("\n", $catalog['deadlines'])) ?></textarea>
                </div>
            </div>

            <div class="widget widget-content-area layout-spacing">
                <div class="section-title"><?= icon('check-circle') ?> Çalışma Süreci</div>
                <textarea class="form-control font-monospace small" name="process" rows="7"><?= e(implode("\n", array_map(static fn ($x) => implode(' | ', $x), $catalog['process']))) ?></textarea>
                <div class="form-text">Her satıra bir adım: <code>ikon | Başlık | Açıklama</code></div>
            </div>

            <div class="widget widget-content-area layout-spacing">
                <div class="section-title"><?= icon('star') ?> Kullanılabilir ikonlar</div>
                <div class="icon-palette">
                    <?php foreach ($icons as $ic): ?>
                        <button type="button" class="icon-chip js-copy-icon" data-icon="<?= e($ic) ?>" title="Kopyala: <?= e($ic) ?>"><?= icon($ic, 16) ?><span><?= e($ic) ?></span></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="widget widget-content-area layout-spacing catalog-save">
                <button type="submit" class="btn btn-primary w-100 mb-2"><?= icon('check', 16) ?> Kaydet</button>
                <?php if ($customized): ?>
                    <button type="submit" form="resetForm" class="btn btn-outline-danger w-100 btn-sm" data-confirm-title="Varsayılana dön" data-confirm-variant="danger" data-confirm="Yaptığınız tüm düzenlemeler silinip başlangıç içeriklerine dönülecek. Emin misiniz?"><?= icon('refresh', 14) ?> Varsayılanlara dön</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
<form method="post" action="<?= e(admin_url('teklif-formu/sifirla')) ?>" id="resetForm" data-ajax data-ajax-success="reload"><?= csrf_field() ?></form>

<template id="packageTpl"><?= $this->partial('package-row', ['service' => '__S__', 'index' => '__I__', 'pkey' => '', 'pkg' => ['name' => '', 'price' => '', 'desc' => '', 'items' => [], 'popular' => false]]) ?></template>
<template id="faqTpl">
    <div class="faq-row ajax-item">
        <div class="flex-grow-1">
            <input type="text" class="form-control mb-2" name="faq[__I__][q]" placeholder="Soru">
            <textarea class="form-control" name="faq[__I__][a]" rows="2" placeholder="Cevap"></textarea>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm js-remove-row" title="Kaldır"><?= icon('trash', 14) ?></button>
    </div>
</template>

<?php $this->start('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var counter = 1000;
    function fromTemplate(id, replace) {
        var html = document.getElementById(id).innerHTML;
        Object.keys(replace).forEach(function (k) { html = html.split(k).join(replace[k]); });
        var tmp = document.createElement('div');
        tmp.innerHTML = html.trim();
        return tmp.firstElementChild;
    }
    document.querySelectorAll('.js-add-package').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var list = document.querySelector('.package-list[data-service="' + btn.dataset.service + '"]');
            var row = fromTemplate('packageTpl', { '__S__': btn.dataset.service, '__I__': counter++ });
            list.appendChild(row);
            row.querySelector('input').focus();
        });
    });
    document.getElementById('addFaq').addEventListener('click', function () {
        var row = fromTemplate('faqTpl', { '__I__': counter++ });
        document.getElementById('faqList').appendChild(row);
        row.querySelector('input').focus();
    });
    document.addEventListener('click', function (e) {
        var rm = e.target.closest('.js-remove-row');
        if (rm) { rm.closest('.ajax-item').remove(); return; }
        var ic = e.target.closest('.js-copy-icon');
        if (ic && navigator.clipboard) {
            navigator.clipboard.writeText(ic.dataset.icon).then(function () { Ajax.toast('"' + ic.dataset.icon + '" kopyalandı', 'info', 1500); });
        }
    });
    // Hatalı alan başka sekmedeyse o sekmeyi aç
    document.getElementById('catalogForm').addEventListener('ajax:error', function () {
        var bad = document.querySelector('#catalogForm .is-invalid');
        var pane = bad && bad.closest('.tab-pane');
        if (pane) bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#' + pane.id + '"]')).show();
    });
});
</script>
<?php $this->stop() ?>
