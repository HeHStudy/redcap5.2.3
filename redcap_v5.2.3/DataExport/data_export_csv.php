<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Increase memory limit so large data sets do not crash and yield a blank page


// Required files
require_once APP_PATH_DOCROOT . 'DataExport/functions.php';
// Call survey_functions
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// Build CSV list of all fields
$fields_csv_list = "'" . implode("', '", array_keys($Proj->metadata)) . "'";

// Set type of output and filename prefix
$getReturnCodes = false; // Default value for flag to return the survey Return Codes
if (!isset($_GET['type']) || (isset($_GET['type']) && $_GET['type'] != 'labels')) {
	// Export raw data (may contain return codes)
	if ($_GET['type'] == 'return_codes') {
		$logging_description = "Export data (CSV raw with return codes)";
		$getReturnCodes = true;
	} else {
		$logging_description = "Export data (CSV raw)";
	}
	$_GET['type'] = 'raw';
	$filename_prefix = "_DATA_";
} else {
	// Export data with labels
	$logging_description = "Export data (CSV labels)";
	$filename_prefix = "_DATA_LABELS_";
}

// Retrieve project data (raw & labels) and headers in CSV format
list ($headers, $headers_labels, $data_csv, $data_csv_labels, $field_names) = fetchDataCsv($fields_csv_list,"",$getReturnCodes);
// Log the event	
log_event("","redcap_data","data_export","",str_replace("'","",$fields_csv_list),$logging_description);

// Write headers for the file to be saved
$filename = substr(str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9 ]/", "", html_entity_decode($app_title, ENT_QUOTES)))), 0, 20) . $filename_prefix . date("Y-m-d-H-i-s") . ".csv";
// $filename = $filename_prefix.strtoupper($app_name."_".$userid).date("_Y-m-d-H-i-s").".CSV";
header('Pragma: anytextexeptno-cache', true);
header("Content-type: application/csv");

header("Content-Disposition: attachment; filename=$filename");

// Output content
if ($_GET['type'] == 'raw') {
	print addBOMtoUTF8($headers . $data_csv);
} elseif ($_GET['type'] == 'labels') {
	print addBOMtoUTF8($headers_labels . $data_csv_labels);
} else {
	print "ERROR!";
}