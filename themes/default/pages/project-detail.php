<?php
/**
 * Proje detayı
 * @var App\Core\View $this
 * @var App\Models\Project $project
 * @var string|null $readme  GitHub'ın işlediği README HTML'i
 * @var App\Models\Project[] $related
 */
$this->layout('main');
?>
<?php $this->start('styles') ?>
<link rel="stylesheet" href="<?= $this->asset('css/readme.css') ?>">
<?php $this->stop() ?>

<section class="page-header project-header">
    <div class="container">
        <a href="<?= e(url('projeler')) ?>" class="link-back"><?= icon('arrow-left', 16) ?> Tüm projeler</a>

        <h1><?= e($project->title) ?></h1>
        <?php if ($project->summary): ?><p class="lead"><?= e($project->summary) ?></p><?php endif; ?>

        <div class="project-facts">
            <?php if ($project->kindLabel()): ?>
                <a href="<?= e(url('projeler') . '?tur=' . App\Models\Project::KINDS[$project->kind][2]) ?>" class="chip chip-kind chip-kind-<?= e($project->kind) ?> chip-static"><?= icon($project->kindIcon(), 14) ?> <?= e($project->kindLabel()) ?></a>
            <?php endif; ?>
            <?php if ($project->language): ?>
                <span class="lang"><i style="background: <?= e($project->languageColor()) ?>"></i><?= e($project->language) ?></span>
            <?php endif; ?>
            <span><?= icon('star', 16) ?> <?= (int) $project->stars ?> yıldız</span>
            <span><?= icon('git-branch', 16) ?> <?= (int) $project->forks ?> fork</span>
            <?php if ($project->pushed_at): ?>
                <span><?= icon('clock', 16) ?> Son güncelleme: <?= e(time_ago($project->pushed_at)) ?></span>
            <?php endif; ?>
        </div>

        <div class="hero-actions">
            <?php if (!empty($shots)): ?>
                <a href="<?= e(url('projeler/' . $project->slug . '/tanitim')) ?>" class="btn btn-primary"><?= icon('image', 18) ?> Tanıtımı İncele</a>
            <?php endif; ?>
            <?php if ($project->github_url): ?>
                <a href="<?= e($project->github_url) ?>" class="btn <?= !empty($shots) ? 'btn-ghost' : 'btn-primary' ?>" target="_blank" rel="noopener"><?= icon('github', 18) ?> GitHub'da İncele</a>
            <?php endif; ?>
            <?php if ($project->demo_url): ?>
                <a href="<?= e($project->demo_url) ?>" class="btn btn-ghost" target="_blank" rel="noopener"><?= icon('external-link', 18) ?> Canlı Demo</a>
            <?php endif; ?>
        </div>

        <?php if ($project->tagList()): ?>
            <div class="tag-list">
                <?php foreach ($project->tagList() as $tag): ?>
                    <a class="tag" href="<?= e(url('projeler') . '?' . http_build_query(['q' => $tag])) ?>">#<?= e($tag) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <?php if (!empty($shots)): ?>
            <a href="<?= e(url('projeler/' . $project->slug . '/tanitim')) ?>" class="shot-strip glass" aria-label="Tanıtımı incele">
                <span class="shot-strip-images">
                    <?php foreach (array_slice($shots, 0, 4) as $s): ?>
                        <img src="<?= e($s->imageUrl()) ?>" alt="<?= e($s->title) ?>" loading="lazy">
                    <?php endforeach; ?>
                </span>
                <span class="shot-strip-text"><strong><?= count($shots) ?> ekran görüntüsüyle tanıtım</strong><small>Arayüzü ve özellikleri adım adım inceleyin <?= icon('arrow-right', 14) ?></small></span>
            </a>
        <?php endif; ?>
        <?php if ($project->imageUrl()): ?>
            <figure class="project-image glass"><img src="<?= e($project->imageUrl()) ?>" alt="<?= e($project->title) ?>"></figure>
        <?php endif; ?>

        <?php if ($project->content): ?>
            <div class="glass content-block">
                <h2>Proje Hakkında</h2>
                <div class="prose"><?= nl2br(e($project->content)) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($readme): ?>
            <div class="glass content-block">
                <h2 class="readme-title"><?= icon('github', 20) ?> README.md</h2>
                <div class="markdown-body"><?= $readme /* GitHub tarafından temizlenmiş HTML */ ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$project->content && !$readme): ?>
            <div class="empty-state"><p>Bu proje için ayrıntılı açıklama henüz eklenmedi.</p></div>
        <?php endif; ?>
    </div>
</section>

<?php if ($related): ?>
<section class="section pt-0">
    <div class="container">
        <div class="section-head"><h2>Benzer Projeler</h2></div>
        <div class="project-grid">
            <?php foreach ($related as $item): ?>
                <?= $this->partial('project-card', ['project' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
