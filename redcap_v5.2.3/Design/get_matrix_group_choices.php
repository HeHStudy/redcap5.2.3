<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

$response = "[]";

if (isset($_POST['grid_name'])) 
{
	// If project is in production, use metadata_temp (draft mode)
	$metadata = ($status > 0) ? $Proj->metadata_temp : $Proj->metadata;
	// Obtain element_enum of first instance of this grid_name in this project
	foreach ($metadata as $field=>$attr)
	{
		// If a radio/checkbox with matching grid_name, return element_enum
		if (($attr['element_type'] == 'checkbox' || $attr['element_type'] == 'radio') && $attr['grid_name'] == $_POST['grid_name']) {
			// Parse and modify choices for display in the textarea
			$choices = array();
			foreach (parseEnum($attr['element_enum']) as $code=>$label) {
				$choices[] = "$code, $label";
			}
			exit(implode("\n", $choices));
		}
	}	
}
	
// Give affirmative response back
print $response;