<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// First, do server side check of email address to ensure not attempting XSS attack
if (isEmail($_POST['email']))
{
	// Sanitize inputs
	foreach ($_POST as &$val) $val = strip_tags(html_entity_decode($val, ENT_QUOTES));
		
	// Update/insert into table
	$sql = "insert into redcap_user_information (username, user_email, user_firstname, user_lastname, user_firstvisit, allow_create_db) values
			('".prep($userid)."', '".prep($_POST['email'])."', '".prep($_POST['firstname'])."', 
			'".prep($_POST['lastname'])."', '".NOW."', $allow_create_db_default)
			on duplicate key
			update user_email = '".prep($_POST['email'])."', user_firstname = '".prep($_POST['firstname'])."', 
			user_lastname = '".prep($_POST['lastname'])."', ui_id = LAST_INSERT_ID(ui_id)";
	if (db_query($sql))
	{
		// Get user's ui_id
		$ui_id = db_insert_id();
		// Logging
		log_event($sql,"redcap_user_information","MANAGE",$userid,"username = '".prep($userid)."'","Update user info");
		// Now send an email to their account so they can verify their email 
		$verificationCode = User::setUserVerificationCode($ui_id, 1);
		if ($verificationCode !== false) {
			// Send verification email to user	
			$emailSent = User::sendUserVerificationCode($_POST['email'], $verificationCode);
			if ($emailSent) {
				// Redirect back to previous page to display confirmation message and notify user that they were sent an email
				redirect(APP_PATH_WEBROOT . "Profile/user_info.php?verify_email_sent=1");
			}
		}
	}
}

//Redirect back
redirect(APP_PATH_WEBROOT_FULL);
