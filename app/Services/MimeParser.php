<?php

namespace App\Services;

/**
 * Ham e-postayı (RFC 822 / MIME) çözümler: başlıklar, düz metin gövde, ek adları.
 * base64 / quoted-printable kodlamalarını ve farklı karakter setlerini UTF-8'e çevirir.
 */
class MimeParser
{
    /**
     * @return array{from_email: string, from_name: string, to_email: string, to_name: string, subject: string, message_id: string,
     *               in_reply_to: string, date: ?string, text: string, attachments: string[]}
     */
    public static function parse(string $raw): array
    {
        [$headers, $body] = self::split($raw);
        $parts = ['text' => '', 'html' => '', 'attachments' => []];
        self::walk($headers, $body, $parts);

        $text = trim($parts['text']) !== '' ? $parts['text'] : self::htmlToText($parts['html']);
        [$fromName, $fromEmail] = self::address($headers['from'] ?? '');
        // Birden çok alıcı olabilir; ilkini al
        [$toName, $toEmail] = self::address(explode(',', self::decodeHeader($headers['to'] ?? ''))[0] ?? '');
        $date = isset($headers['date']) ? strtotime(preg_replace('/\s*\(.*\)$/', '', $headers['date'])) : false;

        return [
            'from_email'  => $fromEmail,
            'from_name'   => $fromName,
            'to_email'    => $toEmail,
            'to_name'     => $toName,
            'subject'     => self::decodeHeader($headers['subject'] ?? '') ?: '(konusuz)',
            'message_id'  => trim($headers['message-id'] ?? ''),
            'in_reply_to' => trim($headers['in-reply-to'] ?? ''),
            'date'        => $date ? date('Y-m-d H:i:s', $date) : null,
            'text'        => self::normalize($text),
            'attachments' => $parts['attachments'],
        ];
    }

    /** Yanıt e-postalarındaki alıntılanan eski metni keser */
    public static function stripQuoted(string $text): string
    {
        $lines = explode("\n", $text);
        $out = [];
        foreach ($lines as $i => $line) {
            $t = trim($line);
            // "On ... wrote:" / "... tarihinde ... şunu yazdı:" / Outlook ayırıcıları
            if (preg_match('/(wrote|yazdı)\s*:\s*$/iu', $t)
                || preg_match('/^-{2,}\s*(Original Message|Orijinal İleti|Özgün İleti)/iu', $t)
                || preg_match('/^(From|Kimden)\s*:/u', $t) && $i > 0 && trim($lines[$i - 1] ?? '') === ''
                || preg_match('/^--- .+ yazdı ---$/u', $t)) {
                // Önceki satır "tarihinde" ile kesilmiş bir başlangıç olabilir (Gmail uzun satırı böler)
                if ($out && preg_match('/tarihinde|^On\s/iu', trim(end($out)))) {
                    array_pop($out);
                }
                break;
            }
            $out[] = $line;
        }
        // Sondaki ">" alıntı bloklarını temizle
        while ($out && (str_starts_with(trim(end($out)), '>') || trim(end($out)) === '')) {
            array_pop($out);
        }
        $result = trim(implode("\n", $out));
        return $result !== '' ? $result : trim($text);
    }

    // ------------------------------------------------------------

    /** @return array{0: array<string,string>, 1: string} */
    private static function split(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $pos = strpos($raw, "\n\n");
        $head = $pos === false ? $raw : substr($raw, 0, $pos);
        $body = $pos === false ? '' : substr($raw, $pos + 2);

        $headers = [];
        $head = preg_replace("/\n[ \t]+/", ' ', $head); // katlanmış satırları birleştir
        foreach (explode("\n", (string) $head) as $line) {
            if (($c = strpos($line, ':')) !== false) {
                $key = strtolower(trim(substr($line, 0, $c)));
                $headers[$key] ??= trim(substr($line, $c + 1));
            }
        }
        return [$headers, $body];
    }

    private static function walk(array $headers, string $body, array &$parts, int $depth = 0): void
    {
        if ($depth > 10) return;
        $type = strtolower($headers['content-type'] ?? 'text/plain');
        $mime = trim(explode(';', $type)[0]);
        $disposition = strtolower($headers['content-disposition'] ?? '');

        if (str_starts_with($mime, 'multipart/')) {
            if (!preg_match('/boundary="?([^";]+)"?/i', $headers['content-type'], $m)) return;
            $boundary = '--' . $m[1];
            $sections = explode($boundary, $body);
            array_shift($sections); // ilk sınırdan önceki giriş metni
            foreach ($sections as $section) {
                if (str_starts_with($section, '--')) break; // kapanış sınırı
                [$h, $b] = self::split(ltrim($section, "\n"));
                self::walk($h, $b, $parts, $depth + 1);
            }
            return;
        }

        $filename = self::filename($headers);
        if ($filename !== '' || str_starts_with($disposition, 'attachment')) {
            $parts['attachments'][] = $filename ?: 'ek';
            return;
        }

        if ($mime === 'message/rfc822') {
            [$h, $b] = self::split($body);
            self::walk($h, $b, $parts, $depth + 1);
            return;
        }

        if ($mime !== 'text/plain' && $mime !== 'text/html') return;

        $content = self::decodeBody($body, strtolower($headers['content-transfer-encoding'] ?? ''));
        $charset = preg_match('/charset="?([^";\s]+)"?/i', $headers['content-type'] ?? '', $m) ? $m[1] : 'UTF-8';
        $content = self::toUtf8($content, $charset);

        if ($mime === 'text/plain' && $parts['text'] === '') $parts['text'] = $content;
        if ($mime === 'text/html' && $parts['html'] === '') $parts['html'] = $content;
    }

    private static function decodeBody(string $body, string $encoding): string
    {
        return match (trim($encoding)) {
            'base64'           => (string) base64_decode(preg_replace('/\s+/', '', $body)),
            'quoted-printable' => quoted_printable_decode($body),
            default            => $body,
        };
    }

    private static function toUtf8(string $text, string $charset): string
    {
        $charset = strtoupper(trim($charset));
        if ($charset === '' || $charset === 'UTF-8' || $charset === 'US-ASCII') {
            return mb_check_encoding($text, 'UTF-8') ? $text : mb_convert_encoding($text, 'UTF-8', 'ISO-8859-9');
        }
        $map = ['WINDOWS-1254' => 'Windows-1254', 'CP1254' => 'Windows-1254', 'ISO-8859-9' => 'ISO-8859-9'];
        $from = $map[$charset] ?? $charset;
        try {
            return mb_convert_encoding($text, 'UTF-8', $from);
        } catch (\Throwable) {
            $converted = function_exists('iconv') ? @iconv($from, 'UTF-8//IGNORE', $text) : false;
            return $converted !== false ? $converted : mb_convert_encoding($text, 'UTF-8', 'ISO-8859-9');
        }
    }

    /** =?UTF-8?B?...?= / =?ISO-8859-9?Q?...?= başlıklarını çözer */
    public static function decodeHeader(string $value): string
    {
        $value = preg_replace('/\?=\s+=\?/', '?==?', $value); // ardışık kodlu parçalar arasındaki boşluk
        $decoded = preg_replace_callback('/=\?([^?]+)\?([BbQq])\?([^?]*)\?=/', static function ($m) {
            $text = strtoupper($m[2]) === 'B'
                ? (string) base64_decode($m[3])
                : quoted_printable_decode(str_replace('_', ' ', $m[3]));
            return self::toUtf8($text, $m[1]);
        }, (string) $value);
        return trim((string) $decoded);
    }

    /** @return array{0: string, 1: string} [ad, e-posta] */
    private static function address(string $value): array
    {
        $value = self::decodeHeader($value);
        if (preg_match('/^(.*)<([^>]+)>/', $value, $m)) {
            return [trim($m[1], " \"'"), strtolower(trim($m[2]))];
        }
        return ['', strtolower(trim($value, " <>"))];
    }

    private static function filename(array $headers): string
    {
        foreach (['content-disposition', 'content-type'] as $h) {
            if (isset($headers[$h]) && preg_match('/(?:file)?name\*?="?([^";]+)"?/i', $headers[$h], $m)) {
                $name = $m[1];
                if (preg_match("/^([^']*)'[^']*'(.+)$/", $name, $x)) { // RFC 2231: utf-8''dosya%20adi
                    $name = self::toUtf8(rawurldecode($x[2]), $x[1] ?: 'UTF-8');
                }
                return self::decodeHeader($name);
            }
        }
        return '';
    }

    private static function htmlToText(string $html): string
    {
        if ($html === '') return '';
        $html = preg_replace('#<(script|style|head)[^>]*>.*?</\1>#si', '', $html);
        $html = preg_replace('#<br\s*/?>#i', "\n", (string) $html);
        $html = preg_replace('#</(p|div|tr|li|h[1-6]|blockquote)>#i', "\n", (string) $html);
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return (string) preg_replace("/[ \t]+/", ' ', $text);
    }

    private static function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\xC2\xA0"], ["\n", "\n", ' '], $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        return trim((string) preg_replace("/\n{3,}/", "\n\n", (string) $text));
    }
}
