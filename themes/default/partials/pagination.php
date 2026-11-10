<?php
/**
 * @var int $page
 * @var int $pages
 * @var array $query  Korunacak diğer parametreler
 */
if ($pages <= 1) return;
$link = static fn (int $p) => url('projeler') . '?' . http_build_query(array_filter($query + ['sayfa' => $p > 1 ? $p : null]));
?>
<nav class="pagination" aria-label="Sayfalama">
    <?php if ($page > 1): ?>
        <a href="<?= e($link($page - 1)) ?>" aria-label="Önceki"><?= icon('arrow-left', 16) ?></a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= e($link($i)) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
        <a href="<?= e($link($page + 1)) ?>" aria-label="Sonraki"><?= icon('arrow-right', 16) ?></a>
    <?php endif; ?>
</nav>
