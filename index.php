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
$db_error_msg = "";
$db_conn_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'database.php';	
include $db_conn_file;
if (!isset($hostname) || !isset($db) || !isset($username) || !isset($password)) {
	$db_error_msg = "One or more of your database connection values (\$hostname, \$db, \$username, \$password) 
					 could not be found in your database connection file [$db_conn_file]. Please make sure all four variables are
					 defined with a correct value in that file.";
}
$rc_connection = mysql_connect($hostname, $username, $password);
if (!($rc_connection && mysql_select_db($db, $rc_connection))) {
	$db_error_msg = "Your REDCap database connection file [$db_conn_file] could not connect to the database server. 
					 Please check the connection values in that file (\$hostname, \$db, \$username, \$password) 
					 because they may be incorrect."; 
}
// If there was a db connection error, then display it
if ($db_error_msg != "")
{
	?>
	<html><body style="font-family:arial;font-size:12px;padding:20px;">
		<div style="font: normal 12px Verdana, Arial;padding:10px;border: 1px solid red;color: #800000;max-width: 600px;background: #FFE1E1;">
			<div style="font-weight:bold;font-size:15px;padding-bottom:5px;">
				CRITICAL ERROR: REDCap server is offline!
			</div>
			<div>
				For unknown reasons, REDCap cannot communicate with its database server, which may be offline. Please contact your 
				local REDCap administrator to inform them of this issue immediately. If you are a REDCap administrator, then please see this 
				<a href="javascript:;" style="color:#000066;" onclick="document.getElementById('db_error_msg').style.display='block';">additional information</a>.
				We are sorry for any inconvenience.
			</div>
			<div id="db_error_msg" style="display:none;color:#333;background:#fff;padding:5px 10px 10px;margin:20px 0;border:1px solid #bbb;">
				<b>Message for REDCap administrators:</b><br/><?php echo $db_error_msg ?>
			</div>
		</div>
	</body></html>
	<?php
	// Do custom offline notification if special file exists in main directory
	@include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'index_offline_notify.php';
	exit;
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
	$homepagePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "redcap_v" . $redcap_version . DIRECTORY_SEPARATOR . "home.php";
	if (!include $homepagePath) 
	{
		print "ERROR: Could not find the correct file ($homepagePath)!<br><br>You may need to complete the <a href='install.php'>installation</a>.";
	}	
} 
else 
{
	print "ERROR: Could not find the correct version of REDCap in the \"redcap_config\" table!<br><br>You may need to complete the <a href='install.php'>installation</a>.";
}
