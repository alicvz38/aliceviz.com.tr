<?php
/**
 * Hizmet paketi düzenleme satırı
 * @var string $service
 * @var int|string $index
 * @var string $pkey
 * @var array $pkg
 */
$n = "services[$service][packages][$index]";
?>
<div class="package-row ajax-item">
    <input type="hidden" name="<?= $n ?>[key]" value="<?= e($pkey) ?>">
    <div class="row g-2">
        <div class="col-md-5">
            <input type="text" class="form-control" name="<?= $n ?>[name]" value="<?= e($pkg['name']) ?>" placeholder="Paket adı" maxlength="60">
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="<?= $n ?>[price]" value="<?= e($pkg['price']) ?>" placeholder="Fiyat (boşsa: Projeye özel fiyat)" maxlength="60">
        </div>
        <div class="col-md-3 d-flex align-items-center justify-content-between gap-2">
            <div class="form-check m-0">
                <input class="form-check-input" type="checkbox" name="<?= $n ?>[popular]" value="1" id="pop-<?= e($service . '-' . $index) ?>" <?= !empty($pkg['popular']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="pop-<?= e($service . '-' . $index) ?>">Öne çıkan</label>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm js-remove-row" title="Paketi kaldır"><?= icon('trash', 14) ?></button>
        </div>
        <div class="col-md-5">
            <textarea class="form-control" name="<?= $n ?>[desc]" rows="4" placeholder="Kısa açıklama" maxlength="300"><?= e($pkg['desc']) ?></textarea>
        </div>
        <div class="col-md-7">
            <textarea class="form-control" name="<?= $n ?>[items]" rows="4" placeholder="Pakete dahil olanlar (her satıra bir)"><?= e(implode("\n", $pkg['items'])) ?></textarea>
        </div>
    </div>
</div>
