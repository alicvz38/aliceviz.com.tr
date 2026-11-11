<?php
/**
 * Telefon onayı kutusu (ücretsiz, WhatsApp ile)
 * Ziyaretçi teşekkür ekranındaki düğmeyle onay kodunu kendi WhatsApp'ından gönderir.
 * Kod size bu numaradan geldiyse numara gerçektir: "Doğrulandı" olarak işaretleyin.
 *
 * @var object $item    Message veya QuoteRequest (phone, phone_code, phone_verified_at)
 * @var string $action  İşaretleme adresi
 */
if (!$item->phone) {
    return;
}
$verified = !empty($item->phone_verified_at);
?>
<div class="widget widget-content-area mb-4 phone-verify<?= $verified ? ' is-verified' : '' ?>">
    <div class="section-title"><?= icon('whatsapp') ?> Telefon onayı</div>
    <?php if ($verified): ?>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge badge-light-success"><?= icon('check-circle', 13) ?> Doğrulandı</span>
            <span class="text-muted small"><?= e(format_date($item->phone_verified_at, 'd.m.Y H:i')) ?></span>
        </div>
        <p class="small text-muted mb-3">Bu numaranın kişiye ait olduğu WhatsApp ile onaylandı.</p>
    <?php elseif ($item->phone_code): ?>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge badge-light-warning">Onay bekliyor</span>
            <span class="small">Kod: <b class="text-white" style="letter-spacing:.12em"><?= e($item->phone_code) ?></b></span>
        </div>
        <p class="small text-muted mb-3">
            Kişi teşekkür ekranındaki düğmeyle size WhatsApp'tan <b>"Onay kodu: <?= e($item->phone_code) ?>"</b> yazan bir mesaj gönderir.
            Mesaj <b><?= e($item->phone) ?></b> numarasından geldiyse numara gerçektir.
        </p>
    <?php else: ?>
        <p class="small text-muted mb-3">Bu kayıt onay sistemi eklenmeden önce geldi; numarayı WhatsApp'ta yazışarak kendiniz doğrulayabilirsiniz.</p>
    <?php endif; ?>
    <form method="post" action="<?= e($action) ?>" data-ajax data-ajax-success="reload" class="m-0">
        <?= csrf_field() ?>
        <?php if ($verified): ?>
            <button class="btn btn-outline-secondary btn-sm w-100"><?= icon('refresh', 14) ?> Onayı geri al</button>
        <?php else: ?>
            <button class="btn btn-success btn-sm w-100"><?= icon('check', 14) ?> Kod geldi, Doğrulandı olarak işaretle</button>
        <?php endif; ?>
    </form>
</div>
