-- ------------------------------------------------------------
-- Gönderilen / taslak e-postalar okunmamış görünmesin
-- (Gmail, SMTP ile gönderilenlerin kopyasını okunmamış işaretleyebiliyor)
-- ------------------------------------------------------------
UPDATE `messages` SET `is_read` = 1 WHERE `is_read` = 0 AND (`folder` IN ('sent', 'drafts') OR `send_status` = 'sent');
