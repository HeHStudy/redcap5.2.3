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

// Retrieve survey info
$q = db_query("select * from redcap_surveys where project_id = $project_id and survey_id = " . $_GET['survey_id']);
foreach (db_fetch_assoc($q) as $key => $value)
{
	$$key = trim(html_entity_decode($value, ENT_QUOTES));
}

// Default
$response = '';

if (isset($_POST['participant_id']) && is_numeric($_POST['participant_id']))
{
	// Save the email address
	if (isset($_POST['email']))
	{
		$_POST['email'] = strip_tags(label_decode(trim($_POST['email'])));
		$sql = "update redcap_surveys_participants set participant_email = '" . prep($_POST['email']) . "' 
				where survey_id = {$_GET['survey_id']} and participant_id = {$_POST['participant_id']} and event_id = {$_GET['event_id']}";
		if (db_query($sql))
		{
			$response = $_POST['email'];
			// Logging
			log_event($sql,"redcap_surveys_participants","MANAGE",$_POST['participant_id'],"participant_id = " . $_POST['participant_id'],"Edit survey participant email address");
		}
	}
	// Save the identifier
	elseif (isset($_POST['identifier']))
	{
		$_POST['identifier'] = trim(strip_tags(label_decode($_POST['identifier'])));
		$sql = "update redcap_surveys_participants set participant_identifier = '" . prep($_POST['identifier']) . "' 
				where survey_id = {$_GET['survey_id']} and participant_id = {$_POST['participant_id']} and event_id = {$_GET['event_id']}";
		if (db_query($sql))
		{
			$response = $_POST['identifier'];
			// Logging
			log_event($sql,"redcap_surveys_participants","MANAGE",$_POST['participant_id'],"participant_id = " . $_POST['participant_id'],"Edit survey participant identifier");
		}
	}
}

print $response;