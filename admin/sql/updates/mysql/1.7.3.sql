--
-- Version 1.7.3 update
--
ALTER TABLE `#__churchdirectory_details` ADD COLUMN `birthdate` DATE NOT NULL DEFAULT '0000-00-00'
AFTER `lng`;
ALTER TABLE `#__churchdirectory_details` ADD COLUMN `anniversary` DATE NOT NULL DEFAULT '0000-00-00'
AFTER `birthdate`;
