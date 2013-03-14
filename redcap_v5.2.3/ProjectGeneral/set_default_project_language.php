<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Set default response
$response = "0";

if (isset($_POST['is_checked'])) 
{
	// Set new default language
	if ($_POST['is_checked'])
	{
		$sql = "update redcap_projects set google_translate_default = '" . prep($_POST['new_lang']) . "' where project_id = $project_id";		
		if (db_query($sql)) {
			$response = "1";
			// Logging
			log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Set project default language");
		}
	}
	// Set default language as null
	else
	{
		$sql = "update redcap_projects set google_translate_default = null where project_id = $project_id";		
		if (db_query($sql)) {
			$response = "1";
			// Logging
			log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Remove project default language");
		}
	}
}

// Send response
print $response;