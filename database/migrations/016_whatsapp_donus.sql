-- Teklif formu açıklaması: dönüş WhatsApp ve e-posta ile yapılır
-- (yalnızca panelde değiştirilmemiş hazır metin güncellenir)
UPDATE `settings`
SET `value` = REPLACE(`value`, '24 saat içinde e-posta adresinize dönüş yaparım.', '24 saat içinde WhatsApp ve e-posta üzerinden dönüş yaparım.')
WHERE `key` = 'service_catalog';
