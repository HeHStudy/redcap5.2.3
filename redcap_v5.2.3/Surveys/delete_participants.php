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

// Delete the participant from the participants table (for this survey-event only)
$sql = "delete from redcap_surveys_participants where event_id = {$_GET['event_id']} and survey_id = {$_GET['survey_id']}
		and participant_email is not null";
if (db_query($sql))
{
	// Logging
	log_event($sql,"redcap_surveys_participants","MANAGE",$_GET['survey_id'],"survey_id = {$_GET['survey_id']}\nevent_id = {$_GET['event_id']}","Delete all survey participants");
	// Set response
	$response = "1";
}
	

exit($response);