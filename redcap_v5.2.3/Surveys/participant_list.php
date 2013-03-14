<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id'])) $_GET['survey_id'] = getSurveyId();

// Ensure the survey_id belongs to this project
if (!$Proj->validateSurveyId($_GET['survey_id']))
{
	redirect(APP_PATH_WEBROOT . "index.php?pid=" . PROJECT_ID);
}

// Retrieve survey info
$q = db_query("select * from redcap_surveys where project_id = $project_id and survey_id = " . $_GET['survey_id']);
foreach (db_fetch_assoc($q) as $key => $value)
{
	$$key = trim(html_entity_decode($value, ENT_QUOTES));
}

// Obtain current arm_id
$_GET['event_id'] = getEventId();
$_GET['arm_id'] = getArmId();

// Check if this is a follow-up survey
$isFollowUpSurvey = !($_GET['survey_id'] == $Proj->firstFormSurveyId && $Proj->isFirstEventIdInArm($_GET['event_id']));

// Gather participant list (with identfiers and if Sent/Responded)
list ($part_list, $part_list_duplicates) = getParticipantList($survey_id, $_GET['event_id']);

// Set array to fill with table display info
$part_list_full = array();






// DISPLAY FOR EMAIL POP-UP FORMAT (contains checkboxes and does not list those already responded)
if (isset($_GET['emailformat']) && $_GET['emailformat'] == '1')
{
	// Expand array with full details to render table
	$i = 0; // counter
	foreach ($part_list as $this_part=>$attr)
	{
		// If we have no email, then we can't email this one, so skip it
		if ($attr['email'] == '') continue;
		// Set "checked" status of checkbox if sent/unsent
		$sentclass = ($attr['sent']) ? "part_sent" : "part_unsent";
		// Set "checked" status of checkbox
		$schedclass = ($attr['scheduled'] == '') ? "unsched" : "sched";
		// Don't pre-check checkbox if they have been sent an email OR have partially completed survey
		$checked = ($attr['sent'] || $attr['scheduled'] != '' || $attr['response'] != '0') ? "" : "checked";
		// Check for duplicated emails in order to pre-pend with number, if needed
		$email_num = "";
		if ($part_list_duplicates[$attr['email']]['total'] > 1) {
			$email_num = "<span style='color:#777;'>" . $part_list_duplicates[$attr['email']]['current'] . ")</span>&nbsp;&nbsp;";
			$part_list_duplicates[$attr['email']]['current']++; // Increment current email number for next time 
		}
		// Skip those that have already responded completely
		if ($attr['response'] == '2') continue;
		// For followup surveys, append record name after email 
		$emailDisplay = $attr['email'];
		if ($attr['record'] != '' && $attr['identifier'] != '') {
			$emailDisplay .= " &nbsp;<span style='color:#777;'>(ID {$attr['record']})</span>";
		}
		// Add to array
		$part_list_full[$i] = array();
		$part_list_full[$i][] = "<input type='checkbox' class='chk_part $sentclass $schedclass' id='chk_part{$this_part}' $checked>";
		$part_list_full[$i][] = $email_num . $emailDisplay;
		$part_list_full[$i][] = $attr['identifier'];
		$part_list_full[$i][] = ($attr['scheduled'] == '' ? '-' : RCView::img(array('src'=>'clock_fill.png','title'=>format_ts_mysql($attr['scheduled']))));
		$part_list_full[$i][] = ($attr['sent'] ? RCView::img(array('src'=>'email_check.png','title'=>$lang['survey_316'])) : RCView::img(array('src'=>'email_gray.gif','title'=>$lang['survey_317'])));
		// Increment counter
		$i++;
	}

	// If no participants exist yet, render one row to let user know that
	if (empty($part_list_full)) $part_list_full[] = array("",$lang['survey_34'],"","");

	// Build participant list table
	$partTableHeight = (count($part_list_full) <= 16) ? "auto" : 465;
	$partTableWidth = (count($part_list_full) <= 16) ? 459 : 476;
	$partTableHeaders = array();
	$partTableHeaders[] = array(16, "");
	$partTableHeaders[] = array(198, $lang['global_33']);
	$partTableHeaders[] = array(100, $lang['survey_250']);
	$partTableHeaders[] = array(56, "Scheduled?", "center");
	$partTableHeaders[] = array(28, $lang['survey_36'], 'center');
	// Create drop-down of action choices for checking/unchecking participants in list
	$checkOptions = array(''=>$lang['survey_280']);
	$checkOptions['check_all'] = $lang['survey_41'];
	$checkOptions['uncheck_all'] = $lang['survey_42'];
	$checkOptions['check_sent'] = $lang['survey_39'];
	$checkOptions['check_unsent'] = $lang['survey_40'];
	$checkOptions['check_sched'] = $lang['survey_319'];
	$checkOptions['check_unsched'] = $lang['survey_320'];
	$checkOptions['check_unsent_unsched'] = $lang['survey_321'];
	$partTableTitle =	RCView::div(array('style'=>'padding:0;font-family:arial;'),
							RCView::div(array('style'=>'float:left;font-size:13px;padding:0 0 2px;'),
								$lang['survey_37'] . RCView::br() .
								RCView::span(array('style'=>'font-weight:normal;font-size:11px;color:#666;'),
									$lang['survey_38']
								)
							) .
							RCView::div(array('style'=>'float:right;font-size:11px;'),
								$lang['survey_281'] . 
								RCView::select(array('onchange'=>'emailPartPreselect(this.value);','style'=>'margin-left:5px;font-size:11px;'), $checkOptions)
							)
						);
	// Build Participant List
	renderGrid("participant_table_email", $partTableTitle, $partTableWidth, $partTableHeight, $partTableHeaders, $part_list_full);
}





// DISPLAY FOR MAIN PAGE
elseif (!isset($_GET['emailformat']))
{
	## Build drop-down list of surveys/events
	// Create drop-down of ALL surveys and, if longitudinal, the events for which they're designated
	$surveyEventOptions = array();
	// Loop through each arm
	foreach ($Proj->events as $this_arm=>$arm_attr) 
	{
		// Loop through each instrument
		foreach ($Proj->forms as $form_name=>$form_attr) 
		{
			// Ignore if not a survey
			if (!isset($form_attr['survey_id'])) continue;
			// Get survey_id
			$this_survey_id = $form_attr['survey_id'];
			// Loop through each event and output each where this form is designated
			foreach ($Proj->eventsForms as $this_event_id=>$these_forms) 
			{
				// If event does not belong to the current arm OR the form has not been designated for this event, then go to next loop
				if (!($arm_attr['events'][$this_event_id] && in_array($form_name, $these_forms))) continue; 
				// If this is the first form and first event, note it as "public survey"
				$public_survey_text = ($Proj->isFirstEventIdInArm($this_event_id) && $form_name == $Proj->firstForm) ? $lang['survey_351']." " : "";
				// If longitudinal, add event name
				$event_name = ($longitudinal) ? " - ".$Proj->eventInfo[$this_event_id]['name_ext'] : "";
				// Truncate survey title if too long
				$survey_title = $Proj->surveys[$this_survey_id]['title'];
				if (strlen($public_survey_text.$survey_title.$event_name) > 70) {
					$survey_title = substr($survey_title, 0, 67-strlen($public_survey_text)-strlen($event_name)) . "...";
				}
				// Add this survey/event as drop-down option
				$surveyEventOptions["$this_survey_id-$this_event_id"] = "$public_survey_text\"$survey_title\"$event_name";
			}
		}
	}
	// Collect HTML
	$surveyEventDropdown = RCView::select(array('class'=>"x-form-text x-form-field",
		'style'=>'max-width:400px;font-weight:bold;padding-right:0;height:22px;font-size:11px;',
		'onchange'=>"if(this.value!=''){showProgress(1);var seid = this.value.split('-'); window.location.href = app_path_webroot+'Surveys/invite_participants.php?pid=$project_id&participant_list=1&survey_id='+seid[0]+'&event_id='+seid[1];}"), 
			$surveyEventOptions, $_GET['survey_id']."-".$_GET['event_id'], 500
		);
	
	
	## Option to enable/disable PARTICIPANT IDENTIFIERS
	$partIdentBtnDisabled = ($status < 1 || $super_user) ? "" : "disabled";
	$partIdentDisabled = "";
	$partIdentHdrStyle = "margin-right:5px;";
	if (!$enable_participant_identifiers) {
		// Disabled
		$enablePartIdent = "&nbsp; <button onclick='enablePartIdent({$_GET['survey_id']},{$_GET['event_id']});' class='jqbuttonsm' style='color:#007000;' $partIdentBtnDisabled>{$lang['survey_152']}</button>";
		$partIdentHdrStyle = "margin-right:20px;color:#888;";
	} else {
		// Enabled
		$partIdentDisabled = $lang['survey_251'];
		$enablePartIdent = "&nbsp; <button onclick='enablePartIdent({$_GET['survey_id']},{$_GET['event_id']});' class='jqbuttonsm' style='color:#800000;' $partIdentBtnDisabled>{$lang['control_center_153']}</button>";
	}
	// Remove enable/disable button for followup surveys
	if ($isFollowUpSurvey) $enablePartIdent = "";
	
	// First, get form, record, and event_id for all complete/partial responses to display as links
	$participantParams = array();
	if (!empty($part_list))
	{
		$sql = "select s.form_name, r.record, p.event_id, p.participant_id 
				from redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r 
				where s.survey_id = {$_GET['survey_id']} and s.survey_id = p.survey_id 
				and p.participant_id = r.participant_id and p.event_id = {$_GET['event_id']} 
				and p.participant_id in (".implode(", ", array_keys($part_list)).")";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Add params to array with participant_id as key
			$participantParams[$row['participant_id']] = "&page={$row['form_name']}&id={$row['record']}&event_id={$row['event_id']}";
		}
	}
	
	// Expand array with full details to render table
	$i = 0; // counter
	foreach ($part_list as $this_part=>$attr)
	{
		// Trim identifier
		$attr['identifier'] = trim($attr['identifier']);
		// Check for duplicated emails in order to pre-pend with number, if needed
		$email_num = "";
		$email_num_raw = "";
		if ($part_list_duplicates[$attr['email']]['total'] > 1) {
			$email_num_raw = $part_list_duplicates[$attr['email']]['current'];
			$email_num = "<span style='color:#777;'>$email_num_raw)</span>&nbsp;&nbsp;";
			$part_list_duplicates[$attr['email']]['current']++; // Increment current email number for next time 
		}
		// Set flag to edit identifier/email ONLY if participant has NOT taken the survey yet
		$editidentifier = "noeditidentifier";
		$editemail = "noeditemail";
		$viewresponse = ($attr['identifier'] == '') ? "noviewresponse" : "viewresponse";
		$imgtitle = ($attr['identifier'] == '') ? '' : 'title="'.cleanHtml2($lang['survey_245']).'"';
		// Set response and link icons
		$link_icon = "<a target='_blank' href='" . APP_PATH_SURVEY_FULL . "?s={$attr['hash']}'><img class='partLink' src='".APP_PATH_IMAGES."link.png' title=\"".cleanHtml2($lang['survey_246'])."\"></a>";
		if ($attr['response'] == "2") {
			// Responded
			$response_icon = '<img class="'.$viewresponse.'" src="'.APP_PATH_IMAGES.'tick_circle_frame.png" '.$imgtitle.'>';
			$link_icon = '-';
		} elseif ($attr['response'] == "1") {
			// Partial response
			$response_icon = '<img class="'.$viewresponse.'" src="'.APP_PATH_IMAGES.'circle_orange_tick.png" '.$imgtitle.'>';
		} else {
			// No response
			$response_icon = '<img src="'.APP_PATH_IMAGES.'stop_gray.png">';
			// Set editidentifier/editemail classes
			$editidentifier = "editidentifier";
			$editemail = "editemail";
		}
		// Add link to response (ONLY if has identifier and ONLY for partial and complete responses)
		if ($attr['identifier'] != '' && isset($participantParams[$this_part])) {
			$response_icon = "<a href='".APP_PATH_WEBROOT."DataEntry/index.php?pid=$project_id{$participantParams[$this_part]}'>$response_icon</a>";
		}
		// Append record name after email address IF has an identifier
		$emailDisplay = $attr['email'];
		if ($attr['record'] != '') {
			if ($attr['email'] == '') $emailDisplay .= "<i>{$lang['survey_284']}</i>";
			if ($attr['identifier'] != '') {
				$emailDisplay .= " &nbsp;<span style='color:#777;'>(ID {$attr['record']})</span>";
			}
		}
		// If identifiers are disabled
		if (!$enable_participant_identifiers && ($attr['response'] == "0" || $attr['identifier'] == '')) {
			// Set identifier text as "disabled"
			$attr['identifier'] = $lang['global_23'];
			// Set "disabled" class for identifier cells
			$editidentifier = "partIdentColDisabled";
		}
		// If identifier is blank, add space to make it clearly editable
		else {
			if ($attr['identifier'] == '') $attr['identifier'] = '&nbsp;';
		}
		// If this is the initial survey AND response was not created via Participant List, then do NOT display it here
		if (!$isFollowUpSurvey && $attr['email'] == '') {
			continue;
		}
		// Add to array
		$part_list_full[$i] = array();
		$part_list_full[$i][] = "<span style='display:none;'>{$attr['email']}{$email_num_raw}</span><div class='$editemail' id='editemail_{$this_part}' part='$this_part'>{$email_num}{$emailDisplay}";
		$part_list_full[$i][] = "<div class='$editidentifier' id='editidentifier_{$this_part}' part='$this_part'>{$attr['identifier']}";
		$part_list_full[$i][] = $link_icon;
		if ($attr['scheduled'] != '' && $attr['scheduled'] <= NOW ) {
			$part_list_full[$i][] = '-';
			// If email was scheduled (or was sent Immediately but cron has not sent it yet) and is sending right now, give special email icon
			$part_list_full[$i][] = RCView::img(array('src'=>'email_go.png','title'=>$lang['survey_346']));
		} else {
			$part_list_full[$i][] = ($attr['scheduled'] == '' ? '-' : RCView::img(array('src'=>'clock_fill.png','title'=>format_ts_mysql($attr['scheduled']))));
			// If email was sent or not yet, display icon for each
			$part_list_full[$i][] = ($attr['sent'] ? RCView::img(array('src'=>'email_check.png','title'=>$lang['survey_316'])) : RCView::img(array('src'=>'email_gray.gif','title'=>$lang['survey_317'])));
		}
		$part_list_full[$i][] = $response_icon;
		$part_list_full[$i][] = ($isFollowUpSurvey ? "" : '<a onclick=\'deleteParticipant('.$_GET['survey_id'].','.$_GET['event_id'].','.$this_part.');\'" href="javascript:;" style="color:#888;font-size:10px;text-decoration:underline;">'.$lang['survey_43'].'</a>');
		// Increment counter
		$i++;
	}
	
	// If no participants exist yet, render one row to let user know that
	if (empty($part_list_full)) 
	{
		// No participants exist yet
		$part_list_full[0] = array($lang['survey_44'],"","","","","","");
	} 
	
	// Get participant count
	$participant_count = count($part_list);		
	// Section the Participant List into multiple pages
	$num_per_page = 50;
	$limit_begin  = 0;
	if (isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) && $_GET['pagenum'] > 1) 
	{
		$limit_begin = ($_GET['pagenum'] - 1) * $num_per_page;
	}
	// Take full participant list and cut down to one page length of participants
	$part_list_full = array_slice($part_list_full, $limit_begin, $num_per_page);
	## Build the paging drop-down for participant list
	$pageDropdown = "<select id='pageNumSelect' onchange='loadPartList({$_GET['survey_id']},{$_GET['event_id']},this.value);' style='vertical-align:middle;font-size:11px;'>";
	//Calculate number of pages of for dropdown
	$num_pages = ceil($participant_count/$num_per_page);		
	//Loop to create options for dropdown
	for ($i = 1; $i <= $num_pages; $i++) {
		$end_num   = $i * $num_per_page;
		$begin_num = $end_num - $num_per_page + 1;
		$value_num = $end_num - $num_per_page;
		if ($end_num > $participant_count) $end_num = $participant_count;
		$pageDropdown .= "<option value='$i' " . ($_GET['pagenum'] == $i ? "selected" : "") . ">$begin_num - $end_num</option>";
	}
	if ($num_pages == 0) {
		$pageDropdown .= "<option value=''>0</option>";
	}
	$pageDropdown .= "</select>";
	$pageDropdown  = "<span style='margin-right:25px;'>{$lang['survey_45']} $pageDropdown {$lang['survey_133']} $participant_count</span>";
	
	// Build participant list table
	$partTableWidth = 750;
	$partTableHeaders = array();
	$partTableHeaders[] = array(239, $lang['global_33']);
	$partTableHeaders[] = array(200, "<span style='$partIdentHdrStyle'>{$lang['survey_250']} $partIdentDisabled</span> $enablePartIdent");
	$partTableHeaders[] = array(20,  $lang['design_196'], "center");
	$partTableHeaders[] = array(58, "<span class='wrap'>{$lang['survey_318']}</span>", "center");
	$partTableHeaders[] = array(50,  "<span class='wrap'>{$lang['survey_46']}</span>", "center");
	$partTableHeaders[] = array(58,  $lang['survey_47'], "center");
	$partTableHeaders[] = array(40,  " ", "center");
	// Table title
	$partTableTitle =  "<!-- Set value to be called via JS -->
						<input type='hidden' id='enable_participant_identifiers' value='$enable_participant_identifiers'>
						<!-- Participant List table -->
						<table id='partListTitle' cellspacing='0' style='width:100%;table-layout:fixed;'>
							<tr>
								<td valign='bottom'>
									<div style='vertical-align:middle;color:#000;font-size:14px;padding:0;font-family:arial;'>
										{$lang['survey_37']} 
										".RCView::span(array('style'=>'padding:0 3px;font-weight:normal;color:#666;font-size:12px;'), $lang['survey_33'])." 
										$surveyEventDropdown 
									</div>
									<div style='vertical-align:middle;font-weight:normal;padding:8px 0 0;color:#555;'>
										$pageDropdown
										<span id='addPartsBtnSpan'><button id='addPartsBtn' class='jqbuttonmed' ".($isFollowUpSurvey ? "disabled" : "")." onclick=\"
											addPart({$_GET['survey_id']},{$_GET['event_id']});
										\"><img src='".APP_PATH_IMAGES."user_add2.png' style='vertical-align:middle;'> <span style='vertical-align:middle;'>{$lang['survey_230']}</span></button></span>
										<button id='sendEmailsBtn' class='jqbuttonmed' onclick=\"sendEmails($survey_id,{$_GET['event_id']});\"><img src='".APP_PATH_IMAGES."email.png' style='vertical-align:middle;'> 
											<span style='vertical-align:middle;'>{$lang['survey_266']}</span></button>
									</div>
								</td>
								<td valign='bottom' style='text-align:right;width:150px;'>
									".((!$isFollowUpSurvey && $_GET['event_id'] == $Proj->firstEventId) ?
										"<div style='padding:0 0 5px 0;'>
											<button class='jqbuttonsm' style='color:#666;' onclick=\"deleteParticipants({$_GET['survey_id']},{$_GET['event_id']})\">{$lang['survey_166']}</button>
										</div>"
									  : ""
									)."
									<div style='padding:0'>
										<button class='jqbuttonmed' onclick=\"
											window.location.href='".APP_PATH_WEBROOT."Surveys/participant_export.php?pid=$project_id&survey_id={$_GET['survey_id']}&event_id={$_GET['event_id']}';
										\"><img src='".APP_PATH_IMAGES."xls.gif' style='vertical-align:middle;'> <span style='vertical-align:middle;'>{$lang['survey_229']}</span></button>
									</div>
								</td>
							</tr>
						</table>";
	// Build Participant List
	renderGrid("participant_table", $partTableTitle, $partTableWidth, "auto", $partTableHeaders, $part_list_full);
}