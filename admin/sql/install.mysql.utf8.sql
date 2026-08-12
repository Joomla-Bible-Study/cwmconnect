SET SQL_MODE = '';

--
-- Table structure for table `#__cwmconnect_details`
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_details` (
  `id`                   INT(11)                                NOT NULL AUTO_INCREMENT,
  `pc_person_id`         BIGINT                                 NULL,
  `pc_last_synced_at`    DATETIME                               NULL,
  `display_in_directory` TINYINT(1)                             NOT NULL DEFAULT 1,
  `directory_scope`      ENUM('public', 'household', 'hidden')  NOT NULL DEFAULT 'public',
  `hidden_reason`        VARCHAR(20)                            NOT NULL DEFAULT '',
  `pc_membership`        VARCHAR(50)                            NOT NULL DEFAULT '',
  `gender`               VARCHAR(20)                            NOT NULL DEFAULT '',
  `is_child`             TINYINT(1)                             NOT NULL DEFAULT 0,
  `pc_shared_info`       JSON                                   NULL,
  `image_filename`       VARCHAR(255)                           NULL,
  `image_hash`           VARCHAR(64)                            NULL,
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
  `email_to`         VARCHAR(255) DEFAULT NULL,
  `default_con`      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `published`        TINYINT(3)          NOT NULL DEFAULT '0',
  `checked_out`      INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `checked_out_time` DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering`         INT(11)             NOT NULL DEFAULT '0',
  `params`           TEXT                NOT NULL,
  `user_id`          int unsigned        DEFAULT NULL,
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
  `fname`            VARCHAR(255)        NOT NULL DEFAULT '',
  `mname`            VARCHAR(255)        NOT NULL DEFAULT '',
  `nickname`         VARCHAR(255)        NOT NULL DEFAULT '',
  `suffix`           VARCHAR(64)         NOT NULL DEFAULT '',
  `is_board`         TINYINT(1)          NOT NULL DEFAULT '0',
  `is_leader`        TINYINT(1)          NOT NULL DEFAULT '0',
  `pc_positions`     VARCHAR(255)        NOT NULL DEFAULT '',
  `pc_ministry_teams` VARCHAR(1024)      NOT NULL DEFAULT '',
  `pc_office_role`   VARCHAR(255)        NOT NULL DEFAULT '',
  `pc_social`        VARCHAR(2048)       NOT NULL DEFAULT '',
  `mstatus`          TINYINT(3)          NOT NULL DEFAULT '0'
  COMMENT 'Used to track Members Status',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pc_person_id` (`pc_person_id`),
  UNIQUE KEY `uniq_user_id` (`user_id`),
  KEY `idx_catid` (`catid`),
  KEY `idx_access` (`access`),
  KEY `Idx_checkout` (`checked_out`),
  KEY `idx_state` (`published`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_featured_catid` (`featured`, `catid`),
  KEY `idx_language` (`language`),
  KEY `idx_xreference` (`xreference`),
  KEY `idx_kmlid` (`kmlid`),
  KEY `idx_funit` (`funitid`),
  KEY `idx_display_in_directory` (`display_in_directory`),
  KEY `idx_directory_scope` (`directory_scope`),
  KEY `idx_is_child` (`is_child`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci
  AUTO_INCREMENT =4;

-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_dirheader`
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_dirheader` (
  `id`                INT(11)             NOT NULL AUTO_INCREMENT,
  `pc_campus_id`      BIGINT              NULL,
  `pc_last_synced_at` DATETIME            NULL,
  `pc_street`         VARCHAR(255)        NULL,
  `pc_city`          VARCHAR(255)        NULL,
  `pc_state`         VARCHAR(100)        NULL,
  `pc_zip`           VARCHAR(20)         NULL,
  `pc_country`       VARCHAR(100)        NULL,
  `pc_phone`         VARCHAR(50)         NULL,
  `pc_email`         VARCHAR(255)        NULL,
  `pc_website`       VARCHAR(255)        NULL,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8mb4
                     COLLATE utf8mb4_bin    NOT NULL DEFAULT '',
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pc_campus_id` (`pc_campus_id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci
  AUTO_INCREMENT =3;

-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_familyunit`
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_familyunit` (
  `id`                INT(11)             NOT NULL AUTO_INCREMENT,
  `pc_household_id`   BIGINT              NULL,
  `pc_last_synced_at` DATETIME            NULL,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8mb4
                     COLLATE utf8mb4_bin    NOT NULL DEFAULT '',
  `description`      MEDIUMTEXT          NOT NULL,
  `image`            VARCHAR(255) DEFAULT NULL,
  `image_hash`       VARCHAR(64)  DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pc_household_id` (`pc_household_id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci
  AUTO_INCREMENT =2;

-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_geoupdate`
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_geoupdate` (
  `member_id` INT(11)      NOT NULL,
  `status`    VARCHAR(255) NOT NULL,
  PRIMARY KEY (`member_id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_kml`
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_kml` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8mb4
                     COLLATE utf8mb4_bin    NOT NULL DEFAULT '',
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
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci
  AUTO_INCREMENT =2;


-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_position`
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_position` (
  `id`               INT(11)             NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(255)        NOT NULL DEFAULT '',
  `alias`            VARCHAR(255)
                     CHARACTER SET utf8mb4
                     COLLATE utf8mb4_bin    NOT NULL DEFAULT '',
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
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci
  AUTO_INCREMENT =31;

-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_update`
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_update` (
  `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci
  AUTO_INCREMENT =6;

-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_feed_tokens`
--
-- Per-user revocable tokens for member self-service KML feed URLs
-- (Phase I). One row per token; the secret is shown to the user once
-- at issue and never stored — only its SHA-256 hash lives here.
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_feed_tokens` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL,
  `token_hash`   CHAR(64)     NOT NULL,
  `label`        VARCHAR(120) NOT NULL,
  `created_at`   DATETIME     NOT NULL,
  `last_used_at` DATETIME     NULL,
  `revoked_at`   DATETIME     NULL,
  `expires_at`   DATETIME     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`)
)
  ENGINE          = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__cwmconnect_pc_field_map`
--
-- Phase D: maps a Planning Center custom-field definition to a Joomla
-- custom field (#__fields row in context com_cwmconnect.member). The
-- sync engine reads this table to translate each PC FieldDatum into a
-- FieldsHelper::setFieldValue() call on the member row.
--
-- pc_field_id is the integer id from PC's FieldDefinition resource.
-- pc_field_slug is a denormalised copy of the PC slug captured at map
-- time, kept for admin display and rebuild-after-PC-rename diagnostics
-- (never used to find the field — that's pc_field_id's job).
--
-- joomla_field_id is FK-shaped against #__fields.id but not declared
-- as a real FK because #__fields belongs to com_fields and Joomla
-- discourages cross-component FKs. The repository validates on save.
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_pc_field_map` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pc_field_id`     BIGINT       NOT NULL,
  `pc_field_slug`   VARCHAR(120) NOT NULL DEFAULT '',
  `pc_field_name`   VARCHAR(255) NOT NULL DEFAULT '',
  `joomla_field_id` INT UNSIGNED NOT NULL,
  `created_at`      DATETIME     NOT NULL,
  `updated_at`      DATETIME     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pc_field_id`     (`pc_field_id`),
  UNIQUE KEY `uniq_joomla_field_id` (`joomla_field_id`)
)
  ENGINE          = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Starting data for table `#__cwmconnect_dirheader`
-- One header block and one footer block for the rendered directory
-- (`section` 0 = header, 1 = footer). Placeholder wording — every church
-- will want to reword these, but an empty directory renders with no title
-- and no notice at all, which is a worse starting point.
--

INSERT INTO `#__cwmconnect_dirheader` (`id`, `name`, `alias`, `description`, `image`, `published`, `section`, `checked_out`, `checked_out_time`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `ordering`, `language`, `created`, `created_by`, `params`, `user_id`, `catid`, `access`, `asset_id`, `publish_up`, `publish_down`)
VALUES
  (1, 'Our Church Directory', 'our-church-directory', '<p>Welcome to our church directory. Please keep the contact details it contains to yourself and use them only to stay in touch with our members.</p>', NULL, 1, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 1, '*', '0000-00-00 00:00:00', 0, '', 0, 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (2, 'Confidentiality', 'confidentiality', '<p><strong>Confidentiality notice</strong>: this directory is for the sole use of our members. Please do not forward, copy or redistribute it.</p>', NULL, 1, 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 2, '*', '0000-00-00 00:00:00', 0, '', 0, 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Starting data for table `#__cwmconnect_position`
-- Role vocabulary, not sample content: the Positions list is a lookup an
-- admin picks from, and an empty one makes the member form unusable until
-- somebody types all of these in by hand. Edit or delete freely.
--

INSERT INTO `#__cwmconnect_position` (`id`, `name`, `alias`, `published`, `checked_out`, `checked_out_time`, `modified`, `modified_by`, `metakey`, `metadesc`, `metadata`, `ordering`, `language`, `created`, `created_by`, `params`, `user_id`, `access`, `asset_id`, `publish_up`, `publish_down`)
VALUES
  (1, 'Pastor', 'pastor', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 1, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (2, 'Elder', 'elder', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 2, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (3, 'Deacon', 'deacon', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 3, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (4, 'Deaconess', 'deaconess', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 4, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (5, 'Secretary', 'secretary', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 5, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (6, 'Treasurer', 'treasurer', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 6, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (7, 'Asst. Treasurer', 'asst-treasurer', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 7, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (8, 'Head Clerk', 'head-clerk', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 8, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (9, 'Asst. Clerk', 'asst-clerk', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 9, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (10, 'Head Deacon', 'head-deacon', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 10, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (11, 'Head Elder', 'head-elder', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 11, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (12, 'Head Deaconess', 'head-deaconess', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 12, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (13, 'Board Member', 'board-member', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 13, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (14, 'Health & Temperance Department', 'health-and-temperance-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 14, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (15, 'AYS Leader', 'ays-leader', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 15, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (16, 'AYS Helper', 'ays-helper', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 16, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (17, 'Community Services Department', 'community-services-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 17, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (18, 'Bulletin', 'bulletin', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 18, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (19, 'Website', 'website', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 19, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (20, 'Prayer Coordinator', 'prayer-coordinator', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 20, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (21, 'Investment Secretary', 'investment-secretary', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 21, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (22, 'Sabbath School Department', 'sabbath-school-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 22, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (23, 'Personal Ministries Department', 'personal-ministries-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 23, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (24, 'Music Department', 'music-department', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 24, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (25, 'Religious Liberty', 'religious-liberty', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 25, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
  (26, 'Vacation Bible School', 'vacation-bible-school', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 26, '*', '0000-00-00 00:00:00', 0, '', 0, 1, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
