<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

/** Giriş yapmış kullanıcıyı giriş sayfasından panele yönlendirir */
class GuestMiddleware implements Middleware
{
    public function handle(Request $request): void
    {
        if (Auth::check()) {
            redirect(admin_url());
        }
    }
}
