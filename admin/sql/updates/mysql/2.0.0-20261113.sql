-- Planning Center people removed under a privacy request. The sync neither refills
-- nor re-imports them, and keeps their blanked, archived row as the handle an
-- administrator restores them from: taking that row out of the archive sets
-- released_at, and the next sync refills the row, lists them again and deletes
-- this one.

CREATE TABLE IF NOT EXISTS `#__cwmconnect_privacy_suppressions` (
    `pc_person_id` BIGINT   NOT NULL,
    `removed_at`   DATETIME NOT NULL,
    `released_at`  DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`pc_person_id`)
)
    ENGINE          = InnoDB
    DEFAULT CHARSET = utf8mb4
    DEFAULT COLLATE = utf8mb4_unicode_ci;
