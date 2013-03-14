<?php

?>

-- Add new Terminated user status
ALTER TABLE  `redcap_user_information` ADD  `user_lastactivity` DATETIME NULL AFTER  `user_firstactivity` ,
	ADD  `user_suspended_time` DATETIME NULL AFTER  `user_lastactivity`;
	
<?php

print "-- Back-fill 'last activity' for all users\n";
$sql = "select username from redcap_user_information";
$q = db_query($sql);
while ($row = db_fetch_assoc($q))
{
	$q2 = db_query("select user, timestamp(max(ts)) as user_lastactivity from redcap_log_event where user = '".prep($row['username'])."'");
	$this_user = db_result($q2, 0, "user");
	if (!empty($this_user))
	{
		$this_user_lastactivity = db_result($q2, 0, "user_lastactivity");
		print "update redcap_user_information set user_lastactivity = '$this_user_lastactivity' where username = '".prep($row['username'])."';\n";
	}
}