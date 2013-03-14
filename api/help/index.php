<?php 
/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

// Set error reporting and prevent caching
error_reporting(0);

header("Expires: 0");
header("cache-control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");

//Connect to db
include dirname(dirname(dirname(__FILE__))) . "/database.php";
if (!$conn = mysql_connect($hostname,$username,$password)) { 
	exit("The hostname ($hostname) / username ($username) / password (XXXXXX) combination could not connect to the MySQL server. 
		Please check their values."); 
}
if (!$db_conn = mysql_select_db($db,$conn)) { 
	exit("The hostname ($hostname) / database ($db) / username ($username) / password (XXXXXX) combination could not connect to the MySQL server. 
		Please check their values."); 
}

//Query the rs_config table to get basic configuration info 
$query = mysql_query("select value from redcap_config where field_name = 'redcap_version'");
if (mysql_num_rows($query) == 0 || !$query) { 
	exit("ERROR: Problem connecting to database"); 
}

// Call the API page
$this_page = dirname(dirname(dirname(__FILE__))) . "/redcap_v" . mysql_result($query, 0) . "/API/help.php";
if (!include $this_page)
{
	exit("ERROR: Could not find the page $this_page"); 
}