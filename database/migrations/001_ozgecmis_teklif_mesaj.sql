-- ------------------------------------------------------------
-- Özgeçmiş (CV), teklif talepleri ve mesaj (e-posta) sistemi
-- Bu dosya ilk istekte otomatik çalışır (App\Core\Migrator).
-- ------------------------------------------------------------

-- Özgeçmiş kalemleri: deneyim, eğitim, sertifika, yetenek, dil
CREATE TABLE IF NOT EXISTS `resume_items` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`         VARCHAR(20)  NOT NULL COMMENT 'experience | education | certificate | skill | language',
    `title`        VARCHAR(150) NOT NULL COMMENT 'Pozisyon / bölüm / sertifika / yetenek / dil',
    `organization` VARCHAR(150) NULL     COMMENT 'Şirket / okul / kurum / yetenek grubu',
    `location`     VARCHAR(100) NULL,
    `start_date`   VARCHAR(7)   NULL     COMMENT 'YYYY-MM',
    `end_date`     VARCHAR(7)   NULL     COMMENT 'YYYY-MM',
    `is_current`   TINYINT(1)   NOT NULL DEFAULT 0,
    `description`  TEXT         NULL,
    `level`        TINYINT UNSIGNED NULL COMMENT '0-100 (yetenek / dil)',
    `url`          VARCHAR(255) NULL,
    `sort_order`   INT          NOT NULL DEFAULT 0,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `resume_items_type_idx` (`type`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Web sitesi / masaüstü uygulama teklif talepleri
CREATE TABLE IF NOT EXISTS `quote_requests` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `service`     VARCHAR(30)  NOT NULL,
    `package`     VARCHAR(50)  NULL,
    `name`        VARCHAR(100) NOT NULL,
    `email`       VARCHAR(150) NOT NULL,
    `phone`       VARCHAR(30)  NULL,
    `company`     VARCHAR(150) NULL,
    `budget`      VARCHAR(50)  NULL,
    `deadline`    VARCHAR(50)  NULL,
    `message`     TEXT         NOT NULL,
    `status`      VARCHAR(20)  NOT NULL DEFAULT 'new',
    `admin_note`  TEXT         NULL,
    `ip`          VARCHAR(45)  NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `quote_requests_status_idx` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mesajlar: ziyaretçilerden gelenler (inbox) ve panelden gönderilenler (sent)
CREATE TABLE IF NOT EXISTS `messages` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `folder`      VARCHAR(10)  NOT NULL DEFAULT 'inbox' COMMENT 'inbox | sent',
    `name`        VARCHAR(100) NULL     COMMENT 'Gönderen (inbox) / alıcı adı (sent)',
    `email`       VARCHAR(150) NOT NULL COMMENT 'Gönderen (inbox) / alıcı (sent)',
    `subject`     VARCHAR(200) NOT NULL,
    `body`        MEDIUMTEXT   NOT NULL,
    `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
    `is_starred`  TINYINT(1)   NOT NULL DEFAULT 0,
    `replied_at`  DATETIME     NULL,
    `parent_id`   INT UNSIGNED NULL     COMMENT 'Yanıtlanan mesaj',
    `quote_id`    INT UNSIGNED NULL     COMMENT 'İlgili teklif talebi',
    `send_status` VARCHAR(20)  NULL     COMMENT 'sent | failed (yalnızca gönderilenler)',
    `send_error`  VARCHAR(500) NULL,
    `ip`          VARCHAR(45)  NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `messages_folder_idx` (`folder`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('cv_title', 'Full Stack Yazılım Geliştirici'),
('cv_location', 'Türkiye'),
('cv_phone', ''),
('cv_avatar', ''),
('cv_available', '1'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_secure', 'tls'),
('smtp_user', ''),
('smtp_pass', ''),
('mail_from_email', ''),
('mail_from_name', ''),
('mail_signature', ''),
('mail_notify', '1'),
('mail_autoreply', '1');

-- Örnek özgeçmiş içerikleri (panelden düzenleyip silebilirsiniz)
INSERT INTO `resume_items` (`type`, `title`, `organization`, `location`, `start_date`, `end_date`, `is_current`, `description`, `level`, `sort_order`) VALUES
('experience', 'Serbest Yazılım Geliştirici', 'Freelance', 'Uzaktan', '2022-01', NULL, 1, 'Kurumsal web siteleri, yönetim panelleri ve Windows masaüstü uygulamaları geliştiriyorum.', NULL, 0),
('education', 'Bilgisayar Programcılığı', 'Örnek Üniversitesi', 'Türkiye', '2019-09', '2021-06', 0, NULL, NULL, 0),
('skill', 'PHP', 'Backend', NULL, NULL, NULL, 0, NULL, 90, 0),
('skill', 'MySQL', 'Backend', NULL, NULL, NULL, 0, NULL, 80, 1),
('skill', 'JavaScript', 'Frontend', NULL, NULL, NULL, 0, NULL, 80, 2),
('skill', 'HTML / CSS', 'Frontend', NULL, NULL, NULL, 0, NULL, 90, 3),
('skill', 'C# / .NET', 'Masaüstü', NULL, NULL, NULL, 0, NULL, 75, 4),
('language', 'Türkçe', NULL, NULL, NULL, NULL, 0, 'Ana dil', 100, 0),
('language', 'İngilizce', NULL, NULL, NULL, NULL, 0, 'Orta seviye', 60, 1);
