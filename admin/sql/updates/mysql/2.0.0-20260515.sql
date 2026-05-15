--
-- Phase A: v2 data model — additive columns + feed-tokens table.
--
-- Lands the schema surface that later v2 phases (B sync core, E photos,
-- I KML feed, K admin print) need to exist. Plain ALTER / CREATE, no
-- IF NOT EXISTS guards: Joomla's schema tracker runs each update file
-- once per registered version, so re-runs are not the contract here.
--
-- Existing admin (member edit form, members list, etc.) continues to
-- work unchanged — every new column has a safe default and existing
-- code doesn't reference any of them yet.
--
-- Deferred to Phase H (member portal / identity binding):
--   - `user_id` shape change from `INT(11) NOT NULL DEFAULT 0` to
--     `INT UNSIGNED NULL UNIQUE`. The existing MemberTable still coerces
--     unset user_id to 0; adding UNIQUE now would collide on the legacy
--     fleet. The transform lands alongside MemberTable / MemberModel
--     updates in Phase H.
--

SET SQL_MODE = '';

--
-- #__cwmconnect_details: Planning Center sync, privacy tiers, photo cache.
--

ALTER TABLE `#__cwmconnect_details`
    ADD COLUMN `pc_person_id`         BIGINT                                       NULL          AFTER `id`,
    ADD COLUMN `pc_last_synced_at`    DATETIME                                     NULL          AFTER `pc_person_id`,
    ADD COLUMN `display_in_directory` TINYINT(1)                                   NOT NULL DEFAULT 1,
    ADD COLUMN `directory_scope`      ENUM('public', 'household', 'hidden')        NOT NULL DEFAULT 'public',
    ADD COLUMN `pc_shared_info`       JSON                                         NULL,
    ADD COLUMN `image_filename`       VARCHAR(255)                                 NULL,
    ADD COLUMN `image_hash`           VARCHAR(64)                                  NULL,
    ADD UNIQUE INDEX `uniq_pc_person_id` (`pc_person_id`),
    ADD INDEX `idx_display_in_directory` (`display_in_directory`),
    ADD INDEX `idx_directory_scope` (`directory_scope`);

--
-- #__cwmconnect_feed_tokens: per-user revocable tokens for member self-service
-- KML feed URLs (Phase I). One row per token; never re-displayed after issue.
--

CREATE TABLE IF NOT EXISTS `#__cwmconnect_feed_tokens` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `token_hash`   CHAR(64)     NOT NULL,
    `label`        VARCHAR(120) NOT NULL,
    `created_at`   DATETIME     NOT NULL,
    `last_used_at` DATETIME     NULL,
    `revoked_at`   DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_token_hash` (`token_hash`),
    KEY `idx_user_id` (`user_id`)
)
    ENGINE          = InnoDB
    DEFAULT CHARSET = utf8mb4
    DEFAULT COLLATE = utf8mb4_unicode_ci;