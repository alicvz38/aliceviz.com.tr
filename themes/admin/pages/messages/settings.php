<?php
/**
 * SMTP / e-posta ayarları
 * @var App\Core\View $this
 * @var array $settings
 * @var bool $hasPass
 */
$this->layout('main');
$s = static fn (string $k, string $d = '') => $settings[$k] ?? $d;
$domain = $s('site_domain') ?: 'alanadiniz.com';
// Hosting mail sunucusu: config.php > hosting > mail_server (SSL sertifikasındaki ad). Yoksa mail.alanadiniz
$hostServer = (string) (App\Core\Config::get('hosting.mail_server') ?: 'mail.' . $domain);
$cronPhp = (string) (App\Core\Config::get('hosting.cron_php') ?: '/usr/local/bin/php');
$presets = [
    'Hostingim (cPanel)' => [$hostServer, 465, 'ssl', $hostServer],
    'Gmail'              => ['smtp.gmail.com', 587, 'tls', 'imap.gmail.com'],
    'Yandex'             => ['smtp.yandex.com', 465, 'ssl', 'imap.yandex.com'],
    'Outlook'            => ['smtp-mail.outlook.com', 587, 'tls', 'outlook.office365.com'],
];
?>

<!-- cPanel ile e-posta kurulum rehberi -->
<div class="row">
    <div class="col-12 layout-spacing">
        <details class="widget widget-content-area mail-guide" <?= $s('smtp_host') === '' ? 'open' : '' ?>>
            <summary><?= icon('book-open') ?> <strong>Hostinginizin maili nasıl kurulur? (cPanel)</strong> <span class="text-muted">— adım adım</span></summary>
            <div class="row mt-3">
                <div class="col-lg-6">
                    <h6 class="text-white">1. cPanel'de e-posta hesabı açın</h6>
                    <ol class="mail-guide-steps">
                        <li>cPanel'e giriş yapın ve <strong>E-posta → E-posta Hesapları</strong> (Email Accounts) bölümünü açın.</li>
                        <li><strong>+ Oluştur</strong> (Create) düğmesine basın.</li>
                        <li>Alan adı olarak <code><?= e($domain) ?></code> seçin, kullanıcı adına örneğin <code>info</code> yazın (adresiniz <code>info@<?= e($domain) ?></code> olur).</li>
                        <li>Güçlü bir şifre belirleyin, depolama alanını isterseniz "Sınırsız" yapın ve <strong>Oluştur</strong>'a basın.</li>
                        <li>İsterseniz hesabın yanındaki <strong>Check Email</strong> ile webmail'e girip çalıştığını görebilirsiniz.</li>
                    </ol>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-white">2. Bu sayfaya bilgileri girin</h6>
                    <ol class="mail-guide-steps">
                        <li>Aşağıda <strong>"Hostingim (cPanel)"</strong> düğmesine basın: sunucu (<code><?= e($hostServer) ?></code>), port (465) ve güvenlik (SSL) kendiliğinden dolar.</li>
                        <li><strong>Kullanıcı adı:</strong> e-posta adresinin <u>tamamı</u> (ör. <code>info@<?= e($domain) ?></code>). <strong>Şifre:</strong> o hesabın şifresi.</li>
                        <li><strong>Gönderen e-posta</strong>'ya aynı adresi, <strong>Gönderen adı</strong>'na görünmesini istediğiniz adı yazın.</li>
                        <li>Gelen e-postaların panele düşmesi için <strong>E-posta Senkronizasyonu</strong> anahtarını açın (IMAP sunucusu da aynı: <code><?= e($hostServer) ?></code>, port 993).</li>
                        <li><strong>Test E-postası Gönder</strong> ile deneyin, sonra <strong>Ayarları Kaydet</strong>.</li>
                    </ol>
                </div>
            </div>
            <div class="alert alert-light-warning mt-2 mb-3">
                <?= icon('alert-triangle') ?> Sunucu adı olarak <code>mail.<?= e($domain) ?></code> değil <strong><code><?= e($hostServer) ?></code></strong> yazın.
                Hostinginizin SSL sertifikası bu ada kayıtlı; diğer adla şifreli bağlantı "sertifika" hatası verir.
            </div>
            <h6 class="text-white">3. Gelen e-postaların her dakika alınması (zamanlanmış görev)</h6>
            <p class="mb-2">cPanel → <strong>Gelişmiş → Cron Jobs</strong> → "Common Settings" listesinden <strong>Once Per Minute (* * * * *)</strong> seçin ve <strong>Command</strong> alanına şunu yapıştırıp <strong>Add New Cron Job</strong>'a basın:</p>
            <div class="mail-guide-cmd">
                <code id="cronCmd"><?= e($cronPhp . ' ' . ROOT_PATH . '/cron/mail-sync.php >/dev/null 2>&1') ?></code>
                <button type="button" class="btn btn-sm btn-outline-primary" id="copyCron"><?= icon('check', 14) ?> Kopyala</button>
            </div>
            <div class="form-text mt-2">Görev eklenmezse e-postalar yalnızca panel açıkken alınır. Site formundan gelen mesajlar ise her zaman anında düşer.</div>
        </details>
    </div>
</div>

<form method="post" action="<?= e(admin_url('mesajlar/ayarlar')) ?>" data-ajax data-ajax-success="none" id="smtpForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-xl-8 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('mail') ?> SMTP Sunucusu</div>

                <div class="mb-3">
                    <span class="form-label d-block mb-1">Hazır ayarlar:</span>
                    <?php foreach ($presets as $label => [$host, $port, $secure, $imap]): ?>
                        <button type="button" class="btn btn-sm <?= $label === 'Hostingim (cPanel)' ? 'btn-primary' : 'btn-outline-secondary' ?> me-1 mb-1 js-preset" data-host="<?= e($host) ?>" data-port="<?= (int) $port ?>" data-secure="<?= e($secure) ?>" data-imap="<?= e($imap) ?>"><?= e($label) ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="smtp_host">SMTP sunucusu</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= e($s('smtp_host')) ?>" placeholder="<?= e($hostServer) ?>">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label" for="smtp_port">Port</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= e($s('smtp_port', '587')) ?>">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label" for="smtp_secure">Güvenlik</label>
                        <select class="form-select" id="smtp_secure" name="smtp_secure">
                            <?php foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Yok (25)'] as $k => $l): ?>
                                <option value="<?= $k ?>" <?= $s('smtp_secure', 'tls') === $k ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="smtp_user">Kullanıcı adı (e-posta)</label>
                        <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?= e($s('smtp_user')) ?>" autocomplete="off" placeholder="info@<?= e($domain) ?>">
                        <div class="form-text">E-posta adresinin tamamını yazın.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="smtp_pass">Şifre</label>
                        <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" autocomplete="new-password" placeholder="<?= $hasPass ? '•••••••• (kayıtlı — değiştirmek için yazın)' : '' ?>">
                        <div class="form-text">Hosting maili: cPanel'de hesabı açarken belirlediğiniz şifre. (Gmail kullanırsanız normal şifre çalışmaz; Google Hesabı › Güvenlik › <strong>Uygulama şifreleri</strong>'nden oluşturulan 16 haneli şifre gerekir.)</div>
                    </div>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('refresh') ?> E-posta Senkronizasyonu (Posta kutusu → Panel)</div>
                <div class="switch form-switch-custom switch-inline form-switch-primary mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="imap_enabled" name="imap_enabled" value="1" <?= $s('imap_enabled', '1') === '1' ? 'checked' : '' ?>>
                    <label class="switch-label" for="imap_enabled">Posta kutusunun tüm klasörlerini panele aktar (Gelen, Gönderilen, Taslak, Spam, Çöp ve diğer klasörler)</label>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="imap_host">IMAP sunucusu</label>
                        <input type="text" class="form-control" id="imap_host" name="imap_host" value="<?= e($s('imap_host')) ?>" placeholder="<?= e($hostServer) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="imap_port">Port (SSL)</label>
                        <input type="number" class="form-control" id="imap_port" name="imap_port" value="<?= e($s('imap_port', '993')) ?>">
                    </div>
                </div>
                <label class="form-label d-block">Gelen Kutusu'na hangi e-postalar alınsın?</label>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="radio" name="imap_filter" id="imap_filter_all" value="all" <?= $s('imap_filter', 'all') === 'all' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="imap_filter_all"><strong>Tümü</strong> <small class="text-muted">(posta kutusuyla birebir)</small></label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="imap_filter" id="imap_filter_contacts" value="contacts" <?= $s('imap_filter', 'all') !== 'all' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="imap_filter_contacts"><strong>Yalnızca yazıştığım kişilerden gelenler</strong><br>
                        <small class="text-muted">Siteden mesaj / teklif gönderenler ve e-posta attığınız kişiler. Diğer klasörler yine tamamen aktarılır.</small></label>
                </div>
                <div class="form-text">
                    Yukarıdaki kullanıcı adı ve şifre kullanılır. Zamanlanmış görev (cron) eklendiyse senkronizasyon <strong>her dakika otomatik</strong> çalışır
                    (panel kapalıyken de). Posta kutusunda silinen e-postalar panelden de kalkar; panelde sildiğiniz e-posta posta kutusunda Çöp Kutusu'na taşınır.
                    Tüm klasörlerdeki e-postaların <strong>tamamı</strong> aktarılır: en yeniler hemen, eskiler arka planda en yeniden en eskiye doğru tamamlanır.
                    <?php if ($s('imap_last_error')): ?><br><span class="text-danger">Son hata: <?= e($s('imap_last_error')) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('user') ?> Gönderen</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="mail_from_email">Gönderen e-posta</label>
                        <input type="email" class="form-control" id="mail_from_email" name="mail_from_email" value="<?= e($s('mail_from_email')) ?>" placeholder="Boşsa kullanıcı adı kullanılır">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="mail_from_name">Gönderen adı</label>
                        <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" value="<?= e($s('mail_from_name')) ?>" placeholder="<?= e($s('site_title')) ?>">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="mail_signature">İmza</label>
                        <textarea class="form-control" id="mail_signature" name="mail_signature" rows="4" placeholder="Saygılarımla,&#10;<?= e($s('site_title')) ?>&#10;<?= e($s('cv_title')) ?>"><?= e($s('mail_signature')) ?></textarea>
                        <div class="form-text">Boş bırakılırsa ad, unvan ve site adresinizden otomatik oluşur.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('zap') ?> Otomatik e-postalar</div>
                <div class="switch form-switch-custom switch-inline form-switch-primary mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="mail_notify" name="mail_notify" value="1" <?= $s('mail_notify', '1') === '1' ? 'checked' : '' ?>>
                    <label class="switch-label" for="mail_notify">Yeni mesaj / teklif gelince bana e-posta gönder</label>
                </div>
                <div class="form-text mb-3">Bildirim, Site Ayarları'ndaki iletişim e-postasına (<?= e($s('contact_email') ?: 'tanımlı değil') ?>) gider. Bildirimi yanıtladığınızda yanıt doğrudan ziyaretçiye ulaşır.</div>
                <div class="switch form-switch-custom switch-inline form-switch-primary mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="mail_autoreply" name="mail_autoreply" value="1" <?= $s('mail_autoreply', '1') === '1' ? 'checked' : '' ?>>
                    <label class="switch-label" for="mail_autoreply">Ziyaretçiye "mesajınız alındı" otomatik yanıtı gönder</label>
                </div>
            </div>

            <?php $rt = App\Support\Realtime::status(); ?>
            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('zap') ?> Canlı Bildirim (WebSocket)</div>
                <?php if ($rt): ?>
                    <p class="mb-2"><span class="badge badge-light-success">Çalışıyor</span>
                        <small class="text-muted ms-1"><?= e($rt['address']) ?> · <?= (int) $rt['clients'] ?> bağlı panel</small></p>
                    <p class="small text-muted mb-2">
                        Posta kutusu dinleme: <strong><?= e(['idle' => 'açık (anlık)', 'error' => 'hata — yeniden deneniyor', 'off' => 'kapalı'][$rt['imap']] ?? $rt['imap']) ?></strong><br>
                        Başlangıç: <?= e(time_ago(date('Y-m-d H:i:s', (int) $rt['started']))) ?>
                    </p>
                <?php elseif (!App\Support\Realtime::installed()): ?>
                    <p class="mb-2"><span class="badge badge-light-info">Otomatik yenileme</span></p>
                    <p class="small text-muted mb-2">Paylaşımlı hostingde sürekli açık kalan WebSocket sunucusu çalıştırılamaz; panel yeni e-postaları ve sohbet mesajlarını birkaç saniyede bir kendiliğinden kontrol eder. E-postaların sunucuya çekilmesi için yukarıdaki <strong>cron</strong> görevini cPanel'de kurmanız yeterlidir.</p>
                <?php else: ?>
                    <p class="mb-2"><span class="badge badge-light-dark">Kapalı</span></p>
                    <p class="small text-muted mb-2">Sunucu kapalıyken panel 30 saniyede bir kontrol eder. Anlık bildirim için
                        <code>realtime\baslat.bat</code> dosyasını çalıştırın; bilgisayar açılınca kendiliğinden başlasın diye
                        <code>realtime\otomatik-baslat-kur.bat</code>.</p>
                <?php endif; ?>
                <div class="form-text">Yeni e-posta posta kutunuza düştüğü an panelde ve sayıda görünür. Canlı sunucu çalışırken arka plan zamanlanmış görevine gerek yoktur.</div>
            </div>

            <div class="widget widget-content-area mt-4 d-grid gap-2">
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Ayarları Kaydet</button>
                <button type="button" class="btn btn-outline-info" id="smtpTest"><?= icon('send', 16) ?> Test E-postası Gönder</button>
                <div class="form-text">Test, formdaki (kaydedilmemiş olsa bile) ayarlarla yapılır.</div>
            </div>
        </div>
    </div>
</form>

<?php $this->start('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('smtpForm');
    document.querySelectorAll('.js-preset').forEach(function (b) {
        b.addEventListener('click', function () {
            form.elements.smtp_host.value = b.dataset.host;
            form.elements.smtp_port.value = b.dataset.port;
            form.elements.smtp_secure.value = b.dataset.secure;
            form.elements.imap_host.value = b.dataset.imap || b.dataset.host;
            form.elements.imap_port.value = 993;
            Ajax.toast('Sunucu bilgileri dolduruldu. Şimdi kullanıcı adı (tam e-posta adresi) ve şifreyi yazın.', 'info', 5000);
            form.elements.smtp_user.focus();
        });
    });
    // Cron komutunu kopyala
    var copyCron = document.getElementById('copyCron');
    if (copyCron) {
        copyCron.addEventListener('click', function () {
            var text = document.getElementById('cronCmd').textContent;
            (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () {
                Ajax.toast('Komut kopyalandı. cPanel → Cron Jobs → Command alanına yapıştırın.', 'success');
            }, function () { Ajax.toast('Kopyalanamadı; komutu seçip elle kopyalayın.', 'warning'); });
        });
    }
    var test = document.getElementById('smtpTest');
    test.addEventListener('click', function () {
        test.disabled = true;
        test.classList.add('btn-loading');
        Ajax.post(<?= json_encode(admin_url('mesajlar/ayarlar/test')) ?>, new FormData(form)).then(function (res) {
            test.disabled = false;
            test.classList.remove('btn-loading');
            Ajax.showMessages(res);
        });
    });
    form.addEventListener('ajax:success', function () { form.elements.smtp_pass.value = ''; });
});
</script>
<?php $this->stop() ?>
