<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once (APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php');

// If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
// Determine if adding to very bottom of table or not
$is_last = ($_POST['this_sq_id'] == "") ? 1 : 0;
// Determine if editing an existing question or not
$edit_question = ($_POST['sq_id'] == "") ? 0 : 1;
// Determine if a section header rather than a real field
$is_section_header = ($_POST['field_type'] == "section_header") ? 1 : 0;
// Determine if WAS a section header but was changed to a real field
$was_section_header = (!$is_section_header && isset($_POST['wasSectionHeader']) && $_POST['wasSectionHeader']);
// Default for table row in DOM that should be deleted
$delete_row = "";
// Required Field value
$_POST['field_req'] = ($_POST['field_req'] == "") ? 0 : $_POST['field_req'];
// Edoc_id value
$_POST['edoc_id'] = ($_POST['field_type'] == 'descriptive' && is_numeric($_POST['edoc_id'])) ? $_POST['edoc_id'] : "";
// Edoc image/file attachment display value
$_POST['edoc_display_img'] = ($_POST['field_type'] == 'descriptive' && is_numeric($_POST['edoc_id']) && is_numeric($_POST['edoc_display_img'])) ? $_POST['edoc_display_img'] : 0;
// Check custom alignment
$align_options = array('', 'LV', 'LH', 'RV', 'RH');
$_POST['custom_alignment'] = (isset($_POST['custom_alignment']) && $_POST['field_type'] != 'descriptive' && $_POST['field_type'] != 'section_header' && in_array($_POST['custom_alignment'], $align_options)) ? $_POST['custom_alignment'] : "";
// If field_type is missing, then set as Text field
if (!isset($_POST['field_type']) || (isset($_POST['field_type']) && $_POST['field_type'] == "")) {
	$_POST['field_type'] = 'text';
}
// If a text field with any kind of date validation with min/max range set, reformat min/max data value to YMD format when saving
if ($_POST['field_type'] == "text" && (substr($_POST['val_type'], -4) == "_mdy" || substr($_POST['val_type'], -4) == "_dmy"))
{
	// Check validation min
	if ($_POST['val_min'] != "") {
		// If has time component, remove it temporarily to convert the date separately
		$this_date = $_POST['val_min'];
		$this_time = "";
		if (substr($_POST['val_type'], 0, 8) == "datetime") {
			list ($this_date, $this_time) = explode(" ", $_POST['val_min']);
		}		
		if (substr($_POST['val_type'], -4) == "_mdy") {
			$_POST['val_min'] = trim(date_mdy2ymd($this_date) . " " . $this_time);
		} else {
			$_POST['val_min'] = trim(date_dmy2ymd($this_date) . " " . $this_time);
		}
	}
	// Check validation max
	if ($_POST['val_max'] != "") {
		// If has time component, remove it temporarily to convert the date separately
		$this_date = $_POST['val_max'];
		$this_time = "";
		if (substr($_POST['val_type'], 0, 8) == "datetime") {
			list ($this_date, $this_time) = explode(" ", $_POST['val_max']);
		}		
		if (substr($_POST['val_type'], -4) == "_mdy") {
			$_POST['val_max'] = trim(date_mdy2ymd($this_date) . " " . $this_time);
		} else {
			$_POST['val_max'] = trim(date_dmy2ymd($this_date) . " " . $this_time);
		}
	}
}


// SQL Field: Do extra server-side check to ensure that only super users can add/edit "sql" field types
if ($_POST['field_type'] == 'sql' && !$super_user)
{
	// Send back JS error msg
	print  "<script type='text/javascript'>
			window.top.window.alert('".cleanHtml($lang['design_272'])."');
			window.top.window.location.reload();
			</script>";
	exit;
}


// Set slider labels as val type, min, and max
if ($_POST['field_type'] == "slider") {
	$_POST['slider_label_left']   = trim($_POST['slider_label_left']);
	$_POST['slider_label_middle'] = trim($_POST['slider_label_middle']);
	$_POST['slider_label_right']  = trim($_POST['slider_label_right']);
	// Determine how to delimit the enum string
	$_POST['element_enum'] = $_POST['slider_label_left'];
	if (!empty($_POST['slider_label_middle'])) {
		$_POST['element_enum'] .= " | " . $_POST['slider_label_middle'];
	}
	if (!empty($_POST['slider_label_right'])) {
		$_POST['element_enum'] .= " | " . $_POST['slider_label_right'];
	}
	// Set slider display value
	$_POST['val_type'] = ($_POST['slider_display_value'] == 'on') ? 'number' : '';
	// Set defaults for min/max as blank
	$_POST['val_min']  = "";
	$_POST['val_max']  = "";
}
// Make sure only text fields and sliders have any validation/slider labels (could get left over when changing field type)
elseif ($_POST['field_type'] != "text" && $_POST['field_type'] != "slider")
{
	$_POST['val_type'] = "";
	$_POST['val_min']  = "";
	$_POST['val_max']  = "";
}
// Make sure we restore legacy validation values when saving
elseif ($_POST['field_type'] == "text")
{
	if ($_POST['val_type'] == "number") {
		$_POST['val_type'] = "float";
	} elseif ($_POST['val_type'] == "integer") {
		$_POST['val_type'] = "int";
	}
}

// If using matrix formatting for a radio or checkbox, capture the grid_name
$grid_name = "";
if (($_POST['field_type'] == "checkbox" || $_POST['field_type'] == "radio") && 
	((isset($_POST['grid_name_dd']) && $_POST['grid_name_dd'] != '') || (isset($_POST['grid_name_text']) && $_POST['grid_name_text'] != '')))
{
	$grid_name = ($_POST['grid_name_dd'] != '') ? $_POST['grid_name_dd'] : $_POST['grid_name_text'];
	// Ensure that only specified charcters are allowed
	if ($grid_name == "" || $grid_name == cleanHtml($lang['design_297']) || !preg_match("/^[a-z0-9_]+$/", $grid_name)) {
		$grid_name = "";
	}
}






 

/**
 * EDITING EXISTING QUESTION
 * If was a Section Header but it being converted to a real field, then simply ADD as a new field
 */
if ($edit_question && !$was_section_header) 
{
		
	// Set associated values for query
	$element_validation_checktype = ($_POST['field_type'] == "text") ? "soft_typed" : "";
	
	// Parse multiple choices
	if ($_POST['element_enum'] != "" && ($_POST['field_type'] == "checkbox" || $_POST['field_type'] == "advcheckbox" || $_POST['field_type'] == "radio" || $_POST['field_type'] == "select")) {
		$_POST['element_enum'] = autoCodeEnum($_POST['element_enum']);
	// Clean calc field equation
	} elseif ($_POST['field_type'] == "calc") {
		$_POST['element_enum'] = html_entity_decode(trim(str_replace($br_orig, $br_repl, $_POST['element_enum'])), ENT_QUOTES);
	// Ensure that most fields do not have a "select choice" value
	} elseif (in_array($_POST['field_type'], array("text", "textarea", "notes", "file", "yesno", "truefalse"))) {
		$_POST['element_enum'] = "";
	}
	
	// Edit field's section header
	if ($is_section_header) 
	{
		// If user is changing a field into a section header, delete actual field and move section header down one field in metadata table.
		if (isset($_POST['field_name']) && $_POST['field_name'] != "") 
		{
			// See if a section header already exists for the field. If so, append new value onto it and move down one field
			$sql = "select field_order, element_preceding_header from $metadata_table where project_id = $project_id 
					and field_name = '{$_POST['field_name']}' limit 1";
			$q = db_query($sql);
			$sh_existing1 = db_result($q, 0, "element_preceding_header");
			$forder_existing1 = db_result($q, 0, "field_order");
			// See if section header exists for the succeeding field
			$sql = "select field_name, element_preceding_header from $metadata_table where project_id = $project_id 
					and field_order > $forder_existing1 order by field_order limit 1";
			$q = db_query($sql);
			$sh_existing2 = db_result($q, 0, "element_preceding_header");
			$fieldname_existing2 = db_result($q, 0, "field_name");
			// Append other section header values onto submitted one
			if ($sh_existing1 != "") $_POST['field_label']  = $sh_existing1 . "<br><br>" . $_POST['field_label'];
			if ($sh_existing2 != "") $_POST['field_label'] .= "<br><br>" . $sh_existing2;
			// Move section header to succeeding field
			$sql = "update $metadata_table set element_preceding_header = " . checkNull($_POST['field_label']) . " 
					where project_id = $project_id and field_name = '$fieldname_existing2'";
			db_query($sql);
			// Delete current field and reduce field_order of following fields
			db_query("delete from $metadata_table where project_id = $project_id and field_name = '{$_POST['field_name']}'");
			db_query("update $metadata_table set field_order = field_order - 1 where project_id = $project_id and field_order > $forder_existing1");
			## FORM MENU: Always make sure the form_menu_description value stays only with first field on form
			// Set all field's form_menu_description as NULL
			$sql = "update $metadata_table set form_menu_description = NULL where project_id = $project_id and form_name = '{$_POST['form_name']}'";
			db_query($sql);	
			// Now set form_menu_description for first field		
			$sql = "update $metadata_table set form_menu_description = '".prep(label_decode($Proj->forms[$_POST['form_name']]['menu']))."' 
					where project_id = $project_id and form_name = '{$_POST['form_name']}' order by field_order limit 1";
			db_query($sql);
			//print "<script type='text/javascript'>alert('".cleanHTML($sql)."');</script>";
			// Run javascript to reload table
			print  "<script type='text/javascript'>
					window.top.window.reloadDesignTable('{$_POST['form_name']}');
					</script>";
			exit;
			
		} 		
		// Modify section header normally
		else {
			$sql = "update $metadata_table set "
				 . "element_preceding_header = " . checkNull($_POST['field_label']) . " "
				 . "where project_id = $project_id and field_name = '{$_POST['sq_id']}'";
		}
	
	}
	// Edit field itself
	else 
	{
		// CHECK IF NEED TO DELETE EDOC: If edoc_id is blank then set as "deleted" in edocs_metadata table (development only OR if added then deleted in Draft Mode)
		// Get current edoc_id
		$q = db_query("select edoc_id from $metadata_table where project_id = $project_id and field_name = '{$_POST['sq_id']}' limit 1");
		$current_edoc_id = db_result($q, 0);
		if (empty($_POST['edoc_id']) || $current_edoc_id != $_POST['edoc_id'])
		{
			deleteEdoc($_POST['sq_id']);
		}
		
		// Update field
		$sql = "update $metadata_table set "
			 . "field_name = '{$_POST['field_name']}', "
			 . "element_label = " . checkNull($_POST['field_label']) . ", "
			 . "field_req = '{$_POST['field_req']}', "
			 . "field_phi = " . checkNull($_POST['field_phi']) . ", "
			 . "element_note = " . checkNull($_POST['field_note']) . ", "
			 . "element_type = '{$_POST['field_type']}', "
			 . "element_validation_type = " . checkNull($_POST['val_type']) . ", "
			 . "element_validation_checktype = " . checkNull($element_validation_checktype) . ", "
			 . "element_enum = " . checkNull($_POST['element_enum']) . ", "
			 . "element_validation_min = " . checkNull($_POST['val_min']) . ", "
			 . "element_validation_max = " . checkNull($_POST['val_max']) . ", "
			 . "edoc_id = " . checkNull($_POST['edoc_id']) . ", "
			 . "edoc_display_img = {$_POST['edoc_display_img']}, "
			 . "custom_alignment = " . checkNull($_POST['custom_alignment']) . ", "
			 . "question_num = " . checkNull($_POST['question_num']) . ", "
			 . "grid_name = " . checkNull($grid_name) . " "
			 . "where project_id = $project_id and field_name = '{$_POST['sq_id']}'";
			 
	}
	$q = db_query($sql);
	//print "<script type='text/javascript'>alert('".cleanHTML($sql)."');</script>";
	
	// Logging
	if ($q) log_event($sql,$metadata_table,"MANAGE",$_POST['field_name'],"field_name = '{$_POST['field_name']}'","Edit project field");


/**
 * ADDING NEW QUESTION
 */
} else {
	
	// Reformat value if adding field directly above a Section Header (i.e. ends with "-sh")
	if (substr($_POST['this_sq_id'], -3) == "-sh") {
		$_POST['this_sq_id'] = substr($_POST['this_sq_id'], 0, -3);
		$possible_sh_attached = false;
	} else {
		// Set flag and check later if field directly below has a Section Header (i.e. are we adding a field "between" a SH and a field?)
		$possible_sh_attached = true;	
	}
	
	## Section Headers ONLY
	if ($is_section_header) {
		
		// Prevent user from adding section header as last field
		if ($_POST['this_sq_id'] == "") {
			exit("<script type='text/javascript'>
				  window.top.window.resetAddQuesForm();
				  window.top.window.alert('".remBr($lang['design_201'])."');
				  </script>");
		}	
		
		// Update field
		$sql = "update $metadata_table set element_preceding_header = " . checkNull($_POST['field_label']) . " where project_id = $project_id "
			 . "and field_name = '{$_POST['this_sq_id']}'";
		$q = db_query($sql);
		// Set field name of field its attached to
		$_POST['field_name'] = $_POST['this_sq_id'];
		// Logging
		if ($q) log_event($sql,$metadata_table,"MANAGE",$last_field,"field_name = '$last_field'","Edit project field");

	## All field types (except section headers)
	} else {
		
		// Check new form_name value to see if it already exists. If so, unset the value to mimic field-adding behavior for an existing form.
		if (isset($_POST['add_form_name'])) {
			$formExists = db_result(db_query("select count(1) from $metadata_table where project_id = $project_id 
					and form_name = '".prep($_POST['form_name'])."' limit 1"), 0);
			if ($formExists) {
				unset($_POST['add_form_name']);
			}
		}		
		
		// Creating new form or editing existing?
		if (isset($_POST['add_form_name']) && isset($_POST['add_before_after'])) {
			// NEW FORM being added
			$form_menu_description = "'" . prep(strip_tags(label_decode($_POST['add_form_name']))) . "'";
			if ($_POST['add_before_after']) {
				// Place after selected form
				$sql = "select max(field_order)+1 from $metadata_table where project_id = $project_id and form_name = '".prep($_POST['add_form_place'])."'";	
			} elseif (!$_POST['add_before_after']) {
				// Place before selected form
				$sql = "select min(field_order) from $metadata_table where project_id = $project_id and form_name = '".prep($_POST['add_form_place'])."'";	
			}			
		} else {
			// EXISTING FORM
			$form_menu_description = "NULL";
			// Determine if adding to very bottom of table or not. If so, get position of last field on form + 1
			if ($is_last) {
				$sql = "select max(field_order) from $metadata_table where project_id = $project_id and form_name = '{$_POST['form_name']}'";
			// Obtain the destination field's field_order value (i.e. field_order of field that will be located after this new one)
			} else {
				$sql = "select field_order from $metadata_table where project_id = $project_id and field_name = '{$_POST['this_sq_id']}' limit 1";
			}
		}		
		// Get the following question's field order
		$new_field_order = db_result(db_query($sql), 0);
		// Increment added to all fields occurring after this new one. If creating a new form, also add extra increment 
		// number for field_order to give extra room for the Form Status field created
		$increase_field_order = isset($_POST['add_form_name']) ? 2 : 1;
		
		// Increase field_order of all fields after this new one
		db_query("update $metadata_table set field_order = field_order + $increase_field_order where project_id = $project_id and field_order >= $new_field_order");
		// Set associated values for query
		$element_validation_checktype = "";
		if ($_POST['field_type'] == "text") {
			$element_validation_checktype = "soft_typed";
		// Parse multiple choices
		} elseif ($_POST['element_enum'] != "" && ($_POST['field_type'] == "checkbox" || $_POST['field_type'] == "advcheckbox" || $_POST['field_type'] == "radio" || $_POST['field_type'] == "select")) {
			$_POST['element_enum'] = autoCodeEnum($_POST['element_enum']);
		// Clean calc field equation (and for "sql" field types also)
		} elseif ($_POST['element_enum'] != "") {
			$_POST['element_enum'] = html_entity_decode(trim(str_replace($br_orig, $br_repl, $_POST['element_enum'])), ENT_QUOTES);
		}
		// Query to create new field
		$sql = "insert into $metadata_table values ($project_id, '".prep($_POST['field_name'])."', " . checkNull($_POST['field_phi']) . ", " 
			 . "'{$_POST['form_name']}', $form_menu_description, '$new_field_order', NULL, NULL, '{$_POST['field_type']}', " 
			 . checkNull($_POST['field_label']) . ", " 
			 . checkNull($_POST['element_enum']) . ", " 
			 . checkNull($_POST['field_note']) . ", " 
			 . checkNull($_POST['val_type']) . ", "
			 . checkNull($_POST['val_min']) . ", "
			 . checkNull($_POST['val_max']) . ", "
			 . checkNull($element_validation_checktype) . ", " 
			 . "NULL, "
			 . "'{$_POST['field_req']}', "
			 . checkNull($_POST['edoc_id']) . ", "
			 . $_POST['edoc_display_img'] . ", " 
			 . checkNull($_POST['custom_alignment']) . ", "
			 . "NULL, "
			 . checkNull($_POST['question_num']) . ", "
			 . checkNull($grid_name) . ", "
			 . "NULL" . ")";
		$q = db_query($sql);	
		// print "<script type='text/javascript'>alert('".cleanHTML($sql)."');</script>";
		// Logging
		if ($q) {
			log_event($sql,$metadata_table,"MANAGE",$_POST['field_name'],"field_name = '{$_POST['field_name']}'","Create project field");
		} else {
			// UNDO previous "reorder" query: Decrease field_order of all fields after where this new one should've gone
			db_query("update $metadata_table set field_order = field_order - $increase_field_order 
						 where project_id = $project_id and field_order >= ".($new_field_order + $increase_field_order));
			// If field failed to save, then give error msg and reload form completely
			print  "<script type='text/javascript'>
					window.top.window.alert(window.top.window.woops);
					window.top.window.reloadDesignTable('{$_POST['form_name']}');
					</script>";
			exit;
		}
		
		// If creating a new form, also add Form Status field
		if (isset($_POST['add_form_name'])) 
		{
			// Add the Form Status field
			$sql = "insert into $metadata_table (project_id, field_name, form_name, field_order, element_type, 
					element_label, element_enum, element_preceding_header) values ($project_id, '{$_POST['form_name']}_complete', 
					'{$_POST['form_name']}', '".($new_field_order+1)."', 'select', 'Complete?', 
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
		
		// NOT adding a new form, so deal with some logic and placement issues
		} else {
			
			## SECTION HEADER PLACEMENT
			// Check if we are adding a field "between" a SH and a field? If so, move SH to new field from one directly after it.
			if ($possible_sh_attached && !$is_last) 
			{
				$sql = "select element_preceding_header from $metadata_table where project_id = $project_id and form_name = '{$_POST['form_name']}' 
						and field_order = (select field_order+1 from $metadata_table where project_id = $project_id 
						and field_name = '{$_POST['field_name']}' limit 1) and element_preceding_header is not null limit 1";
				$q = db_query($sql);
				if (db_num_rows($q) > 0) {
					// Yes, we are adding a field "between" a SH and a field. Move the SH to the field we just created.
					$sh_value = db_result($q, 0);
					// If changed a SH to a real field, then don't reattach the SH, but instead set to null
					if ($was_section_header) {
						$sh_value = "";
					}
					$sql = "update $metadata_table set element_preceding_header = " . checkNull($sh_value) . " where project_id = $project_id 
							and field_name = '{$_POST['field_name']}' limit 1";
					$q = db_query($sql);
					// Get name of field directly after the new one we created.
					$sql = "select field_name from $metadata_table where project_id = $project_id and form_name = '{$_POST['form_name']}' 
							and field_order = ".($new_field_order+1)." limit 1";
					$following_field = db_result(db_query($sql), 0);
					// Set SH value from other field to NULL now that we have copied it to new field
					$sql = "update $metadata_table set element_preceding_header = NULL where project_id = $project_id and field_name = '$following_field' limit 1";
					$q = db_query($sql);
					// Set value for row in table to be deleted in DOM (delete section header on following field, which is now null)
					$delete_row = $following_field . "-sh";
					// Reload form completely in order to associate section header with newly added field below it
					print  "<script type='text/javascript'>
							window.top.window.reloadDesignTable('{$_POST['form_name']}');
							</script>";
					exit;
				}
			}
		
			## FORM MENU: Always make sure the form_menu_description value stays only with first field on form
			// Set all field's form_menu_description as NULL
			$sql = "update $metadata_table set form_menu_description = NULL where project_id = $project_id and form_name = '{$_POST['form_name']}'";
			db_query($sql);	
			// Now set form_menu_description for first field		
			$sql = "update $metadata_table set form_menu_description = '".prep(label_decode($Proj->forms[$_POST['form_name']]['menu']))."' 
					where project_id = $project_id and form_name = '{$_POST['form_name']}' order by field_order limit 1";
			db_query($sql);
		
		}

	}

	
}



/* 
print  "<script type='text/javascript'>window.top.window.alert('";
foreach ($_POST as $key=>$value) { print "$key=>$value, "; }
print  "');</script>";
exit;
*/

 
// RELOAD DESIGN TABLE if fields are not in proper order 
// OR if matrix field was add/edited. 
// OR if edited the Primary Key field.
// If not, reload table on page, else do insertRow into table.
if (($_POST['field_name'] == $table_pk) || $grid_name != "" || $Proj->checkReorderFields($metadata_table))
{
	// Reload form completely in order to associate section header with newly added field below it
	print  "<script type='text/javascript'>
			window.top.window.reloadDesignTable('{$_POST['form_name']}');
			</script>";
}
// Reload whole page if Primary Key field's variable name was modified
elseif ($_POST['sq_id'] == $table_pk && $_POST['sq_id'] != $_POST['field_name'])
{
	print  "<script type='text/javascript'>
			window.top.window.showProgress(1);
			window.top.window.location.href = window.top.window.app_path_webroot+window.top.window.page+'?pid='+window.top.window.pid+'&page='+window.top.window.getParameterByName('page');
			</script>";
}
// Insert new row into table and close "Add/Edit Field" dialog pop-up
else
{
	print  "<script type='text/javascript'>
			window.top.window.insertRow('draggable', '{$_POST['field_name']}', $edit_question, $is_last, 0, $is_section_header, '$delete_row');
			</script>";
}

// Check if the table_pk has changed during this script. If so, give user a prompt alert.
if (Design::recordIdFieldChanged()) 
{
	print  "<script type='text/javascript'>
			window.top.window.update_pk_msg(false,'field');
			</script>";
}


// SURVEY QUESTION NUMBERING (DEV ONLY): Detect if form is a survey, and if so, if has any branching logic. If so, disable question auto numbering.
if (Design::checkDisableSurveyQuesAutoNum($_POST['form_name']))
{
	// Give user a prompt as notice of this change
	print  "<script type='text/javascript'>
			setTimeout(function(){
				window.top.window.alert(window.top.window.disabledAutoQuesNumMsg);
			},300);
			</script>";
}
