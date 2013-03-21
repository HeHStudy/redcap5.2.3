<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Set flag for no authentication for survey pages
define("NOAUTH", true);
// Call config_functions before config file in this case since we need some setup before calling config
require_once dirname(dirname(__FILE__)) . '/Config/init_functions.php';
// Survey functions needed
require_once dirname(__FILE__) . "/survey_functions.php";
// Check if user's IP has been banned
checkBannedIp();
// Validate and clean the survey hash, while also returning if a legacy hash
$hash = $_GET['s'] = checkSurveyHash();
// Set all survey attributes as global variables
setSurveyVals($hash);
// Now set $_GET['pid'] before calling config
$_GET['pid'] = $project_id;
// Config
require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
// Functions for rendering and saving the form
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
// Graphical module functions
require_once APP_PATH_DOCROOT  . "Graphical/functions.php";
// Set survey valuse
$_GET['event_id'] = $event_id;
$arm_id = $Proj->eventInfo[$event_id]['arm_id'];
$_GET['page'] = (empty($form_name)) ? $Proj->firstForm : $form_name;
$public_survey = ($participant_email === null && $event_id == $Proj->firstEventId); // Is this a public survey (vs. invited via Participant List)?
// Make sure any CSRF tokens get unset here (just in case)
unset($_POST['redcap_csrf_token']);
// PASSTHRU: Use this page as a passthru for certain files used by the survey page (e.g. file uploading/downloading)
if (isset($_GET['__passthru']) && !empty($_GET['__passthru']))
{
	// Set array of allowed passthru files
	$passthruFiles = array(
		"DataEntry/file_download.php", "DataEntry/file_upload.php", "DataEntry/file_delete.php",
		"DataEntry/image_view.php", "Surveys/email_participant_return_code.php", "Design/get_fieldlabel.php",
		"DataEntry/empty.php", "DataEntry/check_unique_ajax.php"
	);
	// Decode the value
	$_GET['__passthru'] = urldecode($_GET['__passthru']);
	// Check if a valid passthru file
	if (in_array($_GET['__passthru'], $passthruFiles))
	{
		// Include the file
		require_once APP_PATH_DOCROOT . $_GET['__passthru'];
		exit;
	}
	// Remove now since not needed
	unset($_GET['__passthru']);
}


// Class for html page display system
$objHtmlPage = new HtmlPage();
$objHtmlPage->addStylesheet("bootstrap.css", 'screen,print'); //todd: Added for heh customization to enable Christa's popover tip
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("style_heh.css", 'screen,print');  //todd: Added to override default style.css for heh customizations
$objHtmlPage->addStylesheet("survey.css", 'screen,print');
$objHtmlPage->addStylesheet("survey_heh.css", 'screen,print'); //todd: Added to override default survey.css for heh customizations
$objHtmlPage->addStylesheet("custom.css", 'screen,print'); //christa: Added for heh customizations
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "fontsize.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "customize_yahoo_mediaplayer.js");  //todd: Added for heh customization. This must come before the main mediaplayer include. Adds customizations to the yahoo media player. audio which is used in the palptiations survey and maybe others.
$objHtmlPage->addExternalJS(APP_PATH_JS . "yahoo_mediaplayer.js");   ///todd: Added for heh customization. Adds ability to play audio which is used in the palptiations survey and maybe others. This was added directly so that we don't get ssl warnings.

//todd: for Christa's bootsrap js inclusions which come at end of page and allow popover see redcap_v5.2.3/Classes/HtmpPage.php

// Mobile: Add mobile-specific stylesheets and CSS3 conditions to detect small browsers
if ($isMobileDevice)
{
	$objHtmlPage->addStylesheet("mobile_survey_portrait.css","only screen and (max-width: 320px)");
	$objHtmlPage->addStylesheet("mobile_survey_landscape.css","only screen and (min-width: 321px) and (max-width: 480px)");
}	
$objHtmlPage->setPageTitle(strip_tags($title));


## SET SURVEY TITLE AND LOGO
$title_logo = "";
// LOGO: Render, if logo is provided
if (is_numeric($logo)) {
	//Set max-width for logo (include for mobile devices)
	$logo_width = (isset($isMobileDevice) && $isMobileDevice) ? '300' : '600';
	$title_logo .= "<div style='padding:10px 0 0;'><img src='" . APP_PATH_SURVEY . "index.php?pid=$project_id&__passthru=".urlencode("DataEntry/image_view.php")."&s=$hash&id=$logo' alt='[IMAGE]' title='[IMAGE]' style='max-width:{$logo_width}px;expression(this.width > $logo_width ? $logo_width : true);'></div>";
}
// SURVEY TITLE
if (!$hide_title) {
	$title_logo .= "<div class='surveytitle'>".filter_tags($title)."</div>";
}

// If survey is enabled, check if its access has expired.
if ($survey_enabled > 0 && $survey_expiration != '' && $survey_expiration <= NOW) {
	// Survey has expired, so set it as inactive
	$survey_enabled = 0;
	db_query("update redcap_surveys set survey_enabled = 0 where survey_id = $survey_id");
}

// If survey is disabled OR project is inactive or archived OR if project has been scheduled for deletion, then do not display survey.
if ($survey_enabled < 1 || $date_deleted != '' || $status == 2 || $status == 3) {
	exitSurvey($lang['survey_219']);
}

// Create array of field names designating their survey page with page number as key, and the number of total pages for survey
list ($pageFields, $totalPages) = getPageFields($form_name, $question_by_section);

// GET RESPONSE ID: If $_POST['__response_hash__'] exists and is not empty, then set $_POST['__response_id__']
initResponseId();

// CHECK POSTED PAGE NUMBER (verify if correct to prevent gaming the system)
initPageNumCheck();

// If posting to survey from other webpage and using __prefill flag, then unset $_POST['submit-action'] to prevent issues downstream
if (isset($_POST['__prefill'])) unset($_POST['submit-action']);


/**
 * START OVER: For non-public surveys where the user returned later and decided to "start over" (delete existing response)
 */
if (!$public_survey && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['__startover']) && isset($_POST['__response_hash__']))
{
	// Get record name from response table
	$sql = "select record from redcap_surveys_response where response_id = ".$_POST['__response_id__'];
	$_GET['id'] = $_POST[$table_pk] = $fetched = db_result(db_query($sql), 0);
	// Get list of all fields with data for this record
	$sql = "select distinct field_name from redcap_data where project_id = $project_id and event_id = $event_id and record = '$fetched' and field_name in (
			" . pre_query("select field_name from redcap_metadata where project_id = $project_id and form_name = '$form_name'
			and field_name != '$table_pk'") . ")";
	$q = db_query($sql);
	$eraseFields = array();
	while ($row = db_fetch_assoc($q))
	{
		$eraseFields[$row['field_name']] = $row['field_name'] . " = ''";
	}
	// Delete all responses from data table for this form (do not delete actual record name - will keep same record name)
	$sql = "delete from redcap_data where project_id = $project_id and event_id = $event_id and record = '$fetched' 
			and field_name in ('" . implode("','", array_keys($eraseFields)) . "')";
	db_query($sql);
	// Log the data change
	log_event($sql, "redcap_data", "UPDATE", $fetched, implode(",\n",$eraseFields), "Erase survey responses and start survey over");
	// Reset the page number to 1
	$_GET['__page__'] = 1;
}


/**
 * RETURNING PARTICIPANT: Participant is "Returning Later" and entering return code
 */
// Show page for entering validation code OR validate code and determine response_id from it
if ($save_and_return && !isset($_POST['submit-action']) && (isset($_GET['__return']) || isset($_POST['__code']))) 
{
	// If a respondent from the Participant List is returning via Save&Return link to a completed survey, 
	// then show the "survey already completed" message.
	if (isset($_GET['__return']) && !$public_survey) {
		// Obtain the record number, if exists
		$partRecArray = getRecordFromPartId(array($participant_id));
		// Determine if survey was completed
		if (!empty($partRecArray) && isResponseCompleted($survey_id, $partRecArray[$participant_id], $event_id)) {
			// Redirect back to regular survey page (without &__return=1 in URL)
			redirect(APP_PATH_SURVEY."index.php?s={$_GET['s']}");
		}
	}
	
	// Set error message for entering code
	$codeErrorMsg = "";
	
	// If return code was posted, set as variable for later checking
	if (isset($_POST['__code']))
	{
		$return_code = $_POST['__code'];
		unset($_POST['__code']);
	}
	
	// CODE WAS SUBMITTED: If we have a return code submitted, validate it
	if (isset($return_code)) 
	{	
		// Query if code is correct for this survey/participant
		$sql = "select response_id, record, completion_time from redcap_surveys_response where return_code = '" . prep($return_code) . "' 
				and participant_id = $participant_id limit 1";
		$q = db_query($sql);
		$responseExists = (db_num_rows($q) > 0);
		if (!$responseExists) {
			// Code is not valid, so set error msg
			$codeErrorMsg = $lang['survey_161'];
			// Unset return_code so that user will be prompted to enter it again
			unset($return_code);
		} elseif (db_result($q, 0, "completion_time") != "") {
			// This survey response has already been completed (nothing to do)
			exitSurvey($lang['survey_111']);
		} else {
			// Code is valid, so set response_id and record name
			$_POST['__response_id__'] = db_result($q, 0, "response_id");	
			// Set response_hash
			$_POST['__response_hash__'] = encryptResponseHash($_POST['__response_id__'], $participant_id);	
			// Record exists AND is a non-public survey, so set record name for this page for pre-filling fields
			$_GET['id'] = $_POST[$table_pk] = $fetched = db_result($q, 0, "record");
		}
	}	
	
	// PROMPT FOR CODE: Code has not been entered yet or was entered incorrectly
	if (!isset($return_code)) 
	{
		// Header and title
		$objHtmlPage->PrintHeader();
		print "$title_logo<br><br>";
		// Show error msg if entered incorrectly
		if (!empty($codeErrorMsg)) {
			print  "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
					$codeErrorMsg
					</div><br>";
		}
		print  "<p>{$lang['survey_108']}</p>
				<form id='return_code_form' action='".PAGE_FULL."?s=$hash' method='post' enctype='multipart/form-data'>
					<input type='password' maxlength='8' size='8' class='x-form-text x-form-field' name='__code'> &nbsp; 
					<button class='jqbutton' onclick=\"$('#return_code_form').submit();\">{$lang['survey_109']}</button>
				</form><br>";
		// START OVER: For emailed one-time surveys, allow them to erase all previous answers and start over
		if (!$public_survey) 
		{
			// First get response_id so we can put response_hash in the form
			$sql = "select r.response_id from redcap_surveys_response r, redcap_surveys_participants p 
					where p.participant_id = $participant_id and p.participant_id = r.participant_id 
					and p.participant_email is not null limit 1";
			$q = db_query($sql);
			if (db_num_rows($q))
			{
				// response_id
				$_POST['__response_id__'] = db_result($q, 0);
				// Output Start Over button and text
				print  "<div style='border-top:1px solid #aaa;padding-top:10px;margin-top:30px;'>
							{$lang['survey_110']}<br><br>
							<form action='".PAGE_FULL."?s=$hash&__startover=1' method='post' enctype='multipart/form-data'>
								<input class='jqbutton' type='submit' value=' Start Over ' style='padding: 3px 5px !important;'>
								<input type='hidden' name='__response_hash__' value='".encryptResponseHash($_POST['__response_id__'], $participant_id)."'>
							</form>
						</div>";
			}
		}
		$objHtmlPage->PrintFooter();
		exit;
	}
}




/**
 * VIEW GRAPHICAL RESULTS & STATS
 * Display results to participant if they have completed the survey
 */
if ($enable_plotting_survey_results && $view_results && isset($_GET['__results']))
{
	include APP_PATH_DOCROOT . "Surveys/view_results.php";
}





/**
 * GET THE RECORD NAME (i.e. $fetched)
 */
// GET METHOD
if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	// FIRST PAGE OF A SURVEY (i.e. request method = GET)
	// Check if responses exist already for this participant AND is non-public survey
	$sql = "select r.response_id, r.record, r.first_submit_time, r.completion_time, r.return_code
			from redcap_surveys_response r, redcap_surveys_participants p 
			where p.participant_id = $participant_id and p.participant_id = r.participant_id 
			and p.participant_email is not null	
			order by r.return_code desc, r.completion_time desc, r.response_id limit 1";
	$q = db_query($sql);
	$response_exists = (db_num_rows($q) > 0);
	// Determine if survey was completed fully or partially (if so, then stop here)
	$first_submit_time  = ($response_exists ? db_result($q, 0, "first_submit_time") : "");
	$completion_time    = ($response_exists ? db_result($q, 0, "completion_time")   : "");
	$return_code 	    = ($response_exists ? db_result($q, 0, "return_code")       : "");
	$partiallyCompleted = ($completion_time == "");
	$isFollowupSurvey   = ($first_submit_time == "");
	// Existing record on NON-public survey
	if ($response_exists) 
	{
		// Survey is for a non-first form for an existing record (i.e. followup survey), which has no first_submit_time
		if ($isFollowupSurvey)
		{
			// Set response_id
			$_POST['__response_id__'] = db_result($q, 0, "response_id");
			// Set record name
			$_GET['id'] = $fetched = db_result($q, 0, "record");
		}
		// Save & Return was used, so redirect them to enter their return code
		elseif ($save_and_return && $return_code != "" && $partiallyCompleted) 
		{
			// Redirect to Return Code page so they can enter their return code
			redirect(PAGE_FULL . "?s=$hash&__return=1");
		}
		// Whether using Save&Return or not, give participant option to start over if only partially completed
		elseif ($partiallyCompleted)
		{
			// Set response_id
			$_POST['__response_id__'] = db_result($q, 0, "response_id");
			// Give participant the option to delete their responses and start over	
			$objHtmlPage->PrintHeader();
			print  "$title_logo<br><br>
					<h3 style='font-weight:bold;'>{$lang['survey_163']}</h3>
					<p>{$lang['survey_162']}</p>
					<form action='".PAGE_FULL."?s=$hash&__startover=1' method='post' enctype='multipart/form-data'>
						<input class='jqbutton' type='submit' value=' Start Over ' style='padding: 3px 5px !important;'>
						<input type='hidden' name='__response_hash__' value='".encryptResponseHash($_POST['__response_id__'], $participant_id)."'>
					</form>";
			$objHtmlPage->PrintFooter();
			exit;
		}
		else
		{
			// Participant is not allowed to complete the survey because it has been completed
			exitSurvey($lang['survey_111']);
		}
	}
	// Either a public survey OR non-public survey when record does not exist
	else
	{
		// Set current record as auto-numbered value
		$_GET['id'] = $fetched = getAutoId();
	}
}
// POST METHOD
elseif (isset($_POST['submit-action']) && !isset($_GET['preview'])) 
{
	// Set flag to retrieve record name via response_id or via auto-numbering
	$getRecordNameFlag = true;
	// TWO-TAB CHECK FOR EXISTING RECORD: For participant list participant, make sure they're not taking survey in 2 windows simultaneously.
	// If record exists before we even save responses from page 1, then we know the survey was started in another tab, 
	// so set the response_id so that this second tab instance doesn't create a duplicate record.
	if (!$public_survey)
	{
		// Get record name (if is existing record)
		$partIdRecArray = getRecordFromPartId(array($participant_id));
		if (isset($partIdRecArray[$participant_id])) 
		{		
			// Set flag to false so we don't run redundant queries below
			$getRecordNameFlag = false;
			// Set record name since it alreay exists in the table
			$_GET['id'] = $fetched = $_POST[$table_pk] = $partIdRecArray[$participant_id];
			// Record exists, so use record name to get response_id and check if survey is completed
			$sql = "select response_id, completion_time from redcap_surveys_response 
					where record = '" . prep($fetched) . "' and participant_id = $participant_id limit 1";
			$q = db_query($sql);
			if (db_num_rows($q)) {
				// Set response_id
				$_POST['__response_id__'] = db_result($q, 0, 'response_id');
				// If the completion_time is not null (i.e. the survey was completed), then stop here
				$completion_time_existing_record = db_result($q, 0, 'completion_time');
				if ($completion_time_existing_record != "") {
					// This survey response has already been completed (nothing to do)
					exitSurvey($lang['survey_111']);
				}
			}
		}
	}
		
	// RECORD EXISTS ALREADY and we have response_id, so use response_id to obtain the current record name
	if ($getRecordNameFlag)
	{
		if (isset($_POST['__response_id__']))
		{
			// Use response_id to get record name
			$sql = "select record from redcap_surveys_response where response_id = {$_POST['__response_id__']} 
					and participant_id = $participant_id limit 1";
			$q = db_query($sql);
			// Set record name since it alreay exists in the table
			$_GET['id'] = $fetched = $_POST[$table_pk] = db_result($q, 0);
		} 
		// RECORD DOES NOT YET EXIST: Get record using auto id since doesn't exist yet
		else 
		{
			// Since record does not exist yet, get tentative record name using auto id
			$_GET['id'] = $fetched = $_POST[$table_pk] = getAutoId();
		}
	}
}
// PREVIEW MODE
elseif (isset($_POST['submit-action']) && isset($_GET['preview'])) 
{
	// Set record number
	$_GET['id'] = $fetched = $_POST[$table_pk];
}


// Check for Required fields that weren't entered (checkboxes are ignored - cannot be Required)
if (!isset($_GET['__prevpage']) && !isset($_GET['__endsurvey'])) 
{
	checkReqFields($fetched, true);
}


// Determine the current page number and set as a session variable, and return label for Save button
list ($saveBtnText, $hideFields) = setPageNum($pageFields, $totalPages);
// Create array of fields to be auto-numbered (same as $pageFields, but exclude Descriptive fields)
if ($question_auto_numbering) 
{
	$autoNumFields = array();
	$this_qnum = 1;
	foreach ($pageFields as $this_page=>$these_fields) {
		foreach ($these_fields as $this_field) {
			if ($Proj->metadata[$this_field]['element_type'] != 'descriptive') {
				$autoNumFields[$this_page][$this_qnum++] = $this_field;
			}
		}
	}
}



/**
 * SAVE RESPONSES: Do not save data while in Preview mode
 */
if (isset($_POST['submit-action']) && !isset($_GET['preview'])) 
{
	// Parameters for determining if survey has ended and if nothing is left to be done
	$returningToSurvey = isset($_GET['__return']);
	$reqFieldsLeft = isset($_GET['__reqmsg']);
	$surveyEnded = (isset($_GET['__endsurvey']) || ($_GET['__page__'] > $totalPages) || !$question_by_section || $totalPages == 1);
	
	// Has survey now been compeleted?
	$survey_completed = ($surveyEnded && !$reqFieldsLeft && !$returningToSurvey);
	
	// END OF SURVEY
	if ($survey_completed)
	{
		// Set survey completion time as now
		$completion_time = "'".NOW."'";
		// Form Status = Complete
		$_POST[$_GET['page'].'_complete'] = '2'; 
	}
	// NOT END OF SURVEY (PARTIALLY COMPLETED)
	else
	{
		// Set survey completion time as null
		$completion_time = "null";
		// Form Status = Incomplete
		$_POST[$_GET['page'].'_complete'] = '0';
	}
	
	// INSERT/UPDATE RESPONSE TABLE: Double check to make sure this response isn't already in the response table (use record and participant_id to match)
	$sql  = "select response_id from redcap_surveys_response where participant_id = '" . prep($participant_id) . "' and ";
	$sql .= (isset($_POST['__response_id__'])) ? "response_id = {$_POST['__response_id__']}" : "record = '" . prep($fetched) . "' limit 1";
	$q = db_query($sql);
	if ($q && db_num_rows($q) > 0) {
		// UPDATE existing response
		$_POST['__response_id__'] = db_result($q, 0);
		$sql = "update redcap_surveys_response set completion_time = $completion_time 
				where response_id = {$_POST['__response_id__']}";
		db_query($sql);
	} else {
		// If survey has Save & Return Later enabled, then generate a return code (regardless of it they clicked the Save&Return button)
		$return_code = ($save_and_return) ? getUniqueReturnCode($survey_id) : "";
		// INSERT new response
		$sql = "insert into redcap_surveys_response (participant_id, record, first_submit_time, completion_time, return_code) 
				values (" . checkNull($participant_id) . ", " . checkNull($fetched) . ", '".NOW."', 
				$completion_time, " . checkNull($return_code) . ")";
		$q = db_query($sql);
		if ($q && db_affected_rows() > 0) 
		{
			// Set response_id
			$_POST['__response_id__'] = db_insert_id();
			## DOUBLE POST CHECK: Due to strange incidents of the page posting twice at the same time,
			## make sure a record wasn't entered with a value of 1 lower than the current record for the same timestamp.
			## (i.e. prevent dulicate response from being saved).
			if ($completion_time != "null")
			{
				// Check if same timestamp (or in past X seconds) was set for several previous records in response table 
				// (in case have multiple submissions of same response)
				$prevRecordsToCheckDuplicates = 5;
				$prevRecords = array();
				for ($k=1; $k<=$prevRecordsToCheckDuplicates; $k++) {
					$prevRecords[] = "'" . prep($fetched-$k) . "'";
				}
				// Get timestamp for 3 seconds ago
				$xSecondsAgo = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s")-3,date("m"),date("d"),date("Y")));
				// Query to check for possible duplicate responses in past few seconds
				$sql = "select record from redcap_surveys_response where participant_id = '" . prep($participant_id) . "' 
						and record in (".implode(", ", $prevRecords).") and completion_time <= $completion_time 
						and completion_time >= '$xSecondsAgo' limit $prevRecordsToCheckDuplicates";
				$q = db_query($sql);
				if (db_num_rows($q) > 0) {
					// Collect record names of possible duplicates
					// (will later compare data values between these records to see if they're really duplicates)
					$checkDuplicateRecords = array();
					while ($row = db_fetch_assoc($q)) {
						$checkDuplicateRecords[] = $row['record'];
					}
				}
			}
		}
		else 
		{
			// FAILSAFE: Somehow this row exists already, but we didn't catch it in the select query above (how is that possible?), 
			// so update it instead and get response_id.
			$sql = "update redcap_surveys_response set completion_time = $completion_time 
					where participant_id = '" . prep($participant_id) . "' and record = '" . prep($fetched) . "'";
			db_query($sql);
			// Now get response_id
			$sql = "select response_id from redcap_surveys_response where participant_id = '" . prep($participant_id) . "' 
					and record = '" . prep($fetched) . "' limit 1";
			$q = db_query($sql);
			if (db_num_rows($q) > 0) {
				// Set response_id
				$_POST['__response_id__'] = db_result($q, 0);
			}
		}
	}	
	
	// FOLLOWUP SURVEYS, which begin with first_submit_time=NULL, set first_submit_time as NOW (or completion_time, if just completed)
	if (isset($_POST['__response_id__']))
	{
		// Set first_submit_time in response table
		$sql = "update redcap_surveys_response set first_submit_time = if(completion_time is null, '".NOW."', completion_time) 
				where response_id = {$_POST['__response_id__']} and first_submit_time is null";
		$q = db_query($sql);
	}
	
	// Save the submitted data (if a required field was triggered, then we've already saved it once, so don't do it twice)
	if (!isset($_GET['__reqmsg'])) 
	{
		saveRecord($fetched);
	}
	
	## DOUBLE POST CHECK: Due to strange incidents of the page posting twice at the same time,
	## make sure a record wasn't entered with a value of 1 lower than the current record for the same timestamp.
	if (isset($checkDuplicateRecords) && !empty($checkDuplicateRecords))
	{
		// Get current record data (from table)
		$currData = getRecordData($fetched, $_GET['event_id']);
		unset($currData[$table_pk]); // Remove participant_id because it will always be different for each record
		// Loop through past several records and check each for exact duplicate data with $currData (current response)
		foreach ($checkDuplicateRecords as $thisPrevRecord)
		{
			// Since the timestamp matches for the previous record, compare the actual data values 
			// Get previous record data (from table)
			$prevData = getRecordData($thisPrevRecord, $_GET['event_id']);
			unset($prevData[$table_pk]); // Remove participant_id because it will always be different for each record
			// Is the record identical?
			if ($currData === $prevData)
			{
				// Set flag
				$duplicateResponse = true;
				// Remove duplicate record from data table
				db_query("delete from redcap_data where project_id = ".PROJECT_ID." and record = '".prep($fetched)."'");	
				// Remove duplicate record from response table
				db_query("delete from redcap_surveys_response where participant_id = $participant_id and record = '".prep($fetched)."'");	
				// Remove duplicate record from log_event table
				db_query("delete from redcap_log_event where project_id = ".PROJECT_ID." and user = '[survey respondent]' 
							 and page = 'surveys/index.php' and event = 'INSERT'
							 and object_type = 'redcap_data' and pk = '".prep($fetched)."' and event_id = {$_GET['event_id']}");
			}
		}
	}
	
	// If survey is officially completed, then send an email to survey admins, if enabled.
	if ($survey_completed && !(isset($duplicateResponse) && $duplicateResponse))
	{
		sendEndSurveyEmails($survey_id, $_GET['event_id'], strip_tags($title), $participant_id, $fetched);
	}
	
	/** 
	 * SAVE & RETURN LATER button was clicked at bottom of survey page
	 */
	// If user clicked "Save & Return Later", then provide validation code for returning
	if ($save_and_return && isset($_GET['__return'])) 
	{
		// Check if return code exists already
		$sql = "select return_code from redcap_surveys_response where return_code is not null 
				and response_id = {$_POST['__response_id__']} limit 1";
		$q = db_query($sql);
		if (db_num_rows($q) > 0) {
			// Get return code that already exists in table
			$return_code = db_result($q, 0);
		} else {
			// Create a return code for the participant since one does not exist yet
			$return_code = getUniqueReturnCode($survey_id);
			// Add return code to response table (but only if it does not exist yet)
			$sql = "update redcap_surveys_response set completion_time = null, return_code = '$return_code' 
					where response_id = ".$_POST['__response_id__'];
			db_query($sql);
		}
		// Set the URL of the page called via AJAX to send the participant's email to themself
		$return_email_page = APP_PATH_SURVEY . "index.php?pid=$project_id&__passthru=".urlencode("Surveys/email_participant_return_code.php");
		// Instructions for returning
		$objHtmlPage->PrintHeader();
		?>
		<div id="return_instructions" style="padding:40px 0 30px 0;">
			<h3><b><?php echo $lang['survey_112'] ?></b></h3>
			<div>
				<?php echo $lang['survey_113'] ?> <i style="color:#800000;"><?php echo $lang['survey_114'] ?></i> <?php echo $lang['survey_115'] ?>
				<i style="color:#800000;"><?php echo $lang['survey_116'] ?></i><?php echo $lang['period'] ?> <?php echo $lang['survey_117'] ?><br>
				<div style="padding:20px 20px;margin-left:2em;text-indent:-2em;">
					<b>1.) <u><?php echo $lang['survey_118'] ?></u></b><br>
					<?php echo $lang['survey_119'] ?><br>
					<?php echo $lang['survey_118'] ?>&nbsp;
					<input readonly style="font-family:verdana;width:150px;padding:3px;margin-top:4px;background-color:#EDF2FD;border:1px solid #A7C3F1;color:#000066;font-size:13px;" 
						onclick="this.select();" value="<?php echo $return_code ?>"><br>
					<span style="color:#800000;font-size:10px;font-family:tahoma;">
						* <?php echo $lang['survey_120'] ?>
					</span>
				</div>
				<div style="padding:5px 20px;margin-left:2em;text-indent:-2em;">
					<b>2.) <u><?php echo $lang['survey_121'] ?></u></b><br>
					<span id="provideEmail" style="<?php echo (!$public_survey ? "display:none;" : "") ?>">
						<?php echo $lang['survey_123'] ?><br><br>
						<input type="text" id="email" style="font-size:11px;color:#777777;width:180px;" 
							value="Enter email address"
							onblur="if(this.value==''){this.value='Enter email address';this.style.color='#777777';} if(this.value != 'Enter email address'){redcap_validate(this,'','','soft_typed','email')}"}
							onfocus="if(this.value=='Enter email address'){this.value='';this.style.color='#000000';}"
							onclick="if(this.value=='Enter email address'){this.value='';this.style.color='#000000';}"
						> 
						<button id="sendLinkBtn" class="jqbuttonsm" onclick="
							redcap_validate(document.getElementById('email'), '', '', '', 'email');
							if (document.getElementById('email').value == 'Enter email address') {
								alert('Please enter an email address');
							} else {
								emailReturning(<?php echo "$survey_id, $event_id, $participant_id, '$hash'" ?>, $('#email').val(), '<?php echo $return_email_page ?>');
							}
						"><?php echo $lang['survey_124'] ?></button>
						<span id="progress_email" style="visibility:hidden;">
							<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix">
						</span><br>
						<span style="font-size:10px;color:#800000;font-family:tahoma;">* <?php echo $lang['survey_125'] ?></span>
					</span>
					<span id="autoEmail" style="<?php echo ($public_survey ? "display:none;" : "") ?>">
						<?php echo $lang['survey_122'] ?>
					</span>
		<?php if (!$public_survey) { ?>
					<script type="text/javascript">
					emailReturning(<?php echo "$survey_id, $event_id, $participant_id, '$hash'" ?>, '', '<?php echo $return_email_page ?>');
					</script>
		<?php } ?>
				</div>
				<div style="border-top:1px solid #aaa;margin-top:40px;padding:10px;">
					<form id="return_continue_form" action="<?php echo PAGE_FULL ?>?s=<?php echo $hash ?>" method="post" enctype="multipart/form-data">
					<b><?php echo $lang['survey_126'] ?></b>
					<input type="hidden" maxlength="8" size="8" name="__code" value="<?php echo $return_code ?>"> 
					<div style="padding-top:10px;"><button class="jqbutton" onclick="$('#return_continue_form').submit();"><?php echo $lang['survey_127'] ?></button></div>
					</form>
				</div>
			</div>
		</div>
		<p id="codePopupReminder" title="Validation code needed to return">
			<?php echo $lang['survey_128'] ?><br><br>
			<b>Validation code:</b>&nbsp;
			<input readonly style="font-family:verdana;width:150px;padding:3px;margin-top:4px;background-color:#EDF2FD;border:1px solid #A7C3F1;color:#000066;font-size:13px;" 
				onclick="this.select();" value="<?php echo $return_code ?>">
		</p>
		<script type="text/javascript">
		// Give dialog on page load to make sure participant writes it down
		$(function(){
			$('#codePopupReminder').dialog({ bgiframe: true, modal: true, width: (isMobileDevice ? $('body').width() : 450), buttons: { 
				Close: function() { $(this).dialog('close'); }
			}});
		});
		</script>
		<?php
		$objHtmlPage->PrintFooter();
		exit;
	}
}




// ACKNOWLEDGEMENT OR SURVEY REDIRECT: If just finished the last page, then end survey and show acknowledgement
if (isset($_POST['submit-action']) && ($_GET['__page__'] > $totalPages || isset($_GET['__endsurvey']))) 
{
	## REDIRECT TO ANOTHER WEBPAGE
	if ($end_survey_redirect_url != '')
	{
		// Append participant_id?
		$appendURL = '';
		if ($end_survey_redirect_url_append_id && isset($fetched)) {
			$appendURL = (strpos($end_survey_redirect_url, '?') === false ? '?' : '&') . "participant_id=$fetched";
		}
		// Redirect to other page
		redirect($end_survey_redirect_url . $appendURL);
	}
	## DISPLAY ACKNOWLEDGEMENT TEXT
	else
	{
		// Determine if we should show the View Survey Results button
		$surveyResultsBtn = "";
		if ($enable_plotting_survey_results && $view_results)
		{
			// Generate and save a results code for this participant
			$results_code = getUniqueResultsCode($survey_id);
			// Save the code
			$sql = "update redcap_surveys_response set results_code = " . checkNull($results_code) . " 
					where response_id = {$_POST['__response_id__']}";
			if (db_query($sql))
			{
				// HTML for View Survey Results button form with the results code (and its hash) embedded
				$surveyResultsBtn = "<div style='text-align:center;border-top:1px solid #ccc;padding:20px 0;margin-top:50px;'>
										<form id='results_code_form' action='".APP_PATH_SURVEY_FULL."index.php?s={$_GET['s']}&__results=$results_code' method='post' enctype='multipart/form-data'>
											<input type='hidden' name='results_code_hash' value='".getResultsCodeHash($results_code)."'>
											<input type='hidden' name='__response_hash__' value='".encryptResponseHash($_POST['__response_id__'], $participant_id)."'>
											<button class='jqbutton' onclick=\"\$('#results_code_form').submit();\">
												<img src='".APP_PATH_IMAGES."chart_curve.png' class='imgfix'>
												{$lang['survey_167']}
											</button>
										</form>
									 </div>";
			}
		}
		// Display acknowledgement
		exitSurvey(filter_tags($acknowledgement).$surveyResultsBtn, false);
	}
}











/**
 * BUILD FORM METADATA
 */
// Set pre-fill data array as empty (will be used to fill survey form with existing values)
$element_data = array();
// Calculate Parser class (object $cp used in buildFormData() )
$cp = new CalculateParser();
// Branching Logic class (object $bl used in buildFormData() )
$bl = new BranchingLogic();
// Obtain form/survey metadata for rendering
list ($elements, $calc_fields_this_form, $branch_fields_this_form, $chkbox_flds) = buildFormData($form_name);
// If survey's first field is record identifier field, remove it since we're adding it later as a hidden field.
if ($elements[0]['name'] == $table_pk) array_shift($elements);
// Remove the Form Status field (last 2 elements of array - includes its section header)
array_pop($elements); array_pop($elements);
// Add hidden survey fields and their data
$elements[] = array('rr_type'=>'hidden', 'id'=>'submit-action', 'name'=>'submit-action', 'value'=>'Save Record');
$elements[] = array('rr_type'=>'hidden', 'id'=>$table_pk, 'name'=>$table_pk, 'value'=>$fetched);
$elements[] = array('rr_type'=>'hidden', 'name'=>'__page__');
$elements[] = array('rr_type'=>'hidden', 'name'=>'__page_hash__');
$elements[] = array('rr_type'=>'hidden', 'name'=>'__response_hash__');
$element_data[$table_pk] = $fetched;
$element_data['__page__'] = $_GET['__page__'];
$element_data['__page_hash__'] = getPageNumHash($_GET['__page__']);
$element_data['__response_hash__'] = (isset($_POST['__response_id__']) ? encryptResponseHash($_POST['__response_id__'], $participant_id) : '');
// Add the Save buttons
$saveBtn = RCView::button(array('class'=>'jqbutton','style'=>'color:#800000;width:140px;','onclick'=>'this.disabled=true;dataEntrySubmit(this);'), $saveBtnText);
if ($question_by_section && $_GET['__page__'] > 1) {
	// "Previous page" and "Next page"/"Submit" buttons
	$saveBtnRow = RCView::td(array('style'=>'padding:0 40px 15px;width:50%;text-align:right;'),
					RCView::button(array('class'=>'jqbutton','style'=>'color:#800000;width:140px;','onclick'=>'this.disabled=true;dataEntrySubmit(this);'), '<< Previous Page')
				  )
				 . RCView::td(array('style'=>'padding:0 40px 15px;width:50%;'), $saveBtn);
} else {
	// "Submit" button
	$saveBtnRow = RCView::td(array('colspan'=>'2','style'=>'text-align:center;padding:0 0 15px;'), $saveBtn);
}
// Show "save and return later" button if setting is enabled for the survey
$saveReturnRow = "";
if ($save_and_return) {
	$saveReturnRow = RCView::tr(array(),
						RCView::td(array('colspan'=>'2','style'=>'text-align:center;'),
							RCView::button(array('class'=>'jqbutton','onclick'=>'this.disabled=true;dataEntrySubmit(this);'), 'Save & Return Later')
						)
					);
}
$elements[] = array('rr_type'=>'surveysubmit', 'label'=>RCView::table(array('cellspacing'=>'0'), RCView::tr(array(), $saveBtnRow) . $saveReturnRow));


/**
 * ADD CALC FIELDS AND BRANCHING LOGIC FROM OTHER FORMS
 * Add fields from other forms as hidden fields if involved in calc/branching on this form
 */
list ($elementsOtherForms, $chkbox_flds_other_forms, $jsHideOtherFormChkbox) = addHiddenFieldsOtherForms($form_name, array_merge($branch_fields_this_form, $calc_fields_this_form));
$elements 	 = array_merge($elements, $elementsOtherForms);
$chkbox_flds = array_merge($chkbox_flds, $chkbox_flds_other_forms);


/**
 * PRE-FILL DATA FOR EXISTING SAVED RESPONSE (from previous pages or previous session)
 */
if (!isset($_GET['preview']) && ($_SERVER['REQUEST_METHOD'] == 'POST' || ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($isFollowupSurvey) && $isFollowupSurvey !== false))) 
{
	//Build query for pulling existing data to render on top of form
	$datasql = "select field_name, value from redcap_data where	project_id = $project_id and event_id = {$_GET['event_id']} 
				and record = '".prep($fetched)."' and field_name in (";
	foreach ($elements as $fldarr) {
		if (isset($fldarr['field'])) $datasql .= "'".$fldarr['field']."', ";
	}
	$datasql = substr($datasql, 0, -2) . ")";
	$q = db_query($datasql);
	while ($row_data = db_fetch_array($q)) 
	{
		// Checkbox
		if (isset($chkbox_flds[$row_data['field_name']])) {
			$element_data[$row_data['field_name']][] = $row_data['value'];
		// Non-checkbox, non-date field
		} else {		
			$element_data[$row_data['field_name']] = $row_data['value'];
		}
	}
}



/**
 * PRE-FILL QUESTIONS VIA QUERY STRING OR VIA __prefill flag FROM POST REQUEST
 * Catch any URL variables passed to use for pre-filling fields (i.e. plug into $element_data array for viewing)
 */
$reservedParams = array();
// If a GET request with variables in query string
if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	// Ignore certain GET variables that are currently used in the application
	$reservedParams = array("s", "hash", "page", "event_id", "pid", "pnid", "preview", "id");
	// Loop through all query string variables
	foreach ($_GET as $key=>$value) {
		// Ignore reserved fields
		if (in_array($key, $reservedParams)) continue;		
		// First check if field is a checkbox field ($key will be formatted as "fieldname___codedvalue" and $value as "1" or "0")
		$prefillFldIsChkbox = false;
		if (!isset($Proj->metadata[$key]) && $value == '1' && strpos($key, '___') !== false) {
			// Is possibly a checkbox, but parse into true field name and value to be sure
			list ($keychkboxcode, $keychkboxname) = explode('___', strrev($key), 2);
			$keychkboxname = strrev($keychkboxname);
			// Verify checkbox field name
			if (isset($Proj->metadata[$keychkboxname])) {
				// Is a real field, so reset key/value
				$prefillFldIsChkbox = true;
				$key = $keychkboxname;
				$value = $keychkboxcode;
			}
		}
		// Now verify the field name
		if (!isset($Proj->metadata[$key])) continue;		
		// Add to pre-fill data
		if ($prefillFldIsChkbox) {
			$element_data[$key][] = $value;
		} else {
			$element_data[$key] = urldecode($value);
		}
	}
}
// If a POST request with variable as Post values (__prefill flag was set)
elseif (isset($_POST['__prefill']))
{
	// Ignore special fields that only occur for surveys
	$postIgnore = array('__page__', '__response_hash__', '__response_id__');
	// Loop through all Post variables
	foreach ($_POST as $key=>$value) 
	{
		// Ignore special Post fields
		if (in_array($key, $postIgnore)) continue;		
		// First check if field is a checkbox field ($key will be formatted as "fieldname___codedvalue" and $value as "1" or "0")
		$prefillFldIsChkbox = false;
		if (!isset($Proj->metadata[$key]) && $value == '1' && strpos($key, '___') !== false) {
			// Is possibly a checkbox, but parse into true field name and value to be sure
			list ($keychkboxcode, $keychkboxname) = explode('___', strrev($key), 2);
			$keychkboxname = strrev($keychkboxname);
			// Verify checkbox field name
			if (isset($Proj->metadata[$keychkboxname])) {
				// Is a real field, so reset key/value
				$prefillFldIsChkbox = true;
				$key = $keychkboxname;
				$value = $keychkboxcode;
			}
		}
		// Now verify the field name
		if (!isset($Proj->metadata[$key])) continue;		
		// Add to pre-fill data
		if ($prefillFldIsChkbox) {
			$element_data[$key][] = $value;
		} else {
			$element_data[$key] = $value;
		}
	}
}











// Page header
$objHtmlPage->PrintHeader();

// PREVIEW PANE: If user in viewing in Preview mode, then give notification bar at top of page
renderPreviewPane();

?>
<script type="text/javascript" src="<?php echo APP_PATH_JS ?>survey.js"></script>
<script type="text/javascript">
// Set variables
var record_exists = <?php echo $hidden_edit ?>;
var require_change_reason = 0;
var event_id = <?php echo $_GET['event_id'] ?>;
$(function() {
	// Check for any reserved parameters in query string
	checkReservedSurveyParams(new Array('<?php echo implode("','", $reservedParams) ?>'));
	<?php if ($question_auto_numbering) { ?>
	// AUTO QUESTION NUMBERING: Add page number values where needed
	var qnums = new Array('<?php echo implode("','", array_keys($autoNumFields[$_GET['__page__']])) ?>');
	var qvars = new Array('<?php echo implode("','", $autoNumFields[$_GET['__page__']]) ?>');
	for (x in qnums) $('#'+qvars[x]+'-tr').find('td:first').html(qnums[x]+')');	
	<?php } ?>
	// For some browsers, the survey table might not fill out 100% width for certain question combinations/alignments, 
	// so change colspan of Submit button row. It is unclear why this is an issue since the HTML seems to be formatted fine.
	if (!isMobileDevice && $('.surveysubmit').length && $('.surveysubmit td.label').width() < 720) {
		$('.surveysubmit td.label').width(750);
		if ($('.surveysubmit td.label').width() < 720) {
			$('.surveysubmit td.label').attr('colspan','2');
		}
	}
});
</script>

<!-- Title and/or Logo -->
<table id="surveytitle" cellspacing="0" width="100%" style="table-layout:fixed;">
	<tr>
		<td valign="top">
			<?php echo $title_logo ?>
		</td>
		<td valign="top" id="changeFont" style="padding-top:7px;text-align:right;width:75px;position:relative;color:#666;font-family:tahoma;font-size:11px;">
		  <!-- todd customized for HEH. Hide font resize
			<span style="white-space:nowrap;"><?php echo $lang['survey_218'] ?></span><br/>
			<span style="white-space:nowrap;"><a href="javascript:;" class="increaseFont"><img src="<?php echo APP_PATH_IMAGES ?>font_add.png" class="imgfix"></a>&nbsp;&nbsp;|&nbsp;<a href="javascript:;" class="decreaseFont"><img src="<?php echo APP_PATH_IMAGES ?>font_delete.png" class="imgfix"></a></span>
			 -->
		</td>
		<?php 			
		//Give note at top for public surveys if user is returning
		if ($save_and_return && $public_survey && $_SERVER['REQUEST_METHOD'] == 'GET') 
		{
			?><td valign="top" class="bubbleInfo" style="width:100px;position:relative;"><?php
			include APP_PATH_DOCROOT . "Surveys/return_code_widget.php";
			?></td><?php
		}
		?>
	</tr>
</table>

<?php
// Survey Instructions (display for first page only)
if ($_SERVER['REQUEST_METHOD'] != 'POST' || isset($_POST['__prefill'])) {
	print RCView::div(array('id'=>'surveyinstr'), filter_tags($instructions));
}
// Page number (if multi-page enabled)
if ($question_by_section) { 
	print RCView::p(array('id'=>'surveypagenum'), "{$lang['survey_132']} {$_GET['__page__']} {$lang['survey_133']} $totalPages");
}
// Survey Questions
form_renderer($elements, $element_data, $hideFields);
// JavaScript for Calculated Fields and Branching Logic
if ($longitudinal) echo addHiddenFieldsOtherEvents();
// Output JavaScript for branching and calculations
print $cp->exportJS() . $bl->exportBranchingJS(); 
// JavaScript that hides checkbox fields from other forms, which need to be hidden
print $jsHideOtherFormChkbox;
// Stop Action text and JavaScript, if applicable
print enableStopActions(); 
print RCView::div(array('id'=>'stopActionPrompt','title'=>$lang['survey_01']), RCView::b($lang['survey_02']) . RCView::SP . $lang['survey_03']);
print RCView::div(array('id'=>'stopActionReturn','title'=>$lang['survey_05']), $lang['survey_04']);
// Required fields pop-up message
msgReqFields($fetched, '', true);
// Set file upload dialog
initFileUploadPopup(); 
// Secondary unique field javascript
renderSecondaryIdJs();;
// if Survey Email Participant Field is on this survey page, and the participant is in the Participant List,
// then pre-fill the email field with the email address from the Participant List and disable the field.
if (!$public_survey && $survey_email_participant_field != '' 
	&& isset($Proj->forms[$_GET['page']]['fields'][$survey_email_participant_field])
	&& in_array($survey_email_participant_field, $pageFields[$_GET['__page__']])) 
{
	// If $participant_email is empty because this is not an initial survey, then obtain it from initial survey's Participant List value
	$thisPartEmailTrue = $participant_email;
	if ($thisPartEmailTrue == '') {
		$thisPartEmailIdent = array_shift(getResponsesEmailsIdentifiers(getRecordFromPartId(array($participant_id))));		
		$thisPartEmailTrue = $thisPartEmailIdent['email'];
	}
	if ($thisPartEmailTrue != '') {
		?>
		<script type="text/javascript">
		$(function(){
			$('form#form input[name="<?php echo $survey_email_participant_field ?>"]').css('color','gray').attr('readonly', true)
				.val('<?php echo cleanHtml($thisPartEmailTrue) ?>')
				.attr('title', '<?php echo cleanHtml($lang['survey_485']) ?>');
		})
		</script>
		<?php
	}
}
// Page footer
print RCView::div(array('class'=>'space'),'&nbsp;');
$objHtmlPage->PrintFooter();
