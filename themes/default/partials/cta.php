<?php /** Sayfa sonu "Teklif Al" çağrısı */ ?>
<section class="section pt-0">
    <div class="container">
        <div class="cta-box glass">
            <div>
                <h2>Projenizi konuşalım</h2>
                <p>Kısa bir form doldurun; ihtiyacınızı inceleyip 24 saat içinde size özel, ücretsiz teklif göndereyim.</p>
            </div>
            <div class="cta-actions">
                <a href="<?= e(url('teklif-al')) ?>" class="btn btn-primary"><?= icon('send', 18) ?> Teklif Al</a>
                <a href="<?= e(url('iletisim')) ?>" class="btn btn-ghost"><?= icon('message-square', 18) ?> Soru Sor</a>
            </div>
        </div>
    </div>
</section>
