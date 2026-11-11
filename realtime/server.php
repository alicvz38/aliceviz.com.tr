<?php
/**
 * CANLI BİLDİRİM SUNUCUSU (WebSocket) — bağımlılıksız saf PHP
 * ---------------------------------------------------------------
 * - Gmail'i IMAP IDLE ile dinler: yeni e-posta Gmail'e düştüğü an panele aktarılır
 * - Panele (tarayıcıya) WebSocket ile anında bildirir: yeni e-posta, okunmamış sayısı,
 *   yeni teklif talebi, site iletişim formu mesajları
 * - Ayrıca dakikada bir tüm klasörleri senkronize eder (cron'a gerek kalmaz)
 *
 * Başlatma (Windows/XAMPP):  realtime\baslat.bat   (pencere açmadan arka planda çalışır)
 * Durdurma:                  realtime\durdur.bat
 * Bilgisayar açılınca otomatik: realtime\otomatik-baslat-kur.bat
 * Linux VPS:  php realtime/server.php   (systemd / supervisor ile sürekli çalıştırın)
 *
 * Adres: ws://127.0.0.1:8090 (config/config.php > realtime)
 * Bu klasör tarayıcıdan erişime kapalıdır (.htaccess).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Yalnızca komut satırından çalıştırılabilir.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Migrator;
use App\Models\Message;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\ImapClient;
use App\Services\MailFetcher;
use App\Support\Realtime;

set_time_limit(0);
ignore_user_abort(true);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

$verbose = in_array('-v', $argv ?? [], true);
$logFile = STORAGE_PATH . '/realtime.log';
$stopFile = STORAGE_PATH . '/realtime.stop';

function rt_log(string $msg): void
{
    global $logFile, $verbose;
    $line = date('Y-m-d H:i:s') . '  ' . $msg . PHP_EOL;
    if (is_file($logFile) && filesize($logFile) > 1_000_000) {
        @rename($logFile, $logFile . '.1');
    }
    @file_put_contents($logFile, $line, FILE_APPEND);
    if ($verbose) {
        echo $line;
    }
}

// ---------------------------------------------------------------- Tek örnek kilidi
$lock = fopen(STORAGE_PATH . '/realtime.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Canlı sunucu zaten çalışıyor.\n";
    exit(0);
}
@unlink($stopFile);

try {
    (new Migrator())->run();
} catch (Throwable $e) {
    rt_log('Migration hatası: ' . $e->getMessage());
}

$host = Realtime::host();
$port = Realtime::port();
$server = @stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);
if (!$server) {
    rt_log("Sunucu başlatılamadı ({$host}:{$port}): {$errstr}");
    exit(1);
}
stream_set_blocking($server, false);
rt_log("Canlı sunucu başladı: ws://{$host}:{$port}");

// ---------------------------------------------------------------- WebSocket yardımcıları
/** @return array{0:int,1:string}|null [opcode, veri] veya eksikse null */
function ws_decode(string &$buf): ?array
{
    if (strlen($buf) < 2) return null;
    $b1 = ord($buf[0]);
    $b2 = ord($buf[1]);
    $opcode = $b1 & 0x0f;
    $masked = ($b2 & 0x80) !== 0;
    $len = $b2 & 0x7f;
    $off = 2;
    if ($len === 126) {
        if (strlen($buf) < 4) return null;
        $len = unpack('n', substr($buf, 2, 2))[1];
        $off = 4;
    } elseif ($len === 127) {
        if (strlen($buf) < 10) return null;
        $len = unpack('J', substr($buf, 2, 8))[1];
        $off = 10;
    }
    if ($len > 65536) {
        $buf = '';
        return [8, '']; // çok büyük çerçeve: bağlantıyı kapat
    }
    $maskLen = $masked ? 4 : 0;
    if (strlen($buf) < $off + $maskLen + $len) return null;
    $payload = substr($buf, $off + $maskLen, $len);
    if ($masked) {
        $mask = substr($buf, $off, 4);
        for ($i = 0; $i < $len; $i++) {
            $payload[$i] = $payload[$i] ^ $mask[$i % 4];
        }
    }
    $buf = (string) substr($buf, $off + $maskLen + $len);
    return [$opcode, $payload];
}

function ws_encode(string $payload, int $opcode = 1): string
{
    $len = strlen($payload);
    $head = chr(0x80 | $opcode);
    if ($len < 126) {
        $head .= chr($len);
    } elseif ($len < 65536) {
        $head .= chr(126) . pack('n', $len);
    } else {
        $head .= chr(127) . pack('J', $len);
    }
    return $head . $payload;
}

/** @var array<int, array{sock: resource, buf: string, ready: bool, uid: int}> $clients */
$clients = [];

function ws_send(array $client, array $data): bool
{
    $frame = ws_encode(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $written = 0;
    $tries = 0;
    while ($written < strlen($frame) && $tries++ < 50) {
        $n = @fwrite($client['sock'], substr($frame, $written));
        if ($n === false) return false;
        if ($n === 0) usleep(2000);
        $written += $n;
    }
    return $written === strlen($frame);
}

function ws_close(int $id): void
{
    global $clients;
    if (isset($clients[$id])) {
        @fwrite($clients[$id]['sock'], ws_encode('', 8));
        @fclose($clients[$id]['sock']);
        unset($clients[$id]);
    }
}

/** Yalnızca panellere (yöneticilere) gönder */
function broadcast(array $data): void
{
    global $clients;
    foreach ($clients as $id => $c) {
        if ($c['ready'] && $c['role'] === 'admin' && !ws_send($c, $data)) {
            ws_close($id);
        }
    }
}

/** Belirli bir canlı destek sohbetinin ziyaretçisine gönder */
function send_visitor(int $conversationId, array $data): void
{
    global $clients;
    foreach ($clients as $id => $c) {
        if ($c['ready'] && $c['role'] === 'visitor' && $c['conv'] === $conversationId && !ws_send($c, $data)) {
            ws_close($id);
        }
    }
}

function handshake(int $id): void
{
    global $clients;
    $c = &$clients[$id];
    $end = strpos($c['buf'], "\r\n\r\n");
    if ($end === false) {
        if (strlen($c['buf']) > 8192) ws_close($id);
        return;
    }
    $head = substr($c['buf'], 0, $end);
    $c['buf'] = (string) substr($c['buf'], $end + 4);

    $lines = explode("\r\n", $head);
    $headers = [];
    foreach (array_slice($lines, 1) as $l) {
        if (($p = strpos($l, ':')) !== false) {
            $headers[strtolower(trim(substr($l, 0, $p)))] = trim(substr($l, $p + 1));
        }
    }
    parse_str((string) parse_url(explode(' ', $lines[0])[1] ?? '/', PHP_URL_QUERY), $query);
    $token = (string) ($query['token'] ?? '');
    // Yönetici (panel) veya canlı destek ziyaretçisi (yalnızca kendi sohbeti)
    $uid = str_starts_with($token, 'v.') ? null : Realtime::verify($token);
    $conv = $uid ? null : Realtime::verifyVisitor($token);
    $key = $headers['sec-websocket-key'] ?? '';

    if ((!$uid && !$conv) || $key === '' || stripos($headers['upgrade'] ?? '', 'websocket') === false) {
        @fwrite($c['sock'], "HTTP/1.1 401 Unauthorized\r\nConnection: close\r\nContent-Length: 0\r\n\r\n");
        ws_close($id);
        return;
    }
    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    fwrite($c['sock'], "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: {$accept}\r\n\r\n");
    $c['ready'] = true;
    if ($uid) {
        $c['role'] = 'admin';
        $c['uid'] = $uid;
        ws_send($c, ['type' => 'hello', 'live' => true] + snapshot());
    } else {
        $c['role'] = 'visitor';
        $c['conv'] = $conv;
        ws_send($c, ['type' => 'hello', 'live' => true, 'online' => admin_count() > 0 || App\Services\LiveChat::adminOnline()]);
        Database::getInstance()->query('UPDATE chat_conversations SET visitor_seen_at = NOW() WHERE id = ?', [$conv]);
    }
}

function admin_count(): int
{
    global $clients;
    return count(array_filter($clients, static fn ($c) => $c['ready'] && $c['role'] === 'admin'));
}

// ---------------------------------------------------------------- Veri
function snapshot(): array
{
    Setting::refresh();
    $last = (int) Setting::get('imap_last_sync', '0');
    return [
        'type'         => 'counters',
        'messages'     => Message::countUnread(),
        'quotes'       => QuoteRequest::countNew(),
        'chats'        => App\Models\ChatConversation::countUnread(),
        'mail_version' => (string) Database::getInstance()->fetchColumn("SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(id), 0), '-', COALESCE(SUM(is_read), 0)) FROM messages"),
        'synced_ago'   => $last ? time_ago(date('Y-m-d H:i:s', $last)) : null,
        'progress'     => MailFetcher::progress(),
        'imap'         => $GLOBALS['imapState'] ?? 'off',
        'stats_version' => App\Support\Stats::version(),
    ];
}

$lastSnapshot = null;
$lastMailId = 0;
$lastQuoteId = 0;

/** Değişiklik varsa tüm panellere gönder (yeni e-posta / teklif bildirimleriyle) */
function check_and_broadcast(): void
{
    global $lastSnapshot, $lastMailId, $lastQuoteId;
    $snap = snapshot();
    $db = Database::getInstance();

    // Yeni e-postalar: yalnızca son 1 güne ait okunmamışlar (eski e-postaların aktarımı bildirim üretmesin)
    $newMails = $db->fetchAll(
        "SELECT id, name, email, subject, source FROM messages
         WHERE folder = 'inbox' AND id > ? AND is_read = 0 AND created_at > (NOW() - INTERVAL 1 DAY)
         ORDER BY id DESC LIMIT 5",
        [$lastMailId]
    );
    $newQuotes = $db->fetchAll('SELECT id, name, service FROM quote_requests WHERE id > ? ORDER BY id DESC LIMIT 5', [$lastQuoteId]);
    $lastMailId = max($lastMailId, (int) $db->fetchColumn("SELECT COALESCE(MAX(id), 0) FROM messages"));
    $lastQuoteId = max($lastQuoteId, (int) $db->fetchColumn('SELECT COALESCE(MAX(id), 0) FROM quote_requests'));

    $sig = [$snap['messages'], $snap['quotes'], $snap['chats'], $snap['mail_version'], $snap['progress']['imported'], $snap['progress']['done'], $snap['imap'], $snap['stats_version']];
    if ($sig !== $lastSnapshot) {
        broadcast($snap);
        $lastSnapshot = $sig;
    }
    if ($newMails) {
        broadcast(['type' => 'new_mail', 'items' => $newMails]);
    }
    if ($newQuotes) {
        broadcast(['type' => 'new_quote', 'items' => $newQuotes]);
    }
}

// ---------------------------------------------------------------- Canlı destek
$lastChatId = 0;
$typingSent = []; // "conv:kim" => son gönderim zamanı
$lastAdminOnline = null;

/** Yeni sohbet mesajlarını ve "yazıyor" durumlarını ilgili taraflara anında ilet */
function chat_dispatch(): void
{
    global $lastChatId, $typingSent, $lastAdminOnline, $clients;
    $db = Database::getInstance();

    foreach ($db->fetchAll(
        'SELECT m.*, c.name FROM chat_messages m JOIN chat_conversations c ON c.id = m.conversation_id WHERE m.id > ? ORDER BY m.id ASC LIMIT 100',
        [$lastChatId]
    ) as $row) {
        $lastChatId = (int) $row['id'];
        $msg = (new App\Models\ChatMessage($row))->toClient();
        broadcast(['type' => 'chat', 'message' => $msg, 'name' => $row['name']]);
        send_visitor((int) $row['conversation_id'], ['type' => 'chat', 'message' => $msg]);
    }

    // "Yazıyor..." (en fazla 2 sn'de bir aynı bildirim)
    foreach ($db->fetchAll(
        'SELECT id, admin_typing_at, visitor_typing_at FROM chat_conversations
         WHERE admin_typing_at > (NOW() - INTERVAL 4 SECOND) OR visitor_typing_at > (NOW() - INTERVAL 4 SECOND)'
    ) as $row) {
        $id = (int) $row['id'];
        if ($row['visitor_typing_at'] && strtotime($row['visitor_typing_at']) > time() - 4 && (time() - ($typingSent["$id:v"] ?? 0)) >= 2) {
            broadcast(['type' => 'typing', 'conv' => $id, 'who' => 'visitor']);
            $typingSent["$id:v"] = time();
        }
        if ($row['admin_typing_at'] && strtotime($row['admin_typing_at']) > time() - 4 && (time() - ($typingSent["$id:a"] ?? 0)) >= 2) {
            send_visitor($id, ['type' => 'typing', 'who' => 'admin']);
            $typingSent["$id:a"] = time();
        }
    }

    // Yönetici çevrimiçi / çevrimdışı olunca ziyaretçilere haber ver
    $online = admin_count() > 0 || App\Services\LiveChat::adminOnline();
    if ($online !== $lastAdminOnline) {
        foreach ($clients as $id => $c) {
            if ($c['ready'] && $c['role'] === 'visitor') {
                ws_send($c, ['type' => 'presence', 'online' => $online]);
            }
        }
        $lastAdminOnline = $online;
    }
}

// İlk durum: mevcut kayıtlar için bildirim gönderme
try {
    $lastChatId = (int) Database::getInstance()->fetchColumn('SELECT COALESCE(MAX(id), 0) FROM chat_messages');
    $lastMailId = (int) Database::getInstance()->fetchColumn('SELECT COALESCE(MAX(id), 0) FROM messages');
    $lastQuoteId = (int) Database::getInstance()->fetchColumn('SELECT COALESCE(MAX(id), 0) FROM quote_requests');
} catch (Throwable $e) {
    rt_log('Veritabanı hatası: ' . $e->getMessage());
}

// ---------------------------------------------------------------- Gmail (IMAP IDLE)
/** @var ImapClient|null $imap */
$imap = null;
$imapState = 'off';
$imapTry = 0;
$idleSince = 0;
$lastSync = 0;

/**
 * E-posta senkronizasyonu AYRI bir süreçte çalışır (cron/mail-sync.php).
 * Böylece bu sunucunun döngüsü hiç bloklanmaz: sohbet mesajları ve bildirimler
 * senkronizasyon sürerken de anında iletilir.
 * @var resource|null $child
 */
$child = null;

function sync_running(): bool
{
    global $child;
    if (!$child) {
        return false;
    }
    $status = proc_get_status($child);
    if ($status['running']) {
        return true;
    }
    proc_close($child);
    $child = null;
    return false;
}

function spawn_sync(): void
{
    global $child, $lastSync;
    if (sync_running()) {
        return;
    }
    $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    // php-win.exe ile başlatıldıysa alt süreç de pencere açmadan çalışır
    $child = @proc_open(
        [PHP_BINARY, ROOT_PATH . '/cron/mail-sync.php'],
        [0 => ['file', $null, 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']],
        $pipes,
        ROOT_PATH
    ) ?: null;
    $lastSync = time();
    if (!$child) {
        rt_log('Senkronizasyon süreci başlatılamadı.');
    }
}

function imap_connect(): void
{
    global $imap, $imapState, $imapTry, $idleSince;
    $imapTry = time();
    Setting::refresh();
    if (!MailFetcher::isEnabled()) {
        $imapState = 'off';
        return;
    }
    try {
        $imap = new ImapClient((string) Setting::get('imap_host', 'imap.gmail.com'), (int) Setting::get('imap_port', '993') ?: 993, 'ssl');
        $imap->connect((string) Setting::get('smtp_user', ''), (string) Setting::get('smtp_pass', ''));
        $imap->select('INBOX');
        $imap->idleStart();
        $idleSince = time();
        $imapState = 'idle';
        rt_log('Gmail dinleniyor (IMAP IDLE).');
    } catch (Throwable $e) {
        $imap = null;
        $imapState = 'error';
        rt_log('Gmail bağlantı hatası: ' . $e->getMessage());
    }
}

function write_status(): void
{
    global $clients, $imapState, $started;
    @file_put_contents(Realtime::statusFile(), json_encode([
        'heartbeat' => time(),
        'started'   => $started,
        'pid'       => getmypid(),
        'clients'   => admin_count(), // bağlı panel sayısı (canlı destek için "çevrimiçi")
        'visitors'  => count(array_filter($clients, static fn ($c) => $c['ready'] && $c['role'] === 'visitor')),
        'syncing'   => sync_running(),
        'imap'      => $imapState,
        'address'   => 'ws://' . Realtime::host() . ':' . Realtime::port(),
    ]));
}

$started = time();
$lastCheck = 0;
$lastStatus = 0;
$lastSeen = 0;
$syncWanted = true;
// Eski e-postaların aktarımı sürüyorsa senkronizasyon süreci bitince hemen yeniden başlatılır
$backfillPending = !MailFetcher::progress()['done'];
write_status();
imap_connect();

// ---------------------------------------------------------------- Ana döngü
while (true) {
    if (is_file($stopFile)) {
        @unlink($stopFile);
        rt_log('Durdurma isteği alındı.');
        break;
    }

    $read = [$server];
    foreach ($clients as $c) {
        $read[] = $c['sock'];
    }
    if ($imap) {
        $read[] = $imap->socket();
    }
    $write = $except = null;
    // En fazla 0,5 sn olay bekle (sohbet mesajları bu sıklıkla iletilir)
    if (@stream_select($read, $write, $except, 0, 500000) === false) {
        usleep(200000);
    }

    $mailEvent = false;
    foreach ($read as $sock) {
        if ($sock === $server) {
            $conn = @stream_socket_accept($server, 0);
            if ($conn) {
                stream_set_blocking($conn, false);
                $clients[(int) $conn] = ['sock' => $conn, 'buf' => '', 'ready' => false, 'uid' => 0, 'role' => '', 'conv' => 0, 'since' => time()];
            }
            continue;
        }
        if ($imap && $sock === $imap->socket()) {
            try {
                foreach ($imap->idleRead() as $line) {
                    if (preg_match('/^\* \d+ (EXISTS|EXPUNGE|FETCH)/', $line)) {
                        $mailEvent = true;
                    }
                }
            } catch (Throwable $e) {
                rt_log('Gmail bağlantısı koptu: ' . $e->getMessage());
                $imap = null;
                $imapState = 'error';
            }
            continue;
        }

        $id = (int) $sock;
        if (!isset($clients[$id])) continue;
        $data = @fread($sock, 65536);
        if ($data === '' || $data === false) {
            if (feof($sock)) ws_close($id);
            continue;
        }
        $clients[$id]['buf'] .= $data;
        if (!$clients[$id]['ready']) {
            try {
                handshake($id);
            } catch (Throwable $e) {
                rt_log('Bağlantı hatası: ' . $e->getMessage());
                Database::reset();
                ws_close($id);
            }
            if (!isset($clients[$id]) || !$clients[$id]['ready']) continue;
        }
        while (isset($clients[$id]) && ($frame = ws_decode($clients[$id]['buf'])) !== null) {
            [$op, $payload] = $frame;
            if ($op === 8) {
                ws_close($id);
            } elseif ($op === 9) {
                @fwrite($clients[$id]['sock'], ws_encode($payload, 10)); // ping -> pong
            } elseif ($op === 1 && $payload === 'refresh' && $clients[$id]['role'] === 'admin') {
                try { ws_send($clients[$id], snapshot()); } catch (Throwable) {}
            }
        }
    }

    // El sıkışmasını tamamlamayan bağlantıları kapat
    foreach ($clients as $id => $c) {
        if (!$c['ready'] && time() - $c['since'] > 10) ws_close($id);
    }

    // E-posta senkronizasyonu (ayrı süreçte): Gmail'de yeni e-posta olunca hemen, ayrıca dakikada bir;
    // eski e-postaların aktarımı sürüyorsa bir süreç biter bitmez yenisi başlar.
    if ($mailEvent || time() - $lastSync >= 60 || $backfillPending) {
        $syncWanted = true;
    }
    if ($syncWanted && MailFetcher::isEnabled() && !sync_running()) {
        spawn_sync();
        $syncWanted = false;
        $lastCheck = 0;
    }

    try {
        // Gmail IDLE'ı ~29 dk'da keser: 25 dk'da bir yenile
        if ($imap && time() - $idleSince > 1500) {
            $imap->idleDone();
            $imap->idleStart();
            $idleSince = time();
        }
    } catch (Throwable $e) {
        rt_log('Gmail IDLE hatası: ' . $e->getMessage());
        $imap = null;
        $imapState = 'error';
    }

    if (!$imap && time() - $imapTry > 30) {
        imap_connect();
    }

    // Canlı destek: yeni mesajlar ve "yazıyor" durumu her döngüde (~0,5 sn) iletilir
    if ($clients) {
        try {
            chat_dispatch();
        } catch (Throwable $e) {
            rt_log('Canlı destek hatası: ' . $e->getMessage());
            Database::reset();
        }
    }

    // Veritabanındaki değişiklikleri (site formu, teklif, senkronizasyon, diğer sekmeler) 2 sn'de bir yayınla
    if (time() - $lastCheck >= 2) {
        $lastCheck = time();
        try {
            check_and_broadcast();
            $wasPending = $backfillPending;
            $backfillPending = !MailFetcher::progress()['done'];
            if ($wasPending && !$backfillPending) {
                rt_log('Eski e-postaların aktarımı tamamlandı.');
            }
        } catch (Throwable $e) {
            rt_log('Veritabanı hatası (yeniden bağlanılıyor): ' . $e->getMessage());
            Database::reset();
        }
    }

    // Bağlı ziyaretçileri "çevrimiçi" işaretle (panelde yeşil nokta)
    if (time() - $lastSeen >= 10) {
        $lastSeen = time();
        $convs = array_values(array_unique(array_map(static fn ($c) => $c['conv'], array_filter($clients, static fn ($c) => $c['ready'] && $c['role'] === 'visitor'))));
        if ($convs) {
            try {
                Database::getInstance()->query('UPDATE chat_conversations SET visitor_seen_at = NOW() WHERE id IN (' . implode(',', array_map('intval', $convs)) . ')');
            } catch (Throwable) {
            }
        }
    }

    if (time() - $lastStatus >= 5) {
        $lastStatus = time();
        write_status();
    }
}

foreach (array_keys($clients) as $id) {
    ws_close($id);
}
if ($imap) {
    $imap->logout();
}
sync_running(); // biten alt süreci kapat (çalışan varsa kendi işini bitirir)
fclose($server);
@unlink(Realtime::statusFile());
rt_log('Canlı sunucu durdu.');
