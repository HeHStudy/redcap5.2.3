<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{
	$_GET['survey_id'] = getSurveyId();
}

// Ensure the survey_id belongs to this project and that Post method was used
if (!checkSurveyProject($_GET['survey_id']) || $_SERVER['REQUEST_METHOD'] != "POST" || !isset($_POST['participants']))
{
	redirect(APP_PATH_WEBROOT . "Surveys/invite_participants.php?pid=" . PROJECT_ID);
}

// Ensure that participant_id's are all numerical
foreach (explode(",", $_POST['participants']) as $this_part)
{
	if (!is_numeric($this_part)) redirect(APP_PATH_WEBROOT . "Surveys/invite_participants.php?pid=" . PROJECT_ID);
}



// Check if this is a follow-up survey
$isFollowUpSurvey = ($_GET['survey_id'] != $Proj->firstFormSurveyId);

// Obtain current event_id
$_GET['event_id'] = getEventId();

// Set flag to send immediately or not schedule them (even if scheduling them for NOW)
$sendLater = (Cron::checkIfCronsActive());

// Get user info
$user_info = User::getUserInfo($userid);

// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// Set page header text
if ($sendLater && $_POST['emailSendTime'] != 'IMMEDIATELY') {
	// Emails will be scheduled
	renderPageTitle("<img src='".APP_PATH_IMAGES."clock_frame.png' class='imgfix2'> {$lang['survey_334']}");
} else {
	// Emails are being sent
	renderPageTitle("<img src='".APP_PATH_IMAGES."email.png' class='imgfix2'> {$lang['survey_138']}");
}


// Get email address for each participant_id (whether it's an initial survey or follow-up survey)
$participant_emails_ids = array();
if ($isFollowUpSurvey) {
	// Follow-up surveys (may not have an email stored for this specific survey/event, so can't simply query participants table)
	$participant_records = getRecordFromPartId(explode(",",$_POST['participants']));
	$responseAttr = getResponsesEmailsIdentifiers($participant_records);
	foreach ($participant_records as $partId=>$record) {
		$participant_emails_ids[$partId] = $responseAttr[$record]['email'];
	}
} else {
	// Initial survey: Obtain email from participants table
	$sql = "select participant_email, participant_id from redcap_surveys_participants 
			where survey_id = {$_GET['survey_id']} and participant_id in ({$_POST['participants']})";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$participant_emails_ids[$row['participant_id']] = $row['participant_email'];
	}
	$participant_records = getRecordFromPartId(array_keys($participant_emails_ids));
}


// Set the From address for the emails sent
$fromEmailTemp = 'user_email' . ($_POST['emailFrom'] > 1 ? $_POST['emailFrom'] : '');
$fromEmail = $$fromEmailTemp;
if (!isEmail($fromEmail)) $fromEmail = $user_email;

// Set some value for insert query into redcap_surveys_emails
$emailsTableSendTime = ($sendLater) ? "" : NOW;
$emailsTableStaticEmail = ($sendLater && $_POST['emailSendTime'] != 'IMMEDIATELY') ? $fromEmail : "";


// Add email info to tables
$sql = "insert into redcap_surveys_emails (survey_id, email_subject, email_content, email_sender, 
		email_account, email_static, email_sent) values 
		({$_GET['survey_id']}, '" . prep(filter_tags(label_decode($_POST['emailTitle']))) . "', 
		'" . prep(filter_tags(label_decode($_POST['emailCont']))) . "', {$user_info['ui_id']}, 
		'" . prep($_POST['emailFrom']) . "', ".checkNull($emailsTableStaticEmail).", ".checkNull($emailsTableSendTime).")";
if (db_query($sql))
{
	// Get email_id
	$email_id = db_insert_id();
	
	// Logging
	log_event($sql,"redcap_surveys_emails","MANAGE",$email_id,"email_id = $email_id,\nsurvey_id = {$_GET['survey_id']},\nevent_id = {$_GET['event_id']}","Email survey participants");
	
	// Get count of recipients
	$recipCount = count($participant_emails_ids);
	
	## SCHEDULE ALL EMAILS: Since cron is running, offload all emails to the cron emailer (even those to be sent immediately)
	if ($sendLater)
	{
		// If specified exact date/time, convert timestamp from mdy to ymd for saving in backend
		if ($_POST['emailSendTimeTS'] != '') {
			list ($this_date, $this_time) = explode(" ", $_POST['emailSendTimeTS']);
			$_POST['emailSendTimeTS'] = trim(date_mdy2ymd($this_date) . " $this_time:00");
		}
		
		// Set the send time for the emails
		$sendTime = ($_POST['emailSendTime'] == 'IMMEDIATELY') ? NOW : $_POST['emailSendTimeTS'];
		
		## REMOVE INVITATIONS ALREADY QUEUED: If any participants have already been scheduled, 
		## then remove all those instances so they can be scheduled again here (first part of query returns those where
		## record=null - i.e. from initial survey Participant List, and second part return those that are existing records).
		removeQueuedSurveyInvitations($_GET['survey_id'], $_GET['event_id'], array_keys($participant_emails_ids));
		
		## Add participants to the email queue (i.e. the emails_recipients table - since email_sent=NULL)
		$insertErrors = 0;
		foreach ($participant_emails_ids as $this_part=>$this_email) {
			// Add to emails_recipients table
			$sql = "insert into redcap_surveys_emails_recipients (email_id, participant_id) values ($email_id, $this_part)";
			if (db_query($sql)) {
				// Get email_recip_id
				$email_recip_id = db_insert_id();
				// Get record name (may not have one if this is an initial survey's Participant List)
				$this_record = (isset($participant_records[$this_part])) ? $participant_records[$this_part] : "";
				// Now add to scheduler_queue table
				$sql = "insert into redcap_surveys_scheduler_queue (email_recip_id, record, scheduled_time_to_send) 
						values ($email_recip_id, ".checkNull($this_record).", '".prep($sendTime)."')";
				if (!db_query($sql)) $insertErrors++;
			} else {
				$insertErrors++;
			}
		}
		
		// Confirmation text for IMMEDIATE sending
		if ($_POST['emailSendTime'] == 'IMMEDIATELY') 
		{
			print 	RCView::p(array('style'=>'margin:20px 0;'), $lang['survey_328']) .
					RCView::div(array('style'=>'font-weight:bold;margin-bottom:20px;'),
						RCView::span(array('style'=>'margin-right:15px;'), 
							($recipCount > 1 ? "$recipCount {$lang['survey_335']}" : "$recipCount {$lang['survey_336']}")
						) .
						RCView::img(array('src'=>'accept.png','class'=>'imgfix')) .
						RCView::span(array('style'=>'color:green;'), $lang['survey_329'])
					);
		} 		
		// Confirmation text for SCHEDULING the emails to be sent
		else
		{
			print 	RCView::p(array('style'=>'margin:20px 0;'), $lang['survey_331']) .
					RCView::div(array('style'=>'font-weight:bold;margin-bottom:20px;'),
						RCView::img(array('src'=>'accept.png','class'=>'imgfix')) .
						RCView::span(array('style'=>'color:green;'), $lang['survey_332']) .
						RCView::div(array('style'=>'padding:15px 0 0 5px;'),
							"$recipCount {$lang['survey_333']} " . 
							RCView::span(array('style'=>'color:#800000;'), format_ts_mysql($sendTime))
						)
					);
		}
		
		// Back button
		print RCView::button(array('onclick'=>"window.location.href=app_path_webroot+'Surveys/invite_participants.php?participant_list=1&pid='+pid+'&survey_id={$_GET['survey_id']}&event_id={$_GET['event_id']}';"), $lang['global_77']);
	}
	
	
	## SEND ALL EMAILS IN REAL-TIME ON THIS PAGE
	else
	{
		?>	
		<p style="margin:20px 0;">
			<?php echo $lang['survey_139'] ?>
		</p>
		<b><?php echo $lang['survey_140'] ?> <span id="send_progress">0</span> <?php echo $lang['survey_133'] ?> <?php echo $recipCount ?>
		<span id="progress_done" style="padding-left:15px;"><img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix"></span></b><br>
		<br><br>
		<input type="button" id="backBtn" value="<?php echo cleanHtml2($lang['global_77']) ?>" disabled="disabled" 
			onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Surveys/invite_participants.php?pid=<?php echo $project_id ?>&survey_id=<?php echo $_GET['survey_id'] ?>&event_id=<?php echo $_GET['event_id'] ?>';">
		
		<script type='text/javascript'>
		// Set global variables
		var emailsSent = 0;
		var partIds = '<?php echo implode(",", array_keys($participant_emails_ids)) ?>';
		// Increment the count of emails sent
		function incrementCount(data) {
			data = parseFloat(data);
			if (data != '' && data != '0' && data > 0) {
				emailsSent += data;
				$('#send_progress').html(emailsSent);
				if ($('#send_progress').html() == '<?php echo $recipCount ?>') {
					$('#progress_done').html('<img src="<?php echo APP_PATH_IMAGES ?>accept.png" class="imgfix"> <font color="green"><?php echo cleanHtml($lang['survey_329']) ?></font>');
					document.getElementById('backBtn').disabled=false;
				}
			} else {
				alert('<?php echo cleanHtml($lang['survey_330']) ?>');
			}
		}
		// Send a batch of emails
		function sendEmails(partIds) {
			$.post(app_path_webroot+'Surveys/email_participants_ajax.php?survey_id=<?php echo $_GET['survey_id'] ?>&event_id=<?php echo $_GET['event_id'] ?>&pid='+pid, { redcap_csrf_token: '<?php echo getCsrfToken() ?>', participants: partIds, email_id: <?php echo $email_id ?> }, function(data) { 
				//incrementCount(data); 
				var json_data = jQuery.parseJSON(data);
				if (json_data.length < 1) {
					alert(woops);
					return;
				}
				// Set variables
				var sentcount = json_data.sentcount;
				var nextPartIds = json_data.nextPartIds;
				// Increment the sent count
				incrementCount(sentcount);
				// Decide if we need to send another batch
				if (nextPartIds.length > 0) {
					sendEmails(nextPartIds);
				}
			}); 
		}
		$(function(){
			// Begin the sending of emails on pageload
			sendEmails(partIds);
		});
		</script>
		<?php
	}
}
else
{
	print $lang['global_01'];
}

// Footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	