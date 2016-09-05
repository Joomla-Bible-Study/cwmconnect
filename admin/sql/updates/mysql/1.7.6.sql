ALTER TABLE `#__churchdirectory_details`
  ADD COLUMN `mstatus` TINYINT(3) NOT NULL DEFAULT '0'
COMMENT 'Used to track Members Status';
ALTER TABLE `#__churchdirectory_details`
  ADD COLUMN `note` VARCHAR(255) NOT NULL DEFAULT ''
COMMENT 'Notes on the member for Privet use';
