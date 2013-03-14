<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'Design/functions.php';

// Default response
$response = '0';

## Validate the fields in the logic
if (isset($_POST['logic']))
{
	// Demangle (if needed)
	$_POST['logic'] = html_entity_decode($_POST['logic'], ENT_QUOTES);

	// Obtain array of error fields that are not real fields
	$error_fields = validateBranchingCalc($_POST['logic'], true);	
	
	// If longitudinal, make sure that each field references an event and that the event is valid
	if ($longitudinal) {
		// Initialize array to capture invalid event names
		$invalid_event_names = array();
		foreach (array_keys(getBracketedFields(cleanBranchingOrCalc($_POST['logic']), true, true)) as $eventDotfield) {
			// If lacks a dot, then the event name is missing. Flag it
			if (strpos($eventDotfield, '.') !== false) {
				// Validate the unique event name
				list ($unique_event, $field) = explode('.', $eventDotfield, 2);
				if (!$Proj->uniqueEventNameExists($unique_event)) {
					// Invalid event name, so place in array
					$invalid_event_names[] = $unique_event;
				}
			}
		}
	}
	
	
	
	
	// Return list of fields that do not exist (i.e. were entered incorrectly), else continue.
	if (!empty($error_fields))
	{
		$response = cleanHtml2("{$lang['dataqueries_47']}{$lang['colon']} {$lang['dataqueries_45']}")."\n\n".cleanHtml2($lang['dataqueries_46'])."\n- " 
				  . implode("\n- ", $error_fields);
	}
	
	// If longitudinal and some unique event names are invalid
	elseif ($longitudinal && !empty($invalid_event_names))
	{
		$response = cleanHtml2($lang['dataqueries_111'])."\n\n".cleanHtml2($lang['dataqueries_112'])."\n- " 
				  . implode("\n- ", $invalid_event_names);
	}
	
	// Check for any formatting issues or illegal functions used
	else
	{
		// All is good (no errors)
		$response = '1';
		// Check the logic
		$parser = new LogicParser();
		try {
			$parser->parse($_POST['logic']);
		}
		catch (LogicException $e) {
			if (count($parser->illegalFunctionsAttempted) === 0) {
				// Contains syntax errors
				$response = $lang['dataqueries_99'];
			}
			else {
				// Contains illegal functions
				$response = cleanHtml2("{$lang['dataqueries_47']}{$lang['colon']} {$lang['dataqueries_109']}")."\n\n".cleanHtml2($lang['dataqueries_48'])."\n- " 
						  . implode("\n- ", $parser->illegalFunctionsAttempted);
			}
		}
	}
}

// Send response
exit($response);
