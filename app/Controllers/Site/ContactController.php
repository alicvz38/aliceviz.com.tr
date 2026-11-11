<?php

namespace App\Controllers\Site;

use App\Core\Response;
use App\Models\Message;
use App\Services\Notifier;
use App\Support\PhoneVerify;

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
        if ($phone === null) $errors['phone'] = 'Telefon numaranızı yazın; size WhatsApp üzerinden de dönüş yapacağım.';
        if ($phone === false) $errors['phone'] = 'Telefon numarasını 0 (5XX) XXX XX XX biçiminde eksiksiz yazın.';
        elseif (is_string($phone) && !str_starts_with($phone, '0 (5')) $errors['phone'] = 'WhatsApp ile dönüş yapabilmem için cep telefonu numaranızı yazın (05XX ile başlayan).';
        $data['phone'] = $phone ?: null;
        if (mb_strlen($data['name']) < 2) $errors['name'] = 'Adınızı yazın.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Geçerli bir e-posta adresi yazın; size bu adresten de dönüş yapacağım.';
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

        $code = PhoneVerify::code();
        $id = Message::create($data + ['folder' => 'inbox', 'ip' => $r->ip(), 'phone_code' => $code]);
        $this->hit('contact');

        $body = $data['body'] . "\n\nTelefon: " . $data['phone']
            . "\nWhatsApp onay kodu: " . $code . ' (bu kod WhatsApp\'tan bu numaradan gelirse numara gerçektir)';
        Notifier::newMessage($data['name'], $data['email'], $data['subject'], $body, $this->absolute(admin_url('mesajlar/' . $id)));

        $this->respond('Mesajınız gönderildi! En kısa sürede WhatsApp (' . $data['phone'] . ') ve e-posta (' . $data['email'] . ') üzerinden dönüş yapacağım.', url('iletisim'), [
            'verify_url'  => PhoneVerify::enabled() ? PhoneVerify::link($code, 'mesajımı', $id) : '',
            'verify_code' => $code,
        ]);
    }

    private function absolute(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
    }
}
