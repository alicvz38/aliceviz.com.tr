-- ------------------------------------------------------------
-- Proje türü: Web / Uygulama (panelden seçilir; GitHub'dan aktarılanlar türsüz gelir)
-- ------------------------------------------------------------
ALTER TABLE `projects`
    ADD COLUMN `kind` VARCHAR(10) NULL COMMENT 'web | app (NULL: belirtilmemiş)' AFTER `language`,
    ADD KEY `projects_kind_idx` (`kind`);
