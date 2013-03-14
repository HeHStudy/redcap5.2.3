<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Disable authentication
define("NOAUTH", true);
// Call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();

?>
<script type="text/javascript">
function whyComponentMissing() {
	var msg = "Because some components are added to REDCap at a specific version and are never modified thereafter, they are not included in every "
			+ "version of REDCap in the upgrade zip file. Since such components are only added once, it does not make sense to include them in every "
			+ "upgrade file, so instead they are thus only included in the version that first utilizes them. This triggers the "
			+ "error you see here, which prompts you to go download them now. This is not ideal, but it is the best approach for now. "
			+ "Sorry for any inconvenience.";
	alert(msg);
}
</script>
<?php

############################################################################################

//PAGE HEADER	
print  "<table width=100% cellspacing=0 style='margin-top:10px;'><tr>
			<td align=left style='font-size:24px;color:#800000;font-weight:bold;'>
				REDCap Configuration Test
			</td><td style='text-align:right;'>
				<img src='" . APP_PATH_IMAGES . "redcaplogo_small.gif'>
			</td>
		</tr></table>
		<p>
			<b>This file will test your current REDCap configuration to determine if any errors exist
			that might prevent it from functioning properly.</b>
		</p>";


## Basic tests
print "<p style='padding-top:10px;color:#800000;font-weight:bold;font-family:verdana;font-size:13px;'>Basic tests</p>";



$testInitMsg = "<b>INITIAL TEST: Establish basic REDCap file structure</b>
				<br>Search for necessary files and folders that should be located in the main REDCap folder 
				(i.e. \"".dirname(APP_PATH_DOCROOT)."\").";
$missing_files = 0;
if (substr(basename(APP_PATH_DOCROOT),0,8) != "redcap_v" && basename(APP_PATH_DOCROOT) != "codebase"){ 
	exit (RCView::div(array('class'=>'red'), "$testInitMsg<br> &bull; redcap_v?.?.? - <b>MISSING!<p>ERROR! - This file (Test/index.php) should be located in a folder named with the following format:
	/redcap/redcap_v?.?.?/. Find this folder and place the Test/index.php file in it, and run this test again.</b>")); 
	$missing_files = 1; 
}
if (!is_dir(dirname(APP_PATH_DOCROOT)."/temp")) { 
	$testInitMsg .= "<br> &bull; temp - <b>MISSING!</b>";
	$missing_files = 1; 
}
if (!is_dir(dirname(APP_PATH_DOCROOT)."/edocs")) {
	$testInitMsg .= "<br> &bull; edocs - <b>MISSING!</b>";
	$missing_files = 1; 
}
if (!is_file(dirname(APP_PATH_DOCROOT)."/database.php")) {
	$testInitMsg .= "<br> &bull; database.php - <b>MISSING!</b>";
	$missing_files = 1; 
}
if (is_dir(dirname(APP_PATH_DOCROOT)."/webtools2")) { 
	// See if the webdav folder is in correct location
	if (!is_dir(dirname(APP_PATH_DOCROOT)."/webtools2/webdav")) {
		$testInitMsg .= "<p><b>ERROR! - The sub-folder named \"webdav\" is missing from the \"webtools2\" folder.</b>
		<br>Find this folder and place it in the \"webtools2\" folder. Then run this test again.";
		$missing_files = 1;  
	}
	// R/Apache folder
	if (!is_file(dirname(APP_PATH_DOCROOT)."/webtools2/RApache/rapache_init_conn.php")) { 
		$testInitMsg .= "<br> &bull; webtools2/RApache/rapache_init_conn.php - <b>MISSING!</b>";
		$missing_files = 1; 
	}
	// LDAP folder
	if (!is_file(dirname(APP_PATH_DOCROOT)."/webtools2/ldap/ldap_config.php")) {
		$testInitMsg .= "<br> &bull; webtools2/ldap/ldap_config.php - <b>MISSING!</b>";
		$missing_files = 1; 
	}
	// TinyMCE folder
	if (!is_dir(dirname(APP_PATH_DOCROOT)."/webtools2/tinymce_".TINYMCE_VERSION)) { 
		$testInitMsg .= "<br> &bull; webtools2/tinymce_".TINYMCE_VERSION." - <b>MISSING!</b> - Must be obtained from install/upgrade zip file from version 4.9.7. 
				See the <a href='https://iwg.devguard.com/trac/redcap/wiki/RedcapDownloadArea' style='text-decoration:underline;' target='_blank'>REDCap download page</a>.
				(<a href='javascript:;' onclick='whyComponentMissing()' style='color:#800000'>Why is this missing?</a>)";
		$missing_files = 1; 
	}
	
} else { 
	$testInitMsg .= "<br> &bull; webtools2 - <b>MISSING! &nbsp; <font color=#800000>This folder needs to be in the folder named \"".dirname(APP_PATH_DOCROOT)."\".</font></b>";
	$missing_files = 1; 
}
if (!is_dir(dirname(APP_PATH_DOCROOT)."/languages")) { 
	$testInitMsg .= "<br> &bull; languages - <b>MISSING!</b> - Must be obtained from install/upgrade zip file from version 3.2.0. 
			See the <a href='https://iwg.devguard.com/trac/redcap/wiki/RedcapDownloadArea' style='text-decoration:underline;' target='_blank'>REDCap download page</a>.
			(<a href='javascript:;' onclick='whyComponentMissing()' style='color:#800000'>Why is this missing?</a>)";
	$missing_files = 1; 
}
if (!is_dir(dirname(APP_PATH_DOCROOT)."/api")) { 
	$testInitMsg .= "<br> &bull; api - <b>MISSING!</b> - Must be obtained from install/upgrade zip file from version 3.3.0. 
			See the <a href='https://iwg.devguard.com/trac/redcap/wiki/RedcapDownloadArea' style='text-decoration:underline;' target='_blank'>REDCap download page</a>.
			(<a href='javascript:;' onclick='whyComponentMissing()' style='color:#800000'>Why is this missing?</a>)";
	$missing_files = 1; 
}
if (!is_dir(dirname(APP_PATH_DOCROOT)."/api/help")) { 
	$testInitMsg .= "<br> &bull; api/help - <b>MISSING!</b> - Must be obtained from install/upgrade zip file from version 3.3.0. 
			See the <a href='https://iwg.devguard.com/trac/redcap/wiki/RedcapDownloadArea' style='text-decoration:underline;' target='_blank'>REDCap download page</a>.
			(<a href='javascript:;' onclick='whyComponentMissing()' style='color:#800000'>Why is this missing?</a>)";
	$missing_files = 1; 
}
if (!is_dir(dirname(APP_PATH_DOCROOT)."/surveys")) { 
	$testInitMsg .= "<br> &bull; surveys - <b>MISSING!</b> - Must be obtained from install/upgrade zip file from version 4.0.0. 
			See the <a href='https://iwg.devguard.com/trac/redcap/wiki/RedcapDownloadArea' style='text-decoration:underline;' target='_blank'>REDCap download page</a>.
			(<a href='javascript:;' onclick='whyComponentMissing()' style='color:#800000'>Why is this missing?</a>)";
	$missing_files = 1; 
}

if ($missing_files == 1){ 
	exit(RCView::div(array('class'=>'red'), "$testInitMsg<br><br><b><font color=red>ERROR!</font> - One or more of the files/folders listed above could not be found 
			in the folder named \"".dirname(APP_PATH_DOCROOT)."\". Please locate those files/folders in the Install/Upgrade zip file
			that you downloaded from the REDCap wiki, then add them to the correct location on your server and run this test again.")); 
} else { 
	$testInitMsg .= "<br><br><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - All necessary files and folders were found."; 
	print RCView::div(array('class'=>'darkgreen','style'=>'color:green;'), $testInitMsg);
}






$testMsg2 = "<b>TEST 1: Check connection to MySQL server and project</b><br><br>";
include(dirname(APP_PATH_DOCROOT).'/database.php'); 
$conn = mysqli_connect($hostname,$username,$password,$db);
if ($conn) {
	print RCView::div(array('class'=>'darkgreen','style'=>'color:green;'), "$testMsg2 <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - Using the path of the database connection file given above, 
		a connection has been made to the MySQL server and database named <b>".$db."</b> on the host named 
		<b>".$hostname."</b>.");	
} else {
	exit (RCView::div(array('class'=>'red'), "$testMsg2<b>ERROR! - A connection could NOT be made to the MySQL server properly.</b>
		<br>This error may have resulted from an incorrect firewall configuration. 
		<b>ALSO</b>, make sure that your database connection file follows the variable format as seen below 
		and that all the values for those variables are correct:
		<font color=#800000><br>".chr(60).chr(63)."
		<br>".chr(36)."hostname = 'your_host_name';  ($hostname)
		<br>".chr(36)."username = 'your_db_username';  ($username)
		<br>".chr(36)."password = 'your_db_password';  ($password)
		<br>".chr(36)."db = 'your_db_name';  ($db)</font>
		<br><br>Once you have checked these things and made corrections, run this test again."));	
}





$testMsg3 = "<b>TEST 2: Connect to the table named \"redcap_config\"</b><br><br>";
$QQuery = db_query("SHOW TABLES FROM `$db` LIKE 'redcap_config'");
if (db_num_rows($QQuery) == 1) {
	print RCView::div(array('class'=>'darkgreen','style'=>'color:green;'), "$testMsg3 <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - The table \"redcap_config\" in the MySQL database named <b>".$db."</b>
			was accessed successfully.");
} else {
	exit (RCView::div(array('class'=>'red'), "$testMsg3<b>ERROR! - The database table named \"redcap_config\" could NOT be accessed.</b>
	<br>This error may have resulted if there was an error during the install/upgrade process. Please make sure that the
	\"redcap_config\" table is located in the MySQL project <b>".$db."</b>. 
	If it is not, you will need to re-install/re-upgrade REDCap, and then run this test again."));
}


// Check for PEAR modules
$testMsg = "<b>TEST 3: Check if PHP PEAR packages are installed</b><br><br>";
if (@ !include 'PEAR.php') {
	exit (RCView::div(array('class'=>'red'), "$testMsg<b>ERROR! - PEAR does not appear to be installed in your PHP configuration. Please install it.</b> 
			<a target='_blank' style='text-decoration:underline;' href='http://pear.php.net/package/PEAR/download'>Download PEAR here.</a>
			If you feel confident that PEAR *is* installed, then perhaps it has been installed incorrectly or you may not have the 
			correct path set for PEAR for 'include_path' in your PHP.INI file."));
} else {
	$testMsg .= " <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - PEAR is installed.<br>";
}
if (@ !include 'Auth.php') {
	exit (RCView::div(array('class'=>'red'), "$testMsg<b>ERROR! - The PEAR Auth package could not be found. Please install it.</b>
			<a target='_blank' style='text-decoration:underline;' href='http://pear.php.net/package/Auth/download'>Download PEAR Auth here.</a>
			If you feel confident that PEAR Auth *is* installed, then perhaps it has been installed incorrectly. You may want to try reinstalling it."));
} else {
	$testMsg .= " <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - The PEAR Auth package is installed.<br>";
}
$pearDBLink = "<a target='_blank' style='text-decoration:underline;' href='http://pear.php.net/package/DB/download'>Download PEAR DB here.</a>";
if (@ !include 'DB.php') {
	exit (RCView::div(array('class'=>'red'), "$testMsg<b>ERROR! - The PEAR DB package could not be found. Please install it.</b> 
		$pearDBLink If you feel confident that PEAR Auth *is* installed, then perhaps it has been installed incorrectly. You may want to try reinstalling it."));
} elseif (version_compare(DB::apiVersion(), '1.7.14', '<')) {
	$testMsg .= RCView::div(array('class' => 'red'), "<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'>
		<b>PEAR DB version &gt;= 1.7.14 - CRITICAL</b>: your version of the PEAR DB package (" . DB::apiVersion() . ") may
		contain bugs. Please upgrade it. $pearDBLink");
} else {
	$testMsg .= " <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - The PEAR DB package is installed.<br>";
}
print RCView::div(array('class'=>'darkgreen','style'=>'color:green;'), $testMsg);



## Check if cURL is installed
$testMsg = "<b>TEST 4: Check if PHP cURL extension is installed</b><br><br>";
// cURL is installed
if (function_exists('curl_init')) 
{
	print RCView::div(array('class'=>'darkgreen','style'=>'color:green;'), $testMsg." <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - The cURL extension is installed.<br>");
}
// cURL not installed
else
{
	?>
	<div class="yellow">
		<?php echo $testMsg ?>
		<img src="<?php echo APP_PATH_IMAGES ?>exclamation_orange.png" class="imgfix">
		<b>Your web server does NOT have the PHP library cURL installed.</b> cURL is NOT necessary to run REDCap normally, but it is highly 
		recommended. cURL is used for some optional functionality in REDCap, such as in the REDCap Shared Library, in the 
		Graphical Data View & Stats module, and is used when reporting site stats using the "automatic reporting" method. 
		To use cURL in REDCap, you will need to download cURL/libcurl, and then install and configure it with PHP on your web server. You will find 
		<a href='http://curl.haxx.se/libcurl/php/' target='_blank' style='font-family:arial;text-decoration:underline;'>instructions for cURL/libcurl installation here</a>. 
		Other documentation on cURL can be found at 
		<a href='http://us.php.net/manual/en/book.curl.php' target='_blank' style='font-family:arial;text-decoration:underline;'>http://us.php.net/manual/en/book.curl.php</a>.
	</div>
	<?php
	// If cURL is not installed AND allow_url_fopen is not enabled, then will not be able make outside requests
	if (ini_get('allow_url_fopen') != '1')
	{
		?>
		<div class="red">
			<img src="<?php echo APP_PATH_IMAGES ?>exclamation.png" class="imgfix"> 
			<b>Your web server also does NOT have the PHP setting "allow_url_fopen" enabled.</b> 
			Without cURL installed AND without "allow_url_fopen" being enabled, REDCap will not be able to perform certain processes. 
			It is highly recommended that you either install cURL (as described above) or at least enable "allow_url_fopen" on the web server.
			To enable "allow_url_fopen", simply open your web server's PHP.INI file for editing and change the value of "allow_url_fopen" to 
			<b>On</b>. Then reboot your web server.
		</div>
		<?php
	}
}



## Check if can communicate with REDCap Consortium server (for reporting stats)
$testMsg = "<b>TEST 5: Checking communication with REDCap Consortium server</b> (".CONSORTIUM_WEBSITE.")<br>
			(used to report weekly site stats and connect to Shared Library)<br><br>";
// Send request to consortium server using cURL via an ajax request (in case it loads slowly)
?>
<div id="server_ping_response_div_parent" class="grayed" style="color:#333;background:#eee;">
	<?php echo $testMsg ?>
	<div id="server_ping_response_div">
		<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix"> 
		<b>Communicating with server... please wait</b>
	</div>
</div>
<script type="text/javascript">
var resp = "<b>FAILED!</b> - Could NOT communicate with the REDCap Consortium server. "
		 + "You will NOT be able to report your institutional REDCap statistics using the \"automatic reporting\" method, but you may try "
		 + "the \"manual reporting\" method instead (see the General Configuration page in the Control Center for this setting).";
var respClass = 'red';
var respColor = '#800000';
$(function(){
	// Ajax request
	var thisAjax = $.get(app_path_webroot+'Test/server_ping.php', { }, function(data) {
		if (data.length > 0 && data == "1") {
			resp = "<img src='"+app_path_images+"tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - Communicated successfully to the REDCap Consortium server. "
					 + "You WILL be able to use the \"automatic reporting\" method to report your site stats, as well as use the REDCap Shared Library.";
			respClass = 'darkgreen';
			respColor = 'green';
		}
		$('#server_ping_response_div').html(resp);
		$('#server_ping_response_div_parent').removeClass('grayed').css('background','').addClass(respClass).css('color',respColor);
	});
	// Check after 10s to see if communicated with server, in case it loads slowly. If not after 10s, then assume cannot be done.
	var resptimer = resp;
	var maxAjaxTime = 10; // seconds
	setTimeout(function(){
		if (thisAjax.readyState == 1) {
			thisAjax.abort();
			$('#server_ping_response_div').html(resptimer);
			$('#server_ping_response_div_parent').removeClass('grayed').css('background','').addClass(respClass).css('color',respColor);
		}
	},maxAjaxTime*1000);
});
</script>
<?php




## Check if REDCap Cron Job is running
$testMsg = "<b>TEST 6: Check if REDCap Cron Job is running</b><br><br>";
if (Cron::checkIfCronsActive()) {
	print RCView::div(array('class'=>'darkgreen','style'=>'color:green;'), $testMsg." <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b>SUCCESSFUL!</b> - REDCap Cron Job is running properly.<br>");
} else {
	print RCView::div(array('class'=>'red'), 
		$testMsg .
		RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) . 
		RCView::b($lang['control_center_288']) . RCView::br() . $lang['control_center_289'] . RCView::br() . RCView::br() . 
		RCView::a(array('href'=>'javascript:;','style'=>'font-family:arial;','onclick'=>"window.location.href=app_path_webroot+'ControlCenter/cron_jobs.php';"), $lang['control_center_290'])
	);
}







/**
 * SECONDARY TESTS
 */
print "<p style='padding-top:15px;color:#800000;font-weight:bold;font-family:verdana;font-size:13px;'>Secondary tests</p>";


// Check for SSL
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
	print "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b style='color:green;'>Using SSL</b></div>";
} else {
	print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>NOT using SSL 
			- CRITICAL:</b> It is HIGHLY recommended that you use SSL (i.e. https) on your web server when hosting REDCap. Otherwise, 
			data security could be compromised. If your server does not already have an SSL certificate, you will need to obtain one.</div>";
}
// Check for PHP 5
if (version_compare(PHP_VERSION, '5.0.0', '>=')) {
	print "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b style='color:green;'>Using PHP 5</b></div>";
} else {
	print "<div class='yellow'><img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> <b>NOT using PHP 5 
			- RECOMMENDED:</b> It is recommended that you upgrade your web server to PHP 5 (you are currently running PHP ".PHP_VERSION.").
			Some functionality within REDCap may not be functional on PHP versions prior to PHP 5.</div>";
}
// Check for MySQL 5
$q = db_query("select version()");
$mysql_version = db_result($q, 0);
if (substr($mysql_version, 0, 1) >= 5) {
	print "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b style='color:green;'>Using MySQL 5</b></div>";
} else {
	print "<div class='yellow'><img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> <b>NOT using MySQL 5 
			- RECOMMENDED:</b> It is recommended that you upgrade your database server to MySQL 5 (you are currently running MySQL $mysql_version).
			Some functionality within REDCap may not be functional on MySQL versions prior to MySQL 5.</div>";
}

// Check max_input_vars for PHP 5.3.9+ (although it's been seen on PHP 5.3.3 - how?)
$max_input_vars = ini_get('max_input_vars');
$max_input_vars_min = 10000;
if (is_numeric($max_input_vars) && $max_input_vars < $max_input_vars_min) 
{
	// Give recommendation to increase max_input_vars
	print  "<div class='yellow'>
				<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
				<b>'max_input_vars' could be larger - RECOMMENDED:</b> 
				It is highly recommended that you change your value for 'max_input_vars' in your PHP.INI configuration file to 
				a value of $max_input_vars_min or higher. If not increased, then REDCap might not be able to successfully save data when entered on a very long survey
				or data entry form.	You can modify this setting in your server's PHP.INI configuration file.
				If 'max_input_vars' is not found in your PHP.INI file, you should add it as <i style='color:#800000;'>max_input_vars = $max_input_vars_min</i>.
				Once done, restart your web server for the changes to take effect.
			</div>";
}

// Make sure 'max_allowed_packet' is large enough in MySQL
$sql = "show variables like 'max_allowed_packet'";
$q = db_query($sql);
if ($q && db_num_rows($q) == 1) {
	$row = db_fetch_assoc($q);
	if ($row['Value'] <= 2097152) { // <=2MB
		print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>'max_allowed_packet' is too small:</b> 
				It is HIGHLY recommended that you change your value for 'max_allowed_packet' in MySQL to a higher value, preferably 
				greater than 10MB (i.e. 10485760). You can modify this in your MY.CNF file (or MY.INI for Windows), then restart MySQL.
				At such a small value, your users will likely have issues uploading files into REDCap's File Repository module.</div>";
	} elseif ($row['Value'] <= 10485760) { // <=10MB
		print "<div class='yellow'><img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> <b>'max_allowed_packet' could be larger:
				- RECOMMENDED:</b> It is recommended that you change your value for 'max_allowed_packet' in MySQL to a higher value, preferably 
				greater than 10MB (i.e. 10485760). You can modify this in your MY.CNF configuration file (or MY.INI for Windows), then restart MySQL.
				At such a small value, your users will likely have issues uploading files into REDCap's File Repository module.</div>";
	}
}

// Make sure 'upload_max_filesize' and 'post_max_size' are large enough in PHP so files upload properly
$maxUploadSize = maxUploadSize();
if ($maxUploadSize <= 2) { // <=2MB
	print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
			<b>'upload_max_filesize' and 'post_max_size' are too small:</b> 
			It is HIGHLY recommended that you change your value for both 'upload_max_filesize' and 'post_max_size' in PHP to a higher value, preferably 
			greater than 10MB (e.g. 32M). You can modify this in your server's PHP.INI configuration file, then restart your web server.
			At such small values, your users will likely have issues uploading files if you do not increase these.</div>";
} elseif ($maxUploadSize <= 10) { // <=10MB
	print "<div class='yellow'><img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
			<b>'upload_max_filesize' and 'post_max_size' could be larger
			- RECOMMENDED:</b> It is recommended that you change your value for both 'upload_max_filesize' and 'post_max_size' in PHP to a higher value, preferably 
			greater than 10MB (e.g. 32M). You can modify this in your server's PHP.INI configuration file, then restart your web server.
			At such small values, your users could potentially have issues uploading files if you do not increase these.</div>";
}

// Check if the PDF UTF-8 fonts are installed (otherwise cannot render special characters in PDFs)
$pathToPdfUtf8Fonts = APP_PATH_WEBTOOLS . "pdf" . DS . "font" . DS . "unifont" . DS;
if (!is_dir($pathToPdfUtf8Fonts)) 
{
	print  "<div class='yellow'>
				<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
				<b>Missing UTF-8 fonts for PDF export - RECOMMENDED:</b> 
				In REDCap version 4.5.0, the capability was added for rendering special UTF-8 characters in PDF files
				exported from REDCap. This feature is not necessary but is good to have, especially for any international projects
				that might want to enter data or create field labels using special non-English characters. 
				Without this feature installed, some special characters might appear jumbled and unreadable in a PDF export.
				In order to utilize this capability, the UTF-8 fonts must be installed in REDCap. To do this, simply 
				<a href='https://iwg.devguard.com/trac/redcap/browser/misc/webtools2-pdf.zip?format=raw'>download the zip file of the UTF-8 fonts here</a>, 
				and then extract the contents of the zip file into the /webtools2 subfolder in the main REDCap folder on your web server. 
				The file structure should then be /webtools2/pdf/fonts/unifont. Overwrite any existing files or folders there.
				In addition, to utilize this feature, you must also have the 
				<a href='http://www.php.net/manual/en/mbstring.setup.php' target='_blank'>PHP extension \"mbstring\"</a> 
				installed on your web server. If not installed, install it, then reboot your web server.
			</div>";
}

// Must have PHP extension "mbstring" installed in order to render UTF-8 characters properly
if (!function_exists('mb_convert_encoding'))
{
	print  "<div class='yellow'>
				<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
				<b>PHP extension \"mbstring\" not installed - RECOMMENDED:</b> 
				This extension is not necessary for REDCap but is good to have, especially for any international projects
				that might want to enter data or create field labels using special non-English characters. 
				Without this extension installed, some special characters might appear jumbled and unreadable in a PDF export.
				To utilize this feature, you must install the 
				<a href='http://www.php.net/manual/en/mbstring.setup.php' target='_blank'>PHP extension \"mbstring\"</a> 
				on your web server. Once installed, reboot your web server.
			</div>";
}

// Make sure 'innodb_buffer_pool_size' is large enough in MySQL
$q = db_query("SHOW VARIABLES like 'innodb_buffer_pool_size'");
if ($q && db_num_rows($q) > 0)
{
	while ($row = db_fetch_assoc($q)) {
		$innodb_buffer_pool_size = $row['Value'];
	}	
	$total_mysql_space = 0;
	$q = db_query("SHOW TABLE STATUS from `$db` like 'redcap_%'");
	while ($row = db_fetch_assoc($q)) {
		if (strpos($row['Name'], "_20") === false) { // Ignore timestamped archive tables
			$total_mysql_space += $row['Data_length'] + $row['Index_length'];
		}	
	}
	// Set max buffer pool size that anyone would probably need
	$innodb_buffer_pool_size_max_neccessary = 1*1024*1024*1024; // 1 GB
	// Compare
	if ($innodb_buffer_pool_size <= ($innodb_buffer_pool_size_max_neccessary*0.95) && $innodb_buffer_pool_size < ($total_mysql_space*1.1))
	{
		// Determine severity (red/severe is < 20% of total MySQL space)
		$class = ($innodb_buffer_pool_size < ($total_mysql_space*.2)) ? "red" : "yellow";
		$img   = ($class == "red") ? "exclamation.png" : "exclamation_orange.png";
		// Set recommend pool size
		$recommended_pool_size = ($total_mysql_space*1.1 < $innodb_buffer_pool_size_max_neccessary) ? $total_mysql_space*1.1 : $innodb_buffer_pool_size_max_neccessary;
		// Give recommendation
		print "<div class='$class'><img src='".APP_PATH_IMAGES."$img' class='imgfix'> <b>'innodb_buffer_pool_size' could be larger
				- RECOMMENDED:</b> It is recommended that you change your value for 'innodb_buffer_pool_size' in MySQL to a higher value.
				It is generally recommended that it be set to 10% larger than the size of your database, which is currently
				".round($total_mysql_space/1024/1024)."MB in size. So ideally <b>'innodb_buffer_pool_size' 
				should be set to at least ".round($recommended_pool_size/1024/1024)."MB</b> if possible 
				(it is currently ".round($innodb_buffer_pool_size/1024/1024)."MB). 
				Also, it is recommended that the size of 'innodb_buffer_pool_size' <b>not exceed 80% of your total RAM (memory)
				that is allocated to MySQL</b> on your database server.
				You can modify this in your MY.CNF configuration file (or MY.INI for Windows), then restart MySQL.
				If you do not increase this value, you may begin to see performance issues in MySQL.</div>";
	}
}


// Make sure magic_quotes in PHP is turned on
if (get_magic_quotes_runtime()) {
	print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>'magic_quotes_runtime' should be turned off:</b> 
			It is HIGHLY recommended that you change your value for 'magic_quotes_runtime' in PHP to 'Off'. 
			You can modify this in your server's PHP.INI configuration file, then restart your web server. REDCap will not function correctly with
			'magic_quotes_runtime' set to 'On'.</div>";
}

// Check if /temp is writable
$temp_dir = dirname(APP_PATH_DOCROOT) . DS . "temp" . DS;
// Try to make it writable, if it's not
if (!isDirWritable($temp_dir)) {
	@chmod($temp_dir, 0777);
	@system("chmod 777 $temp_dir");
}
if (isDirWritable($temp_dir)) {
	print "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b style='color:green;'>\"temp\" folder is writable (location: $temp_dir)</b></div>";
} else {
	print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>\"temp\" folder is NOT writable
			- CRITICAL:</b> It is HIGHLY recommended that you modify the REDCap \"temp\" folder (located at $temp_dir) so that it is
			writable for all server users. Some functionality within REDCap will not be functional until this folder is writable.</div>";
}

// Check if /edocs is writable
$edocs_dir = EDOC_PATH;
// Try to make it writable, if it's not
if (!isDirWritable($edocs_dir)) {
	@chmod($edocs_dir, 0777);
	@system("chmod 777 $edocs_dir");
}
if (isDirWritable($edocs_dir)) {
	print "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> <b style='color:green;'>\"edocs\" folder is writable (location: ".EDOC_PATH.")</b></div>";
} else {
	print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>\"edocs\" folder is NOT writable
			- CRITICAL:</b> It is HIGHLY recommended that you modify the REDCap \"edocs\" folder (located at $edocs_dir) so that it is 
			writable for all server users. Some functionality within REDCap will not be functional until this folder is writable.</div>";
}

// Check if using default .../redcap/edocs/ folder for file uploads (not recommended)
if (!$edoc_storage_option && trim($edoc_path) == "")
{
	print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
			<b>Directory that stores user-uploaded documents is exposed to the web:</b><br> 
			It is HIGHLY recommended that you change your location where user-uploaded files are stored. 
			Currently, they are being stored in REDCap's \"edocs\" directory, which is the default location and is completely accessible to the web.
			Although it is extremely unlikely that anyone could successfully retrieve a file from that location on the server via the web,
			it is still a potential security risk, especially if the documents contain sensitive information. 
			<br><br>
			It is recommend that you go to the Modules page in the Control Center and set a new path for your user-uploaded documents 
			(i.e. \"Enable alternate internal storage of uploaded files rather than default 'edocs' folder\"), and set it to
			a path on your web server that is NOT accessible from the web. Once you have
			changed that value, go to the 'edocs' directory and copy all existing files in that folder to the new location you just set.
			</div>";
}


// Check for test/install/upgrade.php files and recommend to move/rename
$check_file = APP_PATH_DOCROOT . "install.php";
if (is_file($check_file)) {
	print "<div class='yellow'><img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> <b>\"install.php\" is exposed
			- RECOMMENDED:</b> For security reasons, you may want to delete, rename, or move the file \"install.php\" (located at
			$check_file) because it is freely accessible to anyone over the web and exposes some information about your database table
			structure. This file will not be used by REDCap in the future.</div>";
}
$check_file = APP_PATH_DOCROOT . "Test" . DS . "index.php";
if (is_file($check_file)) {
	print "<div class='yellow'><img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> <b>\"Test/index.php\" is exposed
			- RECOMMENDED:</b> For security reasons, you may want to rename or remove the current page \"Test/index.php\" (located at
			$check_file) because it is freely accessible to anyone over the web and exposes some information about your server
			configuration. This file should only be used temporarily for diagnostic use. You may rename it (e.g. test_3res7fa.php) or move it elsewhere on the
			server, and if needed again, it can be renamed or moved back to its current location.<br><br>
			<b>NOTE:</b> Because you are currently on the page \"Test/index.php\", you will want to remove/rename it only AFTER you are finished
			viewing this page.
			</div>";
}






/** 
 * CONGRATULATIONS!
 */
print  "<p><br><hr><p><h3 style='font-size:20px;color:#800000;font-family:Arial;'>
		<img src='".APP_PATH_IMAGES."star.png'> CONGRATULATIONS! <img src='".APP_PATH_IMAGES."star.png'></h3>
		<p><b>It appears that the REDCap software has been correctly installed/upgraded and configured on your system. ";

print  "It is ready for use.</b>
		You may begin using REDCap by first visiting the REDCap home page at the link below. 
		(It may be helpful to bookmark this link.)";

print  "<div class='blue' style='padding:10px;'>
		<b>REDCap home page:</b>&nbsp;
		<a style='text-decoration:underline;' target=\"_blank\" href=\"".APP_PATH_WEBROOT_FULL."\">".APP_PATH_WEBROOT_FULL."</a>
		</div>";

// Check global auth_meth value
if ($auth_meth_global == "none")
{
	print "<p><b>Currently, REDCap's global pages (e.g. Home page, My Projects page, Control Center) are using the authentication method \"None\"</b>, 
	which is utilized solely by a generic user named \"<b>site_admin</b>\". This authentication method is best to use if you are using a
	development server or if you have not yet worked out all issues with user authentication on your system. Once you have your site's authentication working
	properly, you may go into the Control Center to the Security & Authentication page to change the 
	authentication type (\"<b>Authentication Method for Global Pages</b>\") to the one you will be implementing on 
	your system (i.e. LDAP, Table-based, RSA SecurID, LDAP/Table combination, Shibboleth). You may also want to change
	the default authentication type for all newly created projects (\"<b>Authentication Method for Project</b>\") to the same value as the 
	global value on the Security & Authentication page. You may change this value for existing projects by navigating to the Edit Project Settings page in the Control Center.
	<b>If you decide to switch from \"None\" authentication to \"Table-based\"</b>, 
	please be sure to add yourself as a new Table-based user (on the User Control tab in the Control Center)
	before you switch over the authentication method, otherwise you won't be able to log in.";
} 
else 
{
	print "<p><b>Currently, REDCap's global pages (e.g. Home page, My Projects page, Control Center) are using the authentication method \"$auth_meth_global\"</b>. 
	If you have just installed REDCap for the first time and the REDCap Home page will not load, it may be a problem with your authentication. 
	If you are using LDAP authentication in this case, you may want to recheck your LDAP configuration settings in /webtools2/ldap/ldap_config.php";
}

print  "<p><b>NOTE:</b> If you are unable to successfully load the REDCap home page or project-level pages, 
		it is most likely that a problem exists with how you are authenticating REDCap users on your system, 
		such as an incorrect LDAP configuration. If this is the case and you are unable to get REDCap working with 
		authentication after several attempts, please consult another member of the REDCap consortium for help with this.";



print "<br><br><br><br></font>";	

$objHtmlPage->PrintFooter();