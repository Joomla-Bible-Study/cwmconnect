ALTER TABLE `#__cwmconnect_details`
  ADD COLUMN `mstatus` TINYINT(3) NOT NULL DEFAULT '0'
COMMENT 'Used to track Members Status';

-- Dropped again in 2.0.0-20260729; commented out so the schema check does not
-- expect a column that no longer exists. See core 5.3.0-2024-12-19.sql for the pattern.
-- ALTER TABLE `#__cwmconnect_details`
--   ADD COLUMN `note` VARCHAR(255) NOT NULL DEFAULT ''
-- COMMENT 'Notes on the member for Privet use';
