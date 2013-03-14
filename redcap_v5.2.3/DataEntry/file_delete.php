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


// Required files
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';


// Surveys only: Perform double checking to make sure the survey participant has rights to this file
if (isset($_GET['s']) && !empty($_GET['s']))
{
	checkSurveyFileRights();
	$field_label_page = APP_PATH_SURVEY . "index.php?pid=$project_id&__passthru=".urlencode("Design/get_fieldlabel.php");
}
// Non-surveys: Check form-level rights and DAGs to ensure user has access to this file
elseif (!isset($_GET['s']) || empty($_GET['s']))
{
	checkFormFileRights();
	$field_label_page = APP_PATH_WEBROOT . "Design/get_fieldlabel.php?pid=$project_id";	
}


if (is_numeric($_GET['event_id']) && is_numeric($_GET['id']) && isset($Proj->metadata[$_GET['field_name']]))
{
	// If user is a double data entry person, append --# to record id when saving
	if (isset($user_rights) && $double_data_entry && $user_rights['double_data'] != 0) 
	{
		$_GET['record'] .= "--" . $user_rights['double_data'];
	}

	// Delete data for this field from data table
	$sql = "DELETE FROM redcap_data WHERE record = '" . prep($_GET['record']) . "' AND field_name = '{$_GET['field_name']}' 
			AND project_id = $project_id AND event_id = {$_GET['event_id']}";
	$q = db_query($sql);
	log_event($sql,"redcap_data","doc_delete",$_GET['record'],$_GET['field_name'],"Delete uploaded document");

	// Set the file as "deleted" in redcap_edocs_metadata table, but don't really delete the file or the table entry
	$edoc_q = "UPDATE redcap_edocs_metadata SET delete_date = '" . NOW . "' WHERE doc_id = " . $_GET['id'];
	$q = db_query($edoc_q);
	
	// Send back HTML for uploading a new file (since this one has been removed)
	print  '<img src="'.APP_PATH_IMAGES.'add.png" class="imgfix"> 
			<a href="javascript:;" style="text-decoration:none;font-size:12px;color:green;font-family:Arial;" onclick="filePopUp(\''.$_GET['field_name'].'\',\''.$field_label_page.'\')">Upload document</a>';

}