<?php

namespace App\Core;

use App\Middleware\Middleware;

/**
 * Basit yönlendirici.
 *   $router->get('projeler/{slug}', [ProjectController::class, 'show']);
 *   $router->group('admin', ['auth'], function (Router $r) { ... });
 */
final class Router
{
    private array $routes = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    /** Kısa ad => sınıf eşlemesi */
    private array $middlewareMap = [
        'auth'        => \App\Middleware\AuthMiddleware::class,
        'guest'       => \App\Middleware\GuestMiddleware::class,
        'csrf'        => \App\Middleware\CsrfMiddleware::class,
        'maintenance' => \App\Middleware\MaintenanceMiddleware::class,
    ];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, array_merge(['csrf'], $middleware));
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        [$oldPrefix, $oldMw] = [$this->prefix, $this->groupMiddleware];
        $this->prefix = trim($oldPrefix . '/' . trim($prefix, '/'), '/');
        $this->groupMiddleware = array_merge($oldMw, $middleware);
        $callback($this);
        [$this->prefix, $this->groupMiddleware] = [$oldPrefix, $oldMw];
    }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $path = trim($this->prefix . '/' . trim($pattern, '/'), '/');
        $regex = preg_replace(
            ['#\{id\}#', '#\{([a-z_]+)\}#'],
            ['(?P<id>\d+)', '(?P<$1>[A-Za-z0-9\-_.]+)'],
            $path
        );
        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => array_unique(array_merge($this->groupMiddleware, $middleware)),
        ];
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $request->method()) {
                $methodMismatch = true;
                continue;
            }

            foreach ($route['middleware'] as $name) {
                $class = $this->middlewareMap[$name] ?? $name;
                /** @var Middleware $mw */
                $mw = new $class();
                $mw->handle($request);
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $method] = $route['handler'];
            $controller = new $class($request);
            $controller->$method(...array_values($params));
            return;
        }

        if ($methodMismatch) {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        // Panel adresi altındaysa panel temalı 404 göster
        $prefix = trim((string) Config::get('admin.prefix', 'admin'), '/');
        if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
            if (!Auth::check()) {
                (new \App\Middleware\AuthMiddleware())->handle($request); // giriş sayfasına yönlendirir
            }
            (new \App\Controllers\Admin\ErrorController($request))->notFound();
            return;
        }

        (new \App\Controllers\Site\PageController($request))->notFound();
    }
}
