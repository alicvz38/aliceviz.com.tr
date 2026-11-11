<?php
/**
 * Yazışma balonu (gelen veya gönderilen)
 * @var App\Models\Message $m
 */
?>
<div class="mail-bubble <?= $m->isOutgoing() ? 'sent' : 'inbox' ?>">
    <div class="mail-bubble-head">
        <span>
            <?php if ($m->isOutgoing()): ?>
                <?= icon('send', 13) ?> <strong>Siz</strong> → <?= e($m->name ? $m->name . ' <' . $m->email . '>' : $m->email) ?>
                <?php if ($m->send_status === 'failed'): ?>
                    <span class="badge badge-light-danger ms-1" title="<?= e($m->send_error) ?>">Gönderilemedi</span>
                <?php endif; ?>
            <?php else: ?>
                <?= icon('inbox', 13) ?> <strong><?= e($m->name ?: $m->email) ?></strong> &lt;<?= e($m->email) ?>&gt;
            <?php endif; ?>
        </span>
        <span title="<?= e(format_date($m->created_at, 'd.m.Y H:i')) ?>"><?= e(format_date($m->created_at, 'd.m.Y H:i')) ?></span>
    </div>
    <div class="fw-bold mb-2 text-white"><a href="<?= e(admin_url('mesajlar/' . $m->id)) ?>" class="text-white"><?= e($m->subject) ?></a></div>
    <div class="mail-bubble-body"><?= e($m->body) ?></div>
    <?php $files = $m->isOutgoing() ? App\Services\MailAttachments::forMessage((int) $m->id) : []; ?>
    <?php if ($files): ?>
        <div class="mail-files">
            <?php foreach ($files as $f): [$ext, $color] = App\Services\MailAttachments::badge((string) $f['name']); $alive = $f['path'] !== ''; ?>
                <?= $alive ? '<a href="' . e(admin_url('mesajlar/ek/' . $f['token'] . '/indir')) . '" class="mail-file" title="İndir">' : '<span class="mail-file is-expired" title="Artık indirilemez (silindi ya da 30 günü doldu)">' ?>
                    <span class="mail-file-ext" style="background:<?= e($color) ?>"><?= e($ext) ?></span>
                    <span class="mail-file-info">
                        <b><?= e($f['name']) ?></b>
                        <small><?= e(App\Services\MailAttachments::human((int) $f['size'])) ?> · <?= $f['as_link'] ? 'İndirme bağlantısı' . ($f['downloads'] ? ' · ' . (int) $f['downloads'] . ' kez indirildi' : '') : 'E-postaya eklendi' ?><?= $alive ? '' : ' · silindi' ?></small>
                    </span>
                    <?php if ($alive): ?><span class="mail-file-dl"><?= icon('download', 16) ?></span><?php endif; ?>
                <?= $alive ? '</a>' : '</span>' ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
