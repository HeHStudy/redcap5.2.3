<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Is the form valid?
$formValid = ($status > 0) ? isset($Proj->forms_temp[$_POST['form']]['menu']) : isset($Proj->forms[$_POST['form']]['menu']);
if (!isset($_POST['form']) || !$formValid) exit('0');

// Get form label
$formLabel = ($status > 0) ? $Proj->forms_temp[$_POST['form']]['menu'] : $Proj->forms[$_POST['form']]['menu'];

// Change survey title in survey table
$sql = "update redcap_surveys set title = '".prep($formLabel)."' 
		where project_id = $project_id and form_name = '".prep($_POST['form'])."'";
if (!db_query($sql)) {
	exit('0');
} else {
	// Logging
	log_event($sql,"redcap_surveys","MANAGE",$_POST['form'],"form_name = '".prep($_POST['form'])."'","Modify survey title");
	// Output the survey title to display to user for confirmation
	print $formLabel;
}