<?php
/** @var App\Core\View $this */
$this->layout('auth');
?>
<div class="card mt-3 mb-3">
    <div class="card-body">
        <form method="post" action="<?= e(admin_url('giris')) ?>" class="row" autocomplete="on">
            <?= csrf_field() ?>
            <div class="col-md-12 mb-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?= brand_mark('site-brand-mark') ?>
                    <strong class="text-white"><?= e(setting('site_domain', '') ?: $siteTitle) ?></strong>
                </div>
                <h2>Giriş Yap</h2>
                <p><?= e($siteTitle) ?> yönetim paneline erişmek için bilgilerinizi girin.</p>
            </div>
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label" for="login">Kullanıcı adı veya e-posta</label>
                    <input type="text" class="form-control" id="login" name="login" value="<?= e(old('login')) ?>" required autofocus>
                </div>
            </div>
            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label" for="password">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>
            </div>
            <div class="col-12">
                <div class="mb-4">
                    <div class="form-check form-check-primary form-check-inline m-0">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="remember">Beni hatırla</label>
                    </div>
                    <div class="form-text mt-1">30 gün boyunca bu tarayıcıda tekrar şifre sorulmaz. Ortak kullanılan bilgisayarlarda işaretlemeyin.</div>
                </div>
            </div>
            <div class="col-12">
                <div class="mb-4">
                    <button type="submit" class="btn btn-secondary w-100">GİRİŞ YAP</button>
                </div>
            </div>
            <div class="col-12">
                <div class="text-center">
                    <p class="mb-0"><a href="<?= e(url()) ?>" class="text-warning">&larr; Siteye dön</a></p>
                </div>
            </div>
        </form>
    </div>
</div>
