<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


/**
 * Set this checklist item as checked or unchecked in the table (if exists in table, then it will be checked in checklist)
 */
 
// Default response
$response = "0";
 
// Return error if no 'name' or 'action' is given
if (!isset($_POST['name']) || (isset($_POST['name']) && empty($_POST['name']))) exit($response);

// REMOVE COMPLETE STATUS
if ($_POST['action'] == "remove")
{
	// Remove from table
	$sql = "delete from redcap_project_checklist where project_id = $project_id and name = '" . prep($_POST['name']) . "' limit 1";
	if (db_query($sql))
	{
		// Log this event
		log_event($sql, "redcap_project_checklist", "MANAGE", $project_id, "project_id = $project_id\nname = '{$_POST['name']}'", "Unchecked item in project checklist");
		// Return successful response
		$response = "1";
	}
}

// SET AS COMPLETE
elseif ($_POST['action'] == "add")
{
	// First check if in table already. If so, then return error because this should not happen.
	$sql = "select count(1) from redcap_project_checklist where project_id = $project_id and name = '" . prep($_POST['name']) . "'";
	$q = db_query($sql);
	if (db_result($q, 0) > 0) exit("0");

	// Add to table
	$sql = "insert into redcap_project_checklist (project_id, name) values ($project_id, '" . prep($_POST['name']) . "')";
	if (db_query($sql))
	{
		// Log this event
		log_event($sql, "redcap_project_checklist", "MANAGE", $project_id, "project_id = $project_id\nname = '{$_POST['name']}'", "Checked off item in project checklist");
		// Return successful response
		$response = "1";
	}
}

// Additional actions (e.g. save setting in redcap_projects table)
if ($response == "1" && isset($_POST['optionalSaveValue']) && $_POST['optionalSaveValue'] != "")
{
	// Make sure the "name" setting is a real one that we can change
	$viableSettingsToChange = array('repeatforms','surveys_enabled');
	if (in_array($_POST['name'], $viableSettingsToChange)) {
		// Modify setting in table
		$sql = "update redcap_projects set {$_POST['name']} = '" . prep($_POST['optionalSaveValue']). "' 
				where project_id = $project_id";
		db_query($sql);
	}
}

// Send response
print $response;
