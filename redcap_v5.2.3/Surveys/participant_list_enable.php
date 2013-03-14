<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

//Default response
$response = "0"; 

## OUTPUT DIALOG POP-UP CONTAINS TO ENABLE/DISABLE
if (isset($_POST['action']) && $_POST['action'] == 'view')
{
	// Set custom title text
	if ($enable_participant_identifiers) {
		$text1 = $lang['survey_255'] . " " . $lang['survey_253'];
		$title = $lang['survey_253'];
		$saveBtn = $lang['survey_257'];
		$successDialogContent .= $lang['survey_258'];
	} else {
		$text1 = $lang['survey_254'] . " " . $lang['survey_252'];
		$title = $lang['survey_252'];
		$saveBtn = $lang['survey_256'];
		$successDialogContent .= $lang['survey_260'];
	}
	$successDialogContent = RCView::b($lang['global_79']) . " $successDialogContent " . $lang['survey_261'];
	// HTML
	$html = RCView::div(array('style'=>'margin:10px 0;'),
		$text1 . 
		RCView::div(array('style'=>'padding-top:10px;color:#777;padding:5px;border:1px solid #ddd;margin-top:20px;font-size:11px;'), 
			RCView::b($lang['global_03'].$lang['colon'])." ".$lang['survey_259']
		)
	);
	
	// Return JSON as response
	$response = '{"saveBtn":"'.cleanHtml2($saveBtn).'","title":"'.cleanHtml2($title).'","payload":"'.cleanHtml2($html).'",'
			  . '"successDialogContent":"'.cleanHtml2($successDialogContent).'"}';
}

## SAVE THE VALUE AS ENABLED OR DISABLED
elseif (isset($_POST['action']) && $_POST['action'] == 'save' && ($status < 1 || $super_user))
{
	// Change existing value
	$new_enable_participant_identifiers = ($enable_participant_identifiers) ? "0" : "1";
	// Save value in table
	$sql = "update redcap_projects set enable_participant_identifiers = $new_enable_participant_identifiers
			where project_id = $project_id";
	if (db_query($sql))
	{
		// If disabling identifiers, then erase *all* existing identifiers 
		// (*except* partial and completed responses that already have identifiers)
		if (!$new_enable_participant_identifiers) 
		{
			$subsql = "select r.participant_id from redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r
					where s.project_id = $project_id and s.survey_id = p.survey_id and p.participant_email is not null 
					and p.participant_email != '' and p.participant_id = r.participant_id";
			$sql = "update redcap_surveys s, redcap_surveys_participants p 
					set p.participant_identifier = null where s.project_id = $project_id 
					and s.survey_id = p.survey_id and p.participant_email is not null 
					and p.participant_email != '' and p.participant_identifier is not null 
					and p.participant_id not in (".pre_query($subsql).")";
			db_query($sql);
		}
		// Logging
		$descrip = ($new_enable_participant_identifiers ? RCLog::DESC_PARTIDENT_ENABLE : RCLog::DESC_PARTIDENT_DISABLE);
		log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id",$descrip);
		// Set response returned
		$response = "1";
	}
}

exit($response);