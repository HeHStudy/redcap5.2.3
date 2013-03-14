<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Default response
$response = "0";

// Only those with Design rights can delete a project when in development, and super users can always delete
if (isset($_POST['action']) && !empty($_POST['action']) && (($user_rights['design'] && $status < 1) || $super_user))
{
	// Give text to display in the pop-up to DELETE project
	if ($_POST['action'] == "prompt")
	{
		// Give extra warning if delete the project immediately (super users only)
		$deleteImmediatelyWarning = '';
		if (isset($_POST['delete_now']) && $_POST['delete_now'] == "1" && $super_user) {
			$deleteImmediatelyWarning = "<p class='red' style='margin:15px 0;'>
											<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
											<b>{$lang['global_48']}{$lang['colon']}</b> {$lang['control_center_382']}
										 </p>";	
		}		
		// Output html
		$response = "<div style='color:#800000;font-size:14px;margin-bottom:15px;'>
						<img src='".APP_PATH_IMAGES."delete.png' class='imgfix'> 
						{$lang['edit_project_51']} \"<b>".filter_tags(label_decode($app_title))."</b>\"{$lang['period']}
					</div>
					 <p>{$lang['edit_project_139']} \"{$lang['edit_project_48']}\" {$lang['edit_project_140']}</p>
					 $deleteImmediatelyWarning
					 <p style='font-family:verdana;font-weight:bold;margin:20px 0;'>
						{$lang['edit_project_47']} \"{$lang['edit_project_48']}\" {$lang['edit_project_49']}<br>
						<input type='text' id='delete_project_confirm' class='x-form-text x-form-field' style='border:2px solid red;width:170px;'>
					 </p>";
	}
	
	// Give text to display in the pop-up to RESTORE/UNDELETE project
	elseif ($_POST['action'] == "prompt_undelete")
	{
		// Output html
		$response = "<div style='color:green;font-size:14px;margin-bottom:15px;'>
						{$lang['control_center_379']} \"<b>".filter_tags(label_decode($app_title))."</b>\"{$lang['period']}
					</div>
					<div>
						{$lang['control_center_376']} {$lang['control_center_377']}
					</div>";
	}
	
	// Delete the project
	elseif ($_POST['action'] == "delete")
	{
		if (isset($_POST['delete_now']) && $_POST['delete_now'] == "1" && $super_user) {
			// Delete the project immediately (super users only) and log the deletion
			deleteProjectNow($project_id);	
			// Set response
			$response = "1";		
		} else {
			// Flag it for deletion in 30 days. Add "date_deleted" timestamp to project
			$sql = "update redcap_projects set date_deleted = '".NOW."' 
					where project_id = $project_id and date_deleted is null";
			if (db_query($sql)) {
				// Set response
				$response = "1";
				// Logging
				log_event($sql,"redcap_projects","MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Delete project");
			}
		}
	}
	
	// Undelete the project (super users only)
	elseif ($_POST['action'] == "undelete" && $super_user)
	{
		// Remove "date_deleted" timestamp from project
		$sql = "update redcap_projects set date_deleted = null where project_id = $project_id";
		if (db_query($sql)) {
			// Set response
			$response = "1";
			// Logging
			log_event($sql,"redcap_projects","MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Restore/undelete project");
		}
	}
}

print $response;