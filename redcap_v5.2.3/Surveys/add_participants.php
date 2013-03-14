<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{
	$_GET['survey_id'] = getSurveyId();
}

// Ensure the survey_id belongs to this project and that Post method was used
if (!checkSurveyProject($_GET['survey_id']))
{
	exit("0");
}

$response = "0"; //Default


if (isset($_POST['participants']) && !empty($_POST['participants']))
{
	// Process the emails/identifiers
	$invalid_emails = array();
	$participants 	= array();
	$disableIdentErrors = array();
	
	// Loop through all participants submitted
	$i = 1;
	foreach (explode("\n", trim($_POST['participants'])) as $i=>$line) 
	{
		$line = trim($line);
		if ($line != '') 
		{	
			//If line has comma, separate as email/identifier
			if (strpos($line, ",") !== false) { 				
				list ($this_email, $this_ident) = explode(",", $line, 2);
				$this_email = trim($this_email);
				$this_ident = trim($this_ident);
				// If trying to add an identifier when identifiers are disabled, give error message
				if (!$enable_participant_identifiers && $this_ident != "") {
					$disableIdentErrors[] = "$this_email, $this_ident";
				}
			} else {
				$this_email = $line;
				$this_ident = "";
			}		
			// Unescape any apostrophes in emails
			$this_email = strip_tags(label_decode($this_email));
			//Check if email is valid
			if (preg_match("/^([_a-z0-9-']+)(\.[_a-z0-9-']+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i" , $this_email)) {
				$participants[$i]['email'] 		= $this_email;
				$participants[$i]['identifier'] = $this_ident;
				$i++;
			} else {			
				$invalid_emails[] = $this_email;			
			}	
		}
	}
	
	// Give response back if trying to add an identifier when identifiers are disabled
	if (count($disableIdentErrors) > 0)
	{
		print "{$lang['survey_269']}<br><br>{$lang['survey_270']} - " 
			 . implode("<br>{$lang['survey_270']} - ", $disableIdentErrors);
		exit;
	}
	
	// Give response back if some emails are not formatted correctly
	if (count($invalid_emails) > 0)
	{
		print "{$lang['survey_157']}\n\n {$lang['survey_158']} - " 
			 . implode("\n {$lang['survey_158']} - ", $invalid_emails);
		exit;
	}
		
	// Loop through all submitted participants and add to tables
	foreach ($participants as $attr)
	{
		// Add to participant table and retrieve its hash
		setHash($_GET['survey_id'], $attr['email'], $_GET['event_id'], $attr['identifier']);
	}
	
	// Logging
	log_event("","redcap_surveys_participants","MANAGE",$_GET['survey_id'],"survey_id = {$_GET['survey_id']}\nevent_id = {$_GET['event_id']}","Add survey participants");
	
	$response = "1";
	
}

exit($response);