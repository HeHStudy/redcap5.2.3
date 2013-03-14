<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


/**
 * CHANGE THE PROJECT STATUS
 */
 
## ACTION: prod/inactive/archived=>dev
if (isset($_GET['moveToDev']) && $status > 0 && $super_user) 
{
	// Remove production date and set
	$sql = "update redcap_projects set status = 0, draft_mode = 0, production_time = NULL, inactive_time = NULL 
			where project_id = $project_id";
	if (db_query($sql))
	{
		// Make sure there are no residual fields from Draft Mode
		db_query("delete from redcap_metadata_temp where project_id = $project_id");
		db_query("delete from redcap_metadata_prod_revisions where project_id = $project_id");
		// Logging
		log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Move project back to development status");
		exit("1");
	}
	exit("0");
} 




 
## ACTIONS: dev=>prod, prod=>inactive, inactive=>prod, archived=>prod	
elseif ($_GET['do_action_status']) 
{
	
	// Set to Inactive
	if ($status == 1 && $_GET['archive'] == 0) {
		$newstatus = 2;
		// Set timestamp for inactivity
		db_query("update redcap_projects set inactive_time = '".NOW."' where project_id = $project_id");
		// Logging
		log_event("","redcap_projects","MANAGE",$project_id,"project_id = $project_id","Set project as inactive");
		
	// Set to Archived
	} elseif ($_GET['archive'] == 1) {
		$newstatus = 3;
		// Logging
		log_event("","redcap_projects","MANAGE",$project_id,"project_id = $project_id","Archive project");
	// Set to Production
	} else {
		$newstatus = 1;
		// If dev=>prod, then delete ALL data for this project and reset all logging, docs, etc.
		if ($status == 0) {
			// Delete project data and all documents and calendar events, if user checked the checkbox to do so
			if ($_GET['delete_data']) 
			{
				// "Delete" edocs for 'file' field type data (keep its record in table so actual files can be deleted later from web server, if needed)
				$sql = "select e.doc_id from redcap_metadata m, redcap_data d, redcap_edocs_metadata e where m.project_id = $project_id 
						and m.project_id = d.project_id and e.project_id = m.project_id and m.element_type = 'file' 
						and d.field_name = m.field_name and d.value = e.doc_id";
				$fileFieldEdocIds = pre_query($sql);
				db_query("update redcap_edocs_metadata set delete_date = '".NOW."' where project_id = $project_id and doc_id in ($fileFieldEdocIds)");
				// Delete project data
				db_query("delete from redcap_data where project_id = $project_id");				
				// Delete calendar events
				db_query("delete from redcap_events_calendar where project_id = $project_id");
				// Delete logged events (only delete data-related logs)
				$sql = "delete from redcap_log_event where project_id = $project_id and object_type not like '%\_rights' 
						and (event in ('UPDATE', 'INSERT', 'DELETE', 'DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE')
						or (event = 'MANAGE' and description = 'Download uploaded document'))";
				db_query($sql);
				// Delete docs (but only export files, not user-uploaded files)
				db_query("delete from redcap_docs where project_id = $project_id and export_file = 1");
				// Delete locking data
				db_query("delete from redcap_locking_data where project_id = $project_id");
				// Delete esignatures
				db_query("delete from redcap_esignatures where project_id = $project_id");
				// Delete survey-related info (response tracking, emails, participants) but not actual survey structure
				$survey_ids = pre_query("select survey_id from redcap_surveys where project_id = $project_id");
				$participant_ids = pre_query("select participant_id from redcap_surveys_participants where survey_id in (0, $survey_ids)");
				db_query("delete from redcap_surveys_emails where survey_id in (0, $survey_ids)");
				db_query("delete from redcap_surveys_response where participant_id in (0, $participant_ids)");
			}
			// If not deleting all data BUT using the randomization module, DELETE ONLY the randomization field's data
			elseif ($randomization && Randomization::setupStatus())
			{
				// Get randomization setup values first
				$randAttr = Randomization::getRandomizationAttributes();
				if ($randAttr !== false) {
					Randomization::deleteSingleFieldData($randAttr['targetField']);
				}
			}
			// Add production date
			db_query("update redcap_projects set production_time = '".NOW."', inactive_time = NULL where project_id = $project_id");
			// Logging
			log_event("","redcap_projects","MANAGE",$project_id,"project_id = $project_id","Move project to production status");
		// Moving BACK to production from inactive
		} else {		
			// Logging
			log_event("","redcap_projects","MANAGE",$project_id,"project_id = $project_id","Return project to production from inactive status");
		}
	}
	// Query
	$sql = "update redcap_projects set status = $newstatus where project_id = $project_id";	
	// Run query and set response
	print db_query($sql) ? $newstatus : 0;
	exit;
}

// Not supposed to be here, so redirect
redirect(APP_PATH_WEBROOT_PARENT);
