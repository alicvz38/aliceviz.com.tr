<?php
/**
 * Panel ana iskeleti (Equation vertical-dark-menu)
 *   head + loader + navbar + sidebar + [breadcrumb + uyarılar + içerik] + footer + scripts
 * @var App\Core\View $this
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?= $this->partial('head', ['pageTitle' => $pageTitle ?? null]) ?>
    <?= $this->section('styles') ?>
</head>
<body class="dark">

    <?= $this->partial('loader') ?>
    <?= $this->partial('navbar') ?>

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <?= $this->partial('sidebar') ?>

        <!--  BEGIN CONTENT AREA  -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">
                <div class="middle-content container-fluid p-0">

                    <?= $this->partial('breadcrumb', ['breadcrumbs' => $breadcrumbs ?? []]) ?>

                    <div class="row layout-top-spacing">
                        <div class="col-12">
                            <?= $this->partial('alerts') ?>
                        </div>
                    </div>

                    <?= $this->section('content') ?>

                </div>
            </div>

            <?= $this->partial('footer') ?>
        </div>
        <!--  END CONTENT AREA  -->
    </div>
    <!-- END MAIN CONTAINER -->

    <?= $this->partial('scripts') ?>
    <?= $this->section('scripts') ?>
</body>
</html>
