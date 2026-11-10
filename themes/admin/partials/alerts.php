<?php
/** Oturumdaki tek seferlik mesajları Equation uyarı kutusu olarak gösterir */
$icons = ['success' => 'check', 'danger' => 'alert-triangle', 'warning' => 'alert-triangle', 'info' => 'code'];
foreach (App\Core\Session::pullFlash() as $flash): ?>
    <div class="alert alert-icon-left alert-light-<?= e($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"><?= icon('x') ?></button>
        <?= icon($icons[$flash['type']] ?? 'code') ?>
        <?= e($flash['message']) ?>
    </div>
<?php endforeach; ?>
