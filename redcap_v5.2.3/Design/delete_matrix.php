<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Default response
$response = "[]";

if (isset($_POST['grid_name']) && isset($_POST['field_name'])) 
{
	// If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	
	// Validate the grid_name and field name
	$fieldExists = ($status > 0) ? isset($Proj->metadata_temp[$_POST['field_name']]) : isset($Proj->metadata[$_POST['field_name']]);
	$gridExists =  ($status > 0) ? isset($Proj->matrixGroupNamesTemp[$_POST['grid_name']]) : isset($Proj->matrixGroupNames[$_POST['grid_name']]);
	$gridFields =  ($status > 0) ? $Proj->matrixGroupNamesTemp[$_POST['grid_name']] : $Proj->matrixGroupNames[$_POST['grid_name']];
	if (!$fieldExists || !$gridExists || !in_array($_POST['field_name'], $gridFields)) exit('0');
	
	// Delete all fields in the grid
	$sql1 = "delete from $metadata_table where project_id = $project_id and field_name in ('" . implode("', '", $gridFields) . "')";
	if (!db_query($sql1)) exit('0');
	
	// Check if first field in grid has a Form Menu Label (if first field on a form), and preserve it
	$formMenuLabel = ($status > 0) ? $Proj->metadata_temp[$_POST['field_name']]['form_menu_description'] : $Proj->metadata[$_POST['field_name']]['form_menu_description'];
	$sql2 = "";
	if ($formMenuLabel != "") {	
		// Get form name for this matrix
		$formName = ($status > 0) ? $Proj->metadata_temp[$_POST['field_name']]['form_name'] : $Proj->metadata[$_POST['field_name']]['form_name'];
		// Get last field in this matrix and its field_order (so we can get position of its following field)
		$lastFieldInMatrix = $gridFields[count($gridFields)-1];
		$lastFieldInMatrixFieldOrder = ($status > 0) ? $Proj->metadata_temp[$lastFieldInMatrix]['field_order'] : $Proj->metadata[$lastFieldInMatrix]['field_order'];
		$sql2 = "update $metadata_table set form_menu_description = '".prep($formMenuLabel)."' 
				 where project_id = $project_id and form_name = '".prep($formName)."' and field_order = ".($lastFieldInMatrixFieldOrder+1);
		db_query($sql2);
	}
		
	// Check if the table_pk has changed during the recording. If so, give back different response so as to inform the user of change.
	$pk_changed = (Design::recordIdFieldChanged()) ? '1' : '0';
	
	// Log this event
	$fieldNamesLog = "grid_name = '{$_POST['grid_name']}'\nfield_name = '" . implode("'\nfield_name = '", $gridFields) . "'";
	log_event("$sql1;\n$sql2",$metadata_table,"MANAGE",$_POST['grid_name'],$fieldNamesLog,"Delete matrix of fields");
	
	// Set successful JSON response if we got this far
	$response = '{"fields":["'. implode('","', $gridFields) .'"],"pk_changed":"'.$pk_changed.'"}';
}
	
// Output response value
print $response;