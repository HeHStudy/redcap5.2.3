<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require dirname(dirname(__FILE__)) . "/Config/init_global.php";

//If user is not a super user, go back to Home page
if (!$super_user) { redirect(APP_PATH_WEBROOT); exit; }

if (isset($_GET['username']) && $super_user) {

	// Remove user from user info table
	$q1 = db_query("delete from redcap_user_information where username = '".prep($_GET['username'])."'");
	$q1_rows = db_affected_rows();

	// Remove user from user rights table
	$q2 = db_query("delete from redcap_user_rights where username = '".prep($_GET['username'])."'");

	// Remove user from auth table (in case if using Table-based authentication)
	$q3 = db_query("delete from redcap_auth where username = '".prep($_GET['username'])."'");

	// Also remove user from the user whitelist (in case they are used there)
	$q4 = db_query("delete from redcap_user_whitelist where username = '".prep($_GET['username'])."'");
		
	// If all queries ran as expected, give positive response
	if ($q1_rows == 1 && $q1 && $q2 && $q3 && $q4) {
		// Logging
		log_event("","redcap_user_information\nredcap_user_rights\nredcap_auth","MANAGE",$_GET['username'],"username = '".prep($_GET['username'])."'","Delete user from REDCap");
		// Give positive response
		exit("1");
	}
}

exit("0");