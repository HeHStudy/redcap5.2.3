<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Set the form menu description for the form
if (isset($_GET['action']) && $_GET['action'] == "set_menu_name") {

	//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
		
	## Set the new form menu name
	$_GET['menu_description'] = remBr(strip_tags(label_decode($_GET['menu_description'])));
	// First set all form_menu_description as null
	$sql = "update $metadata_table set form_menu_description = null where form_name = '".prep($_GET['page'])."' and project_id = $project_id";
	$q1 = db_query($sql);
	// Get lowest field_order in form
	$sql = "select field_name from $metadata_table where form_name = '".prep($_GET['page'])."' and project_id = $project_id order by field_order limit 1";
	$q1 = db_query($sql);
	$min_field_order_var = db_result($q1, 0);
	// Now add the new form menu label
	$sql = "update $metadata_table set form_menu_description = '".prep($_GET['menu_description'])."' 
			where field_name = '$min_field_order_var' and project_id = $project_id";
	$q1 = db_query($sql);
	
	// As a default, the form_name stays the same value
	$new_form_name = $_GET['page'];
	
	## If in DEVELOPMENT ONLY, change the back-end form name value based upon the form menu name and ensure uniqueness
	// Cannot do this in production because of issues with form name being tied to Form Status field)
	if ($status < 1) 
	{
		$new_form_name = preg_replace("/[^a-z0-9_]/", "", str_replace(" ", "_", strtolower(html_entity_decode($_GET['menu_description'], ENT_QUOTES))));
		// Remove any double underscores, beginning numerals, and beginning/ending underscores
		while (strpos($new_form_name, "__") !== false) 		$new_form_name = str_replace("__", "_", $new_form_name);
		while (substr($new_form_name, 0, 1) == "_") 		$new_form_name = substr($new_form_name, 1);
		while (substr($new_form_name, -1) == "_") 			$new_form_name = substr($new_form_name, 0, -1);
		while (is_numeric(substr($new_form_name, 0, 1))) 	$new_form_name = substr($new_form_name, 1);
		while (substr($new_form_name, 0, 1) == "_") 		$new_form_name = substr($new_form_name, 1);
		// Cannot begin with numeral and cannot be blank
		if (is_numeric(substr($new_form_name, 0, 1)) || $new_form_name == "") {
			$new_form_name = substr(preg_replace("/[0-9]/", "", md5($new_form_name)), 0, 4) . $new_form_name;
		}
		// Make sure it's less than 50 characters long
		$new_form_name = substr($new_form_name, 0, 50);
		while (substr($new_form_name, -1) == "_") $new_form_name = substr($new_form_name, 0, -1);
		// Make sure this form value doesn't already exist
		if ($new_form_name != $_GET['page']) {
			$formExists = ($status > 0) ? isset($Proj->forms_temp[$new_form_name]) : isset($Proj->forms[$new_form_name]);
			while ($formExists) {
				// Make sure it's less than 64 characters long
				$new_form_name = substr($new_form_name, 0, 45);
				// Append random value to form_name to prevent duplication
				$new_form_name .= "_" . substr(md5(rand()), 0, 4);
				// Try again
				$formExists = ($status > 0) ? isset($Proj->forms_temp[$new_form_name]) : isset($Proj->forms[$new_form_name]);
			}
		}
		// Change back-end form name in metadata table
		$sql = "update $metadata_table set form_name = '".prep($new_form_name)."' where form_name = '".prep($_GET['page'])."' and project_id = $project_id";
		db_query($sql);
		// Change back-end form name in event_forms table
		$sql = "update redcap_events_forms set form_name = '".prep($new_form_name)."' where form_name = '".prep($_GET['page'])."' 
				and event_id in (" . pre_query("select m.event_id from redcap_events_arms a, redcap_events_metadata m where a.arm_id = m.arm_id and a.project_id = $project_id") . ")";
		db_query($sql);		
		// Change back-end form name in user_rights table
		$sql = "update redcap_user_rights set data_entry = replace(data_entry, '[{$_GET['page']},', '[$new_form_name,')
				where project_id = $project_id";
		db_query($sql);		
		// Change back-end form name in library_map table
		$sql = "update redcap_library_map set form_name = '".prep($new_form_name)."' where project_id = $project_id and form_name = '".prep($_GET['page'])."'";
		db_query($sql);			
		// Change back-end form name in locking tables
		$sql = "update redcap_locking_labels set form_name = '".prep($new_form_name)."' where project_id = $project_id and form_name = '".prep($_GET['page'])."'";
		db_query($sql);
		$sql = "update redcap_locking_data set form_name = '".prep($new_form_name)."' where project_id = $project_id and form_name = '".prep($_GET['page'])."'";
		db_query($sql);
		$sql = "update redcap_esignatures set form_name = '".prep($new_form_name)."' where project_id = $project_id and form_name = '".prep($_GET['page'])."'";
		db_query($sql);
		// Change back-end form name in survey table
		$sql = "update redcap_surveys set form_name = '".prep($new_form_name)."' where project_id = $project_id and form_name = '".prep($_GET['page'])."'";
		db_query($sql);
		// Change variable name of the form's Form Status field	
		$sql = "update $metadata_table set field_name = '{$new_form_name}_complete' where field_name = '{$_GET['page']}_complete' and project_id = $project_id";
		db_query($sql);
		// Change actual data table field_names to reflect the changed Form Status field
		$sql = "update redcap_data set field_name = '{$new_form_name}_complete' where field_name = '{$_GET['page']}_complete' and project_id = $project_id";
		db_query($sql);
	}
	
	// Get survey title, if enabled as a survey
	$surveyTitle = "";
	if ($surveys_enabled) {
		$sql = "select title from redcap_surveys where project_id = $project_id and form_name = '".prep($new_form_name)."' limit 1";
		$q = db_query($sql);
		if (db_num_rows($q)) {
			$surveyTitle = strip_tags(label_decode(db_result($q, 0)));
		}
	}
	
	// Logging
	if ($q1) log_event("",$metadata_table,"MANAGE",$_GET['page'],"form_name = '".prep($_GET['page'])."'","Rename data collection instrument");
	
	// Response (form_name \ label \ survey title)
	print $new_form_name . "\n" . $_GET['menu_description'] . "\n" . $surveyTitle;
	
}
