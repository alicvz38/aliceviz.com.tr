<?php
/**
 * GitHub depoları: listele, seç, içe aktar
 * @var App\Core\View $this
 * @var string $username
 * @var array $repos
 * @var App\Models\Project[] $imported  [kullanici/depo (küçük harf) => Project]
 * @var string|null $error
 */
$this->layout('main');
$langColor = static fn (?string $lang) => (new App\Models\Project(['language' => $lang]))->languageColor();
?>
<?php $this->start('pageActions') ?>
<div class="d-flex gap-2">
    <?php if ($username): ?>
        <a href="<?= e(admin_url('github') . '?yenile=1') ?>" class="btn btn-outline-secondary btn-sm"><?= icon('refresh', 16) ?> Listeyi Yenile</a>
        <form method="post" action="<?= e(admin_url('github/senkronize')) ?>" class="m-0" data-ajax data-ajax-success="reload">
            <?= csrf_field() ?>
            <button class="btn btn-outline-info btn-sm"><?= icon('refresh', 16) ?> Eklenenleri Senkronize Et</button>
        </form>
    <?php endif; ?>
</div>
<?php $this->stop() ?>

<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="section-title mb-0">
                    <?= icon('github') ?>
                    <?php if ($username): ?>
                        <a href="https://github.com/<?= e($username) ?>" target="_blank" rel="noopener">github.com/<?= e($username) ?></a>
                        <span class="badge badge-light-primary"><?= count($repos) ?> depo</span>
                    <?php else: ?>
                        GitHub Depoları
                    <?php endif; ?>
                </div>
                <?php if ($repos): ?>
                    <input type="search" id="tableFilter" data-target="#reposTable" class="form-control form-control-sm" style="max-width: 260px" placeholder="Depo ara...">
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-light-warning mb-0">
                    <?= e($error) ?>
                    <?php if (!$username): ?> <a href="<?= e(admin_url('ayarlar/github')) ?>" class="alert-link">Ayarlara git &rarr;</a><?php endif; ?>
                </div>
            <?php elseif (!$repos): ?>
                <p class="text-muted mb-0">Bu kullanıcının herkese açık deposu bulunamadı.</p>
            <?php else: ?>
                <form method="post" action="<?= e(admin_url('github/ice-aktar')) ?>" data-ajax data-ajax-success="reload">
                    <?= csrf_field() ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="reposTable">
                            <thead>
                            <tr>
                                <th style="width:40px">
                                    <div class="form-check form-check-primary m-0"><input class="form-check-input" type="checkbox" id="selectAllRepos" title="Tümünü seç"></div>
                                </th>
                                <th>Depo</th>
                                <th>Dil</th>
                                <th class="text-center">⭐</th>
                                <th class="text-center">🍴</th>
                                <th>Son Güncelleme</th>
                                <th class="text-end">Durum</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($repos as $repo): ?>
                                <?php $existing = $imported[strtolower($repo['full_name'])] ?? null; ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-primary m-0">
                                            <input class="form-check-input repo-check" type="checkbox" name="repos[]" value="<?= e($repo['full_name']) ?>" <?= $existing ? 'disabled' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= e($repo['html_url']) ?>" target="_blank" rel="noopener" class="fw-bold"><?= e($repo['name']) ?></a>
                                        <?php if (!empty($repo['fork'])): ?><span class="badge badge-light-dark ms-1">fork</span><?php endif; ?>
                                        <?php if (!empty($repo['archived'])): ?><span class="badge badge-light-warning ms-1">arşiv</span><?php endif; ?>
                                        <div class="repo-desc"><?= e(str_limit($repo['description'] ?? '', 110)) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($repo['language'])): ?>
                                            <span class="lang-dot" style="background: <?= e($langColor($repo['language'])) ?>"></span><?= e($repo['language']) ?>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= (int) $repo['stargazers_count'] ?></td>
                                    <td class="text-center"><?= (int) $repo['forks_count'] ?></td>
                                    <td class="text-muted"><?= e(time_ago(isset($repo['pushed_at']) ? date('Y-m-d H:i:s', strtotime($repo['pushed_at'])) : null)) ?></td>
                                    <td class="text-end">
                                        <?php if ($existing): ?>
                                            <a href="<?= e(admin_url('projeler/' . $existing->id . '/duzenle')) ?>" class="badge badge-light-success">Eklendi · Düzenle</a>
                                        <?php else: ?>
                                            <span class="badge badge-light-dark">Eklenmedi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                        <div class="switch form-switch-custom switch-inline form-switch-success">
                            <input class="switch-input" type="checkbox" role="switch" id="publish" name="publish" value="1" checked>
                            <label class="switch-label" for="publish">Eklenen projeleri hemen yayınla</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= icon('download', 16) ?> Seçilenleri İçe Aktar</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
