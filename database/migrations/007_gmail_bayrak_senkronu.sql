-- ------------------------------------------------------------
-- Okundu / yıldız değişikliklerinin Gmail ile iki yönlü senkronu
-- (IMAP CONDSTORE: yalnızca değişen e-postaların bayrakları alınır)
-- ------------------------------------------------------------
ALTER TABLE `mail_folders`
    ADD COLUMN `modseq` BIGINT UNSIGNED NULL COMMENT 'Son görülen HIGHESTMODSEQ' AFTER `uidvalidity`;
