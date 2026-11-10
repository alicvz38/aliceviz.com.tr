<?php

namespace App\Controllers\Site;

class PageController extends SiteController
{
    /** Bakım sayfası (bakimda.php tasarımı) */
    public function maintenance(): void
    {
        header('Retry-After: 3600');
        $this->render('maintenance', [
            'pageTitle' => 'Sitemiz Bakımdadır',
            'message'   => setting('maintenance_message', 'Sitemiz şu anda bakımda olup en kısa zamanda hizmete açılacaktır.'),
        ], 503);
    }

    public function notFound(): void
    {
        // Bakım modunda ziyaretçiye 404 yerine bakım sayfası gösterilir
        if (\App\Models\Setting::isMaintenance() && !\App\Core\Auth::check() && !str_starts_with($this->request->path(), (string) \App\Core\Config::get('admin.prefix'))) {
            $this->maintenance();
            return;
        }
        $this->render('404', ['pageTitle' => 'Sayfa Bulunamadı'], 404);
    }
}
