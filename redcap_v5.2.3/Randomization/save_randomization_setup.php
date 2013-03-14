<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// Default return message
$msg = 'error';

// ERASE the whole randomization setup and allocations
if (isset($_POST['action']) && $_POST['action'] == 'erase')
{
	// Set return message
	$msg = 'error';
	if (Randomization::eraseRandomizationSetup()) {
		$msg = 'erased';
		// Logging
		log_event("", "redcap_randomization", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Erase randomization model and allocations");
	}
}


// SAVE SETUP
else
{
	// Save the randomization model
	$msg = 'error';
	if (Randomization::saveRandomizationSetup($_POST)) {
		$msg = 'saved';
		// Logging
		log_event("", "redcap_randomization", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Save randomization model");
	}
}

// Redirect back to Setup page
redirect(APP_PATH_WEBROOT . "Randomization/index.php?pid=$project_id&msg=$msg");