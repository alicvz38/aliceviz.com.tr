-- Takvim: kayıt kimliği (aynı geçmiş kaydı / GitHub commit'i iki kez eklenmesin) + veritabanından geçmiş
ALTER TABLE `activity_log`
    ADD COLUMN `ref` VARCHAR(80) NULL AFTER `path`,
    ADD UNIQUE KEY `activity_log_ref_uq` (`ref`);

-- Projeler: eklendikten sonra güncellenenler (son güncelleme zamanı)
INSERT IGNORE INTO `activity_log` (`category`, `title`, `url`, `path`, `ref`, `created_at`)
SELECT 'proje', CONCAT('Proje güncellendi: ', `title`), CONCAT('projeler/', `id`, '/duzenle'), 'gecmis',
       CONCAT('gecmis:proje-guncel:', `id`, ':', UNIX_TIMESTAMP(`updated_at`)), `updated_at`
FROM `projects`
WHERE `updated_at` IS NOT NULL AND `updated_at` > `created_at` + INTERVAL 2 MINUTE;

-- Özgeçmiş kayıtları (deneyim, eğitim, yetenek, dil, sertifika)
INSERT IGNORE INTO `activity_log` (`category`, `title`, `detail`, `url`, `path`, `ref`, `created_at`)
SELECT 'icerik', CONCAT('Özgeçmişe eklendi: ', `title`),
       CASE `type` WHEN 'experience' THEN 'İş deneyimi' WHEN 'education' THEN 'Eğitim' WHEN 'skill' THEN 'Yetenek'
                   WHEN 'language' THEN 'Yabancı dil' WHEN 'certificate' THEN 'Sertifika' ELSE `type` END,
       'ozgecmis', 'gecmis', CONCAT('gecmis:ozgecmis:', `id`), `created_at`
FROM `resume_items`;

-- Teklif talepleri: gelen talep ve sonradan durum güncellemesi
INSERT IGNORE INTO `activity_log` (`category`, `title`, `detail`, `url`, `path`, `ref`, `created_at`)
SELECT 'musteri', CONCAT('Yeni teklif talebi: ', `name`), CONCAT('Hizmet: ', `service`), CONCAT('teklifler/', `id`), 'gecmis',
       CONCAT('gecmis:teklif:', `id`), `created_at`
FROM `quote_requests`;

INSERT IGNORE INTO `activity_log` (`category`, `title`, `detail`, `url`, `path`, `ref`, `created_at`)
SELECT 'musteri', CONCAT('Teklif talebi güncellendi: ', `name`), CONCAT('Durum: ', `status`), CONCAT('teklifler/', `id`), 'gecmis',
       CONCAT('gecmis:teklif-guncel:', `id`, ':', UNIX_TIMESTAMP(`updated_at`)), `updated_at`
FROM `quote_requests`
WHERE `updated_at` IS NOT NULL AND `updated_at` > `created_at` + INTERVAL 2 MINUTE;

-- Canlı destek: kapatılan ve değerlendirilen sohbetler
INSERT IGNORE INTO `activity_log` (`category`, `title`, `url`, `path`, `ref`, `created_at`)
SELECT 'musteri', CONCAT('Canlı destek sohbeti kapatıldı: ', IFNULL(NULLIF(`name`, ''), CONCAT('Ziyaretçi #', `id`))),
       'canli-destek', 'gecmis', CONCAT('gecmis:sohbet-kapat:', `id`), `closed_at`
FROM `chat_conversations` WHERE `closed_at` IS NOT NULL;

INSERT IGNORE INTO `activity_log` (`category`, `title`, `detail`, `url`, `path`, `ref`, `created_at`)
SELECT 'musteri', CONCAT('Sohbet değerlendirildi: ', IFNULL(NULLIF(`name`, ''), CONCAT('Ziyaretçi #', `id`))),
       CONCAT(`rating`, '/5', IF(`rating_comment` IS NULL OR `rating_comment` = '', '', CONCAT(' · ', LEFT(`rating_comment`, 200)))),
       'canli-destek', 'gecmis', CONCAT('gecmis:sohbet-puan:', `id`), `rated_at`
FROM `chat_conversations` WHERE `rated_at` IS NOT NULL;

-- Yanıtlanan e-postalar
INSERT IGNORE INTO `activity_log` (`category`, `title`, `detail`, `url`, `path`, `ref`, `created_at`)
SELECT 'eposta', CONCAT('E-postaya yanıt verildi: ', IF(`subject` = '' OR `subject` IS NULL, '(konusuz)', `subject`)),
       CONCAT('Kimden: ', `email`), CONCAT('mesajlar/', `id`), 'gecmis', CONCAT('gecmis:yanit:', `id`), `replied_at`
FROM `messages` WHERE `replied_at` IS NOT NULL;

-- Son panel girişi
INSERT IGNORE INTO `activity_log` (`user_id`, `category`, `title`, `url`, `path`, `ref`, `created_at`)
SELECT `id`, 'oturum', 'Panele giriş yapıldı', '', 'gecmis', CONCAT('gecmis:giris:', `id`, ':', UNIX_TIMESTAMP(`last_login_at`)), `last_login_at`
FROM `users` WHERE `last_login_at` IS NOT NULL;
