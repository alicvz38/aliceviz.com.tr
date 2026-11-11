-- Telefon onayı (017) kaldırıldı: alanlar silinir
ALTER TABLE `messages`
    DROP COLUMN `phone_code`,
    DROP COLUMN `phone_verified_at`;

ALTER TABLE `quote_requests`
    DROP COLUMN `phone_code`,
    DROP COLUMN `phone_verified_at`;
