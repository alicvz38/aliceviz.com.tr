<?php

namespace App\Middleware;

use App\Core\Request;

interface Middleware
{
    /** İsteği durdurmak için yönlendirme yapabilir veya çıkış yapabilir */
    public function handle(Request $request): void;
}
