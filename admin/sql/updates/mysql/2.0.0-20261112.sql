-- The last email each Joomla account has proven is its own: confirmed through the
-- registration activation link, or set by an administrator when creating the account
-- or changing its email. Joomla does not record this, and automatic pairing must not
-- trust an email the account merely holds, so pairing requires the account's current
-- email to match this row. One row per account; the user plugin writes it.
--
-- Sites updating from an earlier build start with no rows: no account is trusted
-- until it proves its email again, and an administrator can still pair a member by
-- hand in the member's edit form.

CREATE TABLE IF NOT EXISTS `#__cwmconnect_verified_emails` (
    `user_id`     INT UNSIGNED NOT NULL,
    `email`       VARCHAR(100) NOT NULL,
    `source`      VARCHAR(20)  NOT NULL,
    `verified_at` DATETIME     NOT NULL,
    PRIMARY KEY (`user_id`)
)
    ENGINE          = InnoDB
    DEFAULT CHARSET = utf8mb4
    DEFAULT COLLATE = utf8mb4_unicode_ci;
