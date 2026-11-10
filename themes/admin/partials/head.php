<?php
/**
 * Panel <head> parçası — Equation global stilleri
 * @var App\Core\View $this
 */
$layout = $this->info('layout', 'vertical-dark-menu');
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="admin-url" content="<?= e(admin_url()) ?>">
<?php if (App\Core\Auth::id() && App\Support\Realtime::isRunning()): ?>
<!-- Canlı bildirim (WebSocket) sunucusu çalışıyor: realtime/server.php -->
<meta name="realtime-port" content="<?= (int) App\Support\Realtime::port() ?>">
<meta name="realtime-token" content="<?= e(App\Support\Realtime::token((int) App\Core\Auth::id())) ?>">
<?php endif; ?>
<title><?= e(($pageTitle ?? 'Panel') . ' | ' . $siteTitle . ' Yönetim') ?></title>
<link rel="icon" href="https://img.icons8.com/fluency/48/code--v1.png" type="image/png">

<link href="<?= $this->vendor("layouts/$layout/css/light/loader.css") ?>" rel="stylesheet">
<link href="<?= $this->vendor("layouts/$layout/css/dark/loader.css") ?>" rel="stylesheet">
<script src="<?= $this->asset('js/loader.js') ?>"></script>

<!-- BEGIN GLOBAL MANDATORY STYLES -->
<link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
<link href="<?= $this->vendor('src/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor("layouts/$layout/css/light/plugins.css") ?>" rel="stylesheet">
<link href="<?= $this->vendor("layouts/$layout/css/dark/plugins.css") ?>" rel="stylesheet">
<!-- END GLOBAL MANDATORY STYLES -->

<!-- BEGIN PAGE LEVEL STYLES -->
<link href="<?= $this->vendor('src/assets/css/light/elements/alert.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor('src/assets/css/dark/elements/alert.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor('src/assets/css/light/forms/switches.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor('src/assets/css/dark/forms/switches.css') ?>" rel="stylesheet">
<!-- DataTables (Equation) -->
<link href="<?= $this->vendor('src/plugins/src/table/datatable/datatables.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor('src/plugins/css/light/table/datatable/dt-global_style.css') ?>" rel="stylesheet">
<link href="<?= $this->vendor('src/plugins/css/dark/table/datatable/dt-global_style.css') ?>" rel="stylesheet">
<link href="<?= e(shared_asset('css/ajax.css')) ?>" rel="stylesheet">
<link href="<?= $this->asset('css/admin.css') ?>" rel="stylesheet">
<!-- END PAGE LEVEL STYLES -->
