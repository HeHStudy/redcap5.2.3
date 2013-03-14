<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

// Ensure the survey_id belongs to this project and that Post method was used
if (!$Proj->validateSurveyId($_GET['survey_id'])) exit("0");

$response = "0"; //Default

// Remove from table
$sql = "delete from redcap_surveys where survey_id = {$_GET['survey_id']}";
if (db_query($sql))
{
	// Logging
	log_event($sql,"redcap_surveys","MANAGE",$_GET['survey_id'],"survey_id = {$_GET['survey_id']}","Delete survey");
	// Set response
	$response = "1";
}

print $response;