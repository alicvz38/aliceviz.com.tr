<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Message;
use App\Models\Setting;
use RuntimeException;

/**
 * Gmail'in (IMAP) TÜM klasörlerini panele aynalar:
 *   Gelen Kutusu, Önemli, Gönderilenler, Taslaklar, Spam, Çöp Kutusu ve kendi etiketleriniz.
 *
 *  - Yeni e-postalar eklenir (okundu / yıldızlı durumu Gmail'deki gibi gelir)
 *  - Gmail'de silinen / başka klasöre taşınan e-postalar panelde de kalkar
 *  - Panelde silinen e-posta Gmail'de Çöp Kutusu'na taşınır (bkz. deleteRemote)
 *
 * Otomatik çalışır: cron/mail-sync.php her dakika (Windows Görev Zamanlayıcı / hosting cron)
 * ve yedek olarak panel açıkken bildirim kontrolünde.
 */
class MailFetcher
{
    /** Gmail özel klasörü => [panel anahtarı, Türkçe ad, sıra] */
    private const SPECIAL = [
        'inbox'      => ['inbox', 'Gelen Kutusu', 0],
        '\\important' => ['important', 'Önemli', 1],
        '\\sent'     => ['sent', 'Gönderilenler', 2],
        '\\drafts'   => ['drafts', 'Taslaklar', 3],
        '\\junk'     => ['spam', 'Spam', 4],
        '\\trash'    => ['trash', 'Çöp Kutusu', 5],
        '\\all'      => ['all', 'Tüm Postalar', 6],
    ];

    /** Aynalanmayan klasörler: Yıldızlı (bayrak olarak alınır, panelde ayrı görünüm var) */
    private const SKIP_ATTRS = ['\\noselect', '\\flagged'];

    private static $lock = null;

    public static function isEnabled(): bool
    {
        return Setting::get('imap_enabled', '0') === '1'
            && Setting::get('imap_host', '') !== ''
            && Setting::get('smtp_user', '') !== ''
            && Setting::get('smtp_pass', '') !== '';
    }

    /** Son senkronizasyondan bu yana $minSeconds geçtiyse çalıştırır (panel açıkken yedek) */
    public static function syncIfDue(int $minSeconds = 60, int $seconds = 15): ?int
    {
        if (!self::isEnabled() || time() - (int) Setting::get('imap_last_sync', '0') < $minSeconds) {
            return null;
        }
        try {
            return (new self())->sync($seconds);
        } catch (\Throwable $e) {
            Setting::set('imap_last_error', mb_substr($e->getMessage(), 0, 300));
            Setting::set('imap_last_sync', (string) time());
            return null;
        }
    }

    /**
     * Yalnızca Gelen Kutusu'ndaki yeni e-postaları hızlıca alır (anlık bildirim sonrası).
     * Klasörler henüz keşfedilmemişse tam senkronizasyona döner.
     * @return int Eklenen e-posta sayısı
     */
    public function syncInbox(int $seconds = 15): int
    {
        $folder = Database::getInstance()->fetch("SELECT * FROM mail_folders WHERE folder_key = 'inbox' LIMIT 1");
        if (!$folder) {
            return $this->sync($seconds);
        }
        if (!self::acquireLock()) {
            return 0; // başka bir senkronizasyon zaten çalışıyor (o da yeni e-postayı alacak)
        }
        $this->deadline = microtime(true) + $seconds;
        try {
            $imap = $this->connect();
            try {
                [$added] = $this->syncNew($imap, $folder, strtolower((string) Setting::get('smtp_user', '')));
            } finally {
                $imap->logout();
            }
            Setting::set('imap_last_sync', (string) time());
            Setting::set('imap_last_error', '');
            return $added;
        } finally {
            self::releaseLock();
        }
    }

    /** Gelen Kutusu'nu dinlemek için bağlantı açar (IMAP IDLE); çağıran kapatır */
    public static function openIdle(): ImapClient
    {
        $imap = (new self())->connect();
        $imap->select('INBOX');
        $imap->idleStart();
        return $imap;
    }

    /** Bu çalıştırmanın bitmesi gereken an (zaman bütçesi) */
    private float $deadline = 0;

    /**
     * Tüm klasörleri senkronize eder:
     *   1) Her klasörde silinenleri kaldırır ve YENİ e-postaların tamamını alır
     *   2) Kalan sürede ESKİ e-postaları en yeniden en eskiye doğru aktarır (tamamı bitene kadar her çalıştırmada devam eder)
     *
     * @param int $seconds Zaman bütçesi (cron: 50 sn, panel: 15 sn)
     * @return int Eklenen e-posta sayısı
     */
    public function sync(int $seconds = 50): int
    {
        if (!self::acquireLock()) {
            return 0; // başka bir senkronizasyon zaten çalışıyor
        }
        $this->deadline = microtime(true) + $seconds;
        try {
            $user = (string) Setting::get('smtp_user', '');
            $pass = (string) Setting::get('smtp_pass', '');
            if (Setting::get('imap_host', '') === '' || $user === '' || $pass === '') {
                throw new RuntimeException('E-posta bağlantısı için IMAP sunucusu, kullanıcı adı ve şifre gerekli (E-posta Ayarları).');
            }

            $imap = $this->connect();
            try {
                $total = 0;
                $folders = $this->discoverFolders($imap);
                $uids = [];
                // 1. tur: yeni e-postalar (öncelikli)
                foreach ($folders as $folder) {
                    [$added, $uids[$folder['folder_key']]] = $this->syncNew($imap, $folder, strtolower($user));
                    $total += $added;
                }
                // 2. tur: eski e-postalar (kalan sürede)
                $db = Database::getInstance();
                foreach ($db->fetchAll('SELECT * FROM mail_folders WHERE backfill_done = 0 ORDER BY sort_order, label') as $folder) {
                    if ($this->timeUp()) {
                        break;
                    }
                    $total += $this->backfill($imap, $folder, $uids[$folder['folder_key']] ?? [], strtolower($user));
                }
            } finally {
                $imap->logout();
            }

            Setting::set('imap_last_sync', (string) time());
            Setting::set('imap_last_error', '');
            return $total;
        } finally {
            self::releaseLock();
        }
    }

    /** Panelde silinen e-postayı Gmail'de de siler (Çöp Kutusu'na taşır; Çöp/Spam'deyse kalıcı siler) */
    public static function deleteRemote(Message $message): void
    {
        self::remoteDelete([$message]);
    }

    /**
     * Birden çok e-postayı Gmail'de siler.
     * @param Message[] $messages
     */
    public static function remoteDelete(array $messages): void
    {
        $db = Database::getInstance();
        $trash = $db->fetch("SELECT imap_name FROM mail_folders WHERE special = 'trash'");
        self::eachRemoteFolder($messages, static function (ImapClient $imap, array $folder, array $uids) use ($trash) {
            if (in_array($folder['special'], ['trash', 'spam'], true) || !$trash) {
                $imap->deleteMany($uids);
            } else {
                $imap->moveMany($uids, $trash['imap_name']);
            }
        });
    }

    /**
     * Okundu (\Seen) / yıldız (\Flagged) değişikliğini Gmail'e yazar.
     * @param Message[] $messages
     */
    public static function remoteFlag(array $messages, string $flag, bool $add): void
    {
        self::eachRemoteFolder($messages, static function (ImapClient $imap, array $folder, array $uids) use ($flag, $add) {
            $imap->store($uids, $flag, $add);
        });
    }

    /** Spam / Çöp Kutusu'ndaki tüm e-postaları Gmail'den kalıcı siler */
    public static function remoteEmptyFolder(string $folderKey): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $folder = Database::getInstance()->fetch('SELECT * FROM mail_folders WHERE folder_key = ?', [$folderKey]);
        if (!$folder || !in_array($folder['special'], ['spam', 'trash'], true)) {
            return;
        }
        $imap = (new self())->connect();
        try {
            $box = $imap->select($folder['imap_name']);
            if ($box['exists'] > 0) {
                $imap->deleteAll();
            }
        } finally {
            $imap->logout();
        }
        Database::getInstance()->query('UPDATE mail_folders SET total = 0 WHERE folder_key = ?', [$folderKey]);
    }

    /** Klasördeki tüm e-postaları Gmail'de okundu yapar */
    public static function remoteMarkFolderRead(string $folderKey): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $folder = Database::getInstance()->fetch('SELECT * FROM mail_folders WHERE folder_key = ?', [$folderKey]);
        if (!$folder) {
            return;
        }
        $imap = (new self())->connect();
        try {
            $box = $imap->select($folder['imap_name']);
            if ($box['exists'] > 0) {
                $imap->storeAll('\\Seen');
            }
        } finally {
            $imap->logout();
        }
    }

    /** Gmail'e bağlı e-postaları klasörlerine göre gruplayıp tek bağlantıda işler */
    private static function eachRemoteFolder(array $messages, callable $fn): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $groups = [];
        foreach ($messages as $m) {
            if ($m->imap_uid) {
                $groups[(string) $m->folder][] = (int) $m->imap_uid;
            }
        }
        if (!$groups) {
            return;
        }
        $db = Database::getInstance();
        $imap = (new self())->connect();
        try {
            foreach ($groups as $key => $uids) {
                $folder = $db->fetch('SELECT * FROM mail_folders WHERE folder_key = ?', [$key]);
                if ($folder) {
                    $imap->select($folder['imap_name']);
                    foreach (array_chunk($uids, 500) as $chunk) {
                        $fn($imap, $folder, $chunk);
                    }
                }
            }
        } finally {
            $imap->logout();
        }
    }

    /** @return array<int, array{key: string, label: string, special: ?string, sort: int}> Paneldeki klasör listesi */
    public static function folders(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $rows = Database::getInstance()->fetchAll('SELECT folder_key, label, special, sort_order FROM mail_folders ORDER BY sort_order, label');
        } catch (\Throwable) {
            $rows = [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[$r['folder_key']] = ['key' => $r['folder_key'], 'label' => $r['label'], 'special' => $r['special'], 'sort' => (int) $r['sort_order']];
        }
        // Gmail bağlı değilken de temel klasörler görünsün
        foreach (['inbox' => ['Gelen Kutusu', 0], 'sent' => ['Gönderilenler', 2]] as $key => [$label, $sort]) {
            $out[$key] ??= ['key' => $key, 'label' => $label, 'special' => $key, 'sort' => $sort];
        }
        uasort($out, static fn ($a, $b) => [$a['sort'], $a['label']] <=> [$b['sort'], $b['label']]);
        return $cache = $out;
    }

    // ------------------------------------------------------------

    private function connect(): ImapClient
    {
        $imap = new ImapClient((string) Setting::get('imap_host', 'imap.gmail.com'), (int) Setting::get('imap_port', '993') ?: 993, 'ssl');
        $imap->connect((string) Setting::get('smtp_user', ''), (string) Setting::get('smtp_pass', ''));
        return $imap;
    }

    /** Gmail'deki klasörleri bulur, mail_folders tablosunu günceller */
    private function discoverFolders(ImapClient $imap): array
    {
        $db = Database::getInstance();
        $found = [];
        foreach ($imap->listFolders() as $f) {
            if (array_intersect($f['attrs'], self::SKIP_ATTRS)) {
                continue;
            }
            $special = null;
            if (strtoupper($f['name']) === 'INBOX') {
                $special = 'inbox';
            } else {
                foreach ($f['attrs'] as $attr) {
                    if (isset(self::SPECIAL[$attr])) {
                        $special = $attr;
                        break;
                    }
                }
            }
            if ($special) {
                [$key, $label, $sort] = self::SPECIAL[$special];
            } else {
                // Kullanıcı etiketi: "[Gmail]/..." dışındaki klasörler
                if (str_starts_with($f['name'], '[Gmail]') || str_starts_with($f['name'], '[Google Mail]')) {
                    continue;
                }
                $key = 'l:' . mb_substr($f['label'], 0, 110);
                $label = $f['label'];
                $sort = 100;
            }
            $found[$key] = ['key' => $key, 'imap_name' => $f['name'], 'label' => $label, 'special' => $special ? $key : null, 'sort' => $sort];
        }

        foreach ($found as $f) {
            $db->query(
                'INSERT INTO mail_folders (folder_key, imap_name, label, special, sort_order) VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE imap_name = VALUES(imap_name), label = VALUES(label), special = VALUES(special), sort_order = VALUES(sort_order)',
                [$f['key'], $f['imap_name'], $f['label'], $f['special'], $f['sort']]
            );
        }
        // Gmail'de silinen etiketler panelden de kalksın
        foreach ($db->fetchAll('SELECT folder_key FROM mail_folders') as $row) {
            if (!isset($found[$row['folder_key']])) {
                $db->delete('messages', 'folder = ? AND imap_uid IS NOT NULL', [$row['folder_key']]);
                $db->delete('mail_folders', 'folder_key = ?', [$row['folder_key']]);
            }
        }
        return $db->fetchAll('SELECT * FROM mail_folders ORDER BY sort_order, label');
    }

    private function timeUp(): bool
    {
        return microtime(true) >= $this->deadline;
    }

    /**
     * 1. tur: silinenleri kaldırır, yeni e-postaların tamamını alır.
     * @return array{0: int, 1: int[]} [eklenen sayısı, klasördeki tüm UID'ler]
     */
    private function syncNew(ImapClient $imap, array $folder, string $self): array
    {
        $db = Database::getInstance();
        $key = $folder['folder_key'];
        $box = $imap->select($folder['imap_name'], true);
        $lastUid = (int) $folder['last_uid'];
        $oldest = $folder['oldest_uid'] !== null ? (int) $folder['oldest_uid'] : null;
        $modseq = $folder['modseq'] ?? null;
        $done = (int) $folder['backfill_done'];

        // Klasör Gmail'de yeniden oluşturulduysa eski UID'ler geçersizdir: baştan aktar
        if ((string) $box['uidvalidity'] !== (string) $folder['uidvalidity']) {
            $db->delete('messages', 'folder = ? AND imap_uid IS NOT NULL', [$key]);
            $lastUid = 0;
            $oldest = null;
            $modseq = null;
        }

        $all = $box['exists'] > 0 ? $imap->search('ALL') : [];

        // Gmail'de (telefon, web vb.) okunan / yıldızlanan e-postaları panele yansıt
        if ($modseq && $box['modseq'] > (int) $modseq) {
            foreach ($imap->changedFlags((int) $modseq) as $uid => $flags) {
                // Gönderdiğiniz / taslak e-postalar her zaman "okunmuş" sayılır (Gmail SMTP kopyasını okunmamış işaretleyebiliyor)
                $db->query(
                    "UPDATE messages SET is_read = IF(folder IN ('sent', 'drafts') OR send_status = 'sent', 1, ?), is_starred = ? WHERE folder = ? AND imap_uid = ?",
                    [in_array('\\seen', $flags, true) ? 1 : 0, in_array('\\flagged', $flags, true) ? 1 : 0, $key, $uid]
                );
            }
        }

        // Gmail'de silinen / taşınan e-postaları panelden kaldır
        $local = array_map('intval', array_column($db->fetchAll('SELECT imap_uid FROM messages WHERE folder = ? AND imap_uid IS NOT NULL', [$key]), 'imap_uid'));
        foreach (array_chunk(array_values(array_diff($local, $all)), 200) as $chunk) {
            $db->query('DELETE FROM messages WHERE folder = ? AND imap_uid IN (' . implode(',', array_fill(0, count($chunk), '?')) . ')', array_merge([$key], $chunk));
        }

        // İlk kez: mevcut e-postaların hepsi "eski" sayılır ve en yeniden başlanarak aktarılır
        if ($oldest === null) {
            if ($lastUid === 0) {
                $lastUid = $all ? max($all) : 0;
                $oldest = $lastUid + 1;
            } else {
                $minLocal = (int) $db->fetchColumn('SELECT COALESCE(MIN(imap_uid), 0) FROM messages WHERE folder = ? AND imap_uid IS NOT NULL', [$key]);
                $oldest = $minLocal ?: $lastUid + 1;
            }
            $done = 0;
        }
        // Aktarılacak eski e-posta kalmadıysa (veya klasör boşsa) tamamlandı say
        if (!$done && !array_filter($all, static fn ($u) => $u < $oldest)) {
            $done = 1;
        }

        // Yeni e-postalar: tamamı, 25'erli gruplar halinde (süre biterse kalanı sonraki çalıştırmada)
        $added = 0;
        $new = array_values(array_filter($all, static fn ($u) => $u > $lastUid));
        foreach (array_chunk($new, self::BATCH) as $chunk) {
            if ($added > 0 && $this->timeUp()) {
                break;
            }
            $added += $this->importBatch($imap, $folder, $chunk, $self);
            $lastUid = max($chunk);
        }

        $db->query(
            'UPDATE mail_folders SET uidvalidity = ?, modseq = ?, last_uid = ?, oldest_uid = ?, backfill_done = ?, total = ?, synced_at = NOW() WHERE folder_key = ?',
            [$box['uidvalidity'], $box['modseq'] ?: null, $lastUid, $oldest, $done, count($all), $key]
        );
        return [$added, $all];
    }

    /** 2. tur: eski e-postaları en yeniden en eskiye doğru, süre yettiğince aktarır */
    private function backfill(ImapClient $imap, array $folder, array $all, string $self): int
    {
        $db = Database::getInstance();
        $oldest = (int) $folder['oldest_uid'];
        $older = array_values(array_filter($all, static fn ($u) => $u < $oldest));
        rsort($older);

        $added = 0;
        if ($older) {
            $imap->select($folder['imap_name']);
            foreach (array_chunk($older, self::BATCH) as $chunk) {
                if ($this->timeUp()) {
                    break;
                }
                $added += $this->importBatch($imap, $folder, $chunk, $self);
                $oldest = min($chunk);
            }
        }

        $done = !$older || $oldest <= min($older) ? 1 : 0;
        $db->query('UPDATE mail_folders SET oldest_uid = ?, backfill_done = ? WHERE folder_key = ?', [$oldest, $done, $folder['folder_key']]);
        return $added;
    }

    /**
     * Yalnızca eski e-postaların aktarımına devam eder (canlı sunucu bunu boşluk bırakmadan art arda çağırır).
     * Tam senkronizasyondan farklı olarak klasörlerin tamamını taramaz; sadece kalan eski UID'leri sorar.
     * @return int|null Eklenen sayısı; aktarılacak bir şey kalmadıysa null
     */
    public function backfillStep(int $seconds = 5): ?int
    {
        $db = Database::getInstance();
        $pending = $db->fetchAll('SELECT * FROM mail_folders WHERE backfill_done = 0 AND oldest_uid IS NOT NULL ORDER BY sort_order, label');
        if (!$pending) {
            return null;
        }
        if (!self::acquireLock()) {
            return 0; // tam senkronizasyon (veya cron) çalışıyor; sonra tekrar dene
        }
        $this->deadline = microtime(true) + $seconds;
        try {
            $self = strtolower((string) Setting::get('smtp_user', ''));
            // Bağlantı adımlar arasında açık tutulur (her adımda yeniden bağlanma beklemesi olmasın)
            $imap = $this->conn ??= $this->connect();
            $added = 0;
            try {
                foreach ($pending as $folder) {
                    if ($this->timeUp()) {
                        break;
                    }
                    $imap->select($folder['imap_name']);
                    $oldest = (int) $folder['oldest_uid'];
                    $older = $oldest > 1 ? $imap->search('UID 1:' . ($oldest - 1)) : [];
                    // "UID 1:n" aralığında e-posta yoksa sunucu bazen en son e-postayı döndürür; filtrele
                    $older = array_values(array_filter($older, static fn ($u) => $u < $oldest));
                    $added += $this->backfill($imap, $folder, $older, $self);
                }
            } catch (\Throwable $e) {
                $this->close(); // bozuk bağlantıyı at, sonraki adımda yeniden bağlan
                throw $e;
            }
            return $added;
        } finally {
            self::releaseLock();
        }
    }

    private ?ImapClient $conn = null;

    /** backfillStep() için açık tutulan bağlantıyı kapatır */
    public function close(): void
    {
        if ($this->conn) {
            try {
                $this->conn->logout();
            } catch (\Throwable) {
            }
            $this->conn = null;
        }
    }

    /** Aktarım ilerlemesi (panelde "x / y e-posta aktarıldı" göstermek için) */
    public static function progress(): array
    {
        try {
            $row = Database::getInstance()->fetch(
                "SELECT COALESCE(SUM(total), 0) AS total, SUM(backfill_done = 0) AS pending FROM mail_folders"
            );
            $local = (int) Database::getInstance()->fetchColumn('SELECT COUNT(*) FROM messages WHERE imap_uid IS NOT NULL');
            return ['total' => (int) $row['total'], 'imported' => $local, 'done' => (int) $row['pending'] === 0];
        } catch (\Throwable) {
            return ['total' => 0, 'imported' => 0, 'done' => true];
        }
    }

    /** Tek istekte indirilen e-posta sayısı */
    private const BATCH = 25;

    /**
     * Bir grup e-postayı tek IMAP isteğiyle indirip aktarır. Zaten aktarılmış olanlar hiç indirilmez.
     * @return int Eklenen sayısı
     */
    private function importBatch(ImapClient $imap, array $folder, array $uids, string $self): int
    {
        if (!$uids) {
            return 0;
        }
        $key = $folder['folder_key'];
        $existing = array_map('intval', array_column(Database::getInstance()->fetchAll(
            'SELECT imap_uid FROM messages WHERE folder = ? AND imap_uid IN (' . implode(',', array_fill(0, count($uids), '?')) . ')',
            array_merge([$key], $uids)
        ), 'imap_uid'));
        $todo = array_values(array_diff($uids, $existing));
        if (!$todo) {
            return 0;
        }
        // Metin bölümü için 600 KB yeterli; büyük ekler indirilmez
        $added = 0;
        foreach ($imap->fetchMany($todo, 600_000) as $uid => $fetched) {
            if ($this->importFetched($folder, (int) $uid, $fetched, $self)) {
                $added++;
            }
        }
        return $added;
    }

    private function importFetched(array $folder, int $uid, array $fetched, string $self): bool
    {
        $key = $folder['folder_key'];
        if ($fetched['raw'] === null || $fetched['raw'] === '') {
            return false;
        }
        $mail = MimeParser::parse($fetched['raw']);
        $flags = array_map('strtolower', $fetched['flags']);
        // Gönderilen / taslak veya (Tüm Postalar'da) sizin gönderdiğiniz e-posta: "email" alanı alıcıdır
        $outgoing = in_array($key, ['sent', 'drafts'], true) || ($key !== 'inbox' && $mail['from_email'] === $self);

        // Gelen Kutusu'ndaki kendi kendinize gönderdiğiniz bildirimler (site formu kopyası) alınmaz
        if ($key === 'inbox' && $mail['from_email'] === $self) {
            return false;
        }
        // "Yalnızca yazıştığım kişiler" seçiliyse Gelen Kutusu filtrelenir
        if ($key === 'inbox' && Setting::get('imap_filter', 'all') === 'contacts' && !in_array($mail['from_email'], $this->contacts(), true)) {
            return false;
        }
        // Aynı e-posta bu klasörde zaten var mı? (panelden gönderilenler dahil)
        if ($mail['message_id'] !== '' && Message::count('folder = ? AND message_id = ?', [$key, $mail['message_id']]) > 0) {
            // Panelden gönderilen / eski sürümde aktarılan kaydı Gmail'deki kopyasına bağla (silme senkronu için)
            Database::getInstance()->query('UPDATE messages SET imap_uid = ? WHERE folder = ? AND message_id = ? AND imap_uid IS NULL', [$uid, $key, $mail['message_id']]);
            return false;
        }

        $email = $outgoing ? $mail['to_email'] : $mail['from_email'];
        $name = $outgoing ? $mail['to_name'] : $mail['from_name'];
        $body = $key === 'drafts' ? $mail['text'] : MimeParser::stripQuoted($mail['text']);
        if ($mail['attachments']) {
            $body .= "\n\n[Ekler: " . implode(', ', array_slice($mail['attachments'], 0, 10)) . ' — posta kutunuzda görüntüleyin]';
        }
        $parent = !$outgoing && $email !== ''
            ? (Message::where("folder = 'sent' AND email = ?", [$email], 'id DESC', 1)[0] ?? null)
            : null;

        Message::create([
            'folder'     => $key,
            'name'       => mb_substr($name, 0, 100) ?: null,
            'email'      => mb_substr($email, 0, 150),
            'subject'    => mb_substr($mail['subject'], 0, 200),
            'body'       => mb_substr($body !== '' ? $body : '(boş e-posta)', 0, 100000),
            'is_read'    => $outgoing || in_array('\\seen', $flags, true) ? 1 : 0,
            'is_starred' => in_array('\\flagged', $flags, true) ? 1 : 0,
            'parent_id'  => $parent?->id,
            'quote_id'   => $parent?->quote_id,
            'message_id' => mb_substr($mail['message_id'], 0, 255) ?: null,
            'imap_uid'   => $uid,
            'source'     => 'email',
            'send_status'=> $outgoing && $key !== 'drafts' ? 'sent' : null,
            'created_at' => $mail['date'] ?? date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    private ?array $contactsCache = null;

    private function contacts(): array
    {
        if ($this->contactsCache !== null) {
            return $this->contactsCache;
        }
        $db = Database::getInstance();
        $emails = array_merge(
            array_column($db->fetchAll("SELECT DISTINCT LOWER(email) AS e FROM messages WHERE source <> 'email' OR folder = 'sent'"), 'e'),
            array_column($db->fetchAll('SELECT DISTINCT LOWER(email) AS e FROM quote_requests'), 'e')
        );
        return $this->contactsCache = array_values(array_unique($emails));
    }

    private static function acquireLock(): bool
    {
        self::$lock = @fopen(STORAGE_PATH . '/mail-sync.lock', 'c');
        if (!self::$lock || !flock(self::$lock, LOCK_EX | LOCK_NB)) {
            return false;
        }
        @set_time_limit(PHP_SAPI === 'cli' ? 0 : 60);
        return true;
    }

    private static function releaseLock(): void
    {
        if (self::$lock) {
            flock(self::$lock, LOCK_UN);
            fclose(self::$lock);
            self::$lock = null;
        }
    }
}
