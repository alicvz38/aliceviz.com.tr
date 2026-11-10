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
</div>
