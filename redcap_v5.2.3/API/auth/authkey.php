<?php


/**
 * "EXTERNAL LINKS" AUTHENTICATION UTILITY (via Advanced Link)
 * Allows any website to verify that a user's session is still active, and if so, reset the timer for that user's session.
 */
 
// Decrypt the authkey
$decrypted_authkey = unserialize(decrypt($_POST['authkey']));

// Check if the session is active
if (on_session_read($decrypted_authkey['session_id']) !== false)
{
	// Session still active, so return information
	if (isset($_POST['format']) && $_POST['format'] == 'json')
	{
		## JSON
		// Special formatting
		$decrypted_authkey['callback_url'] = str_replace("/", "\\/", $decrypted_authkey['callback_url']);
		if (strpos($decrypted_authkey['data_access_group_name'], '"') !== false) {
			$decrypted_authkey['data_access_group_name'] = str_replace('"', '\"', $decrypted_authkey['data_access_group_name']);
		}
		print '{';
		print '"project_id":' . $decrypted_authkey['project_id'] . ',';
		print '"username":"' . $decrypted_authkey['username'] . '",';
		print '"data_access_group_id":"' . $decrypted_authkey['data_access_group_id'] . '",';
		print '"data_access_group_name":"' . $decrypted_authkey['data_access_group_name'] . '",';
		print '"callback_url":"' . $decrypted_authkey['callback_url'] . '"';
		print '}';
	}
	elseif (isset($_POST['format']) && $_POST['format'] == 'xml')
	{
		## XML
		print '<?xml version="1.0" encoding="UTF-8" ?><items>';
		print '<project_id><![CDATA[' . $decrypted_authkey['project_id'] . ']]></project_id>';
		print '<username><![CDATA[' . $decrypted_authkey['username'] . ']]></username>';
		print '<data_access_group_id><![CDATA[' . $decrypted_authkey['data_access_group_id'] . ']]></data_access_group_id>';
		print '<data_access_group_name><![CDATA[' . $decrypted_authkey['data_access_group_name'] . ']]></data_access_group_name>';
		print '<callback_url><![CDATA[' . $decrypted_authkey['callback_url'] . ']]></callback_url>';
		print '</items>';
	}
	else
	{
		## CSV (default)
		// Special formatting
		if (strpos($decrypted_authkey['data_access_group_name'], '"') !== false || strpos($decrypted_authkey['data_access_group_name'], ',') !== false) {
			$decrypted_authkey['data_access_group_name'] = '"' . str_replace('"', '""', $decrypted_authkey['data_access_group_name']) . '"';
		}
		print "project_id,username,data_access_group_id,data_access_group_name,callback_url\n";
		print $decrypted_authkey['project_id'] . ",";
		print $decrypted_authkey['username'] . ",";
		print $decrypted_authkey['data_access_group_id'] . ",";
		print $decrypted_authkey['data_access_group_name'] . ",";
		print $decrypted_authkey['callback_url'];
	}	
	// Log this event
	defined("PROJECT_ID") or define("PROJECT_ID", $decrypted_authkey['project_id']);
	defined("USERID") 	  or define("USERID", $decrypted_authkey['username']);
	//defined("PAGE")   	  or define("PAGE", "");
	log_event("","redcap_external_links","MANAGE","","project_id = {$decrypted_authkey['project_id']}\nusername = '{$decrypted_authkey['username']}'","Verify project bookmark authkey (success)");
}
else
{
	// Session not active
	print "0";	
	// Log this event
	log_event("","redcap_external_links","MANAGE","","session_id = {$decrypted_authkey['session_id']}","Verify project bookmark authkey (failure)");

}
