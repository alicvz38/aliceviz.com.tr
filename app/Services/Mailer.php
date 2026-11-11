<?php

namespace App\Services;

use App\Models\Setting;
use RuntimeException;

/**
 * Bağımlılıksız SMTP e-posta gönderici.
 * SSL (465), STARTTLS (587) ve şifresiz (25) bağlantıları, AUTH LOGIN kimlik doğrulamasını destekler.
 * Ayarlar panelde: Mesajlar > E-posta Ayarları.
 *
 *   (new Mailer())->send('kisi@ornek.com', 'Konu', 'Düz metin gövde', ['reply_to' => ..., 'name' => ...]);
 */
class Mailer
{
    private string $host;
    private int $port;
    private string $secure;   // ssl | tls | none
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;
    /** @var resource|null */
    private $socket = null;
    private array $log = [];

    public function __construct(?array $config = null)
    {
        $c = $config ?? [
            'host'   => Setting::get('smtp_host', ''),
            'port'   => Setting::get('smtp_port', '587'),
            'secure' => Setting::get('smtp_secure', 'tls'),
            'user'   => Setting::get('smtp_user', ''),
            'pass'   => Setting::get('smtp_pass', ''),
            'from'   => Setting::get('mail_from_email', ''),
            'name'   => Setting::get('mail_from_name', '') ?: Setting::get('site_title', ''),
        ];
        $this->host = trim((string) $c['host']);
        $this->port = (int) $c['port'] ?: 587;
        $this->secure = in_array($c['secure'], ['ssl', 'tls', 'none'], true) ? $c['secure'] : 'tls';
        $this->user = (string) $c['user'];
        $this->pass = (string) $c['pass'];
        $this->fromEmail = (string) ($c['from'] ?: $c['user']);
        $this->fromName = (string) $c['name'];
    }

    public static function isConfigured(): bool
    {
        return Setting::get('smtp_host', '') !== '' && (Setting::get('mail_from_email', '') !== '' || Setting::get('smtp_user', '') !== '');
    }

    public function fromEmail(): string
    {
        return $this->fromEmail;
    }

    /**
     * @param array{name?: string, reply_to?: string, reply_name?: string, html?: string, attachments?: array<array{path: string, name: string, mime?: string}>} $options
     * @return string Gönderilen e-postanın Message-ID'si (Gmail kopyasıyla eşleştirmek için)
     * @throws RuntimeException
     */
    public function send(string $to, string $subject, string $text, array $options = []): string
    {
        if ($this->host === '' || $this->fromEmail === '') {
            throw new RuntimeException('E-posta (SMTP) ayarları yapılmamış. Panel > Mesajlar > E-posta Ayarları bölümünü doldurun.');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Geçersiz alıcı adresi: ' . $to);
        }

        $html = $options['html'] ?? self::textToHtml($text);
        $domain = substr(strrchr($this->fromEmail, '@') ?: '@localhost', 1);
        $messageId = '<' . bin2hex(random_bytes(12)) . '@' . $domain . '>';
        $message = $this->buildMessage($to, $options['name'] ?? '', $subject, $text, $html, $options + ['message_id' => $messageId]);

        try {
            $this->connect();
            if (!empty($options['attachments'])) {
                // Ekli e-postada veri büyüktür; sunucunun alma/tarama süresi için daha uzun bekle
                stream_set_timeout($this->socket, 180);
                @set_time_limit(300);
            }
            $this->command('MAIL FROM:<' . $this->fromEmail . '>', [250]);
            $this->command('RCPT TO:<' . $to . '>', [250, 251]);
            $this->command('DATA', [354]);
            // Satır başındaki noktalar ikiye katlanır (dot-stuffing)
            $data = preg_replace('/^\./m', '..', $message);
            $this->command($data . "\r\n.", [250]);
            $this->command('QUIT', [221], false);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            $this->socket = null;
        }
        return $messageId;
    }

    /** Bağlantı ve kimlik doğrulamayı dener (ayar testi) */
    public function test(): void
    {
        try {
            $this->connect();
            $this->command('QUIT', [221], false);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    // ------------------------------------------------------------

    private function connect(): void
    {
        $remote = ($this->secure === 'ssl' ? 'ssl://' : 'tcp://') . $this->host . ':' . $this->port;
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException("SMTP sunucusuna bağlanılamadı ({$this->host}:{$this->port}): {$errstr}");
        }
        stream_set_timeout($socket, 20);
        $this->socket = $socket;

        $this->expect([220]);
        $hostname = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
        $ehlo = $this->command('EHLO ' . $hostname, [250]);

        if ($this->secure === 'tls') {
            $this->command('STARTTLS', [220]);
            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!@stream_socket_enable_crypto($this->socket, true, $crypto)) {
                throw new RuntimeException('TLS bağlantısı kurulamadı. Port/güvenlik ayarını kontrol edin (587 = TLS, 465 = SSL).');
            }
            $ehlo = $this->command('EHLO ' . $hostname, [250]);
        }

        if ($this->user !== '') {
            if (stripos($ehlo, 'AUTH') === false) {
                throw new RuntimeException('Sunucu kimlik doğrulamayı desteklemiyor. Güvenlik ayarını TLS veya SSL yapın.');
            }
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode($this->user), [334]);
            try {
                $this->command(base64_encode($this->pass), [235]);
            } catch (RuntimeException) {
                throw new RuntimeException('SMTP kullanıcı adı veya şifre hatalı. Gmail kullanıyorsanız normal şifreniz yerine "Uygulama Şifresi" oluşturun.');
            }
        }
    }

    private function command(string $line, array $expected, bool $strict = true): string
    {
        fwrite($this->socket, $line . "\r\n");
        return $this->expect($expected, $strict);
    }

    private function expect(array $codes, bool $strict = true): string
    {
        $response = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $response .= $line;
            // "250-..." devam satırı, "250 ..." son satır
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $this->log[] = trim($response);
        $code = (int) substr($response, 0, 3);
        if ($strict && !in_array($code, $codes, true)) {
            throw new RuntimeException('SMTP hatası: ' . (trim($response) ?: 'sunucu yanıt vermedi'));
        }
        return $response;
    }

    private function buildMessage(string $to, string $toName, string $subject, string $text, string $html, array $o): string
    {
        $boundary = 'b' . bin2hex(random_bytes(12));
        $domain = substr(strrchr($this->fromEmail, '@') ?: '@localhost', 1);

        $headers = [
            'Date: ' . date('r'),
            'Message-ID: ' . ($o['message_id'] ?? '<' . bin2hex(random_bytes(10)) . '@' . $domain . '>'),
            'From: ' . self::address($this->fromEmail, $this->fromName),
            'To: ' . self::address($to, $toName),
            'Subject: ' . self::encode($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: WebSitem-Mailer',
        ];
        if (!empty($o['reply_to']) && filter_var($o['reply_to'], FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . self::address($o['reply_to'], $o['reply_name'] ?? '');
        }
        // Yanıtlarda Gmail'in aynı konuşmada göstermesi için
        if (!empty($o['in_reply_to']) && preg_match('/^<[^<>\s]+>$/', $o['in_reply_to'])) {
            $headers[] = 'In-Reply-To: ' . $o['in_reply_to'];
            $headers[] = 'References: ' . $o['in_reply_to'];
        }

        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text)) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html)) . "\r\n"
            . "--{$boundary}--";

        // Dosya ekleri: metin + HTML bölümü "multipart/mixed" içine alınır, her dosya ayrı bölüm olur
        $files = array_filter((array) ($o['attachments'] ?? []), static fn ($a) => is_array($a) && is_file((string) ($a['path'] ?? '')));
        if ($files) {
            $mixed = 'm' . bin2hex(random_bytes(12));
            foreach ($headers as $i => $h) {
                if (str_starts_with($h, 'Content-Type: multipart/alternative')) {
                    $headers[$i] = 'Content-Type: multipart/mixed; boundary="' . $mixed . '"';
                }
            }
            $out = "--{$mixed}\r\nContent-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n" . $body . "\r\n";
            foreach ($files as $a) {
                $name = str_replace(['"', "\r", "\n"], '', (string) $a['name']);
                $mime = preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#i', (string) ($a['mime'] ?? '')) ? $a['mime'] : 'application/octet-stream';
                $enc = self::encode($name);
                $out .= "--{$mixed}\r\n"
                    . "Content-Type: {$mime}; name=\"{$enc}\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\n"
                    . "Content-Disposition: attachment; filename=\"{$enc}\"; filename*=UTF-8''" . rawurlencode($name) . "\r\n\r\n"
                    . chunk_split(base64_encode((string) file_get_contents($a['path']))) . "\r\n";
            }
            $body = $out . "--{$mixed}--";
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private static function encode(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);
        return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }

    private static function address(string $email, string $name = ''): string
    {
        $email = str_replace(["\r", "\n", '<', '>'], '', $email);
        return $name !== '' ? self::encode(str_replace('"', '', $name)) . ' <' . $email . '>' : $email;
    }

    /** Düz metni sade, okunaklı bir HTML e-postaya çevirir */
    /** @param string $extraHtml Metnin altına eklenecek HTML (ör. dosya kartları) */
    public static function textToHtml(string $text, string $extraHtml = ''): string
    {
        $body = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        $body = preg_replace('#(https?://[^\s<]+)#', '<a href="$1" style="color:#22d3ee;text-decoration:none">$1</a>', $body);
        return self::layout($body . $extraHtml);
    }

    /**
     * Sitenin tasarımıyla e-posta iskeleti: koyu arka plan, camsı kart, camgöbeği → mor şerit,
     * üstte logo + alan adı, altta profil fotoğrafı ve imza. Tablo + satır içi stil (tüm e-posta uygulamaları).
     */
    public static function layout(string $content): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $domain = (string) \App\Models\Setting::get('site_domain', '') ?: preg_replace('#^https?://#', '', site_origin());
        $site = 'https://' . preg_replace('#^www\.#', '', $domain);
        $name = (string) \App\Models\Setting::get('site_title', '');
        $job = (string) \App\Models\Setting::get('cv_title', '');
        $photo = '';
        $path = (string) \App\Models\Setting::get('cv_avatar', '');
        if ($path !== '' && is_file(ROOT_PATH . '/' . $path)) {
            $photo = $site . '/' . ltrim($path, '/') . asset_version(ROOT_PATH . '/' . $path);
        }
        // Arka plan: dışta döşenen yıldız deseni; ortadaki panelde sitenin arka plan resmi (eposta-kapak.jpg)
        $asset = static function (string $file) use ($site): string {
            $full = ROOT_PATH . '/assets/img/' . $file;
            return is_file($full) ? $site . '/assets/img/' . $file . asset_version($full) : '';
        };
        $bg = $asset('eposta-arka-plan.jpg');
        $cover = $asset('eposta-kapak.jpg');
        $font = "'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

        $sign = '';
        if ($name !== '') {
            $sign = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto"><tr>'
                . ($photo !== '' ? '<td style="padding-right:12px;vertical-align:middle"><img src="' . $esc($photo) . '" width="44" height="44" alt="" style="display:block;width:44px;height:44px;border-radius:50%;border:2px solid #1f2a44;object-fit:cover"></td>' : '')
                . '<td style="vertical-align:middle;text-align:left;font-family:' . $font . '">'
                . '<div style="color:#e2e8f0;font-size:14px;font-weight:bold">' . $esc($name) . '</div>'
                . ($job !== '' ? '<div style="color:#94a3b8;font-size:12px;margin-top:2px">' . $esc($job) . '</div>' : '')
                . '</td></tr></table>';
        }

        // Cam kart: yarı saydam zemin + ince açık kenarlık (sitedeki .glass gibi); rgba desteklemeyende koyu zemin
        $glass = 'background-color:#111827;background-color:rgba(15,23,42,.66);border:1px solid #2a3550;border:1px solid rgba(255,255,255,.14);border-radius:18px';
        // Orta panel: sitenin resmi tam genişlikte, ortalı (panel sabit genişlikte ve ortada; resim her uygulamada ortada kalır)
        $panel = 'max-width:660px;border-radius:22px;background-color:#0b0f19'
            . ($cover !== '' ? ';background-image:url(' . $esc($cover) . ');background-size:cover;background-position:center center;background-repeat:no-repeat' : '');

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta name="color-scheme" content="dark"><meta name="supported-color-schemes" content="dark">'
            . '<style>:root{color-scheme:dark}a{color:#22d3ee}</style></head>'
            . '<body style="margin:0;padding:0;background-color:#0b0f19' . ($bg !== '' ? ';background-image:url(' . $esc($bg) . ');background-repeat:repeat' : '') . '" bgcolor="#0b0f19">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#0b0f19"' . ($bg !== '' ? ' background="' . $esc($bg) . '"' : '') . ' style="background-color:#0b0f19' . ($bg !== '' ? ';background-image:url(' . $esc($bg) . ');background-repeat:repeat' : '') . '">'
            . '<tr><td align="center" style="padding:28px 12px">'
            // Orta panel (resimli)
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#0b0f19"' . ($cover !== '' ? ' background="' . $esc($cover) . '"' : '') . ' style="' . $panel . '">'
            . '<tr><td style="padding:30px 26px 26px">'
            // Üst: logo + alan adı
            . '<a href="' . $esc($site) . '" style="text-decoration:none"><table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:20px"><tr>'
            . '<td width="34" height="34" align="center" bgcolor="#1e293b" style="width:34px;height:34px;border-radius:10px;background:#1e293b;background-image:linear-gradient(135deg,#22d3ee,#a855f7);color:#ffffff;font:bold 14px/34px Consolas,monospace">&lt;&gt;</td>'
            . '<td style="padding-left:10px;color:#f1f5f9;font:bold 17px ' . $font . ';text-shadow:0 1px 6px rgba(0,0,0,.6)">' . $esc($domain) . '</td>'
            . '</tr></table></a>'
            // Cam kart: metin + dosyalar
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#111827" style="' . $glass . '"><tr><td style="padding:0">'
            . '<div style="height:4px;line-height:4px;font-size:0;border-radius:18px 18px 0 0;background:#22d3ee;background-image:linear-gradient(90deg,#22d3ee,#6366f1,#a855f7)">&nbsp;</div>'
            . '<div style="padding:28px 28px 26px;color:#e2e8f0;font-family:' . $font . ';font-size:15px;line-height:1.7">' . $content . '</div>'
            . '</td></tr></table>'
            // İmza + bağlantılar (panelin içinde, kartın altında)
            . ($sign !== '' ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="padding:22px 0 4px">' . $sign . '</td></tr></table>' : '')
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="padding:10px 0 0;font-family:' . $font . ';font-size:12px;color:#94a3b8;text-shadow:0 1px 4px rgba(0,0,0,.7)">'
            . '<a href="' . $esc($site) . '" style="color:#cbd5e1;text-decoration:none">' . $esc($domain) . '</a>'
            . ' &nbsp;&middot;&nbsp; <a href="' . $esc($site . '/projeler') . '" style="color:#cbd5e1;text-decoration:none">Projeler</a>'
            . ' &nbsp;&middot;&nbsp; <a href="' . $esc($site . '/iletisim') . '" style="color:#cbd5e1;text-decoration:none">İletişim</a>'
            . '</td></tr></table>'
            . '</td></tr></table>'
            . '</td></tr></table></body></html>';
    }
}
