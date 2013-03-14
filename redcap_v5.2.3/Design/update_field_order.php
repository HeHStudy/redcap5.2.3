<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


// Default response to send back	
$response = 0;

if (isset($_POST['field_names']) && isset($_POST['form_name'])) 
{
	//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
		
	//Before modifying, get current field_order number for first field on this form
	$sql = "select field_order from $metadata_table where project_id = $project_id and form_name = '{$_POST['form_name']}' order by field_order limit 1";
	$order_num = db_result(db_query($sql), 0);
	if ($order_num == "" || $order_num < 1) $order_num = 1;
	
	//  Loop through new order of fields for this form and reorder
	$fields_array = explode(",", $_POST['field_names']);
		
	if ($_POST['section_header'] == "") {
	
		// Loop and collect new order in array
		foreach ($fields_array as $this_field) {
			// Ignore section headers during reordering
			if (substr($this_field, -3) != "-sh" && $this_field != "") {
				// Update each field with new field_order
				$q = db_query("update $metadata_table set field_order = $order_num where project_id = $project_id 
								  and field_name = '".prep($this_field)."'");
				if (!$q) {
					// Errors occurred, so undo any changes made
					db_query("ROLLBACK");
					// Send error response
					exit("0");
				}
				// Increment counter
				$order_num++;
			}
		}
		// Set order for form_status field too
		db_query("update $metadata_table set field_order = $order_num where project_id = $project_id and field_name = '{$_POST['form_name']}_complete'");
		
		// If field was moved FROM directly under a Section Header, must move Section Header value onto that field
		if (strpos($_POST['field_names'], "{$_POST['field_name']}-sh,") !== false && strpos($_POST['field_names'], "{$_POST['field_name']}-sh,{$_POST['field_name']},") === false) {
			// Get field name for attaching SH to
			$sh_pos = strpos($_POST['field_names'], "{$_POST['field_name']}-sh,") + strlen("{$_POST['field_name']}-sh,");			
			$comma_pos = strpos(substr($_POST['field_names'], $sh_pos), ",");
			$new_field_attach_sh = substr($_POST['field_names'], $sh_pos, $comma_pos);
			// Move section header to other field
			if ($new_field_attach_sh != "") {
				// Set new section header value for moved field after obtaining it first
				$sql = "select element_preceding_header from $metadata_table where project_id = $project_id and field_name = '{$_POST['field_name']}' limit 1";
				$sh_value = db_result(db_query($sql), 0);
				$sql = "update $metadata_table set element_preceding_header = '" . prep($sh_value) . "' where project_id = $project_id and field_name = '$new_field_attach_sh'";
				db_query($sql);				
				// Set old section header value to null
				$sql = "update $metadata_table set element_preceding_header = NULL where project_id = $project_id and field_name = '{$_POST['field_name']}'";
				db_query($sql);
			}			
		}
		
		// If field was moved TO directly under a Section Header, must move Section Header value onto that field
		if (strpos($_POST['field_names'], "-sh,{$_POST['field_name']},") !== false && strpos($_POST['field_names'], "{$_POST['field_name']}-sh,{$_POST['field_name']},") === false) {
			// Loop to get section header before current field
			$sh = "";
			foreach ($fields_array as $this_field) {
				if ($this_field == $_POST['field_name']) {
					$sh = substr($prev_field, 0, -3);
				}
				$prev_field = $this_field;
			}
			// Move section header to newly moved field
			if ($sh != "") {
				// Set new section header value for moved field after obtaining it first
				$sql = "select element_preceding_header from $metadata_table where project_id = $project_id and field_name = '$sh' limit 1";
				// If field moved had a SH, add it to new one if two SH's end up adjacent
				$sh_value = ((isset($sh_value) && $sh_value != "" && preg_match("/({$_POST['field_name']}-sh,)([a-z0-9_]+)(-sh,{$_POST['field_name']},)/", $_POST['field_names'])) ? "$sh_value<br><br>" : "") . db_result(db_query($sql), 0);
				$sql = "update $metadata_table set element_preceding_header = '" . prep($sh_value) . "' where project_id = $project_id and field_name = '{$_POST['field_name']}'";
				db_query($sql);
				// Set old section header value to null
				$sql = "update $metadata_table set element_preceding_header = NULL where project_id = $project_id and field_name = '$sh'";
				db_query($sql);
			}			
		}
		
		
		## Reset the form_menu_description for the first field on form (in case the first field is no longer the first field)
		Design::fixFormLabels();
		
		// Set response as successful
		$response = 1;
		
		// Check if the table_pk has changed during the recording. If so, give back different response so as to inform the user of change.
		if (Design::recordIdFieldChanged()) {
			$response = 3;
		}
		
		// Logging
		log_event("",$metadata_table,"MANAGE",$_POST['form_name'],"form_name = '{$_POST['form_name']}'","Reorder project fields");
		
	// If the "field" moved was a Section Header, move only the section header value and 
	// do logic checks to ensure no problems occur (such as 2 adjacent section headers)
	} else {
		
		// Get section header value
		$sql = "select element_preceding_header from $metadata_table where project_id = $project_id and field_name = '{$_POST['section_header']}'";
		$sh_value = db_result(db_query($sql), 0);	
		
		// Get new destination field that section header will be attached to
		$sh_dest_field = "";
		$prev_field = "";
		$double_secthdr = false;
		foreach ($fields_array as $this_field) {
			if ($prev_field == $_POST['section_header']."-sh" || $double_secthdr) {
				// If we have two section headers in a row, then iterate one more loop
				if (substr($this_field, -3) == "-sh") {
					$double_secthdr = true;
				// Found destination field!
				} else {
					$double_secthdr = false;
					$sh_dest_field = $this_field;
				}				
			}
			$prev_field = $this_field;
		}
		
		// If section header is being moved to another field...
		if ($sh_dest_field != "" && $sh_dest_field != $_POST['section_header']) 
		{
			// Set response
			$response = 1;
			// First, see if destination field already has a section header. If so, then merge the two (no other way to deal with this issue).
			$sql = "select element_preceding_header from $metadata_table where project_id = $project_id and field_name = '$sh_dest_field'";
			$sh_dest_field_value = db_result(db_query($sql), 0);
			if ($sh_dest_field_value != "") {
				// Append new section header to existing one and separate with line breaks
				$sh_value = "$sh_dest_field_value<br><br>$sh_value";
				// Set response as 2 to prompt alert box for user that section headers were merged
				$response = 2;	
			}
			// Set new section header value		
			$sql = "update $metadata_table set element_preceding_header = '". prep($sh_value) . "' where project_id = $project_id and field_name = '$sh_dest_field'";
			db_query($sql);
			// Set old section header value as NULL
			$sql = "update $metadata_table set element_preceding_header = NULL where project_id = $project_id and field_name = '{$_POST['section_header']}'";
			db_query($sql);
			
			// Logging
			log_event("",$metadata_table,"MANAGE",$_POST['form_name'],"form_name = '{$_POST['form_name']}'","Reorder project fields");
		}
	}
	
}

print $response;
