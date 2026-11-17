-- The details a member keeps from the general membership, as a comma-separated
-- list of keys (email, phone, mobile, address, birthdate, anniversary, photo).
-- The member sets it in My Profile, or an administrator on the member's edit
-- form; church leadership with the "See all member details" permission still
-- sees everything. Empty means nothing is hidden, which is how every member
-- starts.

ALTER TABLE `#__cwmconnect_details` ADD `hidden_fields` VARCHAR(255) NOT NULL DEFAULT '' AFTER `hidden_reason`;
