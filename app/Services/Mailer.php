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
    public static function textToHtml(string $text): string
    {
        $body = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        $body = preg_replace('#(https?://[^\s<]+)#', '<a href="$1">$1</a>', $body);
        return '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:620px;margin:0 auto;background:#fff;border-radius:10px;padding:28px;color:#1f2937;font-size:15px;line-height:1.6">'
            . $body . '</div></body></html>';
    }
}
