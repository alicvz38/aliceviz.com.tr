-- Panel etkinlik günlüğü (Takvim sayfası). Paneldeki her başarılı işlem otomatik olarak buraya yazılır.
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NULL,
    `category`   VARCHAR(20)  NOT NULL,              -- proje, icerik, musteri, eposta, ayar, oturum
    `title`      VARCHAR(255) NOT NULL,
    `detail`     VARCHAR(500) NULL,
    `url`        VARCHAR(255) NULL,                  -- panel önekinden sonraki yol (ör. projeler/5/duzenle)
    `path`       VARCHAR(255) NULL,                  -- işlemin yapıldığı panel yolu
    `ip`         VARCHAR(45)  NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `activity_log_created_idx` (`created_at`),
    KEY `activity_log_category_idx` (`category`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Geçmiş: veritabanındaki mevcut kayıtlar kendi tarihleriyle takvime eklenir
INSERT INTO `activity_log` (`category`, `title`, `detail`, `url`, `path`, `created_at`)
SELECT 'proje', CONCAT('Proje eklendi: ', `title`),
       IF(`github_repo` IS NULL OR `github_repo` = '', NULL, CONCAT('GitHub: ', `github_repo`)),
       CONCAT('projeler/', `id`, '/duzenle'), 'gecmis', `created_at`
FROM `projects`;

INSERT INTO `activity_log` (`category`, `title`, `detail`, `url`, `path`, `created_at`)
SELECT 'eposta', CONCAT('E-posta gönderildi: ', IF(`subject` = '' OR `subject` IS NULL, '(konusuz)', `subject`)),
       CONCAT('Kime: ', `email`), CONCAT('mesajlar/', `id`), 'gecmis', `created_at`
FROM `messages`
WHERE `folder` = 'sent' AND `source` = 'panel';
