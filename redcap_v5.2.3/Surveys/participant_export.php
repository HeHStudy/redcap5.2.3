<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{
	$_GET['survey_id'] = getSurveyId();
}

// Ensure the survey_id belongs to this project
if (!checkSurveyProject($_GET['survey_id']))
{
	redirect(APP_PATH_WEBROOT . "index.php?pid=" . PROJECT_ID);
}

// Retrieve survey info
$q = db_query("select * from redcap_surveys where project_id = $project_id and survey_id = " . $_GET['survey_id']);
foreach (db_fetch_assoc($q) as $key => $value)
{
	$$key = trim(html_entity_decode($value, ENT_QUOTES));
}

// Obtain current arm_id
$_GET['event_id'] = getEventId();
$_GET['arm_id'] = getArmId();

// Check if this is a follow-up survey
$isFollowUpSurvey = ($_GET['survey_id'] != $Proj->firstFormSurveyId);

// Gather participant list (with identfiers and if Sent/Responded)
list ($part_list, $part_list_duplicates) = getParticipantList($_GET['survey_id'], $_GET['event_id']);

// Set file name and path
$filename = APP_PATH_TEMP . date("YmdHis") . '_' . PROJECT_ID . '_participants.csv';

// Add headers for CSV file
$headers = array($lang['control_center_56'], $lang['survey_69'], $lang['survey_46'], $lang['survey_47']);

// Begin writing file from query result
$fp = fopen($filename, 'w');

if ($fp) 
{
	// Write headers to file
	fputcsv($fp, $headers);
	
	// Set values for this row and write to file
	foreach ($part_list as $row)
	{		
		// If this is the initial survey AND response was not created via Participant List, then do NOT display it here
		if (!$isFollowUpSurvey && $row['email'] == '') {
			continue;
		}
		// Remove the survey URL hash and return code elements (not needed here)
		unset($row['hash'],$row['return_code'],$row['record'],$row['scheduled']);
		// Decode the identifier
		if ($row['identifier'] != "") {
			$row['identifier'] = label_decode($row['identifier']);
		}
		// Convert boolean to text
		$row['sent'] = ($row['sent'] == '1') ? $lang['design_100'] : $lang['design_99'];
		switch ($row['response']) {
			case '2':
				$row['response'] = $lang['design_100'];
				break;
			case '1':
				$row['response'] = $lang['survey_27'];
				break;
			default:
				$row['response'] = $lang['design_99'];
		}
		// Add row to CSV
		fputcsv($fp, $row);
	}
	
	// Close file for writing
	fclose($fp);
	
	// Open file for downloading
	$download_filename = camelCase(html_entity_decode($app_title, ENT_QUOTES)) . "_Participants_" . date("Y-m-d_Hi") . ".csv";
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
	log_event("","redcap_surveys_participants","MANAGE",$_GET['survey_id'],"survey_id = {$_GET['survey_id']}\narm_id = {$_GET['arm_id']}","Export survey participant list");

}
else
{
	print $lang['global_01'];
}
