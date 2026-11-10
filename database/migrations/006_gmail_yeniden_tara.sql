-- ------------------------------------------------------------
-- Uzun IMAP yanıtları kesildiği için eksik kalan e-postaları tamamlamak üzere
-- tüm klasörleri baştan tara. Zaten aktarılmış e-postalar yeniden indirilmez.
-- ------------------------------------------------------------
UPDATE `mail_folders` SET `last_uid` = 0, `oldest_uid` = NULL, `backfill_done` = 0;
