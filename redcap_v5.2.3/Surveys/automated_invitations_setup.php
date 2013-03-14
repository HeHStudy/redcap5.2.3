<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id'])) $_GET['survey_id'] = getSurveyId();

// Obtain current event_id
$_GET['event_id'] = getEventId();

// Ensure the survey_id belongs to this project and that Post method was used
if (!(isset($_GET['survey_id']) && checkSurveyProject($_GET['survey_id']) && isset($_GET['event_id']) 
	&& isset($Proj->eventInfo[$_GET['event_id']])) || $_SERVER['REQUEST_METHOD'] != "POST") {
	exit("0");
}

// Instantiate scheduler object
$surveyScheduler = new SurveyScheduler();

// Initialize vars
$popupContent = $popupTitle = "";
$response = "0";


## DISPLAY DIALOG CONTENT
if (isset($_POST['action']) && $_POST['action'] == 'view')
{
	// Obtain the HTML for the setup table
	$popupContent = $surveyScheduler->renderConditionalInviteSetupTable($_GET['survey_id'], $_GET['event_id']);
	// Set dialog title
	$popupTitle = RCView::img(array('src'=>'clock_pencil.png','class'=>'imgfix','style'=>'margin-right:3px;')) . $lang['survey_337'];
	// Set response as successful
	$response = "1";
}

## SAVE SCHEDULE
elseif (isset($_POST['action']) && $_POST['action'] == 'save')
{
	// Decode posted values
	foreach ($_POST as &$val) $val = html_entity_decode($val, ENT_QUOTES);

	// Convert exact datetime format to Y-M-D H:M
	if ($_POST['condition_send_time_exact'] != '') {
		list ($this_date, $this_time) = explode(" ", $_POST['condition_send_time_exact']);
		$_POST['condition_send_time_exact'] = trim(date_mdy2ymd($this_date) . " $this_time:00");
	}
	// Check email address
	$_POST['email_sender'] = label_decode($_POST['email_sender']);
	if (!isEmail($_POST['email_sender'])) $_POST['email_sender'] = "";
	
	// Insert into table (or update table if already exists)
	$sql = "insert into redcap_surveys_scheduler 
			(survey_id, event_id, email_subject, email_content, email_sender, condition_send_time_exact, 
			condition_surveycomplete_survey_id, condition_surveycomplete_event_id, condition_logic, 
			condition_send_time_option, condition_send_next_day_type, condition_send_next_time, 
			condition_send_time_lag_days, condition_send_time_lag_hours, condition_send_time_lag_minutes, condition_andor, active) 
			values
			({$_GET['survey_id']}, {$_GET['event_id']}, ".checkNull($_POST['email_subject']).", ".checkNull($_POST['email_content']).", ".checkNull($_POST['email_sender']).", ".checkNull($_POST['condition_send_time_exact']).", 
			".checkNull($_POST['condition_surveycomplete_survey_id']).", ".checkNull($_POST['condition_surveycomplete_event_id']).", ".checkNull($_POST['condition_logic']).",  
			".checkNull($_POST['condition_send_time_option']).", ".checkNull($_POST['condition_send_next_day_type']).", ".checkNull($_POST['condition_send_next_time']).", 
			".checkNull($_POST['condition_send_time_lag_days']).", ".checkNull($_POST['condition_send_time_lag_hours']).", ".checkNull($_POST['condition_send_time_lag_minutes']).",
			".checkNull($_POST['condition_andor']).", ".checkNull($_POST['active']).") 
			on duplicate key update
			email_subject = ".checkNull($_POST['email_subject']).", email_content = ".checkNull($_POST['email_content']).", email_sender = ".checkNull($_POST['email_sender']).",
			condition_send_time_exact = ".checkNull($_POST['condition_send_time_exact']).", condition_surveycomplete_survey_id = ".checkNull($_POST['condition_surveycomplete_survey_id']).", 
			condition_surveycomplete_event_id = ".checkNull($_POST['condition_surveycomplete_event_id']).", condition_logic = ".checkNull($_POST['condition_logic']).", 
			condition_send_time_option = ".checkNull($_POST['condition_send_time_option']).", condition_send_next_day_type = ".checkNull($_POST['condition_send_next_day_type']).", 
			condition_send_next_time = ".checkNull($_POST['condition_send_next_time']).", condition_send_time_lag_days = ".checkNull($_POST['condition_send_time_lag_days']).", 
			condition_send_time_lag_hours = ".checkNull($_POST['condition_send_time_lag_hours']).", condition_send_time_lag_minutes = ".checkNull($_POST['condition_send_time_lag_minutes']).",
			condition_andor = ".checkNull($_POST['condition_andor']).", active = ".checkNull($_POST['active']);
	if (db_query($sql)) 
	{
		// Set successful response
		$response = "1";
		
		// Logging
		$existingSchedule = (db_affected_rows() != 1);
		$logDescrip = ($existingSchedule) ? "Edit settings for automated survey invitations" : "Add settings for automated survey invitations";
		log_event($sql,"redcap_surveys_scheduler","MANAGE",$project_id,"survey_id = {$_GET['survey_id']}\nevent_id = {$_GET['event_id']}",$logDescrip);
		
		// Now set output that will be returned and displayed in a dialog as confirmation
		$popupTitle = $lang['survey_408'];
		$popupContent = RCView::div(array('style'=>'color:green;'),
							RCView::img(array('src'=>'tick.png','class'=>'imgfix')) . 
							$lang['survey_409'] . 
							" <b>\"" . $Proj->surveys[$_GET['survey_id']]['title'] . "\" " .
							(!$longitudinal ? '' : ' - ' . $Proj->eventInfo[$_GET['event_id']]['name_ext']) . "</b>" .
							$lang['period']
						);
	}
}



// Send back JSON response
print '{"response":"' . $response . '","popupContent":"' . cleanHtml2($popupContent) 
	. '","popupTitle":"' . cleanHtml2($popupTitle) . '"}';
	