-- Gönderilen dosyalarda alıcı bilgisi dosyanın kendi kaydında da tutulur
-- (e-posta panelden silinse bile Gönderilen Dosyalar listesinde alıcı görünür)
ALTER TABLE `mail_attachments`
    ADD COLUMN `recipient` VARCHAR(190) NULL DEFAULT NULL AFTER `message_id`,
    ADD COLUMN `recipient_name` VARCHAR(100) NULL DEFAULT NULL AFTER `recipient`,
    ADD COLUMN `subject` VARCHAR(200) NULL DEFAULT NULL AFTER `recipient_name`;

-- Mevcut dosyalar: e-posta kaydı hâlâ duruyorsa bilgiler oradan alınır
UPDATE `mail_attachments` a JOIN `messages` m ON m.id = a.message_id
SET a.recipient = m.email, a.recipient_name = m.name, a.subject = m.subject
WHERE a.recipient IS NULL;
