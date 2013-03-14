<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Default response
$responseMatrixGroup = '';
$responseFieldNames = '';

## CHECK MATRIX GROUP NAME
if (isset($_GET['checkGridName']) && $_GET['checkGridName'] && isset($_GET['grid_name']) && !empty($_GET['grid_name']))
{
	// Check if matrix group exists
	$matrixGroupExists = ($status > 0) ? isset($Proj->matrixGroupNames_temp[$_GET['grid_name']]) : isset($Proj->matrixGroupNames[$_GET['grid_name']]);
	// Return if group exists (1=exists=return error via JS)
	$responseMatrixGroup = ($matrixGroupExists) ? '1' : '0';
}
	
	
## CHECK FIELD NAMES
if (isset($_GET['checkFieldNames']) && $_GET['checkFieldNames'] && !empty($_GET['fieldNames']))
{
	// Check if all field names exist
	$existingFields = array();
	foreach (explode(",", $_GET['fieldNames']) as $field) {
		$fieldExists = ($status > 0) ? isset($Proj->metadata_temp[$field]) : isset($Proj->metadata[$field]);
		if ($fieldExists) {
			$existingFields[] = $field;
		}
	}
	// Return any existing field names, if they exist. If not, return 0.
	$responseFieldNames = (!empty($existingFields)) ? implode(",", $existingFields) : '0';
}


// Return JSON response
print '{"matrixGroup":"'.$responseMatrixGroup.'","fieldNames":"'.$responseFieldNames.'"}';