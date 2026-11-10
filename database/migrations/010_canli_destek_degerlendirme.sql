-- ------------------------------------------------------------
-- Canlı destek: sohbet sonu değerlendirmesi (1-5 yıldız + yorum)
-- ------------------------------------------------------------
ALTER TABLE `chat_conversations`
    ADD COLUMN `rating` TINYINT UNSIGNED NULL COMMENT '1-5 yıldız' AFTER `status`,
    ADD COLUMN `rating_comment` VARCHAR(500) NULL AFTER `rating`,
    ADD COLUMN `rated_at` DATETIME NULL AFTER `rating_comment`;
