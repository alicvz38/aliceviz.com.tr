-- Ziyaret istatistikleri (günlük, tekil): IP adresi saklanmaz, yalnızca günlük değişen tek yönlü özet tutulur
-- kind: site (günlük tekil ziyaretçi), page (sayfa yolu), project (proje kimliği)
CREATE TABLE IF NOT EXISTS `stat_daily` (
    `day`   DATE NOT NULL,
    `kind`  VARCHAR(20) NOT NULL,
    `ref`   VARCHAR(190) NOT NULL DEFAULT '',
    `views` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`day`, `kind`, `ref`),
    KEY `kind_ref` (`kind`, `ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aynı gün aynı ziyaretçinin tekrar sayılmaması için (yalnızca bugünün kayıtları tutulur, eskiler silinir)
CREATE TABLE IF NOT EXISTS `stat_seen` (
    `day`     DATE NOT NULL,
    `kind`    VARCHAR(20) NOT NULL,
    `ref`     VARCHAR(190) NOT NULL DEFAULT '',
    `visitor` CHAR(32) NOT NULL,
    PRIMARY KEY (`day`, `kind`, `ref`, `visitor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
