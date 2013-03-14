<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

## DISPLAY TABLE IN DIALOG
if (!isset($_POST['action']))
{
	// Get survey_id (if submitted)
	if (isset($_GET['survey_id']) && $_GET['survey_id'] != '' && !$Proj->validateSurveyId($_GET['survey_id'])) {
		$_GET['survey_id'] = $Proj->firstFormSurveyId;
	}
	
	// First get list of all project users to fill default values for array
	$endSurveyNotify = array();
	$sql = "select if(u.ui_id is null,0,1) as hasEmail, u.user_email as email, lower(r.username) as username from redcap_user_rights r 
			left outer join redcap_user_information u 
			on u.username = r.username where r.project_id = $project_id order by lower(r.username)";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		$endSurveyNotify[$row['username']] = array('surveys'=>array(), 'hasEmail'=>$row['hasEmail'], 'email'=>$row['email']); // where 0 is default value
	}
	// Get list of users who have and have not been set up for survey notification via email
	$sql = "select lower(u.username) as username, a.survey_id from redcap_actions a, redcap_user_information u 
			where a.project_id = $project_id and a.action_trigger = 'ENDOFSURVEY' and a.action_response = 'EMAIL' 
			and u.ui_id = a.recipient_id order by u.username";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		$endSurveyNotify[$row['username']]['surveys'][$row['survey_id']] = 1;
	}

	// Instructions
	$h = $lang['setup_56'] . 
		 // If only display one survey, then given option to display all surveys
		 ((isset($_GET['survey_id']) && $_GET['survey_id'] != '' && count($Proj->surveys) > 1)
			? RCView::div(array('style'=>'padding-top:8px;'),
				RCView::b($lang['survey_373']) . RCView::SP . RCView::a(array('href'=>'javascript:;','style'=>'text-decoration:underline;','onclick'=>'displayTrigNotifyPopup()'), $lang['survey_372'])
			  )
			: ""
		 );
	
	//print_array($Proj->surveys);
	// Display table with all project users for each survey
	foreach ($Proj->surveys as $this_survey_id=>$survey_attr) 
	{
		// If survey_id was sent in request, then only show that specific survey
		if (isset($_GET['survey_id']) && $_GET['survey_id'] != '' && $_GET['survey_id'] != $this_survey_id) continue;
		
		// First, build rows for each user in the project
		$r = '';
		foreach ($endSurveyNotify as $this_user=>$attr) 
		{
			// Is user already checked for this survey?
			$checked = (isset($attr['surveys'][$this_survey_id])) ? "checked" : "";
			// Build row for user
			$r .=	RCView::tr('',					
						RCView::td(array('class'=>'data','style'=>'padding:0 10px 0 5px;'),
							RCView::div(array('style'=>'float:left;'),
								// Display username
								$this_user . 
								// Display email
								RCView::span(array('style'=>'margin-left:10px;font-size:10px;color:#777;font-family:tahoma;'),
									"(" . (($attr['hasEmail'] && $attr['email'] != '')
										? RCView::a(array('href'=>'mailto:'.$attr['email'],'style'=>'font-size:10px;text-decoration:underline;font-family:tahoma;'), $attr['email'])
										: $lang['setup_58']
									) . ")"
								)
							) .
							// "Changes saved!" message (will display when user clicks checkbox)
							RCView::div(array('id'=>'triggerEndSurv-svd-'.$this_survey_id.'-'.$this_user,'style'=>'float:right;display:none;color:red;font-size:11px;font-family:arial;'),
								$lang['setup_57']
							)
						) .					
						RCView::td(array('class'=>'data','style'=>'height:18px;color:#bbb;font-size:9px;font-family:tahoma;width:35px;text-align:center;'),
							// Checkbox for user
							($attr['hasEmail'] 
								? RCView::checkbox(array('onclick'=>"endSurvTrig('$this_user',this.checked,$this_survey_id);",$checked=>$checked))
								: ""
							)
						)
					);
		}
		
		// Build table for this survey
		$h .= 	RCView::table(array('cellspacing'=>'0','class'=>'form_border','style'=>'margin:15px 0;width:100%;'),
					// Table header
					RCView::tr('',					
						RCView::td(array('class'=>'header','style'=>'color:#800000;'),
							$survey_attr['title']
						) .					
						RCView::td(array('class'=>'header','style'=>'text-align:center;'),
							RCView::img(array('src'=>'email.png','class'=>'imgfix'))
						)
					) .
					// All rows of users
					$r
				);
	}
	
	// Set dialog title
	$t = RCView::img(array('src'=>'email.png','style'=>'vertical-align:middle;')) . 
		 RCView::span(array('style'=>'vertical-align:middle;'), $lang['setup_55']);
		
	// Send back JSON with info
	exit('{"title":"' . cleanHtml2($t) . '","content":"' . cleanHtml2($h) . '"}');
}




## SAVE TRIGGER FOR END-SURVEY EMAIL RESPONSE
if ($_POST['action'] == "endsurvey_email")
{
	// Get survey_id
	if (!isset($_GET['survey_id']) || (isset($_GET['survey_id']) && !$Proj->validateSurveyId($_GET['survey_id']))) {
		$_GET['survey_id'] = $Proj->firstFormSurveyId;
	}
	
	// Save value
	if ($_POST['value'] == '1') {
		$sql = "insert into redcap_actions (project_id, survey_id, action_trigger, action_response, recipient_id) values
				($project_id, {$_GET['survey_id']}, 'ENDOFSURVEY', 'EMAIL', (select ui_id from redcap_user_information 
				where username = '".prep($_POST['username'])."' limit 1))";
	} else {
		$sql = "delete from redcap_actions where project_id = $project_id and survey_id = {$_GET['survey_id']} 
				and action_trigger = 'ENDOFSURVEY' and action_response = 'EMAIL' 
				and recipient_id = (select ui_id from redcap_user_information where username = '".prep($_POST['username'])."')";
	}	
	if (!db_query($sql)) exit('0');
	
	// Log the event
	log_event($sql, "redcap_actions", "MANAGE", $_POST['username'], "username = '{$_POST['username']}'\nsurvey_id = {$_GET['survey_id']}", "Enabled survey notification for user");
	
	// Response
	exit('1');
}
