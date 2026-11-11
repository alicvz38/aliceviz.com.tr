<?php

namespace App\Controllers\Site;

use App\Core\Database;
use App\Services\MailAttachments;

/**
 * E-postadaki büyük dosyaların indirme bağlantısı: /dosya/{anahtar}
 * Anahtar tahmin edilemez (32 karakter); bağlantı 30 gün geçerlidir, sonra dosya silinir.
 */
class FileController extends SiteController
{
    public function download(string $token): void
    {
        $row = MailAttachments::findLink($token);
        if (!$row) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            header('X-Robots-Tag: noindex');
            echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dosya bulunamadı</title>'
                . '<body style="font-family:system-ui,sans-serif;background:#0b0f19;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:20px">'
                . '<div><h1 style="font-size:1.4rem">Dosya bulunamadı</h1><p style="color:#94a3b8">Bağlantının süresi dolmuş ya da dosya kaldırılmış olabilir.<br>Gönderen kişiden dosyayı yeniden göndermesini isteyebilirsiniz.</p>'
                . '<p><a href="' . e(url()) . '" style="color:#22d3ee">' . e(setting('site_domain', 'Ana sayfa')) . '</a></p></div></body>';
            return;
        }

        Database::getInstance()->query('UPDATE mail_attachments SET downloads = downloads + 1 WHERE id = ?', [$row['id']]);

        MailAttachments::stream($row); // tarayıcı açmaya çalışmaz, indirir
    }
}
