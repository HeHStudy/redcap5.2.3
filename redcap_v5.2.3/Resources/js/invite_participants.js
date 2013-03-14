
// In email survey inviations pop-up, pre-select checkboxes based on action selected
function emailPartPreselect(val) {
	if (val.length < 1) return;
	if (val == 'check_all') {
		// Check all
		$('#participant_table_email input.chk_part').prop('checked',true);
	} else {
		// Uncheck all first
		$('#participant_table_email input.chk_part').prop('checked',false);
		// Now check specifically
		if (val == 'check_sent') {
			$('#participant_table_email input.part_sent').prop('checked',true);	
		} else if (val == 'check_unsent') {
			$('#participant_table_email input.part_unsent').prop('checked',true);	
		} else if (val == 'check_sched') {
			$('#participant_table_email input.sched').prop('checked',true);
		} else if (val == 'check_unsched') {
			$('#participant_table_email input.unsched').prop('checked',true);
		} else if (val == 'check_unsent_unsched') {
			$('#participant_table_email input.unsched.part_unsent').prop('checked',true);
		}
	}
}

// Load/reload the participant list via ajax
function loadPartList(survey_id,event_id,pagenum,callback_msg,callback_title) {
	if (pagenum == null) pagenum = 1;
	showProgress(1);
	$.get(app_path_webroot+'Surveys/participant_list.php?pid='+pid+'&survey_id='+survey_id+'&event_id='+event_id+'&pagenum='+pagenum, function(data){
		$('#partlist_outerdiv').html(data);
		showProgress(0);
		// Only make table editable if on initial survey
		if (survey_id == firstFormSurveyId && event_id == firstEventId) enableEditParticipant();		
		// Initialize all buttons in participant list
		initWidgets();
		resizeMainWindow();
		if (callback_msg != null) simpleDialog(callback_msg,callback_title);
	});
}

// Retrieve short url and display for user
function getShortUrl(hash,survey_id) {
	if ($('#shorturl').val().length < 1) {
		$('#shorturl_div').hide();
		$('#shorturl_loading_div').show('fade','fast');
		$.get(app_path_webroot+'Surveys/shorturl.php', { pid: pid, hash: hash, survey_id: survey_id }, function(data) {
			if (data != '0') {
				$('#shorturl_loading_div').hide();
				$('#shorturl').val(data);
				$('#shorturl_div').show('fade','fast');
				$('#shorturl_div').effect('highlight', 'slow');
			}
		});
	} else {
		$('#shorturl_div').effect('highlight', 'slow');
	}
}

// Click the enable/disable Participant Identifiers button to open dialog
function enablePartIdent(survey_id,event_id) {
	// First, fire JS to reorder table by Email (since the button to trigger this function ordered it by Identifier)
	setTimeout(function(){ SortTable('table-participant_table',0,'string'); },5);
	// Ajax request
	$.post(app_path_webroot+'Surveys/participant_list_enable.php?pid='+pid, { action: 'view' },function(data){
		if (data == "0") {
			alert(woops);
			return;
		}
		// Set dialog title/content
		var json_data = jQuery.parseJSON(data);
		$('#popupEnablePartIdent').prop("title",json_data.title);
		$('#popupEnablePartIdent').html(json_data.payload);
		var saveBtn = json_data.saveBtn;
		var successDialogContent = json_data.successDialogContent;
		// Open dialog
		$('#popupEnablePartIdent').dialog({ bgiframe: true, modal: true, width: 550, buttons: [{
				text: "Cancel",
				click: function () {
					$(this).dialog('close');
				}
			},{
				text: json_data.saveBtn,
				click: function () {
					// Save value via AJAX
					$.post(app_path_webroot+'Surveys/participant_list_enable.php?pid='+pid, { action: 'save' },function(data){
						if (data == "0") {
							alert(woops);
						} else {
							// Success!
							$('#popupEnablePartIdent').dialog('destroy');
							var pageNum = $('#pageNumSelect').val();
							if (!isNumeric(pageNum)) pageNum = 1;
							simpleDialog(successDialogContent,null,'',500,"loadPartList("+survey_id+","+event_id+","+pageNum+");");
						}
					});
				}
			}]
		});
	});
};

// Disable Participant Identifiers column in the List (prevent adding/editing)
function disablepartIdentColumn() {
	if ($('#enable_participant_identifiers').val() == '0') {
		// DISABLED
		// Set gray background for all cells in column
		$('.partIdentColDisabled').parent().parent().css('background-color','#E8E8E8');
		// Hide text on page relating to identifiers
		$('.partIdentInstrText').css('visibility','hidden');
		// Pop-up tooltip: Give warning message to user if tries to edit identifier IF identifiers are DISABLED (not allowed)	
		$('.partIdentColDisabled').tooltip({
			tip: '#tooltipIdentDisabled',
			position: 'center right',
			offset: [10, -60],
			delay: 100,
			events: { def: "click,mouseout" }
		});
	} else {
		// ENABLED
		// Show text on page relating to identifiers
		$('.partIdentInstrText').css('visibility','visible');		
	}
}

// Copy the public survey URL to the user's clipboard
function copyUrlToClipboard(id) {
	// Create progress element that says "Copied!" when clicked
	var rndm = Math.random()+"";
	var copyid = 'clip'+rndm.replace('.','');
	var clipSaveHtml = '<span class="clipboardSaveProgress" id="'+copyid+'">Copied!</span>';
	$('#flashObj_'+id).after(clipSaveHtml);	
	$('#'+copyid).toggle('fade','fast');
	setTimeout(function(){
		$('#'+copyid).toggle('fade','fast',function(){
			$('#'+copyid).remove();
		});
	},2000);
	// Save to clipboard
	var s = $('#'+id).val();
	if (window.clipboardData) {
		window.clipboardData.setData('text', s);
	} else {
		return s;
	}
}

// Pop-up tooltip: Give warning message to user if tries to click partial/complete icon to view response IF identifier is not defined
function noViewResponseTooltip() {
	$('.noviewresponse').tooltip({
		tip: '#tooltipViewResp',
		position: 'center left',
		offset: [30, -10],
		delay: 100,
		events: { def: "click,mouseout" }
	});
}

// Set up in-line editing for email address and identifier
function enableEditParticipant() {
	// First, check if we should disabled the Identifier column in the table(if not enabled yet)
	disablepartIdentColumn();
	// Pop-up tooltip: Give warning message to user if tries to edit email/identifier IF response is partial/complete (not allowed)	
	$('.noeditemail, .noeditidentifier').tooltip({
		tip: '#tooltipEdit',
		position: 'center right',
		offset: [10, -60],
		delay: 100,
		events: { def: "click,mouseout" }
	});
	// Pop-up tooltip: Give warning message to user if tries to click partial/complete icon to view response IF identifier is not defined
	noViewResponseTooltip();
	// Hide tooltips if they are clicked on
	$('#tooltipEdit, #tooltipViewResp, #tooltipIdentDisabled').click(function(){
		$(this).hide('fade');
	});
	// Pop-up tooltip: Denote that user can click partial/complete icon to view response
	$('.viewresponse, .partLink').tooltip({
		position: 'center right',
		offset: [0, 10],
		delay: 100
	});
	
	// For editing email
	$('.editemail').hover(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1) { 
			$(this).unbind('click');
			return;
		}
		$(this).css('cursor','pointer');
		$(this).addClass('edit_active');
		$(this).prop('title','Click to edit email');
	}, function() {
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
	});
	$('.editemail').click(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1) { 
			$(this).unbind('click');
			return;
		}
		// Undo css
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
		$(this).unbind('click');
		var thisEmail = $(this).text();
		if (thisEmail.indexOf(')') > 0) { 
			var aaa = thisEmail.split(')');
			thisEmail = trim(aaa[1]);
		}
		var thisPartId = $(this).attr('part');
		$(this).html( '<input id="partNewEmail_'+thisPartId+'" onblur=\'redcap_validate(this,"","","soft_typed","email")\' type="text" class="x-form-text x-form-field" style="vertical-align:middle;width:81%;" value="'+thisEmail+'"> &nbsp;'
					+ '<button style="vertical-align:middle;" class="jqbuttonsm" onclick="editPartEmail('+thisPartId+');">'+langSave+'</button>');
	});
	// For editing identifier
	$('.editidentifier').hover(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1) { 
			$(this).unbind('click');
			return;
		}
		$(this).css('cursor','pointer');
		$(this).addClass('edit_active');
		$(this).prop('title','Click to edit identifier');
	}, function() {
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
	});
	$('.editidentifier').click(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1) { 
			$(this).unbind('click');
			return;
		}
		// Undo css
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
		$(this).unbind('click');
		var thisIdentifier = $(this).text().replace(/"/ig,'&quot;');
		var thisPartId = $(this).attr('part');
		$(this).html( '<input id="partNewIdentifier_'+thisPartId+'" type="text" class="x-form-text x-form-field" style="vertical-align:middle;width:73%;" value="'+thisIdentifier+'"> &nbsp;'
					+ '<button style="vertical-align:middle;" class="jqbuttonsm" onclick="editPartIdentifier('+thisPartId+');">'+langSave+'</button>');
	});
}

// Open the "view email" dialog
function viewEmail(email_recip_id) {
	$.post(app_path_webroot+'Surveys/view_sent_email.php?pid='+pid,{ email_recip_id: email_recip_id }, function(data){
		if (data == "0") {
			alert(woops);
			return;
		}
		var json_data = jQuery.parseJSON(data);
		// Display dialog
		simpleDialog(json_data.content,json_data.title,null,600);
	});
}

// Reload the Survey Invitation Log for another "page" when paging the log
function loadInvitationLog(pagenum) {
	showProgress(1);
	window.location.href = app_path_webroot+page+'?pid='+pid+'&email_log=1&pagenum='+pagenum+
		'&filterBeginTime='+$('#filterBeginTime').val()+'&filterEndTime='+$('#filterEndTime').val()+
		'&filterInviteType='+$('#filterInviteType').val()+'&filterResponseType='+$('#filterResponseType').val()+
		'&filterSurveyEvent='+$('#filterSurveyEvent').val();
}

// Delete a scheduled survey invitation from invitation log
function deleteSurveyInvite(email_recip_id) {
	$.post(app_path_webroot+'Surveys/survey_invitation_ajax.php?pid='+pid,{ email_recip_id: email_recip_id, action: 'view_delete' }, function(data){
		if (data == "0") {
			alert(woops);
			return;
		}
		var json_data = jQuery.parseJSON(data);
		// Display dialog
		simpleDialog(json_data.content,json_data.title,null,500,null,'Cancel','deleteSurveyInviteDo('+email_recip_id+')','Delete invitation');
	});
}
function deleteSurveyInviteDo(email_recip_id) {
	$.post(app_path_webroot+'Surveys/survey_invitation_ajax.php?pid='+pid,{ email_recip_id: email_recip_id, action: 'delete' }, function(data){
		if (data == "0") {
			alert(woops);
			return;
		}
		var json_data = jQuery.parseJSON(data);
		// Display dialog
		simpleDialog(json_data.content,json_data.title,null,500,'showProgress(1);window.location.reload()');
	});
}



// Modify the send time for a scheduled survey invitation in the invitation log
function editSurveyInviteTime(email_recip_id) {
	$.post(app_path_webroot+'Surveys/survey_invitation_ajax.php?pid='+pid,{ email_recip_id: email_recip_id, action: 'view_edit_time' }, function(data){
		if (data == "0") {
			alert(woops);
			return;
		}
		var json_data = jQuery.parseJSON(data);
		// Display dialog
		simpleDialog(json_data.content,json_data.title,null,500,null,'Cancel','editSurveyInviteTimeDo('+email_recip_id+')','Change invitation time');
		initWidgets();
		window.newInviteTime = $('#newInviteTime').val();
	});
}
function editSurveyInviteTimeDo(email_recip_id) {
	if (window.newInviteTime == '') {
		simpleDialog("Please enter a date/time");
	} else {
		$.post(app_path_webroot+'Surveys/survey_invitation_ajax.php?pid='+pid,{ email_recip_id: email_recip_id, action: 'edit_time', newInviteTime: window.newInviteTime }, function(data){
			if (data == "0") {
				alert(woops);
				return;
			}
			var json_data = jQuery.parseJSON(data);
			// Display dialog
			simpleDialog(json_data.content,json_data.title,null,500,'showProgress(1);window.location.reload()');
		});
	}
}