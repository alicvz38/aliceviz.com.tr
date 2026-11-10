<?php
/** @var App\Core\View $this */
$layout = $this->info('layout', 'vertical-dark-menu');
?>
<!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
<script src="<?= $this->vendor('src/plugins/src/global/vendors.min.js') ?>"></script>
<script src="<?= $this->vendor('src/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= $this->vendor('src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js') ?>"></script>
<script src="<?= $this->vendor('src/plugins/src/mousetrap/mousetrap.min.js') ?>"></script>
<script src="<?= $this->vendor('src/plugins/src/waves/waves.min.js') ?>"></script>
<script src="<?= $this->vendor("layouts/$layout/app.js") ?>"></script>
<!-- END GLOBAL MANDATORY SCRIPTS -->
<script src="<?= $this->vendor('src/plugins/src/table/datatable/datatables.js') ?>"></script>
<script src="<?= e(shared_asset('js/ajax.js')) ?>"></script>
<script src="<?= e(shared_asset('js/mask.js')) ?>"></script>
<script src="<?= $this->asset('js/admin.js') ?>"></script>
