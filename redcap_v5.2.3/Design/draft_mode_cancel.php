<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

if ($status > 0)
{
	// First, delete all fields for this project in metadata temp table (i.e. in Draft Mode)
	$q1 = db_query("delete from redcap_metadata_temp where project_id = $project_id");

	// Now set draft_mode to "0"
	$q2 = db_query("update redcap_projects set draft_mode = 0 where project_id = $project_id");
		
	// Logging
	log_event("","redcap_projects","MANAGE",$project_id,"project_id = $project_id","Cancel draft mode");
}

// Redirect back to previous page
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
	redirect($_SERVER['HTTP_REFERER'] . "&msg=cancel_draft_mode");
} else {
	// If can't find referer, just send back to Online Designer
	redirect(APP_PATH_WEBROOT . "Design/online_designer.php?pid=$project_id&msg=cancel_draft_mode");
}
