<?php
/**
 * Teklif Al formu — AJAX ile gönderilir
 * @var App\Core\View $this
 * @var array $services
 * @var string $selected
 * @var string $selectedPackage
 * @var array $budgets
 * @var array $deadlines
 */
$this->layout('main');
$sel = old('service', $selected);
$selPkg = old('package', $selectedPackage);
$form = App\Support\ServiceCatalog::form();
?>
<section class="page-header">
    <div class="container narrow">
        <span class="eyebrow">Ücretsiz Teklif</span>
        <h1 class="gradient-title"><?= e($form['title']) ?></h1>
        <p class="lead"><?= e($form['lead']) ?></p>
    </div>
</section>

<section class="section pt-0">
    <div class="container narrow">
        <form method="post" action="<?= e(url('teklif-al')) ?>" class="form-card glass" data-ajax data-ajax-success="none" id="quoteForm" novalidate>
            <?= csrf_field() ?>
            <!-- Bot tuzağı: insanlar görmez -->
            <div class="hp" aria-hidden="true"><label>Web siteniz <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <fieldset class="form-section">
                <legend><span>1</span> Ne yaptırmak istiyorsunuz?</legend>
                <div class="choice-grid">
                    <?php foreach ($services as $key => $svc): ?>
                        <label class="choice" style="--svc: <?= e($svc['color']) ?>">
                            <input type="radio" name="service" value="<?= e($key) ?>" <?= $sel === $key ? 'checked' : '' ?>>
                            <span class="choice-body">
                                <span class="service-icon sm"><?= icon($svc['icon'], 20) ?></span>
                                <strong><?= e($svc['short']) ?></strong>
                                <small><?= e($svc['tagline']) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (!empty($form['other'])): ?>
                    <label class="choice" style="--svc: #f43f5e">
                        <input type="radio" name="service" value="other" <?= $sel === 'other' ? 'checked' : '' ?>>
                        <span class="choice-body">
                            <span class="service-icon sm"><?= icon('code', 20) ?></span>
                            <strong>Diğer</strong>
                            <small>Mobil, API, entegrasyon, bakım...</small>
                        </span>
                    </label>
                    <?php endif; ?>
                </div>

                <div class="field-row">
                    <div class="field" id="packageField">
                        <label for="package">Paket</label>
                        <select id="package" name="package">
                            <option value="">Emin değilim / önerinizi isterim</option>
                            <?php foreach ($services as $key => $svc): ?>
                                <optgroup label="<?= e($svc['short']) ?>" data-service="<?= e($key) ?>">
                                    <?php foreach ($svc['packages'] as $pkey => $pkg): ?>
                                        <option value="<?= e($pkey) ?>" <?= $selPkg === $pkey && $sel === $key ? 'selected' : '' ?>><?= e($pkg['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="budget">Tahmini bütçe</label>
                        <select id="budget" name="budget">
                            <?php foreach ($budgets as $b): ?><option <?= old('budget') === $b ? 'selected' : '' ?>><?= e($b) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="deadline">Ne zaman lazım?</label>
                        <select id="deadline" name="deadline">
                            <?php foreach ($deadlines as $d): ?><option <?= old('deadline') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend><span>2</span> Projenizden bahsedin</legend>
                <div class="field">
                    <label for="message">Açıklama <em>*</em></label>
                    <textarea id="message" name="message" rows="7" maxlength="5000" required placeholder="Projenizi detaylı açıklayın"><?= e(old('message')) ?></textarea>
                    <div class="field-hint"><span id="msgCount">0</span> / 5000</div>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend><span>3</span> İletişim bilgileriniz</legend>
                <div class="field-row two">
                    <div class="field">
                        <label for="name">Ad Soyad <em>*</em></label>
                        <input type="text" id="name" name="name" maxlength="100" required value="<?= e(old('name')) ?>" autocomplete="name">
                    </div>
                    <div class="field">
                        <label for="email">E-posta <em>*</em></label>
                        <input type="email" id="email" name="email" maxlength="150" required value="<?= e(old('email')) ?>" autocomplete="email">
                    </div>
                    <div class="field">
                        <label for="phone">Telefon <small>(isteğe bağlı)</small></label>
                        <input type="tel" id="phone" name="phone" data-mask="phone" value="<?= e(old('phone')) ?>" autocomplete="tel">
                    </div>
                    <div class="field">
                        <label for="company">Firma <small>(isteğe bağlı)</small></label>
                        <input type="text" id="company" name="company" maxlength="150" value="<?= e(old('company')) ?>" autocomplete="organization">
                    </div>
                </div>
                <label class="consent">
                    <input type="checkbox" name="consent" value="1" required>
                    <span>Bilgilerimin yalnızca bu talebe dönüş yapılması amacıyla kullanılmasını kabul ediyorum.</span>
                </label>
            </fieldset>

            <button type="submit" class="btn btn-primary btn-block"><span><?= icon('send', 18) ?> Teklif Talebini Gönder</span></button>
        </form>

        <div class="form-success glass" id="quoteSuccess" hidden>
            <span class="success-icon"><?= icon('check-circle', 40) ?></span>
            <h2>Talebiniz alındı!</h2>
            <p id="quoteSuccessText"></p>
            <div class="hero-actions">
                <a href="<?= e(url('projeler')) ?>" class="btn btn-ghost"><?= icon('layers', 18) ?> Projelerime göz atın</a>
                <a href="<?= e(url()) ?>" class="btn btn-primary"><?= icon('home', 18) ?> Ana sayfa</a>
            </div>
        </div>
    </div>
</section>
