<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// Default response
$response = '0';

// Validate params
if (isset($_POST['email_account']) && is_numeric($_POST['email_account']) && $_POST['email_account'] > 1) 
{
	// Get user info
	$user_info = User::getUserInfo($userid);
	// Remove the email from the user's account
	$emailRemoved = User::removeUserEmail($user_info['ui_id'], $_POST['email_account']);
	if ($emailRemoved) {
		// Log the event
		log_event("", "redcap_user_information", "MANAGE", $userid, "username = '$userid'", "Remove user email address");
		// Set successful response
		$response = '1';
	}
}

// Return response
print $response;
