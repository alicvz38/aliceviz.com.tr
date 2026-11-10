<?php
/**
 * Uygulama ayarları — ÖRNEK DOSYA
 * ---------------------------------------------------------------
 * Bu dosyayı "config.php" adıyla kopyalayıp kendi bilgilerinizi girin:
 *   copy config\config.example.php config\config.php      (Windows)
 *   cp config/config.example.php config/config.php        (Linux / macOS)
 *
 * config.php GitHub'a yüklenmez (.gitignore); veritabanı şifreniz gizli kalır.
 * config.php yoksa uygulama bu örnek ayarlarla (XAMPP varsayılanları) çalışır.
 */
return [
    'app' => [
        'name'     => 'Web Sitem',
        'debug'    => true,              // Canlıya alırken false yapın
        'timezone' => 'Europe/Istanbul',
        'locale'   => 'tr',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'websitem',
        'user'    => 'root',
        'pass'    => '',                 // Canlı sunucuda veritabanı şifreniz
        'charset' => 'utf8mb4',
    ],

    'theme' => [
        'site'  => 'default',   // themes/default
        'admin' => 'admin',     // themes/admin  (Equation - vertical-dark-menu)
    ],

    'admin' => [
        // Panel adresi: siteadresi/<prefix>. Canlıda "admin" yerine tahmin edilemeyen bir adres kullanın
        // (ör. "yonetim-k3x9p2"); bilinen adresleri botlar sürekli dener.
        'prefix' => 'admin',
    ],

    'github' => [
        'cache_ttl'  => 3600,   // GitHub API yanıtları kaç saniye önbellekte tutulsun
        'user_agent' => 'websitem-portfolio',
    ],

    // Canlı bildirim (WebSocket) sunucusu: realtime/server.php
    // Yerelde 127.0.0.1 yeterli. VPS'te '0.0.0.0' yapıp portu açın veya Nginx/Apache ile wss:// olarak yönlendirin.
    'realtime' => [
        'host' => '127.0.0.1',
        'port' => 8090,
    ],

    'upload' => [
        'dir'      => 'uploads/projects',
        'max_size' => 2 * 1024 * 1024, // 2 MB
        'allowed'  => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'],
    ],
];
