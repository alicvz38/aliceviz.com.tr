<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Siteden gelen mesaj / teklif talepleri için e-posta bildirimleri.
 *  - Site sahibine "yeni mesaj" bildirimi (Yanıtla düğmesi doğrudan ziyaretçiye gider)
 *  - Ziyaretçiye "mesajınız alındı" otomatik yanıtı
 * SMTP ayarlı değilse veya gönderim başarısız olursa sessizce geçilir; mesaj yine panele kaydedilmiştir.
 */
class Notifier
{
    public static function newMessage(string $name, string $email, string $subject, string $body, string $panelLink): void
    {
        if (!Mailer::isConfigured()) {
            return;
        }
        $siteTitle = (string) Setting::get('site_title', '');

        if (Setting::get('mail_notify', '1') === '1') {
            $owner = (string) (Setting::get('contact_email', '') ?: Setting::get('mail_from_email', ''));
            if ($owner !== '') {
                $text = "Sitenizden yeni bir mesaj geldi.\n\n"
                    . "Gönderen: {$name} <{$email}>\n"
                    . "Konu: {$subject}\n\n"
                    . $body . "\n\n"
                    . "— Bu e-postayı yanıtladığınızda doğrudan {$email} adresine gider.\n"
                    . "Panelde görüntüle: {$panelLink}";
                self::safeSend($owner, '[' . $siteTitle . '] ' . $subject, $text, [
                    'reply_to'   => $email,
                    'reply_name' => $name,
                ]);
            }
        }

        if (Setting::get('mail_autoreply', '1') === '1') {
            $text = "Merhaba {$name},\n\n"
                . "Mesajınız bana ulaştı, teşekkür ederim. En kısa sürede (genellikle 24 saat içinde) size WhatsApp ve e-posta üzerinden dönüş yapacağım.\n\n"
                . "Gönderdiğiniz mesaj:\n"
                . "------------------------------\n"
                . "Konu: {$subject}\n\n{$body}\n"
                . "------------------------------\n\n"
                . self::signature();
            self::safeSend($email, 'Mesajınız alındı: ' . $subject, $text, ['name' => $name]);
        }
    }

    public static function signature(): string
    {
        $sig = trim((string) Setting::get('mail_signature', ''));
        if ($sig !== '') {
            return $sig;
        }
        $lines = array_filter([
            'Saygılarımla,',
            (string) Setting::get('site_title', ''),
            (string) Setting::get('cv_title', ''),
            Setting::get('site_domain', '') ? 'https://' . Setting::get('site_domain', '') : '',
        ]);
        return implode("\n", $lines);
    }

    private static function safeSend(string $to, string $subject, string $text, array $options = []): void
    {
        try {
            (new Mailer())->send($to, $subject, $text, $options);
        } catch (\Throwable $e) {
            error_log('E-posta gönderilemedi (' . $to . '): ' . $e->getMessage());
        }
    }
}
