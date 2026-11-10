<?php
/**
 * JavaScript kapalıyken (klasik form gönderiminde) oluşan flash mesajları.
 * AJAX'ta mesajlar toast olarak gösterilir.
 */
$flashes = App\Core\Session::pullFlash();
if (!$flashes) return;
?>
<div class="container flash-stack">
    <?php foreach ($flashes as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
</div>
