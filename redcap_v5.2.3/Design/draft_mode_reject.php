<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_CLASSES . 'Message.php';

// Only allow changes to be made if user is Super User and status=waiting approval
if ($super_user && $status == 1 && $draft_mode == 2) {

	//Get info of user who requested changes
	$q = db_query("select u.user_firstname, u.user_lastname, u.user_email from redcap_metadata_prod_revisions r, redcap_projects p, 
					  redcap_user_information u where p.project_id = r.project_id and p.project_id = $project_id and r.ts_approved is null 
					  and u.ui_id = r.ui_id_requester order by r.ts_req_approval desc limit 1");
	$srow = db_fetch_array($q);
	
	// Set draft_mode to "1" and send user back to previous page in Draft Mode
	$sql = "update redcap_projects set draft_mode = 1 where project_id = $project_id";
	$q1 = db_query($sql);
	// Remove value from prod_revisions table
	$q2 = db_query("delete from redcap_metadata_prod_revisions where project_id = $project_id and ts_approved is null");
	
	if ($q1 && $q2) {
			
		// Logging
		log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Reject production project modifications");		
	 
		//Email the end-user of rejected changes
		$email = new Message ();
		$emailContents = '
			<html>
			<head>
			<title>'.$lang['draft_mode_16'].'</title>
			</head>
			<body style="font-family:Arial;font-size:10pt;">
			'.$lang['global_21'].'<br /><br />
			'.$lang['draft_mode_02'].' 
			"<a href="'.APP_PATH_WEBROOT_FULL.'redcap_v'.$redcap_version.'/index.php?pid='.$project_id.'">'.$app_title.'</a>" 
			'.$lang['draft_mode_17'].'<br /><br /> 
			'.$lang['draft_mode_18'].' '.$user_firstname.' '.$user_lastname.' 
			(<a href="mailto:'.$user_email.'">'.$user_email.'</a>).
			</body>
			</html>';
		$email->setTo($srow['user_email']);
		$email->setFrom($user_email);
		$email->setSubject('[REDCap] ' . $lang['draft_mode_16']);
		$email->setBody($emailContents);
		if (!$email->send()) {
			print "<div style='width:600px;font-family:Arial;font-size:13px;'><b><u>{$lang['draft_mode_06']} {$srow['user_email']}:</u></b><br><br>";
			exit($emailContents);
		}
	
	}
	
}

redirect(APP_PATH_WEBROOT . "Design/draft_mode_notified.php?action=reject&pid=$project_id&user_email={$srow['user_email']}&user_firstname={$srow['user_firstname']}&user_lastname={$srow['user_lastname']}");	
