CREATE TABLE IF NOT EXISTS `#__churchdirectory_geoupdate` (
  `member_id` INT(11)      NOT NULL,
  `status`    VARCHAR(255) NOT NULL,
  PRIMARY KEY (`member_id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

ALTER TABLE `#__churchdirectory_details` DROP `imagepos`;

ALTER TABLE `#__churchdirectory_details` ENGINE =InnoDB;
ALTER TABLE `#__churchdirectory_dirheader` ENGINE =InnoDB;
ALTER TABLE `#__churchdirectory_familyunit` ENGINE =InnoDB;
ALTER TABLE `#__churchdirectory_kml` ENGINE =InnoDB;
ALTER TABLE `#__churchdirectory_position` ENGINE =InnoDB;
ALTER TABLE `#__churchdirectory_update` ENGINE =InnoDB;

ALTER TABLE `#__churchdirectory_details` ADD COLUMN `version` INT(10) UNSIGNED NOT NULL DEFAULT '1';
ALTER TABLE `#__churchdirectory_details` ADD COLUMN `hits` INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `#__churchdirectory_details` ADD COLUMN `surname` VARCHAR(255) NOT NULL DEFAULT '';
