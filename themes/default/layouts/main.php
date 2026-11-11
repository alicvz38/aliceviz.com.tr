<?php
/**
 * Ana iskelet: head + navbar + içerik + footer + scripts
 * @var App\Core\View $this
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?= $this->partial('head', ['pageTitle' => $pageTitle ?? null, 'metaDescription' => $metaDescription ?? null, 'ogImage' => $ogImage ?? null, 'ogType' => $ogType ?? null, 'schema' => $schema ?? null, 'noindex' => $noindex ?? false]) ?>
    <link rel="stylesheet" href="<?= $this->asset('css/style.css') ?>">
    <?= $this->section('styles') ?>
</head>
<body class="<?= setting('colorful_icons', '0') === '1' ? 'icons-colorful' : '' ?>">
    <div class="page-bg" aria-hidden="true"></div>

    <?= $this->partial('admin-bar') ?>

    <?= $this->partial('navbar') ?>

    <main id="main">
        <?= $this->partial('flash') ?>
        <?= $this->section('content') ?>
    </main>

    <?= $this->partial('footer') ?>
    <?= $this->partial('toast') ?>
    <?= $this->partial('chat-widget') ?>
    <?= $this->partial('cookie-consent') ?>
    <?= $this->partial('scripts') ?>
    <?= $this->section('scripts') ?>
</body>
</html>
