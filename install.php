<?php
/**
 * KURULUM SİHİRBAZI
 * ---------------------------------------------------------------
 * 1. config/config.php içindeki veritabanı bilgilerini kontrol edin.
 * 2. Tarayıcıda siteadresi/install.php adresini açın.
 * 3. Yönetici bilgilerini girin. Veritabanı ve tablolar otomatik oluşturulur.
 * Kurulum bittikten sonra storage/installed.lock dosyası oluşur ve
 * bu sayfa tekrar çalışmaz. Canlı sunucuda bu dosyayı silebilirsiniz.
 */

require __DIR__ . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Csrf;

$lock = STORAGE_PATH . '/installed.lock';
$errors = [];
$done = false;

if (is_file($lock)) {
    $done = true;
}

// Ön kontroller
$checks = [
    'PHP 8.1 veya üzeri'           => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO MySQL eklentisi'          => extension_loaded('pdo_mysql'),
    'fileinfo eklentisi'           => extension_loaded('fileinfo'),
    'cURL eklentisi (önerilir)'    => extension_loaded('curl'),
    'storage/ klasörü yazılabilir' => is_writable(STORAGE_PATH),
    'uploads/ klasörü yazılabilir' => is_writable(ROOT_PATH . '/uploads'),
];

if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        $errors[] = 'Oturum süresi doldu, sayfayı yenileyin.';
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $github = trim((string) ($_POST['github'] ?? ''));

    if ($name === '') $errors[] = 'Ad Soyad zorunludur.';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) $errors[] = 'Kullanıcı adı 3-50 karakter olmalı (harf, rakam, _ . -).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
    if (mb_strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    if ($github !== '' && !preg_match('/^[A-Za-z0-9-]{1,39}$/', $github)) $errors[] = 'GitHub kullanıcı adı geçersiz.';

    if (!$errors) {
        $c = Config::get('db');
        try {
            // Veritabanı yoksa oluştur
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;charset=%s', $c['host'], $c['port'], $c['charset']),
                $c['user'], $c['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $dbName = str_replace('`', '', $c['name']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Şemayı çalıştır
            $sql = (string) file_get_contents(ROOT_PATH . '/database/schema.sql');
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                $pdo->exec($statement);
            }

            // Yönetici
            $stmt = $pdo->prepare('INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), password = VALUES(password)');
            $stmt->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);

            $set = $pdo->prepare('UPDATE settings SET `value` = ? WHERE `key` = ?');
            $set->execute([$email, 'contact_email']);
            if ($github !== '') {
                $set->execute([$github, 'github_username']);
            }

            // Ek tablolar (özgeçmiş, teklifler, mesajlar...)
            (new App\Core\Migrator())->run();

            file_put_contents($lock, 'Kurulum: ' . date('Y-m-d H:i:s'));
            $done = true;
        } catch (PDOException $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage() . ' — config/config.php içindeki bilgileri ve MySQL\'in çalıştığını kontrol edin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #0b0f19; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
        .card { width: 100%; max-width: 520px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.12); border-radius: 20px; padding: 2.2rem; }
        h1 { font-size: 1.6rem; margin-bottom: .4rem; }
        p.lead { color: #94a3b8; margin-bottom: 1.5rem; font-size: .95rem; }
        ul.checks { list-style: none; margin-bottom: 1.5rem; font-size: .9rem; }
        ul.checks li { padding: .35rem 0; display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(255,255,255,.08); }
        .ok { color: #34d399; } .no { color: #f43f5e; }
        label { display: block; font-size: .85rem; color: #cbd5e1; margin: .9rem 0 .35rem; }
        input { width: 100%; padding: .75rem .9rem; background: #111827; color: #f8fafc; border: 1px solid rgba(255,255,255,.12); border-radius: 10px; font-size: .95rem; }
        input:focus { outline: none; border-color: #38bdf8; }
        button, .btn { display: inline-block; margin-top: 1.4rem; width: 100%; padding: .85rem; border: 0; border-radius: 10px; background: linear-gradient(90deg,#38bdf8,#a855f7); color: #0b0f19; font-weight: 700; font-size: 1rem; cursor: pointer; text-align: center; text-decoration: none; }
        .errors { background: rgba(244,63,94,.12); border: 1px solid rgba(244,63,94,.4); color: #fecdd3; padding: .9rem 1rem; border-radius: 10px; font-size: .9rem; margin-bottom: 1rem; }
        .errors li { margin-left: 1rem; }
        .info { font-size: .82rem; color: #94a3b8; margin-top: 1rem; }
        code { background: #111827; padding: .1rem .35rem; border-radius: 5px; color: #38bdf8; }
    </style>
</head>
<body>
<div class="card">
<?php if ($done): ?>
    <h1>✅ Kurulum tamamlandı</h1>
    <p class="lead">Site varsayılan olarak <b>bakım modunda</b> başlar. Panelden projelerinizi ekleyip bakım modunu kapatabilirsiniz.</p>
    <a class="btn" href="<?= e(admin_url('giris')) ?>">Yönetim Paneline Git</a>
    <p class="info">Güvenlik için canlı sunucuda <code>install.php</code> dosyasını silebilirsiniz.</p>
<?php else: ?>
    <h1>Kurulum Sihirbazı</h1>
    <p class="lead">Veritabanı: <code><?= e(Config::get('db.name')) ?></code> @ <code><?= e(Config::get('db.host')) ?></code> — <small>config/config.php</small></p>

    <ul class="checks">
        <?php foreach ($checks as $label => $ok): ?>
            <li><span><?= e($label) ?></span><span class="<?= $ok ? 'ok' : 'no' ?>"><?= $ok ? '✔' : '✖' ?></span></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($errors): ?>
        <div class="errors"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <label>Ad Soyad</label>
        <input name="name" value="<?= e($_POST['name'] ?? 'Ali Ceviz') ?>" required>
        <label>Kullanıcı Adı</label>
        <input name="username" value="<?= e($_POST['username'] ?? 'admin') ?>" required>
        <label>E-posta</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        <label>Şifre (en az 8 karakter)</label>
        <input type="password" name="password" required minlength="8">
        <label>GitHub Kullanıcı Adı (isteğe bağlı)</label>
        <input name="github" value="<?= e($_POST['github'] ?? '') ?>" placeholder="ornek: aliceviz">
        <button type="submit">Kurulumu Başlat</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
