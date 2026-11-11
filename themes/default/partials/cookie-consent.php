<?php
/**
 * Çerez onayı: alttan kayarak açılan pencere + çerez ayarları
 * Tercih "cerez_tercihi" çerezine yazılır (App\Support\Consent). Altbilgideki "Çerez Ayarları" ile yeniden açılır.
 * @var App\Core\View $this
 */
use App\Support\Consent;

$stats = Consent::statistics() !== false;
$ads = Consent::ads();
?>
<div class="cookie-consent" id="cookieConsent" role="dialog" aria-modal="false" aria-labelledby="cookieTitle" data-decided="<?= Consent::decided() ? '1' : '0' ?>" hidden>
    <div class="cookie-card glass">
        <div class="cookie-main">
            <span class="cookie-icon" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.5A9 9 0 1 1 11.5 3a3 3 0 0 0 3.5 3.5 3 3 0 0 0 3.5 3.5 3 3 0 0 0 2.5 2.5z"/><circle cx="8.5" cy="10.5" r="1"/><circle cx="15.5" cy="15.5" r="1"/><circle cx="9.5" cy="15.5" r="1"/></svg>
            </span>
            <div class="cookie-text">
                <h2 id="cookieTitle">Çerezleri kullanıyoruz</h2>
                <p>
                    Sitenin çalışması için gerekli çerezlerin yanında, hangi projelerin kaç kişi tarafından incelendiğini
                    <b>anonim olarak</b> saymak (istatistik) ve reklamları ilgi alanınıza göre göstermek (reklam) için çerez kullanıyoruz.
                    Seçim tamamen sizin. <a href="<?= e(url('cerez-politikasi')) ?>">Çerez Politikası</a>
                </p>
            </div>
        </div>

        <div class="cookie-settings" id="cookieSettings" hidden>
            <label class="cookie-opt is-locked">
                <span><b>Zorunlu çerezler</b><small>Oturum, form güvenliği ve canlı destek. Site bunlarsız çalışmaz.</small></span>
                <input type="checkbox" checked disabled><i aria-hidden="true"></i>
            </label>
            <label class="cookie-opt">
                <span><b>İstatistik</b><small>Sayfa ve proje görüntülenme sayıları. Kim olduğunuz değil, yalnızca adet tutulur; IP adresiniz saklanmaz.</small></span>
                <input type="checkbox" name="s" <?= $stats ? 'checked' : '' ?>><i aria-hidden="true"></i>
            </label>
            <label class="cookie-opt">
                <span><b>Reklam</b><small>Google AdSense'in ilgi alanınıza göre reklam göstermesi. Kapalıyken de reklam çıkabilir ama kişiselleştirilmez.</small></span>
                <input type="checkbox" name="a" <?= $ads ? 'checked' : '' ?>><i aria-hidden="true"></i>
            </label>
        </div>

        <div class="cookie-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-cookie="settings" aria-controls="cookieSettings" aria-expanded="false">Ayarlar</button>
            <button type="button" class="btn btn-ghost btn-sm" data-cookie="necessary">Yalnızca zorunlu</button>
            <button type="button" class="btn btn-ghost btn-sm" data-cookie="save" hidden>Seçimi kaydet</button>
            <button type="button" class="btn btn-primary btn-sm" data-cookie="all"><span>Tümünü kabul et</span></button>
        </div>
    </div>
</div>
