<?php
/**
 * @var App\Core\View $this
 * @var App\Models\User $user
 */
$this->layout('main');
$photo = profile_photo();
?>
<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="section-title"><?= icon('user') ?> Profil fotoğrafı</div>
            <div class="row g-4 align-items-center">
                <div class="col-auto">
                    <?php if ($photo): ?>
                        <img src="<?= e($photo) ?>" id="photoPreview" class="resume-avatar" alt="Profil fotoğrafı">
                    <?php else: ?>
                        <img src="" id="photoPreview" class="resume-avatar d-none" alt="Profil fotoğrafı">
                        <span class="resume-avatar"><?= e($user->initials()) ?></span>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <p class="text-muted small mb-2">Bu fotoğraf <b>panelin sağ üstünde</b>, sitenizde ve <b>Özgeçmiş</b> sayfasında kullanılır. Buradan ya da Özgeçmiş'ten değiştirdiğinizde her yerde birlikte değişir. JPG, PNG veya WEBP; kare fotoğraflar en iyi görünür.</p>
                    <form method="post" action="<?= e(admin_url('hesabim/fotograf')) ?>" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center" data-ajax data-ajax-success="reload">
                        <?= csrf_field() ?>
                        <input type="file" class="form-control form-control-sm" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="#photoPreview" style="max-width:320px" required>
                        <button class="btn btn-primary btn-sm" type="submit"><?= icon('upload', 14) ?> Yükle</button>
                        <?php if ($photo): ?>
                            <button class="btn btn-outline-danger btn-sm" type="submit" form="photoRemove" data-confirm="Profil fotoğrafı kaldırılsın mı? Sitede ve panelde baş harfleriniz görünür."><?= icon('trash', 14) ?> Kaldır</button>
                        <?php endif; ?>
                    </form>
                    <?php if ($photo): ?>
                        <form method="post" action="<?= e(admin_url('hesabim/fotograf/sil')) ?>" id="photoRemove" data-ajax data-ajax-success="reload"><?= csrf_field() ?></form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<form method="post" action="<?= e(admin_url('hesabim')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-xl-6 col-12 layout-spacing">
            <div class="widget widget-content-area h-100">
                <div class="section-title"><?= icon('user') ?> Profil</div>
                <div class="mb-3">
                    <label class="form-label" for="name">Ad Soyad</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= e(old('name', $user->name)) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="username">Kullanıcı Adı</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= e(old('username', $user->username)) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email', $user->email)) ?>" required>
                </div>
                <small class="text-muted">Son giriş: <?= e(format_date($user->last_login_at, 'd.m.Y H:i')) ?></small>
            </div>
        </div>

        <div class="col-xl-6 col-12 layout-spacing">
            <div class="widget widget-content-area h-100">
                <div class="section-title"><?= icon('settings') ?> Şifre Değiştir</div>
                <p class="text-muted small">Şifrenizi değiştirmek istemiyorsanız bu alanları boş bırakın.</p>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Mevcut Şifre</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Yeni Şifre (en az 8 karakter)</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Yeni Şifre (Tekrar)</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="col-12 layout-spacing">
            <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Kaydet</button>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="section-title mb-0"><?= icon('shield') ?> "Beni Hatırla" ile Kayıtlı Cihazlar</div>
                <?php if (count(array_filter($devices, static fn ($d) => !$d['current']))): ?>
                    <form method="post" action="<?= e(admin_url('hesabim/cihazlar/digerleri')) ?>" data-ajax data-ajax-success="reload" class="m-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger btn-sm" data-confirm-title="Diğer cihazlardan çıkış" data-confirm-variant="danger" data-confirm-ok="Evet, çıkış yap"
                                data-confirm="Bu cihaz hariç tüm cihazlarda kayıtlı giriş kapatılacak; o cihazlarda tekrar şifre sorulacak."><?= icon('log-out', 14) ?> Diğer tüm cihazlardan çıkış yap</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($devices): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Cihaz</th><th>IP</th><th>Son kullanım</th><th>Geçerlilik</th><th class="text-end"></th></tr></thead>
                        <tbody>
                        <?php foreach ($devices as $d): ?>
                            <tr>
                                <td>
                                    <?= icon(str_contains($d['device'], 'Android') || str_contains($d['device'], 'iOS') ? 'smartphone' : 'monitor', 16) ?>
                                    <?= e($d['device']) ?>
                                    <?php if ($d['current']): ?><span class="badge badge-light-success ms-1">Bu cihaz</span><?php endif; ?>
                                </td>
                                <td class="text-muted"><?= e($d['ip']) ?></td>
                                <td class="text-muted"><?= e(time_ago($d['last_used_at'])) ?></td>
                                <td class="text-muted"><?= e(format_date($d['expires_at'], 'd.m.Y')) ?> tarihine kadar</td>
                                <td class="text-end">
                                    <form method="post" action="<?= e(admin_url('hesabim/cihazlar/' . $d['id'] . '/sil')) ?>" data-ajax data-ajax-success="remove" class="m-0">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-danger btn-sm" data-confirm-title="Cihazı kaldır" data-confirm-variant="danger" data-confirm-ok="Kaldır"
                                                data-confirm="<?= $d['current'] ? 'Bu tarayıcı artık hatırlanmayacak; tarayıcıyı kapatınca tekrar şifre sorulur.' : 'Bu cihazda tekrar şifre sorulacak.' ?>"><?= icon('trash', 14) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Kayıtlı cihaz yok. Girişte <strong>"Beni hatırla"</strong>yı işaretlerseniz o tarayıcıda 30 gün boyunca şifre sorulmaz.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
