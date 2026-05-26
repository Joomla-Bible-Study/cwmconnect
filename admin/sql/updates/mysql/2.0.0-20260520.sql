--
-- Phase H.1: identity-binding user_id transform on #__cwmconnect_details.
--
-- Coalesce legacy user_id=0 to NULL, then modify column to nullable
-- unsigned with a UNIQUE index. NULLs are not considered equal under
-- UNIQUE in MySQL/MariaDB, so multiple unpaired rows remain valid.
--

UPDATE `#__cwmconnect_details` SET `user_id` = NULL WHERE `user_id` = 0;

ALTER TABLE `#__cwmconnect_details` MODIFY `user_id` int unsigned DEFAULT NULL;

ALTER TABLE `#__cwmconnect_details` ADD UNIQUE INDEX `uniq_user_id` (`user_id`);
