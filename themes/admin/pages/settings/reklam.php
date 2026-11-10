<?php
/**
 * Site Ayarları · Reklam (Google AdSense)
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
$client = (string) ($settings['adsense_client'] ?? '');
$on = ($settings['adsense_enabled'] ?? '0') === '1';
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<form method="post" action="<?= e(admin_url('ayarlar/reklam')) ?>" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row justify-content-center">
        <div class="col-xl-8 col-12 layout-spacing">

            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('dollar-sign') ?> Google AdSense</div>

                <div class="mb-3">
                    <label class="form-label" for="adsense_client">Yayıncı kimliği</label>
                    <input type="text" class="form-control" id="adsense_client" name="adsense_client" value="<?= e($s('adsense_client')) ?>" placeholder="ca-pub-1234567890123456 veya AdSense kodunun tamamı">
                    <div class="form-text">AdSense'in "Kodu sitenize yapıştırın" adımında verdiği <code>&lt;script ...&gt;</code> kodunun tamamını yapıştırabilirsiniz; kimlik otomatik ayıklanır.</div>
                </div>

                <div class="switch form-switch-custom switch-inline form-switch-success mb-2 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="adsense_enabled" name="adsense_enabled" value="1" <?= $on ? 'checked' : '' ?>>
                    <label class="switch-label" for="adsense_enabled">Reklam kodunu sitede etkinleştir</label>
                </div>
                <div class="form-text mb-3">Açıkken AdSense kodu sitenin tüm sayfalarına eklenir (panel, bakım ve hata sayfaları hariç). Reklamların nerede ve ne sıklıkta çıkacağını AdSense panelindeki <strong>Otomatik reklamlar</strong> ayarından yönetirsiniz.</div>

                <div class="ads-status mb-3">
                    <span>Durum: <?= $client === '' ? '<b class="text-muted">Kimlik girilmemiş</b>' : ($on ? '<b class="text-success">Reklam kodu sitede</b>' : '<b class="text-warning">Kimlik kayıtlı, reklamlar kapalı</b>') ?></span>
                    <span>ads.txt: <a href="<?= e(absolute_url('ads.txt')) ?>" target="_blank"><?= e(absolute_url('ads.txt')) ?></a> <?= $client === '' ? '<small class="text-muted">(kimlik girilince oluşur)</small>' : '' ?></span>
                </div>
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
            </div>

            <details class="widget widget-content-area mt-4 mail-guide">
                <summary><?= icon('info') ?> AdSense'e nasıl başvurulur?</summary>
                <ol class="mt-3 mb-0">
                    <li><a href="https://adsense.google.com" target="_blank" rel="noopener">adsense.google.com</a> adresinde Google hesabınızla kaydolun ve sitenizi (<code><?= e(preg_replace('#^https?://#', '', rtrim(absolute_url(), '/'))) ?></code>) ekleyin.</li>
                    <li>"Sitenizi AdSense'e bağlayın" adımında verilen kodu yukarıdaki alana yapıştırın, <strong>anahtarı açın</strong> ve kaydedin.</li>
                    <li>AdSense'te <strong>ads.txt</strong> ve doğrulama adımlarını onaylayıp "İnceleme iste" deyin. İnceleme birkaç gün ile birkaç hafta sürebilir.</li>
                    <li>Onaydan sonra AdSense → <strong>Reklamlar → Siteye göre</strong> bölümünden otomatik reklamları açın ve hangi reklam türlerinin çıkacağını seçin.</li>
                    <li>Avrupa'dan gelen ziyaretçiler için AdSense → <strong>Gizlilik ve mesajlar</strong> bölümünden Google'ın ücretsiz çerez onay mesajını (GDPR) etkinleştirin.</li>
                </ol>
                <div class="form-text mt-3">Not: Google, özgün ve yeterli içeriği olan siteleri onaylar. Proje sayfalarına açıklama eklemek ve düzenli içerik yayınlamak onay şansını artırır.</div>
            </details>
        </div>
    </div>
</form>
