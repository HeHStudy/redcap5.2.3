<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
include_once APP_PATH_DOCROOT . "Logging/logging_functions.php";

// Obtain names of Events (for Longitudinal projects) and put in array
$event_ids = array();
if ($longitudinal) 
{
	// Query list of event names
	$sql = "select e.event_id, e.descrip, a.arm_name, a.arm_num from redcap_events_metadata e, redcap_events_arms a where 
			a.arm_id = e.arm_id and a.project_id = " . PROJECT_ID;
	$q = db_query($sql);
	// More than one arm, so display arm name
	if ($multiple_arms) 
	{	
		// Loop through events
		while ($row = db_fetch_assoc($q)) 
		{
			$event_ids[$row['event_id']] = $row['descrip'] . " - {$lang['global_08']} " . $row['arm_num'] . "{$lang['colon']} " . $row['arm_name'];
		}
	}
	// Only one arm, so only display event name
	else
	{
		// Loop through events
		while ($row = db_fetch_assoc($q)) 
		{
			$event_ids[$row['event_id']] = $row['descrip'];
		}
	}
}

// If user is in DAG, limit viewing to only users in their own DAG
$dag_users_array = getDagUsers($project_id, $user_rights['group_id']);
$dag_users = empty($dag_users_array) ? "" : "AND user in ('" . implode("', '", $dag_users_array) . "')";

// Query logging table
$sql = "SELECT * FROM redcap_log_event WHERE project_id = $project_id $dag_users ORDER BY log_event_id DESC";	
$result = db_query($sql);

// Set headers
$headers = array($lang['reporting_19'], $lang['global_11'], $lang['reporting_21'], str_replace("\n", " ", br2nl($lang['reporting_22'])));
// If project-level flag is set, then add "reason changed" to row data
if ($require_change_reason) $headers[] = "Reason for Data Change(s)";	

// Set file name and path
$filename = APP_PATH_TEMP . date("YmdHis") . '_' . PROJECT_ID . '_logging.csv';

// Begin writing file from query result
$fp = fopen($filename, 'w');

if ($fp && $result) 
{
	// Write headers to file
	fputcsv($fp, $headers);
	
	// Set values for this row and write to file
	while ($row = db_fetch_assoc($result)) 
	{
		fputcsv($fp, renderLogRow($row, false));
	}
	
	// Close file for writing
	fclose($fp);
	db_free_result($result);
	
	// Open file for downloading
	$download_filename = camelCase(html_entity_decode($app_title, ENT_QUOTES)) . "_Logging_" . date("Y-m-d_Hi") . ".csv";
	header('Pragma: anytextexeptno-cache', true);
	header("Content-type: application/csv");
	
	header("Content-Disposition: attachment; filename=$download_filename");
	
	// Open file for reading and output to user
	$fp = fopen($filename, 'rb');
	print fread($fp, filesize($filename));
	
	// Close file and delete it from temp directory
	fclose($fp);
	unlink($filename);	
	
	// Logging
	log_event($sql,"redcap_log_event","MANAGE",$project_id,"project_id = $project_id","Export entire logging record");

}
else
{
	print $lang['global_01'];
}
	