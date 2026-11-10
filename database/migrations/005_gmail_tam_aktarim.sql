-- ------------------------------------------------------------
-- Gmail klasörlerinin TAMAMININ aktarılması (yalnızca son 30 gün değil)
-- Eski e-postalar en yeniden en eskiye doğru arka planda tamamlanır.
-- ------------------------------------------------------------
ALTER TABLE `mail_folders`
    ADD COLUMN `oldest_uid` INT UNSIGNED NULL COMMENT 'Bu UID ve sonrası aktarıldı (geriye doğru doldurma imleci)' AFTER `last_uid`,
    ADD COLUMN `backfill_done` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Eski e-postaların tamamı aktarıldı mı' AFTER `oldest_uid`;
