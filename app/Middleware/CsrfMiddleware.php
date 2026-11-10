<?php

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

/**
 * POST isteklerinde CSRF belirtecini doğrular.
 * Belirteç form alanından (_token) veya AJAX başlığından (X-CSRF-TOKEN) okunur.
 */
class CsrfMiddleware implements Middleware
{
    public function handle(Request $request): void
    {
        if (!$request->isPost()) {
            return;
        }
        $token = $request->post('_token') ?? $request->header('X-CSRF-TOKEN');
        if (Csrf::verify(is_string($token) ? $token : null)) {
            return;
        }

        $message = 'Oturum süresi doldu veya geçersiz istek. Lütfen sayfayı yenileyip tekrar deneyin.';
        // Not: 419 bazı Apache kurulumlarında 500'e dönüşür; standart 403 kullanılır
        if (Request::isAjax()) {
            Response::error($message, [], 403);
        }
        http_response_code(403);
        exit($message);
    }
}
