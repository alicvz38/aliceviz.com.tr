<?php
/**
 * @var App\Core\View $this
 * @var App\Models\Project[] $projects
 */
$this->layout('main');
?>
<?php $this->start('pageActions') ?>
<div class="d-flex gap-2">
    <a href="<?= e(admin_url('github')) ?>" class="btn btn-outline-info btn-sm"><?= icon('github', 16) ?> GitHub'dan Aktar</a>
    <a href="<?= e(admin_url('projeler/ekle')) ?>" class="btn btn-primary btn-sm"><?= icon('plus', 16) ?> Yeni Proje</a>
</div>
<?php $this->stop() ?>

<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="section-title mb-0"><?= icon('folder') ?> Projeler (<?= count($projects) ?>)</div>
            </div>

            <?php if ($projects): ?>
            <div>
                <table class="table table-hover dt-table-hover" id="projectsTable" style="width:100%" data-datatable data-no-sort="0,7" data-order="[[5,&quot;asc&quot;]]">
                    <thead>
                    <tr>
                        <th style="width:70px"></th>
                        <th>Proje</th>
                        <th>Dil</th>
                        <th>Tür</th>
                        <th class="text-center">⭐ / 🍴</th>
                        <th class="text-center">Sıra</th>
                        <th class="text-center">Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects as $p): ?>
                        <tr>
                            <td>
                                <?php if ($p->imageUrl()): ?>
                                    <img src="<?= e($p->imageUrl()) ?>" class="table-thumb" alt="">
                                <?php else: ?>
                                    <span class="table-thumb"><?= icon('code') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= e(admin_url('projeler/' . $p->id . '/duzenle')) ?>" class="fw-bold"><?= e($p->title) ?></a>
                                <?php if ($p->is_featured): ?><span class="badge badge-light-warning ms-1">Öne çıkan</span><?php endif; ?>
                                <div class="text-muted small">
                                    <?= $p->github_repo ? icon('github', 12) . ' ' . e($p->github_repo) : '/projeler/' . e($p->slug) ?>
                                </div>
                            </td>
                            <td><?php if ($p->language): ?><span class="lang-dot" style="background: <?= e($p->languageColor()) ?>"></span><?= e($p->language) ?><?php else: ?>-<?php endif; ?></td>
                            <td data-order="<?= e($p->kindLabel() ?? 'zz') ?>">
                                <!-- Seçilince hemen kaydedilir (AJAX) -->
                                <select class="form-select form-select-sm kind-select <?= $p->kind ? 'has-kind' : '' ?>" data-url="<?= e(admin_url('projeler/' . $p->id . '/tur')) ?>" aria-label="Proje türü">
                                    <option value="">— Seçin —</option>
                                    <?php foreach (App\Models\Project::KINDS as $k => [$label]): ?>
                                        <option value="<?= e($k) ?>" <?= $p->kind === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center" data-col="stats"><?= (int) $p->stars ?> / <?= (int) $p->forks ?></td>
                            <td class="text-center"><?= (int) $p->sort_order ?></td>
                            <td class="text-center">
                                <form method="post" action="<?= e(admin_url('projeler/' . $p->id . '/durum')) ?>" class="m-0 js-toggle-status" data-ajax data-ajax-success="none">
                                    <?= csrf_field() ?>
                                    <button class="badge border-0 <?= $p->is_published ? 'badge-light-success' : 'badge-light-dark' ?>" title="Durumu değiştir">
                                        <?= $p->is_published ? 'Yayında' : 'Taslak' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="<?= e($p->url()) ?>" target="_blank" class="btn btn-outline-secondary js-view-link <?= $p->is_published ? '' : 'd-none' ?>" title="Sitede gör"><?= icon('eye') ?></a>
                                    <?php if ($p->github_repo): ?>
                                        <form method="post" action="<?= e(admin_url('projeler/' . $p->id . '/senkronize')) ?>" class="js-sync" data-ajax data-ajax-success="none">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-outline-info" title="GitHub'dan güncelle"><?= icon('refresh') ?></button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= e(admin_url('projeler/' . $p->id . '/duzenle')) ?>" class="btn btn-outline-primary" title="Düzenle"><?= icon('edit') ?></a>
                                    <form method="post" action="<?= e(admin_url('projeler/' . $p->id . '/sil')) ?>" data-ajax data-ajax-success="remove" data-ajax-remove="tr">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-danger" title="Sil" data-confirm="&quot;<?= e($p->title) ?>&quot; silinsin mi? Bu işlem geri alınamaz."><?= icon('trash') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted">Henüz proje eklenmedi.</p>
                    <a href="<?= e(admin_url('github')) ?>" class="btn btn-info me-2"><?= icon('github', 16) ?> GitHub'dan İçe Aktar</a>
                    <a href="<?= e(admin_url('projeler/ekle')) ?>" class="btn btn-primary"><?= icon('plus', 16) ?> Elle Ekle</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
