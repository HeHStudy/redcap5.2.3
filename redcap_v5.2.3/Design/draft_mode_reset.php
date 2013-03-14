<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_CLASSES . 'Message.php';

// Only allow changes to be made if user is Super User and status=waiting approval
if ($super_user && $status == 1 && $draft_mode == 2) {

	// Set up all actions as a transaction to ensure everything is done here
	db_query("SET AUTOCOMMIT=0");
	db_query("BEGIN");

	// Get info of user who requested changes
	$q = db_query("select u.user_firstname, u.user_lastname, u.user_email from redcap_metadata_prod_revisions r, redcap_projects p, 
					  redcap_user_information u where p.project_id = r.project_id and p.project_id = $project_id and r.ts_approved is null 
					  and u.ui_id = r.ui_id_requester order by r.ts_req_approval desc limit 1");
	$srow = db_fetch_array($q);
	
	// First delete all fields for this project in metadata temp table
	$q1 = db_query("delete from redcap_metadata_temp where project_id = $project_id");
	
	// Remove value from prod_revisions table
	$q2 = db_query("delete from redcap_metadata_prod_revisions where project_id = $project_id and ts_approved is null");
	
	// Set draft_mode to "0" and send user back to previous page
	$q3 = db_query("update redcap_projects set draft_mode = 0 where project_id = $project_id");
	
	if ($q1 && $q2 && $q3) {
			
		// Logging
		log_event("","redcap_projects","MANAGE",$project_id,"project_id = $project_id","Reset production project modifications");
	 
		// Email the end-user of reset changes
		$email = new Message ();
		$emailContents = '
			<html>
			<head>
			<title>'.$lang['draft_mode_19'].'</title>
			</head>
			<body style="font-family:Arial;font-size:10pt;">
			'.$lang['global_21'].'<br /><br />
			'.$lang['draft_mode_02'].' 
			"<a href="'.APP_PATH_WEBROOT_FULL.'redcap_v'.$redcap_version.'/index.php?pid='.$project_id.'">'.$app_title.'</a>" 
			'.$lang['draft_mode_20'].'<br /><br /> 
			'.$lang['draft_mode_18'].' '.$user_firstname.' '.$user_lastname.' 
			(<a href="mailto:'.$user_email.'">'.$user_email.'</a>).
			</body>
			</html>';
		$email->setTo($srow['user_email']);
		$email->setFrom($user_email);
		$email->setSubject('[REDCap] ' . $lang['draft_mode_19']);
		$email->setBody($emailContents);
		if (!$email->send()) {
			print "<div style='width:600px;font-family:Arial;font-size:13px;'><b><u>{$lang['draft_mode_06']} {$srow['user_email']}:</u></b><br><br>";
			exit($emailContents);
		}
		
		// Commit changes
		db_query("COMMIT");
		
		// Redirect
		redirect(APP_PATH_WEBROOT . "Design/draft_mode_notified.php?action=reset&pid=$project_id&user_email={$srow['user_email']}&user_firstname={$srow['user_firstname']}&user_lastname={$srow['user_lastname']}");	
	
	} else {
		
		// Errors occurred, so undo any changes made
		db_query("ROLLBACK");
		
		// Redirect
		redirect(APP_PATH_WEBROOT . "Design/project_modifications.php?pid=$project_id");
		
	}
	
}


