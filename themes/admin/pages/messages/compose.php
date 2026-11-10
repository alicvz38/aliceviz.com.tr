<?php
/**
 * Yeni e-posta
 * @var App\Core\View $this
 * @var string $to
 * @var string $name
 * @var string $subject
 * @var App\Models\Message|null $parent
 * @var App\Models\QuoteRequest|null $quote
 * @var bool $configured
 * @var string $signature
 */
$this->layout('main');
$body = "Merhaba" . ($name ? ' ' . $name : '') . ",\n\n\n\n" . $signature;
if (!empty($draftBody)) {
    $body = $draftBody;
} elseif ($quote) {
    $body = "Merhaba {$quote->name},\n\n"
        . $quote->serviceLabel() . " talebiniz için teşekkür ederim. Projenizi inceledim.\n\n\n\n"
        . $signature;
}
?>
<?php if (!$configured): ?>
    <div class="alert alert-light-warning mb-4">
        <?= icon('alert-triangle') ?> E-posta gönderebilmek için önce
        <a href="<?= e(admin_url('mesajlar/ayarlar')) ?>" class="alert-link">E-posta Ayarları</a>'nı yapmalısınız.
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-8 col-12 layout-spacing">
        <form method="post" action="<?= e(admin_url('mesajlar/gonder')) ?>" data-ajax class="widget widget-content-area">
            <?= csrf_field() ?>
            <input type="hidden" name="parent_id" value="<?= (int) ($parent?->id) ?>">
            <input type="hidden" name="quote_id" value="<?= (int) ($quote?->id) ?>">
            <div class="section-title"><?= icon('send') ?> Yeni E-posta</div>
            <div class="row">
                <div class="col-md-7 mb-3">
                    <label class="form-label" for="to">Kime (e-posta) <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="to" name="to" value="<?= e($to) ?>" placeholder="ornek@mail.com" required>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label" for="name">Alıcı adı</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="subject">Konu <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="subject" name="subject" value="<?= e($subject) ?>" maxlength="200" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="body">Mesaj <span class="text-danger">*</span></label>
                <textarea class="form-control" id="body" name="body" rows="14" required><?= e($body) ?></textarea>
                <div class="form-text">Düz metin olarak yazın; bağlantılar otomatik tıklanabilir olur.</div>
            </div>
            <button type="submit" class="btn btn-primary" <?= $configured ? '' : 'disabled' ?>><?= icon('send', 16) ?> Gönder</button>
        </form>
    </div>

    <div class="col-xl-4 col-12 layout-spacing">
        <?php if ($quote): ?>
            <div class="widget widget-content-area mb-4">
                <div class="section-title"><?= icon('inbox') ?> İlgili teklif</div>
                <p class="mb-1 text-white"><?= e($quote->serviceLabel()) ?> · <?= e($quote->packageLabel()) ?></p>
                <p class="small text-muted mb-2">Bütçe: <?= e($quote->budget ?: '-') ?> · Süre: <?= e($quote->deadline ?: '-') ?></p>
                <div class="mail-bubble inbox mb-0"><div class="mail-bubble-body small"><?= e(str_limit($quote->message, 600)) ?></div></div>
            </div>
        <?php elseif ($parent): ?>
            <div class="widget widget-content-area mb-4">
                <div class="section-title"><?= icon('corner-up-left') ?> Yanıtlanan mesaj</div>
                <?= $this->partial('message-bubble', ['m' => $parent]) ?>
            </div>
        <?php endif; ?>
        <div class="widget widget-content-area">
            <div class="section-title"><?= icon('alert-triangle') ?> Bilgi</div>
            <p class="small text-muted mb-0">
                E-posta, <a href="<?= e(admin_url('mesajlar/ayarlar')) ?>">E-posta Ayarları</a>'nda tanımlı adresinizden gönderilir
                ve <strong>Gönderilenler</strong> klasörüne kaydedilir. Alıcı yanıt verdiğinde yanıt kendi e-posta kutunuza gelir.
            </p>
        </div>
    </div>
</div>
