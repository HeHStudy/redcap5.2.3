<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


if (!isset($_GET['survey_id']) && isset($_GET['email']) && isset($_GET['url']) && !empty($_GET['url']))
{
	define("NOAUTH", true);
}

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_CLASSES . "Message.php";	
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";


// Default response
$response = "0";



## Send email for viewing survey results
if (!isset($_GET['survey_id']) && isset($_GET['email']) && isset($_GET['url']) && !empty($_GET['url']))
{
	// Set URL to be sent within email
	$href = urldecode($_GET['url']);

	//Set the contents of the email
	$emailContents = "
	<html><body style=\"font-family:Arial;font-size:10pt;\">
	{$lang['global_21']}<br><br>
	{$lang['survey_182']}<br>
	<a href=\"$href\">{$lang['survey_167']}</a><br><br>
	{$lang['survey_135']}<br>
	$href
	</body></html>";

	//Sending email to self
	$email = new Message ();
	$email->setTo($_GET['email']); 
	$email->setFrom($_GET['email']);
	$email->setSubject('[REDCap] '.$lang['survey_183']);
	$email->setBody($emailContents);
	// If successful, send back user's email, which is included in alert msg as confirmation
	$response = ($email->send() ? "1" : "0");
}



## Survey admin sends email containing survey link to self
elseif (checkSurveyProject($_GET['survey_id']) && isset($_GET['url']) && !empty($_GET['url']))
{	
	// Get survey title
	$q = db_query("select title from redcap_surveys where survey_id = " . $_GET['survey_id']);
	$survey_title = filter_tags(label_decode(db_result($q,0)));

	// Set URL to be sent within email
	$href = urldecode($_GET['url']);

	//Set the contents of the email
	$emailContents = "
	<html><body style=\"font-family:Arial;font-size:10pt;\">
	{$lang['global_21']}<br><br>
	{$lang['survey_134']}<br>
	<a href=\"$href\">$survey_title</a><br><br>
	{$lang['survey_135']}<br>
	$href
	</body></html>";

	//Sending email to self
	$email = new Message ();
	$email->setTo($user_email); 
	$email->setFrom($user_email);
	$email->setSubject('[REDCap] '.$lang['survey_136']);
	$email->setBody($emailContents);
	// If successful, send back user's email, which is included in alert msg as confirmation
	$response = ($email->send() ? $user_email : "0");
	
	// Logging
	log_event("","redcap_surveys","MANAGE",$_GET['survey_id'],"survey_id = " . $_GET['survey_id'],"Email survey link to self");
	
}

exit($response);