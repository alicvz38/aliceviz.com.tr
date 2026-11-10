<?php
/**
 * Site Ayarları · İletişim & WhatsApp
 * @var App\Core\View $this
 * @var array $settings
 * @var array $sections
 * @var string $section
 */
$this->layout('main');
$s = static fn (string $key) => old($key, $settings[$key] ?? '');
?>
<?= $this->partial('settings-tabs', ['sections' => $sections, 'section' => $section]) ?>
<form method="post" action="<?= e(admin_url('ayarlar/iletisim')) ?>" data-ajax id="settingsForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-xl-8 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('mail') ?> İletişim Bilgileri</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="contact_email">İletişim E-postası</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= e($s('contact_email')) ?>">
                        <div class="form-text">Sitede görünür; yeni mesaj / teklif bildirimleri bu adrese gelir.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="cv_phone">Telefon</label>
                        <input type="tel" class="form-control" id="cv_phone" name="cv_phone" data-mask="phone" value="<?= e($s('cv_phone')) ?>">
                        <div class="form-text">İletişim sayfasında, alt bilgide ve özgeçmişte görünür. Boş bırakılırsa gösterilmez.</div>
                    </div>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('whatsapp') ?> WhatsApp</div>
                <div class="switch form-switch-custom switch-inline form-switch-success mb-2 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="whatsapp_enabled" name="whatsapp_enabled" value="1" <?= old('whatsapp_enabled', $settings['whatsapp_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <label class="switch-label" for="whatsapp_enabled">Telefonu WhatsApp'a bağla</label>
                </div>
                <div class="form-text mb-3">Açıkken sitedeki numaraya dokunan ziyaretçi WhatsApp'tan yazar. Kapalıyken numara normal arama bağlantısıdır.</div>
                <label class="form-label" for="whatsapp_message">Hazır mesaj</label>
                <textarea class="form-control" id="whatsapp_message" name="whatsapp_message" rows="2" maxlength="500" placeholder="Merhaba, <?= e($s('site_domain') ?: 'sitenizden') ?> üzerinden ulaşıyorum."><?= e($s('whatsapp_message')) ?></textarea>
                <div class="form-text mb-3">WhatsApp bu mesaj yazılı olarak açılır; ziyaretçi göndermeden önce değiştirebilir. Boş bırakılırsa mesajsız açılır.</div>
                <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
            </div>
        </div>
    </div>
</form>
