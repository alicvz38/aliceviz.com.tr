<?php

namespace App\Middleware;

use App\Controllers\Site\PageController;
use App\Core\Auth;
use App\Core\Request;
use App\Models\Setting;

/**
 * Bakım modu açıksa ziyaretçilere bakım sayfasını (bakimda) gösterir.
 * Panele giriş yapmış yönetici siteyi normal şekilde görebilir.
 */
class MaintenanceMiddleware implements Middleware
{
    public function handle(Request $request): void
    {
        if (Setting::isMaintenance() && !Auth::check()) {
            (new PageController($request))->maintenance();
            exit;
        }
    }
}
