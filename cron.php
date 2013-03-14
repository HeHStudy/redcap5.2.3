<?php 
/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

// Turn off error reporting
error_reporting(0);

// Prevent caching
header("Expires: 0");
header("cache-control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");

// Connect to DB
$db_conn_file = dirname(__FILE__) . '/database.php';	
include ($db_conn_file);
if (!isset($hostname) || !isset($db) || !isset($username) || !isset($password)) 
{
	exit("There is not a valid hostname ($hostname) / database ($db) / username ($username) / password (XXXXXX) combination in your database connection file [$db_conn_file].");
}
$conn = mysql_connect($hostname,$username,$password);
if (!$conn)
{
	exit("The hostname ($hostname) / username ($username) / password (XXXXXX) combination in your database connection file [$db_conn_file] could not connect to the server. Please check their values.<br><br>You may need to complete the <a href='install.php'>installation</a>."); 
}
if (!mysql_select_db($db,$conn)) 
{
	exit("The hostname ($hostname) / database ($db) / username ($username) / password (XXXXXX) combination in your database connection file [$db_conn_file] could not connect to the server. Please check their values.<br><br>You may need to complete the <a href='install.php'>installation</a>."); 
}

// Find the current system version of REDCap
$q = mysql_query("select * from redcap_config");
while ($row = mysql_fetch_assoc($q)) 
{
	$$row['field_name'] = $row['value'];
}

// Include the file home.php from the proper REDCap version folder
if ($redcap_version != "") 
{
	$homepagePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "redcap_v" . $redcap_version . DIRECTORY_SEPARATOR . "cron.php";
	if (!include $homepagePath) 
	{
		print "ERROR: Could not find the correct file ($homepagePath)!<br><br>You may need to complete the <a href='install.php'>installation</a>.";
	}	
} 
else 
{
	print "ERROR: Could not find the correct version of REDCap in the \"redcap_config\" table!<br><br>You may need to complete the <a href='install.php'>installation</a>.";
}
