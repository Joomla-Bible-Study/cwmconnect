-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_details`
--

DROP TABLE IF EXISTS `#__churchdirectory_details`;
CREATE TABLE IF NOT EXISTS `#__churchdirectory_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `lname` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `con_position` varchar(255) NOT NULL DEFAULT '',
  `contact_id` int(3) DEFAULT '0',
  `address` text,
  `suburb` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `postcodeaddon` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax` varchar(255) DEFAULT NULL,
  `misc` mediumtext,
  `spouse` varchar(255) NOT NULL DEFAULT '',
  `children` varchar(255) NOT NULL DEFAULT '',
  `image` varchar(255) DEFAULT NULL,
  `imagepos` varchar(20) DEFAULT NULL,
  `email_to` varchar(255) DEFAULT NULL,
  `default_con` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `published` tinyint(3) NOT NULL DEFAULT '0',
  `checked_out` int(11) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `catid` int(10) unsigned NOT NULL DEFAULT '0',
  `kmlid` int(10) unsigned NOT NULL DEFAULT '1',
  `funitid` int(10) unsigned NOT NULL DEFAULT '0',
  `access` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `mobile` varchar(255) NOT NULL DEFAULT '',
  `webpage` varchar(255) NOT NULL DEFAULT '',
  `sortname1` varchar(255) NOT NULL,
  `sortname2` varchar(255) NOT NULL,
  `sortname3` varchar(255) NOT NULL,
  `language` char(7) NOT NULL DEFAULT '*' COMMENT 'The language code for the contact.',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL,
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  `metadata` text NOT NULL,
  `featured` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `xreference` varchar(50) NOT NULL,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `skype` varchar(255) NOT NULL DEFAULT '',
  `yahoo_msg` varchar(255) NOT NULL DEFAULT '',
  `lat` float(10,6) NOT NULL,
  `lng` float(10,6) NOT NULL,
  `attribs` varchar(5120) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_catid` (`catid`),
  KEY `idx_access` (`access`),
  KEY `Idx_checkout` (`checked_out`),
  KEY `idx_state` (`published`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_featured_catid` (`featured`,`catid`),
  KEY `idx_language` (`language`),
  KEY `idx_xreference` (`xreference`),
  KEY `idx_kmlid` (`kmlid`),
  KEY `idx_funit` (`funitid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=408 ;

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_dirheader`
--

DROP TABLE IF EXISTS `#__churchdirectory_dirheader`;
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

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_familyunit`
--

DROP TABLE IF EXISTS `#__churchdirectory_familyunit`;
CREATE TABLE IF NOT EXISTS `#__churchdirectory_familyunit` (
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
  `access` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `asset_id` int(10) DEFAULT NULL,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_kml`
--

DROP TABLE IF EXISTS `#__churchdirectory_kml`;
CREATE TABLE IF NOT EXISTS `#__churchdirectory_kml` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `description` mediumtext,
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
  `linestyle` varchar(8) NOT NULL DEFAULT '00000000',
  `polystyle` varchar(8) NOT NULL DEFAULT '00000000',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `access` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `asset_id` int(10) DEFAULT NULL,
  `lat` float(10,6) NOT NULL DEFAULT '36.131973',
  `lng` float(10,6) NOT NULL DEFAULT '-86.812370',
  `icon` varchar(255) DEFAULT NULL,
  `style` mediumtext NOT NULL,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=78 ;

--
-- Default values for table `#__churchdirectory_kml`
--

INSERT INTO `#__churchdirectory_kml` (`id`, `name`, `alias`, `description`, `published`, `checked_out`, `checked_out_time`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `ordering`, `language`, `created`, `created_by`, `params`, `linestyle`, `polystyle`, `user_id`, `access`, `asset_id`, `lat`, `lng`, `icon`, `style`, `publish_up`, `publish_down`) VALUES
(1, 'Nashville First SDA Church Members Directory', 'nashville-first-sda-church-members-directory', '<div>\r\n<p><strong>Confidentiality Notice</strong>: This KML file, including any attachments, is for the sole use of the <strong>Nasvhille First SDA Church Members</strong> and may contain confidential and/or privileged information. If you are not the intended recipient(s), you are hereby notified that any dissemination, unauthorized review, use, disclosure or distribution of this KML file and any materials contained in any attachments is prohibited. If you receive this KML file in error, or are not the intended recipient(s), please immediately notify the sender by email and destroy all copies of the original message, including attachments.Privicy Statment</p>\r\n<div style="text-align: center;">\r\n<p>615-297-1343 | 2800 Blair Blvd | Nashville, TN 37212 <br />webmaster@nfsda.org</p>\r\n</div>\r\n</div>', 1, 42, '2012-07-14 04:56:31', '2012-03-18 04:35:11', 63, '', '', '', 0, '*', '2011-12-14 00:00:00', 0, '{"altitude":"0","range":"110027.8255488604","rmaxlines":"","tilt":"0","heading":"-1.119363650863577e-006","lscolormode":"normal","lsscale":".6","icscale":"1.2","open":"1","mcropen":"0","msropen":"0","lscolor":"#ffffff","gxaltitudeMode":"relativeToSeaFloor"}', '00000000', '00000000', 0, 4, 1120, 36.131973, -86.812370, 'b', '', '2011-12-14 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_position`
--

DROP TABLE IF EXISTS `#__churchdirectory_position`;
CREATE TABLE IF NOT EXISTS `#__churchdirectory_position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
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
  `access` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `asset_id` int(10) DEFAULT NULL,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

-- --------------------------------------------------------

--
-- Table structure for table `#__churchdirectory_update`
--

DROP TABLE IF EXISTS `#__churchdirectory_update`;
CREATE TABLE IF NOT EXISTS `#__churchdirectory_update` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;