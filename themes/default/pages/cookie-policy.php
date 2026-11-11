<?php
/**
 * Çerez politikası: kullanılan çerezler ve tercihlerin değiştirilmesi
 * @var App\Core\View $this
 */
$this->layout('main');
$cookies = [
    ['ACSESSID', 'Zorunlu', 'Oturum ve form güvenliği (CSRF). Formların güvenle gönderilmesini sağlar.', 'Tarayıcı kapanınca'],
    ['cerez_tercihi', 'Zorunlu', 'Bu penceredeki çerez seçiminiz. Tekrar sorulmaması için saklanır.', '6 ay'],
    ['ac_chat', 'Zorunlu', 'Canlı destek kullanırsanız sohbetinizin size gösterilmesi.', '30 gün'],
    ['ziyaretci', 'İstatistik', 'Rastgele, anonim bir numara. Aynı gün aynı sayfaya tekrar baktığınızda sayının artmaması için kullanılır. Kim olduğunuzu göstermez.', '1 yıl'],
    ['Google AdSense (__gads, __gpi vb.)', 'Reklam', 'Google\'ın reklam gösterimi ve izne bağlı olarak ilgi alanına göre reklam seçimi. Google tarafından yerleştirilir.', 'Google\'a göre değişir'],
];
?>
<section class="page-header">
    <div class="container">
        <span class="eyebrow">Gizlilik</span>
        <h1>Çerez <span class="gradient-text">Politikası</span></h1>
        <p class="lead">Bu sitede hangi çerezlerin neden kullanıldığını ve tercihlerinizi nasıl değiştirebileceğinizi burada bulabilirsiniz.</p>
    </div>
</section>

<section class="section pt-0">
    <div class="container legal">
        <div class="glass legal-card">
            <h2>Çerez nedir?</h2>
            <p>Çerezler, ziyaret ettiğiniz sitenin tarayıcınıza kaydettiği küçük metin dosyalarıdır. Bu sitede çerezler yalnızca aşağıda yazan amaçlarla kullanılır; kişisel bilgileriniz satılmaz veya paylaşılmaz.</p>

            <h2>İstatistik nasıl tutuluyor?</h2>
            <p>Hangi projenin ve sayfanın kaç kişi tarafından incelendiği <b>günlük ve tekil</b> olarak sayılır: aynı gün aynı sayfaya tekrar bakmanız sayıyı artırmaz. Yalnızca <b>adet</b> tutulur.</p>
            <ul>
                <li><b>İstatistik çerezine izin verdiyseniz:</b> tarayıcınıza rastgele, anonim bir numara (<code>ziyaretci</code>) kaydedilir.</li>
                <li><b>Henüz seçim yapmadıysanız:</b> çerez kullanılmaz. IP adresiniz ve tarayıcı bilginiz, her gün değişen gizli bir anahtarla geri çevrilemez bir özete dönüştürülür. <b>IP adresiniz kaydedilmez</b>, özet de ertesi gün silinir.</li>
                <li><b>İstatistiği reddettiyseniz:</b> ziyaretiniz hiç sayılmaz.</li>
            </ul>

            <h2>Kullanılan çerezler</h2>
            <div class="legal-table-wrap">
                <table class="legal-table">
                    <thead><tr><th>Çerez</th><th>Tür</th><th>Amaç</th><th>Süre</th></tr></thead>
                    <tbody>
                    <?php foreach ($cookies as [$name, $type, $purpose, $time]): ?>
                        <tr><td><code><?= e($name) ?></code></td><td><span class="legal-tag<?= $type === 'Zorunlu' ? '' : ' is-optional' ?>"><?= e($type) ?></span></td><td><?= e($purpose) ?></td><td><?= e($time) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h2>Tercihlerinizi değiştirin</h2>
            <p>Zorunlu çerezler dışındakileri dilediğiniz zaman açıp kapatabilirsiniz. Ayrıca tarayıcınızın ayarlarından tüm çerezleri silebilirsiniz.</p>
            <button type="button" class="btn btn-primary" data-cookie-open><span>Çerez Ayarlarını Aç</span></button>
        </div>
    </div>
</section>
