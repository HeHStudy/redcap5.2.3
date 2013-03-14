<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Make sure only super users can download the allocation tables when in production
if (!$randomization || !in_array($_GET['status'], array('0','1')) ||
	($_GET['status'] == '1' && $status == '1' && !$super_user)
	) 
{
	exit($lang['random_11']);
}

// Get contents of allocation table
$output = Randomization::getAllocFileContents($_GET['status']);

// Logging
$statusText = ($_GET['status'] == '1') ? "production" : "development";
log_event("", "redcap_randomization", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Download randomization allocation table ($statusText)");

// Output to file
$filename = "RandomizationAllocationTable_" . ($_GET['status'] ? "Prod" : "Dev") . ".csv";
header('Pragma: anytextexeptno-cache', true);
header("Content-type: application/csv");

header("Content-Disposition: attachment; filename=$filename");
print $output;