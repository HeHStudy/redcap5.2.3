<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Retrieve survey_id using form_name
function getSurveyId($form_name = null)
{
	global $Proj;
	if (empty($form_name)) $form_name = $Proj->firstForm;
	return (isset($Proj->forms[$form_name]['survey_id']) ? $Proj->forms[$form_name]['survey_id'] : "");
}

// Creates unique hash after checking current hashes in tables, and returns that value
function getUniqueHash()
{
	do {
		// Generate a new random hash
		$hash = generateRandomHash();
		// Ensure that the hash doesn't already exist in either redcap_surveys or redcap_surveys_hash (both tables keep a hash value)
		$sql = "select hash from redcap_surveys_participants where hash = '$hash' limit 1";
		$hashExists = (db_num_rows(db_query($sql)) > 0);
	} while ($hashExists);
	// Hash is unique, so return it
	return $hash;
}

// Creates unique return_code (that is, unique within that survey) and returns that value
function getUniqueReturnCode($survey_id=null,$response_id=null)
{
	// Make sure we have a survey_id value
	if (!is_numeric($survey_id)) return false;
	// If response_id is provided, then fetch existing return code. If doesn't have a return code, then generate one.
	if (is_numeric($response_id))
	{
		// Query to get existing return code
		$sql = "select r.return_code from redcap_surveys_participants p, redcap_surveys_response r 
				where p.survey_id = $survey_id and r.response_id = $response_id 
				and p.participant_id = r.participant_id limit 1";
		$q = db_query($sql);
		$existingCode = (db_num_rows($q) > 0) ? db_result($q, 0) : "";
		if ($existingCode != "") {
			return $existingCode;
		}
	}
	// Generate a new unique return code for this survey (keep looping till we get a non-existing unique value)
	do {
		// Generate a new random hash
		$code = strtolower(generateRandomHash(8));
		// Ensure that the hash doesn't already exist
		$sql = "select r.return_code from redcap_surveys_participants p, redcap_surveys_response r 
				where p.survey_id = $survey_id and r.return_code = '$code'
				and p.participant_id = r.participant_id limit 1";
		$q = db_query($sql);
		$codeExists = (db_num_rows($q) > 0);
	} 
	while ($codeExists);
	// If the response_id provided does not have an existing code, then save the new one we just generated
	if (is_numeric($response_id) && $existingCode == "")
	{
		$sql = "update redcap_surveys_response set return_code = '$code' where response_id = $response_id";
		$q = db_query($sql);
	}
	// Code is unique, so return it
	return $code;
}

// Creates unique results_code (that is, unique within that survey) and returns that value
function getUniqueResultsCode($survey_id=null)
{
	if (!is_numeric($survey_id)) return false;
	do {
		// Generate a new random hash
		$code = strtolower(generateRandomHash(8));
		// Ensure that the hash doesn't already exist in either redcap_surveys or redcap_surveys_hash (both tables keep a hash value)
		$sql = "select r.results_code from redcap_surveys_participants p, redcap_surveys_response r 
				where p.survey_id = $survey_id and r.results_code = '$code' limit 1";
		$codeExists = (db_num_rows(db_query($sql)) > 0);
	} while ($codeExists);
	// Code is unique, so return it
	return $code;
}

// Exit the survey and give message to participant
function exitSurvey($text, $largeFont=true)
{
	// If paths have not been set yet, call functions that set them (need paths set for HtmlPage class)
	if (!defined('APP_PATH_WEBROOT'))
	{
		// Pull values from redcap_config table and set as global variables
		setConfigVals();
		// Set directory definitions
		define_constants();	
	}
	global $isMobileDevice;
	// Class for html page display system
	$objHtmlPage = new HtmlPage();
	$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
	$objHtmlPage->addExternalJS(APP_PATH_JS . "fontsize.js");
	$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
	$objHtmlPage->addStylesheet("style.css", 'screen,print');
	$objHtmlPage->addStylesheet("survey.css", 'screen,print');
	// Mobile: Add mobile-specific stylesheets and CSS3 conditions to detect small browsers
	if ($isMobileDevice)
	{
		$objHtmlPage->addStylesheet("mobile_survey_portrait.css","only screen and (max-width: 320px)");
		$objHtmlPage->addStylesheet("mobile_survey_landscape.css","only screen and (min-width: 321px) and (max-width: 480px)");
	}	
	$objHtmlPage->PrintHeader();
	if ($largeFont) {
		print "<h3 style='margin:50px 0;font-weight:bold;'>$text</h3>";
	} else {
		print "<p style='margin:50px 0;'>$text</p>";
	}
	// Delete the session cookie, just in case
	?><script type="text/javascript">deleteCookie('survey');</script><?php
	// Footer
	$objHtmlPage->PrintFooter();
	## Destroy the session
	$_SESSION = array();
	session_unset();
	session_destroy();
	exit;
}


// Obtain the survey hash for array of participant_id's and event_id and survey_id
function getParticipantHashes($participant_id=array())
{
	// Collect hashes in array with particpant_id as key
	$hashes = array();	
	// Retrieve hashes
	$sql = "select participant_id, hash from redcap_surveys_participants 
			where participant_id in (".prep_implode($participant_id, false).")";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$hashes[$row['participant_id']] = $row['hash'];
	}
	// Return hashes
	return $hashes;
}


// Obtain the survey hash for specified event_id (return public survey hash if participant_id is not provided)
function getSurveyHash($survey_id, $event_id = null, $participant_id=null)
{
	global $Proj;
	
	// Check event_id (use first event_id in project if not provided)
	if (!is_numeric($event_id)) $event_id = $Proj->firstEventId;
	
	// Retrieve hash ("participant_email=null" means it's a public survey)
	$sql = "select hash from redcap_surveys_participants where survey_id = $survey_id and event_id = $event_id ";
	if (!is_numeric($participant_id)) {
		// Public survey
		$sql .= "and participant_email is null ";
	} else {
		// Specific participant
		$sql .= "and participant_id = $participant_id ";
	}
	$sql .= "order by participant_id limit 1";
	$q = db_query($sql);
	
	// Hash exists
	if (db_num_rows($q) > 0) {
		$hash = db_result($q, 0);
	}
	// Create hash
	else {
		$hash = setHash($survey_id, null, $event_id);
	}
	
	return $hash;
}

// Create a new survey hash [for current arm] 
function setHash($survey_id, $participant_email = null, $event_id = null, $identifier = null)
{
	// Check event_id
	if (!is_numeric($event_id)) return false;
	
	// Create unique hash
	$hash = getUniqueHash();
	$sql = "insert into redcap_surveys_participants (survey_id, event_id, participant_email, participant_identifier, hash) 
			values ($survey_id, $event_id, " . checkNull($participant_email) . ", " . checkNull($identifier) . ", '$hash')";
	$q = db_query($sql);
	
	// Return nothing if could not store hash
	return ($q ? $hash : "");
}

// Return participant_id when passed the hash
function getParticipantIdFromHash($hash=null)
{
	if ($hash == null) return false;
	$sql = "select participant_id from redcap_surveys_participants where hash = '" . prep($hash) . "' limit 1";
	$q = db_query($sql);
	// If participant_id exists, then return it
	return (db_num_rows($q) > 0) ? db_result($q, 0) : false;
}

// Create a new survey participant for followup survey (email will be '' and not null)
// Return participant_id (set $forceInsert=true to bypass the Select query if we already know it doesn't exist yet)
function getFollowupSurveyParticipantIdHash($survey_id, $record, $event_id=null, $forceInsert=false)
{
	// Check event_id
	if (!is_numeric($event_id)) return false;
	// Set flag to perform the insert query
	if ($forceInsert) {
		$doInsert = true;
	}
	// Check if participant_id for this event-record-survey exists yet
	else {
		$sql = "select p.participant_id, p.hash from redcap_surveys_participants p, redcap_surveys_response r 	
				where p.survey_id = $survey_id and p.participant_id = r.participant_id
				and p.event_id = $event_id and p.participant_email is not null
				and r.record = '".prep($record)."' limit 1";
		$q = db_query($sql);
		// If participant_id exists, then return it
		if (db_num_rows($q) > 0) {
			$participant_id = db_result($q, 0, 'participant_id');
			$hash = db_result($q, 0, 'hash');
		} else {
			$doInsert = true;
		}
	}
	// Create placeholder in participants and response tables
	if ($doInsert) {
		// Generate random hash
		$hash = getUniqueHash();
		// Since participant_id does NOT exist yet, create it. 
		$sql = "insert into redcap_surveys_participants (survey_id, event_id, participant_email, participant_identifier, hash) 
				values ($survey_id, $event_id, '', null, '$hash')";
		if (!db_query($sql)) return false;
		$participant_id = db_insert_id();	
		// Now place empty record in surveys_responses table to complete this process (sets first_submit_time as NULL - very crucial for followup)
		$sql = "insert into redcap_surveys_response (participant_id, record) values ($participant_id, '".prep($record)."')";
		if (!db_query($sql)) {
			// If query failed (likely to the fact that it already exists, which it shouldn't), then undo
			db_query("delete from redcap_surveys_participants where participant_id = $participant_id");
			// If $forceInsert flag was to true, then try with it set to false (in case there was a mistaken determining that this placeholder existed already)
			if (!$forceInsert) {
				return false;
			} else {
				// Run recursively with $forceInsert=false
				return getFollowupSurveyParticipantIdHash($survey_id, $record, $event_id);
			}
		}
	}
	// Return nothing if could not store hash
	return array($participant_id, $hash);
}

// Validate and clean the survey hash, while also returning if a legacy hash
function checkSurveyHash()
{
	global $lang, $project_language;
	// Obtain hash from GET or POST
	$hash = isset($_GET['s']) ? $_GET['s'] : (isset($_POST['s']) ? $_POST['s'] : "");
	// If could not find hash, try as legacy hash
	if (empty($hash)) {
		$hash = isset($_GET['hash']) ? $_GET['hash'] : (isset($_POST['hash']) ? $_POST['hash'] : "");
	}
	// Trim hash, just in case
	$hash = trim($hash);
	// Language: Call the correct language file for this project (default to English)
	if (empty($lang)) {
		$lang = getLanguage($project_language);
	}
	// Ensure integrity of hash, and if extra characters have been added to hash somehow, chop them off.
	if ((strlen($hash) == 6 || strlen($hash) == 5 || strlen($hash) == 4) && preg_match("/([A-Za-z0-9])/", $hash)) {
		$legacy = false;
	} elseif (strlen($hash) > 6 && strlen($hash) < 32 && preg_match("/([A-Za-z0-9])/", $hash)) {
		$hash = substr($hash, 0, 6);
		$legacy = false;
	} elseif (strlen($hash) == 32 && preg_match("/([a-z0-9])/", $hash)) {
		$legacy = true;
	} elseif (strlen($hash) > 32 && preg_match("/([a-z0-9])/", $hash)) {
		$hash = substr($hash, 0, 32);
		$legacy = true;
	} elseif (empty($hash)) {
		exitSurvey("{$lang['survey_11']}
					<a href='javascript:;' style='font-size:16px;color:#800000;' onclick=\"
						window.location.href = app_path_webroot+'Surveys/create_survey.php?pid='+getParameterByName('pid',true)+'&view=showform';
					\">{$lang['survey_12']}</a> {$lang['survey_13']}");
	} else {
		exitSurvey($lang['survey_14']);
	}
	// If legacy hash, then retrieve newer hash to return
	if ($legacy)
	{
		$q = db_query("select hash from redcap_surveys_participants where legacy_hash = '$hash'");
		if (db_num_rows($q) > 0) {
			$hash = db_result($q, 0);
		} else {
			exitSurvey($lang['survey_14']);
		}
	}
	// Return hash
	return $hash;
}

// Make sure the survey belongs to this project
function checkSurveyProject($survey_id)
{
	global $Proj;
	return (is_numeric($survey_id) && isset($Proj->surveys[$survey_id]));
}


// Pull survey values from tables and set as global variables
function setSurveyVals($hash)
{
	global $lang;
	// Ensure that hash exists. Retrieve ALL survey-related info and make all table fields into global variables
	$sql = "select * from redcap_surveys s, redcap_surveys_participants h where h.hash = '".prep($hash)."' 
			and s.survey_id = h.survey_id limit 1";
	$q = db_query($sql);
	if (!$q || !db_num_rows($q)) {
		exitSurvey($lang['survey_14']);
	}
	foreach (db_fetch_assoc($q) as $key => $value) 
	{
		$GLOBALS[$key] = ($value === null) ? $value : trim(label_decode($value));
	}
}

// Create array of field names designating their survey page with page number as key
function getPageFields($form_name, $question_by_section)
{
	global $Proj, $table_pk;
	// Set page counter at 1
	$page = 1;
	// Field counter
	$i = 1;
	// Create empty array
	$pageFields = array();
	// Loop through all form fields and designate fields to page based on location of section headers
	foreach (array_keys($Proj->forms[$form_name]['fields']) as $field_name)
	{
		// Do not include record identifier field nor form status field (since they are not shown on survey)
		if ($field_name == $table_pk || $field_name == $form_name."_complete") continue;
		// If field has a section header, then increment the page number (ONLY for surveys that have paging enabled)
		if ($question_by_section && $Proj->metadata[$field_name]['element_preceding_header'] != "" && $i != 1) $page++;
		// Add field to array
		$pageFields[$page][$i] = $field_name;
		// Increment field count
		$i++;
	}
	// Return array
	return array($pageFields, count($pageFields));
}

// Find the page number that a survey question is on based on variable name
function getQuestionPage($variable,$pageFields)
{
	$foundField = false;
	foreach ($pageFields as $this_page=>$these_fields) {
		foreach ($these_fields as $this_field) {
			if ($variable == $this_field) {
				// Found the page
				return $this_page;
			}
			if ($foundField) break;
		}
		if ($foundField) break;
	}
	// If not found, set to page 1
	return 1;
}

// Track the page number as a GET variable (not seen in query string). 
// Return the label for the Save button and array of fields to hide on this page.
function setPageNum($pageFields, $totalPages)
{
	global $table_pk, $participant_id, $return_code;
	// FIRST PAGE OF SURVEY (i.e. request method = GET)
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$_GET['__page__'] = 1;
	}
	// If returning and just entered return code, determine page based upon last field with data entered
	elseif (isset($return_code) && !empty($return_code)) {
		// Query data table for data and retrieve field with highest field order on this form 
		// (exclude calc fields because may allow participant to pass up required fields that occur earlier)
		$sql = "select m.field_name from redcap_data d, redcap_metadata m where m.project_id = " . PROJECT_ID . " 
				and d.record = ". pre_query("select record from redcap_surveys_response where return_code = '" . prep($return_code) . "' 
				and participant_id = $participant_id and completion_time is null limit 1") . " 
				and m.project_id = d.project_id and m.field_name = d.field_name and d.event_id = {$_GET['event_id']}
				and m.field_name != '$table_pk' and m.field_name != concat(m.form_name,'_complete') and m.form_name = '{$_GET['page']}'
				and m.element_type != 'calc' and d.value != '' order by m.field_order desc limit 1";
		$lastFieldWithData = db_result(db_query($sql), 0);
		// Now find the page of this field
		$_GET['__page__'] = getQuestionPage($lastFieldWithData, $pageFields);
	}
	// Reduce page number if clicked previous page button
	elseif (isset($_POST['submit-action']) && isset($pageFields[$_POST['__page__']]) && is_numeric($_POST['__page__']))
	{
		if (!isset($_GET['__reqmsg'])) {
			// PREV PAGE
			if (isset($_GET['__prevpage'])) {
				// Decrement $_POST['__page__'] value by 1
				$_GET['__page__'] = $_POST['__page__'] - 1;
			}
			// NEXT PAGE
			else {
				// Increment $_POST['__page__'] value by 1
				$_GET['__page__'] = $_POST['__page__'] + 1;
			}
		} else {
			// If reloaded page for REQUIRED FIELDS, then set Get page as Post page (i.e. no increment)
			$_GET['__page__'] = $_POST['__page__'];
		}
	}
	
	// Make sure page num is not in error
	if (!isset($_GET['__page__']) || $_GET['__page__'] < 1 || !is_numeric($_GET['__page__'])) {
		$_GET['__page__'] = 1;
	}
	
	// Set the label for the Submit button
	if ($totalPages > 1 && $totalPages != $_GET['__page__']) {
		$saveBtn = "Next Page >>";
	} else {
		$saveBtn = "Submit";
	}
	
	// Given the current page number, determine the fields on this form that should be hidden
	$hideFields = array();
	foreach ($pageFields as $this_page=>$these_fields) {
		if ($this_page != $_GET['__page__']) {
			foreach ($these_fields as $this_field) {
				$hideFields[] = $this_field;
			}
		}
	}
	
	// Return the label for the Save button and array of fields to hide on this page
	return array($saveBtn, $hideFields);
}

// Gather participant list (with identfiers and if Sent/Responded) and return as array
function getParticipantList($survey_id, $event_id = null)
{
	global $Proj, $table_pk;

	// Check event_id (if not provided, then use first one - i.e. for public surveys)
	if (!is_numeric($event_id)) $event_id = getEventId();
	// Ensure the survey_id belongs to this project
	if (!checkSurveyProject($survey_id))
	{
		redirect(APP_PATH_WEBROOT . "index.php?pid=" . PROJECT_ID);
	}
	
	// Check if this is a follow-up survey
	$isFollowUpSurvey = !($survey_id == $Proj->firstFormSurveyId && $Proj->isFirstEventIdInArm($event_id));
	
	// If a followup survey, go ahead and pre-populate the participants table and responses table with row for each record (if not already there)
	// This must be done in order for the follow-up survey's participant list to display fully
	if ($isFollowUpSurvey)
	{
		// Find all records NOT in participants table yet for this followup survey
		$sub = "select r.record from redcap_surveys_participants p, redcap_surveys_response r 
				where p.survey_id = $survey_id and r.participant_id = p.participant_id 
				and p.event_id = $event_id and p.participant_email is not null";
		$sql = "select distinct record from redcap_data where project_id = " . PROJECT_ID . " 
				and field_name = '$table_pk' and record not in (" . pre_query($sub) . ")";
		if (count($Proj->events) > 1) {
			// Multiple arms exist, so only query records in current arm
			$eventIdsThisArm = array();
			foreach ($Proj->events as $this_arm_num=>$arm_attr) {
				if (isset($arm_attr['events'][$event_id])) {
					$eventIdsThisArm = array_keys($arm_attr['events']);
					break;
				}
			}
			if (!empty($eventIdsThisArm)) {
				$sql .= " and event_id in (".prep_implode($eventIdsThisArm).")";
			}
		}
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Add row to participants table and responses table for this record (return value not needed here)
			getFollowupSurveyParticipantIdHash($survey_id, $row['record'], $event_id, true);
		}
	}
	
	// Build participant list
	$part_list = array();
	$sql = "select p.* from redcap_surveys_participants p, redcap_surveys s 
			where p.survey_id = $survey_id and s.survey_id = p.survey_id and p.event_id = $event_id
			and p.participant_email is not null
			order by abs(p.participant_email), p.participant_email, p.participant_id";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		// Set with email, identifier, and basic defaults for counts
		$part_list[$row['participant_id']] = array( 'record'=>'', 'email'=>$row['participant_email'], 'identifier'=>$row['participant_identifier'], 
													'hash'=>$row['hash'], 'sent' =>0, 'response'=>0, 'return_code'=>'', 'scheduled'=>'');
	}

	// Query email invitations sent
	$sql = "select p.participant_id from redcap_surveys_emails e1, redcap_surveys_emails_recipients r, redcap_surveys_participants p 
			where e1.survey_id = $survey_id and e1.email_id = r.email_id and p.survey_id = e1.survey_id 
			and p.participant_id = r.participant_id and p.event_id = $event_id and e1.email_sent is not null";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		$part_list[$row['participant_id']]['sent'] = 1;
	}

	// Query for any responses AND return codes
	$saveAndReturnEnabled = ($Proj->surveys[$survey_id]['save_and_return']);
	$sql = "select p.participant_id, r.first_submit_time, r.completion_time, r.return_code, r.record, p.participant_email
			from redcap_surveys_participants p, redcap_surveys_response r
			where p.survey_id = $survey_id and r.participant_id = p.participant_id and p.participant_email is not null 
			and p.event_id = $event_id";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{	
		$part_list[$row['participant_id']]['record'] = $row['record'];
		if ($row['participant_email'] === null) {
			// Initial survey
			$part_list[$row['participant_id']]['response'] = ($row['completion_time'] == "" ? 1 : 2);
		} else {
			// Followup surveys (participant_email will be '' not null)
			if ($row['completion_time'] == "" && $row['first_submit_time'] == "") {
				$part_list[$row['participant_id']]['response'] = 0;
			} elseif ($row['completion_time'] == "" && $row['first_submit_time'] != "") {
				$part_list[$row['participant_id']]['response'] = 1;
			} else {
				$part_list[$row['participant_id']]['response'] = 2;
			}
		}
		// If save and return enabled, then include return code, if exists.
		if ($saveAndReturnEnabled) {
			$part_list[$row['participant_id']]['return_code'] = $row['return_code'];
		}
	}

	// SCHEDULED: Query for any responses that have been scheduled via the Invitation Scheduler
	$sql = "select p.participant_id, q.scheduled_time_to_send from redcap_surveys_participants p, 
			redcap_surveys_scheduler_queue q, redcap_surveys_emails_recipients r where p.survey_id = $survey_id 
			and p.event_id = $event_id and p.participant_email is not null and q.email_recip_id = r.email_recip_id 
			and p.participant_id = r.participant_id and q.status = 'QUEUED'";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{	
		$part_list[$row['participant_id']]['scheduled'] = $row['scheduled_time_to_send'];
	}
	
	## OBTAIN EMAIL ADDRESSES FOR FOLLOWUP SURVEYS (SINCE THEY DON'T HAVE THEM NATURALLY)
	// Follow-up surveys will not have an email in the participants table, so pull from initial survey's Participant List (if exists there)
	if ($isFollowUpSurvey)
	{
		// Store record as key so we can retrieve this survey's participan_id for this record later
		$partRecords = array();		
		foreach ($part_list as $this_part=>$attr) {
			$partRecords[$attr['record']] = $this_part;
		}
		// Get all participant attributes for this followup survey
		$participantAttributes = getResponsesEmailsIdentifiers(array_keys($partRecords));
		// Now use that record list to get the original email from first survey's participant list
		foreach ($participantAttributes as $record=>$attr) {
			if (isset($part_list[$partRecords[$record]])) {
				$part_list[$partRecords[$record]]['email'] = $attr['email'];
				$part_list[$partRecords[$record]]['identifier'] = $attr['identifier'];
			}
		}		
		## Since we added the emails addresses at the end, we now need to re-sort the array by email address
		// Loop through participants and add participant_id and email to separate arrays
		$orderEmail  = array();
		$orderPartId = array();
		foreach ($part_list as $this_part_id=>$attr) {
			// Convert all keys to strings so that multisort preserves the indexes
			unset($part_list[$this_part_id]);
			$part_list[" $this_part_id"] = $attr;
			// Add values to arrays
			$orderEmail[]  = $attr['email'];
			$orderPartId[] = $this_part_id;
		}
		// Now do a multisort
		array_multisort($orderEmail, SORT_STRING, $orderPartId, SORT_NUMERIC, $part_list);
		// Now fix the array keys, which got padded with spaces to preserve indexes during the multisort
		foreach ($part_list as $this_part_id=>$attr) {
			unset($part_list[$this_part_id]);
			$part_list[trim($this_part_id)] = $attr;
		}		
	}
	
	// DUPLICATE EMAIL ADDRESSES: Track when there are email duplicates so we can pre-pend with #) when displaying it multiple times in table
	$part_list_duplicates = array();
	foreach ($part_list as $this_part_id=>$attr) {
		if ($attr['email'] == '') continue;
		if (isset($part_list_duplicates[$attr['email']])) {
			$part_list_duplicates[$attr['email']]['total']++;
		} else {
			$part_list_duplicates[$attr['email']]['total'] = 1;
			$part_list_duplicates[$attr['email']]['current'] = 1;
		}
	}
	
	// print_array($part_list);
	
	// Return array
	return array($part_list, $part_list_duplicates);
}

// Returns array of emails and identifiers for a list of records for a follow-up survey
function getResponsesEmailsIdentifiers($records=array())
{
	global $Proj, $survey_email_participant_field;
	
	// If pass in empty array of records, pass back empty array
	if (empty($records)) return array();
	
	// Get the first event_id of every Arm and place in array
	$firstEventIds = array();
	foreach ($Proj->events as $this_arm_num=>$arm_attr) {
		$firstEventIds[] = print_r(array_shift(array_keys($arm_attr['events'])), true);
	}
	
	// Create an array to return with participant_id as key and attributes as subarray
	$responseAttributes = array();
	// Pre-fill with all records passed in first
	foreach ($records as $record) {
		if ($record == '') continue;
		$responseAttributes[label_decode($record)] = array('email'=>'','identifier'=>'');
	}
	
	## GET EMAILS FROM INITIAL SURVEY'S PARTICIPANT LIST (if there is an initial survey)
	if ($Proj->firstFormSurveyId != null)
	{
		// Create record list to query participant table. Escape the record names for the query.
		$partRecordsSql = array();
		foreach ($records as $record) {
			if ($record == '') continue;
			$partRecordsSql[] = label_decode($record);
		}
		// Now use that record list to get the original email from first survey's participant list	
		$sql = "select r.record, p.participant_email, p.participant_identifier 
				from redcap_surveys_participants p, redcap_surveys_response r, redcap_surveys s 
				where s.project_id = ".PROJECT_ID." and p.survey_id = s.survey_id and p.participant_id = r.participant_id 
				and r.record in (".prep_implode($partRecordsSql).") and s.form_name = '".$Proj->firstForm."' 
				and p.event_id in (".prep_implode($firstEventIds).") and p.participant_email is not null and p.participant_email != ''";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$responseAttributes[label_decode($row['record'])] = array('email'=>label_decode($row['participant_email']),
																 'identifier'=>strip_tags(label_decode($row['participant_identifier'])));
		}
	}
	
	## GET ANY REMAINING MISSING EMAILS FROM SPECIAL EMAIL FIELD IN REDCAP_PROJECTS TABLE
	if ($survey_email_participant_field != '')
	{
		// Create record list of responses w/o emails to query data table. Escape the record names for the query.
		$partRecordsSql = array();
		foreach ($responseAttributes as $record=>$attr) {
			if ($attr['email'] != '') continue;
			$partRecordsSql[] = label_decode($record);
		}
		// Now use that record list to get the email value from the data table
		$sql = "select record, value from redcap_data where project_id = ".PROJECT_ID." 
				and field_name = '".prep($survey_email_participant_field)."' 
				and record in (".prep_implode($partRecordsSql).") and value != ''";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$email = trim(label_decode($row['value']));
			// Don't use it unless it's a valid email address
			if (isEmail($email)) {
				$responseAttributes[label_decode($row['record'])]['email'] = $email;
			}
		}
	}
	
	// Return array
	return $responseAttributes;
}

// Returns array of record names from an array of participant_ids (with participant_id as array key)
// NOTE: For FOLLOWUP SURVEYS ONLY (assumes row exists in response table)
function getRecordFromPartId($partIds=array())
{
	$records = array();
	$sql = "select p.participant_id, r.record from redcap_surveys_participants p, redcap_surveys_response r
			where r.participant_id = p.participant_id and p.participant_id in (".prep_implode($partIds, false).")
			order by abs(r.record), r.record";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{	
		$records[$row['participant_id']] = $row['record'];
	}
	return $records;
}

// For SURVEYS ONLY, check if IP address has been banned. If so, stop everything NOW.
function checkBannedIp()
{
	// Get IP
	$ip = getIpAddress();
	// Check for IP in banned IP table
	$q = db_query("select 1 from redcap_surveys_banned_ips where ip = '".prep($ip)."' limit 1");
	if (db_num_rows($q) > 0)
	{
		// End survey now to prevent using further server resources (in case of attack)
		exit("Your IP address ($ip) has been banned due to suspected abuse.");
	}
}

// Send emails to survey admins when a survey is completed, if enabled for any admin
function sendEndSurveyEmails($survey_id,$event_id,$survey_title,$participant_id,$record)
{
	global $redcap_version, $lang;
	// Check if any emails need to be sent and to whom
	$sql = "select distinct(trim(u.user_email)) as user_email from redcap_actions a, redcap_user_information u, redcap_user_rights r 
			where a.project_id = " . PROJECT_ID . " and a.survey_id = $survey_id and a.project_id = r.project_id 
			and r.username = u.username and a.action_trigger = 'ENDOFSURVEY' and a.action_response = 'EMAIL' and u.ui_id = a.recipient_id";
	$q = db_query($sql);
	if (db_num_rows($q) > 0)
	{	
		// If this participant has an identifier, display identifier name in email
		$identifier = "";
		$sql = "select participant_identifier from redcap_surveys_participants where participant_identifier is not null 
				and participant_identifier != '' and participant_id = $participant_id limit 1";
		$q2 = db_query($sql);
		if (db_num_rows($q2) > 0) {
			$identifier = "(" . db_result($q2, 0) . ") ";
		}
		// Initialize email
		$emailContents = "
			{$lang['survey_15']} {$identifier}{$lang['survey_16']} \"<b>$survey_title</b>\" {$lang['global_51']} ".date('m/d/Y g:ia').". 	
			{$lang['survey_17']} <a href='".APP_PATH_WEBROOT_FULL."redcap_v{$redcap_version}/DataEntry/index.php?pid=".PROJECT_ID."&page={$_GET['page']}&event_id=$event_id&id=$record'>{$lang['survey_18']}</a>{$lang['period']}<br><br>	
			{$lang['survey_371']} <a href='".APP_PATH_WEBROOT_FULL."redcap_v{$redcap_version}/Design/online_designer.php?pid=".PROJECT_ID."'>{$lang['design_25']}</a> 
			{$lang['survey_20']}";
		$email = new Message();	
		$email->setSubject('[REDCap] '.$lang['survey_21']);
		$email->setBody($emailContents,true);
		// Loop through all applicable admins and send email to each
		while ($row = db_fetch_assoc($q))
		{
			$email->setTo($row['user_email']); 
			$email->setFrom($row['user_email']); // Have it send it from themself
			$email->send();
		}
	}
}

// Encrypt the survey participant's response id as a hash
function encryptResponseHash($response_id, $participant_id)
{
	global $__SALT__;
	return md5($__SALT__ . $response_id) . md5($__SALT__ . $participant_id);	
}

// Decrypt the survey participant's response hash as the response id
function decryptResponseHash($hash, $participant_id)
{
	global $__SALT__;
	// Make sure it's 64 chars long
	if (empty($hash) || (!empty($hash) && strlen($hash) != 64)) return '';
	// Break into two pieces
	$response_id_hash = substr($hash, 0, 32);
	$participant_id_hash = substr($hash, 32);
	// Verify participant_id value
	if ($participant_id_hash != md5($__SALT__ . $participant_id)) return '';
	// Now we must find the response_id by running a query to find it using one-way md5 hashing
	$sql = "select response_id from redcap_surveys_response where participant_id = $participant_id 
			and md5(concat('$__SALT__',response_id)) = '$response_id_hash' limit 1";
	$q = db_query($sql);
	if ($q) {
		// Return the response_id
		return db_result($q, 0);
	}
	return '';
}

// Obtain the response_hash value from the results code in the query string
function getResponseHashFromResultsCode($results_code, $participant_id)
{
	$sql = "select response_id from redcap_surveys_response where participant_id = $participant_id 
			and results_code = '".prep($results_code)."' limit 1";
	$q = db_query($sql);
	if ($q && db_num_rows($q))
	{
		$response_id = db_result($q, 0);
		if (is_numeric($response_id)) {
			return encryptResponseHash($response_id, $participant_id);
		}
	}
	return '';
}

// Encrypt the page number __page__ on the form in order to later verify against the real value
function getPageNumHash($page)
{
	global $__SALT__;
	return md5($__SALT__ . $page . $__SALT__);
}

// Verify that the page number hash is correct for the page number sent via Post
function verifyPageNumHash($hash, $page)
{
	return ($hash == getPageNumHash($page));
}

// GET RESPONSE ID: If $_POST['__response_hash__'] exists and is not empty, then set $_POST['__response_id__']
function initResponseId()
{
	global $participant_id;
	// If somehow __response_id__ was posted on form (it should NOT), then remove it here
	unset($_POST['__response_id__']);
	// If response_hash exists, convert to response_id
	if (isset($_POST['__response_hash__']) && !empty($_POST['__response_hash__']))
	{
		$_POST['__response_id__'] = decryptResponseHash($_POST['__response_hash__'], $participant_id);
		// Somehow it failed to get response_id, then unset it
		if (empty($_POST['__response_id__'])) unset($_POST['__response_id__']);
	}
}

// CHECK POSTED PAGE NUMBER (verify if correct to prevent gaming the system)
function initPageNumCheck()
{
	if (isset($_POST['__page__']))
	{
		if (!isset($_POST['__page_hash__']) || (isset($_POST['__page_hash__']) && !verifyPageNumHash($_POST['__page_hash__'], $_POST['__page__'])))
		{
			// Could not verify page hash, so set to 0 (so gets set to page 1)
			$_POST['__page__'] = 0;
		}
	}
	// Remove page_hash from Post
	unset($_POST['__page_hash__']);
	// If someone manually inserts __page__ into query string, then remove (illegal)
	unset($_GET['__page__']);
}

// PREVIEW PANE: If user in viewing in Preview mode, then give notification bar at top of page
function renderPreviewPane()
{
	global $hash, $lang, $question_by_section;
	if (isset($_GET['preview']) && $_GET['preview'] == '1')
	{
		?>
		<script type="text/javascript">
		$(function(){
			setTimeout(function(){
				$('#previewMsg').css({'display':'none','visibility':'visible'}).show('fade','slow');
			},500);
		});
		</script>
		<div id="previewMsg" class="tooltip3" style="display:block;visibility:hidden;">
			<table cellspacing="0" width="100%">
				<tr>
					<td valign="top">
						<b><?php echo $lang['survey_105'] ?></b> <?php echo $lang['survey_129'] ?>&nbsp;
						<a href="<?php echo PAGE_FULL . "?s=$hash" ?>" style="color:red;"><?php echo $lang['survey_130'] ?></a>
						<?php if ($question_by_section) { ?>
							<div style="color:#ddd;padding-top:5px;line-height:12px;"><?php echo $lang['survey_311'] ?></div>
						<?php } ?>
					</td>
					<td valign="top" style="padding:0 20px;text-align:right;">
						<a href="javascript:;" onclick="$('#previewMsg').hide('blind');" style="text-decoration:none;color:#fff;font-weight:bold;">[X]&nbsp;<?php echo $lang['survey_131'] ?></a>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}


// Returns boolean with regard to if the record-survey-event is a completed survey response 
// (as opposed to a regular record or a partial response)
function isResponseCompleted($survey_id = null, $record = null, $event_id = null)
{
	// Check event_id/survey_id
	if (!is_numeric($event_id) || !is_numeric($survey_id)) return false;
	// Query response table	
	$sql = "select 1 from redcap_surveys_participants p, redcap_surveys_response r
			where r.participant_id = p.participant_id and p.survey_id = $survey_id
			and p.event_id = $event_id and r.record = '" . prep($record) . "'
			and r.completion_time is not null limit 1";
	$q = db_query($sql);
	// Return true if found the response to be completed
	return (db_num_rows($q) > 0);
}

// TESTING ONLY: REMOVE THIS LATER
function savePrevFakeRecord($fetched, $participant_id, $completion_time)
{
	global $table_pk;
	// Delete all project data first to prevent issues
	db_query("delete from redcap_data where project_id = ".PROJECT_ID);	
	db_query("delete from redcap_surveys_response where participant_id = $participant_id and record < '$fetched'");
	// Set to prev record and save it
	$fetched--;
	$_POST[$table_pk] = $fetched;
	saveRecord($fetched);
	$sql = "insert into redcap_surveys_response (participant_id, record, first_submit_time, completion_time) 
			values (" . checkNull($participant_id) . ", " . checkNull($fetched) . ", '".NOW."', $completion_time)";
	db_query($sql);
	// print "<br>$sql";
	// print "<br>".db_error();
	// print "<br>Inserted record $fetched into response table as response_id ".db_insert_id();
	// Set back to currrent record
	$fetched++;
	$_POST[$table_pk] = $fetched;

}

// REMOVE QUEUED SURVEY INVITATIONS
// If any participants have already been scheduled, then remove all those instances so they can be 
// scheduled again here (first part of query returns those where record=null - i.e. from initial survey 
// Participant List, and second part return those that are existing records).
function removeQueuedSurveyInvitations($survey_id, $event_id, $email_ids=array())
{
	$deleteErrors = 0;
	if (!empty($email_ids))
	{
		$email_recip_ids_delete = array();
		$sql = "(select e.email_recip_id from redcap_surveys_participants p, redcap_surveys_scheduler_queue q, 
				redcap_surveys_emails_recipients e where p.survey_id = $survey_id and p.event_id = $event_id 
				and p.participant_email is not null and q.email_recip_id = e.email_recip_id 
				and p.participant_id = e.participant_id and q.status = 'QUEUED' 
				and p.participant_id in (".prep_implode($email_ids, false)."))
				union
				(select e.email_recip_id from redcap_surveys_participants p, redcap_surveys_response r, 
				redcap_surveys_scheduler_queue q, redcap_surveys_emails_recipients e where p.survey_id = $survey_id 
				and p.event_id = $event_id and r.participant_id = p.participant_id and p.participant_email is not null 
				and q.email_recip_id = e.email_recip_id and p.participant_id = e.participant_id and r.record = q.record 
				and q.status = 'QUEUED' and p.participant_id in (".prep_implode($email_ids, false)."))";
		$q = db_query($sql);
		if (db_num_rows($q) > 0) 
		{
			// Gather all ssq_id's and email_recip_id's into arrays so we know what to delete
			while ($row = db_fetch_assoc($q)) {
				$email_recip_ids_delete[] = $row['email_recip_id'];
			}
			// Delete those already scheduled in redcap_surveys_emails_recipients (this will cascade to also delete in redcap_surveys_scheduler_queue)
			$sql = "delete from redcap_surveys_emails_recipients where email_recip_id 
					in (".implode(",", $email_recip_ids_delete).")";
			if (!db_query($sql)) $deleteErrors++;
		}
	}
	// Return false if errors occurred
	return ($deleteErrors > 0);
}