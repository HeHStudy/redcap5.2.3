<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'Design/functions.php';

if (isset($_GET['form_name']) && preg_match("/[a-z_0-9]/", $_GET['form_name'])) 
{	
	// If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
		
	// Get current table_pk (temp if in Draft Mode)
	$current_table_pk = (empty($Proj->table_pk_temp)) ? $Proj->table_pk : $Proj->table_pk_temp;
	
	// Check if randomization has been enabled and prevent deletion of the rand field and any strata fields
	if ($randomization && Randomization::setupStatus()) 
	{
		// Get randomization attributes
		$randAttr = Randomization::getRandomizationAttributes();
		// If the randomization field or strata fields are on this form, then stop here
		$sql = "select 1 from $metadata_table where project_id = $project_id and form_name = '{$_GET['form_name']}'
				and field_name in ('{$randAttr['targetField']}', '" . implode("', '", array_keys($randAttr['strata'])) . "')";
		$q = db_query($sql);
		if (db_num_rows($q) > 0) {
			// One or more fields are on this form, so return error code
			exit("3");
		}
	}
	
	// Get name of first form to compare in the end to see if it was moved
	// $firstFormBefore = getFirstForm();

	$sql_all = array();
	
	// Before deleting form, get number of fields on form and field_order of first field (for reordering later)
	$sql = "select count(1) from $metadata_table where project_id = $project_id and form_name = '{$_GET['form_name']}'";
	$field_count = db_result(db_query($sql), 0);
	$sql = "select field_order from $metadata_table where project_id = $project_id and form_name = '{$_GET['form_name']}' limit 1";
	$first_field_order = db_result(db_query($sql), 0);
	
	// Remove standard code mappings
	// Note: standards and standard codes are not removed since the audit trail may still be pointing to them.  Only the mapping is removed.
	$getFieldsSql = "select field_name from $metadata_table where project_id = $project_id and form_name = '{$_GET['form_name']}'";
	$getFieldsQuery = db_query($getFieldsSql);
	while($getFieldsRow = db_fetch_array($getFieldsQuery)) {
		$removeMappingSql = "delete from redcap_standard_map where project_id = $project_id and field_name = '{$getFieldsRow['field_name']}'";
		$removeMappingQuery = db_query($removeMappingSql);
	}
			
	// If edoc_id exists for any fields on this form, then set all as "deleted" in edocs_metadata table
	$sql = "update redcap_edocs_metadata set delete_date = '".NOW."' where project_id = $project_id and delete_date is null and doc_id in
			(select edoc_id from $metadata_table where project_id = $project_id and form_name = '{$_GET['form_name']}' and edoc_id is not null)";
	if (db_query($sql)) $sql_all[] = $sql;
	
	// Delete this form's fields from the metadata table (do NOT delete PK field if deleting first form - will change PK's form_name below)
	$sql = "delete from $metadata_table where project_id = $project_id 
			and form_name = '{$_GET['form_name']}' and field_name != '$current_table_pk'";
	if (db_query($sql)) {
		$sql_all[] = $sql;	
		// Now adjust all field orders to compensate for missing form
		$sql = "update $metadata_table set field_order = field_order - $field_count where project_id = $project_id 
				and field_order > $first_field_order";
		if (db_query($sql)) $sql_all[] = $sql;
	}
	
	// If deleted first form (with the exception of the PK field), then set PK field's form_name to new first form_name value
	$sql = "select field_name from $metadata_table where project_id = $project_id 
			and form_name = '{$_GET['form_name']}' and field_name = '$current_table_pk' limit 1";
	$q = db_query($sql);
	$firstFormDeleted = (db_num_rows($q) > 0);
	if ($firstFormDeleted) 
	{
		$forms = ($status > 0) ? $Proj->forms_temp : $Proj->forms;
		array_shift($forms);
		$formsKeys = array_keys($forms);
		$secondForm = array_shift($formsKeys);
		$secondFormMenu = $forms[$secondForm]['menu'];
		$secondFormFirstField = array_shift(array_keys($forms[$secondForm]['fields']));
		// Set PK's form_name with the value of the next form (which is the new first form)
		$sql = "update $metadata_table set form_name = '".prep($secondForm)."', form_menu_description = '".prep($secondFormMenu)."',
				field_order = 0 where project_id = $project_id and field_name = '$current_table_pk'";
		if (db_query($sql)) $sql_all[] = $sql;
		// Fix duplication of form_menu_description on the new first form
		$sql = "update $metadata_table set form_menu_description = null
				where project_id = $project_id and field_name = '$secondFormFirstField'";
		if (db_query($sql)) $sql_all[] = $sql;
		// Now adjust all field orders to compensate for this issue with PK field
		$sql = "update $metadata_table set field_order = field_order + 1 where project_id = $project_id";
		if (db_query($sql)) $sql_all[] = $sql;
	}
	
	// If in Development, delete all form-level rights associated with the form
	if ($status < 1) 
	{
		// Catch all 3 possible instances of form-level rights to delete them from user rights table
		$sql = "update redcap_user_rights set data_entry = replace(data_entry,'[{$_GET['form_name']},0]',''),
				data_entry = replace(data_entry,'[{$_GET['form_name']},1]',''), data_entry = replace(data_entry,'[{$_GET['form_name']},2]','')
				where project_id = $project_id";
		if (db_query($sql)) $sql_all[] = $sql;
		// Delete form from all tables EXCEPT metadata tables and user_rights table
		deleteFormFromTables($_GET['form_name']);
	}
	
	// Logging
	log_event(implode(";\n", $sql_all), $metadata_table, "MANAGE", $_GET['form_name'], "form_name = '{$_GET['form_name']}'", "Delete data collection instrument");
	
	// Send successful response (1 = OK)
	print "1";
	
}
