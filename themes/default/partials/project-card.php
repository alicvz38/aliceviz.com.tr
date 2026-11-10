<?php
/**
 * Proje kartı parçası
 * @var App\Core\View $this
 * @var App\Models\Project $project
 */
$tags = array_slice($project->tagList(), 0, 3);
?>
<article class="project-card">
    <a href="<?= e($project->url()) ?>" class="project-cover" style="--lang: <?= e($project->languageColor()) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($project->imageUrl()): ?>
            <img src="<?= e($project->imageUrl()) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="cover-placeholder"><?= icon('code', 34) ?></span>
        <?php endif; ?>
        <?php if ($project->is_featured): ?><span class="chip chip-featured"><?= icon('star', 12) ?> Öne çıkan</span><?php endif; ?>
        <?php if ($project->kindLabel()): ?><span class="chip chip-kind chip-kind-<?= e($project->kind) ?>"><?= icon($project->kindIcon(), 12) ?> <?= e($project->kindLabel()) ?></span><?php endif; ?>
    </a>

    <div class="project-body">
        <h3 class="project-title"><a href="<?= e($project->url()) ?>"><?= e($project->title) ?></a></h3>
        <p class="project-summary"><?= e(str_limit($project->summary, 130) ?: 'Bu proje için henüz açıklama eklenmedi.') ?></p>

        <?php if ($tags): ?>
            <div class="tag-list">
                <?php foreach ($tags as $tag): ?><span class="tag">#<?= e($tag) ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="project-meta">
        <?php if ($project->language): ?>
            <span class="lang"><i style="background: <?= e($project->languageColor()) ?>"></i><?= e($project->language) ?></span>
        <?php endif; ?>
        <span title="Yıldız"><?= icon('star', 14) ?> <?= (int) $project->stars ?></span>
        <span title="Fork"><?= icon('git-branch', 14) ?> <?= (int) $project->forks ?></span>
        <span class="meta-links">
            <?php if ($project->github_url): ?>
                <a href="<?= e($project->github_url) ?>" target="_blank" rel="noopener" aria-label="GitHub'da aç"><?= icon('github', 16) ?></a>
            <?php endif; ?>
            <?php if ($project->demo_url): ?>
                <a href="<?= e($project->demo_url) ?>" target="_blank" rel="noopener" aria-label="Canlı demo"><?= icon('external-link', 16) ?></a>
            <?php endif; ?>
        </span>
    </footer>
</article>
