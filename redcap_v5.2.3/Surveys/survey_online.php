<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";
require_once APP_PATH_DOCROOT . "ProjectGeneral/form_renderer_functions.php";


// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{
	$_GET['survey_id'] = getSurveyId();
}

// Ensure the survey_id belongs to this project and that Post method was used
if (!checkSurveyProject($_GET['survey_id']))
{
	exit("[]");
}

// Defaults
$response = "";
$popup_content = "";
$popup_title = "";

// Set $enable value as opposite of current survey_enabled value
$enable = ($Proj->surveys[$_GET['survey_id']]['survey_enabled']) ? 0 : 1;

// Set survey as enabled or disabled
$sql = "update redcap_surveys set survey_enabled = $enable 
		where survey_id = {$_GET['survey_id']} and project_id = $project_id";
if (db_query($sql))
{
	// Get values for survey
	$form_name = $Proj->surveys[$_GET['survey_id']]['form_name'];
	$title = $Proj->surveys[$_GET['survey_id']]['title'];
	// Set response as HTML
	if ($enable) {
		$logmsg = "Bring survey back online";
		$surveyActiveImg = "accept.png";
		$surveyActiveClr = "green";
		$surveyActiveTxt = $lang['setup_64'];
		$surveyActiveBtn = "<button class='jqbuttonsm' style='margin-left:10px;' onclick='surveyOnline({$_GET['survey_id']});'>{$lang['setup_67']}</button>";
	} else {
		$logmsg = "Take survey offline";
		$surveyActiveImg = "delete.png";
		$surveyActiveClr = "red";
		$surveyActiveTxt = $lang['setup_65'];
		$surveyActiveBtn .= "<button class='jqbuttonsm' style='margin-left:10px;' onclick='surveyOnline({$_GET['survey_id']});'>{$lang['setup_66']}</button>";
	}
	$response = RCView::img(array('src'=>$surveyActiveImg,'class'=>'imgfix')) . 
				RCView::span(array('style'=>"color:$surveyActiveClr;"), $surveyActiveTxt) .
				$surveyActiveBtn;
	// Logging
	log_event($sql,"redcap_surveys","MANAGE",$_GET['survey_id'],"survey_id = " . $_GET['survey_id'],$logmsg);
	// Determine if survey has an expiration date set (if survey was just enabled). If expire time <= NOW, then
	// give user notice that expirate time was removed.
	$sql = "select survey_expiration from redcap_surveys where survey_id = {$_GET['survey_id']} and
			survey_enabled = 1 and survey_expiration is not null limit 1";
	$q = db_query($sql);
	if ($q && db_num_rows($q)) {
		$survey_expiration = db_result($q, 0);
		if ($survey_expiration <= NOW) {
			// Remove expirate time
			$sql = "update redcap_surveys set survey_expiration = null where survey_id = {$_GET['survey_id']}";
			db_query($sql);
			// Reformat $survey_expiration from YMDHS to MDYHS for display purposes
			list ($this_date, $this_time) = explode(" ", $survey_expiration);
			$survey_expiration = trim(date_ymd2mdy($this_date) . " " . $this_time);
			// Give user notice that expirate time was removed
			$popup_title = $lang['survey_305'];
			$popup_content = $lang['survey_306'] . " (<b>$survey_expiration</b>) " . $lang['survey_307'];
		}
	}
}

print '{"payload":"'.cleanHtml2($response).'","popup_title":"'.cleanHtml2($popup_title).'","popup_content":"'.cleanHtml2($popup_content).'"}';
