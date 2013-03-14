<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Get contents of allocation template file
$output = Randomization::getAllocTemplateFileContents($_GET['example_num']);

// Logging
log_event("", "redcap_randomization", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Download randomization allocation template");

// Output to file
$filename = "RandomizationAllocationTemplate.csv";
header('Pragma: anytextexeptno-cache', true);
header("Content-type: application/csv");

header("Content-Disposition: attachment; filename=$filename");
print $output;
