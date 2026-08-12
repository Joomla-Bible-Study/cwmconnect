-- Retire three columns that no code reads or writes, and that install.mysql.utf8.sql
-- and the update files disagreed about — so whether you had them depended entirely on
-- whether your site was installed fresh or upgraded.
--
--   details.note      added by 1.7.6, never in the install SQL
--   details.imagepos  dropped by 1.7.5, never removed from the install SQL
--   position.webpage  added by 1.7.1, never in the install SQL
--
-- CAN FAIL because which of these exist depends on that same history; a site that
-- never had the column is not in error, it just has nothing to drop.

ALTER TABLE `#__cwmconnect_details` DROP COLUMN `note` /** CAN FAIL **/;

ALTER TABLE `#__cwmconnect_details` DROP COLUMN `imagepos` /** CAN FAIL **/;

ALTER TABLE `#__cwmconnect_position` DROP COLUMN `webpage` /** CAN FAIL **/;
