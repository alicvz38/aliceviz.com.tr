<?php

namespace App\Core;

/**
 * AJAX yanıtları.
 *
 * Tüm JSON yanıtları aynı biçimdedir:
 *   {
 *     "ok": true|false,
 *     "message": "Kullanıcıya gösterilecek ilk mesaj",
 *     "messages": [{"type": "success", "message": "..."}],
 *     "errors": {"alan": "hata"},     // isteğe bağlı (form doğrulama)
 *     "redirect": "/admin/projeler",  // isteğe bağlı
 *     "data": {...}                   // isteğe bağlı
 *   }
 */
final class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        if (!empty($payload['ok']) && !empty($payload['message']) && is_string($payload['message'])) {
            \App\Support\ActivityLog::capture($payload['message']); // panel etkinlik günlüğü
        }
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public static function success(string $message = '', array $extra = [], int $status = 200): never
    {
        self::json(array_merge([
            'ok'       => true,
            'message'  => $message,
            'messages' => $message !== '' ? [['type' => 'success', 'message' => $message]] : [],
        ], $extra), $status);
    }

    public static function error(string $message, array $errors = [], int $status = 422, array $extra = []): never
    {
        self::json(array_merge([
            'ok'       => false,
            'message'  => $message,
            'messages' => [['type' => 'danger', 'message' => $message]],
            'errors'   => (object) $errors,
        ], $extra), $status);
    }

    /**
     * Klasik "flash + yönlendir" akışının AJAX karşılığı.
     * Oturumdaki flash mesajlarını JSON'a taşır; böylece mevcut denetleyiciler
     * hiçbir değişiklik yapmadan AJAX ile de çalışır.
     */
    public static function fromFlash(string $redirect): never
    {
        $messages = Session::pullFlash();
        Session::clearOld();
        $ok = true;
        foreach ($messages as $m) {
            if ($m['type'] === 'danger') {
                $ok = false;
            }
        }
        self::json([
            'ok'       => $ok,
            'message'  => $messages[0]['message'] ?? '',
            'messages' => $messages,
            'redirect' => $redirect,
        ], $ok ? 200 : 422);
    }
}
