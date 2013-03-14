<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'Design/functions.php';

## Remove the calc equation from the back-end for a field (due to invalid eqn syntax that got saved)
if (isset($_POST['field']) && isset($_POST['action']) && $_POST['action'] == 'erase'
	&& (($status == 0 && isset($Proj->metadata[$_POST['field']])) || ($status > 0 && isset($Proj->metadata_temp[$_POST['field']]))))
{
	// Remove equation in metadata
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	$sql = "update $metadata_table set element_enum = null where project_id = $project_id 
			and field_name = '".prep($_POST['field'])."'";
	if (db_query($sql)) {			
		// Log the data change
		log_event($sql, $metadata_table, "MANAGE", $_POST['field'], "field_name = '{$_POST['field']}'", "Edit project field");
		exit('1');
	}
}




## Validate the fields in the calc equation
elseif (isset($_POST['eq']) && isset($_POST['field']))
{
	// Clean
	$_POST['eq'] = trim(label_decode($_POST['eq']));
	
	// Obtain array of error fields that are not real fields
	$error_fields = validateBranchingCalc($_POST['eq']);
	
	// Text stating that the calculation was not saved
	$notSavedText = RCView::div(array('class'=>'yellow','style'=>'margin-top:20px;'), 
						RCView::b($lang['global_02'].$lang['colon']) . RCView::SP .
						$lang['design_452']
					);
	
	// Return list of fields that do not exist (i.e. were entered incorrectly), else continue.
	if (!empty($error_fields))
	{
		$response = $lang['design_413'] . RCView::br() . RCView::br() . RCView::b($lang['survey_472']) . 
				    RCView::br() . "- " . implode( RCView::br() . "- ", $error_fields) .
				    $notSavedText;
		exit($response);
	}
	
	// If is existing field, compare old eqn with new eqn
	if (($status == 0 && isset($Proj->metadata[$_POST['field']])) || ($status > 0 && isset($Proj->metadata_temp[$_POST['field']]))) 
	{
		// Get existing calc equation (if not changing, then allow it to save)
		$existing_eq = ($status == 0 ? $Proj->metadata[$_POST['field']]['element_enum'] : $Proj->metadata_temp[$_POST['field']]['element_enum']);
		$existing_eq = trim(label_decode($existing_eq));
		// Since equation is not changing and all fields are valid fields, don't go further and check syntax indepthly
		if ($existing_eq == $_POST['eq']) exit('');
	}
	
	// Check if calculation is valid
	$newEqIsValid = LogicTester::isValid($_POST['eq']);
	
	// NON-SUPER USERS: Perform deeper inspection of syntax to make sure nothing malicious gets through
	if (!$super_user && $_POST['eq'] != "" && !$newEqIsValid) 
	{
		// Default: Contains syntax errors (general)
		$response = "<b>{$lang['dataqueries_47']}{$lang['colon']}</b><br>{$lang['dataqueries_99']}$notSavedText";
		// Check the logic for illegal functions
		$parser = new LogicParser();
		try {
			$parser->parse($_POST['eq']);
		} catch (LogicException $e) {
			if (count($parser->illegalFunctionsAttempted) !== 0) {
				// Contains illegal functions
				$response = "<b>{$lang['dataqueries_47']}{$lang['colon']}</b><br>{$lang['dataqueries_109']}<br><br><b>{$lang['dataqueries_48']}</b><br>- " 
						  . implode("<br>- ", $parser->illegalFunctionsAttempted) . $notSavedText;
				exit($response);
			}
		}
		// Return error message
		exit($response);
	}

	// If a super user and there is an allowable syntax error in the equation (e.g. custom javascript), then give special msg.
	elseif ($super_user && $_POST['eq'] != "" && !$newEqIsValid) {
		exit('2');
	}
	
	// If we got here, then return successful response
	exit('1');
}

// ERROR
else
{
	exit('0');
}
