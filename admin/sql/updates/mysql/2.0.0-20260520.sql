--
-- Phase H.1: identity-binding user_id transform on #__cwmconnect_details.
--
-- Existing schema (pre-H.1):
--   `user_id` INT(11) NOT NULL DEFAULT '0'
-- Target schema (post-H.1):
--   `user_id` INT UNSIGNED NULL DEFAULT NULL + UNIQUE INDEX uniq_user_id
--
-- Step 1: relax the column to nullable so zeros can become NULL.
-- Step 2: coalesce legacy 0 values to NULL.
-- Step 3: add the UNIQUE constraint (NULLs are not considered equal).
--

ALTER TABLE `#__cwmconnect_details`
    MODIFY COLUMN `user_id` INT UNSIGNED NULL DEFAULT NULL;

UPDATE `#__cwmconnect_details` SET `user_id` = NULL WHERE `user_id` = 0;

ALTER TABLE `#__cwmconnect_details`
    ADD UNIQUE INDEX `uniq_user_id` (`user_id`);
