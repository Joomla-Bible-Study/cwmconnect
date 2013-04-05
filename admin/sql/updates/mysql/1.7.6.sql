ALTER TABLE `#__churchdirectory_details` ADD COLUMN `mstatus` tinyint(3) NOT NULL DEFAULT '0' COMMENT 'Used to track Members Status';
ALTER TABLE `#__churchdirectory_details` ADD COLUMN `note` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Notes on the member for Privet use';
