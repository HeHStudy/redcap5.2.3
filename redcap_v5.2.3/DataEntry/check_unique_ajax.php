<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Check if coming from survey or authenticated form
if (isset($_GET['s']) && !empty($_GET['s']))
{
	// Call config_functions before config file in this case since we need some setup before calling config
	require_once dirname(dirname(__FILE__)) . '/Config/init_functions.php';
	// Survey functions needed
	require_once dirname(dirname(__FILE__)) . "/Surveys/survey_functions.php";
	// Validate and clean the survey hash, while also returning if a legacy hash
	$hash = $_GET['s'] = checkSurveyHash();
	// Set all survey attributes as global variables
	setSurveyVals($hash);
	// Now set $_GET['pid'] before calling config
	$_GET['pid'] = $project_id;
	// Set flag for no authentication for survey pages
	define("NOAUTH", true);
}

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


// Check if we have field_name in query string
if (!isset($_GET['field_name']) || (isset($_GET['field_name']) && !isset($Proj->metadata[$_GET['field_name']])))
{
	// Error
	if ($isAjax) {
		exit;
	} else {
		redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");
	}
}



/**
 * CHECK UNIQUENESS OF FIELD VALUES FOR SECONDARY_PK
 */

// Default: Check to make sure ALL current values for the field given are unique.
if (!isset($_GET['record']) && !isset($_GET['value']))
{
	// Get a count of all duplicated values for the field submitted
	$sql = "select sum(duplicates) from (select count(1) as duplicates from 
			(select distinct record, value from redcap_data where project_id = $project_id 
			and field_name = '{$_GET['field_name']}') x group by value) y where duplicates > 1";
	$q = db_query($sql);
	// Return the number of duplicates
	$duplicates = db_result($q, 0);
	print (is_numeric($duplicates)) ? $duplicates : 0;
}

// If value and record are given, check uniqueness against all other records' values.
elseif (isset($_GET['record']) && isset($_GET['value']) && $secondary_pk != "" && $secondary_pk == $_GET['field_name']) 
{
	// Get a count of all duplicated values for the $secondary_pk field (exclude submitted record name when counting)
	$sql = "select count(1) from redcap_data where project_id = $project_id and field_name = '$secondary_pk' 
			and value = '" . prep(urldecode($_GET['value'])) . "' and record != '' and record != '" . prep(urldecode($_GET['record'])) . "'";
	$q = db_query($sql);
	// Return the number of duplicates
	print db_result($q, 0);
}
