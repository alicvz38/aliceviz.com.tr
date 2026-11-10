-- ------------------------------------------------------------
-- İletişim formuna telefon alanı
-- ------------------------------------------------------------
ALTER TABLE `messages`
    ADD COLUMN `phone` VARCHAR(30) NULL COMMENT 'İletişim formundaki telefon (0 (5XX) XXX XX XX)' AFTER `email`;
