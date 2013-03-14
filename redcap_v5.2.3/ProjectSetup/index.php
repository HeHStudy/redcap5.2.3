<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
// TABS
include APP_PATH_DOCROOT . "ProjectSetup/tabs.php";

// Display action messages when 'msg' in URL
if (isset($_GET['msg']) && !empty($_GET['msg']))
{
	// Defaults
	$msgAlign = "center";
	$msgClass = "green";
	$msgText  = "<b>{$lang['setup_08']}</b> {$lang['setup_09']}";
	$msgIcon  = "tick.png";
	$timeVisible = 7; //seconds
	// Determine which message to display
	switch ($_GET['msg'])
	{
		// Created project
		case "newproject":
			$msgText  = "<b>{$lang['new_project_popup_02']}</b><br>{$lang['new_project_popup_03']}";
			$msgAlign = "left";
			$timeVisible = 10;
			break;
		// Copied project
		case "copiedproject":
			$msgText  = "<b>{$lang['new_project_popup_16']}</b><br>{$lang['new_project_popup_17']}";
			$msgAlign = "left";
			$timeVisible = 10;
			break;
		// Modified project info
		case "projectmodified":
			break;
		// Moved to production
		case "movetoprod":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['setup_15']}";
			break;
		// Moved back to development
		case "movetodev":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['setup_72']}";
			break;
		// Sent request to move to production
		case "request_movetoprod":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['setup_16']}";
			break;
		// Set secondary id
		case "secondaryidset":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['setup_17']}";
			break;
		// REset secondary id
		case "secondaryidreset":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['setup_18']}";
			break;
		// Error (general)
		case "error":
			$msgText  = "<b>{$lang['global_64']}</b>";
			$msgClass = "red";
			$msgIcon   = "exclamation.png";
			break;
	}
	// Display message
	displayMsg($msgText, "actionMsg", $msgAlign, $msgClass, $msgIcon, $timeVisible, true);
}









/**
 * CHECKLIST
 */
 
$checkList = array();
// Set disabled status for any buttons/checkboxes whose pages relate to the Design/Setup user rights
$disableBtn = ($user_rights['design'] ? "" : "disabled");
// Set disabled status for any buttons/checkboxes that should NOT be changed while in production
$disableProdBtn = (($status < 1 || $super_user) ? "" : "disabled");
// Counter
$stepnum = 1;
// Set project creation timestamp as integer for use in log_event queries (to help reduce query time)
$creation_time_sql = ($creation_time == "") ? "" : "ts > ".str_replace(array('-',' ',':'), array('','',''), $creation_time)." and";
// Get all checklist items that have already been manually checked off by user. Store in array.
$checkedOff = array();
$q = db_query("select name from redcap_project_checklist where project_id = $project_id");
while ($row = db_fetch_assoc($q))
{
	$checkedOff[$row['name']] = true;
}





// MAIN PROJECT SETTINGS
$optionRepeatformsChecked = ($repeatforms) ? "checked" : "";
$optionSurveysChecked = ($surveys_enabled) ? "checked" : "";
$sql = "select 1 from redcap_log_event where $creation_time_sql project_id = $project_id
		and page in ('ProjectSetup\modify_project_setting_ajax.php', 'ProjectGeneral\edit_project_settings.php') 
		and description = 'Modify project settings' limit 1";
$modifyProjectStatus = ((isset($checkedOff['modify_project']) || $status > 0) ? 2 : db_num_rows(db_query($sql)));
$video_link =  	RCView::span(array('style'=>'margin-left:5px;'),
						RCView::img(array('src'=>'video_small.png','class'=>'imgfix')) . 
						RCView::a(array('href'=>'javascript:;','onclick'=>"popupvid('redcap_survey_basics02.flv')",'style'=>'font-weight:normal;font-size:11px;text-decoration:underline;'), $lang['training_res_63'])
					);
$checkList[$stepnum++] = array("header" => $lang['setup_105'], "status" => $modifyProjectStatus, "name" => "modify_project",
	"text" =>   // If in production, give note regarding why options above are disabled
				RCView::div(array('style'=>'color:#777;font-size:11px;padding-bottom:3px;'.(($status > 0 && !$super_user) ? '' : 'display:none;')), $lang['setup_106']) .
				// Use longitudinal?
				RCView::div(array('style'=>'padding:2px 0;font-size:13px;color:'.($repeatforms ? 'green' : '#800000').';'), 
					RCView::button(array('id'=>'setupLongiBtn','class'=>'jqbuttonsm','style'=>'','onclick'=>($longitudinal ? "confirmUndoLongitudinal()" : "saveProjectSetting($(this),'repeatforms','1','0',1);"),$disableBtn=>$disableBtn,$disableProdBtn=>$disableProdBtn,$optionRepeatformsChecked=>$optionRepeatformsChecked),
						($repeatforms ? $lang['control_center_153'] : $lang['survey_152'] . RCView::SP)
					) . 
					RCView::img(array('src'=>($repeatforms ? 'accept.png' : 'delete.png'),'class'=>'imgfix','style'=>'margin-left:8px;')) .
					$lang['setup_93'] . 
					// Question pop-up
					RCView::a(array('href'=>'javascript:;','class'=>'help','title'=>$lang['global_58'],'onclick'=>"simpleDialog(null,null,'longiDialog');"), $lang['questionmark']) . 
					// Invisible "saved" msg
					RCView::span(array('class'=>'savedMsg'), $lang['design_243'])
				) .
				// Use surveys?
				(!$enable_projecttype_singlesurveyforms ? '' :
				RCView::div(array('style'=>'padding:2px 0;font-size:13px;color:'.($surveys_enabled ? 'green' : '#800000').';'), 
					RCView::button(array('id'=>'setupEnableSurveysBtn','class'=>'jqbuttonsm','style'=>'','onclick'=>(($surveys_enabled && count($Proj->surveys) > 0) ? "confirmUndoEnableSurveys()" : "saveProjectSetting($(this),'surveys_enabled','1','0',1);"),$disableBtn=>$disableBtn,$disableProdBtn=>$disableProdBtn,$optionSurveysChecked=>$optionSurveysChecked),
						($surveys_enabled ? $lang['control_center_153'] : $lang['survey_152'] . RCView::SP)
					) . 
					RCView::img(array('src'=>($surveys_enabled ? 'accept.png' : 'delete.png'),'class'=>'imgfix','style'=>'margin-left:8px;')) .
					$lang['setup_96'] .
					// Question pop-up
					RCView::a(array('href'=>'javascript:;','class'=>'help','title'=>$lang['global_58'],'onclick'=>"simpleDialog(null,null,'useSurveysDialog');"), $lang['questionmark']) . 
					// Invisible "saved" msg
					RCView::span(array('class'=>'savedMsg'), $lang['design_243']) .
					$video_link
				)) .
				// Make Additional Customizations button
				RCView::button(array('class'=>'jqbuttonmed','style'=>'margin-top:13px;',$disableBtn=>$disableBtn,'onclick'=>'displayEditProjPopup();'), $lang['setup_100'])
);


## Design your data collection instruments
if ($status < 1)
{
	if (isset($checkedOff['design'])) {
		$buildFieldsStatus = 2;
	} else {
		// Check log_event table to see if they've modified events (i.e. used Design/designate_forms_ajax.php page)
		$sql = "select 1 from redcap_log_event where $creation_time_sql project_id = $project_id and 
				description in ('Reorder project fields', 'Reorder data collection instruments', 'Create project field',
				'Edit project field', 'Create data collection instrument', 'Delete project field', 'Delete section header',
				'Copy project field', 'Delete data collection instrument', 'Rename data collection instrument',
				'Upload data dictionary', 'Download instrument from Shared Library') limit 1";
		$buildFieldsStatus = ($status > 0 ? 2 : db_num_rows(db_query($sql)));
	}
	// Set button
	if ($user_rights['design'])
	{
		$designBtn =   "{$lang['setup_44']}
						<a href='".APP_PATH_WEBROOT."PDF/index.php?pid=$project_id'
							style='font-size:12px;text-decoration:underline;color:#800000;'>{$lang['design_266']}</a> 
						{$lang['global_46']}
						<a href='javascript:;' onclick='downloadDD(0,{$Proj->formsFromLibrary()});'
							style='font-size:12px;text-decoration:underline;'>{$lang['design_119']} {$lang['global_09']}</a> ";
		if ($status > 0) {
			$designBtn .=  "{$lang['global_46']}
							<a href='javascript:;' onclick='downloadDD(1,{$Proj->formsFromLibrary()});'
								style='font-size:12px;text-decoration:underline;'>{$lang['design_121']} {$lang['global_09']} {$lang['design_122']}</a>";
		}
	}
	$designBtn .=  "<div class='chklistbtn' style='display:" . ($status > 0 ? "none" : "block") . ";'>
						{$lang['setup_45']}&nbsp; <button $disableBtn class='jqbuttonmed' onclick=\"window.location.href=app_path_webroot+'Design/online_designer.php?pid=$project_id';\">{$lang['design_25']}</button>
						&nbsp;{$lang['global_47']}&nbsp; <button $disableBtn class='jqbuttonmed' style='white-space:nowrap;' onclick=\"window.location.href=app_path_webroot+'Design/data_dictionary_upload.php?pid=$project_id';\">{$lang['design_108']}</button>
						".renderBrowseLibraryForm()."
						<div style='padding:15px 0 0;color:#444;'>
							{$lang['edit_project_141']} 
							<input type='button' class='jqbuttonmed' value=' ".cleanHtml($lang['design_37'])." ' style='font-size:11px;color:#800000;' onclick=\"$('form#browse_rsl').submit();\"> 
						</div>
					</div>";
	// Survey + Forms
	if ($surveys_enabled) {
		$checkList[$stepnum++] = array("header" => $lang['setup_90'], "name"=>"design", "status" => $buildFieldsStatus, 
			"text" =>  "{$lang['setup_29']} " . $lang['setup_91'] . " $designBtn"
		);	
	} 
	// Forms only
	else {
		$checkList[$stepnum++] = array("header" => $lang['setup_30'], "name"=>"design", "status" => $buildFieldsStatus, 
			"text" =>  "{$lang['setup_31']}
						$designBtn"
		);
	}
}


## Define My Events: For potentially longitudinal projects (may not have multiple events yet)
if ($repeatforms)
{
	if ($checkedOff['define_events']) {
		$defineEventsStatus = 2;
	} else {
		// Check log_event table to see if they've modified events (i.e. used Design/define_events_ajax.php page)
		$sql = "select 1 from redcap_log_event where $creation_time_sql project_id = $project_id and description in 
				('Delete event', 'Edit arm name/number', 'Create arm', 'Delete arm', 'Create event', 'Edit event', 
				'Designate data collection instruments for events') limit 1";
		$q = db_query($sql);
		$defineEventsStatus = ($status > 0 ? 2 : db_num_rows($q));
	}
	// Set button as disabled if in prod and not a super user
	$checkList[$stepnum++] = array("header" => $lang['setup_33'], "name" => "define_events", "status" => $defineEventsStatus,
		"text" =>  "{$lang['setup_34']}
					<div class='chklistbtn'>
						{$lang['setup_45']}&nbsp; <button $disableBtn class='jqbuttonmed' onclick=\"window.location.href=app_path_webroot+'Design/define_events.php?pid=$project_id';\">{$lang['global_16']}</button>
						&nbsp;{$lang['global_47']}&nbsp; <button $disableBtn class='jqbuttonmed' onclick=\"window.location.href=app_path_webroot+'Design/designate_forms.php?pid=$project_id';\">{$lang['global_28']}</button>
					</div>"
	);
}



// MISCELLANEOUS MODULES (auto-numbering, randomization, scheduling, etc.)
$moduleAutoNumChecked = ($auto_inc_set) ? "checked" : "";
$moduleAutoNumDisabled = ($Proj->firstFormSurveyId == null) ? "" : "disabled";
$moduleAutoNumClass = ($Proj->firstFormSurveyId == null) ? "" : "opacity25";
$moduleRandChecked = ($randomization) ? "checked" : "";
$moduleSchedChecked = ($repeatforms && $scheduling) ? "checked" : "";
$moduleSchedDisabled = ($repeatforms) ? "" : "disabled";
$moduleStatus = ($checkedOff['modules'] || $status > 0) ? 2 : "";
$moduleEmailFieldDisabled = "disabled";
if ($surveys_enabled && ($super_user || $status == 0 || ($status > 0 && $survey_email_participant_field == ''))) {
	$moduleEmailFieldDisabled = "";
}
$moduleEmailFieldChecked = ($survey_email_participant_field != '') ? "checked" : "";
$checkList[$stepnum++] = array("header" => $lang['setup_95'], "status" => $moduleStatus, "name" => "modules",
	"text" =>   // If in production, give note regarding why options above are disabled
				RCView::div(array('style'=>'color:#777;font-size:11px;padding-bottom:3px;'.(($status > 0 && !$super_user) ? '' : 'display:none;')), $lang['setup_106']) .
				// Auto-numbering for records
				RCView::div(array('style'=>'white-space:nowrap;color:'.($auto_inc_set ? 'green' : '#800000').';'), 
					RCView::button(array('class'=>'jqbuttonsm','style'=>'','onclick'=>"saveProjectSetting($(this),'auto_inc_set','1','0',1,'setupChklist-modules');",$disableBtn=>$disableBtn,$disableProdBtn=>$disableProdBtn,$moduleAutoNumChecked=>$moduleAutoNumChecked, $moduleAutoNumDisabled=>$moduleAutoNumDisabled),
						($auto_inc_set ? $lang['control_center_153'] : $lang['survey_152'] . RCView::SP)
					) . 
					RCView::img(array('src'=>($auto_inc_set ? 'accept.png' : 'delete.png'),'class'=>"imgfix",'style'=>'margin-left:8px;')) .
					$lang['setup_94'] . 
					// Tell Me More link
					RCView::a(array('href'=>'javascript:;','class'=>'help','title'=>$lang['global_58'],'onclick'=>"simpleDialog(null,null,'autoNumDialog');"), $lang['questionmark']) . 
					// Invisible "saved" msg
					RCView::span(array('class'=>'savedMsg'), $lang['design_243'])
				) .
				// Scheduling module
				RCView::div(array('style'=>'white-space:nowrap;color:'.(($repeatforms && $scheduling) ? 'green' : '#800000').';'), 
					RCView::button(array('class'=>'jqbuttonsm','style'=>'','onclick'=>"saveProjectSetting($(this),'scheduling','1','0',1,'setupChklist-modules');",$disableBtn=>$disableBtn,$disableProdBtn=>$disableProdBtn,$moduleSchedChecked=>$moduleSchedChecked, $moduleSchedDisabled=>$moduleSchedDisabled),
						(($repeatforms && $scheduling) ? $lang['control_center_153'] : $lang['survey_152'] . RCView::SP)
					) . 
					RCView::img(array('src'=>(($repeatforms && $scheduling) ? 'accept.png' : 'delete.png'),'class'=>"imgfix",'style'=>'margin-left:8px;')) .
					$lang['define_events_19'] . RCView::SP . $lang['setup_97'] .
					// Tell Me More link
					RCView::a(array('href'=>'javascript:;','class'=>'help','title'=>$lang['global_58'],'onclick'=>"simpleDialog(null,null,'schedDialog');"), $lang['questionmark']) . 
					// Invisible "saved" msg
					RCView::span(array('class'=>'savedMsg'), $lang['design_243'])
				) .
				// Randomization module
				RCView::div(array('style'=>'white-space:nowrap;color:'.($randomization ? 'green' : '#800000').';'), 
					RCView::button(array('class'=>'jqbuttonsm','style'=>'','onclick'=>"saveProjectSetting($(this),'randomization','1','0',1,'setupChklist-modules');",$disableBtn=>$disableBtn,$disableProdBtn=>$disableProdBtn,$moduleRandChecked=>$moduleRandChecked),
						($randomization ? $lang['control_center_153'] : $lang['survey_152'] . RCView::SP)
					) . 
					RCView::img(array('src'=>($randomization ? 'accept.png' : 'delete.png'),'class'=>"imgfix",'style'=>'margin-left:8px;')) .
					$lang['setup_98'] . 
					// Tell Me More link
					RCView::a(array('href'=>'javascript:;','class'=>'help','title'=>$lang['global_58'],'onclick'=>"simpleDialog(null,null,'randDialog');"), $lang['questionmark']) . 
					// Invisible "saved" msg
					RCView::span(array('class'=>'savedMsg'), $lang['design_243'])
				) .
				// Additional email field for survey invitations
				RCView::div(array('style'=>'white-space:nowrap;color:'.($survey_email_participant_field != '' ? 'green' : '#800000').';'), 
					RCView::button(array('id'=>'enableSurveyPartEmailFieldBtn','class'=>'jqbuttonsm','style'=>'','onclick'=>"dialogSurveyEmailField(".($survey_email_participant_field != '' ? '0' : '1').");",$disableBtn=>$disableBtn,$moduleEmailFieldDisabled=>$moduleEmailFieldDisabled,$moduleEmailFieldChecked=>$moduleEmailFieldChecked),
						($survey_email_participant_field != '' ? $lang['control_center_153'] : $lang['survey_152'] . RCView::SP)
					) . 
					RCView::img(array('src'=>($survey_email_participant_field != '' ? 'accept.png' : 'delete.png'),'class'=>"imgfix",'style'=>'margin-left:8px;')) .
					$lang['setup_113'] . 
					// Tell Me More link
					RCView::a(array('href'=>'javascript:;','class'=>'help','title'=>$lang['global_58'],'onclick'=>"simpleDialog(null,null,'surveyEmailFieldDialog',600);"), $lang['questionmark']) . 
					// Invisible "saved" msg
					RCView::span(array('class'=>'savedMsg'), $lang['design_243']) .
					// If email field is already designated, then list it here as informational info
					($survey_email_participant_field == '' ? '' :
						RCView::div(array('style'=>'width:500px;padding:1px 0 0 77px;color:#666;text-overflow:ellipsis;white-space:nowrap;overflow:hidden;'),
							$lang['setup_121'] . " <b>$survey_email_participant_field</b>&nbsp; (\"<i>{$Proj->metadata[$survey_email_participant_field]['element_label']}</i>\")"
						)
					)
				) .
				// Make Additional Customizations button
				RCView::button(array('class'=>'jqbuttonmed','style'=>'margin-top:10px;',$disableBtn=>$disableBtn,'onclick'=>'displayCustomizeProjPopup();'), $lang['setup_104'])
);


## Randomization
if ($randomization)
{
	// Check table to determine progress of randomization setup
	$randomizeStatus = ($checkedOff['randomization'] || $status > 0) ? 2 : 0;
	if ($randomizeStatus < 2) 
	{
		$sql = "select distinct r.rid, a.project_status from redcap_randomization r 
				left outer join redcap_randomization_allocation a on r.rid = a.rid 
				where r.project_id = $project_id";
		$q = db_query($sql);
		$randomizeStatus = db_num_rows($q);
	}
	$disableBtnRandomization = ($user_rights['random_setup'] || $user_rights['random_dashboard']) ? "" : "disabled";
	// Set button as disabled if in prod and not a super user
	$rpage = ($user_rights['random_setup']) ? "index.php" : "dashboard.php";
	$checkList[$stepnum++] = array("header" => $lang['setup_81'], 
		"name" => "randomization",
		"status" => $randomizeStatus,
		"text" =>  "{$lang['setup_82']}
					<div class='chklistbtn'>
						{$lang['setup_45']}&nbsp; <button $disableBtn $disableBtnRandomization class='jqbuttonmed' onclick=\"window.location.href=app_path_webroot+'Randomization/$rpage?pid=$project_id';\">{$lang['setup_83']}</button>
					</div>"
	);
}


## Project Bookmarks
$ExtRes_stepnum = $stepnum;
if ($checkedOff['external_resources']) {
	$ExtResStatus = 2;
} else {
	$sql = "select 1 from redcap_external_links where project_id = $project_id limit 1";
	$q = db_query($sql);
	$ExtResStatus = db_num_rows($q) ? 1 : "";
}
$checkList[$stepnum++] = array("header" => "{$lang['setup_78']} {$lang['global_06']}", "status" => $ExtResStatus, "name" => "external_resources",
	"text" =>  "{$lang['setup_80']}
				<div class='chklistbtn'>
					{$lang['setup_45']}&nbsp; 
					<button class='jqbuttonmed' $disableBtn onclick=\"window.location.href=app_path_webroot+'ExternalLinks/index.php?pid=$project_id';\">{$lang['setup_79']}</button>
				</div>"
);

## User Rights and DAGs
$dagText = $lang['setup_38'];
$dagBtn  = "&nbsp;{$lang['global_47']}&nbsp; <button class='jqbuttonmed' ".($user_rights['data_access_groups'] ? "" : "disabled")." onclick=\"window.location.href=app_path_webroot+'DataAccessGroups/index.php?pid=$project_id';\">{$lang['global_22']}</button>";
$userRights_stepnum = $stepnum;
$checkList[$stepnum++] = array("header" => $lang['setup_39'], "status" => ($checkedOff['user_rights'] ? 2 : ""), "name" => "user_rights",
	"text" =>  "{$lang['setup_40']} $dagText
				<div class='chklistbtn'>
					{$lang['setup_45']}&nbsp; 
					<button class='jqbuttonmed' ".($user_rights['user_rights'] ? "" : "disabled")." onclick=\"window.location.href=app_path_webroot+'UserRights/index.php?pid=$project_id';\">{$lang['app_05']}</button>
					$dagBtn
				</div>"
);

## Move to production
// Check log_event table to see if they've sent a request before (if project requests have been enabled)
$sql = "select 1 from redcap_log_event where $creation_time_sql project_id = $project_id and 
		description = 'Send request to move project to production status' limit 1";
$moveToProdStatus = ($status > 0) ? 2 : ($superusers_only_move_to_prod && db_num_rows(db_query($sql)) ? 1 : 0);
$checkList[$stepnum++] = array("header" => $lang['setup_41'], "status" => $moveToProdStatus,
	"text" =>  "{$lang['setup_42']}
				<div class='chklistbtn' style='display:" . ($status > 0 ? "none" : "block") . ";'>
					{$lang['setup_45']}&nbsp; <button $disableBtn class='jqbuttonmed' onclick=\"btnMoveToProd()\">{$lang['setup_43']}</button>
				</div>"
);

// Move External Links and User Rights/DAGs below Move to Prod when in prod
if ($status > 0) 
{
	// Now add it to the end
	$checkList[$stepnum++] = $checkList[$ExtRes_stepnum];
	// Remove the original now that we added it to the end
	unset($checkList[$ExtRes_stepnum]);
	// Now add it to the end
	$checkList[$stepnum++] = $checkList[$userRights_stepnum];
	// Remove the original now that we added it to the end
	unset($checkList[$userRights_stepnum]);
}


## Modify Fields in Draft Mode
if ($status > 0)
{
	// Check if production project is in draft mode or has been in the past
	$q = db_query("select 1 from redcap_metadata_prod_revisions where project_id = $project_id limit 1");
	$beenRevised = db_num_rows($q);
	$draftCheckStatus = ($draft_mode == '0') ? "" : 1;
	// Set disabled button status
	$draftModeBtnsDisabled = ($status < 1 || ($status > 0 && !$user_rights['design'])) ? "disabled" : "";
	// Set button
	$designBtn = "";
	// Quick Links
	if ($status > 0 && $user_rights['design'])
	{
		$designBtn .=  "{$lang['setup_44']}
						<a href='".APP_PATH_WEBROOT."PDF/index.php?pid=$project_id'
							style='font-size:12px;text-decoration:underline;color:#800000;'>{$lang['design_266']}</a> 
						{$lang['global_46']}
						<a href='javascript:;' onclick='downloadDD(0,{$Proj->formsFromLibrary()});'
							style='font-size:12px;text-decoration:underline;'>{$lang['design_119']} {$lang['global_09']}</a> ";
		if ($draft_mode > 0) {
			$designBtn .=  "{$lang['global_46']}
							<a href='javascript:;' onclick='downloadDD(1,{$Proj->formsFromLibrary()});'
								style='font-size:12px;text-decoration:underline;'>{$lang['design_121']} {$lang['global_09']} {$lang['design_122']}</a>";
		}
	}
	$designBtn .=  "<div class='chklistbtn'>
						{$lang['setup_45']}&nbsp; <button $draftModeBtnsDisabled class='jqbuttonmed' onclick=\"window.location.href=app_path_webroot+'Design/online_designer.php?pid=$project_id';\">{$lang['design_25']}</button>
						&nbsp;{$lang['global_47']}&nbsp; <button $draftModeBtnsDisabled class='jqbuttonmed' style='white-space:nowrap;' onclick=\"window.location.href=app_path_webroot+'Design/data_dictionary_upload.php?pid=$project_id';\">{$lang['design_108']}</button>
					</div>";
	// Survey + Forms
	if ($surveys_enabled) {
		$checkList[$stepnum++] = array("header" => $lang['setup_47'], "status" => $draftCheckStatus, 
			"text" =>  "{$lang['setup_50']}
						$designBtn"
		);	
	} 
	// Forms only
	else {
		$checkList[$stepnum++] = array("header" => $lang['setup_48'], "status" => $draftCheckStatus, 
			"text" =>  "{$lang['setup_51']}
						$designBtn"
		);
	}
}






## Show the PROJECT STATUS (and link to survey how-to video, if applicable)
// Project status label
$statusLabel = '<b style="color:#000;float:none;border:0;">'.$lang['edit_project_58'].'</b>&nbsp; ';	
// Set icon/text for project status
if ($status == '1') {
	$iconstatus = '<span style="color:green;font-weight:normal;">'.$statusLabel.'<img src="'.APP_PATH_IMAGES.'accept.png" class="imgfix"> '.$lang['global_30'].'</span>';
} elseif ($status == '2') {
	$iconstatus = '<span style="color:#800000;font-weight:normal;">'.$statusLabel.'<img src="'.APP_PATH_IMAGES.'delete.png" class="imgfix"> '.$lang['global_31'].'</span>';
} elseif ($status == '3') {
	$iconstatus = '<span style="color:#800000;font-weight:normal;">'.$statusLabel.'<img src="'.APP_PATH_IMAGES.'bin_closed.png" class="imgfix"> '.$lang['global_26'].'</span>';
} else {
	$iconstatus = '<span style="color:#666;font-weight:normal;">'.$statusLabel.'<img src="'.APP_PATH_IMAGES.'page_white_edit.png" class="imgfix"> '.$lang['global_29'].'</span>';
}
// Determine how many steps have been completed thus far
// Only show Steps Completed text when in development
$stepsCompletedText = "";
if ($status < 1) 
{
	$stepsTotal = count($checkList);
	$stepsCompleted = 0;
	$doneStatuses = array('2', '4', '5'); // Status that denote that a step is "done"
	foreach ($checkList as $attr) {
		if (in_array($attr['status'], $doneStatuses)) $stepsCompleted++;
	}
	$stepsCompletedText = "<div style='font-family:verdana,arial;font-size:13px;color:#800000;'>
								{$lang['edit_project_120']} <b id='stepsCompleted'>$stepsCompleted</b> {$lang['survey_133']} 
								<b id='stepsTotal'>$stepsTotal</b>
							</div>";
}
// Output to page above checklist
print  "<div style='clear:both;padding-bottom:10px;max-width:700px;'>
			<table cellspacing=0 width=100%>
				<tr>
					<td valign='top'>
						$iconstatus
					</td>
					<td valign='top' style='text-align:right;'>
						$stepsCompletedText
					</td>
				</tr>
			</table>
		</div>";



## RENDER THE CHECKLIST
ProjectSetup::renderSetupCheckList($checkList, $checkedOff);



## HIDDEN DIALOG DIVS
// Longitudinal enable - hidden dialog
print RCView::simpleDialog($lang['create_project_70'], $lang['setup_93'], 'longiDialog');
// Longitudinal pre-disable confirmation - hidden dialog
print RCView::simpleDialog($lang['setup_110'], $lang['setup_109'], 'longiConfirmDialog');
// Surveys enable - hidden dialog
print RCView::simpleDialog($lang['create_project_71'], $lang['setup_96'], 'useSurveysDialog');
// Surveys pre-disable confirmation - hidden dialog
print RCView::simpleDialog($lang['setup_112'], $lang['setup_111'], 'useSurveysConfirmDialog');
// Auto-numbering enable - hidden dialog
print RCView::simpleDialog($lang['edit_project_44'] .
	(($Proj->firstFormSurveyId != null && $auto_inc_set) ? RCView::div(array('style'=>'color:red;margin-top:10px;'), RCView::b($lang['global_03'].$lang['colon'])." ".$lang['setup_107']) : ''), 
	$lang['edit_project_43'], 'autoNumDialog');
// Scheduling enable - hidden dialog
print RCView::simpleDialog($lang['create_project_54'] .
	(!$repeatforms ? RCView::div(array('style'=>'color:red;margin-top:10px;'), RCView::b($lang['global_03'].$lang['colon'])." ".$lang['setup_108']) : ''), 
	$lang['define_events_19'], 'schedDialog');
// Randomization enable - hidden dialog
print RCView::simpleDialog($lang['random_01']."<br><br>".$lang['create_project_63'], $lang['setup_98'], 'randDialog');
// Survey email field enable - hidden dialog
print RCView::simpleDialog($lang['setup_114']."<br><br>".$lang['setup_122']."<br><br>".RCView::b($lang['global_02'].$lang['colon']) . " " . $lang['setup_115'], $lang['setup_113'], 'surveyEmailFieldDialog');
// Data Entry Trigger explanation - hidden dialog
print RCView::simpleDialog($lang['edit_project_123']."<br><br>".$lang['edit_project_128'] .
	RCView::div(array('style'=>'padding:12px 0 2px;text-indent:-2em;margin-left:2em;'), "&bull; ".RCView::b('project_id')." - ".$lang['edit_project_129']). 
	RCView::div(array('style'=>'padding:2px 0;text-indent:-2em;margin-left:2em;'), "&bull; ".RCView::b('instrument')." - ".$lang['edit_project_130']). 
	RCView::div(array('style'=>'padding:2px 0;text-indent:-2em;margin-left:2em;'), "&bull; ".RCView::b('record')." - ".$lang['edit_project_131'].' ("'.RCView::b($table_pk).'")'.$lang['period']).
	RCView::div(array('style'=>'padding:2px 0;text-indent:-2em;margin-left:2em;'), "&bull; ".RCView::b('redcap_event_name')." - ".$lang['edit_project_132']).
	RCView::div(array('style'=>'padding:2px 0;text-indent:-2em;margin-left:2em;'), "&bull; ".RCView::b('redcap_data_access_group')." - ".$lang['edit_project_133']).
	RCView::div(array('style'=>'padding:2px 0;text-indent:-2em;margin-left:2em;'), "&bull; ".RCView::b('[instrument]_complete')." - ".$lang['edit_project_134']).
	RCView::div(array('style'=>'padding:20px 0 5px;color:#800000;'), $lang['global_02'].$lang['colon'].' '.$lang['edit_project_135'])
	,$lang['edit_project_122'],'dataEntryTriggerDialog');







## MOVE TO PRODUCTION LOGIC
// Get randomization setup status (set to 0 by default for super users approving move-to-prod request, so they don't get prompt)
$randomizationStatus = ($randomization && !($_GET['type'] == "move_to_prod" && $super_user) && Randomization::setupStatus()) ? '1' : '0';
$randProdAllocTableExists = ($randomizationStatus == '1' && Randomization::allocTableExists(1)) ? '1' : '0';
// Set up status-specific language and actions
$status_dialog_title = $lang['edit_project_09'];
$status_dialog_btn 	 = "YES, Move to Production Status";
$status_dialog_btn_action = "doChangeStatus(0,'{$_GET['type']}','{$_GET['user_email']}',$randomizationStatus,$randProdAllocTableExists);";
$iconstatus = '<img src="'.APP_PATH_IMAGES.'page_white_edit.png" class="imgfix"> <span style="color:#666;">'.$lang['global_29'].'</span>';
$status_dialog_text  = "{$lang['edit_project_13']}<br><br>
						<img src='" . APP_PATH_IMAGES . "star.png' class='imgfix'> {$lang['edit_project_55']} 
						<a style='text-decoration:underline;' href='".APP_PATH_WEBROOT."IdentifierCheck/index.php?pid=$project_id'>{$lang['identifier_check_01']}</a> {$lang['edit_project_56']}<br><br>
						<div class='red'>
							<div style='margin-left:2.2em;text-indent:-1.9em;'>
								<input type='checkbox' id='delete_data' "
									. (($_GET['type'] == "move_to_prod" && $super_user && $_GET['delete_data'] == "0") ? "" : " checked ") // check the box
									. (($_GET['type'] == "move_to_prod" && $super_user) ? " disabled " : "") // disable the box
								. " > 
								<b style='font-size:12px;font-family:verdana;'>{$lang['edit_project_59']}</b>
							</div>
						</div><br>								
						{$lang['edit_project_15']}";

// If only Super Users can move to production, then give different text for normal users
if (!$super_user && $superusers_only_move_to_prod == '1') 
{
	$status_dialog_text .= "<br>
							<p style='color:#800000;'>
								<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'> 
								<b>{$lang['global_02']}:</b><br>
								{$lang['edit_project_17']} ($user_email) {$lang['edit_project_18']}
							</p>";
	$status_dialog_btn = "Yes, Request Admin to Move to Production Status";
	$status_dialog_title = $lang['edit_project_19'];
	// Javascript to send email to REDCap admin for approval to move to production
	$status_dialog_btn_action = "var delete_data = 0;
								if ($randomizationStatus == 1 && $randProdAllocTableExists == 0) {
									alert('ERROR: This project is utilizing the randomization module and cannot be moved to production status yet because a randomization allocation table has not been uploaded for use in production status. Someone with appropriate rights must first go to the Randomization page and upload an allocation table.');
									return false;
								}
								if ($('#delete_data:checked').val() !== null) {
									if ($('#delete_data:checked').val() == 'on') {
										delete_data = 1;
										// Make user confirm that they want to delete data
										if (!confirm(\"DELETE ALL DATA?\\n\\nAre you sure you really want to delete all existing data when the project is moved to production? If not, click Cancel and uncheck the checkbox inside the red box.\")) {
											return false;
										}
									} else if ($randomizationStatus == 1) {
										// If not deleting all data BUT using randomization module, remind that the randomization field's values will be erased
										if (!confirm(\"WARNING: RANDOMIZATION FIELD'S DATA WILL BE DELETED\\n\\nSince you have enabled the randomization module, please be advised that if any records contain a value for your randomization field (i.e. have been randomized), those values will be PERMANENTLY DELETED once the project is moved to production. (Only data for that field will be deleted. Other fields will not be touched.) Is this okay?\")) {
											return false;
										}
									}
								}
								$.get(app_path_webroot+'ProjectGeneral/notifications.php', { pid: pid, type: 'move_to_prod', delete_data: delete_data },
									function(data) {
										$('#status_dialog').dialog('close');
										if (data == '1') {
											window.location.href = app_path_webroot+page+'?pid='+pid+'&msg=request_movetoprod';
										} else {
											alert('{$lang['global_01']}: {$lang['edit_project_20']}');
										}
									}
								);";
}
	
// Prepare a "certification" pop-up message when user clicks Move To Prod button if text has been set
if ($status == 0 && trim($certify_text_prod) != "" && (!$super_user || ($super_user && !isset($_GET['user_email'])))) 
{
	print "<div id='certify_prod' class='notranslate' title='Notice' style='display:none;text-align:left;'>".nl2br(html_entity_decode($certify_text_prod, ENT_QUOTES))."</div>";
	// Javascript function for when clicking the 'move to production' button
	print  "<script type='text/javascript'>
			function btnMoveToProd() {
				$('#certify_prod').dialog('destroy');
				$('#certify_prod').dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
					'I Agree': function() {
						$(this).dialog('close');
						$('#status_dialog').dialog('destroy');
						$('#status_dialog').dialog({ bgiframe: true, modal: true, width: 650, buttons: { 
							Cancel: function() { $(this).dialog('close'); }, 
							'$status_dialog_btn': function() { $status_dialog_btn_action }
						} });
					},
					Cancel: function() { $(this).dialog('close'); }
				} });
			}
			</script>";
} else {
	// Javascript function for when clicking the 'move to production' button
	print  "<script type='text/javascript'>
			function btnMoveToProd() {
				$('#status_dialog').dialog({ bgiframe: true, modal: true, width: 650, buttons: { 
					Cancel: function() { $(this).dialog('close'); }, 
					'$status_dialog_btn': function() { $status_dialog_btn_action }
				} });
			}
			</script>";
}		
// If Super User has been sent email to approve request to move db to production, then hide all else.
if ($super_user && $status == 0 && isset($_GET['type']) && $_GET['type'] == "move_to_prod") 
{
	?>
	<script type='text/javascript'>
	$(function(){
		btnMoveToProd();
	});
	</script>
	<?php
}
// Invisible div for status change
print  "<div id='status_dialog' title='$status_dialog_title' style='display:none;'><p style='font-family:arial;'>$status_dialog_text</p></div>";







/**
 * MODIFY PROJECT SETTINGS FORM AS POP-UP
 */
?>
<div id="edit_project" style="display:none;" title="Modify project settings">
	<div class="round chklist" style="padding:10px 20px;">
		<form id="editprojectform" action="<?php echo APP_PATH_WEBROOT ?>ProjectGeneral/edit_project_settings.php?pid=<?php echo $project_id ?>" method="post">
		<table style="width:100%;font-family:Arial;font-size:12px;" cellpadding=0 cellspacing=0>
		<?php
		// Include the page with the form
		include APP_PATH_DOCROOT . 'ProjectGeneral/create_project_form.php';
		?>
		</table>
		</form>
	</div>
</div>
<?php










/**
 * CUSTOMIZE PROJECT SETTINGS FORM AS POP-UP
 */
?>
<div id="customize_project" style="display:none;" title="Make optional customizations to your project">
	<div id="customize_project_sub">
	<p>
		<?php echo $lang['setup_52'] ?>
	</p>
	<div class="round chklist" style="padding:10px 20px;">
		<form id="customizeprojectform" action="<?php echo APP_PATH_WEBROOT ?>ProjectGeneral/edit_project_settings.php?pid=<?php echo $project_id ?>&action=customize" method="post">
		<table style="width:100%;font-family:Arial;font-size:12px;" cellspacing=0>
		<!-- Secondary unique field -->
		<tr>
			<td colspan="2" valign="top" style="margin-left:1.5em;text-indent:-2.2em;padding:10px 40px;">
				<input type="checkbox" id="secondary_pk_chkbx" name="secondary_pk_chkbx" <?php if ($secondary_pk != '') print "checked"; ?>>
				&nbsp;
				<b style="font-family:verdana;"><u><?php echo $lang['edit_project_61'] ?></u></b><br>
				<?php echo $lang['setup_92'] ?> 
				<?php if ($longitudinal) { echo $lang['edit_project_86']; } ?>
				<div id="secondary_pk_div" style="text-indent: 0em; padding: 10px 0px 0px;">
					<?php echo renderSecondIdDropDown("secondary_pk", "secondary_pk") ?>
				</div>
			</td>
		</tr>
		<!-- Order the records by another field -->
		<tr>
			<td colspan="2" valign="top" style="margin-left:1.5em;text-indent:-2.2em;padding:10px 40px;">
				<input type="checkbox" id="order_id_by_chkbx" <?php if (!empty($order_id_by)) print "checked"; ?>>
				&nbsp;
				<b style="font-family:verdana;"><u><?php echo $lang['edit_project_72'] ?></u></b><br>
				<?php 
				echo $lang['edit_project_73'];
				if ($longitudinal) {
					echo " <b style='color:#800000;'>" . $lang['edit_project_74'] . "</b>";
				}
				?>
				<div id="order_id_by_div" style="text-indent:0em;padding:10px 0 0;">
					<select name="order_id_by" id="order_id_by" class="x-form-text x-form-field" style="padding-right:0;height:22px;">
						<option value=''><?php echo $lang['edit_project_71'] ?></option>
					<?php 
					// Get field/label list and put in array
					$order_by_id_fields = array();
					// Child: Get fields for parent
					if ($is_child) {
						$sql = "select field_name, element_label from redcap_metadata where element_type != 'descriptive' project_id = $project_id_parent order by field_order";
						$q = db_query($sql);
						while ($row = db_fetch_assoc($q)) {
							$order_by_id_fields[$row['field_name']] = $row['element_label'];
						}
					} 
					// Regular project
					else {
						foreach ($Proj->metadata as $this_field=>$attr) {
							if ($attr['element_type'] == 'descriptive') continue;
							$order_by_id_fields[$this_field] = $attr['element_label'];
						}
					}
					// Loop through all fields
					foreach ($order_by_id_fields as $this_field=>$this_label)
					{
						// Ignore first field (superfluous)
						if ($this_field == $table_pk) continue;
						$this_label = "$this_field - " . strip_tags(label_decode($this_label));
						// Ensure label is not too long
						if (strlen($this_label) > 67) $this_label = substr($this_label, 0, 50) . "..." . substr($this_label, -15);
						// Add option
						echo "<option value='$this_field' " . ($this_field == $order_id_by ? "selected" : "") . ">$this_label</option>";
					}
					?>
					</select>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2" valign="top" style="margin-left:1.5em;text-indent:-2.2em;padding:10px 40px;">
				<input type="checkbox" id="custom_record_label_chkbx" <?php if (!empty($custom_record_label)) print "checked"; ?>>
				&nbsp;
				<b style="font-family:verdana;"><u><?php echo $lang['edit_project_66'] ?></u></b><br>
				<?php 
				echo $lang['edit_project_67'];
				if ($longitudinal) {
					echo " " . $lang['edit_project_86'];
				}
				if ($is_child) {
					echo " <b>" . $lang['edit_project_75'] . "</b>";
				}
				?>
				<div id="custom_record_label_div" style="text-indent:0em;padding:10px 0 0;">
					<?php echo $lang['edit_project_68'] ?>&nbsp; 
					<input type="text" class="x-form-text x-form-field" style="width:300px;" id="custom_record_label" name="custom_record_label" value="<?php echo str_replace('"', '&quot;', $custom_record_label) ?>"><br>
					<span style="color:#800000;font-family:tahoma;font-size:10px;">
						<?php echo $lang['edit_project_69'] ?>
					</span>
				</div>
			</td>
		</tr>		
		<tr>
			<td colspan="2" valign="top" style="margin-left:1.5em;text-indent:-2.2em;padding:10px 40px;">
				<input type="checkbox" id="history_widget_enabled" name="history_widget_enabled" <?php if ($history_widget_enabled) print "checked"; ?>>
				&nbsp;
				<img src="<?php echo APP_PATH_IMAGES ?>history.png" class="imgfix">&nbsp;
				<b style="font-family:verdana;"><u><?php echo $lang['edit_project_53'] ?></u></b><br>
				<?php echo $lang['edit_project_54'] ?>
			</td>
		</tr>
		<tr>
			<td colspan="2" valign="top" style="margin-left:1.5em;text-indent:-2.2em;padding:10px 40px;">
				<input type="checkbox" id="display_today_now_button" name="display_today_now_button" <?php if ($display_today_now_button) print "checked"; ?>>
				&nbsp;
				<b style="font-family:verdana;"><u><?php echo $lang['system_config_143'] ?></u></b><br>
				<?php echo $lang['system_config_144'] ?>
			</td>
		</tr>
		<tr>
			<td colspan="2" valign="top" style="margin-left:1.5em;text-indent:-2.2em;padding:10px 40px;">
				<input type="checkbox" id="require_change_reason" name="require_change_reason" <?php if ($require_change_reason) print "checked"; ?>>
				&nbsp;
				<b style="font-family:verdana;"><u><?php echo $lang['edit_project_41'] ?></u></b><br>
				<?php echo $lang['edit_project_42'] ?>
			</td>
		</tr>
		
		<!-- Data Entry Trigger (if enabled in the Control Center -->
		<?php if ($data_entry_trigger_enabled) { ?>
		<tr>
			<td colspan="2" valign="top" style="margin-left:1.5em;text-indent:-2.2em;padding:10px 40px;">
				<input type="checkbox" id="data_entry_trigger_url_chkbx" <?php if (!empty($data_entry_trigger_url)) print "checked"; ?>>
				&nbsp;
				<b style="font-family:verdana;"><u><?php echo $lang['edit_project_122'] ?></u></b><br>
				<?php echo $lang['edit_project_123'] ?> 
				<a href="javascript:;" onclick="simpleDialog(null,null,'dataEntryTriggerDialog',650);" class="nowrap" style="text-decoration:underline;"><?php echo $lang['edit_project_127'] ?></a>
				<div id="data_entry_trigger_url_div" style="text-indent:0em;padding:10px 0 0;">
					<?php echo $lang['edit_project_124'] ?>&nbsp; 
					<input type="text" class="x-form-text x-form-field" style="width:350px;" id="data_entry_trigger_url" name="data_entry_trigger_url" value="<?php echo cleanHtml2(label_decode($data_entry_trigger_url)) ?>" onblur="
						this.value=trim(this.value);
						if (this.value.length == 0) return;
						// Disallow localhost
						var localhost_array = new Array('localhost', 'http://localhost', 'https://localhost', 'localhost/', 'http://localhost/', 'https://localhost/');
						if (in_array(this.value, localhost_array)) {
							simpleDialog('<?php echo cleanHtml($lang['edit_project_126']) ?>','<?php echo cleanHtml($lang['global_01']) ?>',null,null,'$(\'#data_entry_trigger_url\').focus();'); 
							return;
						}
						// Validate URL
						if (!isUrl(this.value)) {
							if (this.value.substr(0,4).toLowerCase() != 'http' && isUrl('http://'+this.value)) {
								// Prepend 'http' to beginning
								this.value = 'http://'+this.value;
							} else {
								// Error msg
								simpleDialog('<?php echo cleanHtml($lang['edit_project_126']) ?>','<?php echo cleanHtml($lang['global_01']) ?>',null,null,'$(\'#data_entry_trigger_url\').focus();'); 
							}
						}
					">
					<button class="jqbuttonmed" style="" onclick="if ($('#data_entry_trigger_url').val() == '') { $('#data_entry_trigger_url').focus(); return false; } testUrl($('#data_entry_trigger_url').val(),'post','$(\'#data_entry_trigger_url\').val(\'\');'); return false;"><?php echo $lang['edit_project_138'] ?></button><br>
					<span style="color:#800000;font-family:tahoma;font-size:10px;">
						<?php echo $lang['edit_project_125'] ?> https://www.mywebsite.com/redcap_trigger_receive/
					</span>
				</div>
			</td>
		</tr>
		<?php } ?>
		
		</table>
		</form>
	</div>
	</div>
</div>




<script type='text/javascript'>
// Display the pop-up for project customization
function displayCustomizeProjPopup() {
	$('#customize_project').dialog({ bgiframe: true, modal: true, width: 700, 
		open: function(){
			fitDialog(this);
		}, 
		buttons: { 
			Cancel: function() { $(this).dialog('close'); },
			Save: function() { 
				$('#customizeprojectform').submit();
				$(this).dialog('close'); 
			} 
		} 
	});
}
// Display the pop-up for modifying project settings
function displayEditProjPopup() {
	$('#edit_project').dialog({ bgiframe: true, modal: true, width: 700,  
		open: function(){
			fitDialog(this);
			if ($('#projecttype1').prop('checked') || $('#projecttype2').prop('checked') ) {
				$('#step2').fadeTo(0, 1);
				$('#additional_options').fadeTo(0, 1);
			} else {
				$('#step2').hide();
				$('#additional_options').hide();
			}
			if ($('#repeatforms_chk2').prop('checked')) {
				$('#step3').fadeTo(0, 1);
			}
		}, 
		buttons: { 
			Cancel: function() { $(this).dialog('close'); },
			Save: function() {
				if (setFieldsCreateFormChk()) {
					$('#editprojectform').submit();
					$(this).dialog('close'); 
				}
			} 
		} 
	});
}
// Enable/disable a survey
function surveyOnline(survey_id) {
	$.post(app_path_webroot+'Surveys/survey_online.php?pid='+pid+'&survey_id='+survey_id, { }, function(data){
		var json_data = jQuery.parseJSON(data);
		if (json_data.length < 1) {
			alert(woops);
			return false;
		}
		if (json_data.payload == '') {
			alert(woops);
			return false;
		} else {
			// Change HTML on Project Setup page
			$('#survey_active').html(json_data.payload);
			$('#survey_title_div').effect('highlight',2500);
			initWidgets();
			// If popup_content is specified, the show popup
			if (json_data.popup_content != '') {
				simpleDialog(json_data.popup_content,json_data.popup_title);
			}
		}
	});
}

$(function(){

	// Set up actions for Secondary ID field to be unique
	$('#customize_project #secondary_pk, .chklist #secondary_pk').change(function(){
		var ob = $(this);
		if (ob.val() != '') {
			$.get(app_path_webroot+'DataEntry/check_unique_ajax.php', { pid: pid, field_name: ob.val() }, function(data){
				if (data.length == 0) {
					alert(woops);
				} else if (data != '0') {
					simpleDialog('<?php echo cleanHtml($lang['edit_project_64']) ?>','"'+ob.val()+'" <?php echo cleanHtml($lang['edit_project_63']) ?>');
					ob.val('');
				}
			});
		}
	});	
	// Set up actions for 'Customize project settings' form
	$('#data_entry_trigger_url_chkbx').click(function(){
		if ($(this).prop('checked')) {
			$('#data_entry_trigger_url_div').fadeTo('slow', 1);
			$('#data_entry_trigger_url').prop('disabled',false);
		} else {
			$('#data_entry_trigger_url_div').fadeTo('fast', 0.3);
			$('#data_entry_trigger_url').val('').prop('disabled',true);
		}
	});
	$('#custom_record_label_chkbx').click(function(){
		if ($(this).prop('checked')) {
			$('#custom_record_label_div').fadeTo('slow', 1);
			$('#custom_record_label').prop('disabled',false);
		} else {
			$('#custom_record_label_div').fadeTo('fast', 0.3);
			$('#custom_record_label').prop('disabled',true);
			$('#custom_record_label').val('');
		}
	});
	$('#order_id_by_chkbx').click(function(){
		if ($(this).prop('checked')) {
			$('#order_id_by_div').fadeTo('slow', 1);
			$('#order_id_by').prop('disabled',false);
		} else {
			$('#order_id_by_div').fadeTo('fast', 0.3);
			$('#order_id_by').prop('disabled',true);
			$('#order_id_by').val('');
		}
	});
	$('#secondary_pk_chkbx').click(function(){
		if ($(this).prop('checked')) {
			$('#secondary_pk_div').fadeTo('slow', 1);
			$('#customize_project #secondary_pk').prop('disabled',false);
		} else {
			$('#secondary_pk_div').fadeTo('fast', 0.3);
			$('#customize_project #secondary_pk').prop('disabled',true);
			$('#customize_project #secondary_pk').val('');
		}
	});
	// When load page, disabled drop-down if has no value
	if ($('#customize_project #secondary_pk').val().length < 1) {
		$('#secondary_pk_div').fadeTo(0, 0.3);
		$('#customize_project #secondary_pk').prop('disabled',true);
	}
	$('#purpose').change(function(){
		setTimeout(function(){
			fitDialog($('#edit_project'));
			$('#edit_project').dialog('option', 'position', 'center');
		},700);
	});
	
	
	// Use javascript to pre-fill 'modify project settings' form with existing info
	setTimeout(function()
	{
		$('#app_title').val('<?php echo cleanHtml(filter_tags(html_entity_decode($app_title, ENT_QUOTES))) ?>');
		$('#purpose').val('<?php echo $purpose ?>');
		if ($('#purpose').val() == '1') {
			$('#purpose_other_span').css({'visibility':'visible'}); 
			$('#purpose_other_text').val('<?php echo cleanHtml(filter_tags(html_entity_decode($purpose_other, ENT_QUOTES))) ?>');
			$('#purpose_other_text').css('display','');
		} else if ($('#purpose').val() == '2') {
			$('#purpose_other_span').css({'visibility':'visible'});
			$('#project_pi_irb_div').css('display','');
			$('#project_pi_firstname').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_pi_firstname, ENT_QUOTES))) ?>');
			$('#project_pi_mi').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_pi_mi, ENT_QUOTES))) ?>');
			$('#project_pi_lastname').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_pi_lastname, ENT_QUOTES))) ?>');
			$('#project_pi_email').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_pi_email, ENT_QUOTES))) ?>');
			$('#project_pi_alias').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_pi_alias, ENT_QUOTES))) ?>');
			$('#project_pi_username').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_pi_username, ENT_QUOTES))) ?>');
			$('#project_irb_number').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_irb_number, ENT_QUOTES))) ?>');
			$('#project_grant_number').val('<?php echo cleanHtml(filter_tags(html_entity_decode($project_grant_number, ENT_QUOTES))) ?>');
			$('#purpose_other_research').css('display','');
			var purposeOther = '<?php echo $purpose_other ?>';
			if (purposeOther != '') {
				var purposeArray = purposeOther.split(',');
				for (i = 0; i < purposeArray.length; i++) {
					document.getElementById('purpose_other['+purposeArray[i]+']').checked = true;
				}
			}
		}
		$('#repeatforms_chk_div').css({'display':'block'});
		$('#datacollect_chk').prop('checked',true);
		$('#projecttype<?php echo ($surveys_enabled ? '2' : '1') ?>').prop('checked',true);
		$('#repeatforms_chk<?php echo ($repeatforms ? '2' : '1') ?>').prop('checked',true);
	<?php if ($scheduling) { ?>
		$('#scheduling_chk').prop('checked',true);
	<?php } ?>
	<?php if ($randomization) { ?>
		$('#randomization_chk').prop('checked',true);
	<?php } ?>
	<?php if (empty($custom_record_label)) { ?>
		$('#custom_record_label_div').fadeTo(0, 0.3);
		$('#custom_record_label').val('').prop('disabled',true);
	<?php } ?>
	<?php if (empty($data_entry_trigger_url)) { ?>
		$('#data_entry_trigger_url_div').fadeTo(0, 0.3);
		$('#data_entry_trigger_url').val('').prop('disabled',true);
	<?php } ?>
	<?php if (empty($order_id_by)) { ?>
		$('#order_id_by_div').fadeTo(0, 0.3);
		$('#order_id_by').val('').prop('disabled',true);
	<?php } ?>
	
	// Run function to set up the steps accordingly
	setFieldsCreateForm(false);
		
	<?php if ($status > 0 && !$super_user) { ?>
		// Do not allow normal users to edit project settings if in Production
		$('#projecttype0').prop('disabled',true);
		$('#projecttype1').prop('disabled',true);
		$('#projecttype2').prop('disabled',true);
		$('#datacollect_chk').prop('disabled',true);
		$('#scheduling_chk').prop('disabled',true);
		$('#repeatforms_chk1').prop('disabled',true);
		$('#repeatforms_chk2').prop('disabled',true);
		$('#randomization_chk').prop('disabled',true);
		$('#primary_use_disable').show();
		// Add additional hidden fields to the form for disabled checkboxes to preserve current values
		$('#editprojectform').append('<input type="hidden" name="scheduling" value="<?php echo $scheduling ?>">');
	<?php } ?>
	
	},(isIE6 ? 1000 : 1));
});
</script>

<?php




include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
