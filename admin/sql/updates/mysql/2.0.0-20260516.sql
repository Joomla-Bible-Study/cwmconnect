--
-- Phase D: PC custom-field → Joomla custom-field mapping table.
--
-- Lets admins pair a PC FieldDefinition with a Joomla custom field on
-- the com_cwmconnect.member context so the sync engine can write the
-- FieldDatum value into that field via FieldsHelper::setFieldValue().
--
-- joomla_field_id is FK-shaped against #__fields.id but isn't declared
-- as a real FK (com_fields lives in a different component; Joomla
-- discourages cross-component FKs). Repository validates on save.
--

SET SQL_MODE = '';

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