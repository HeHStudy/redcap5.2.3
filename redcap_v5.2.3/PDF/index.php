<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Must have PHP extention "mbstring" installed in order to render UTF-8 characters properly AND also the PDF unicode fonts installed
$pathToPdfUtf8Fonts = APP_PATH_WEBTOOLS . "pdf" . DS . "font" . DS . "unifont" . DS;
if (function_exists('mb_convert_encoding') && is_dir($pathToPdfUtf8Fonts)) {
	// Define the UTF-8 PDF fonts' path
	define("FPDF_FONTPATH",   APP_PATH_WEBTOOLS . "pdf" . DS . "font" . DS);
	define("_SYSTEM_TTFONTS", APP_PATH_WEBTOOLS . "pdf" . DS . "font" . DS);
	// Set contant
	define("USE_UTF8", true);
	// Use tFPDF class for UTF-8 by default
	require_once APP_PATH_CLASSES . "tFPDF.php";
} else {
	// Set contant
	define("USE_UTF8", false);
	// Use normal FPDF class
	require_once APP_PATH_CLASSES . "FPDF.php";
}
// If using language "Japanese", then use MBFPDF class for multi-byte string rendering
if ($project_language == 'Japanese') 
{
	require_once APP_PATH_CLASSES . "MBFPDF.php"; // Japanese
	// Make sure mbstring is installed
	if (USE_UTF8)
	{
		exit("ERROR: In order for the Japanese text to render correctly in the PDF, you must have the PHP extention \"mbstring\" installed on your web server.");
	}
}

// Include other files needed
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_DOCROOT . "PDF/functions.php"; // This MUST be included AFTER we include the FPDF class

// Increase memory limit so large data sets do not crash and yield a blank page


// Save fields into metadata array
$draftMode = false;
if (isset($_GET['page'])) {
	// Check if we should get metadata for draft mode or not
	$draftMode = ($status > 0 && isset($_GET['draftmode']));
	$metadata_table = ($draftMode) ? "redcap_metadata_temp" : "redcap_metadata";
	// Make sure form exists first
	if ((!$draftMode && !isset($Proj->forms[$_GET['page']])) || ($draftMode && !isset($Proj->forms_temp[$_GET['page']]))) {
		exit('ERROR!');
	}
	$Query = "select * from $metadata_table where project_id = $project_id and ((form_name = '{$_GET['page']}'
			  and field_name != concat(form_name,'_complete')) or field_name = '$table_pk') order by field_order";
} else {
	$Query = "select * from redcap_metadata where project_id = $project_id and
			  (field_name != concat(form_name,'_complete') or field_name = '$table_pk') order by field_order";
}
$QQuery = db_query($Query);
$metadata = array();
while ($row = db_fetch_assoc($QQuery)) 
{
	// If user doesn't have rights to view a form, then don't display it in the PDF
	if (!$draftMode && (!isset($user_rights['forms'][$row['form_name']]) || $user_rights['forms'][$row['form_name']] == '0')) {
		continue;
	}
	// If field is an "sql" field type, then retrieve enum from query result
	if ($row['element_type'] == "sql") {
		$row['element_enum'] = getSqlFieldEnum($row['element_enum']);
	}
	// If PK field...
	if ($row['field_name'] == $table_pk) {
		// Ensure PK field is a text field
		$row['element_type'] = 'text';
		// When pulling a single form other than the first form, change PK form_name to prevent it being on its own page
		if (isset($_GET['page'])) {
			$row['form_name'] = $_GET['page'];
		}
	}
	// Store metadata in array
	$metadata[] = $row;	
}


// In case we need to output the Draft Mode version of the PDF, set $Proj object attributes as global vars
if ($draftMode) {
	$ProjMetadata = $Proj->metadata_temp;
	$ProjForms = $Proj->forms_temp;
	$ProjMatrixGroupNames = $Proj->matrixGroupNamesTemp;
} else {
	$ProjMetadata = $Proj->metadata;
	$ProjForms = $Proj->forms;
	$ProjMatrixGroupNames = $Proj->matrixGroupNames;
}

// Create array of all checkbox fields with "0" defaults
$chkbox_fields = getCheckboxFields(true);

// Initialize values
$Data = array();
$study_id_event = "";
$logging_description = "Download data entry form as PDF" . (isset($_GET['id']) ? " (with data)" : "");


// GET SINGLE RECORD'S DATA (ALL FORMS/ALL EVENTS)
if (isset($_GET['id']) && !isset($_GET['page']) && !isset($_GET['event_id'])) 
{
	// Set logging description
	$logging_description = "Download all data entry forms as PDF (with data)";
	// If in DAG, only give DAG's data
	if ($user_rights['group_id'] == "") {
		$group_sql  = ""; 
	} else {
		$group_sql  = "AND record IN (" . pre_query("SELECT record FROM redcap_data where record = '".prep($_GET['id'])."' and project_id = $project_id and field_name = '__GROUPID__' AND value = '".$user_rights['group_id']."'") . ")"; 
	}
	if ($longitudinal) {
		$data_sql = "select d.record, d.event_id, d.field_name, d.value
					 from redcap_data d, redcap_events_metadata e, redcap_events_arms a
					 where d.project_id = $project_id and d.project_id = a.project_id
					 and a.arm_id = e.arm_id and e.event_id = d.event_id 
					 and d.record = '".prep($_GET['id'])."'
					 ".str_replace("AND record IN (", "AND d.record IN (", $group_sql)."
					 and d.field_name in ('$table_pk', '" . implode("', '", array_keys($Proj->metadata)) . "') 
					 order by abs(d.record), d.record, a.arm_num, e.day_offset, e.descrip";
	} else {
		$data_sql = "SELECT record, event_id, field_name, value FROM redcap_data 
					where project_id = $project_id and record = '".prep($_GET['id'])."' 
					$group_sql and field_name in ('$table_pk', '" . implode("', '", array_keys($Proj->metadata)) . "')
					ORDER BY abs(record), record, event_id";
	}
	$dQuery = db_query($data_sql);
	while ($row = db_fetch_assoc($dQuery)) 
	{
		$row['record'] = trim($row['record']);
		if (isset($chkbox_fields[$row['field_name']])) {
			// Checkboxes
			// First set default values if not set yet
			if (!isset($Data[$row['record']][$row['event_id']][$row['field_name']])) {
				$Data[$row['record']][$row['event_id']][$row['field_name']] = $chkbox_fields[$row['field_name']];
			}
			// Now set this value
			$Data[$row['record']][$row['event_id']][$row['field_name']][$row['value']] = "1";
		} else {
			// Regular non-checkbox fields
			$Data[$row['record']][$row['event_id']][$row['field_name']] = $row['value'];
		}
	}
	db_free_result($dQuery);

}

// GET SINGLE RECORD'S DATA (SINGLE FORM ONLY)
elseif (isset($_GET['id']) && isset($_GET['page']) && isset($_GET['event_id'])) 
{
	$id = trim($_GET['id']);
	// Ensure the event_id belongs to this project, and additionally if longitudinal, can be used with this form
	if (!$Proj->validateEventId($_GET['event_id']) 
		// Check if form has been designated for this event
		|| !$Proj->validateFormEvent($_GET['page'], $_GET['event_id'])
		|| ($id == "") )
	{
		if ($longitudinal) {
			redirect(APP_PATH_WEBROOT . "DataEntry/grid.php?pid=" . PROJECT_ID);
		} else {
			redirect(APP_PATH_WEBROOT . "DataEntry/index.php?pid=" . PROJECT_ID . "&page=" . $_GET['page']);
		}
	}
	// If double data entry, then compensate in query
	if ($double_data_entry && $user_rights['double_data'] > 0) {
		$id .= "--" . $user_rights['double_data'];
	}
	$data_sql = "select field_name, value from redcap_data where project_id = $project_id 
				and event_id = {$_GET['event_id']} and record = '".prep($id)."' and value != '' 
				and field_name in ('$table_pk', '" . implode("', '", array_keys($Proj->forms[$_GET['page']]['fields'])) . "')";
	$dQuery = db_query($data_sql);
	while ($row = db_fetch_assoc($dQuery)) 
	{
		if (isset($chkbox_fields[$row['field_name']])) {
			// Checkboxes
			// First set default values if not set yet
			if (!isset($Data[$id][$_GET['event_id']][$row['field_name']])) {
				$Data[$id][$_GET['event_id']][$row['field_name']] = $chkbox_fields[$row['field_name']];
			}
			// Now set this value
			$Data[$id][$_GET['event_id']][$row['field_name']][$row['value']] = "1";
		} else {
			// Regular non-checkbox fields
			$Data[$id][$_GET['event_id']][$row['field_name']] = $row['value'];
		}
	}
	db_free_result($dQuery);
}

// GET ALL RECORDS' DATA
elseif (isset($_GET['allrecords']) && $user_rights['data_export_tool'] > 0)
{
	// Set logging description
	$logging_description = "Export data as PDF";
	// If in DAG, only give DAG's data
	if ($user_rights['group_id'] == "") {
		$group_sql  = ""; 
	} else {
		$group_sql  = "AND record IN (" . pre_query("SELECT record FROM redcap_data where project_id = $project_id and field_name = '__GROUPID__' AND value = '".$user_rights['group_id']."'") . ")"; 
	}
	$data_sql = "SELECT event_id, record, field_name, value FROM redcap_data where project_id = $project_id and record != '' $group_sql
				and field_name in ('$table_pk', '" . implode("', '", array_keys($Proj->metadata)) . "')
				ORDER BY abs(record), record, event_id";
	$dQuery = db_query($data_sql);
	while ($row = db_fetch_assoc($dQuery)) 
	{
		$row['record'] = trim($row['record']);
		if (isset($chkbox_fields[$row['field_name']])) {
			// Checkboxes
			// First set default values if not set yet
			if (!isset($Data[$row['record']][$row['event_id']][$row['field_name']])) {
				$Data[$row['record']][$row['event_id']][$row['field_name']] = $chkbox_fields[$row['field_name']];
			}
			// Now set this value
			$Data[$row['record']][$row['event_id']][$row['field_name']][$row['value']] = "1";
		} else {
			// Regular non-checkbox fields
			$Data[$row['record']][$row['event_id']][$row['field_name']] = $row['value'];
		}
	}
	db_free_result($dQuery);
}

// BLANK PDF FOR SINGLE FORM OR ALL FORMS
else 
{
	$Data[''][''] = null;
	// Set logging description
	if (isset($_GET['page'])) {
		$logging_description = "Download data entry form as PDF";
	} else {
		$logging_description = "Download all data entry forms as PDF";
	}
}

// If form was downloaded from Shared Library and has an Acknowledgement, render it here
$acknowledgement = getAcknowledgement($project_id, $_GET['page']);

// Logging
log_event("","redcap_metadata","MANAGE",$_GET['page'],"form_name = '{$_GET['page']}'",$logging_description);

// Render the PDF
renderPDF($metadata, $acknowledgement, strip_tags(label_decode($app_title)), $user_rights['data_export_tool'], $Data);
