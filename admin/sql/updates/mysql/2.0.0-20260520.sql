--
-- Phase H.1: identity-binding user_id transform on #__cwmconnect_details.
--
-- Existing schema:
--   `user_id` INT(11) NOT NULL DEFAULT '0'
-- New schema:
--   `user_id` INT UNSIGNED NULL  + UNIQUE INDEX uniq_user_id
--
-- The legacy fleet stores 0 for "unpaired" members. Adding UNIQUE on a column
-- where every legacy row reads 0 would collide instantly, so the legacy 0s
-- get coalesced to NULL before the MODIFY + ADD UNIQUE. NULL values are not
-- considered equal under a UNIQUE constraint in MySQL/MariaDB, so multiple
-- unpaired rows remain valid after the transform.
--
-- Scope is intentionally only `#__cwmconnect_details`. The other tables
-- (dirheader / familyunit / kml / position) keep `user_id` as an owner /
-- audit column — not an identity-binding FK — so their shape is unchanged.
--

SET SQL_MODE = '';

UPDATE `#__cwmconnect_details` SET `user_id` = NULL WHERE `user_id` = 0;

ALTER TABLE `#__cwmconnect_details`
    MODIFY COLUMN `user_id` INT UNSIGNED NULL,
    ADD UNIQUE INDEX `uniq_user_id` (`user_id`);
