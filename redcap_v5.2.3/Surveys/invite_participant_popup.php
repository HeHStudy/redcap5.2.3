<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "ProjectGeneral/form_renderer_functions.php";


// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id'])) $_GET['survey_id'] = getSurveyId();

// Validate form, event_id, and survey_id
if (!$Proj->validateEventId($_GET['event_id']) || !$Proj->validateSurveyId($_GET['survey_id']) || !isset($Proj->forms[$_POST['form']]))
{
	exit("0");
}


## DISPLAY POP-UP CONTENT
if ($_POST['action'] == 'popup')
{	
	## Set up email-to options
	$emailToDropdown = '';
	$emailToDropdownOptions = array();
	// Get participant email from Participant List's original invitation, if any
	if (isset($Proj->forms[$Proj->firstForm]['survey_id']))
	{
		$sql = "select p.participant_email from redcap_surveys_participants p, redcap_surveys_response r, redcap_surveys s 
				where s.project_id = ".PROJECT_ID." and p.survey_id = s.survey_id and p.participant_id = r.participant_id 
				and r.record = '".prep($_POST['record'])."' and s.form_name = '".$Proj->firstForm."' and p.event_id = ".$Proj->firstEventId." 
				and p.participant_email is not null and p.participant_email != '' limit 1";
		$q = db_query($sql);
		if (db_num_rows($q) > 0) {
			$val = db_result($q, 0);
			$emailToDropdownOptions[$val] = $val . " " . $lang['survey_275'];
		}
	}
	// Get email address if a field has been specified in project to capture participant's email
	if ($survey_email_participant_field != '') 
	{
		// Query record data to get field's value, if exists. (look over ALL events for flexibility)
		$sql = "select value from redcap_data where project_id = $project_id and record = '".prep($_POST['record'])."' 
				and field_name = '$survey_email_participant_field' and value != '' order by value limit 1";
		$q = db_query($sql);
		if (db_num_rows($q) > 0) {
			$val = db_result($q, 0);
			if (!isset($emailToDropdownOptions[$val])) {
				$emailToDropdownOptions[$val] = $val . " " . $lang['survey_273'];
			}
		}
	}
	// Get any emails used previously (static email address not connected to a participant_id or metadata field)
	$sql = "select e.static_email from redcap_surveys_participants p, redcap_surveys_response r, redcap_surveys s, 
			redcap_surveys_emails_recipients e where s.project_id = ".PROJECT_ID." and p.survey_id = s.survey_id 
			and p.participant_id = r.participant_id and r.record = '".prep($_POST['record'])."' 
			and p.participant_email is not null and p.participant_id = e.participant_id and e.static_email is not null";
	$q = db_query($sql);
	if (db_num_rows($q) > 0) {
		while ($row = db_fetch_assoc($q)) {
			if (!isset($emailToDropdownOptions[$row['static_email']])) {
				$emailToDropdownOptions[$row['static_email']] = $row['static_email'] . " " . $lang['survey_378'];
			}
		}
	}
	// Get HTML for email drop-down
	if (!empty($emailToDropdownOptions)) {
		$emailToDropdown =  RCView::select(array('class'=>'x-form-text x-form-field','style'=>'padding-right:0;height:22px; margin-bottom:5px;','id'=>'followupSurvEmailToDD','onchange'=>'inviteFollowupSurveyPopupSelectEmail(this);'), 
								array_merge(array(''=>"-- ".$lang['survey_274']." --"),$emailToDropdownOptions), '', 500
							) .
							RCView::br() . $lang['survey_276'] . RCView::SP . RCView::SP;
	}	
	
	// Create HTML content
	$html = RCView::fieldset(array('style'=>'padding-left:8px;background-color:#f3f5f5;border:1px solid #ccc;margin-bottom:10px;'),
				RCView::legend(array('style'=>'font-weight:bold;color:#333;'),
					RCView::img(array('src'=>'txt.gif','class'=>'imgfix')) . 
					$lang['survey_340']
				) .
				RCView::div(array('style'=>'padding:3px 8px 8px 2px;'),
					// Survey title
					RCView::div(array('style'=>'color:#800000;'),
						RCView::b($lang['survey_310']) . 
						RCView::span(array('style'=>'font-size:13px;margin-left:8px;'), 
							RCView::escape($Proj->surveys[$_GET['survey_id']]['title'])
						)				
					) .
					// Event name (if longitudinal)
					RCView::div(array('style'=>'color:#000066;padding-top:3px;' . ($longitudinal ? '' : 'display:none;')),
						RCView::b($lang['bottom_23']) . 
						RCView::span(array('style'=>'font-size:13px;margin-left:8px;'), 
							RCView::escape($Proj->eventInfo[$_GET['event_id']]['name_ext'])
						)
					)
				)
			) . 
			## SET TIME FOR SENDING EMAIL
			RCView::fieldset(array('style'=>'padding-left:8px;background-color:#f3f5f5;border:1px solid #ccc;margin-bottom:10px;'),
				RCView::legend(array('style'=>'font-weight:bold;color:#333;'),
					RCView::img(array('src'=>'clock_frame.png','class'=>'imgfix','style'=>'margin-right:3px;')) . 
					$lang['survey_347']
				) .
				RCView::div(array('style'=>'padding:4px 8px 7px 2px;'),
					RCView::radio(array('name'=>'emailSendTime','value'=>'IMMEDIATELY','class'=>'imgfix2','style'=>'','checked'=>'checked')) .
					$lang['survey_323'] . RCView::br() .
					RCView::radio(array('name'=>'emailSendTime','value'=>'EXACT_TIME','class'=>'imgfix2','style'=>'','onclick'=>"if ($('#emailSendTimeTS').val().length<1) $('#emailSendTimeTS').focus();")) .
					$lang['survey_324'] . 
					RCView::input(array('name'=>'emailSendTimeTS', 'id'=>'emailSendTimeTS', 'type'=>'text', 'class'=>'x-form-text x-form-field datetime_mdy', 
						'style'=>'width:92px;height:14px;line-height:14px;font-size:11px;margin-left:7px;padding-bottom:1px;','onkeydown'=>"if(event.keyCode==13){return false;}", 
						'onfocus'=>"$('#inviteFollowupSurvey input[name=\"emailSendTime\"][value=\"EXACT_TIME\"]').prop('checked',true); this.value=trim(this.value); if(this.value.length == 0 && $('.ui-datepicker:first').css('display')=='none'){ $(this).next('img').trigger('click');}",
						'onblur'=>"redcap_validate(this,'','','soft_typed','datetime_mdy',1,0)")) . 
					RCView::span(array('class'=>'df','style'=>'padding-left:5px;'), 'M-D-Y H:M') .
					// Get current time zone, if possible
					RCView::div(array('style'=>'margin:4px 0 0 22px;font-size:10px;line-height:10px;color:#777;'),
						"{$lang['survey_296']} <b>".getTimeZone()."</b>{$lang['survey_297']} <b>" . 
						date('m-d-Y H:i') . "</b>{$lang['period']}"
					)
				)
			) .	
			## COMPOSE EMAIL SUBJECT AND MESSAGE
			RCView::fieldset(array('style'=>'padding-left:8px;background-color:#f3f5f5;border:1px solid #ccc;'),
				RCView::legend(array('style'=>'font-weight:bold;color:#333;'),
					RCView::img(array('src'=>'email.png','class'=>'imgfix')) . 
					$lang['survey_339']
				) .
				RCView::div(array('style'=>'padding:10px 0 10px 2px;'),							
					RCView::table(array('cellspacing'=>'0','border'=>'0','width'=>'100%'),
						// From 
						RCView::tr('',
							RCView::td(array('style'=>'vertical-align:middle;width:50px;'),
								$lang['global_37']
							) .
							RCView::td(array('style'=>'vertical-align:middle;color:#555;'),
								User::emailDropDownList(true,'followupSurvEmailFrom','followupSurvEmailFrom')
							)
						) .
						// To
						RCView::tr('',
							RCView::td(array('style'=>'vertical-align:top;width:50px;padding-top:10px;'),
								$lang['global_38']
							) .
							RCView::td(array('style'=>'vertical-align:top;padding-top:10px;color:#666;'),
								$emailToDropdown . 
								"<input onblur=\"this.value=trim(this.value);if(this.value != ''){inviteFollowupSurveyPopupSelectEmail(this);redcap_validate(this,'','','soft_typed','email');}\" size='30' class='x-form-text x-form-field' style='font-family:arial;' type='text' id='followupSurvEmailTo'>"
							)
						) .
						// Subject
						RCView::tr('',
							RCView::td(array('style'=>'vertical-align:middle;padding:10px 0;width:50px;'),
								$lang['survey_103']
							) .
							RCView::td(array('style'=>'vertical-align:middle;padding:10px 0;'),
								'<input class="x-form-text x-form-field" style="font-family:arial;width:280px;" type="text" id="followupSurvEmailSubject" onkeydown="if(event.keyCode == 13){return false;}" value="'.cleanHtml2(str_replace('"', '&quot;', label_decode($emailSubject))).'"/>'
							)
						) .
						// Message
						RCView::tr('',
							RCView::td(array('colspan'=>'2','style'=>'padding:5px 0 10px;'),
								'<textarea class="x-form-field notesbox" id="followupSurvEmailMsg" style="font-family:arial;height:100px;width:95%;">'.nl2br(label_decode($emailContent)).'</textarea>'
							)
						)
					)
				) . 
				// Extra instructions
				RCView::div(array('style'=>'padding:0 5px;'),
					RCView::div(array('style'=>'font-size:11px;color:#800000;padding-bottom:6px;'),
						RCView::b($lang['survey_105']) . RCView::SP . $lang['survey_104']
					) .
					RCView::div(array('style'=>'font-size:11px;color:#555;padding-bottom:6px;'),
						$lang['survey_164'] .
						'&lt;b&gt; bold, &lt;u&gt; underline, &lt;i&gt; italics, &lt;a href="..."&gt; link, etc.'
					)
				)
			) .
			## HIDDEN INPUTS AND DIVS FOR JAVASCRIPT VALIDATION USE
			RCView::hidden(array('id'=>'now_mdyhm','value'=>date('m-d-Y H:i'))) .
			RCView::div(array('style'=>'display:none;','id'=>'langFollowupProvideTime'), $lang['survey_325']) .
			RCView::div(array('style'=>'display:none;','id'=>'langFollowupTimeInvalid'), $lang['survey_326'] . " " . date('m-d-Y H:i') . $lang['period']) .
			RCView::div(array('style'=>'display:none;','id'=>'langFollowupTimeExistsInPast'), $lang['survey_327']);
		
	// Return the HTML 
	print $html;
}




## SEND EMAIL
elseif ($_POST['action'] == 'email' && isset($_POST['email']))
{
	// Get user info
	$user_info = User::getUserInfo($userid);
	
	// Set vars
	$subject = filter_tags(label_decode($_POST['subject']));
	$content = filter_tags(label_decode($_POST['msg']));	
	
	// Set the From address for the emails sent
	$fromEmailTemp = 'user_email' . ($_POST['email_account'] > 1 ? $_POST['email_account'] : '');
	$fromEmail = $$fromEmailTemp;
	if (!isEmail($fromEmail)) $fromEmail = $user_email;
	
	// Set flag to send immediately or to schedule the email
	$sendLater = ($_POST['sendTime'] != 'IMMEDIATELY');
	
	// Set the send time for the emails. If specified exact date/time, convert timestamp from mdy to ymd for saving in backend
	if ($_POST['sendTimeTS'] != '') {
		list ($this_date, $this_time) = explode(" ", $_POST['sendTimeTS']);
		$_POST['sendTimeTS'] = trim(date_mdy2ymd($this_date) . " $this_time:00");
	}
	$sendTime = ($_POST['sendTime'] == 'IMMEDIATELY') ? NOW : $_POST['sendTimeTS'];

	// Set some value for insert query into redcap_surveys_emails
	$emailsTableSendTime = ($sendLater) ? "" : NOW;
	$emailsTableStaticEmail = ($sendLater) ? $fromEmail : "";
	
	// If using a static email that is not associated with participant, then store it in emails_recipients table
	$recipStaticEmail = (isset($_POST['static_email']) && $_POST['static_email'] == '1') ? $_POST['email'] : "";

	// Get participant_id and hash for this event-record-survey
	list ($participant_id, $hash) = getFollowupSurveyParticipantIdHash($_GET['survey_id'], $_POST['record'], $_GET['event_id']);
	
	// Add email info to tables
	$sql = "insert into redcap_surveys_emails (survey_id, email_subject, email_content, email_sender, 
			email_account, email_static, email_sent) values 
			({$_GET['survey_id']}, '" . prep($subject) . "', '" . prep($content) . "', {$user_info['ui_id']}, 
			'" . prep($_POST['email_account']) . "', ".checkNull($emailsTableStaticEmail).", ".checkNull($emailsTableSendTime).")";
	if (!db_query($sql)) exit("0");
	$email_id = db_insert_id();
	
	// Insert into emails_recipients table
	$sql = "insert into redcap_surveys_emails_recipients (email_id, participant_id, static_email) 
			values ($email_id, $participant_id, ".checkNull($recipStaticEmail).")";
	if (db_query($sql)) {
		// Get email_recip_id
		$email_recip_id = db_insert_id();
		// First, remove invitation if already queued
		removeQueuedSurveyInvitations($_GET['survey_id'], $_GET['event_id'], array($participant_id));		
	} else {
		// If query failed, then undo previous query and return error
		db_query("delete from redcap_surveys_emails where email_id = $email_id");
		exit("0");
	}
	
	
	## SCHEDULE THE INVITATION FOR LATER
	if ($sendLater)
	{
		// Now add to scheduler_queue table
		$sql = "insert into redcap_surveys_scheduler_queue (email_recip_id, record, scheduled_time_to_send) 
				values ($email_recip_id, ".checkNull($_POST['record']).", '".prep($sendTime)."')";
		if (db_query($sql)) {
			// Logging
			log_event($sql,"redcap_surveys_emails","MANAGE",$email_id,"email_id = $email_id,\nparticipant_id = $participant_id","Email survey participant");
			// Return confirmation message in pop-up
			print 	RCView::div(array('class'=>'darkgreen','style'=>'margin:20px 0;'),
						RCView::table(array('cellspacing'=>'10','style'=>'width:100%;'),
							RCView::tr(array(),
								RCView::td(array('style'=>'padding:0 20px;'),
									RCView::img(array('src'=>'check_big.png'))
								) .
								RCView::td(array('style'=>'font-size:14px;font-weight:bold;font-family:verdana;line-height:22px;'),
									$lang['survey_348'] . 
									RCView::div(array('style'=>'color:green;'), $_POST['email']) .
									RCView::div(array('style'=>'color:#555;'), "(" . format_ts_mysql($sendTime) . ")")
								)
							)
						)		
					);
		}
	}
	
	## SEND THE INVITATINO NOW
	else
	{	
		// Send the email to recipient
		$email = new Message();
		$email->setFrom($fromEmail);
		$email->setTo($_POST['email']); 
		$email->setSubject($subject);	
		$emailContents = '
			<html><body style="font-family:Arial;font-size:10pt;">
			'.nl2br($content).'<br /><br />	
			'.$lang['survey_134'].'<br />
			<a href="' . APP_PATH_SURVEY_FULL . '?s=' . $hash . '">'.$Proj->surveys[$_GET['survey_id']]['title'].'</a><br /><br />
			'.$lang['survey_135'].'<br />
			' . APP_PATH_SURVEY_FULL . '?s=' . $hash . '<br /><br />	
			'.$lang['survey_137'].'
			</body></html>';
		$email->setBody($emailContents);
		// Send email
		if (!$email->send()) {
			db_query("delete from redcap_surveys_emails where email_id = $email_id");
			exit("0");
		}
		// Logging
		log_event($sql,"redcap_surveys_emails","MANAGE",$email_id,"email_id = $email_id,\nparticipant_id = $participant_id","Email survey participant");
		// Return confirmation message in pop-up
		print 	RCView::div(array('class'=>'darkgreen','style'=>'margin:20px 0;'),
					RCView::table(array('cellspacing'=>'10','style'=>'width:100%;'),
						RCView::tr(array(),
							RCView::td(array('style'=>'padding:0 0 0 50px;'),
								RCView::img(array('src'=>'check_big.png'))
							) .
							RCView::td(array('style'=>'font-size:14px;font-weight:bold;font-family:verdana;line-height:22px;'),
								$lang['survey_225'] . 
								RCView::div(array('style'=>'color:green;'), $_POST['email'])
							)
						)
					)		
				);
	}
}

## ERROR
else
{
	exit("0");
}