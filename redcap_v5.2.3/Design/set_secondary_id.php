<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

// Default return value
$msg = "error";

// Query to set field as secondary id field
$sql = "update redcap_projects set secondary_pk = '" . prep($_GET['field_name']) . "' where project_id = $project_id";

// Field name is valid
if (isset($_GET['field_name']) && isset($Proj->metadata[$_GET['field_name']]) && db_query($sql))
{
	$msg = "secondaryidset";
	$logmsg = "Set secondary unique field";
}
// Field name is blank (i.e. resetting 2ndary id to blank)
elseif (isset($_GET['field_name']) && $_GET['field_name'] == '' && db_query($sql))
{
	$msg = "secondaryidreset";
	$logmsg = "Remove secondary unique field";
}

// Log the event
log_event($sql, "redcap_projects", "MANAGE", PROJECT_ID, "project_id = ".PROJECT_ID, $logmsg);
// Redirect back
redirect(APP_PATH_WEBROOT . "ProjectSetup/index.php?pid=$project_id&msg=$msg");