<?php
// Set root path
define("ROOT", dirname(dirname(__FILE__)));

// Disable REDCap's authentication (will use API tokens for authentication)
define("NOAUTH", true);

// Config
require_once (ROOT . '/Config/init_global.php');


/**
 * API FUNCTIONALITY
 */

# globals
$format = "xml";
$returnFormat = "xml";

# set format (default = xml)
$format = $_POST['format'];
switch ($format)
{
	case 'json':
		break;
	case 'csv':
		break;
	case 'xml':
	default:
		$format = "xml";
		break;
}
$_POST['format'] = $format;

# set returnFormat for outputting error messages and other stuff (default = xml)
$tempFormat = ($_POST['returnFormat'] != "") ? strtolower($_POST['returnFormat']) : strtolower($_POST['format']);
switch ($tempFormat)
{
	case 'json':
		$returnFormat = "json";
		break;
	case 'csv':
		$returnFormat = "csv";
		break;
	case 'xml':
	default:
		$returnFormat = "xml";
		break;
}

# check if the API is enabled first
if (!$api_enabled) RestUtility::sendResponse(503, $lang['api_01']);

# if sending the authkey for External Links functionality, an API token is not required, so catch it before other processing
if (!isset($_POST['token']) && isset($_POST['authkey']))
{
	require_once ROOT . "/API/auth/authkey.php";
	exit;
}

# process the incoming request
$data = RestUtility::processRequest();

# get all the variables sent in the request
$post = $data->getRequestVars();

# initialize array variables if they were NOT sent or if they are empty
if (!isset($post['records']) or $post['records'] == '') $post['records'] = array();
if (!isset($post['events']) or $post['events'] == '') $post['events'] = array();
if (!isset($post['fields']) or $post['fields'] == '') $post['fields'] = array();
if (!isset($post['forms']) or $post['forms'] == '') $post['forms'] = array();
if (!isset($post['arms']) or $post['arms'] == '') $post['arms'] = array();

if (!isset($post['format'])) $post['format'] = "";
if (!isset($post['type'])) $post['type'] = "";
if (!isset($post['rawOrLabel'])) $post['rawOrLabel'] = "";
if (!isset($post['eventName'])) $post['eventName'] = "";
if (!isset($post['overwriteBehavior'])) $post['overwriteBehavior'] = "";
if (!isset($post['action'])) $post['action'] = "";
if (!isset($post['returnContent'])) $post['returnContent'] = "";
if (!isset($post['event'])) $post['event'] = "";
if (!isset($post['armNumber'])) $post['armNumber'] = "";
if (!isset($post['armName'])) $post['armName'] = "";

# determine if a valid content parameter was passed in
$post['content'] = strtolower($post['content']);
switch ($post['content'])
{
	case 'record':
		$post['exportSurveyFields'] = (isset($post['exportSurveyFields']) && ($post['exportSurveyFields'] == '1' || strtolower($post['exportSurveyFields']."") === 'true'));
		$post['exportDataAccessGroups'] = (isset($post['exportDataAccessGroups']) && ($post['exportDataAccessGroups'] == '1' || strtolower($post['exportDataAccessGroups']."") === 'true'));
		break;
	case 'metadata':
	case 'file':
	case 'event':
	case 'arm':
	case 'user':
		break;
	case 'formeventmapping':
        $post['content'] = "formEventMapping";
		break;
	default:
		die(RestUtility::sendResponse(400, 'The value of the parameter "content" is not valid'));
		break;
}

# If content = file, determine if a valid action was passed in 
if ($post['content'] == "file")
{
	switch (strtolower($post['action']))
	{
		case 'export':
		case 'import':
		case 'delete':
			break;
		default:
			die(RestUtility::sendResponse(400, 'The value of the parameter "action" is not valid'));
			break;
	}
}
if ($post['content'] == 'event' || $post['content'] == "arm")
{
	if ($post['action'] == "") $post['action'] = "export";
}

# set the import action option
if (strtolower($post['overwriteBehavior']) != 'normal' && strtolower($post['overwriteBehavior']) != 'overwrite') $post['overwriteBehavior'] = 'normal';

# set the type
if (strtolower($post['type']) != 'eav' && strtolower($post['type']) != 'flat') $post['type'] = 'flat';

# what content to return when importing data
switch (strtolower($post['returnContent']))
{
	case 'ids':
	case 'nothing':
	case 'count':
		break;
	default:
		$post['returnContent'] = 'count';
		break;
}

# set the type of content to be returned for a field that has data/value pairs
switch (strtolower($post['rawOrLabel']))
{
	case 'raw':
	case 'label':
	case 'both':
		break;
	default:
		$post['rawOrLabel'] = 'raw';
		break;
}

# set the event name option (if not set, use rawOrLabel option)
if (strtolower($post['eventName']) != 'unique' && strtolower($post['eventName']) != 'label') {
	$post['eventName'] = ($post['rawOrLabel'] == 'raw') ? 'unique' : 'label';
}

# determine if we are exporting, importing, or deleting data
if ($post['content'] == "file" || $post['content'] == 'event' || $post['content'] == "arm") {
	$action = $post['action'];
}
else {
	$action = (!isset($post['data'])) ? 'export' : 'import';
}

# determine if the user has the correct user rights
if ($action == "export") {
	if ($post['api_export'] != 1) {
		die(RestUtility::sendResponse(403, "You do not have API data export rights"));
	}
}
else {
	if ($post['api_import'] != 1) {
		die(RestUtility::sendResponse(403, "You do not have API data import/change/delete rights"));
	}
}

# include the necessary file, based off of content type and whether the "data" field was passed in
include ($post['content'] . "/$action.php");

