<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

// Default response
$response = '0';

// Set value in redcap_projects
if (isset($_POST['auto_variable_naming']) && ($_POST['auto_variable_naming'] == '0' || $_POST['auto_variable_naming'] == '1'))
{
	$sql = "update redcap_projects set auto_variable_naming = {$_POST['auto_variable_naming']} where project_id = $project_id";
	if (db_query($sql)) 
	{
		$response = '1';
		// Log the event
		$logText = $_POST['auto_variable_naming'] ? "Enable auto variable naming" : "Disable auto variable naming";
		log_event($sql,"redcap_projects","MANAGE",$project_id,"auto_variable_naming = {$_POST['auto_variable_naming']}",$logText);
	}
}

// Return response
exit($response);