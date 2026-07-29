ALTER TABLE `#__cwmconnect_details` ADD COLUMN `is_child` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `#__cwmconnect_details` ADD INDEX `idx_is_child` (`is_child`);
