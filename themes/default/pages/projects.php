<?php
/**
 * Proje listesi — arama, tür (Web / Uygulama), dil filtresi ve sayfalama AJAX ile çalışır (JS kapalıysa normal bağlantılar)
 * @var App\Core\View $this
 * @var App\Models\Project[] $projects
 * @var array $languages   [dil => adet]
 * @var array $kindCounts  ['web' => n, 'app' => n]
 * @var string|null $kind  seçili tür (web | app)
 */
$this->layout('main');
$kinds = App\Models\Project::KINDS;
$kindSlug = $kind ? $kinds[$kind][2] : '';
// Bağlantılar diğer filtreleri korur (JS kapalıyken de doğru çalışsın)
$link = static fn (array $p) => url('projeler') . (($q = http_build_query(array_filter($p))) ? '?' . $q : '');
?>
<section class="page-header">
    <div class="container">
        <span class="eyebrow">Portföy</span>
        <h1>Projeler</h1>
        <p>GitHub üzerinde geliştirdiğim projelerin tamamı. Toplam <strong id="projectTotal"><?= (int) $total ?></strong> proje.</p>

        <form class="search-form" method="get" action="<?= e(url('projeler')) ?>" role="search" id="projectSearch">
            <?= icon('search', 18) ?>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Proje, etiket veya açıklama ara..." aria-label="Proje ara" autocomplete="off">
            <input type="hidden" name="dil" value="<?= e($language) ?>">
            <input type="hidden" name="tur" value="<?= e($kindSlug) ?>">
            <button type="submit" class="btn btn-sm btn-primary">Ara</button>
        </form>

        <?php if (array_sum($kindCounts) > 0): ?>
            <!-- Tür: Web / Uygulama -->
            <div class="kind-tabs" id="kindTabs" role="tablist" aria-label="Proje türü">
                <a href="<?= e($link(['dil' => $language, 'q' => $search])) ?>" data-kind="" class="kind-tab <?= !$kind ? 'active' : '' ?>">Tümü</a>
                <?php foreach ($kinds as $key => [$label, $ico, $slug]): ?>
                    <a href="<?= e($link(['tur' => $slug, 'dil' => $language, 'q' => $search])) ?>" data-kind="<?= e($slug) ?>" class="kind-tab <?= $kind === $key ? 'active' : '' ?>">
                        <?= icon($ico, 16) ?> <?= e($label) ?> <small><?= (int) $kindCounts[$key] ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($languages): ?>
            <div class="filter-chips" id="languageChips">
                <a href="<?= e($link(['tur' => $kindSlug, 'q' => $search])) ?>" data-lang="" class="chip <?= !$language ? 'active' : '' ?>">Tüm diller</a>
                <?php foreach ($languages as $lang => $count): ?>
                    <a href="<?= e($link(['tur' => $kindSlug, 'dil' => $lang, 'q' => $search])) ?>" data-lang="<?= e($lang) ?>" class="chip <?= $language === $lang ? 'active' : '' ?>">
                        <?= e($lang) ?> <small><?= (int) $count ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section pt-0">
    <div class="container" id="projectResults" data-url="<?= e(url('projeler')) ?>" aria-live="polite">
        <?= $this->partial('project-results', compact('projects', 'language', 'search', 'page', 'pages') + ['kindSlug' => $kindSlug]) ?>
    </div>
</section>
