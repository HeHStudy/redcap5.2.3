<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/
	
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Increase memory limit so large data sets do not crash and yield a blank page



# MAKE DATA IMPORT TEMPLATE EXCEL FILE		

//Make column headers (COLUMN format only)
if ($_GET['format'] == 'cols') {
	$data = "Variable / Field Name";
	for ($k=1; $k<=20; $k++) {
		$data .= ",Record";
	}	
	// Line break
	$data .= "\r\n";
}

// Check if DAGs exist. Add redcap_data_access_group field if DAGs exist AND user is not in a DAG
$dags = $Proj->getGroups();
$addDagField = (!empty($dags) && $user_rights['group_id'] == '');

//Get the field names from metadata table
$select =  "SELECT field_name, element_type, element_enum FROM redcap_metadata WHERE element_type != 'calc' 
			and element_type != 'file' and element_type != 'descriptive' and project_id = $project_id ORDER BY field_order ASC";
$export = db_query($select);
while ($row = db_fetch_array($export)) 
{
	// If a checkbox field, then loop through choices to render pseudo field names for each choice
	if ($row['element_type'] == "checkbox") 
	{
		foreach (array_keys(parseEnum($row['element_enum'])) as $this_value) {
			// If coded value is not numeric, then format to work correct in variable name (no spaces, caps, etc)
			if (!is_numeric($this_value)) {
				$this_value = preg_replace("/[^a-z0-9]/", "", strtolower($this_value));
			}
			$this_fieldname = $row['field_name'] . "___" . $this_value;
			//Write data for each cell
			$data .= $this_fieldname;
			// Line break OR comma
			$data .= ($_GET['format'] == 'rows') ? "," : "\r\n";
		}
	} 
	// Normal non-checkbox fields
	else 
	{
		//Write data for each cell
		$data .= $row['field_name'];
		// Line break OR comma
		$data .= ($_GET['format'] == 'rows') ? "," : "\r\n";
	}
	// If we're on the first field and project is longitudinal, add redcap_event_name
	if ($row['field_name'] == $table_pk) 
	{
		if ($longitudinal) {
			//Write data for each cell
			$data .= "redcap_event_name";
			// Line break OR comma
			$data .= ($_GET['format'] == 'rows') ? "," : "\r\n";
		}
		if ($addDagField) {
			//Write data for each cell
			$data .= "redcap_data_access_group";
			// Line break OR comma
			$data .= ($_GET['format'] == 'rows') ? "," : "\r\n";
		}
	}
}

// Begin output to file
$file_name = substr(str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9 ]/", "", html_entity_decode($app_title, ENT_QUOTES)))), 0, 30)."_ImportTemplate_".date("Y-m-d").".csv";
header('Pragma: anytextexeptno-cache', true);
header("Content-type: application/csv");

header("Content-Disposition: attachment; filename=$file_name");

// Output the data
print $data;

// Logging
log_event("","redcap_metadata","MANAGE",$project_id,"project_id = $project_id","Download data import template");
