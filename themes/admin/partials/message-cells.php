<?php
/**
 * Mesajlar DataTable'ının hücreleri (sunucu tarafında üretilir — MessageController::data)
 * @var App\Models\Message $m
 * @var string $cell  check | star | from | subject | date | actions
 */
$url = admin_url('mesajlar/' . $m->id);
switch ($cell):
    case 'check': ?>
<div class="form-check form-check-primary m-0"><input class="form-check-input mail-check" type="checkbox" value="<?= (int) $m->id ?>" aria-label="Seç"></div>
<?php break;
    case 'star': ?>
<form method="post" action="<?= e(admin_url('mesajlar/' . $m->id . '/yildiz')) ?>" data-ajax data-ajax-success="none" class="js-star m-0">
    <?= csrf_field() ?>
    <button class="btn-icon <?= $m->is_starred ? 'starred' : '' ?>" title="Yıldızla"><?= icon($m->is_starred ? 'star-fill' : 'star') ?></button>
</form>
<?php break;
    case 'from': ?>
<a href="<?= e($url) ?>" class="mail-cell-from">
    <span class="mail-avatar sm"><?= e($m->initials()) ?></span>
    <span class="text-truncate">
        <?php if ($m->folder === 'drafts'): ?><span class="text-danger">Taslak</span> · <?php endif; ?>
        <?= $m->isOutgoing() ? '<span class="text-muted">Kime:</span> ' : '' ?><?= e($m->name ?: ($m->email ?: '(alıcı yok)')) ?>
    </span>
</a>
<?php break;
    case 'subject': ?>
<a href="<?= e($url) ?>" class="mail-cell-subject">
    <span class="mail-subject"><?= e($m->subject) ?></span>
    <?php if (($folder ?? '') === 'starred'): ?><span class="badge badge-light-dark ms-1"><?= e(App\Services\MailFetcher::folders()[$m->folder]['label'] ?? $m->folder) ?></span><?php endif; ?>
    <?php if (!$m->isOutgoing() && $m->source !== 'email'): ?><span class="badge badge-light-primary ms-1"><?= e($m->sourceLabel()) ?></span><?php endif; ?>
    <?php if ($m->replied_at): ?><span class="badge badge-light-success ms-1">Yanıtlandı</span><?php endif; ?>
    <?php if ($m->send_status === 'failed'): ?><span class="badge badge-light-danger ms-1">Gönderilemedi</span><?php endif; ?>
    <span class="mail-snippet">— <?= e(str_limit(preg_replace('/\s+/', ' ', (string) $m->body), 110)) ?></span>
</a>
<?php break;
    case 'date': ?>
<span class="mail-date" title="<?= e(format_date($m->created_at, 'd.m.Y H:i')) ?>"><?= e(strtotime((string) $m->created_at) > strtotime('-1 day') ? format_date($m->created_at, 'H:i') : format_date($m->created_at, date('Y') === date('Y', strtotime((string) $m->created_at)) ? 'd.m' : 'd.m.Y')) ?></span>
<?php break;
    case 'actions': ?>
<div class="mail-actions justify-content-end">
    <?php if (!$m->isOutgoing()): ?>
        <form method="post" action="<?= e(admin_url('mesajlar/' . $m->id . '/okundu')) ?>" data-ajax data-ajax-success="none" class="js-read m-0">
            <?= csrf_field() ?>
            <button class="btn-icon" title="<?= $m->is_read ? 'Okunmadı yap' : 'Okundu yap' ?>"><?= icon($m->is_read ? 'eye-off' : 'eye') ?></button>
        </form>
    <?php endif; ?>
    <form method="post" action="<?= e(admin_url('mesajlar/' . $m->id . '/sil')) ?>" data-ajax data-ajax-success="none" class="js-delete m-0">
        <?= csrf_field() ?>
        <button class="btn-icon" title="Sil" data-confirm-variant="danger"
                data-confirm-title="<?= $m->imap_uid && in_array($m->folder, ['trash', 'spam'], true) ? 'Kalıcı olarak sil' : 'Mesajı sil' ?>"
                data-confirm="<?= $m->imap_uid ? (in_array($m->folder, ['trash', 'spam'], true) ? 'E-posta posta kutusundan kalıcı olarak silinsin mi?' : 'E-posta Çöp Kutusu\'na taşınsın mı?') : 'Mesaj silinsin mi?' ?>"><?= icon('trash') ?></button>
    </form>
</div>
<?php break;
endswitch;
