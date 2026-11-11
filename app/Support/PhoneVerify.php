<?php

namespace App\Support;

/**
 * Ücretsiz telefon onayı (SMS yerine WhatsApp):
 * ziyaretçi formu gönderince 4 haneli bir kod üretilir; ziyaretçi bu kodu kendi WhatsApp'ından
 * site sahibinin numarasına gönderir. Mesaj o numaradan geldiği için numaranın gerçek olduğu kanıtlanır,
 * site sahibi panelde kodu karşılaştırıp "Doğrulandı" olarak işaretler.
 */
final class PhoneVerify
{
    /** WhatsApp açık ve site telefonu tanımlıysa onay adımı gösterilir */
    public static function enabled(): bool
    {
        return setting('whatsapp_enabled', '1') === '1' && whatsapp_href(setting('cv_phone', '')) !== '';
    }

    public static function code(): string
    {
        return (string) random_int(1000, 9999);
    }

    /**
     * Ziyaretçinin WhatsApp'ını site sahibinin numarasına hazır onay mesajıyla açan bağlantı
     * @param string $what "teklif talebimi" / "mesajımı"
     */
    public static function link(string $code, string $what, int $id): string
    {
        $domain = (string) setting('site_domain', preg_replace('#^https?://#', '', site_origin()));
        $text = "Merhaba, {$domain} üzerinden gönderdiğim {$what} (#{$id}) onaylıyorum. Onay kodu: {$code}";
        return whatsapp_href(setting('cv_phone', ''), $text);
    }
}
