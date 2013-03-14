<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Check if coming from survey or authenticated form
if (isset($_GET['s']) && !empty($_GET['s']))
{
	// Call config_functions before config file in this case since we need some setup before calling config
	require_once dirname(dirname(__FILE__)) . '/Config/init_functions.php';
	// Survey functions needed
	require_once dirname(__FILE__) . "/survey_functions.php";
	// Validate and clean the survey hash, while also returning if a legacy hash
	$hash = $_GET['s'] = checkSurveyHash();
	// Set all survey attributes as global variables
	setSurveyVals($hash);
	// Now set $_GET['pid'] before calling config
	$_GET['pid'] = $project_id;
	// Set flag for no authentication for survey pages
	define("NOAUTH", true);
}

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";
require_once APP_PATH_CLASSES . "Message.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{
	$_GET['survey_id'] = getSurveyId();
}

// Ensure the survey_id belongs to this project and that the participant_id was given
if (!checkSurveyProject($_GET['survey_id']) 
	|| !isset($_GET['participant_id']) || !is_numeric($_GET['participant_id'])
	|| !isset($_GET['event_id']) || !is_numeric($_GET['event_id'])
)
{
	exit("0");
}

// Obtain current arm_id
$_GET['arm_id'] = getArmId();

// Retrieve survey info
$sql = "select * from redcap_surveys s, redcap_surveys_participants p where s.survey_id = {$_GET['survey_id']} 
		and s.survey_id = p.survey_id and p.event_id = {$_GET['event_id']} and p.participant_id = {$_GET['participant_id']} limit 1";
$q = db_query($sql);
foreach (db_fetch_assoc($q) as $key => $value)
{
	$$key = trim(html_entity_decode($value, ENT_QUOTES));
	if ($key == 'participant_email') {
		// Determine if a public survey (using public survey link, aka participant_email=null), as opposed to a participant list response or an existing record)
		$public_survey = ($value === null);
	}
}

//If this is an emailed public survey, retrieve participant's email address from URL
if ($participant_email == "" && isset($_GET['email'])) {
	if ($_GET['email'] != "") {
		$participant_email = urldecode(trim($_GET['email']));
	} elseif (!$public_survey) {
		// If this is a follow-up survey where we don't have an email address, so return nothing (no error)
		list ($part_list, $part_list_duplicates) = getParticipantList($_GET['survey_id'], $_GET['event_id']);
		$participant_email = $part_list[$participant_id]['email'];
		if ($participant_email == "") exit("2");
	} else {
		// Return error since we can't find an email
		exit("0");
	}
}

// Set survey link
$survey_link = APP_PATH_SURVEY_FULL.'?s='.$hash;
if ($public_survey) $survey_link .= '&__return=1';

// Set email body
$emailContents = '
	<html><body style="font-family:Arial;font-size:10pt;">
	'.$lang['survey_141'].'<br><br>
	'.$lang['survey_142'].' "'.$title.'". 
	'.$lang['survey_143'].'<br><br>
	<a href="'.$survey_link.'">'.$title.'</a><br><br>
	'.$lang['survey_135'].'<br>
	'.$survey_link.'
	</body></html>';	
//Send email
$email = new Message ();
$email->setTo($participant_email); 
$email->setFrom($participant_email); // Send the email from the participant themself
$email->setSubject($lang['survey_144']);
$email->setBody($emailContents);
// Return "0" for failure or email if successful
print ($email->send() ? $participant_email : "0");
