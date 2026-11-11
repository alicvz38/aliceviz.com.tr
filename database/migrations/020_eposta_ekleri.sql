-- Panelden gönderilen e-postaların dosya ekleri
-- Küçük dosyalar e-postaya ek olarak gider; büyükler (256 MB'a kadar) sunucuda saklanır, e-postada indirme bağlantısı olur
CREATE TABLE IF NOT EXISTS `mail_attachments` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token`       CHAR(32) NOT NULL,
    `message_id`  INT UNSIGNED NULL DEFAULT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `path`        VARCHAR(255) NOT NULL,
    `size`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `mime`        VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
    `as_link`     TINYINT(1) NOT NULL DEFAULT 0,
    `downloads`   INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`  DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
