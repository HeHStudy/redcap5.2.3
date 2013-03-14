<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// Make sure only super users can download the allocation tables when in production
if (!$randomization || ($status > 0 && !$super_user) || !in_array($_GET['status'], array('0','1'))) exit($lang['global_01']);

// Delete the allocation table and return 1/0 of success/failure
if (Randomization::deleteAllocFile($_GET['status'])) {
	print '1';
	// Logging
	$statusText = ($_GET['status'] == '1') ? "production" : "development";
	log_event("", "redcap_randomization_allocation", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Delete randomization allocation table ($statusText)");
} else {
	print '0';
}