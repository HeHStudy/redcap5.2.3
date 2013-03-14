<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require dirname(dirname(__FILE__)) . "/Config/init_global.php";

//If user is not a super user, go back to Home page
if (!$super_user) { redirect(APP_PATH_WEBROOT); exit; }

if (isset($_GET['username']) && isset($_GET['suspend']) && $super_user) 
{
	// Set values
	if ($_GET['suspend'] == '1') {
		$suspend = NOW;
		$logmsg = "Suspend user from REDCap";
	} else {
		$suspend = "";
		$logmsg = "Unsuspend user from REDCap";
	}
	// Update the user info table
	$sql = "update redcap_user_information set user_suspended_time = ".checkNull($suspend)." 
			where username = '{$_GET['username']}'";
	if (db_query($sql)) {
		// Logging
		log_event($sql,"redcap_user_information","MANAGE",$_GET['username'],"username = '{$_GET['username']}'",$logmsg);
		// Give positive response
		exit("1");
	}
}

exit("0");