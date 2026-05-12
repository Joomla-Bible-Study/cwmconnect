--
-- Phase 7: Migrate every component table from utf8 → utf8mb4 (with
-- utf8mb4_unicode_ci default collation). Idempotent: re-running on an
-- already-converted table is a no-op.
--
-- Joomla runs each .sql update file once per registered version, so the
-- safe pattern here is the bare CONVERT TO statement — MySQL accepts
-- the re-conversion as a no-op when the source charset already matches.
--

SET SQL_MODE = '';

ALTER TABLE `#__cwmconnect_details`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__cwmconnect_dirheader`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__cwmconnect_familyunit`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__cwmconnect_geoupdate`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__cwmconnect_kml`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__cwmconnect_position`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

--
-- The alias columns previously had explicit per-column COLLATE utf8_bin
-- overrides for case-sensitive uniqueness on URL slugs. Restore the
-- equivalent utf8mb4_bin collation after the CONVERT TO above (which
-- would otherwise reset them to utf8mb4_unicode_ci).
--

ALTER TABLE `#__cwmconnect_dirheader`
    MODIFY `alias` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '';

ALTER TABLE `#__cwmconnect_familyunit`
    MODIFY `alias` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '';

ALTER TABLE `#__cwmconnect_kml`
    MODIFY `alias` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '';

ALTER TABLE `#__cwmconnect_position`
    MODIFY `alias` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '';
