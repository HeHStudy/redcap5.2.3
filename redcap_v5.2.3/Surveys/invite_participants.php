<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// If not using a type of project with surveys, then don't allow user to use this page.
if (!$surveys_enabled) redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");

// If no survey id in URL, then determine what it should be here (first available survey_id)
if (!isset($_GET['survey_id']))
{
	if ($Proj->firstFormSurveyId != null) {
		// Get first form's survey_id
		$_GET['survey_id'] = getSurveyId();
	} elseif (!empty($Proj->surveys)) {
		// Surveys exist, but the first form is not a survey. So get the first available survey_id.
		$_GET['survey_id'] = array_pop(array_keys($Proj->surveys));
		// If first form isn't a survey and user didn't explicity click the Public Survey Link tab, then redirect on to Participant List
		if (!isset($_GET['public_survey']) && !isset($_GET['participant_list']) && !isset($_GET['email_log'])) {
			redirect(PAGE_FULL . "?pid=$project_id&participant_list=1");
		}
	} elseif (empty($Proj->surveys)) {
		// If no surveys have been enabled, then redirect to Online Designer to enable them
		redirect(APP_PATH_WEBROOT . "Design/online_designer.php?pid=$project_id&dialog=enable_surveys");
	}
}

// Ensure the survey_id belongs to this project
if (!$Proj->validateSurveyId($_GET['survey_id']))
{
	redirect(APP_PATH_WEBROOT . "Surveys/create_survey.php?pid=$project_id&view=showform&redirectInvite=1");
}

// Obtain current event_id
if (!isset($_GET['event_id'])) {
	$_GET['arm_id']   = getArmId();
	$_GET['event_id'] = $Proj->getFirstEventIdArmId($_GET['arm_id']);
}

// Retrieve survey info
$q = db_query("select * from redcap_surveys where project_id = $project_id and survey_id = " . $_GET['survey_id']);
foreach (db_fetch_assoc($q) as $key => $value)
{
	$$key = trim(html_entity_decode($value, ENT_QUOTES));
}
	
// VALIDATE EVENT_ID FOR SURVEY: If event_id isn't applicable for the survey_id that we have here, then get first event_id that is applicable.
if (!$Proj->validateEventIdSurveyId($_GET['event_id'], $_GET['survey_id']))
{
	// Get first event for this survey
	foreach ($Proj->eventsForms as $this_event_id=>$these_forms) {
		if (in_array($Proj->surveys[$_GET['survey_id']]['form_name'], $these_forms)) {
			$_GET['event_id'] = $this_event_id;
			break;
		}
	}
}

// Check if this is a follow-up survey
$isFollowUpSurvey = $Proj->isFollowUpSurvey($_GET['survey_id']);

// Get all previously sent emails to put into hidden Dropdown Menu in pop-up dialog for sending participant emails
$emailSelect = array();
$sql = "select email_content, email_sent from redcap_surveys_emails where survey_id = {$_GET['survey_id']} order by email_id";
$q = db_query($sql);		
$divDispPrevEmails_display = (!db_num_rows($q) ? "display:none;" : "");
//Loop through query
while ($row = db_fetch_array($q)) 
{
	//Remove HTML tags and quotes because they cause problems
	$row['email_content'] = str_replace("\"","&quot;", RCView::escape(label_decode($row['email_content']),false));
	//Do not show repeating emails (if same email was sent more than once)
	if (!isset($emailSelect[$row['email_content']])) 
	{
		// Make sure text is not too long and format timestamp
		$this_val = format_ts_mysql($row['email_sent']).' - '.$row['email_content'];
		if (strlen($this_val) >= 85) $this_val = substr($this_val, 0, 83).'...';
		// Store in array
		$emailSelect[$row['email_content']] = $this_val;
	}
}



// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
// Call JS files
callJSfile("swfobject.js"); // Check if Flash is enabled for browser so we can enabled copy-to-clipboard functionality
callJSfile("invite_participants.js");
// Title
renderPageTitle("<img src='".APP_PATH_IMAGES."send.png' class='imgfix'> ".$lang['app_22']);


// TABS
?>
<div id="sub-nav" style="margin:5px 0 20px;">
	<ul>
		<li<?php echo ((isset($_GET['public_survey']) || (!isset($_GET['email_log']) && !isset($_GET['participant_list']))) ? ' class="active"' : '') ?>>
			<a href="<?php echo APP_PATH_WEBROOT ?>Surveys/invite_participants.php?public_survey=1&pid=<?php echo $project_id ?>" style="font-size:13px;color:#393733;padding:6px 9px 7px 10px;"><img src="<?php echo APP_PATH_IMAGES ?>link.png" class="imgfix" style="padding-right:1px;"> <?php echo $lang['survey_279'] ?></a>
		</li>
		<li<?php echo (isset($_GET['participant_list']) ? ' class="active"' : '') ?>>
			<a href="<?php echo APP_PATH_WEBROOT ?>Surveys/invite_participants.php?participant_list=1&pid=<?php echo $project_id ?>" style="font-size:13px;color:#393733;padding:6px 9px 7px 10px;"><img src="<?php echo APP_PATH_IMAGES ?>group.png" class="imgfix" style="padding-right:1px;"> <?php echo $lang['survey_37'] ?></a>
		</li>
		<li<?php echo (isset($_GET['email_log']) ? ' class="active"' : '') ?>>
			<a href="<?php echo APP_PATH_WEBROOT ?>Surveys/invite_participants.php?email_log=1&pid=<?php echo $project_id ?>" style="font-size:13px;color:#393733;padding:6px 9px 7px 10px;"><img src="<?php echo APP_PATH_IMAGES ?>mails_stack.png" class="imgfix" style="padding-right:1px;"> <?php echo $lang['survey_350'] ?></a>
		</li>
	</ul>
</div>
<div class="clear"></div>
<?php










## SURVEY INVITATION EMAIL LOG
if (isset($_GET['email_log']))
{
	// Instantiate object
	$surveyScheduler = new SurveyScheduler();
	// Instructions
	print RCView::p(array('style'=>'margin-bottom:20px;'), 
			$lang['survey_399']." \"".getTimeZone()."\"".$lang['survey_297']." ".format_ts_mysql(NOW).$lang['period']
		  );
	// Display a table listing all survey invitations (past, present, and future)
	print $surveyScheduler->renderSurveyInvitationLog();
	?>
	<script type="text/javascript">
	$(function(){
		// Set datetime pickers
		$('.filter_datetime_mdy').datetimepicker({
			buttonText: 'Click to select a date', yearRange: '-100:+10', changeMonth: true, changeYear: true, dateFormat: 'mm-dd-yy',
			hour: currentTime('h'), minute: currentTime('m'), buttonText: 'Click to select a date/time', 
			timeFormat: 'hh:mm', constrainInput: true
		});		
		// Add fade mouseover for "delete scheduled invitation" icons
		$(".inviteLogDelIcon").hover(function() {
			$(this).removeClass('opacity50');
		}, function() {
			$(this).addClass('opacity50');
		});
	})
	</script>
	<?php
}









## PUBLIC SURVEY LINK
if (!(isset($_GET['participant_list']) || isset($_GET['email_log'])))
{
	// Obtain the public survey hash [for current arm]
	$hash = getSurveyHash($_GET['survey_id'], $_GET['event_id']);
	
	//print_array($Proj->events);
	
	// Build drop-down list of FIRST surveys/events for EACH arm
	if ($multiple_arms) {
		// Create drop-down of ALL surveys and, if longitudinal, the events for which they're designated
		$surveyEventOptions = array();
		foreach ($Proj->events as $this_arm=>$arm_attr) 
		{
			$this_event_id = array_shift(array_keys($arm_attr['events']));
			// Add event name
			$event_name = $Proj->eventInfo[$this_event_id]['name_ext'];
			// Truncate survey title if too long
			$survey_title = $Proj->surveys[$Proj->firstFormSurveyId]['title'];
			if (strlen($survey_title.$event_name) > 70) {
				$survey_title = substr($survey_title, 0, 67-strlen($event_name)) . "...";
			}			
			// Add this survey/event as drop-down option
			$surveyEventOptions[$arm_attr['id']] = "\"$survey_title\" - $event_name";
		}
		// Collect HTML
		$surveyEventDropdown = RCView::select(array('class'=>"x-form-text x-form-field",
			'style'=>'max-width:400px;font-weight:bold;padding-right:0;height:22px;font-size:11px;',
			'onchange'=>"var val=this.value;showProgress(1);setTimeout(function(){window.location.href=app_path_webroot+page+'?pid='+pid+'&public_survey=1&arm_id='+val;},300);"), 
				$surveyEventOptions, (isset($_GET['arm_id']) ? $_GET['arm_id'] : $Proj->firstArmId), 500
			);
	}
	
	?>
	<!-- Public survey link -->
	<div style="font-size:11px;max-width:700px;">
		
		<p><?php echo $lang['survey_165'] ?></p>
		
		<?php 
		if ($Proj->firstFormSurveyId == null) {
			// If first form is not yet a survey, then cannot display public survey link, so inform user to enable form as survey
			print 	RCView::div(array('class'=>'yellow','style'=>'padding:10px;'), 
						RCView::div(array('style'=>'font-weight:bold;'), 
							RCView::img(array('src'=>'exclamation_orange.png','class'=>'imgfix')) .
							$lang['survey_352']
						) .
						$lang['survey_353'] .
						RCView::div(array('style'=>'padding-top:15px;'), 
							RCView::button(array('class'=>'jqbuttonmed','style'=>'','onclick'=>"window.location.href=app_path_webroot+'Surveys/create_survey.php?pid=$project_id&view=showform&page={$Proj->firstForm}&redirectInvite=1';"),
								$lang['survey_354']
							)
						)
					);

		} else { ?>
			
			<p>
				<font style="color:#800000;"><?php echo $lang['survey_72'] ?></font> <?php echo $lang['survey_73'] ?>
				<?php if ($enable_url_shortener) { ?>
					<a href="javascript:;" onclick="getShortUrl('<?php echo $hash ?>', <?php echo $_GET['survey_id'] ?>)" style="text-decoration:underline;"><?php echo $lang['survey_74'] ?></a> 
					<?php echo $lang['global_47'] ?>
				<?php } ?>
				<a href="javascript:;" onclick="if($('#embed_div').css('display') == 'none'){ $('#embed_div').show('fade','fast'); } $('#embed_div').effect('highlight', 'slow');" 
					style="text-decoration:underline;"><?php echo $lang['survey_75'] ?></a>.
			</p>
			
			
			<?php if ($multiple_arms) { ?>
				<!-- Drop-down for changing arm -->
				<p style="font-weight:bold;color:#800000;">
					<?php echo $lang['survey_76'] ?>&nbsp; <?php echo $surveyEventDropdown ?>
				</p>
			<?php } ?>
			
			<!-- Public survey URL -->
			<div style="padding:5px 0px 6px;">
				<span style="vertical-align:middle;font-size:12px;"><b><?php echo $lang['survey_233'] ?></b></span>
				<!-- Input box and Flash object for copying URL to clipboard -->
				<?php $flashObjectName = 'longurl'; ?>
				<input id="<?php echo $flashObjectName ?>" value="<?php echo APP_PATH_SURVEY_FULL . "?s=$hash" ?>" onclick="this.select();" readonly="readonly" class="staticInput" style="width:320px;">&nbsp;
				<object id='flashObj_<?php echo $flashObjectName ?>' codebase='<?php echo APP_PATH_WEBROOT ?>Resources/misc/swflash9.0.0.0.cab' width="127" height="23" style="vertical-align:middle;">
					<param name='allowScriptAccess' value='sameDomain' />
					<param name='allowFullScreen' value='false' />
					<param name='movie' value='<?php echo APP_PATH_WEBROOT ?>Resources/misc/clipboard.swf' />
					<param name='quality' value='high' />
					<param name='bgcolor' value='#ffffff' />
					<param name='wmode' value='transparent' />
					<param name='flashvars' value="callback=copyUrlToClipboard&callbackArg=<?php echo $flashObjectName ?>" />
					<embed class='clipboardAction' src='<?php echo APP_PATH_WEBROOT ?>Resources/misc/clipboard.swf' flashvars="callback=copyUrlToClipboard&callbackArg=<?php echo $flashObjectName ?>" quality='high' bgcolor='#ffffff' width="127" height="23" wmode='transparent' name='clipboard' allowscriptaccess='always' allowfullscreen='false' type='application/x-shockwave-flash' pluginspage='http://www.adobe.com/go/getflashplayer' style="vertical-align:middle;" />
				</object>
			</div>
			
			<!-- Short URL -->
			<div id="shorturl_loading_div" style="font-size:12px;display:none;padding:10px 0px;font-weight:bold;">
				<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix">&nbsp; <?php echo $lang['survey_79'] ?><br><br>
			</div>	
			<div id="shorturl_div" style="display:none;font-size:12px;padding:0 0 10px;">
				<div style="padding:0px 0px 4px 10px;color:#444;"><?php echo $lang['global_46'] ?></div>
				<b><?php echo $lang['survey_234'] ?></b>&nbsp;&nbsp;
				<!-- Input box and Flash object for copying URL to clipboard -->
				<?php $flashObjectName = 'shorturl'; ?>
				<input id="<?php echo $flashObjectName ?>" value="" onclick="this.select();" readonly="readonly" class="staticInput" style="width:120px;">&nbsp;
				<object id='flashObj_<?php echo $flashObjectName ?>' codebase='<?php echo APP_PATH_WEBROOT ?>Resources/misc/swflash9.0.0.0.cab' width="127" height="23" style="vertical-align:middle;">
					<param name='allowScriptAccess' value='sameDomain' />
					<param name='allowFullScreen' value='false' />
					<param name='movie' value='<?php echo APP_PATH_WEBROOT ?>Resources/misc/clipboard.swf' />
					<param name='quality' value='high' />
					<param name='bgcolor' value='#ffffff' />
					<param name='wmode' value='transparent' />
					<param name='flashvars' value="callback=copyUrlToClipboard&callbackArg=<?php echo $flashObjectName ?>" />
					<embed class='clipboardAction' src='<?php echo APP_PATH_WEBROOT ?>Resources/misc/clipboard.swf' flashvars="callback=copyUrlToClipboard&callbackArg=<?php echo $flashObjectName ?>" quality='high' bgcolor='#ffffff' width="127" height="23" wmode='transparent' name='clipboard' allowscriptaccess='always' allowfullscreen='false' type='application/x-shockwave-flash' pluginspage='http://www.adobe.com/go/getflashplayer' style="vertical-align:middle;" />
				</object>
			</div>
			
			<!-- Embed code for URL -->
			<div id='embed_div' style="font-size:12px;display:none;padding:0 0 5px;">
				<p><?php echo $lang['survey_240'] ?></p>
				<div>
					<b><?php echo $lang['survey_235'] ?> <span style="font-size:12px;">&lt; &gt;</span></b>&nbsp;
					<!-- Input box and Flash object for copying URL to clipboard -->
					<?php $flashObjectName = 'embedurl'; ?>
					<input id="<?php echo $flashObjectName ?>" value="<a href=&quot;<?php echo APP_PATH_SURVEY_FULL . "?s=$hash" ?>&quot;><?php echo $lang['survey_83'] ?></a>" onclick="this.select();" readonly="readonly" class="staticInput" style="width:340px;">&nbsp;
					<object id='flashObj_<?php echo $flashObjectName ?>' codebase='<?php echo APP_PATH_WEBROOT ?>Resources/misc/swflash9.0.0.0.cab' width="127" height="23" style="vertical-align:middle;">
						<param name='allowScriptAccess' value='sameDomain' />
						<param name='allowFullScreen' value='false' />
						<param name='movie' value='<?php echo APP_PATH_WEBROOT ?>Resources/misc/clipboard.swf' />
						<param name='quality' value='high' />
						<param name='bgcolor' value='#ffffff' />
						<param name='wmode' value='transparent' />
						<param name='flashvars' value="callback=copyUrlToClipboard&callbackArg=<?php echo $flashObjectName ?>" />
						<embed class='clipboardAction' src='<?php echo APP_PATH_WEBROOT ?>Resources/misc/clipboard.swf' flashvars="callback=copyUrlToClipboard&callbackArg=<?php echo $flashObjectName ?>" quality='high' bgcolor='#ffffff' width="127" height="23" wmode='transparent' name='clipboard' allowscriptaccess='always' allowfullscreen='false' type='application/x-shockwave-flash' pluginspage='http://www.adobe.com/go/getflashplayer' style="vertical-align:middle;" />
					</object>
				</div>
			</div>
			
			<!-- Buttons to open or email survey -->
			<div style="padding:15px 0 10px;margin-left:5px;">
				<button class="jqbuttonmed" onclick="surveyOpen($('#longurl').val(),0);"
					><img src="<?php echo APP_PATH_IMAGES ?>arrow_right_curve.png" style="vertical-align:middle;"><span style="vertical-align:middle;"> <?php echo $lang['survey_236'] ?></span></button>
				&nbsp;
				<button class="jqbuttonmed" onclick="sendSelfEmail(<?php echo $_GET['survey_id'] ?>,$('#longurl').val());"
					><img src="<?php echo APP_PATH_IMAGES ?>email.png" style="vertical-align:middle;"><span style="vertical-align:middle;"> <?php echo $lang['survey_237'] ?></span></button>
			</div>
			<?php 
			
			## AUTOMATED INVITES CHECK
			// If auto invites are enabled for the first event-first instrument but the email field has not been designated yet,
			// then give a warning.
			// Check if AI are enabled for the first event-first instrument
			$sql = "select 1 from redcap_surveys_scheduler where condition_surveycomplete_survey_id = {$Proj->firstFormSurveyId}
					and condition_surveycomplete_event_id = {$Proj->firstEventId} and active = 1 limit 1";
			$q = db_query($sql);
			if (db_num_rows($q)) {
				// Yes, they are enabled
				if ($survey_email_participant_field == '') {
					// Email field is not designated. Tell user to designate one.
					print 	RCView::div(array('class'=>'red','style'=>'padding:10px;margin-top:15px;'), 
								RCView::div(array('style'=>'font-weight:bold;'), 
									RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) .
									$lang['global_48'].$lang['colon']." ".$lang['survey_481']
								) .
								$lang['survey_480'] . RCView::br() . RCView::br() .
								$lang['survey_482']
							);
				} elseif ($survey_email_participant_field != '' && !isset($Proj->forms[$Proj->firstForm]['fields'][$survey_email_participant_field])) {
					// Email field is designated but does not exist on first instrument (problematic)					
					print 	RCView::div(array('class'=>'red','style'=>'padding:10px;margin-top:15px;'), 
								RCView::div(array('style'=>'font-weight:bold;'), 
									RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) .
									$lang['global_48'].$lang['colon']." ".$lang['survey_483']
								) .
								$lang['survey_484'] . ' ' . 
								RCView::b($survey_email_participant_field) . ' ("' . $Proj->metadata[$survey_email_participant_field]['element_label'] . '")' .
								$lang['period'] . RCView::br() . RCView::br() .
								$lang['survey_482']
							);
				}
			}
		} 
		?>
		
	</div>
	<?php
}








## PARTICIPANT LIST
if (isset($_GET['participant_list']))
{
	?>
	<!-- Participant List Section -->	
	<div style="margin:0 0 20px;max-width:700px;line-height: 1.4em;">
		
		<div style="margin:10px 0;">
			<div>
				<?php echo $lang['survey_355'] . " " . ($isFollowUpSurvey ? "" : $lang['survey_356']) ?>
				<a href="javascript:;" onclick="$('#partListInstrMore').toggle('fade');" style="text-decoration:underline;"><?php echo $lang['survey_86'] ?></a>
			</div>
			<div id="partListInstrMore" style="display:none;margin-top:15px;">
				<?php echo $lang['survey_87'] ?> 
				<b><?php echo $lang['survey_88'] ?></b> <img src="<?php echo APP_PATH_IMAGES ?>tick_circle_frame.png" style="vertical-align:middle;"> <?php echo $lang['global_47'] ?>
				<b><?php echo $lang['survey_89'] ?></b> <img src="<?php echo APP_PATH_IMAGES ?>circle_orange_tick.png" style="vertical-align:middle;"><?php echo $lang['survey_91'] ?> 
				<b><?php echo $lang['survey_90'] ?></b> <img src="<?php echo APP_PATH_IMAGES ?>stop_gray.png" style="vertical-align:middle;"><?php echo $lang['period'] ?>
				<u><?php echo $lang['survey_92'] ?></u> 
				<?php echo $lang['survey_93'] ?>
			</div>
		</div>
		
	</div>

	<!-- Participant List -->
	<div id="partlist_outerdiv" style="margin-bottom:20px;">
		<?php
		// Build Participant List
		include APP_PATH_DOCROOT . 'Surveys/participant_list.php';	
		?>
	</div>
		
	<!-- Hidden "Add Participants" dialog -->
	<div id="emailAdd" title="Add Emails to Participant List" style="display:none;font-family:arial;">
		<p>
			<?php echo $lang['survey_267'] ?>
			<span class="partIdentInstrText"><?php echo $lang['survey_268'] ?></span>
			<?php if ($multiple_arms) { ?>
				<br><br><?php echo $lang['survey_97'] ?> <b><?php echo $armNameFull ?></b>.
			<?php } ?>
		</p>
		<textarea id="newPart" style="font-family:arial;width:95%;height:100px;"></textarea>
		<p style="color:#111111;margin-top:5px;margin-bottom:10px;">
			<span class="partIdentInstrText"><b><?php echo $lang['survey_98'] ?></b>&nbsp; <?php echo $lang['survey_99'] ?></span> &nbsp;&nbsp;&nbsp;
			<font color="#800000"><?php echo $lang['survey_100'] ?></font>
			<br><br>
			<span style="font-size:11px;color:#555555;margin-top:5px;">
				<b><?php echo $lang['survey_101'] ?> #1:</b>&nbsp; john.williams@hotmail.com<br>
				<b><?php echo $lang['survey_101'] ?> #2:</b>&nbsp; jimtaylor@yahoo.com<span class="partIdentInstrText">, Jim Taylor</span><br>
				<b><?php echo $lang['survey_101'] ?> #3:</b>&nbsp; putnamtr@gmail.com<span class="partIdentInstrText">, ID 4930-72</span><br>
			</span>
		</p>
	</div>

	<!-- Hidden pop-up div to display warning message about trying to click identifiers when they are disabled -->
	<div id='tooltipIdentDisabled' class='tooltip' style='max-width:300px;padding:3px 6px;z-index:9999;'>
		<?php echo "<b>{$lang['global_23']}{$lang['colon']}</b><br>" . ($status < 1 ? $lang['survey_262'] : $lang['survey_263']) ?>
	</div>

	<!-- Hidden pop-up div to display warning message about editing email/identifier -->
	<div id='tooltipEdit' class='tooltip' style='max-width:300px;padding:3px 6px;z-index:9999;'>
		<?php echo "<b>{$lang['survey_264']}</b><br>{$lang['survey_242']}" ?>
	</div>

	<!-- Hidden pop-up div to enable/disable Participant Identifiers -->
	<div id='popupEnablePartIdent' style='display:none;'></div>
	<?php
}
?>



<!-- Hidden pop-up div to display warning message about clicking responded icon -->
<div id='tooltipViewResp' class='tooltip' style='max-width:300px;padding:3px 6px;z-index:9999;'>
	<?php echo "<b>{$lang['survey_244']}</b><br>{$lang['survey_243']}" ?>
</div>
	

	
<script type="text/javascript">
// Language vars
var langSave = '<?php echo cleanHtml($lang['designate_forms_13']) ?>';	
// Note the first form's survey_id (referenced in order to disable editing email/identifier for followup surveys)
var firstFormSurveyId = <?php echo is_numeric($Proj->firstFormSurveyId) ? $Proj->firstFormSurveyId : "''" ?>;
var firstEventId = <?php echo $Proj->firstEventId ?>;

$(function(){
	// If "Add Participants" button is disabled but user tried to click it, then provide user with message why they cannot add participants
	$('#addPartsBtnSpan').click(function(){
		if ($('#addPartsBtn').prop('disabled')) {
			simpleDialog('<?php echo cleanHtml($lang['survey_389']) ?>','<?php echo cleanHtml($lang['survey_388']) ?>');
		}
	});
});

<?php if ((isset($_GET['public_survey']) || (!isset($_GET['email_log']) && !isset($_GET['participant_list'])))) { ?>
// Check Flash version to see if we should hide Flash action triggers on page
$(function(){
	var playerVersion = swfobject.getFlashPlayerVersion();
	if (!(isNumeric(playerVersion.major) && playerVersion.major > 0)) {
		$('.clipboardAction').hide();
	}
});
<?php } ?>

$(function(){
<?php if (!$isFollowUpSurvey) { ?>
	// Enable editing of participant list email/identifier
	enableEditParticipant();
	// Reset participant list editing after sorting it by clicking header
	$('div#participant_table table th').click(function(){
		setTimeout(function(){ enableEditParticipant() },100);
	});	
<?php } else { ?>
	// Pop-up tooltip: Give warning message to user if tries to click partial/complete icon to view response IF identifier is not defined
	noViewResponseTooltip();
	// Pop-up tooltip: Denote that user can click partial/complete icon to view response
	$('.viewresponse, .partLink').tooltip({
		position: 'center right',
		offset: [0, 10],
		delay: 100
	});
<?php } ?>
});

// Edit the participant's email address and identifier via ajax
function editPartEmail(thisPartId) {
	var email = trim($('#partNewEmail_'+thisPartId).val());
	if (email.length<1) {
		alert('Enter an email address');
		return;
	}
	$.post(app_path_webroot+'Surveys/edit_participant.php?pid='+pid+'&survey_id=<?php echo $_GET['survey_id'] ?>&event_id=<?php echo $_GET['event_id'] ?>', { email: email, participant_id: thisPartId }, function(data){
		var data2 = data;
		if (data.length<1) data2 = '&nbsp;';
		$('#editemail_'+thisPartId).html(data2);
		$('#editemail_'+thisPartId).addClass('edit_saved');
		setTimeout(function(){
			$('#editemail_'+thisPartId).removeClass('edit_saved');
		},2000);
		enableEditParticipant();
	});
}
function editPartIdentifier(thisPartId) {
	var identifier = trim($('#partNewIdentifier_'+thisPartId).val());
	$.post(app_path_webroot+'Surveys/edit_participant.php?pid='+pid+'&survey_id=<?php echo $_GET['survey_id'] ?>&event_id=<?php echo $_GET['event_id'] ?>', { identifier: identifier, participant_id: thisPartId }, function(data){
		var data2 = data;
		if (data.length<1) data2 = '&nbsp;';
		$('#editidentifier_'+thisPartId).html(data2);
		$('#editidentifier_'+thisPartId).addClass('edit_saved');
		setTimeout(function(){
			$('#editidentifier_'+thisPartId).removeClass('edit_saved');
		},2000);
		enableEditParticipant();
	});
}

// Delete ALL participants from list
function deleteParticipants(survey_id,event_id) {
	simpleDialog('<?php echo cleanHtml($lang['survey_359']) ?>','<?php echo cleanHtml($lang['survey_358']) ?>',null,null,null,'<?php echo cleanHtml($lang['global_53']) ?>',
		"deleteParticipants2("+survey_id+","+event_id+");",'<?php echo cleanHtml($lang['survey_368']) ?>');
}
function deleteParticipants2(survey_id,event_id) {
	simpleDialog('<?php echo cleanHtml($lang['survey_370']) ?>','<?php echo cleanHtml($lang['survey_369']) ?>',null,null,null,'<?php echo cleanHtml($lang['global_53']) ?>',
		"deleteParticipantsDo("+survey_id+","+event_id+");",'<?php echo cleanHtml($lang['survey_368']) ?>');
}
function deleteParticipantsDo(survey_id,event_id) {
	$.post(app_path_webroot+'Surveys/delete_participants.php?pid='+pid+'&survey_id='+survey_id+'&event_id='+event_id, { }, function(data){
		if (data == '1') {
			loadPartList(survey_id,event_id,1,'<?php echo cleanHtml($lang['survey_365']) ?>','<?php echo cleanHtml($lang['survey_364']) ?>');
		} else {
			alert(woops);
		}
	});
}

// Delete a participant from list
function deleteParticipant(survey_id,event_id,part_id) {
	simpleDialog('<?php echo cleanHtml($lang['survey_361']) ?>','<?php echo cleanHtml($lang['survey_360']) ?>',null,null,null,'<?php echo cleanHtml($lang['global_53']) ?>',
		"deleteParticipantDo("+survey_id+","+event_id+","+part_id+");",'<?php echo cleanHtml($lang['scheduling_57']) ?>');
}
function deleteParticipantDo(survey_id,event_id,part_id) {
	$.post(app_path_webroot+'Surveys/delete_participant.php?pid='+pid+'&survey_id='+survey_id+'&event_id='+event_id, { participant_id: part_id }, function(data){
		if (data == '1') {
			loadPartList(survey_id,event_id,1,'<?php echo cleanHtml($lang['survey_363']) ?>','<?php echo cleanHtml($lang['survey_362']) ?>');
		} else {
			alert(woops);
		}
	});
}

// Dialog for adding new participants
function addPart(survey_id,event_id) {
	$('#emailAdd').dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
		'<?php echo cleanHtml($lang['global_53']) ?>': function() { $('#newPart').val(''); $(this).dialog('close'); },
		'<?php echo cleanHtml($lang['survey_230']) ?>': function () {
			$('#newPart').val( trim($('#newPart').val()) );
			if ($('#newPart').val().length < 1) {
				simpleDialog('Add participant email addresses');
				return;
			}
			showProgress(1);
			$.post(app_path_webroot+'Surveys/add_participants.php?pid='+pid+'&event_id='+event_id+'&survey_id='+survey_id, { participants: $('#newPart').val() }, function(data){
				showProgress(0);
				if (data == '1') {
					$('#newPart').val('');
					$('#emailAdd').dialog('destroy');
					loadPartList(survey_id,event_id,1,'<?php echo cleanHtml($lang['survey_366']) ?>','<?php echo cleanHtml($lang['survey_367']) ?>');
				} else if (data == '0') {
					alert(woops);
				} else {
					simpleDialog(data);
				}
			});
		}
	} });
}
// Open email-sending dialog
function sendEmails(survey_id,event_id) {
	$('#emailSendList_div').html('<img src="'+app_path_images+'progress_circle.gif" class="imgfix">&nbsp; <?php echo cleanHtml($lang['survey_287']) ?>');
	$('#emailPart').dialog({ bgiframe: true, modal: true, width: 900, buttons: { 
		'<?php echo cleanHtml($lang['global_53']) ?>': function() { $(this).dialog('close'); },
		'<?php echo cleanHtml($lang['survey_285']) ?>': function() {
			// Trim email subject/message
			$('#emailTitle').val( trim($('#emailTitle').val()) );
			$('#emailCont').val( trim($('#emailCont').val()) );
			// If set exact time in future to send surveys, make sure time doesn't exist in the past
			var now_mdyhm = '<?php echo date('m-d-Y H:i') ?>';
			if ($('form#emailPartForm #emailSendTimeTS').length && $('form#emailPartForm input[name="emailSendTime"]:checked').val() == 'EXACT_TIME') {
				if ($('form#emailPartForm #emailSendTimeTS').val().length < 1) {
					simpleDialog('<?php echo cleanHtml($lang['survey_325']) ?>',null,null,null,"$('form#emailPartForm #emailSendTimeTS').focus();");
					return;
				} else if (!redcap_validate(document.getElementById('emailSendTimeTS'),'','','hard','datetime_mdy',1)) {
					return;
				} else if ($('form#emailPartForm #emailSendTimeTS').val() < now_mdyhm) {
					simpleDialog('<?php echo cleanHtml($lang['survey_326']) ?> '+now_mdyhm+'<?php echo cleanHtml($lang['period']) ?>','<?php echo cleanHtml($lang['survey_327']) ?>');
					return;
				}
			}
			// Gather participant_ids and 
			var participants = new Array();
			var i = 0;
			$('input.chk_part:checked').each(function(){
				participants[i] = $(this).prop('id').substring(8);
				i++;
			});
			// Give error message if no participants are selected
			if (participants.length < 1) {
				simpleDialog('<?php echo cleanHtml($lang['survey_286']) ?>');
				return;
			}
			// Set all checked participant_id's as a single input field
			$('#emailPartForm').append('<input type="hidden" name="participants" value="'+participants.join(",")+'">');
			// Give confirmation message if any participants are about to have their invitations rescheduled
			var numScheduled = $('input.sched:checked').length;
			if (numScheduled > 0) {
				// Display pop-up with confirmation about rescheduling invites
				$('#reschedule-reminder-dialog-resched-count').html(numScheduled);
				$('#reschedule-reminder-dialog').dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
					'<?php echo cleanHtml($lang['global_53']) ?>': function() { $(this).dialog('close'); },
					'<?php echo cleanHtml($lang['survey_285']) ?>': function () {
						$('#emailPartForm').submit();
					}
				}});
				return;
			}
			// Submit the form
			showProgress(1);
			$('#emailPartForm').submit();
		}
	} });
	// After opening "compose email" dialog, load participant list via ajax
	$.get(app_path_webroot+'Surveys/participant_list.php?emailformat=1&survey_id='+survey_id+'&event_id='+event_id+'&pid='+pid, { }, function(data){
		$('#emailSendList_div').html(data);
	});
}
</script>

<style type="text/css">
.edit_active { background: #fafafa url(<?php echo APP_PATH_IMAGES ?>pencil.png) no-repeat right; }
.edit_saved  { background: #C1FFC1 url(<?php echo APP_PATH_IMAGES ?>tick.png) no-repeat right; }
</style>


<!-- Reschedule Participants Dialog -->
<div id="reschedule-reminder-dialog" title='<?php echo cleanHtml($lang['survey_343']) ?>' class="simpleDialog">
	<?php echo $lang['survey_344'] ?>
	<span id="reschedule-reminder-dialog-resched-count">0</span>
	<?php echo $lang['survey_345'] ?>
</div>
	

<!-- Email Participants Dialog -->
<div id="emailPart" title='<?php echo cleanHtml(RCView::img(array('src'=>'email.png','class'=>'imgfix','style'=>'margin-right:3px;')) . $lang['survey_309']) ?>' style="display:none;">
	<form id="emailPartForm" action="<?php echo APP_PATH_WEBROOT . "Surveys/email_participants.php?pid=$project_id&survey_id={$_GET['survey_id']}&event_id={$_GET['event_id']}" ?>" method="post">
	<table cellspacing=0 border=0 style="table-layout:fixed;"><tr>
		<td valign="top" align="left" style="width:380px;padding:2px 10px 0 0;">
			
			<fieldset style="padding-left:8px;border:1px solid #ccc;background-color:#F3F5F5;margin-bottom:10px;">
				<legend style="font-weight:bold;color:#333;">
					<img src="<?php echo APP_PATH_IMAGES ?>txt.gif" class="imgfix" style="margin-right:2px;">
					<?php echo $lang['survey_340'] ?>
				</legend>
				<?php
				print 	RCView::div(array('style'=>'padding:3px 8px 8px 2px;'),
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
						);
				?>
			</fieldset>
			
			<fieldset style="padding-left:8px;border:1px solid #ccc;background-color:#F3F5F5;margin-bottom:10px;">
				<legend style="font-weight:bold;color:#333;">
					<img src="<?php echo APP_PATH_IMAGES ?>clock_frame.png" class="imgfix" style="margin-right:3px;">
					<?php echo $lang['survey_322'] ?>
				</legend>
				<?php
				print 	RCView::div(array('style'=>'padding:4px 8px 7px 2px;'),
							RCView::radio(array('name'=>'emailSendTime','value'=>'IMMEDIATELY','class'=>'imgfix2','style'=>'','checked'=>'checked')) .
							$lang['survey_323'] . RCView::br() .
							RCView::radio(array('name'=>'emailSendTime','value'=>'EXACT_TIME','class'=>'imgfix2','style'=>'','onclick'=>"if ($('#emailSendTimeTS').val().length<1) $('#emailSendTimeTS').focus();")) .
							$lang['survey_324'] . 
							RCView::input(array('name'=>'emailSendTimeTS', 'id'=>'emailSendTimeTS', 'type'=>'text', 'class'=>'x-form-text x-form-field datetime_mdy', 
								'style'=>'width:92px;height:14px;line-height:14px;font-size:11px;margin-left:7px;padding-bottom:1px;','onkeydown'=>"if(event.keyCode==13){return false;}", 
								'onfocus'=>"$('form#emailPartForm input[name=\"emailSendTime\"][value=\"EXACT_TIME\"]').prop('checked',true); this.value=trim(this.value); if(this.value.length == 0 && $('.ui-datepicker:first').css('display')=='none'){ $(this).next('img').trigger('click');}",
								'onblur'=>"redcap_validate(this,'','','soft_typed','datetime_mdy',1,0)")) . 
							RCView::span(array('class'=>'df','style'=>'padding-left:5px;'), 'M-D-Y H:M') .
							// Get current time zone, if possible
							RCView::div(array('style'=>'margin:4px 0 0 22px;font-size:10px;line-height:10px;color:#777;'),
								"{$lang['survey_296']} <b>".getTimeZone()."</b>{$lang['survey_297']} <b>" . 
								date('m-d-Y H:i') . "</b>{$lang['period']}"
							)
						);
				?>
			</fieldset>
			
			<!-- Email form -->
			<fieldset style="padding-left:8px;border:1px solid #ccc;background-color:#F3F5F5;">
				<legend style="font-weight:bold;color:#333;">
					<img src="<?php echo APP_PATH_IMAGES ?>email.png" class="imgfix" style="margin-right:3px;">
					<?php echo $lang['survey_339'] ?>
				</legend>
				<div style="padding:10px 0 10px 2px;">
					<table border=0 cellspacing=0 width=100%>
					<tr>
						<td style="vertical-align:middle;width:50px;"><?php echo $lang['global_37'] ?></td>
						<td style="vertical-align:middle;color:#555;">
						<?php echo User::emailDropDownList() ?> 
					</tr>
					<tr>
						<td style="vertical-align:middle;width:50px;padding-top:10px;"><?php echo $lang['global_38'] ?></td>
						<td style="vertical-align:middle;padding-top:10px;color:#666;font-weight:bold;"><?php echo $lang['survey_102'] ?></td>
					</tr>
					<tr>
						<td style="vertical-align:middle;width:50px;padding:10px 0;"><?php echo $lang['survey_103'] ?></td>
						<td style="vertical-align:middle;padding:10px 0;"><input class="x-form-text x-form-field" style="font-family:arial;width:280px;" type="text" id="emailTitle" name="emailTitle" onkeydown="if(event.keyCode == 13){return false;}" /></td>
					</tr>
					<tr>
						<td colspan="2" style="padding:5px 0 10px;">
							<textarea class="x-form-field notesbox" id="emailCont" name="emailCont" style="font-family:arial;height:120px;width:95%;"></textarea>
						</td>
					</tr>
					</table>
					<!-- Text below email form -->
					<div style="padding:0 5px;">
						<div style="font-size:11px;color:#800000;padding-bottom:6px;">
							<b><?php echo $lang['survey_105'] ?></b> <?php echo $lang['survey_104'] ?>
						</div>
						
						<div style="font-size:11px;color:#555;padding:0 5px 6px 0;">
							<?php echo $lang['survey_164'] ?>
							&lt;b&gt; bold, &lt;u&gt; underline, &lt;i&gt; italics, &lt;a href="..."&gt; link, etc.
						</div>
						
						<div id="divDispPrevEmails" style="font-size:11px;<?php echo $divDispPrevEmails_display ?>">
							<?php echo $lang['survey_106'] ?>
							<input type="checkbox" id="chkDispPrevEmails" class="imgfix" onclick="
								if (this.checked == false) {
									$('#selectDispPrevEmails').val('');
								}
								$('#selectDivDispPrevEmails').toggle('blind','fast');
							"> 
						</div>
						<div id="selectDivDispPrevEmails" style="display:none;font-size:11px;padding-top:6px;">
							<select id="selectDispPrevEmails" style="font-size:11px;font-family:Tahoma,Arial;" onchange="document.getElementById('emailCont').value=this.value;">
								<option value=""> - <?php echo $lang['survey_107'] ?> - </option>
								<?php foreach ($emailSelect as $key=>$val) { ?>
								<option value="<?php echo $key ?>"><?php echo $val ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
			</fieldset>
		</td>
		<td style="padding:10px 0 0 10px;width:480px;" id="emailSendList_div" valign="top"></td>
	</tr></table>
	</form>
</div>

<?php	

// Footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	