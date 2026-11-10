<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;

/** Panel hata sayfaları (Equation tasarımına uygun) */
class ErrorController extends AdminController
{
    public function notFound(): void
    {
        if (Request::isAjax()) {
            Response::error('İstenen panel sayfası bulunamadı.', [], 404);
        }
        $this->render('errors/404', [
            'pageTitle'   => 'Sayfa Bulunamadı',
            'breadcrumbs' => ['404' => null],
            'path'        => '/' . $this->request->path(),
        ], 404);
    }
}
