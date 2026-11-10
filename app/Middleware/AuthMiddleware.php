<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Panel sayfalarını yalnızca giriş yapmış kullanıcılara açar */
class AuthMiddleware implements Middleware
{
    public function handle(Request $request): void
    {
        if (Auth::check()) {
            return;
        }
        if (Request::isAjax()) {
            Response::error('Oturumunuz sona erdi. Lütfen tekrar giriş yapın.', [], 401, ['redirect' => admin_url('giris')]);
        }
        Session::flash('warning', 'Devam etmek için giriş yapmalısınız.');
        redirect(admin_url('giris'));
    }
}
