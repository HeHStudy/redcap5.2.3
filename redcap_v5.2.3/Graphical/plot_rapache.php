<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

error_reporting(0);

// Define DIRECTORY_SEPARATOR as DS for less typing
define("DS", DIRECTORY_SEPARATOR);

// Counter used for halting PHP (in microseconds) in plot_rapache.php to pace the requests to the R/Apache server
if (!isset($_GET['refresh']) && isset($_GET['s']) && is_numeric($_GET['s'])) {
	usleep($_GET['s']);
}

// Set headers
header("Expires: 0");
header("cache-control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");
// Set header for PNG image, but not if in debug mode
if (!isset($_GET['debug'])) {
	header('Content-type: image/png');
}

//Connect to db
$db_conn_file = dirname(dirname(dirname(__FILE__))) . DS . 'database.php';
include ($db_conn_file); $conn = mysqli_connect($hostname,$username,$password,$db);

//Functions
require_once dirname(__FILE__) . '/functions.php';

// Santize $_GET array
foreach ($_GET as $key=>$value) {
	$_GET[$key] = htmlspecialchars($value, ENT_QUOTES);
}

if (isset($_GET['pid']) && $_GET['pid'] != "") 
{
	// Set project id
	define("PROJECT_ID", $_GET['pid']);
	// Webtools folder path
	define("APP_PATH_WEBTOOLS",	dirname(dirname(dirname(__FILE__))) . "/webtools2/");
	// Get data string to send
	$datastring = rapache_field_to_csv($_GET['form'], $_GET['field'], $_GET['totalrecs'], $_GET['group_id']);
	// Render plot	
	if (isset($_GET['debug'])) {
		print $datastring;
	} else {
		print rapache_service('plotvar', NULL, $datastring);
	}	
}
