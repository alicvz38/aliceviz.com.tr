<?php

namespace App\Services;

use RuntimeException;

/**
 * Bağımlılıksız, küçük IMAP istemcisi (PHP imap eklentisi gerektirmez).
 * Yalnızca gelen e-postaları okumak için gereken komutları içerir:
 * LOGIN, SELECT, UID SEARCH, UID FETCH, LOGOUT.
 */
class ImapClient
{
    /** @var resource|null */
    private $socket = null;
    private int $tag = 0;

    public function __construct(
        private string $host,
        private int $port = 993,
        private string $secure = 'ssl'
    ) {
    }

    public function connect(string $user, string $pass): void
    {
        $remote = ($this->secure === 'ssl' ? 'ssl://' : 'tcp://') . $this->host . ':' . $this->port;
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException("IMAP sunucusuna bağlanılamadı ({$this->host}:{$this->port}): {$errstr}");
        }
        stream_set_timeout($socket, 30);
        $this->socket = $socket;

        $greeting = fgets($this->socket, 4096);
        if ($greeting === false || !str_starts_with($greeting, '* OK')) {
            throw new RuntimeException('IMAP sunucusu yanıt vermedi.');
        }

        try {
            $this->command('LOGIN ' . self::quote($user) . ' ' . self::quote($pass));
        } catch (RuntimeException) {
            throw new RuntimeException('IMAP girişi başarısız. Kullanıcı adı ve uygulama şifresini kontrol edin.');
        }
    }

    /**
     * @param bool $condstore Değişiklik sırası (HIGHESTMODSEQ) da istensin mi (Gmail destekler)
     * @return array{uidvalidity: int, uidnext: int, exists: int, modseq: int}
     */
    public function select(string $mailbox = 'INBOX', bool $condstore = false): array
    {
        $info = ['uidvalidity' => 0, 'uidnext' => 0, 'exists' => 0, 'modseq' => 0];
        foreach ($this->command('SELECT ' . self::quote($mailbox) . ($condstore ? ' (CONDSTORE)' : ''))['lines'] as $line) {
            if (preg_match('/UIDVALIDITY (\d+)/', $line, $m)) $info['uidvalidity'] = (int) $m[1];
            if (preg_match('/UIDNEXT (\d+)/', $line, $m)) $info['uidnext'] = (int) $m[1];
            if (preg_match('/HIGHESTMODSEQ (\d+)/', $line, $m)) $info['modseq'] = (int) $m[1];
            if (preg_match('/^\* (\d+) EXISTS/', $line, $m)) $info['exists'] = (int) $m[1];
        }
        return $info;
    }

    /**
     * Belirtilen değişiklik sırasından sonra bayrağı değişen e-postalar.
     * @return array<int, string[]> [uid => bayraklar]
     */
    public function changedFlags(int $sinceModseq): array
    {
        $out = [];
        foreach ($this->command('UID FETCH 1:* (FLAGS) (CHANGEDSINCE ' . $sinceModseq . ')')['lines'] as $line) {
            if (preg_match('/UID (\d+)/', $line, $u) && preg_match('/FLAGS \(([^)]*)\)/', $line, $f)) {
                $out[(int) $u[1]] = array_map('strtolower', preg_split('/\s+/', trim($f[1])) ?: []);
            }
        }
        return $out;
    }

    /** Bayrak ekler / kaldırır: store([1,2,3], '\\Seen', true) */
    public function store(array $uids, string $flag, bool $add): void
    {
        if ($uids) {
            $this->command('UID STORE ' . self::uidSet($uids) . ' ' . ($add ? '+' : '-') . 'FLAGS.SILENT (' . $flag . ')');
        }
    }

    /** Klasördeki TÜM e-postalara bayrak ekler (ör. tümünü okundu işaretle) */
    public function storeAll(string $flag): void
    {
        $this->command('UID STORE 1:* +FLAGS.SILENT (' . $flag . ')');
    }

    /** Birden çok e-postayı taşır */
    public function moveMany(array $uids, string $target): void
    {
        if ($uids) {
            $this->command('UID MOVE ' . self::uidSet($uids) . ' ' . self::quote($target));
        }
    }

    /** Seçili klasördeki TÜM e-postaları kalıcı siler (Spam / Çöp Kutusu'nu boşaltmak için) */
    public function deleteAll(): void
    {
        $this->command('UID STORE 1:* +FLAGS.SILENT (\\Deleted)');
        $this->command('EXPUNGE');
    }

    /** Birden çok e-postayı kalıcı siler */
    public function deleteMany(array $uids): void
    {
        if ($uids) {
            $this->command('UID STORE ' . self::uidSet($uids) . ' +FLAGS.SILENT (\\Deleted)');
            $this->command('EXPUNGE');
        }
    }

    /** [1,2,3,7,9,10] -> "1:3,7,9:10" */
    public static function uidSet(array $uids): string
    {
        $uids = array_values(array_unique(array_map('intval', $uids)));
        sort($uids);
        $parts = [];
        $start = $prev = null;
        foreach ($uids as $u) {
            if ($start === null) {
                $start = $prev = $u;
            } elseif ($u === $prev + 1) {
                $prev = $u;
            } else {
                $parts[] = $start === $prev ? (string) $start : $start . ':' . $prev;
                $start = $prev = $u;
            }
        }
        if ($start !== null) {
            $parts[] = $start === $prev ? (string) $start : $start . ':' . $prev;
        }
        return implode(',', $parts);
    }

    /** @return int[] Ölçüte uyan UID'ler (örn. 'UID 120:* FROM "a@b.com"' veya 'SINCE 01-Nov-2026') */
    public function search(string $criteria): array
    {
        $uids = [];
        foreach ($this->command('UID SEARCH ' . $criteria)['lines'] as $line) {
            if (str_starts_with($line, '* SEARCH')) {
                foreach (preg_split('/\s+/', trim(substr($line, 8))) as $n) {
                    if ($n !== '') $uids[] = (int) $n;
                }
            }
        }
        sort($uids);
        return $uids;
    }

    /** Ham e-postayı (başlık + gövde) döndürür; okundu işaretini değiştirmez */
    public function fetchRaw(int $uid, int $maxBytes = 2_000_000): ?string
    {
        return $this->fetch($uid, $maxBytes)['raw'];
    }

    /**
     * E-posta + bayrakları (\Seen okundu, \Flagged yıldızlı) döndürür; okundu işaretini değiştirmez.
     * @return array{raw: ?string, flags: string[]}
     */
    public function fetch(int $uid, int $maxBytes = 2_000_000): array
    {
        return $this->fetchMany([$uid], $maxBytes)[$uid] ?? ['raw' => null, 'flags' => []];
    }

    /**
     * Birden çok e-postayı TEK istekte indirir (her biri için ayrı gidiş-dönüş beklenmez → çok daha hızlı).
     * @return array<int, array{raw: string, flags: string[]}> [uid => ...]
     */
    public function fetchMany(array $uids, int $maxBytes = 600_000): array
    {
        if (!$uids) {
            return [];
        }
        $res = $this->command('UID FETCH ' . self::uidSet($uids) . ' (UID FLAGS BODY.PEEK[]<0.' . $maxBytes . '>)');
        $out = [];
        foreach ($res['pairs'] as [$line, $bytes, $rest]) {
            // UID ve FLAGS, içerikten önce veya sonra gelebilir
            $meta = $line . ' ' . $rest;
            if (!preg_match('/UID (\d+)/', $meta, $u)) {
                continue;
            }
            $flags = preg_match('/FLAGS \(([^)]*)\)/', $meta, $f) ? (preg_split('/\s+/', trim($f[1])) ?: []) : [];
            $out[(int) $u[1]] = ['raw' => $bytes, 'flags' => array_values(array_filter($flags, 'strlen'))];
        }
        return $out;
    }

    /**
     * Tüm klasörler. Gmail özel klasörleri işaretler: \Sent \Drafts \Junk \Trash \Important \All \Flagged
     * @return array<int, array{name: string, label: string, attrs: string[]}>
     */
    public function listFolders(): array
    {
        $folders = [];
        foreach ($this->command('LIST "" "*"')['lines'] as $line) {
            if (!preg_match('/^\* LIST \(([^)]*)\) (?:"[^"]*"|NIL) (.+)$/', $line, $m)) {
                continue;
            }
            $name = trim($m[2]);
            if (str_starts_with($name, '"')) {
                $name = stripcslashes(substr($name, 1, -1));
            }
            $attrs = array_map('strtolower', preg_split('/\s+/', trim($m[1])) ?: []);
            $folders[] = ['name' => $name, 'label' => self::decodeName($name), 'attrs' => $attrs];
        }
        return $folders;
    }

    /** E-postayı başka klasöre taşır (Gmail: Çöp Kutusu'na taşımak = silmek) */
    public function move(int $uid, string $target): void
    {
        $this->command('UID MOVE ' . $uid . ' ' . self::quote($target));
    }

    /** E-postayı kalıcı olarak siler (Çöp Kutusu / Spam içindeyken) */
    public function deletePermanently(int $uid): void
    {
        $this->command('UID STORE ' . $uid . ' +FLAGS.SILENT (\\Deleted)');
        $this->command('EXPUNGE');
    }

    /** IMAP klasör adları "değiştirilmiş UTF-7" ile kodlanır: [Gmail]/G&APY-nderilmi&AV8- Postalar */
    public static function decodeName(string $name): string
    {
        try {
            return mb_convert_encoding($name, 'UTF-8', 'UTF7-IMAP');
        } catch (\Throwable) {
            return $name;
        }
    }

    // ------------------------------------------------------------ IDLE (anlık bildirim)

    private ?string $idleTag = null;

    /** @return resource Ham bağlantı (stream_select ile dinlemek için) */
    public function socket()
    {
        return $this->socket;
    }

    /** Sunucuya "değişiklik olunca haber ver" der; seçili klasördeki yeni e-postalar anında bildirilir */
    public function idleStart(): void
    {
        $this->idleTag = 'A' . (++$this->tag);
        fwrite($this->socket, $this->idleTag . " IDLE\r\n");
        $line = $this->readLine();
        if (!str_starts_with($line, '+')) {
            $this->idleTag = null;
            throw new RuntimeException('IMAP IDLE desteklenmiyor: ' . trim($line));
        }
    }

    /** IDLE sırasında gelen satırları bekletmeden okur ("* 12 EXISTS" = yeni e-posta) */
    public function idleRead(): array
    {
        $lines = [];
        stream_set_blocking($this->socket, false);
        while (($line = fgets($this->socket, 65536)) !== false && $line !== '') {
            $lines[] = rtrim($line, "\r\n");
        }
        stream_set_blocking($this->socket, true);
        if (feof($this->socket)) {
            throw new RuntimeException('IMAP bağlantısı koptu.');
        }
        return $lines;
    }

    public function idleDone(): void
    {
        if ($this->idleTag === null) {
            return;
        }
        fwrite($this->socket, "DONE\r\n");
        while (true) {
            $line = $this->readLine();
            if (str_starts_with($line, $this->idleTag . ' ')) {
                break;
            }
        }
        $this->idleTag = null;
    }

    public function isIdling(): bool
    {
        return $this->idleTag !== null;
    }

    public function logout(): void
    {
        if (is_resource($this->socket)) {
            try {
                $this->idleDone();
                $this->command('LOGOUT');
            } catch (\Throwable) {
            }
            fclose($this->socket);
        }
        $this->socket = null;
    }

    public function __destruct()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    /**
     * Komutu gönderir, etiketli yanıta kadar okur.
     * {n} biçimindeki literal'ler (e-posta içerikleri) ayrıca toplanır.
     * @return array{lines: string[], literals: string[], pairs: array<int, array{0: string, 1: string, 2: string}>}
     */
    private function command(string $command): array
    {
        $tag = 'A' . (++$this->tag);
        fwrite($this->socket, $tag . ' ' . $command . "\r\n");

        $lines = [];
        $literals = [];
        $pairs = []; // [literalden önceki satır, literal, literalden sonraki satır devamı]
        while (true) {
            $line = $this->readLine();
            // Literal: satır "{1234}" ile bitiyorsa tam o kadar bayt oku, ardından satırın devamını al
            if (preg_match('/\{(\d+)\}\r\n$/', $line, $m)) {
                $bytes = $this->readBytes((int) $m[1]);
                $rest = rtrim($this->readLine(), "\r\n");
                $literals[] = $bytes;
                $pairs[] = [rtrim($line), $bytes, $rest];
                $lines[] = rtrim($line);
                continue;
            }
            $line = rtrim($line, "\r\n");
            if (str_starts_with($line, $tag . ' ')) {
                $status = substr($line, strlen($tag) + 1, 2);
                if ($status !== 'OK') {
                    throw new RuntimeException('IMAP hatası: ' . substr($line, strlen($tag) + 1));
                }
                break;
            }
            $lines[] = $line;
        }
        return ['lines' => $lines, 'literals' => $literals, 'pairs' => $pairs];
    }

    /**
     * Satırın TAMAMINI okur. (fgets tek seferde sınırlı bayt okur; binlerce UID içeren
     * "* SEARCH ..." yanıtı gibi uzun satırlar parçalar halinde gelir ve birleştirilmelidir.)
     */
    private function readLine(): string
    {
        $line = '';
        do {
            $chunk = fgets($this->socket, 65536);
            if ($chunk === false) {
                if ($line !== '') {
                    break;
                }
                throw new RuntimeException('IMAP bağlantısı koptu.');
            }
            $line .= $chunk;
        } while (!str_ends_with($line, "\n"));
        return $line;
    }

    private function readBytes(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, min(65536, $length - strlen($data)));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('IMAP verisi okunamadı.');
            }
            $data .= $chunk;
        }
        return $data;
    }

    public static function quote(string $value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }
}
