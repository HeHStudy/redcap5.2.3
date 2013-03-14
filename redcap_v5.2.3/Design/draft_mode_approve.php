<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_CLASSES . 'Message.php';

// Set up all actions as a transaction to ensure everything is done here
db_query("SET AUTOCOMMIT=0");
db_query("BEGIN");


// Retrieve pr_id from metadata production revisions table for this revision (where ts_approved is NULL)
$sql = "select pr_id from redcap_metadata_prod_revisions where project_id = $project_id and ts_approved is null limit 1";
$q = db_query($sql);
$readyToApprove = (db_num_rows($q) > 0);
if ($readyToApprove) {
	$pr_id = db_result($q, 0);
}


// Only allow changes to be made if user is Super User and status=waiting approval
if (($super_user || ($auto_prod_changes > 0 && isset($_GET['auto_change_token']) && $_GET['auto_change_token'] == md5($__SALT__))) 
	&& $status == 1 && $draft_mode == 2 && $readyToApprove) 
{
	// First, move all existing metadata fields to metadata_archive table as a backup
	$sql = "insert into redcap_metadata_archive select redcap_metadata.*, '$pr_id' from redcap_metadata where project_id = $project_id";
	$q1 = db_query($sql);
		
	## User Rights for form-level rights for new/removed forms
	// Build array for values to be set
	$set_vals = array();
	$set_vals_string = "";
	// Set form-level rights as "1" for all users for any new forms			
	$sql = "select distinct(form_name) from redcap_metadata_temp where project_id = $project_id and 
			form_name not in (" . pre_query("select form_name from redcap_metadata where project_id = $project_id") . ")";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$set_vals_string .= "[{$row['form_name']},1]";
	}
	if ($set_vals_string != "") {
		$set_vals[] = "data_entry = concat(data_entry, '$set_vals_string')";
	}
	// Delete form-level rights from all users for any deleted forms
	$sql = "select distinct(form_name) from redcap_metadata where project_id = $project_id and 
			form_name not in (" . pre_query("select distinct(form_name) from redcap_metadata_temp where project_id = $project_id") . ")";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		// Get name of form to be deleted
		$deleted_form = $row['form_name'];
		// Delete form from all tables EXCEPT metadata tables and user_rights table
		deleteFormFromTables($deleted_form);
		// Catch all 3 possible instances of form-level rights (0, 1, and 2)
		for ($i = 0; $i <= 2; $i++) {
			$set_vals[] = "data_entry = replace(data_entry, '[$deleted_form,$i]', '')";
		}
	}
	// Run query to adjust form-level rights
	$q7 = true;
	if (!empty($set_vals)) {
		$q7 = db_query("update redcap_user_rights set " . implode(", ", $set_vals) . " where project_id = $project_id");
	}
	
	## REMOVE FOR MULTIPLE SURVEYS????? (Should we ALWAYS assume that if first form is a survey that we should preserve first form as survey?)
	// If using first form as survey and form is renamed in DD, then change form_name in redcap_surveys table to the new form name
	if (isset($Proj->forms[$Proj->firstForm]['survey_id']))
	{
		$sql = "select form_name from redcap_metadata_temp where project_id = " . PROJECT_ID . " order by field_order limit 1";
		$q = db_query($sql);
		if (db_num_rows($q) > 0)
		{
			$newFirstForm = db_result($q, 0);
			// Do not rename in table if the new first form is ALSO a survey (assuming it even exists)
			if ($Proj->firstForm != $newFirstForm && !isset($Proj->forms[$newFirstForm]['survey_id']))
			{
				// Change form_name of survey to the new first form name
				$sql = "update redcap_surveys set form_name = '$newFirstForm' where survey_id = ".$Proj->forms[$Proj->firstForm]['survey_id'];
				db_query($sql);
			}
		}
	}
	
	## DELETE UNUSED EDOCS
	// Check for any edocs that have been deleted in Draft Mode and set them as deleted in edocs table
	$sql = "update redcap_edocs_metadata set delete_date = '" . NOW . "' where doc_id in ("
		 . pre_query("select m.edoc_id from redcap_metadata m, redcap_metadata_temp t where m.project_id = $project_id 
					  and m.project_id = t.project_id and m.field_name = t.field_name 
					  and m.edoc_id is not null and (m.edoc_id != t.edoc_id or t.edoc_id is null)") 
		 . ") and project_id = $project_id";
	$q8 = db_query($sql);
	// If a field was deleted in Draft Mode, then set its edoc as deleted in edocs table	
	$sql = "update redcap_edocs_metadata set delete_date = '" . NOW . "' where doc_id in ("
		 . pre_query("select edoc_id from redcap_metadata where project_id = $project_id and edoc_id is not null and field_name 
					  not in (select field_name from redcap_metadata_temp where project_id = $project_id)") 
		 . ") and project_id = $project_id";
	$q9 = db_query($sql);
	
	// Now delete all fields from metadata table now that they've been archived
	$q2 = db_query("delete from redcap_metadata where project_id = $project_id");
	
	// Move all existing metadata temp fields to metadata table
	$q3 = db_query("insert into redcap_metadata select * from redcap_metadata_temp where project_id = $project_id");
	
	// Now delete all fields from metadata temp table now that they've been committed to metadata table
	$q4 = db_query("delete from redcap_metadata_temp where project_id = $project_id");
	
	// Now set draft_mode back to "0" since the changes were approved
	$q5 = db_query("update redcap_projects set draft_mode = 0 where project_id = $project_id");		
	
	// Set ts_approved value in metadata production revisions table
	$q6 = db_query("update redcap_metadata_prod_revisions set ts_approved = '".NOW."', 
					   ui_id_approver = (select ui_id from redcap_user_information where username = '$userid' limit 1) 
					   where project_id = $project_id and ts_approved is null");
	
	// Finalize transaction
	if (!$q1 || !$q2 || !$q3 || !$q4 || !$q5 || !$q6 || !$q7 || !$q8 || !$q9) 
	{
		// Errors occurred
		db_query("ROLLBACK");
		redirect(APP_PATH_WEBROOT . "Design/project_modifications.php?pid=$project_id");
	} 
	else 
	{
		// ALL GOOD - COMMIT CHANGES!
		db_query("COMMIT");	
		db_query("SET AUTOCOMMIT=1");	
		
		// SURVEY QUESTION NUMBERING: Detect if any forms are a survey, and if so, if has any branching logic. If so, disable question auto numbering.
		foreach (array_keys($Proj->surveys) as $this_survey_id)
		{
			if ($Proj->surveys[$this_survey_id]['question_auto_numbering'] && Design::checkSurveyBranchingExists($Proj->surveys[$this_survey_id]['form_name']))
			{
				// Survey is using auto question numbering and has branching, so set to custom numbering
				$sql = "update redcap_surveys set question_auto_numbering = 0 where survey_id = $this_survey_id";
				db_query($sql);
			}
		}
		
		// Logging
		$logTextAppend = (isset($_GET['auto_change_token'])) ? " (automatic)" : "";
		log_event("","redcap_projects","MANAGE",$project_id,"project_id = $project_id","Approve production project modifications".$logTextAppend);
		
		// Set email contents based upon this is a super user OR a normal user with auto prod changes enabled
		if (isset($_GET['auto_change_token'])) {
			// Auto prod changes
			$emailContents = $lang['draft_mode_26'].' 
				"<a href="'.APP_PATH_WEBROOT_FULL.'redcap_v'.$redcap_version.'/index.php?pid='.$project_id.'">'.$app_title.'</a>"
				'.$lang['draft_mode_27'];
		} else {
			// Super user
			$emailContents = $lang['draft_mode_02'].' 
				"<a href="'.APP_PATH_WEBROOT_FULL.'redcap_v'.$redcap_version.'/index.php?pid='.$project_id.'">'.$app_title.'</a>"
				'.$lang['draft_mode_03'].'<br /><br />'.$lang['draft_mode_04'].' '.$user_firstname.' '.$user_lastname.' '.$lang['draft_mode_05'];
		}
		
		## SEND EMAIL BACK TO USER TO INFORM THEM THAT CHANGES WERE COMMITTED
		//Get user info for email
		$q = db_query("select u.username, u.user_firstname, u.user_lastname, u.user_email from redcap_metadata_prod_revisions r, redcap_projects p, 
						  redcap_user_information u where p.project_id = r.project_id and p.project_id = $project_id and r.ts_approved is not null 
						  and u.ui_id = r.ui_id_requester order by r.ts_approved desc limit 1");
		$srow = db_fetch_array($q);
		// If user here is also the requester (and is super user), then don't send confirmation email -> superfluous.
		if (!($super_user && $userid == $srow['username']))
		{
			//Email the user
			$email = new Message ();
			$email->setTo($srow['user_email']);
			$email->setFrom($user_email);
			$email->setSubject('[REDCap] ' . $lang['draft_mode_07']);
			$email->setBody($emailContents, true);
			if (!$email->send()) 
			{
				print "<div style='width:600px;font-family:Arial;font-size:13px;'><b><u>{$lang['draft_mode_06']} {$srow['user_email']}:</u></b><br><br>";
				exit($emailContents);
			}
		}
		
		// AUTO CHANGES: If changes made automatically, then redirect here (no need to send email)
		if (isset($_GET['auto_change_token']))
		{
			redirect(APP_PATH_WEBROOT . "Design/online_designer.php?pid=$project_id&msg=autochangessaved");
		}
	}

}

redirect(APP_PATH_WEBROOT . "Design/draft_mode_notified.php?action=approve&pid=$project_id&user_email={$srow['user_email']}&user_firstname={$srow['user_firstname']}&user_lastname={$srow['user_lastname']}");	
