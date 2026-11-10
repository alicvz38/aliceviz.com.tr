<?php
/**
 * Bakım sayfası
 * ---------------------------------------------------------------
 * Tasarım artık tema sistemine taşındı:
 *   themes/default/pages/maintenance.php       -> sayfa içeriği
 *   themes/default/layouts/blank.php            -> iskelet
 *   themes/default/assets/css/maintenance.css   -> stiller
 *   themes/default/assets/js/maintenance.js     -> animasyon ve kopyalama
 *
 * Site, panelden "Bakım Modu" açıldığında bu sayfayı otomatik gösterir.
 * Bu dosya doğrudan açıldığında da (bakimda.php) aynı sayfa görüntülenir.
 * Veritabanı henüz kurulmamış olsa bile çalışır.
 */

require __DIR__ . '/app/bootstrap.php';

(new App\Controllers\Site\PageController(new App\Core\Request()))->maintenance();
