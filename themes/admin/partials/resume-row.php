<?php
/**
 * Özgeçmiş listesinde bir satır (AJAX ile eklenip güncellenebilir)
 * @var App\Models\ResumeItem $item
 */
$json = json_encode($item->toArray(), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
$isLevel = in_array($item->type, ['skill', 'language'], true);
?>
<div class="resume-row ajax-item" data-id="<?= (int) $item->id ?>" data-item='<?= $json ?>'>
    <span class="drag-handle" title="Sürükleyerek sıralayın" aria-label="Sürükle"><?= icon('menu', 16) ?></span>
    <div class="resume-main">
        <strong><?= e($item->title) ?></strong>
        <?php if ($item->organization && $item->type !== 'skill'): ?>
            <span class="text-muted">· <?= e($item->organization) ?></span>
        <?php endif; ?>
        <div class="resume-meta">
            <?php if ($isLevel): ?>
                <?= $item->type === 'skill' ? e($item->organization ?: 'Diğer') . ' · ' : '' ?>%<?= (int) $item->level ?>
                <?= $item->type === 'language' ? ' · ' . e($item->description ?: $item->levelLabel()) : '' ?>
                <div class="progress"><div class="progress-bar" style="width: <?= (int) $item->level ?>%"></div></div>
            <?php else: ?>
                <?= e(implode(' · ', array_filter([$item->period(), $item->location]))) ?>
                <?php if ($item->is_current): ?><span class="badge badge-light-success ms-1">Devam ediyor</span><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="action-btns">
        <button type="button" class="btn btn-outline-primary js-edit-item" title="Düzenle"><?= icon('edit') ?></button>
        <form method="post" action="<?= e(admin_url('ozgecmis/' . $item->id . '/sil')) ?>" data-ajax data-ajax-success="remove" data-ajax-remove=".ajax-item">
            <?= csrf_field() ?>
            <button class="btn btn-outline-danger" title="Sil" data-confirm="&quot;<?= e($item->title) ?>&quot; silinsin mi?"><?= icon('trash') ?></button>
        </form>
    </div>
</div>
