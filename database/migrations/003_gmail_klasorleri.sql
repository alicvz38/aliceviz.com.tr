-- ------------------------------------------------------------
-- Gmail'in tüm klasörlerinin (Gelen, Gönderilen, Taslak, Spam, Çöp, etiketler) aynalanması
-- ------------------------------------------------------------

ALTER TABLE `messages`
    MODIFY `folder` VARCHAR(120) NOT NULL DEFAULT 'inbox' COMMENT 'inbox | sent | drafts | spam | trash | important | l:EtiketAdı',
    ADD COLUMN `imap_uid` INT UNSIGNED NULL COMMENT 'Gmail klasöründeki UID (senkronizasyon için)' AFTER `message_id`,
    ADD KEY `messages_folder_uid_idx` (`folder`(60), `imap_uid`);

-- Gmail klasörleri ve her birinin senkronizasyon durumu
CREATE TABLE IF NOT EXISTS `mail_folders` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `folder_key`  VARCHAR(120) NOT NULL COMMENT 'Paneldeki klasör anahtarı',
    `imap_name`   VARCHAR(255) NOT NULL COMMENT 'Gmail IMAP adı (ör. [Gmail]/Spam)',
    `label`       VARCHAR(150) NOT NULL,
    `special`     VARCHAR(20)  NULL COMMENT 'inbox | sent | drafts | spam | trash | important | NULL (etiket)',
    `sort_order`  INT NOT NULL DEFAULT 100,
    `uidvalidity` BIGINT UNSIGNED NULL,
    `last_uid`    INT UNSIGNED NOT NULL DEFAULT 0,
    `total`       INT UNSIGNED NOT NULL DEFAULT 0,
    `synced_at`   DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `mail_folders_key_unique` (`folder_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Artık Gmail birebir aynalanıyor: varsayılan olarak tüm e-postalar
UPDATE `settings` SET `value` = 'all' WHERE `key` = 'imap_filter';
