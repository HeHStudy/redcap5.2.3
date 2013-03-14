<?php

print "
-- Add column to collect user's last login time
ALTER TABLE  `redcap_user_information` ADD  `user_lastlogin` DATETIME NULL AFTER  `user_lastactivity`;
-- Backfill last login time for all users
";

// Query all users missing last login time
$q = db_query("select u.username, u.user_firstvisit from redcap_user_information u
			   where u.user_firstvisit is not null and u.username != '' order by u.username");
while ($row = db_fetch_assoc($q))
{
	$sql = "select v.ts from redcap_log_view v where v.user = '".prep($row['username'])."' "
		 . "and v.event = 'LOGIN_SUCCESS' and v.ts >= '".substr($row['user_firstvisit'],0,10)." 00:00:00' "
		 . "order by v.log_view_id desc limit 1";
	$q2 = db_query($sql);
	while ($row2 = db_fetch_assoc($q2))
	{
		print "UPDATE redcap_user_information SET user_lastlogin = '{$row2['ts']}' WHERE username = '".prep($row['username'])."';\n";
	}
}