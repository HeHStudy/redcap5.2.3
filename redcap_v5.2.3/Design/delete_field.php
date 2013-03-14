<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once (APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php');

$response = "0";

if (isset($_GET['field_name']) && preg_match("/[a-z_0-9]/", $_GET['field_name'])) 
{
	//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	
	// Check if randomization has been enabled and prevent deletion of the rand field and any strata fields
	if ($randomization && Randomization::setupStatus()) 
	{
		// Get randomization attributes
		$randAttr = Randomization::getRandomizationAttributes();
		// If this field is the randomization field or a strata fields, then stop here
		if ($_GET['field_name'] == $randAttr['targetField'] || in_array($_GET['field_name'], array_keys($randAttr['strata']))) {
			// Field is used, so return error code
			exit("6");
		}
	}
	
	// Check if the table_pk is being deleted. If so, give back different response so as to inform the user of change.
	$sql = "select field_name from $metadata_table where project_id = $project_id order by field_order limit 1";
	$deletingTablePk = ($_GET['field_name'] == db_result(db_query($sql), 0));
	
	// Remove section header only (do not delete the row in metadata table)
	if ($_GET['section_header']) {
		
		//Set old section header value as NULL
		$sql = "update $metadata_table set element_preceding_header = NULL where project_id = $project_id and field_name = '{$_GET['field_name']}'";
		$q = db_query($sql);			
		// Logging
		if ($q) {
			log_event($sql,$metadata_table,"MANAGE",$_GET['field_name'],"field_name = '{$_GET['field_name']}'","Delete section header");
			$response = "1";
		}
	
	// Delete field from metadata table
	} else {
		
		// Get current field_order of this field we're deleting
		$sql = "select field_order, element_preceding_header from $metadata_table where project_id = $project_id and field_name = '{$_GET['field_name']}'";
		$row = db_fetch_assoc(db_query($sql));
		$this_field_order = $row['field_order'];
		$this_field_section_header = $row['element_preceding_header'];
		
		if ($this_field_order != "") {
		
			// Check to make sure if this is the last field on form, in case it has section header, which we'd need to remove from page
			$sql = "select field_name, element_preceding_header from redcap_metadata where project_id = $project_id 
					and form_name = '{$_GET['form_name']}' order by field_order desc limit 1,1";
			$q = db_query($sql);
			$lastFormField = db_result($q, 0, 'field_name');
			$lastFormFieldSH = db_result($q, 0, 'element_preceding_header');
			$isLastFieldWithSH = ($lastFormField == $_GET['field_name'] && !empty($lastFormFieldSH)) ? true : false;
		
			//Get the field name of the field immediately after the one we're deleting (for purposes later)
			$sql = "select field_name from $metadata_table where project_id = $project_id and field_order > 
				   " . pre_query("select field_order from $metadata_table where project_id = $project_id and field_name = '{$_GET['field_name']}'") . " 
				   order by field_order limit 1";
			$next_field_name = db_result(db_query($sql), 0);
				
			// Determine if field has a Section Header. If so, move it to the field immediately after it.
			if ($this_field_section_header != "") 
			{
				// Set new section header value
				if ($next_field_name != $_GET['form_name']."_complete") {
					// Move SH label to the following field (if following field is NOT the Form Status field)
					$sql = "update $metadata_table set element_preceding_header = '" . prep($this_field_section_header) . "' 
							where project_id = $project_id and field_name = '$next_field_name'";
					db_query($sql);
					// Give response of 3 so javascript knows to reload table (because DOM values connect the SH to the field deleted)
					$response = "3";
				}
			}
			
			// Determine if field is first field on form, meaning that it has the Form Menu Description, which needs to be moved down to next field.
			$sql = "select field_name, form_menu_description from $metadata_table where project_id = $project_id and form_name = 
				   " . pre_query("select form_name from $metadata_table where project_id = $project_id and field_name = '{$_GET['field_name']}' limit 1"). "
				   order by field_order limit 1";
			$q = db_query($sql);
			$first_form_field = db_result($q, 0, "field_name");
			$form_menu_description = db_result($q, 0, "form_menu_description");
			// If we're deleting the first field on this form, then assign Form Menu to field directly below it.
			if ($first_form_field == $_GET['field_name']) {
				//Set new section header value
				$sql = "update $metadata_table set form_menu_description = '" . prep($form_menu_description) . "' 
						where project_id = $project_id and field_name = '$next_field_name'";
				db_query($sql);
			}
			
			## CHECK IF NEED TO DELETE EDOC: If edoc_id exists, then set as "deleted" in edocs_metadata table (development only OR if added then deleted in Draft Mode)
			deleteEdoc($_GET['field_name']);
			
			// Now delete the field
			$sql = "delete from $metadata_table where project_id = $project_id and field_name = '{$_GET['field_name']}'";
			$q = db_query($sql);
			
			//also delete any standard code mappings that may exist
			//note: standards and standard codes are not removed since the audit trail may still be pointing to them.  Only the mapping is removed.
			$mapSql = "delete from redcap_standard_map where project_id = $project_id and field_name = '{$_GET['field_name']}'";
			$mapQ = db_query($mapSql);
			
			// Set successful response (i.e. 1, but if the last field and has a section header, give 3 so we can reload whole table)
			// Give response of 4 if the field deleted was the table_pk
			$response = ($isLastFieldWithSH ? "5" : ($deletingTablePk ? "4" : ($response == "3" ? $response : "1")));
			
			// Logging
			if ($q) log_event($sql,$metadata_table,"MANAGE",$_GET['field_name'],"field_name = '{$_GET['field_name']}'","Delete project field");
			
			// Reset the field_orders of all fields
			$sql = "update $metadata_table set field_order = field_order - 1 where project_id = $project_id and field_order > $this_field_order";
			db_query($sql);
						
			// Form Status field: Now check to make sure other fields exist. If only field left is Form Status, then remove it too.
			$sql = "select field_name, field_order from $metadata_table where project_id = $project_id and form_name = '{$_GET['form_name']}'";
			$q = db_query($sql);
			if (db_num_rows($q) == 1)
			{
				// Is only field the form status field?
				$this_field_name  = db_result($q, 0, "field_name");
				$this_field_order = db_result($q, 0, "field_order");
				if ($this_field_name == $_GET['form_name'] . "_complete")
				{
					// Delete the form status field
					$sql = "delete from $metadata_table where project_id = $project_id and field_name = '$this_field_name'";
					db_query($sql);	
					// Reset the field_orders of all fields
					$sql = "update $metadata_table set field_order = field_order - 1 where project_id = $project_id and field_order > $this_field_order";
					db_query($sql);
					// Set successful response and note that form status field was deleted
					$response = "2";
				}
			}
		
		}
	
	}
	
	//Give affirmative response back
	print $response;
	
}