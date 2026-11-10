<?php
/**
 * Proje ekleme / düzenleme formu (ikisi için ortak)
 * @var App\Core\View $this
 * @var App\Models\Project $project
 */
$this->layout('main');
$isEdit = (bool) $project->id;
$action = $isEdit ? admin_url('projeler/' . $project->id . '/guncelle') : admin_url('projeler/kaydet');
// Hata sonrası eski girdi, yoksa kayıttaki değer
$v = static fn (string $key) => old($key, $project->$key ?? '');
$checked = static function (string $key) use ($project): string {
    $old = App\Core\Session::old('title', null) !== null ? App\Core\Session::old($key, null) : ($project->$key ?? null);
    return $old ? 'checked' : '';
};
?>
<?php $this->start('pageActions') ?>
<div class="d-flex gap-2">
    <?php if ($isEdit && $project->is_published): ?>
        <a href="<?= e($project->url()) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('eye', 16) ?> Sitede Gör</a>
    <?php endif; ?>
    <a href="<?= e(admin_url('projeler')) ?>" class="btn btn-outline-primary btn-sm"><?= icon('arrow-left', 16) ?> Listeye Dön</a>
</div>
<?php $this->stop() ?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" data-ajax id="projectForm">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-xl-8 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('edit') ?> Proje Bilgileri</div>

                <div class="mb-3">
                    <label class="form-label" for="title">Proje Adı <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= e($v('title')) ?>" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="slug">Adres (slug)</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= e(url('projeler/')) ?></span>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= e($v('slug')) ?>" placeholder="otomatik-olusturulur">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="summary">Kısa Açıklama</label>
                    <textarea class="form-control" id="summary" name="summary" rows="3" maxlength="500"><?= e($v('summary')) ?></textarea>
                    <div class="form-text">Kartlarda ve arama motorlarında görünür (en fazla 500 karakter).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="content">Detaylı Açıklama</label>
                    <textarea class="form-control" id="content" name="content" rows="16"><?= e($v('content')) ?></textarea>
                    <div class="form-text">Proje sayfasında "Proje Hakkında" bölümünde gösterilir. README ayrıca GitHub'dan otomatik çekilebilir.</div>
                </div>
                <div class="mb-3">
                    <span class="form-label d-block">Proje türü</span>
                    <?php $kindNow = old('kind', $project->kind ?? ''); ?>
                    <div class="btn-group kind-picker" role="radiogroup" aria-label="Proje türü">
                        <?php foreach (['' => ['Belirtilmemiş', 'minus']] + App\Models\Project::KINDS as $k => [$label, $ico]): ?>
                            <input type="radio" class="btn-check" name="kind" id="kind_<?= e($k ?: 'none') ?>" value="<?= e($k) ?>" <?= (string) $kindNow === (string) $k ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary btn-sm" for="kind_<?= e($k ?: 'none') ?>"><?= icon($ico, 14) ?> <?= e($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text">Sitede kartlarda etiket olarak görünür ve Projeler sayfasında Web / Uygulama filtresinde kullanılır.</div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="language">Ana Dil</label>
                        <input type="text" class="form-control" id="language" name="language" value="<?= e($v('language')) ?>" placeholder="PHP, JavaScript...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="tags">Etiketler</label>
                        <input type="text" class="form-control" id="tags" name="tags" value="<?= e($v('tags')) ?>" placeholder="laravel, api, mysql">
                        <div class="form-text">Virgülle ayırın.</div>
                    </div>
                </div>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('github') ?> GitHub & Bağlantılar</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="github_repo">GitHub Deposu</label>
                        <input type="text" class="form-control" id="github_repo" name="github_repo" value="<?= e($v('github_repo')) ?>" placeholder="kullanici/depo-adi">
                        <div class="form-text">Doldurulursa README ve yıldız bilgisi GitHub'dan alınır.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="github_url">GitHub Adresi</label>
                        <input type="url" class="form-control" id="github_url" name="github_url" value="<?= e($v('github_url')) ?>" placeholder="https://github.com/...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="demo_url">Canlı Demo Adresi</label>
                        <input type="url" class="form-control" id="demo_url" name="demo_url" value="<?= e($v('demo_url')) ?>" placeholder="https://...">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label" for="stars">Yıldız</label>
                        <input type="number" min="0" class="form-control" id="stars" name="stars" value="<?= e($v('stars') ?: 0) ?>">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label" for="forks">Fork</label>
                        <input type="number" min="0" class="form-control" id="forks" name="forks" value="<?= e($v('forks') ?: 0) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-12 layout-spacing">
            <div class="widget widget-content-area">
                <div class="section-title"><?= icon('settings') ?> Yayın</div>

                <div class="switch form-switch-custom switch-inline form-switch-success mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" <?= $checked('is_published') ?>>
                    <label class="switch-label" for="is_published">Sitede yayınla</label>
                </div>
                <div class="switch form-switch-custom switch-inline form-switch-warning mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" <?= $checked('is_featured') ?>>
                    <label class="switch-label" for="is_featured">Ana sayfada öne çıkar</label>
                </div>
                <div class="switch form-switch-custom switch-inline form-switch-info mb-3 d-block">
                    <input class="switch-input" type="checkbox" role="switch" id="show_readme" name="show_readme" value="1" <?= $checked('show_readme') ?>>
                    <label class="switch-label" for="show_readme">GitHub README'sini göster</label>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="sort_order">Sıralama</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= e($v('sort_order') ?: 0) ?>">
                    <div class="form-text">Küçük sayı önce gösterilir.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100"><?= icon('check', 16) ?> <?= $isEdit ? 'Değişiklikleri Kaydet' : 'Projeyi Ekle' ?></button>
            </div>

            <div class="widget widget-content-area mt-4">
                <div class="section-title"><?= icon('layers') ?> Kapak Görseli</div>
                <img id="imagePreview" src="<?= e($project->imageUrl() ?? '') ?>" class="img-preview mb-3 <?= $project->imageUrl() ? '' : 'd-none' ?>" alt="Önizleme">
                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="form-text">JPG, PNG, WEBP veya GIF — en fazla 2 MB. Önerilen oran 16:9.</div>
                <?php if ($isEdit): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                        <label class="form-check-label" for="remove_image">Mevcut görseli kaldır</label>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($isEdit): ?>
                <div class="widget widget-content-area mt-4 small text-muted">
                    Eklenme: <?= e(format_date($project->created_at, 'd.m.Y H:i')) ?><br>
                    Güncelleme: <?= e(format_date($project->updated_at, 'd.m.Y H:i')) ?><br>
                    <?php if ($project->pushed_at): ?>Son GitHub push: <?= e(time_ago($project->pushed_at)) ?><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>
