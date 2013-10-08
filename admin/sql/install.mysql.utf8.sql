-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_details`
--

CREATE TABLE IF NOT EXISTS `#__churchdirectory_details` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `lname`            VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)        NOT NULL DEFAULT '',
  `con_position`     VARCHAR(255)        NOT NULL DEFAULT '',
  `contact_id`       INT(3) DEFAULT '0',
  `address`          TEXT,
  `suburb`           VARCHAR(100) DEFAULT NULL,
  `state`            VARCHAR(100) DEFAULT NULL,
  `country`          VARCHAR(100) DEFAULT NULL,
  `postcode`         VARCHAR(255) DEFAULT NULL,
  `postcodeaddon`    VARCHAR(255) DEFAULT NULL,
  `telephone`        VARCHAR(255) DEFAULT NULL,
  `fax`              VARCHAR(255) DEFAULT NULL,
  `misc`             MEDIUMTEXT,
  `spouse`           VARCHAR(255)        NOT NULL DEFAULT '',
  `children`         VARCHAR(255)        NOT NULL DEFAULT '',
  `image`            VARCHAR(255) DEFAULT NULL,
  `imagepos`         VARCHAR(20) DEFAULT NULL,
  `email_to`         VARCHAR(255) DEFAULT NULL,
  `default_con`      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `published`        TINYINT(3)          NOT NULL DEFAULT '0',
  `checked_out`      INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `checked_out_time` DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering`         INT(11)             NOT NULL DEFAULT '0',
  `params`           TEXT                NOT NULL,
  `user_id`          INT(11)             NOT NULL DEFAULT '0',
  `catid`            INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `kmlid`            INT(10) UNSIGNED    NOT NULL DEFAULT '1',
  `funitid`          INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `access`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `mobile`           VARCHAR(255)        NOT NULL DEFAULT '',
  `webpage`          VARCHAR(255)        NOT NULL DEFAULT '',
  `sortname1`        VARCHAR(255)        NOT NULL,
  `sortname2`        VARCHAR(255)        NOT NULL,
  `sortname3`        VARCHAR(255)        NOT NULL,
  `language`         CHAR(7)             NOT NULL DEFAULT '*'
  COMMENT 'The language code for the contact.',
  `created`          DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `created_by_alias` VARCHAR(255)        NOT NULL,
  `modified`         DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `metakey`          TEXT                NOT NULL,
  `metadesc`         TEXT                NOT NULL,
  `metadata`         TEXT                NOT NULL,
  `featured`         TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `xreference`       VARCHAR(50)         NOT NULL,
  `publish_up`       DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down`     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `skype`            VARCHAR(255)        NOT NULL DEFAULT '',
  `yahoo_msg`        VARCHAR(255)        NOT NULL DEFAULT '',
  `lat`              FLOAT(10, 6)        NOT NULL,
  `lng`              FLOAT(10, 6)        NOT NULL,
  `birthdate`        DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `anniversary`      DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `attribs`          VARCHAR(5120)       NOT NULL,
  `version`          INT(10) UNSIGNED    NOT NULL DEFAULT '1',
  `hits`             INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `surname`          VARCHAR(255)        NOT NULL DEFAULT '',
  `mstatus`          TINYINT(3)          NOT NULL DEFAULT '0'
  COMMENT 'Used to track Members Status',
  PRIMARY KEY (`id`),
  KEY `idx_catid` (`catid`),
  KEY `idx_access` (`access`),
  KEY `Idx_checkout` (`checked_out`),
  KEY `idx_state` (`published`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_featured_catid` (`featured`, `catid`),
  KEY `idx_language` (`language`),
  KEY `idx_xreference` (`xreference`),
  KEY `idx_kmlid` (`kmlid`),
  KEY `idx_funit` (`funitid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =4;

--
-- Dumping data for table `#__churchdirectory_details`
--

INSERT INTO `#__churchdirectory_details` (`id`, `name`, `lname`, `alias`, `con_position`, `contact_id`, `address`, `suburb`, `state`, `country`, `postcode`, `postcodeaddon`, `telephone`, `fax`, `misc`, `spouse`, `children`, `image`, `imagepos`, `email_to`, `default_con`, `published`, `checked_out`, `checked_out_time`, `ordering`, `params`, `user_id`, `catid`, `kmlid`, `funitid`, `access`, `mobile`, `webpage`, `sortname1`, `sortname2`, `sortname3`, `language`, `created`, `created_by`, `created_by_alias`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `featured`, `xreference`, `publish_up`, `publish_down`, `skype`, `yahoo_msg`, `lat`, `lng`, `birthdate`, `anniversary`, `attribs`, `version`, `hits`, `surname`) VALUES
(1, 'Brent Cordis', 'Cordis', 'brent-cordis', '44,35', 0, '2800 Blair Blvd', 'Nashville', 'TN', 'USA', '37212', NULL, '(615) 657-9749', '(615) 657-9749', '', '', 'Child1, Child2', 'images/sampledata/fruitshop/apple.jpg', NULL, 'info@joomlabiblestudy.com', 0, 1, 0, '0000-00-00 00:00:00', 1, '{"visibility":"1","scale":"1.1","open":"0","gxballoonvisibility":"0","show_contact_category":"","show_contact_list":"","presentation_style":"","show_name":"","show_position":"","show_email":"","show_street_address":"","show_suburb":"","show_state":"","show_postcode":"","show_country":"","show_telephone":"","show_mobile":"","show_fax":"","show_webpage":"","show_misc":"","show_image":"","allow_vcard":"","show_articles":"","show_profile":"","show_links":"","linka_name":"","linka":"","linkb_name":"","linkb":"","linkc_name":"","linkc":"","linkd_name":"","linkd":"","linke_name":"","linke":"","contact_layout":"","show_email_form":"","show_email_copy":"","banned_email":"","banned_subject":"","banned_text":"","validate_session":"","custom_reply":"","redirect":""}', 0, 8, 1, 3, 1, '(615) 657-9749', 'www.joomlabiblestudy.com', 'Last', 'First', 'Middle', '*', '2012-12-03 19:10:08', 53, '', '2012-12-06 19:04:09', 53, '', '', '{"robots":"","rights":""}', 0, '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'skeebrent', 'bcordis', 36.131973, - 86.812370, '1981-12-03 00:00:00', '2012-12-18 00:00:00', '{"memberstatus":"0","memberotherinfo":"","familypostion":"0","mailingaddress":"","mailingsuburb":"","mailingstate":"","mailingpostcode":"","mailingcountry":""}', 1, 0, ''),
(2, 'Amy Cordis', 'Cordis', 'amy-cordis', '41', 0, '2800 Blare Blvd', 'Nashville', 'TN', 'USA', '37212', NULL, '(615) 657-9749', '(615) 657-9749', '', '', 'child1, child2', 'images/joomla_black.gif', NULL, 'info@joomlabiblestudy.com', 0, 1, 0, '0000-00-00 00:00:00', 2, '{"visibility":"1","scale":"1.1","open":"0","gxballoonvisibility":"0","show_contact_category":"","show_contact_list":"","presentation_style":"","show_name":"","show_position":"","show_email":"","show_street_address":"","show_suburb":"","show_state":"","show_postcode":"","show_country":"","show_telephone":"","show_mobile":"","show_fax":"","show_webpage":"","show_misc":"","show_image":"","allow_vcard":"","show_articles":"","show_profile":"","show_links":"","linka_name":"","linka":"","linkb_name":"","linkb":"","linkc_name":"","linkc":"","linkd_name":"","linkd":"","linke_name":"","linke":"","contact_layout":"","show_email_form":"","show_email_copy":"","banned_email":"","banned_subject":"","banned_text":"","validate_session":"","custom_reply":"","redirect":""}', 0, 8, 1, 3, 1, '(615) 657-9749', 'www.joomlabiblestudy.com', '', '', '', '*', '2012-12-05 22:22:32', 53, '', '2012-12-06 19:08:24', 53, '', '', '{"robots":"","rights":""}', 0, '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'test2', 'test1', 36.131973, - 86.812370, '1976-12-13 00:00:00', '2007-12-25 00:00:00', '{"memberstatus":"0","memberotherinfo":"","familypostion":"1","mailingaddress":"","mailingsuburb":"","mailingstate":"","mailingpostcode":"","mailingcountry":""}', 1, 0, ''),
(3, 'James Smith', 'Smith', 'james-smith', '32', 0, '999 test St', 'Test', 'TN', 'USA', '99999', NULL, '(XXX) XXX-XXXX', '(XXX) XXX-XXXX', '<p>Demo contact</p>', '', '', 'images/sampledata/fruitshop/apple.jpg', NULL, 'info@joomlabiblestudy.com', 0, 1, 0, '0000-00-00 00:00:00', 3, '{"visibility":"1","scale":"1.1","open":"0","gxballoonvisibility":"0","show_contact_category":"","show_contact_list":"","presentation_style":"","show_name":"","show_position":"","show_email":"","show_street_address":"","show_suburb":"","show_state":"","show_postcode":"","show_country":"","show_telephone":"","show_mobile":"","show_fax":"","show_webpage":"","show_misc":"","show_image":"","allow_vcard":"","show_articles":"","show_profile":"","show_links":"","linka_name":"","linka":"","linkb_name":"","linkb":"","linkc_name":"","linkc":"","linkd_name":"","linkd":"","linke_name":"","linke":"","contact_layout":"","show_email_form":"","show_email_copy":"","banned_email":"","banned_subject":"","banned_text":"","validate_session":"","custom_reply":"","redirect":""}', 0, 8, 1, 0, 1, '(XXX) XXX-XXXX', 'www.joomlabiblestudy.com', '', '', '', '*', '2012-12-05 22:23:11', 53, '', '2012-12-06 19:10:22', 53, '', '', '{"robots":"","rights":""}', 0, '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '', '', 0.000000, 0.000000, '1991-07-22 00:00:00', '0000-00-00 00:00:00', '{"memberstatus":"0","memberotherinfo":"","familypostion":"-1","mailingaddress":"","mailingsuburb":"","mailingstate":"","mailingpostcode":"","mailingcountry":""}', 1, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_dirheader`
--

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
  `section`          TINYINT(3)          NOT NULL DEFAULT '0'
  COMMENT 'Used to track position on page',
    PRIMARY KEY (`id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =3;

--
-- Dumping data for table `#__churchdirectory_dirheader`
--

INSERT INTO `#__churchdirectory_dirheader` (`id`, `name`, `alias`, `description`, `image`, `published`, `checked_out`, `checked_out_time`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `ordering`, `language`, `created`, `created_by`, `params`, `user_id`, `catid`, `access`, `asset_id`, `publish_up`, `publish_down`) VALUES
(1, 'Pastor', 'pastor', '<p>Pastor Info coming Soon</p>', 'images/powered_by.png', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 2, '*', '2012-12-05 21:59:23', 53, '', 0, 1, 1, 69, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(2, 'Our Church Directory', 'our-church-directory', '<p>Church Directory info</p>', 'images/sampledata/parks/banner_cradle.jpg', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 1, '*', '2012-12-05 22:00:22', 53, '', 0, 1, 1, 70, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_familyunit`
--

CREATE TABLE IF NOT EXISTS `#__churchdirectory_familyunit` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8
                     COLLATE utf8_bin    NOT NULL DEFAULT '',
  `description`      MEDIUMTEXT          NOT NULL,
  `image`            VARCHAR(255) DEFAULT NULL,
  `published`        TINYINT(3)          NOT NULL DEFAULT '0',
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
  `access`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `asset_id`         INT(10) DEFAULT NULL,
  `publish_up`       DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down`     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =2;

--
-- Dumping data for table `#__churchdirectory_familyunit`
--

INSERT INTO `#__churchdirectory_familyunit` (`id`, `name`, `alias`, `description`, `image`, `published`, `checked_out`, `checked_out_time`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `ordering`, `language`, `created`, `created_by`, `params`, `user_id`, `access`, `asset_id`, `publish_up`, `publish_down`) VALUES
(1, 'Brent & Amy Cordis', 'brent-amy-cordis', '', '', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 1, '*', '2012-12-05 22:01:13', 53, '', 0, 1, 71, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_geoupdate`
--

CREATE TABLE IF NOT EXISTS `#__churchdirectory_geoupdate` (
  `member_id` INT(11)      NOT NULL,
  `status`    VARCHAR(255) NOT NULL,
  PRIMARY KEY (`member_id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_kml`
--

CREATE TABLE IF NOT EXISTS `#__churchdirectory_kml` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8
                     COLLATE utf8_bin    NOT NULL DEFAULT '',
  `description`      MEDIUMTEXT,
  `published`        TINYINT(3)          NOT NULL DEFAULT '0',
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
  `linestyle`        VARCHAR(8)          NOT NULL DEFAULT '00000000',
  `polystyle`        VARCHAR(8)          NOT NULL DEFAULT '00000000',
  `user_id`          INT(11)             NOT NULL DEFAULT '0',
  `access`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `asset_id`         INT(10) DEFAULT NULL,
  `lat`              FLOAT(10, 6)        NOT NULL DEFAULT '36.131973',
  `lng`              FLOAT(10, 6)        NOT NULL DEFAULT '-86.812370',
  `icon`             VARCHAR(255) DEFAULT NULL,
  `style`            MEDIUMTEXT          NOT NULL,
  `publish_up`       DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down`     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =2;

--
-- Dumping data for table `#__churchdirectory_kml`
--

INSERT INTO `#__churchdirectory_kml` (`id`, `name`, `alias`, `description`, `published`, `checked_out`, `checked_out_time`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `ordering`, `language`, `created`, `created_by`, `params`, `linestyle`, `polystyle`, `user_id`, `access`, `asset_id`, `lat`, `lng`, `icon`, `style`, `publish_up`, `publish_down`) VALUES
(1, 'Nashville First SDA Church Members Directory', 'nashville-first-sda-church-members-directory', '<div>\r\n<p><strong>Confidentiality Notice</strong>: This KML file, including any attachments, is for the sole use of the <strong>Nasvhille First SDA Church Members</strong> and may contain confidential and/or privileged information. If you are not the intended recipient(s), you are hereby notified that any dissemination, unauthorized review, use, disclosure or distribution of this KML file and any materials contained in any attachments is prohibited. If you receive this KML file in error, or are not the intended recipient(s), please immediately notify the sender by email and destroy all copies of the original message, including attachments.Privicy Statment</p>\r\n<div style="text-align: center;">\r\n<p>615-297-1343 | 2800 Blair Blvd | Nashville, TN 37212 <br />webmaster@nfsda.org</p>\r\n</div>\r\n</div>', 1, 0, '0000-00-00 00:00:00', '2012-03-18 04:35:11', 63, '', '', '', 0, '*', '2011-12-14 00:00:00', 0, '{"altitude":"0","range":"110027.8255488604","rmaxlines":"","tilt":"0","heading":"-1.119363650863577e-006","lscolormode":"normal","lsscale":".6","icscale":"1.2","open":"1","mcropen":"0","msropen":"0","lscolor":"#ffffff","gxaltitudeMode":"relativeToSeaFloor"}', '00000000', '00000000', 0, 4, 1120, 36.131973, - 86.812370, 'b', '', '2011-12-14 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_position`
--

CREATE TABLE IF NOT EXISTS `#__churchdirectory_position` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8
                     COLLATE utf8_bin    NOT NULL DEFAULT '',
  `published`        TINYINT(3)          NOT NULL DEFAULT '0',
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
  `access`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `asset_id`         INT(10) DEFAULT NULL,
  `publish_up`       DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down`     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =31;

--
-- Dumping data for table `#__churchdirectory_position`
--

INSERT INTO `#__churchdirectory_position` (`id`, `name`, `alias`, `published`, `checked_out`, `checked_out_time`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `ordering`, `language`, `created`, `created_by`, `params`, `user_id`, `access`, `asset_id`, `publish_up`, `publish_down`) VALUES
(1, 'Pastor', 'pastor', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 1, '*', '2012-12-03 21:09:21', 53, '', 0, 1, 37, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(2, 'Elder', 'elder', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 2, '*', '2012-12-03 21:09:36', 53, '', 0, 1, 38, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(3, 'Deacon', 'deacon', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 3, '*', '2012-12-03 21:09:58', 53, '', 0, 1, 39, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(4, 'Deaconess', 'deaconess', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 4, '*', '2012-12-03 21:10:12', 53, '', 0, 1, 40, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(5, 'Clerk', 'clerk', -2, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 5, '*', '2012-12-03 21:10:26', 53, '', 0, 1, 41, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(6, 'Secretary', 'secretary', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 6, '*', '2012-12-03 21:10:57', 53, '', 0, 1, 42, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(7, 'Treasurer', 'treasurer', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 7, '*', '2012-12-03 21:11:13', 53, '', 0, 1, 43, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(8, 'Ass’t Treasurer', 'ass-t-treasurer', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 8, '*', '2012-12-03 21:11:25', 53, '', 0, 1, 44, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(9, 'Head Clerk', 'head-clerk', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 9, '*', '2012-12-03 21:11:41', 53, '', 0, 1, 45, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(10, 'Asst. Clerk', 'asst-clerk', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 10, '*', '2012-12-03 21:12:48', 53, '', 0, 1, 46, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(11, 'Head Deacon', 'head-deacon', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 11, '*', '2012-12-03 21:13:23', 53, '', 0, 1, 47, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(12, 'Head Elder', 'head-elder', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 12, '*', '2012-12-03 21:13:34', 53, '', 0, 1, 48, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(13, 'Head Deaconess', 'head-deaconess', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 13, '*', '2012-12-03 21:13:45', 53, '', 0, 1, 49, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(14, 'Board Member', 'board-member', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 14, '*', '2012-12-03 21:16:56', 53, '', 0, 1, 50, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(15, 'Health & Temperance Department', 'health-temperance-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 15, '*', '2012-12-03 21:17:36', 53, '', 0, 1, 51, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(16, 'AYS Leader', 'ays-leader', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 16, '*', '2012-12-03 21:17:52', 53, '', 0, 1, 52, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(17, 'AYS Helper', 'ays-helper', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 17, '*', '2012-12-03 21:18:12', 53, '', 0, 1, 53, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(18, 'Community Services Department', 'community-services-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 18, '*', '2012-12-03 21:18:33', 53, '', 0, 1, 54, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(19, 'Bulletin', 'bulletin', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 19, '*', '2012-12-03 21:19:06', 53, '', 0, 1, 55, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(20, 'Website', 'website', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 20, '*', '2012-12-03 21:19:15', 53, '', 0, 1, 56, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(21, 'Prayer Coordinator', 'prayer-coordinator', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 21, '*', '2012-12-03 21:19:27', 53, '', 0, 1, 57, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(22, 'Investment Secretary', 'investment-secretary', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 22, '*', '2012-12-03 21:19:47', 53, '', 0, 1, 58, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(23, 'Children''s Ministries Leader', 'children-s-ministries-leader', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 23, '*', '2012-12-03 21:20:25', 53, '', 0, 1, 59, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(24, 'Children''s Ministries', 'children-s-ministries', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 24, '*', '2012-12-03 21:20:31', 53, '', 0, 1, 60, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(25, 'Sabbath Shool Department Head Superintentdant', 'sabbath-shool-department-head-superintentdant', -2, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 25, '*', '2012-12-03 21:21:03', 53, '', 0, 1, 61, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(26, 'Sabbath Shool Department', 'sabbath-shool-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 26, '*', '2012-12-03 21:21:14', 53, '', 0, 1, 62, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(27, 'Personal Ministries Department', 'personal-ministries-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 27, '*', '2012-12-03 21:21:32', 53, '', 0, 1, 63, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(28, 'Music Department', 'music-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 28, '*', '2012-12-03 21:21:51', 53, '', 0, 1, 64, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(29, 'Religios Liberty', 'religios-liberty', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 29, '*', '2012-12-03 21:23:28', 53, '', 0, 1, 65, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(30, 'Vacation Bible School', 'vacation-bible-school', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 30, '*', '2012-12-03 21:23:39', 53, '', 0, 1, 66, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_update`
--

CREATE TABLE IF NOT EXISTS `#__churchdirectory_update` (
  `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =6;

--
-- Dumping data for table `#__churchdirectory_update`
--

INSERT INTO `#__churchdirectory_update` (`id`, `version`) VALUES
(1, '1.7.1'),
(2, '1.7.2'),
(3, '1.7.3'),
(4, '1.7.4'),
(5, '1.7.5');
