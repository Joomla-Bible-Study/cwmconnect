--
-- Version 1.7.1 update
--
INSERT INTO `#__churchdirectory_update` (id, version) VALUES (1, '1.7.1')
ON DUPLICATE KEY UPDATE version= '1.7.1';

--
-- Fix for Published Problmes to allow for trash
--
ALTER TABLE `#__churchdirectory_details` CHANGE `published` `published` TINYINT(3) NOT NULL DEFAULT '0';
ALTER TABLE `#__churchdirectory_familyunit` CHANGE `published` `published` TINYINT(3) NOT NULL DEFAULT '0';
ALTER TABLE `#__churchdirectory_kml` CHANGE `published` `published` TINYINT(3) NOT NULL DEFAULT '0';
ALTER TABLE `#__churchdirectory_position` CHANGE `published` `published` TINYINT(3) NOT NULL DEFAULT '0';

ALTER TABLE `#__churchdirectory_position` ADD `webpage` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `#__churchdirectory_familyunit` ADD `image` VARCHAR(255)
CHARACTER SET utf8
COLLATE utf8_general_ci NULL DEFAULT NULL
AFTER `description`;
ALTER TABLE `#__churchdirectory_details` ADD INDEX `idx_funit` (`funitid`);

UPDATE `#__menu`
SET `title` =  'COM_CHURCHDIRECTORY_MEMBERS',
  `alias` =  'com-churchdirectory-members',
  `path` =  'com-churchdirectory/com-churchdirectory-members',
  `link` =  'index.php?option=com_churchdirectory&view=members',
  `img` =  '../media/com_churchdirectory/images/menu/icon-16-members.png'
WHERE `#__menu`.`title` = 'COM_CHURCHDIRECTORY_MEMBERS';

CREATE TABLE IF NOT EXISTS `#__churchdirectory_dirheader` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8
                     COLLATE utf8_bin    NOT NULL DEFAULT '',
  `description`      MEDIUMTEXT          NOT NULL,
  `image`            VARCHAR(255) DEFAULT NULL,
  `published`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out`      INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `checked_out_time` DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified`         DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `metakey`          TEXT                NOT NULL,
  `metadesc`         TEXT                NOT NULL,
  `metadata`         TEXT                NOT NULL,
  `ordering`         INT(11)             NOT NULL DEFAULT '0',
  `language`         CHAR(7)             NOT NULL DEFAULT 'None',
  `created`          DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `params`           TEXT                NOT NULL,
  `user_id`          INT(11)             NOT NULL DEFAULT '0',
  `catid`            INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `access`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `asset_id`         INT(10) DEFAULT NULL,
  `publish_up`       DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down`     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
)
  ENGINE =MyISAM
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =3;

CREATE TABLE IF NOT EXISTS `#__churchdirectory_dirheader` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8
                     COLLATE utf8_bin    NOT NULL DEFAULT '',
  `description`      MEDIUMTEXT          NOT NULL,
  `image`            VARCHAR(255) DEFAULT NULL,
  `published`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out`      INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `checked_out_time` DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified`         DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `metakey`          TEXT                NOT NULL,
  `metadesc`         TEXT                NOT NULL,
  `metadata`         TEXT                NOT NULL,
  `ordering`         INT(11)             NOT NULL DEFAULT '0',
  `language`         CHAR(7)             NOT NULL DEFAULT 'None',
  `created`          DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `params`           TEXT                NOT NULL,
  `user_id`          INT(11)             NOT NULL DEFAULT '0',
  `catid`            INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `access`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `asset_id`         INT(10) DEFAULT NULL,
  `publish_up`       DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down`     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
)
  ENGINE =MyISAM
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =3;
