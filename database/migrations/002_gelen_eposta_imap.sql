-- ------------------------------------------------------------
-- Gelen e-postaların (Gmail / IMAP) panele aktarılması
-- ------------------------------------------------------------

ALTER TABLE `messages`
    ADD COLUMN `message_id` VARCHAR(255) NULL COMMENT 'E-postanın Message-ID başlığı (tekrarı önler)' AFTER `ip`,
    ADD COLUMN `source` VARCHAR(10) NOT NULL DEFAULT 'site' COMMENT 'site (iletişim formu) | email (Gmail) | panel' AFTER `message_id`,
    ADD KEY `messages_message_id_idx` (`message_id`(191));

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('imap_enabled', '1'),
('imap_host', 'imap.gmail.com'),
('imap_port', '993'),
('imap_filter', 'contacts'),
('imap_last_uid', '0'),
('imap_uidvalidity', ''),
('imap_last_sync', '0'),
('imap_last_error', '');
