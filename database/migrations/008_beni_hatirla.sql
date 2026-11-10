-- ------------------------------------------------------------
-- "Beni hatırla" (kalıcı giriş çerezi)
-- Çerezde "seçici:doğrulayıcı" bulunur; veritabanında doğrulayıcının yalnızca SHA-256 özeti saklanır.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `selector`     CHAR(24)     NOT NULL COMMENT 'Çerezdeki açık kimlik (aramak için)',
    `token_hash`   CHAR(64)     NOT NULL COMMENT 'Doğrulayıcının SHA-256 özeti',
    `prev_hash`    CHAR(64)     NULL     COMMENT 'Bir önceki özet (aynı anda açılan sekmeler için kısa tolerans)',
    `rotated_at`   DATETIME     NULL,
    `user_agent`   VARCHAR(255) NULL,
    `ip`           VARCHAR(45)  NULL,
    `expires_at`   DATETIME     NOT NULL,
    `last_used_at` DATETIME     NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `remember_tokens_selector_unique` (`selector`),
    KEY `remember_tokens_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
