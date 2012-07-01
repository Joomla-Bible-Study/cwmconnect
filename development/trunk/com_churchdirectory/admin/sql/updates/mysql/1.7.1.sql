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
ALTER TABLE `#__churchdirectory_details` ADD INDEX `idx_funit` ( `funitid` );

UPDATE  `#__menu` SET  `title` =  'COM_CHURCHDIRECTORY_MEMBERS',
`alias` =  'com-churchdirectory-members',
`path` =  'com-churchdirectory/com-churchdirectory-members',
`link` =  'index.php?option=com_churchdirectory&view=members',
`img` =  '../media/com_churchdirectory/images/menu/icon-16-members.png' WHERE  `#__menu`.`title` = 'COM_CHURCHDIRECTORY_CONTACTS';

CREATE TABLE IF NOT EXISTS `#__churchdirectory_dirheader` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `published` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `checked_out` int(11) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  `metadata` text NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `language` char(7) NOT NULL DEFAULT 'None',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `catid` int(11) unsigned NOT NULL DEFAULT '1',
  `access` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `asset_id` int(10) DEFAULT NULL,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

CREATE TABLE IF NOT EXISTS `#__churchdirectory_dirheader` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `published` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `checked_out` int(11) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  `metadata` text NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `language` char(7) NOT NULL DEFAULT 'None',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `catid` int(11) unsigned NOT NULL DEFAULT '1',
  `access` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `asset_id` int(10) DEFAULT NULL,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;