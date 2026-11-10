-- ------------------------------------------------------------
-- Canlı destek (site sağ alttaki sohbet + Panel > Canlı Destek)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_conversations` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token_hash`        CHAR(64)     NOT NULL COMMENT 'Ziyaretçi çerezindeki anahtarın SHA-256 özeti',
    `name`              VARCHAR(100) NOT NULL,
    `email`             VARCHAR(150) NULL,
    `status`            VARCHAR(10)  NOT NULL DEFAULT 'open' COMMENT 'open | closed',
    `page_url`          VARCHAR(255) NULL COMMENT 'Sohbeti başlattığı sayfa',
    `ip`                VARCHAR(45)  NULL,
    `user_agent`        VARCHAR(255) NULL,
    `unread_admin`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Yöneticinin okumadığı mesaj sayısı',
    `unread_visitor`    INT UNSIGNED NOT NULL DEFAULT 0,
    `admin_typing_at`   DATETIME NULL,
    `visitor_typing_at` DATETIME NULL,
    `visitor_seen_at`   DATETIME NULL COMMENT 'Ziyaretçinin son görülmesi (çevrimiçi göstergesi)',
    `last_message_at`   DATETIME NULL,
    `closed_at`         DATETIME NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `chat_conversations_token_unique` (`token_hash`),
    KEY `chat_conversations_status_idx` (`status`, `last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` INT UNSIGNED NOT NULL,
    `sender`          VARCHAR(10)  NOT NULL COMMENT 'visitor | admin | system',
    `body`            TEXT         NOT NULL,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `chat_messages_conv_idx` (`conversation_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('chat_enabled', '1'),
('chat_welcome', 'Merhaba! 👋 Size nasıl yardımcı olabilirim? Web sitesi veya masaüstü uygulama projeniz hakkında sorabilirsiniz.'),
('chat_offline', 'Şu an çevrimdışıyım. Mesajınızı bırakın, en kısa sürede e-posta ile dönüş yapacağım.'),
('chat_admin_seen', '0');
