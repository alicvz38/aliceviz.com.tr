<?php
/**
 * İletişim — ziyaretçi kendi e-posta adresiyle mesaj gönderir (AJAX)
 * @var App\Core\View $this
 * @var string $subject
 */
$this->layout('main');
?>
<section class="page-header">
    <div class="container">
        <span class="eyebrow">İletişim</span>
        <h1>Bana <span class="gradient-text">yazın</span></h1>
        <p class="lead">Sorularınız, iş birliği teklifleriniz ya da sadece merhaba demek için. Mesajınız doğrudan bana ulaşır; size WhatsApp ve e-posta üzerinden dönüş yaparım.</p>
    </div>
</section>

<section class="section pt-0">
    <div class="container contact-layout">
        <div>
            <form method="post" action="<?= e(url('iletisim')) ?>" class="form-card glass" data-ajax data-ajax-success="none" id="contactForm" novalidate>
                <?= csrf_field() ?>
                <div class="hp" aria-hidden="true"><label>Web siteniz <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                <div class="field-row two">
                    <div class="field">
                        <label for="c_name">Ad Soyad <em>*</em></label>
                        <input type="text" id="c_name" name="name" maxlength="100" required value="<?= e(old('name')) ?>" autocomplete="name">
                    </div>
                    <div class="field">
                        <label for="c_email">E-posta adresiniz <em>*</em></label>
                        <input type="email" id="c_email" name="email" maxlength="150" required value="<?= e(old('email')) ?>" autocomplete="email" placeholder="size bu adresten de dönüş yapılır">
                    </div>
                </div>
                <div class="field-row two">
                    <div class="field">
                        <label for="c_phone">Telefon (WhatsApp) <em>*</em></label>
                        <input type="tel" id="c_phone" name="phone" data-mask="phone" required value="<?= e(old('phone')) ?>" autocomplete="tel" placeholder="size WhatsApp'tan dönüş yapılır">
                    </div>
                    <div class="field">
                        <label for="c_subject">Konu <em>*</em></label>
                        <input type="text" id="c_subject" name="subject" maxlength="200" required value="<?= e(old('subject', $subject)) ?>">
                    </div>
                </div>
                <div class="field">
                    <label for="c_message">Mesajınız <em>*</em></label>
                    <textarea id="c_message" name="message" rows="8" maxlength="5000" required><?= e(old('message')) ?></textarea>
                </div>
                <label class="consent">
                    <input type="checkbox" name="consent" value="1" required>
                    <span>Bilgilerimin yalnızca mesajıma yanıt verilmesi amacıyla kullanılmasını kabul ediyorum.</span>
                </label>
                <button type="submit" class="btn btn-primary btn-block"><span><?= icon('send', 18) ?> Mesajı Gönder</span></button>
            </form>

            <div class="form-success glass" id="contactSuccess" hidden>
                <span class="success-icon"><?= icon('check-circle', 40) ?></span>
                <h2>Mesajınız gönderildi!</h2>
                <p id="contactSuccessText"></p>
                <div class="verify-wa" id="contactVerify" hidden>
                    <strong>Son adım: numaranızı onaylayın</strong>
                    <span>Gerçek taleplere öncelik veriyorum. Aşağıdaki düğme WhatsApp'ı hazır bir onay mesajıyla açar; yalnızca <b>gönder</b>'e basmanız yeterli.</span>
                    <a href="#" class="btn btn-wa" target="_blank" rel="noopener"><?= icon('whatsapp', 20) ?> <span>WhatsApp ile Onayla</span></a>
                    <small>Onay kodunuz: <b data-code></b></small>
                </div>
                <button type="button" class="btn btn-ghost" id="contactAgain"><?= icon('edit', 18) ?> Yeni mesaj yaz</button>
            </div>
        </div>

        <aside class="contact-aside">
            <div class="glass contact-info">
                <h3>Doğrudan iletişim</h3>
                <?php if ($site['email']): ?>
                    <button type="button" class="contact-line js-copy-email" data-email="<?= e($site['email']) ?>">
                        <span class="contact-line-icon"><?= icon('mail', 18) ?></span>
                        <span><small>E-posta (kopyalamak için tıklayın)</small><?= e($site['email']) ?></span>
                    </button>
                <?php endif; ?>
                <?php if ($site['phone'] && $site['whatsapp']): ?>
                    <div class="contact-line contact-line-wa">
                        <a class="contact-line-main" href="<?= e($site['whatsapp']) ?>" target="_blank" rel="noopener">
                            <span class="contact-line-icon is-wa"><?= icon('whatsapp', 19) ?></span>
                            <span><small>WhatsApp (yazmak için dokunun)</small><?= e($site['phone']) ?></span>
                        </a>
                    </div>
                <?php elseif ($site['phone']): ?>
                    <a class="contact-line" href="<?= e(phone_href($site['phone'])) ?>">
                        <span class="contact-line-icon"><?= icon('phone', 18) ?></span>
                        <span><small>Telefon (aramak için dokunun)</small><?= e($site['phone']) ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($site['location']): ?>
                    <div class="contact-line">
                        <span class="contact-line-icon"><?= icon('map-pin', 18) ?></span>
                        <span><small>Konum</small><?= e($site['location']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="contact-line">
                    <span class="contact-line-icon"><?= icon('clock', 18) ?></span>
                    <span><small>Yanıt süresi</small>Genellikle 24 saat içinde</span>
                </div>
                <?= $this->partial('social-links') ?>
            </div>

            <div class="glass contact-info">
                <h3>Proje mi yaptırmak istiyorsunuz?</h3>
                <p>Web sitesi veya masaüstü uygulama için detaylı teklif formunu kullanın.</p>
                <a href="<?= e(url('teklif-al')) ?>" class="btn btn-primary btn-sm"><?= icon('send', 16) ?> Teklif Al</a>
            </div>
        </aside>
    </div>
</section>
