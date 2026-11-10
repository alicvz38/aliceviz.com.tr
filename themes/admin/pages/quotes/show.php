<?php
/**
 * @var App\Core\View $this
 * @var App\Models\QuoteRequest $quote
 * @var App\Models\Message[] $mails
 */
$this->layout('main');
?>
<?php $this->start('pageActions') ?>
<div class="d-flex gap-2">
    <a href="<?= e(admin_url('mesajlar/yeni?teklif=' . $quote->id)) ?>" class="btn btn-primary btn-sm"><?= icon('send', 16) ?> E-posta ile Yanıtla</a>
    <a href="<?= e(admin_url('teklifler')) ?>" class="btn btn-outline-primary btn-sm"><?= icon('arrow-left', 16) ?> Listeye Dön</a>
</div>
<?php $this->stop() ?>

<div class="row">
    <div class="col-xl-8 col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div class="section-title mb-0"><?= icon('file-text') ?> <?= e($quote->serviceLabel()) ?> talebi</div>
                <span class="badge badge-light-<?= e($quote->statusColor()) ?>" id="quoteStatusBadge"><?= e($quote->statusLabel()) ?></span>
            </div>

            <dl class="row quote-facts">
                <dt class="col-sm-3">Müşteri</dt>
                <dd class="col-sm-9"><?= e($quote->name) ?><?= $quote->company ? ' — ' . e($quote->company) : '' ?></dd>
                <dt class="col-sm-3">E-posta</dt>
                <dd class="col-sm-9"><a href="<?= e(admin_url('mesajlar/yeni?teklif=' . $quote->id)) ?>"><?= e($quote->email) ?></a></dd>
                <dt class="col-sm-3">Telefon</dt>
                <dd class="col-sm-9"><?= $quote->phone ? '<a href="' . e(phone_href($quote->phone)) . '">' . e($quote->phone) . '</a>' : '-' ?></dd>
                <dt class="col-sm-3">Hizmet / Paket</dt>
                <dd class="col-sm-9"><?= e($quote->serviceLabel()) ?> · <?= e($quote->packageLabel()) ?></dd>
                <dt class="col-sm-3">Bütçe</dt>
                <dd class="col-sm-9"><?= e($quote->budget ?: '-') ?></dd>
                <dt class="col-sm-3">Teslim süresi</dt>
                <dd class="col-sm-9"><?= e($quote->deadline ?: '-') ?></dd>
                <dt class="col-sm-3">Tarih</dt>
                <dd class="col-sm-9"><?= e(format_date($quote->created_at, 'd.m.Y H:i')) ?> <span class="text-muted">(<?= e(time_ago($quote->created_at)) ?>)</span></dd>
            </dl>

            <div class="section-title mt-2"><?= icon('message-square') ?> Proje açıklaması</div>
            <div class="mail-bubble inbox"><div class="mail-bubble-body"><?= e($quote->message) ?></div></div>
        </div>

        <?php if ($mails): ?>
            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('mail') ?> Bu kişiyle yazışmalar</div>
                <?php foreach ($mails as $m): ?>
                    <?= $this->partial('message-bubble', ['m' => $m]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-xl-4 col-12 layout-spacing">
        <form method="post" action="<?= e(admin_url('teklifler/' . $quote->id . '/guncelle')) ?>" class="widget widget-content-area" data-ajax data-ajax-success="none" id="quoteForm">
            <?= csrf_field() ?>
            <div class="section-title"><?= icon('settings') ?> Takip</div>
            <div class="mb-3">
                <label class="form-label" for="status">Durum</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach (App\Models\QuoteRequest::STATUSES as $key => [$label]): ?>
                        <option value="<?= e($key) ?>" <?= $quote->status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="admin_note">Özel not <small class="text-muted">(yalnızca siz görürsünüz)</small></label>
                <textarea class="form-control" id="admin_note" name="admin_note" rows="6" placeholder="Görüşme notları, fiyat teklifi, teslim tarihi..."><?= e($quote->admin_note) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100"><?= icon('check', 16) ?> Kaydet</button>
        </form>

        <div class="widget widget-content-area mt-4">
            <form method="post" action="<?= e(admin_url('teklifler/' . $quote->id . '/sil')) ?>" data-ajax class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger w-100" data-confirm="Bu teklif talebi kalıcı olarak silinsin mi?"><?= icon('trash', 16) ?> Talebi Sil</button>
            </form>
        </div>
    </div>
</div>

<?php $this->start('scripts') ?>
<script>
document.getElementById('quoteForm').addEventListener('ajax:success', function (e) {
    var d = e.detail.data || {};
    var badge = document.getElementById('quoteStatusBadge');
    badge.className = 'badge badge-light-' + d.color;
    badge.textContent = d.label;
});
</script>
<?php $this->stop() ?>
