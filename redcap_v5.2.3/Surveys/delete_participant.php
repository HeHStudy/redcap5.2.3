<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id'])) $_GET['survey_id'] = getSurveyId();
if (!isset($_GET['event_id']))  $_GET['event_id']  = getEventId();
// Ensure the survey_id belongs to this project and that Post method was used
if (!$Proj->validateEventIdSurveyId($_GET['event_id'], $_GET['survey_id']))	exit("0");

$response = "0"; //Default

if (isset($_POST['participant_id']) && is_numeric($_POST['participant_id']))
{
	// Remove from table
	$sql = "delete from redcap_surveys_participants where participant_id = {$_POST['participant_id']} 
			and survey_id = {$_GET['survey_id']}";
	if (db_query($sql))
	{
		// Logging
		log_event($sql,"redcap_surveys_participants","MANAGE",$_POST['participant_id'],"participant_id = {$_POST['participant_id']}","Delete survey participant");
		// Set response
		$response = "1";
	}
	
}

exit($response);