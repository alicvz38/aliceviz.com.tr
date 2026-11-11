<?php
/**
 * Mesaj detayı + hızlı yanıt (AJAX)
 * @var App\Core\View $this
 * @var App\Models\Message $message
 * @var App\Models\Message[] $replies  Bu mesaja panelden verilen yanıtlar
 * @var App\Models\QuoteRequest|null $quote
 * @var bool $configured
 * @var string $signature
 */
$this->layout('main');
$isInbox = !$message->isOutgoing();
$isDraft = $message->folder === 'drafts';
$folderLabel = App\Services\MailFetcher::folders()[$message->folder]['label'] ?? 'Mesajlar';
$replySubject = (str_starts_with(mb_strtolower($message->subject), 're:') ? '' : 'Re: ') . $message->subject;
// WhatsApp yanıtı: numara + hazır selamlama
$wa = $message->phone ? whatsapp_href($message->phone, 'Merhaba ' . $message->name . ', ' . preg_replace('#^https?://#', '', site_origin()) . ' üzerinden gönderdiğiniz "' . $message->subject . '" konulu mesajınız için yazıyorum.') : '';
$quoted = "\n\n\n" . $signature . "\n\n"
    . '--- ' . format_date($message->created_at, 'd.m.Y H:i') . ' tarihinde ' . ($message->name ?: $message->email) . " yazdı ---\n"
    . preg_replace('/^/m', '> ', (string) $message->body);
?>
<?php $this->start('pageActions') ?>
<div class="d-flex gap-2">
    <a href="<?= e(admin_url('mesajlar' . ($message->folder === 'inbox' ? '' : '?klasor=' . rawurlencode((string) $message->folder)))) ?>" class="btn btn-outline-primary btn-sm"><?= icon('arrow-left', 16) ?> <?= e($folderLabel) ?></a>
    <?php if ($wa && $isInbox): ?>
        <a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm"><?= icon('whatsapp', 16) ?> WhatsApp ile Yanıtla</a>
    <?php endif; ?>
    <?php if ($isDraft): ?>
        <a href="<?= e(admin_url('mesajlar/yeni?taslak=' . $message->id)) ?>" class="btn btn-primary btn-sm"><?= icon('edit', 16) ?> Taslağı Düzenle ve Gönder</a>
    <?php endif; ?>
    <form method="post" action="<?= e(admin_url('mesajlar/' . $message->id . '/sil')) ?>" data-ajax class="m-0">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger btn-sm" data-confirm-title="<?= $message->imap_uid && in_array($message->folder, ['trash', 'spam'], true) ? 'Kalıcı olarak sil' : 'Mesajı sil' ?>" data-confirm="<?= $message->imap_uid ? (in_array($message->folder, ['trash', 'spam'], true) ? 'E-posta posta kutusundan kalıcı olarak silinsin mi?' : 'E-posta Çöp Kutusu\'na taşınsın mı?') : 'Mesaj silinsin mi?' ?>"><?= icon('trash', 16) ?> Sil</button>
    </form>
</div>
<?php $this->stop() ?>

<div class="row">
    <div class="col-xl-8 col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <h4 class="text-white mb-0" style="font-size:20px"><?= e($message->subject) ?></h4>
                <?php if ($message->replied_at): ?><span class="badge badge-light-success">Yanıtlandı · <?= e(time_ago($message->replied_at)) ?></span><?php endif; ?>
            </div>

            <!-- Yalnızca açılan mesaj; altında sadece bu mesaja panelden verilen yanıtlar -->
            <div id="threadList">
                <?= $this->partial('message-bubble', ['m' => $message]) ?>
                <?php foreach ($replies as $t): ?>
                    <?= $this->partial('message-bubble', ['m' => $t]) ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hızlı yanıt -->
        <div class="widget widget-content-area mt-4 <?= $isDraft ? 'd-none' : '' ?>">
            <div class="section-title"><?= icon('corner-up-left') ?> <?= $isInbox ? 'Yanıtla' : 'Yeniden yaz' ?></div>
            <?php if (!$configured): ?>
                <div class="alert alert-light-warning">
                    Yanıt göndermek için önce <a href="<?= e(admin_url('mesajlar/ayarlar')) ?>" class="alert-link">E-posta Ayarları</a>'nı yapın.
                </div>
            <?php endif; ?>
            <form method="post" action="<?= e(admin_url('mesajlar/gonder')) ?>" data-ajax id="replyForm">
                <?= csrf_field() ?>
                <input type="hidden" name="parent_id" value="<?= (int) $message->id ?>">
                <input type="hidden" name="quote_id" value="<?= (int) $message->quote_id ?>">
                <input type="hidden" name="name" value="<?= e($message->name) ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="to">Kime</label>
                        <input type="email" class="form-control" id="to" name="to" value="<?= e($message->email) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="subject">Konu</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?= e($replySubject) ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" id="body" name="body" rows="10" required><?= e($isInbox ? $quoted : "\n\n" . $signature) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" <?= $configured ? '' : 'disabled' ?>><?= icon('send', 16) ?> Gönder</button>
            </form>
        </div>
    </div>

    <div class="col-xl-4 col-12 layout-spacing">
        <?php if ($isInbox): ?>
            <?= $this->partial('phone-verify', ['item' => $message, 'action' => admin_url('mesajlar/' . $message->id . '/telefon-onayi')]) ?>
        <?php endif; ?>
        <div class="widget widget-content-area">
            <div class="section-title"><?= icon('user') ?> <?= $isInbox ? 'Gönderen' : 'Alıcı' ?></div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="mail-item p-0 border-0"><span class="mail-avatar"><?= e($message->initials()) ?></span></span>
                <div>
                    <div class="text-white fw-bold"><?= e($message->name ?: '-') ?></div>
                    <a href="mailto:<?= e($message->email) ?>" class="small"><?= e($message->email) ?></a>
                    <?php if ($message->phone): ?>
                        <div><a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="small" title="WhatsApp ile yaz"><?= icon('whatsapp', 12) ?> <?= e($message->phone) ?></a></div>
                    <?php endif; ?>
                </div>
            </div>
            <dl class="quote-facts mb-0">
                <dt>Tarih</dt><dd><?= e(format_date($message->created_at, 'd.m.Y H:i')) ?></dd>
                <?php if ($isInbox && $message->ip): ?><dt>IP adresi</dt><dd><?= e($message->ip) ?></dd><?php endif; ?>
                <?php if ($message->send_status === 'failed'): ?><dt>Gönderim hatası</dt><dd class="text-danger"><?= e($message->send_error) ?></dd><?php endif; ?>
            </dl>
            <?php if ($quote): ?>
                <a href="<?= e(admin_url('teklifler/' . $quote->id)) ?>" class="btn btn-outline-info btn-sm w-100 mt-2"><?= icon('inbox', 14) ?> İlgili teklif talebi</a>
            <?php endif; ?>
            <?php if ($isInbox): ?>
                <form method="post" action="<?= e(admin_url('mesajlar/' . $message->id . '/okundu')) ?>" data-ajax data-ajax-success="redirect" class="mt-2">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-secondary btn-sm w-100"><?= icon('eye-off', 14) ?> Okunmadı olarak işaretle</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->start('scripts') ?>
<script>
// Yanıt gönderilince sayfada kal, yazışmayı listeye ekle
document.getElementById('replyForm').addEventListener('ajax:success', function (e) {
    e.preventDefault();
    var d = e.detail.data || {};
    if (d.html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = d.html.trim();
        document.getElementById('threadList').appendChild(tmp.firstElementChild);
    }
    document.getElementById('body').value = <?= json_encode("\n\n" . $signature) ?>;
    Ajax.showMessages(e.detail);
});
var body = document.getElementById('body');
if (body) { body.focus(); body.setSelectionRange(0, 0); }
</script>
<?php $this->stop() ?>
