INSERT INTO `#__churchdirectory_update` (id,version) VALUES (1,'1.7.1')
ON DUPLICATE KEY UPDATE version= '1.7.1';

ALTER TABLE `#__churchdirectory_details` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0'
ALTER TABLE `#__churchdirectory_familyunit` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0'
ALTER TABLE `#__churchdirectory_kml` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0'
ALTER TABLE `#__churchdirectory_position` CHANGE `published` `published` TINYINT( 3 ) NOT NULL DEFAULT '0'