<?php

// Config for non-project pages
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
require_once APP_PATH_CLASSES . 'Message.php';

// Remove any HTML in the title
$_POST['app_title'] = htmlspecialchars(filter_tags(html_entity_decode($_POST['app_title'], ENT_QUOTES)), ENT_QUOTES);


/**
 * Create $new_app_name variable derived from Project Title (and check for duplication with existing projects)
 */
$new_app_name = preg_replace("/[^a-z0-9_]/", "", str_replace(" ", "_", strtolower(html_entity_decode($_POST['app_title'], ENT_QUOTES))));
// Remove any double underscores, beginning numerals, and beginning/ending underscores
while (strpos($new_app_name, "__") !== false) 	$new_app_name = str_replace("__", "_", $new_app_name);
while (substr($new_app_name, 0, 1) == "_") 		$new_app_name = substr($new_app_name, 1);
while (substr($new_app_name, -1) == "_") 		$new_app_name = substr($new_app_name, 0, -1);
while (is_numeric(substr($new_app_name, 0, 1))) $new_app_name = substr($new_app_name, 1);
// If longer than 50 characters, then shorten it to that length
if (strlen($new_app_name) > 50) $new_app_name = substr($new_app_name, 0, 50);
// Check to make sure the current value isn't already a project title. If it is, append 4 random alphanums and do over.
$appExists = db_result(db_query("select count(1) from redcap_projects where project_name = '$new_app_name'"), 0);
var_dump($appExists);
while ($appExists) {
	if (strlen($new_app_name) > 46) {
		$new_app_name = substr($new_app_name, 0, 46);
	}
	$new_app_name .= substr(md5(rand()), 0, 4);
	// Check again if still exists
	$appExists = db_result(db_query("select count(1) from redcap_projects where project_name = '$new_app_name'"), 0);
}
// If somehow still blank, assign random alphanum as app_name
if ($new_app_name == "") {
	$new_app_name = substr(md5(rand()), 0, 10);
}


// Catch if user selected multiple Research options for Purpose
if (isset($_POST['purpose_other']) && is_array($_POST['purpose_other'])) {
	$_POST['purpose_other'] = implode(",", $_POST['purpose_other']);
} else {
	$_POST['purpose_other'] = isset($_POST['purpose_other']) ? $_POST['purpose_other'] : '';
}
// Make sure other parameters were set properly
$_POST['repeatforms'] = (isset($_POST['repeatforms']) && ($_POST['repeatforms'] == '1' || $_POST['repeatforms'] == '0')) ? $_POST['repeatforms'] : 0;
$_POST['purpose'] = (isset($_POST['purpose']) && is_numeric($_POST['purpose'])) ? $_POST['purpose'] : "NULL";
$_POST['scheduling'] = (isset($_POST['scheduling']) && ($_POST['scheduling'] == '1' || $_POST['scheduling'] == '0')) ? $_POST['scheduling'] : 0;
$_POST['surveys_enabled'] = (isset($_POST['surveys_enabled']) && is_numeric($_POST['surveys_enabled'])) ? $_POST['surveys_enabled'] : 0;
$_POST['randomization'] = (isset($_POST['randomization']) && ($_POST['randomization'] == '1' || $_POST['randomization'] == '0')) ? $_POST['randomization'] : 0;
// Enabled auto-numbering by default for projects with survey as first form
$auto_inc_set = ($_POST['surveys_enabled'] == 0) ? 0 : 1;

// Set flag if creating the project from a template
$isTemplate = (isset($_POST['copyof']) && is_numeric($_POST['copyof']) && isset($_POST['project_template_radio']) && $_POST['project_template_radio'] == '1');




/**
 * Insert defaults and user-defined values for this new project
 */
// Insert into redcap_projects table
$sql = "insert into redcap_projects (project_name, scheduling, repeatforms, purpose, purpose_other, app_title, creation_time, created_by, 
		project_pi_firstname, project_pi_mi, project_pi_lastname, project_pi_email, project_pi_alias, project_pi_username, project_irb_number, 
		project_grant_number, surveys_enabled, auto_inc_set, randomization, auth_meth, template_id) values 
		('$new_app_name', {$_POST['scheduling']}, {$_POST['repeatforms']}, {$_POST['purpose']}, 
		" . checkNull($_POST['purpose_other']) . ", 
		'".prep($_POST['app_title'])."', '".NOW."', (select ui_id from redcap_user_information where username = '$userid' limit 1), 
		" . ((!isset($_POST['project_pi_firstname']) || $_POST['project_pi_firstname'] == "") ? "NULL" : "'".prep($_POST['project_pi_firstname'])."'") . ",  
		" . ((!isset($_POST['project_pi_mi']) || $_POST['project_pi_mi'] == "") ? "NULL" : "'".prep($_POST['project_pi_mi'])."'") . ",  
		" . ((!isset($_POST['project_pi_lastname']) || $_POST['project_pi_lastname'] == "") ? "NULL" : "'".prep($_POST['project_pi_lastname'])."'") . ",  
		" . ((!isset($_POST['project_pi_email']) || $_POST['project_pi_email'] == "") ? "NULL" : "'".prep($_POST['project_pi_email'])."'") . ",  
		" . ((!isset($_POST['project_pi_alias']) || $_POST['project_pi_alias'] == "") ? "NULL" : "'".prep($_POST['project_pi_alias'])."'") . ",  
		" . ((!isset($_POST['project_pi_username']) || $_POST['project_pi_username'] == "") ? "NULL" : "'".prep($_POST['project_pi_username'])."'") . ", 
		" . ((!isset($_POST['project_irb_number']) || $_POST['project_irb_number'] == "") ? "NULL" : "'".prep($_POST['project_irb_number'])."'") . ",  
		" . ((!isset($_POST['project_grant_number']) || $_POST['project_grant_number'] == "") ? "NULL" : "'".prep($_POST['project_grant_number'])."'") . ",
		{$_POST['surveys_enabled']}, $auto_inc_set, {$_POST['randomization']}, '".prep($auth_meth_global)."', ".($isTemplate ? $_POST['copyof'] : "null").")";
$q = db_query($sql);
if (!$q || db_affected_rows() != 1) {
	print db_error();
	queryFail($sql);
}
// Get this new project's project_id
$new_project_id = db_insert_id();
define("PROJECT_ID", $new_project_id);
// Get listing of field names from redcap_projects table to use for inserting project defaults
$rp_fields = array();
$q = db_query("SHOW COLUMNS FROM redcap_projects");
while ($row = db_fetch_assoc($q)) {
	$rp_fields[] = $row['Field'];
}
// Insert project defaults into redcap_projects
$q = db_query("select * from redcap_config");
while ($row = db_fetch_assoc($q)) {
	// If config field is a field in redcap_projects table, then update redcap_projects' value
	if (in_array($row['field_name'], $rp_fields)) {
		$sql = "update redcap_projects set {$row['field_name']} = '" . prep($row['value']) . "' where project_id = $new_project_id";
		$q2 = db_query($sql);
		if (!$q2 && $super_user) queryFail($sql);
	}
}


/**
 * COPYING PROJECT OR CREATING NEW PROJECT USING TEMPLATE
 */
## If copying an existing project
if (isset($_POST['copyof']) && is_numeric($_POST['copyof'])) 
{
	// Message flag used for dialog pop-up
	$msg_flag = ($isTemplate) ? "newproject" : "copiedproject";
	
	// Verify project_id of original
	$q = db_query("select 1 from redcap_projects where project_id = {$_POST['copyof']} limit 1");
	if (!$q || db_num_rows($q) < 1) exit("ERROR!");
	$copyof_project_id = $_POST['copyof'];
	
	// Copy some defined project-level values from the project being copied
	$projectFieldsCopy = array( "repeatforms", "scheduling", "randomization", "surveys_enabled", 
								"display_today_now_button", "auto_inc_set", "require_change_reason", "secondary_pk",
								"history_widget_enabled", "order_id_by", "custom_record_label", "enable_participant_identifiers",
								"survey_email_participant_field");
	// Retrieve field values from project being copied and update newly created project
	$sql = "select " . implode(", ", $projectFieldsCopy) . " from redcap_projects where project_id = $copyof_project_id";
	$q = db_query($sql);
	$row = db_fetch_assoc($q);
	$updateVals = array();
	foreach ($projectFieldsCopy as $this_field)
	{
		// If users are not allowed to create surveys (global setting), then set surveys_enabled = 0
		if (!$enable_projecttype_singlesurveyforms && $this_field == "surveys_enabled") {
			$row[$this_field] = '0';
		}
		// Set field and value for query
		$updateVals[] = $this_field . " = '" . prep(label_decode($row[$this_field])) . "'";
	}
	db_query("update redcap_projects set " . implode(", ", $updateVals) . " where project_id = $new_project_id");
	
	// Set randomization flag for project
	$randomization = (isset($row['randomization'])) ? $row['randomization'] : 0;;
	
	// Copy metadata fields
	$sql = "insert into redcap_metadata select '$new_project_id', field_name, field_phi, form_name, form_menu_description, field_order, 
			field_units, element_preceding_header, element_type, element_label, element_enum, element_note, element_validation_type, 
			element_validation_min, element_validation_max, element_validation_checktype, branching_logic, field_req, NULL, 
			edoc_display_img, custom_alignment, stop_actions, question_num, grid_name, misc from redcap_metadata where project_id = $copyof_project_id";
	$q = db_query($sql);
	
	## CHECK FOR EDOC FILE ATTACHMENTS: Copy all files on the server, if being used (one at a time)
	$sql = "select field_name, edoc_id from redcap_metadata where project_id = $copyof_project_id and edoc_id is not null";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{	
		// Copy file on server
		$new_edoc_id = copyFile($row['edoc_id'], $new_project_id);
		if (is_numeric($new_edoc_id))
		{
			// Now update new field's edoc_id value
			$sql = "update redcap_metadata set edoc_id = $new_edoc_id where project_id = $new_project_id and field_name = '{$row['field_name']}'";
			db_query($sql);
		}
	}
	
	// Copy arms/events (one event at a time)
	$eventid_translate = array(); // Store old event_id as key and new event_id as value
	$q = db_query("select arm_id, arm_num, arm_name from redcap_events_arms where project_id = $copyof_project_id");
	while ($row = db_fetch_assoc($q)) {	
		// Copy arm
		db_query("insert into redcap_events_arms (project_id, arm_num, arm_name) values ($new_project_id, {$row['arm_num']}, '".prep($row['arm_name'])."')");
		$this_arm_id = db_insert_id();		
		$q2 = db_query("select * from redcap_events_metadata where arm_id = {$row['arm_id']}");
		while ($row2 = db_fetch_assoc($q2)) 
		{
			// Copy event
			db_query("insert into redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) values 
						 ($this_arm_id, {$row2['day_offset']}, {$row2['offset_min']}, {$row2['offset_max']}, '".prep($row2['descrip'])."')");
			$this_event_id = db_insert_id();	
			// Get old event_id of copied project and translate to new equivalent event_id for new project
			$eventid_translate[$row2['event_id']] = $this_event_id;
			// Copy events-forms matching
			db_query("insert into redcap_events_forms (event_id, form_name) select '$this_event_id', form_name from redcap_events_forms where event_id = {$row2['event_id']}");
		}
	}
	
	// Copy any Shared Library instrument mappings
	$sql = "insert into redcap_library_map (project_id, form_name, `type`, library_id, upload_timestamp, acknowledgement, acknowledgement_cache) 
			select '$new_project_id', form_name, `type`, library_id, upload_timestamp, acknowledgement, acknowledgement_cache 
			from redcap_library_map where project_id = $copyof_project_id";
	$q = db_query($sql);
	
	// Copy any surveys
	$sql = "select * from redcap_surveys where project_id = $copyof_project_id";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		$sql = "insert into redcap_surveys (project_id, form_name, title, instructions, acknowledgement, question_by_section, 
				question_auto_numbering, survey_enabled, save_and_return, hide_title, view_results, min_responses_view_results, check_diversity_view_results,
				end_survey_redirect_url, end_survey_redirect_url_append_id) values 
				($new_project_id, ".checkNull($row['form_name']).", ".checkNull($row['title']).", ".checkNull($row['instructions']).", 
				".checkNull($row['acknowledgement']).", {$row['question_by_section']}, 
				{$row['question_auto_numbering']}, 1, {$row['save_and_return']}, {$row['hide_title']}, {$row['view_results']}, 
				{$row['min_responses_view_results']}, {$row['check_diversity_view_results']},
				".checkNull(label_decode($row['end_survey_redirect_url'])).", ".checkNull($row['end_survey_redirect_url_append_id']).")";
		db_query($sql);
		$this_survey_id = db_insert_id();		
		// Copy the logo file and get new edoc_id
		if (!empty($row['logo']))
		{
			$edoc_id = copyFile($row['logo'], $new_project_id);
			// Add new edoc_id to surveys table for this survey
			if (!empty($edoc_id)) {
				$sql = "update redcap_surveys set logo = $edoc_id where survey_id = $this_survey_id";
				$q = db_query($sql);
			}
		}		
	}
	
	// Copy data access groups (do one at a time to grab old/new values for matching later)
	$groupid_array = array();
	$q = db_query("select * from redcap_data_access_groups where project_id = $copyof_project_id");
	while ($row = db_fetch_assoc($q)) {	
		db_query("insert into redcap_data_access_groups (project_id, group_name) values ($new_project_id, '".prep($row['group_name'])."')");
		$groupid_array[$row['group_id']] = db_insert_id();
	}
	
	## COPY REPORTS (if a template OR if desired for copy)
	if ($isTemplate || (isset($_POST['copy_reports']) && $_POST['copy_reports'] == "on")) 
	{
		$sql = "update redcap_projects c, redcap_projects o set c.report_builder = o.report_builder 
				where o.project_id = $copyof_project_id and c.project_id = $new_project_id";
		db_query($sql);
	}
	
	## COPY RECORDS (if applicable)
	if (!$isTemplate && isset($_POST['copy_records']) && $_POST['copy_records'] == "on") 
	{
		## COPY DATA: Transfer data one event at a time
		foreach ($eventid_translate as $old_event_id=>$new_event_id)
		{
			$sql = "INSERT INTO redcap_data (project_id, event_id, record, field_name, `value`) 
					select '$new_project_id', '$new_event_id', record, field_name, `value` 
					from redcap_data where project_id = $copyof_project_id and event_id = $old_event_id";
			db_query($sql);
		}		
		## COPY EDOCS: Move the "file" field type values separately (because the docs will have to be copied in the file system)
		$sql = "select distinct d.* from redcap_metadata m, redcap_data d where m.project_id = $new_project_id
				and m.project_id = d.project_id and m.field_name = d.field_name and m.element_type = 'file'";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			// Make sure edoc_id is numerical. If so, copy file. If not, fix this corrupt data and don't copy file.
			$edoc_id = $row['value'];
			// Get edoc_id of new file copy
			$new_edoc_id = (is_numeric($edoc_id)) ? copyFile($edoc_id, $new_project_id) : '';
			// Set the new edoc_id value in the redcap_data table
			$sql = "update redcap_data set value = '$new_edoc_id' where project_id = {$row['project_id']} and event_id = {$row['event_id']} 
					and record = '" . prep($row['record']) . "' and field_name = '{$row['field_name']}'";
			db_query($sql);
		}		
	}
	
	// RANDOMIZATION: If using randomization, copy the basic randomization setup (but not the allocation tables)
	$sql = "select * from redcap_randomization where project_id = $copyof_project_id";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		$sql = "insert into redcap_randomization (project_id, stratified, group_by, target_field, target_event, 
			source_field1, source_event1, source_field2, source_event2, source_field3, source_event3, source_field4, source_event4, 
			source_field5, source_event5, source_field6, source_event6, source_field7, source_event7, source_field8, source_event8, 
			source_field9, source_event9, source_field10, source_event10, source_field11, source_event11, source_field12, source_event12, 
			source_field13, source_event13, source_field14, source_event14, source_field15, source_event15)
			values ($new_project_id, ".checkNull($row['stratified']).", ".checkNull($row['group_by']).", ".checkNull($row['target_field']).", ".checkNull($eventid_translate[$row['target_event']]).", ".checkNull($row['source_field1']).", ".checkNull($eventid_translate[$row['source_event1']]).", 
			".checkNull($row['source_field2']).", ".checkNull($eventid_translate[$row['source_event2']]).", ".checkNull($row['source_field3']).", ".checkNull($eventid_translate[$row['source_event3']]).", ".checkNull($row['source_field4']).", ".checkNull($eventid_translate[$row['source_event4']]).", ".checkNull($row['source_field5']).", ".checkNull($eventid_translate[$row['source_event5']]).", 
			".checkNull($row['source_field6']).", ".checkNull($eventid_translate[$row['source_event6']]).", ".checkNull($row['source_field7']).", ".checkNull($eventid_translate[$row['source_event7']]).", ".checkNull($row['source_field8']).", ".checkNull($eventid_translate[$row['source_event8']]).", ".checkNull($row['source_field9']).", ".checkNull($eventid_translate[$row['source_event9']]).", 
			".checkNull($row['source_field10']).", ".checkNull($eventid_translate[$row['source_event10']]).", ".checkNull($row['source_field11']).", ".checkNull($eventid_translate[$row['source_event11']]).", ".checkNull($row['source_field12']).", ".checkNull($eventid_translate[$row['source_event12']]).", ".checkNull($row['source_field13']).", ".checkNull($eventid_translate[$row['source_event13']]).", 
			".checkNull($row['source_field14']).", ".checkNull($eventid_translate[$row['source_event14']]).", ".checkNull($row['source_field15']).", ".checkNull($eventid_translate[$row['source_event15']]).")";
		$q = db_query($sql);
	}
	
	// Logging	
	log_event("","redcap_projects","MANAGE",$new_project_id,"project_id = $new_project_id",($isTemplate ? "Create project" : "Copy project"));
		
	# USER RIGHTS
	// COPY USER RIGHTS (OF SINGLE USER OF ALL USERS)
	if (isset($_POST['username']) && $superusers_only_create_project && $super_user) {
		// Set username of the user requesting copy
		$single_user_copy = $_POST['username'];
	} else {
		// Set username of the user requesting copy
		$single_user_copy = $userid;
	}
	if ($isTemplate) {
		// ADD USER RIGHTS FOR CREATOR/REQUESTER ONLY (SINCE IT'S A TEMPLATE)
		$sql = "INSERT INTO redcap_user_rights (project_id, username, data_entry, design, data_quality_design, data_quality_execute,
				random_setup, random_dashboard, random_perform) 
				VALUES ($new_project_id, '$single_user_copy', '', 1, 1, 1, $randomization, $randomization, $randomization)";
		$q = db_query($sql);
	} else {
		// Copy this user (and others, if applicable)
		$sql = "insert into redcap_user_rights (project_id, username, expiration, group_id, lock_record, lock_record_multiform, data_export_tool, 
				data_import_tool, data_comparison_tool, data_logging, file_repository, double_data, user_rights, data_access_groups, graphical, 
				reports, design, calendar, data_entry, record_create, record_rename, record_delete, participants, data_quality_design, data_quality_execute,
				random_setup, random_dashboard, random_perform) 
				select '$new_project_id', username, expiration, group_id, lock_record, lock_record_multiform, data_export_tool, data_import_tool, 
				data_comparison_tool, data_logging, file_repository, double_data, user_rights, data_access_groups, graphical, reports, design, 
				calendar, data_entry, record_create, record_rename, record_delete, participants, data_quality_design, data_quality_execute,
				random_setup, random_dashboard, random_perform
				from redcap_user_rights where project_id = $copyof_project_id";
		if (isset($_POST['copy_users']) && $_POST['copy_users'] == "on") {
			// Copy all users
			$q = db_query($sql);	
		} else {
			// Only copy the current normal user
			$q = db_query($sql . " and username = '$single_user_copy'");	
		}	
		// For super users that were not originally on the project being copied, make sure they get added as well
		if ($super_user && $single_user_copy == $userid) {
			// Give default rights for everything since they're a super user and can access everything anyway
			$sql = "insert into redcap_user_rights (project_id, username) values ($new_project_id, '$userid')";
			$q = db_query($sql);
		}
		// Loop through all users and update their rights with the new group_ids
		if (count($groupid_array) > 0) {
			foreach ($groupid_array as $old_id=>$new_id) {
				db_query("update redcap_user_rights set group_id = $new_id where group_id = $old_id and project_id = $new_project_id");
			}
		}
	}
	// If user requested copy, then send user email confirmation of copy
	if (isset($_POST['username']) && $superusers_only_create_project && $super_user) {
		// Email the user requesting this db
		$email = new Message();
		$email->setFrom($project_contact_prod_changes_email);
		$email->setTo($_POST['user_email']);
		if ($isTemplate) {
			// Create project email
			$emailSubject  =   "[REDCap] {$lang['create_project_32']}";
			$emailContents =   "{$lang['create_project_33']} 
								<b>" . html_entity_decode($_POST['app_title'], ENT_QUOTES) . "</b>.<br><br>
								<a href='" . APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/ProjectSetup/index.php?pid=$new_project_id&msg=newproject'>{$lang['create_project_31']}</a>";
		} else {
			// Copy project email
			$emailSubject  =   "[REDCap] {$lang['create_project_28']}";
			$emailContents =   "{$lang['create_project_30']} 
								<b>" . html_entity_decode($_POST['app_title'], ENT_QUOTES) . "</b>.<br><br>
								<a href='" . APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/ProjectSetup/index.php?pid=$new_project_id&msg=newproject'>{$lang['create_project_31']}</a>";
		}
		$email->setBody($emailContents, true);
		$email->setSubject($emailSubject);
		$email->send();
		// Redirect super user to a confirmation page
		redirect(APP_PATH_WEBROOT_FULL . "index.php?action=approved_copy&user_email=" . $_POST['user_email']);
		exit;
	}
} 






	
/**
 * CREATING A NEW PROJECT
 */
else {
	
	// Message flag used for dialog pop-up
	$msg_flag = "newproject";

	// Give this new project an arm and an event (default)
	db_query("insert into redcap_events_arms (project_id) values ($new_project_id)");
	$new_arm_id = db_insert_id();
	db_query("insert into redcap_events_metadata (arm_id) values ($new_arm_id)");
	$new_event_id = db_insert_id();
	
	// Now add the new project's metadata
	$form_names = createMetadata($new_project_id, $_POST['surveys_enabled']);
	
	// Logging
	log_event("","redcap_projects","MANAGE",$new_project_id,"project_id = $new_project_id","Create project");
	
	## USER RIGHTS
	if (isset($_POST['username']) && $superusers_only_create_project && $super_user) {
		// Insert user rights for this new project for user REQUESTING the project
		$sql = "INSERT INTO redcap_user_rights (project_id, username, data_entry, design) VALUES 
				($new_project_id, '{$_POST['username']}', '[".implode(",1][", $form_names).",1]', 1)";
		$q = db_query($sql);
		// Email the user requesting this db
		$email = new Message();
		$email->setFrom($project_contact_prod_changes_email);
		$email->setTo($_POST['user_email']);
		$emailSubject  =   "[REDCap] {$lang['create_project_32']}";
		$emailContents =   "{$lang['create_project_33']} 
							<b>" . html_entity_decode($_POST['app_title'], ENT_QUOTES) . "</b>.<br><br>
							<a href='" . APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/ProjectSetup/index.php?pid=$new_project_id&msg=newproject'>{$lang['create_project_31']}</a>";
		$email->setBody($emailContents, true);
		$email->setSubject($emailSubject);
		$email->send();
		// Redirect super user to a confirmation page
		redirect(APP_PATH_WEBROOT_FULL . "index.php?action=approved_new&user_email=" . $_POST['user_email']);
	} else {
		// Insert user rights for this new project for user CREATING the project
		$sql = "INSERT INTO redcap_user_rights (project_id, username, data_entry, design, data_quality_design, data_quality_execute,
				random_setup, random_dashboard, random_perform) 
				VALUES ($new_project_id, '$userid', '[".implode(",1][", $form_names).",1]', 1, 1, 1, 
				{$_POST['randomization']}, {$_POST['randomization']}, {$_POST['randomization']})";
		$q = db_query($sql);
	}
	
}


// Redirect to the new project
redirect(APP_PATH_WEBROOT . "ProjectSetup/index.php?pid=$new_project_id&msg=$msg_flag");
