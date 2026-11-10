<?php
/**
 * Boş iskelet (navbar/footer yok). Bakım sayfası gibi tek ekranlık sayfalar için.
 * @var App\Core\View $this
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?= $this->partial('head', ['pageTitle' => $pageTitle ?? null, 'metaDescription' => $metaDescription ?? null]) ?>
    <?= $this->section('styles') ?>
</head>
<body class="<?= e($this->section('bodyClass')) ?>">
    <?= $this->section('content') ?>
    <?= $this->section('scripts') ?>
</body>
</html>
