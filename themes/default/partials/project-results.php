<?php
/**
 * Proje sonuçları (ilk yüklemede ve AJAX ile yeniden çizilir)
 * @var App\Core\View $this
 * @var App\Models\Project[] $projects
 */
$query = array_filter(['tur' => $kindSlug ?? '', 'dil' => $language, 'q' => $search]);
?>
<?php if ($projects): ?>
    <div class="project-grid">
        <?php foreach ($projects as $project): ?>
            <?= $this->partial('project-card', ['project' => $project]) ?>
        <?php endforeach; ?>
    </div>
    <?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'query' => $query]) ?>
<?php else: ?>
    <div class="empty-state">
        <?= icon('search', 40) ?>
        <p>Aramanıza uygun proje bulunamadı.</p>
        <a href="<?= e(url('projeler')) ?>" class="btn btn-ghost btn-sm" data-ajax-link>Filtreleri temizle</a>
    </div>
<?php endif; ?>
