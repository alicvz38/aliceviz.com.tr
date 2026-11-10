<?php /** @var App\Core\View $this */ ?>
<script src="<?= e(shared_asset('js/ajax.js')) ?>"></script>
<script src="<?= e(shared_asset('js/mask.js')) ?>"></script>
<script src="<?= $this->asset('js/app.js') ?>"></script>
<?php if (App\Services\LiveChat::enabled()): ?>
<script src="<?= $this->asset('js/chat.js') ?>"></script>
<?php endif; ?>
