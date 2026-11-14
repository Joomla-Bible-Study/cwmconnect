--
-- A member stayed paired to a Joomla account after the account was deleted,
-- so no other account could pair with them, and the account's proven email
-- and map feeds stayed behind too. Let go of all three for accounts that no
-- longer exist, as deleting an account now does.
--
UPDATE `#__cwmconnect_details` AS d
LEFT JOIN `#__users` AS u ON u.`id` = d.`user_id`
SET d.`user_id` = NULL
WHERE d.`user_id` IS NOT NULL
    AND u.`id` IS NULL;

DELETE v FROM `#__cwmconnect_verified_emails` AS v
LEFT JOIN `#__users` AS u ON u.`id` = v.`user_id`
WHERE u.`id` IS NULL;

UPDATE `#__cwmconnect_feed_tokens` AS t
LEFT JOIN `#__users` AS u ON u.`id` = t.`user_id`
SET t.`revoked_at` = UTC_TIMESTAMP()
WHERE t.`revoked_at` IS NULL
    AND u.`id` IS NULL;
