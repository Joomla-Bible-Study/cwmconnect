--
-- Version 1.7.1 update
--
INSERT INTO `#__churchdirectory_update` (id,version) VALUES (1,'1.7.1'),
ON DUPLICATE KEY UPDATE version= '1.7.1';

--
-- Fix for Published Problmes to allow for trash
--
ALTER TABLE `#__churchdirectory_details` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0';
ALTER TABLE `#__churchdirectory_familyunit` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0';
ALTER TABLE `#__churchdirectory_kml` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0';
ALTER TABLE `#__churchdirectory_position` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0';

ALTER TABLE `#__churchdirectory_position` ADD `webpage` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `#__churchdirectory_familyunit` ADD `image` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `description`;
ALTER TABLE `#_churchdirectory_details` ADD INDEX `idx_funit` ( `funitid` );