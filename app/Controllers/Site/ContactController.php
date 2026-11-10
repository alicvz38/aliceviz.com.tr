<?php

namespace App\Controllers\Site;

use App\Core\Response;
use App\Models\Message;
use App\Services\Notifier;

/**
 * İletişim: ziyaretçiler kendi e-posta adresleriyle mesaj gönderir.
 * Mesaj panelde "Mesajlar > Gelen Kutusu"na düşer, isteğe bağlı e-posta bildirimi gider.
 */
class ContactController extends SiteController
{
    public function index(): void
    {
        $this->render('contact', [
            'pageTitle'       => 'İletişim',
            'metaDescription' => 'Soru, iş birliği veya proje talepleriniz için bana yazın.',
            'subject'         => mb_substr((string) $this->request->get('konu', ''), 0, 150),
        ]);
    }

    public function store(): void
    {
        $r = $this->request;

        // Bot tuzağı: gizli alan doluysa sessizce "başarılı" de
        if ((string) $r->post('website', '') !== '') {
            Response::success('Mesajınız gönderildi. Teşekkürler!');
        }

        $data = [
            'name'    => mb_substr((string) $r->post('name', ''), 0, 100),
            'email'   => mb_substr((string) $r->post('email', ''), 0, 150),
            'subject' => mb_substr((string) $r->post('subject', ''), 0, 200),
            'body'    => mb_substr((string) $r->post('message', ''), 0, 5000),
        ];

        $errors = [];
        $phone = phone_normalize((string) $r->post('phone', ''));
        if ($phone === false) $errors['phone'] = 'Telefon numarasını 0 (5XX) XXX XX XX biçiminde eksiksiz yazın.';
        $data['phone'] = $phone ?: null;
        if (mb_strlen($data['name']) < 2) $errors['name'] = 'Adınızı yazın.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Geçerli bir e-posta adresi yazın; size bu adresten dönüş yapacağım.';
        if (mb_strlen($data['subject']) < 3) $errors['subject'] = 'Bir konu yazın.';
        if (mb_strlen($data['body']) < 10) $errors['message'] = 'Mesajınız en az 10 karakter olmalı.';
        if (!$r->post('consent')) $errors['consent'] = 'Devam etmek için onay vermelisiniz.';
        if ($errors) {
            $this->back('iletisim', 'Lütfen işaretli alanları kontrol edin.', $errors);
        }

        if ($wait = $this->throttle('contact', 60)) {
            Response::error("Çok sık mesaj gönderiyorsunuz. Lütfen {$wait} saniye sonra tekrar deneyin.", [], 429);
        }
        if (Message::recentFromIp($r->ip()) >= 5) {
            Response::error('Kısa sürede çok fazla mesaj gönderildi. Lütfen daha sonra tekrar deneyin.', [], 429);
        }

        $id = Message::create($data + ['folder' => 'inbox', 'ip' => $r->ip()]);
        $this->hit('contact');

        $body = $data['body'] . ($data['phone'] ? "\n\nTelefon: " . $data['phone'] : '');
        Notifier::newMessage($data['name'], $data['email'], $data['subject'], $body, $this->absolute(admin_url('mesajlar/' . $id)));

        $this->respond('Mesajınız gönderildi! En kısa sürede ' . $data['email'] . ' adresine dönüş yapacağım.', url('iletisim'));
    }

    private function absolute(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
    }
}
