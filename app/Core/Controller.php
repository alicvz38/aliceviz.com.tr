<?php

namespace App\Core;

/**
 * Tüm denetleyicilerin taban sınıfı.
 */
abstract class Controller
{
    protected Request $request;
    protected View $view;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->view = new View($this->theme());
        $this->boot();
    }

    /** Kullanılacak tema adı */
    abstract protected function theme(): string;

    /** Alt sınıflar ortak verileri burada paylaşabilir */
    protected function boot(): void
    {
    }

    protected function render(string $page, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        echo $this->view->render($page, $data);
        Session::clearOld();
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    /** Doğrulama hatasında geri dön: mesaj + eski girdiler (AJAX ise JSON hata) */
    protected function back(string $path, string $error, array $errors = []): never
    {
        if (Request::isAjax()) {
            Response::error($error, $errors);
        }
        Session::flashInput($this->request->all());
        Session::flash('danger', $error);
        redirect($path);
    }

    /** JSON yanıtı (AJAX uç noktaları için) */
    protected function json(array $payload, int $status = 200): never
    {
        Response::json($payload, $status);
    }

    /**
     * Hem AJAX hem klasik istekte çalışan başarılı yanıt.
     *   AJAX   -> {"ok": true, "message": ..., "data": ..., "redirect": ...}
     *   Normal -> flash mesajı + yönlendirme
     */
    protected function respond(string $message, string $redirectTo, array $data = [], string $type = 'success'): never
    {
        if (Request::isAjax()) {
            Response::json([
                'ok'       => $type !== 'danger',
                'message'  => $message,
                'messages' => [['type' => $type, 'message' => $message]],
                'redirect' => $redirectTo,
                'data'     => (object) $data,
            ], $type === 'danger' ? 422 : 200);
        }
        Session::flash($type, $message);
        redirect($redirectTo);
    }
}
