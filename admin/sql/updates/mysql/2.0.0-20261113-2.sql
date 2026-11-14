--
-- Hand-entered members used to start at Public, so a site search or the
-- birthday module showed them to anyone, while synced members get the member
-- view level (Options). Move the ones still at Public to it, as a new member
-- now starts there. A member at any other level was put there deliberately,
-- and stays.
--
UPDATE `#__cwmconnect_details`
SET `access` = COALESCE((
        SELECT JSON_UNQUOTE(JSON_EXTRACT(`params`, '$.member_access'))
        FROM `#__extensions`
        WHERE JSON_VALID(`params`) = 1
            AND JSON_UNQUOTE(JSON_EXTRACT(`params`, '$.member_access')) REGEXP '^[1-9][0-9]*$'
            AND `type` = 'component'
            AND `element` = 'com_cwmconnect'
    ), 2)
WHERE `pc_person_id` IS NULL
    AND `access` = 1;
