<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'Design/functions.php';
	
// If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	
// Get total field count for all metadata
$total_fields = db_result(db_query("select count(1) from $metadata_table where project_id = $project_id"), 0);

// Check if the table_pk has changed during the recording. If so, give back different response so as to inform the user of change.
$sql = "select field_name, form_name, form_menu_description from $metadata_table where project_id = $project_id order by field_order limit 1";
$q = db_query($sql);
$old_table_pk = db_result($q, 0, "field_name");
$old_first_form = db_result($q, 0, "form_name");
$old_first_form_menu = db_result($q, 0, "form_menu_description");

// Get name of first form to compare in the end to see if it was moved
$firstFormBefore = getFirstForm();
	
// Set up all actions as a transaction to ensure everything is done here
db_query("SET AUTOCOMMIT=0");
db_query("BEGIN");
$sql_errors = 0;


if (isset($_POST['forms']))
{

	// Parse and validate the forms
	$forms = array();
	foreach (explode(",", $_POST['forms']) as $this_form)
	{
		if (!empty($this_form))
		{
			$forms[] = $this_form;		
		}
	}	
	// Check field count for submitted forms against total field count for all metadata
	$sql = "select form_name, field_name from $metadata_table where project_id = $project_id 
			and form_name in ('" . implode("', '", $forms) . "') order by field_order";
	$q = db_query($sql);
	// Quit if any forms are not valid or are missing
	if (db_num_rows($q) != $total_fields) exit("0");
	
	// First create array with all fields
	$fields = array();
	while ($row = db_fetch_assoc($q))
	{
		$fields[$row['form_name']][] = $row['field_name'];
	}
	
	// Loop through all fields and set the new field_order for each field
	$field_order = 1;
	foreach ($forms as $this_form)
	{
		foreach ($fields[$this_form] as $this_field)
		{
			$sql = "update $metadata_table set field_order = $field_order where project_id = $project_id and field_name = '$this_field'";
			if (!db_query($sql)) $sql_errors++;
			$field_order++;
		}
	}
	
	// Get the first form NOW (in case was moved to another position)
	$firstFormAfter = getFirstForm();
	
	// Check if the table_pk has changed during the form move. If so, then move it back to first position.
	$sql = "select field_name, form_menu_description from $metadata_table where project_id = $project_id order by field_order limit 1";
	$q = db_query($sql);
	$new_table_pk = db_result($q, 0, "field_name");
	$new_first_form_menu = db_result($q, 0, "form_menu_description");
	// Compare old first field and new one
	if ($old_table_pk != $new_table_pk) 
	{
		// First set to first position and change form name to new form
		$sql = "update $metadata_table set field_order = 1, form_name = '$firstFormAfter', form_menu_description = '".prep($new_first_form_menu)."' 
				where project_id = $project_id and field_name = '$old_table_pk'";
		if (!db_query($sql)) $sql_errors++;
		// Now move all other fields up one position (on next page, the ProjectAttribute class will fix any messed up ordering)
		$sql = "update $metadata_table set field_order = field_order+1 where project_id = $project_id and field_name != '$old_table_pk'";
		if (!db_query($sql)) $sql_errors++;
		// Now set new table pk form menu label to null because it will be the second field AND set all of old first form as such
		$sql = "update $metadata_table set form_menu_description = null where project_id = $project_id 
				and (field_name = '$new_table_pk' or form_name = '$old_first_form')";
		if (!db_query($sql)) $sql_errors++;
		// Now the new second form needs a form menu label (was attached to old pk)
		$sql = "update $metadata_table set form_menu_description = '".prep($old_first_form_menu)."' 
				where project_id = $project_id and form_name = '$old_first_form' limit 1";
		if (!db_query($sql)) $sql_errors++;		
	}
	
	## CHANGE THIS FOR MULTIPLE SURVEYS!!!! (How to deal with this for multiple survey projects????)
	// If first form was moved and it was a survey, make sure the 
	if ($status < 1 && $firstFormAfter != $firstFormBefore && isset($Proj->forms[$firstFormBefore]['survey_id']) && !isset($Proj->forms[$firstFormAfter]['survey_id']))
	{
		// Change form_name of survey to the new first form name
		$sql = "update redcap_surveys set form_name = '$firstFormAfter' where survey_id = ".$Proj->forms[$firstFormBefore]['survey_id'];
		db_query($sql);	
	}	
	
	// Rollback all changes if sql error occurred
	if ($sql_errors > 0) 
	{
		// Errors occurred, so undo any changes made
		db_query("ROLLBACK");
		// Send error response
		print "0";
	}
	// Logging
	else
	{
		// Log it
		log_event("",$metadata_table,"MANAGE",$project_id,"project_id = $project_id","Reorder data collection instruments");
		// No errors occurred
		db_query("COMMIT");		
		// Send successful response (1 = OK, 2 = OK but first form was moved, i.e. PK was changed)
		print "1";
		/* 
		// PK can no longer be changed using this drag-n-drop method, by definition, so no need to worry about it.
		print (getFirstForm() == $firstFormBefore ? "1" : "2");
		*/
	}

}





/**
 * FUNCTIONS
 */
 
// Move a form to end of metadata
function moveFormToEnd($form) 
{
	global $metadata_table, $project_id, $total_fields, $sql_errors;
	
	// Get field name and order of first field on form1
	$sql = "select field_order from $metadata_table where project_id = $project_id and form_name = '$form' order by field_order limit 1";
	$fieldorder1_form = db_result(db_query($sql), 0);
	
	// Get field count of form1
	$total_fields_form = db_result(db_query("select count(1) from $metadata_table where project_id = $project_id and form_name = '$form'"), 0);
	
	// Move form1 to the end of the metadata
	$sql = "update $metadata_table set field_order = (field_order + $total_fields - $fieldorder1_form + 1) 
			where project_id = $project_id and form_name = '$form'";
	if (!db_query($sql)) $sql_errors++;
	
	// Move all fields that came after form1 down to fill the space where form1 was	
	$sql = "update $metadata_table set field_order = (field_order - $total_fields_form) where project_id = $project_id and field_order >= $fieldorder1_form";
	if (!db_query($sql)) $sql_errors++;
	
	return $total_fields_form;
	
}
