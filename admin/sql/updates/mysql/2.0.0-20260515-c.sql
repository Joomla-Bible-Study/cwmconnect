--
-- Phase C: PC sync companion columns on familyunit + dirheader.
--
-- Phase A landed the additive columns on `#__cwmconnect_details`. This
-- file extends that with the household / campus mirror columns the sync
-- engine needs as join targets when it walks `?include=households,
-- primary_campus` payloads. Suffix `-c` keeps the filename lexicographically
-- after the May-15 Phase A migration so Joomla's schema tracker runs it
-- second.
--

SET SQL_MODE = '';

ALTER TABLE `#__cwmconnect_familyunit`
    ADD COLUMN `pc_household_id`   BIGINT   NULL AFTER `id`,
    ADD COLUMN `pc_last_synced_at` DATETIME NULL,
    ADD UNIQUE INDEX `uniq_pc_household_id` (`pc_household_id`);

ALTER TABLE `#__cwmconnect_dirheader`
    ADD COLUMN `pc_campus_id`      BIGINT   NULL AFTER `id`,
    ADD COLUMN `pc_last_synced_at` DATETIME NULL,
    ADD UNIQUE INDEX `uniq_pc_campus_id` (`pc_campus_id`);
