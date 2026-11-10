<?php
/**
 * Ana sayfa
 * @var App\Core\View $this
 * @var App\Models\Project[] $featured
 * @var array $stats
 */
$this->layout('main');
?>
<section class="hero">
    <div class="hero-bg" aria-hidden="true"></div>
    <div class="container hero-inner">
        <span class="badge js-wave">Açık Kaynak Projelerim</span>
        <h1 class="hero-title">
            Merhaba, ben <span class="gradient-text"><?= e($site['title']) ?></span>
        </h1>
        <?php if ($site['job']): ?><p class="hero-job"><?= e($site['job']) ?></p><?php endif; ?>
        <p class="hero-lead"><?= e($site['description']) ?></p>

        <div class="hero-actions">
            <a href="<?= e(url('teklif-al')) ?>" class="btn btn-primary"><?= icon('send', 18) ?> Projeniz İçin Teklif Alın</a>
            <a href="<?= e(url('projeler')) ?>" class="btn btn-ghost"><?= icon('layers', 18) ?> Projeleri İncele</a>
        </div>

        <dl class="stats">
            <div><dt>Proje</dt><dd data-count="<?= (int) $stats['projects'] ?>"><?= (int) $stats['projects'] ?></dd></div>
            <div><dt>GitHub Yıldızı</dt><dd data-count="<?= (int) $stats['stars'] ?>"><?= (int) $stats['stars'] ?></dd></div>
            <div><dt>Programlama Dili</dt><dd data-count="<?= (int) $stats['languages'] ?>"><?= (int) $stats['languages'] ?></dd></div>
        </dl>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Hizmetler</span>
                <h2>Fikrinizi yazılıma dönüştürelim</h2>
            </div>
            <a href="<?= e(url('hizmetler')) ?>" class="link-arrow">Tüm hizmetler <?= icon('arrow-right', 16) ?></a>
        </div>
        <div class="service-grid">
            <?php foreach ($services as $key => $svc): ?>
                <?= $this->partial('service-card', ['key' => $key, 'svc' => $svc]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Portföy</span>
                <h2>Öne Çıkan Projeler</h2>
            </div>
            <a href="<?= e(url('projeler')) ?>" class="link-arrow">Tümünü gör <?= icon('arrow-right', 16) ?></a>
        </div>

        <?php if ($featured): ?>
            <div class="project-grid">
                <?php foreach ($featured as $project): ?>
                    <?= $this->partial('project-card', ['project' => $project]) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('folder', 40) ?>
                <p>Henüz yayınlanmış proje yok. Yakında burada olacak!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" id="hakkimda">
    <div class="container">
        <div class="about-card glass">
            <div class="about-text">
                <span class="eyebrow">Hakkımda</span>
                <h2>Kod yazmayı, üretmeyi ve paylaşmayı seviyorum.</h2>
                <p><?= nl2br(e(str_limit($site['about'], 420))) ?></p>
                <?php if ($topSkills): ?>
                    <div class="tag-list">
                        <?php foreach ($topSkills as $skill): ?><span class="tag"><?= e($skill->title) ?></span><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="<?= e(url('hakkimda')) ?>" class="link-arrow mt-1"><?= icon('file-text', 16) ?> Özgeçmişimi inceleyin <?= icon('arrow-right', 16) ?></a>
            </div>
            <div class="about-cta">
                <?php if ($site['avatar']): ?>
                    <img src="<?= e($site['avatar']) ?>" alt="<?= e($site['title']) ?>" class="about-avatar">
                <?php endif; ?>
                <a href="<?= e(url('iletisim')) ?>" class="contact-box">
                    <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <span>İletişime Geçin</span>
                </a>
                <?= $this->partial('social-links') ?>
            </div>
        </div>
    </div>
</section>
