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

--
-- remove Old con_posistion
--
ALTER TABLE `#__churchdirectory_posision` DROP `con_position`;
CREATE TABLE IF NOT EXISTS `#__churchdirectory_details_ps` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) NOT NULL DEFAULT '0',
  `posistion_id` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_contact` (`contact_id`),
  KEY `idx_position` (`posistion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `#__churchdirectory_position` ADD `webpage` varchar(255) NOT NULL DEFAULT '';