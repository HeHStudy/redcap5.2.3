<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
 
// Default response
$response = "0";


## DISPLAY DIALOG PROMPT
if (isset($_POST['setting']) && isset($_POST['action']) && $_POST['action'] == 'view')
{
	## Display different messages for different settings	
	// Survey participant email address field
	if ($_POST['setting'] == 'survey_email_participant_field') 
	{
		// Collect all email-validated fields and their labels into an array
		$emailFieldsLabels = array(''=>'--- '.$lang['random_02'].' ---');
		foreach ($Proj->metadata as $field=>$attr) {
			if ($attr['element_validation_type'] == 'email') {
				$emailFieldsLabels[$field] = "$field (\"{$attr['element_label']}\")";
			}
		}
		// Set dialog content
		$response = RCView::div(array(),
						$lang['setup_114'] . "<br><br>" . $lang['setup_122'] . "<br><br>" . 
						RCView::b($lang['global_02'].$lang['colon']) . " " . $lang['setup_115'] . "<br><br>" .
						RCView::b($lang['setup_116']) . RCView::br() .
						RCView::select(array('style'=>'width:70%;','id'=>'surveyPartEmailFieldName'), $emailFieldsLabels, '', 300)
					);
	}
}


## SAVE PROJECT SETTING VALUE
else
{
	// Make sure the "name" setting is a real one that we can change
	$viableSettingsToChange = array('auto_inc_set', 'scheduling', 'randomization', 'repeatforms', 'surveys_enabled', 
									'survey_email_participant_field');
	if (!empty($_POST['name']) && in_array($_POST['name'], $viableSettingsToChange)) 
	{
		// Modify setting in table
		$sql = "update redcap_projects set {$_POST['name']} = '" . prep(label_decode($_POST['value'])). "' 
				where project_id = $project_id";
		if (db_query($sql)) {
			$response = "1";
			// Logging
			log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Modify project settings");
		}
	}
}


// Send response
print $response;
