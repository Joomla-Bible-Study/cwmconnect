--
-- Phase K.6: Planning Center campus contact columns on #__cwmconnect_dirheader.
--
-- The cover page of the printed directory pulls the church name + address from
-- the synced PC campus. The table already reserves pc_campus_id +
-- pc_last_synced_at; these add the contact fields the campus sync writes.
-- One change per ALTER TABLE, no COLUMN keyword (Joomla ChangeItem rules).
--

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_street` varchar(255) NULL;

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_city` varchar(255) NULL;

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_state` varchar(100) NULL;

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_zip` varchar(20) NULL;

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_country` varchar(100) NULL;

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_phone` varchar(50) NULL;

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_email` varchar(255) NULL;

ALTER TABLE `#__cwmconnect_dirheader` ADD `pc_website` varchar(255) NULL;
