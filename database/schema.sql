-- ------------------------------------------------------------
-- Web Sitem - Veritabanı şeması
-- install.php bu dosyayı otomatik çalıştırır.
-- Elle kurmak isterseniz phpMyAdmin > İçe Aktar ile yükleyebilirsiniz
-- (bu durumda yönetici kullanıcıyı install.php ile oluşturun).
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100) NOT NULL,
    `username`      VARCHAR(50)  NOT NULL,
    `email`         VARCHAR(150) NOT NULL,
    `password`      VARCHAR(255) NOT NULL,
    `last_login_at` DATETIME NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_username_unique` (`username`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(150) NOT NULL,
    `slug`         VARCHAR(170) NOT NULL,
    `summary`      VARCHAR(500) NULL,
    `content`      MEDIUMTEXT NULL,
    `github_repo`  VARCHAR(150) NULL COMMENT 'kullanici/depo',
    `github_url`   VARCHAR(255) NULL,
    `demo_url`     VARCHAR(255) NULL,
    `language`     VARCHAR(50) NULL,
    `tags`         VARCHAR(255) NULL,
    `stars`        INT UNSIGNED NOT NULL DEFAULT 0,
    `forks`        INT UNSIGNED NOT NULL DEFAULT 0,
    `image`        VARCHAR(255) NULL,
    `is_featured`  TINYINT(1) NOT NULL DEFAULT 0,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `show_readme`  TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`   INT NOT NULL DEFAULT 0,
    `pushed_at`    DATETIME NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `projects_slug_unique` (`slug`),
    UNIQUE KEY `projects_github_repo_unique` (`github_repo`),
    KEY `projects_published_idx` (`is_published`, `is_featured`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `key`   VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('site_title', 'Ali Ceviz'),
('site_domain', 'aliceviz.com.tr'),
('site_description', 'GitHub üzerinde geliştirdiğim açık kaynak projelerim ve çalışmalarım.'),
('about_text', 'Merhaba! Yazılım geliştirmeyi seven biriyim. Burada GitHub üzerinde yayınladığım projeleri bulabilirsiniz.'),
('contact_email', ''),
('github_username', ''),
('github_token', ''),
('social_instagram', ''),
('social_facebook', ''),
('social_x', ''),
('social_youtube', ''),
('social_linkedin', ''),
('site_theme', 'default'),
('maintenance_mode', '1'),
('maintenance_message', 'Sitemiz şu anda bakımda olup en kısa zamanda hizmete açılacaktır.');
