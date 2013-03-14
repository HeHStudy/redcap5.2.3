<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";
require_once APP_PATH_CLASSES . "Message.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{
	$_GET['survey_id'] = getSurveyId();
}

// Obtain current event_id
$_GET['event_id'] = getEventId();

// Ensure the survey_id belongs to this project and that Post method was used
if (!checkSurveyProject($_GET['survey_id']) || $_SERVER['REQUEST_METHOD'] != "POST" || !isset($_POST['participants']) 
	|| !isset($_POST['email_id']) || !is_numeric($_POST['email_id']))
{
	exit("[]");
}

// Check if this is a follow-up survey
$isFollowUpSurvey = ($_GET['survey_id'] != $Proj->firstFormSurveyId);

// Ensure that participant_id's are all numerical
foreach (explode(",", $_POST['participants']) as $this_part)
{
	if (!is_numeric($this_part)) exit("[]");
}

// Track how many emails get sent and how many to do in a batch
$sentcount = 0;
$num_emails_per_batch = 50;

// Trim off the first batch of participant_id's to use in this ajax call (the rest will be sent back for another call to do)
$partIdsThisBatch = explode(",", $_POST['participants'], $num_emails_per_batch+1);
$nextPartIds = $partIdsThisBatch[$num_emails_per_batch];
unset($partIdsThisBatch[$num_emails_per_batch]);


// Get email address for each participant_id (whether it's an initial survey or follow-up survey)
$participant_emails_ids = array();
if ($isFollowUpSurvey) {
	$records = getRecordFromPartId($partIdsThisBatch);
	$responseAttr = getResponsesEmailsIdentifiers($records);
	foreach ($records as $partId=>$record) {
		$participant_emails_ids[$partId] = $responseAttr[$record]['email'];
	}
} else {
	$sql = "select participant_email, participant_id from redcap_surveys_participants where survey_id = {$_GET['survey_id']}
			and participant_id in (" . implode(", ", $partIdsThisBatch) . ")";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$participant_emails_ids[$row['participant_id']] = $row['participant_email'];
	}
}
if (empty($participant_emails_ids)) exit("[]");


// Get email info (already saved in table)
$sql = "select email_subject, email_content, email_account from redcap_surveys_emails 
		where survey_id = {$_GET['survey_id']} and email_id = {$_POST['email_id']} limit 1";
$q = db_query($sql);
if (!$q) exit("[]");
if (db_num_rows($q) < 1) exit("[]");
$email_subject = label_decode(db_result($q, 0, "email_subject"));
$email_content = label_decode(db_result($q, 0, "email_content"));
$email_account = label_decode(db_result($q, 0, "email_account"));

// Set the From address for the emails sent
$fromEmailTemp = 'user_email' . ($email_account > 1 ? $email_account : '');
$fromEmail = $$fromEmailTemp;
if (!isEmail($fromEmail)) $fromEmail = $user_email;

// Get survey name
$survey_name = strip_tags(label_decode($Proj->surveys[$_GET['survey_id']]['title']));


// Initialize email
$email = new Message();
$email->setFrom($fromEmail);
$email->setSubject($email_subject);

// Get hashes of ALL participants up front before we start looping
$hashes = getParticipantHashes(array_keys($participant_emails_ids));

// Loop through all participants that we're emailing
foreach ($participant_emails_ids as $this_part=>$this_email)
{
	// Set up SQL statement to note that email was sent
	$sql = "insert into redcap_surveys_emails_recipients (email_id, participant_id) 
			values ({$_POST['email_id']}, $this_part)";
	// Make sure we have the hash for this participant and also that insert into table was successful
	if (isset($hashes[$this_part]) && db_query($sql)) 
	{
		// Get hash for this participant
		$hash = $hashes[$this_part];
		// Set up email to participant	
		$emailContents = '
			<html><body style="font-family:Arial;font-size:10pt;">
			'.nl2br($email_content).'<br /><br />	
			'.$lang['survey_134'].'<br />
			<a href="' . APP_PATH_SURVEY_FULL . '?s=' . $hash . '">'.$survey_name.'</a><br /><br />
			'.$lang['survey_135'].'<br />
			' . APP_PATH_SURVEY_FULL . '?s=' . $hash . '<br /><br />	
			'.$lang['survey_137'].'
			</body></html>';
		$email->setTo($this_email); 
		$email->setBody($emailContents);
		// Send email
		if ($email->send()) {
			// Increment counter
			$sentcount++;
		}
	}
}

// Send back JSON with info
exit('{"sentcount":' . $sentcount . ',"nextPartIds":"' . $nextPartIds . '"}');
	