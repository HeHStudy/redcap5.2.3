<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/* 
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{
	$_GET['survey_id'] = getSurveyId();
}

// Ensure the survey_id belongs to this project
if (!checkSurveyProject($_GET['survey_id']))
{
	redirect(APP_PATH_WEBROOT . "Surveys/create_survey.php?pid=" . PROJECT_ID);
}

// Retrieve survey info
$q = db_query("select * from redcap_surveys where project_id = $project_id and survey_id = " . $_GET['survey_id']);
foreach (db_fetch_assoc($q) as $key => $value)
{
	$$key = trim(html_entity_decode($value, ENT_QUOTES));
}

// Loop through all arms, displaying each arm as a different table
foreach ($Proj->events as $this_arm=>$arm_attr)
{
	// Obtain current arm name and event_id's for queries
	$armNameFull = " {$lang['data_entry_67']} <span style='color:#800000;'>{$lang['global_08']} $this_arm{$lang['colon']} " . $arm_attr['name'] . "</span>";
	$eventIdSql = implode(", ", array_keys($arm_attr['events']));

	// Obtain count of responses for this survey
	$sql = "select count(1) from (select distinct r.participant_id, r.record from redcap_surveys_participants p, redcap_surveys_response r where p.participant_id = r.participant_id 
			and p.survey_id = $survey_id and p.event_id in ($eventIdSql)) x";
	$survRespTotal = db_result(db_query($sql), 0);

	// Obtain count of partial responses for this survey
	$sql = "select count(1) from (select distinct r.participant_id, r.record from redcap_surveys_participants p, redcap_surveys_response r where p.participant_id = r.participant_id 
			and p.survey_id = $survey_id and p.event_id in ($eventIdSql) and r.completion_time is null) x";
	$survRespPartial = db_result(db_query($sql), 0);

	// Obtain count of complete responses
	$survRespComplete = $survRespTotal - $survRespPartial;

	// Obtain count of total email invitations sent
	$sql = "select count(distinct(p.participant_id)) from redcap_surveys_emails_recipients r, redcap_surveys_participants p,
			redcap_surveys_emails e	where e.email_id = r.email_id and e.email_sent is not null 
			and p.survey_id = $survey_id and p.event_id in ($eventIdSql) and p.participant_id = r.participant_id";
	$survSentTotal = db_result(db_query($sql), 0);

	// Obtain count of invitations that responded
	$sql = "select count(1) from (select distinct r.participant_id, r.record 
			from redcap_surveys_participants p, redcap_surveys_response r, redcap_surveys_emails_recipients e,
			redcap_surveys_emails se where se.email_id = e.email_id and se.email_sent is not null			
			and p.survey_id = $survey_id and p.event_id in ($eventIdSql) and p.participant_id = r.participant_id 
			and p.participant_id = e.participant_id and p.participant_email is not null and p.participant_email != '') x";
	$survSentResp = db_result(db_query($sql), 0);

	// Obtain count of unresponded
	$survSentUnresp = $survSentTotal - $survSentResp;

	// Render table
	$col_widths_headers = array(
							array(225,  "col1"),
							array(50,   "col2", "center")
						);
	$row_data = array(
					array($lang['survey_26'], number_format($survRespTotal, 0, ".", ",")),
					array("&nbsp;&nbsp;&nbsp; - ".$lang['survey_27'], number_format($survRespPartial, 0, ".", ",")),
					array("&nbsp;&nbsp;&nbsp; - ".$lang['survey_28'], number_format($survRespComplete, 0, ".", ",")),
					array($lang['survey_29'], number_format($survSentTotal, 0, ".", ",")),
					array("&nbsp;&nbsp;&nbsp; - ".$lang['survey_30'], number_format($survSentResp, 0, ".", ",")),
					array("&nbsp;&nbsp;&nbsp; - ".$lang['survey_31'], number_format($survSentUnresp, 0, ".", ","))
				);
	
	print "<br>";
	
	renderGrid("response_summary", $lang['survey_32'].($multiple_arms ? $armNameFull : ""), 300, "auto", $col_widths_headers, $row_data, false);
}
 */