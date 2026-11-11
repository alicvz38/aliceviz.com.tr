-- Telefon onayı (ücretsiz, WhatsApp ile): ziyaretçi onay kodunu kendi WhatsApp'ından gönderir,
-- panelde kod görülünce "Doğrulandı" işaretlenir
ALTER TABLE `messages`
    ADD COLUMN `phone_code` VARCHAR(8) NULL DEFAULT NULL AFTER `phone`,
    ADD COLUMN `phone_verified_at` DATETIME NULL DEFAULT NULL AFTER `phone_code`;

ALTER TABLE `quote_requests`
    ADD COLUMN `phone_code` VARCHAR(8) NULL DEFAULT NULL AFTER `phone`,
    ADD COLUMN `phone_verified_at` DATETIME NULL DEFAULT NULL AFTER `phone_code`;
