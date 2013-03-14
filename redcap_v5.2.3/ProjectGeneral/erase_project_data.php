<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

if ($_GET['action'] == "erase_data" && (($user_rights['design'] && $status < 1) || $super_user)) 
{
	// Set up all actions as a transaction to ensure everything is done here
	db_query("SET AUTOCOMMIT=0");
	db_query("BEGIN");
	
	// "Delete" edocs for 'file' field type data (keep its record in table so actual files can be deleted later from web server, if needed)
	$sql = "update redcap_metadata m, redcap_data d, redcap_edocs_metadata e 
			set e.delete_date = '".NOW."' where m.project_id = $project_id 
			and m.project_id = d.project_id and e.project_id = m.project_id and m.element_type = 'file' 
			and d.field_name = m.field_name and d.value = e.doc_id";
	$q5 = db_query($sql);
	// Delete project data
	$q1 = db_query("delete from redcap_data where project_id = $project_id");
	// Delete calendar events
	$q2 = db_query("delete from redcap_events_calendar where project_id = $project_id");
	// Delete logged events (only delete data-related logs)
	$sql = "delete from redcap_log_event where project_id = $project_id and object_type not like '%\_rights' 
			and (event in ('UPDATE', 'INSERT', 'DELETE', 'DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE')
			or (event = 'MANAGE' and description = 'Download uploaded document'))";
	$q3 = db_query($sql);
	// Delete docs
	$q4 = db_query("delete from redcap_docs where project_id = $project_id");
	// Delete locking data
	$q6 = db_query("delete from redcap_locking_data where project_id = $project_id");
	// Delete esignatures
	$q10 = db_query("delete from redcap_esignatures where project_id = $project_id");
	// Delete survey-related info (response tracking, emails, participants) but not actual survey structure
	$survey_ids = pre_query("select survey_id from redcap_surveys where project_id = $project_id");
	// Delete "participants" for follow-up surveys only (do NOT delete public survey "participants" or initial survey participants)
	$q7 = db_query("delete from redcap_surveys_participants where survey_id in (0, $survey_ids) and participant_email = ''");
	// Delete emails to those in Participant List
	$q8 = db_query("delete from redcap_surveys_emails where survey_id in (0, $survey_ids)");
	// Delete survey responses
	$response_ids = pre_query("select r.response_id from redcap_surveys_response r, redcap_surveys_participants p 
							   where p.participant_id = r.participant_id and p.survey_id in (0, $survey_ids)");
	$q9 = db_query("delete from redcap_surveys_response where response_id in (0, $response_ids)");
	// Remove any randomization assignments
	$q12 = db_query("update redcap_randomization_allocation a, redcap_randomization r set a.is_used_by = null 
						where r.project_id = $project_id and r.rid = a.rid");
	// Remove all survey invitations that were queued for records in this project
	$ss_ids = pre_query("select ss_id from redcap_surveys_scheduler where survey_id in (0, $survey_ids)");
	$q11 = db_query("delete from redcap_surveys_scheduler_queue where ss_id in (0, $ss_ids)");
	// Delete all entries in redcap_data_cleaner
	if (isDev()) {
		$q13 = db_query("delete from redcap_data_cleaner where project_id = $project_id");
	}
	
	// Commit changes
	if ( !$q1 || !$q2 || !$q3 || !$q4 || !$q5 || !$q6 || !$q7 || !$q8 || !$q9 || !$q10 || !$q11 || !$q12 || (isset($q13) && !$q13) ) {
		// Errors occurred
		db_query("ROLLBACK");
		// Give unsuccessful response back
		exit("0");
	} else {
		// All good
		db_query("COMMIT");
		db_query("SET AUTOCOMMIT=1");
		// Logging
		log_event("","redcap_data","MANAGE",$project_id,"project_id = $project_id","Erase all data");
		// Give affirmative response back
		exit("1");
	}

}

// Not supposed to be here, so redirect
redirect(APP_PATH_WEBROOT_PARENT);
