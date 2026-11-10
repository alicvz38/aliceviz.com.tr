<?php
/**
 * Giriş sayfası iskeleti (Equation auth-boxed)
 * @var App\Core\View $this
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?= $this->partial('head', ['pageTitle' => $pageTitle ?? null]) ?>
    <link href="<?= $this->vendor('src/assets/css/light/authentication/auth-boxed.css') ?>" rel="stylesheet">
    <link href="<?= $this->vendor('src/assets/css/dark/authentication/auth-boxed.css') ?>" rel="stylesheet">
</head>
<body class="form dark">
    <?= $this->partial('loader') ?>

    <div class="auth-container d-flex">
        <div class="container mx-auto align-self-center">
            <div class="row">
                <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-8 col-12 d-flex flex-column align-self-center mx-auto">
                    <?= $this->partial('alerts') ?>
                    <?= $this->section('content') ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= $this->vendor('src/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
