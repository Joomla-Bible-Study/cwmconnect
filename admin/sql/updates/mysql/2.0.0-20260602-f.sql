ALTER TABLE `#__cwmconnect_details` ADD COLUMN `is_board` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `#__cwmconnect_details` ADD COLUMN `is_leader` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `#__cwmconnect_details` ADD COLUMN `pc_positions` VARCHAR(255) NOT NULL DEFAULT '';

ALTER TABLE `#__cwmconnect_details` ADD COLUMN `pc_ministry_teams` VARCHAR(1024) NOT NULL DEFAULT '';
