<?php /** @var App\Core\View $this */ ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ? $pageTitle . ' | ' . $site['title'] : $site['title'] . ' | ' . ($site['domain'] ?: 'Projeler')) ?></title>
<meta name="description" content="<?= e($metaDescription ?: $site['description']) ?>">
<meta name="theme-color" content="#0b0f19">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="stylesheet" href="<?= e(shared_asset('css/ajax.css')) ?>">
<meta property="og:title" content="<?= e($pageTitle ?: $site['title']) ?>">
<meta property="og:description" content="<?= e($metaDescription ?: $site['description']) ?>">
<?= favicon_tags() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
