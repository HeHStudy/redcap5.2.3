<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

$_POST['label'] = $_POST['form_name'] = trim(html_entity_decode($_POST['form_name'], ENT_QUOTES));
// Remove illegal characters first
$_POST['form_name'] = preg_replace("/[^a-z_0-9]/", "", str_replace(" ", "_", strtolower($_POST['form_name'])));
// Remove any double underscores, beginning numerals, and beginning/ending underscores
while (strpos($_POST['form_name'], "__") !== false) 	$_POST['form_name'] = str_replace("__", "_", $_POST['form_name']);
while (substr($_POST['form_name'], 0, 1) == "_") 		$_POST['form_name'] = substr($_POST['form_name'], 1);
while (substr($_POST['form_name'], -1) == "_") 			$_POST['form_name'] = substr($_POST['form_name'], 0, -1);
while (is_numeric(substr($_POST['form_name'], 0, 1))) 	$_POST['form_name'] = substr($_POST['form_name'], 1);
while (substr($_POST['form_name'], 0, 1) == "_") 		$_POST['form_name'] = substr($_POST['form_name'], 1);
// Cannot begin with numeral and cannot be blank
if (is_numeric(substr($_POST['form_name'], 0, 1)) || $_POST['form_name'] == "") {
	$_POST['form_name'] = substr(preg_replace("/[0-9]/", "", md5($_POST['form_name'])), 0, 4) . $_POST['form_name'];
}
// Make sure it's less than 50 characters long
$_POST['form_name'] = substr($_POST['form_name'], 0, 50);
// Make sure this form value doesn't already exist
$formExists = ($status > 0) ? isset($Proj->forms_temp[$_POST['form_name']]) : isset($Proj->forms[$_POST['form_name']]);
while ($formExists) {
	// Append random value to form_name to prevent duplication
	$_POST['form_name'] .= "_" . substr(md5(rand()), 0, 4);
	// Make sure it's less than 64 characters long
	$_POST['form_name'] = substr($_POST['form_name'], 0, 50);
	// Try again
	$formExists = ($status > 0) ? isset($Proj->forms_temp[$_POST['form_name']]) : isset($Proj->forms[$_POST['form_name']]);
}



// If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";

// Get position of previous form's Form Status field
$sql = "select max(field_order) from $metadata_table 
		where project_id = $project_id and form_name = '".prep($_POST['after_form'])."'";
$q = db_query($sql);	
if (!db_num_rows($q)) exit('0');
// Add a 0.1 to the previous form status field's field order to set it right after it (ProjectAttributes class will fix ordering automatically)
$new_field_order = db_result($q, 0) + 0.1;

// Add the Form Status field
$sql = "insert into $metadata_table (project_id, field_name, form_name, form_menu_description, field_order, element_type, 
		element_label, element_enum, element_preceding_header) values ($project_id, '{$_POST['form_name']}_complete', 
		'{$_POST['form_name']}', '".prep($_POST['label'])."', '$new_field_order', 'select', 'Complete?', 
		'0, Incomplete \\\\n 1, Unverified \\\\n 2, Complete', 'Form Status')";
$q = db_query($sql);	
// Logging
if ($q) log_event($sql,$metadata_table,"MANAGE",$_POST['form_name'],"form_name = '{$_POST['form_name']}'","Create data collection instrument");

// Only if in Development...
if ($status == 0) {
	// Grant all users full access rights (default) to the new form
	$sql = "update redcap_user_rights set data_entry = concat(data_entry,'[{$_POST['form_name']},1]') where project_id = $project_id";
	db_query($sql);
	// Add new forms to events_forms table ONLY if not longitudinal (if longitudinal, user will designate form for events later)
	if (!$longitudinal) {
		$sql = "insert into redcap_events_forms (event_id, form_name) select e.event_id, '{$_POST['form_name']}' from redcap_events_metadata e, 
				redcap_events_arms a where a.arm_id = e.arm_id and a.project_id = $project_id limit 1";
		db_query($sql);
	}
}

print '1';