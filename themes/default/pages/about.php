<?php
/**
 * Hakkımda — özgeçmiş (CV) sayfası. Yazdırılabilir / PDF olarak kaydedilebilir.
 * @var App\Core\View $this
 * @var array<string, App\Models\ResumeItem[]> $items
 * @var array<string, App\Models\ResumeItem[]> $skillGroups
 * @var array $stats
 * @var App\Models\Project[] $recent
 */
$this->layout('main');
$initials = implode('', array_map(static fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice(preg_split('/\s+/u', trim((string) $site['title'])) ?: [], 0, 2)));
?>
<section class="section cv">
    <div class="container">

        <!-- Üst kart -->
        <header class="cv-header glass">
            <div class="cv-avatar">
                <?php if ($site['avatar']): ?>
                    <img src="<?= e($site['avatar']) ?>" alt="<?= e($site['title']) ?>">
                <?php else: ?>
                    <span><?= e($initials ?: 'A') ?></span>
                <?php endif; ?>
            </div>
            <div class="cv-identity">
                <?php if ($site['available']): ?>
                    <span class="cv-available"><i></i> Yeni projelere açığım</span>
                <?php endif; ?>
                <h1><?= e($site['title']) ?></h1>
                <?php if ($site['job']): ?><p class="cv-job"><?= e($site['job']) ?></p><?php endif; ?>
                <ul class="cv-contact">
                    <?php if ($site['location']): ?><li><?= icon('map-pin', 15) ?> <?= e($site['location']) ?></li><?php endif; ?>
                    <?php if ($site['email']): ?><li><?= icon('mail', 15) ?> <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></li><?php endif; ?>
                    <?php if ($site['phone']): ?><li><?= icon('phone', 15) ?> <a href="<?= e(phone_href($site['phone'])) ?>"><?= e($site['phone']) ?></a></li><?php endif; ?>
                    <?php if ($site['domain']): ?><li><?= icon('globe', 15) ?> <?= e($site['domain']) ?></li><?php endif; ?>
                    <?php if ($site['social']['github']): ?><li><?= icon('github', 15) ?> <a href="<?= e($site['social']['github']) ?>" target="_blank" rel="noopener"><?= e(str_replace('https://', '', $site['social']['github'])) ?></a></li><?php endif; ?>
                </ul>
            </div>
            <div class="cv-actions no-print">
                <a href="<?= e(url('iletisim')) ?>" class="btn btn-primary btn-sm"><?= icon('mail', 16) ?> İletişime Geç</a>
                <button type="button" class="btn btn-ghost btn-sm js-print"><?= icon('printer', 16) ?> Yazdır / PDF</button>
            </div>
        </header>

        <!-- Rakamlar -->
        <dl class="cv-stats">
            <?php if ($stats['years']): ?><div class="glass"><dd><?= (int) $stats['years'] ?>+</dd><dt>Yıl deneyim</dt></div><?php endif; ?>
            <div class="glass"><dd><?= (int) $stats['projects'] ?></dd><dt>Açık kaynak proje</dt></div>
            <div class="glass"><dd><?= count($items['skill']) ?></dd><dt>Teknoloji</dt></div>
            <div class="glass"><dd><?= (int) $stats['stars'] ?></dd><dt>GitHub yıldızı</dt></div>
        </dl>

        <div class="cv-grid">
            <!-- Sol sütun -->
            <div class="cv-main">
                <?php if ($site['about']): ?>
                    <section class="cv-block glass">
                        <h2><?= icon('user', 20) ?> Profil</h2>
                        <p class="cv-summary"><?= nl2br(e($site['about'])) ?></p>
                    </section>
                <?php endif; ?>

                <?php foreach (['experience' => ['İş Deneyimi', 'briefcase'], 'education' => ['Eğitim', 'book-open']] as $type => [$label, $ico]): ?>
                    <?php if ($items[$type]): ?>
                        <section class="cv-block glass">
                            <h2><?= icon($ico, 20) ?> <?= e($label) ?></h2>
                            <ol class="timeline">
                                <?php foreach ($items[$type] as $item): ?>
                                    <li class="timeline-item <?= $item->is_current ? 'current' : '' ?>">
                                        <div class="timeline-head">
                                            <h3><?= e($item->title) ?></h3>
                                            <span class="timeline-date"><?= e($item->period()) ?></span>
                                        </div>
                                        <div class="timeline-org">
                                            <?= e($item->organization) ?>
                                            <?php if ($item->location): ?><span>· <?= e($item->location) ?></span><?php endif; ?>
                                            <?php if ($type === 'experience' && $item->duration()): ?><span class="timeline-duration"><?= e($item->duration()) ?></span><?php endif; ?>
                                        </div>
                                        <?php if ($item->description): ?><p><?= nl2br(e($item->description)) ?></p><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </section>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($recent): ?>
                    <section class="cv-block glass cv-projects-block">
                        <h2><?= icon('layers', 20) ?> Projelerim</h2>
                        <ul class="cv-projects">
                            <?php foreach ($recent as $p): ?>
                                <li>
                                    <div class="cv-project-head">
                                        <a href="<?= e($p->url()) ?>"><?= e($p->title) ?></a>
                                        <?php if ($p->kind): ?><span class="cv-project-kind"><?= icon($p->kindIcon(), 12) ?> <?= e($p->kindLabel()) ?></span><?php endif; ?>
                                        <?php if ($p->language): ?><span class="lang"><i style="background: <?= e($p->languageColor()) ?>"></i><?= e($p->language) ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($p->summary): ?><p><?= e(str_limit($p->summary, 170)) ?></p><?php endif; ?>
                                    <?php if ($p->github_url || $p->demo_url): ?>
                                        <div class="cv-project-links">
                                            <?php if ($p->github_url): ?><a href="<?= e($p->github_url) ?>" target="_blank" rel="noopener"><?= icon('github', 12) ?> <?= e(preg_replace('#^https?://(www\.)?#', '', rtrim($p->github_url, '/'))) ?></a><?php endif; ?>
                                            <?php if ($p->demo_url): ?><a href="<?= e($p->demo_url) ?>" target="_blank" rel="noopener"><?= icon('external-link', 12) ?> <?= e(preg_replace('#^https?://(www\.)?#', '', rtrim($p->demo_url, '/'))) ?></a><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= e(url('projeler')) ?>" class="link-arrow no-print">Tüm projeler <?= icon('arrow-right', 16) ?></a>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Sağ sütun -->
            <aside class="cv-side">
                <?php if ($skillGroups): ?>
                    <section class="cv-block glass">
                        <h2><?= icon('cpu', 20) ?> Yetenekler</h2>
                        <?php foreach ($skillGroups as $group => $skills): ?>
                            <h4 class="skill-group"><?= e($group) ?></h4>
                            <ul class="skill-list">
                                <?php foreach ($skills as $skill): ?>
                                    <li>
                                        <div class="skill-head"><span><?= e($skill->title) ?></span><small>%<?= (int) $skill->level ?></small></div>
                                        <div class="skill-bar"><span style="--w: <?= (int) $skill->level ?>%"></span></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if ($items['language']): ?>
                    <section class="cv-block glass">
                        <h2><?= icon('globe', 20) ?> Yabancı Diller</h2>
                        <ul class="lang-list">
                            <?php foreach ($items['language'] as $l): ?>
                                <li>
                                    <span><?= e($l->title) ?></span>
                                    <span class="lang-dots" title="%<?= (int) $l->level ?>">
                                        <?php for ($i = 1; $i <= 5; $i++): ?><i class="<?= $i * 20 <= (int) $l->level + 10 ? 'on' : '' ?>"></i><?php endfor; ?>
                                    </span>
                                    <small><?= e($l->description ?: $l->levelLabel()) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if ($items['certificate']): ?>
                    <section class="cv-block glass">
                        <h2><?= icon('award', 20) ?> Sertifikalar</h2>
                        <ul class="cert-list">
                            <?php foreach ($items['certificate'] as $c): ?>
                                <li>
                                    <strong><?php if ($c->url): ?><a href="<?= e($c->url) ?>" target="_blank" rel="noopener"><?= e($c->title) ?></a><?php else: ?><?= e($c->title) ?><?php endif; ?></strong>
                                    <small><?= e(implode(' · ', array_filter([$c->organization, month_label($c->start_date)]))) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <section class="cv-block glass no-print cv-hire">
                    <h2><?= icon('briefcase', 20) ?> Birlikte çalışalım</h2>
                    <p>Web sitesi veya masaüstü uygulama ihtiyacınız mı var?</p>
                    <a href="<?= e(url('hizmetler')) ?>" class="btn btn-ghost btn-sm">Hizmetler</a>
                    <a href="<?= e(url('teklif-al')) ?>" class="btn btn-primary btn-sm"><?= icon('send', 14) ?> Teklif Al</a>
                </section>
            </aside>
        </div>

        <!-- Teşekkür -->
        <aside class="thanks-note glass no-print">
            <span class="thanks-icon" aria-hidden="true">✨</span>
            <div>
                <h3>Teşekkür</h3>
                <p>
                    Bu sitenin geliştirilmesinde bana yardımcı olan, Anthropic'in yapay zekâ asistanı
                    <strong>Claude</strong>'a teşekkür ederim. AJAX altyapısından özgeçmiş sayfasına,
                    hizmetler bölümünden mesaj sistemine kadar birçok parçayı birlikte kodladık.
                    Sabrı ve katkıları için teşekkürler, Claude!
                </p>
            </div>
        </aside>
    </div>
</section>
