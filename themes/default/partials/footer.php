<?php /** @var App\Core\View $this */ ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= e(url()) ?>" class="brand">
                <span class="brand-mark"><?= icon('code', 18) ?></span>
                <span class="brand-text"><?= e($site['domain'] ?: $site['title']) ?></span>
            </a>
            <p><?= e($site['description']) ?></p>
            <?= $this->partial('social-links') ?>
        </div>

        <div class="footer-col">
            <h4>Hizmetler</h4>
            <?php foreach ($services as $svc): ?>
                <a href="<?= e(url('hizmetler/' . $svc['slug'])) ?>"><?= e($svc['title']) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(url('hizmetler')) ?>">Çalışma Süreci</a>
            <a href="<?= e(url('teklif-al')) ?>">Ücretsiz Teklif Al</a>
        </div>

        <div class="footer-col">
            <h4>Sayfalar</h4>
            <a href="<?= e(url('projeler')) ?>">Projeler</a>
            <a href="<?= e(url('hakkimda')) ?>">Hakkımda / Özgeçmiş</a>
            <a href="<?= e(url('iletisim')) ?>">İletişim</a>
        </div>

        <div class="footer-col">
            <h4>İletişim</h4>
            <?php if ($site['email']): ?>
                <button type="button" class="footer-link js-copy-email" data-email="<?= e($site['email']) ?>"><?= icon('mail', 14) ?> <?= e($site['email']) ?></button>
            <?php endif; ?>
            <?php if ($site['phone']): ?><a href="<?= e(phone_href($site['phone'])) ?>"><?= icon('phone', 14) ?> <?= e($site['phone']) ?></a><?php endif; ?>
            <?php if ($site['phone'] && $site['whatsapp']): ?><a href="<?= e($site['whatsapp']) ?>" class="footer-wa" target="_blank" rel="noopener"><?= icon('whatsapp', 14) ?> WhatsApp'tan yazın</a><?php endif; ?>
            <?php if ($site['location']): ?><span><?= icon('map-pin', 14) ?> <?= e($site['location']) ?></span><?php endif; ?>
            <a href="<?= e(url('iletisim')) ?>"><?= icon('message-square', 14) ?> Mesaj gönderin</a>
        </div>
    </div>
    <div class="container footer-bottom">
        &copy; <?= date('Y') ?> <a href="<?= e(url()) ?>"><?= e($site['domain'] ? 'www.' . $site['domain'] : $site['title']) ?></a> &mdash; Tüm hakları saklıdır.
    </div>
</footer>
