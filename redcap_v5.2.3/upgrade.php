<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/********************************************************************************************
This file is used for upgrading to newer versions of REDCap.
It may be used for cumulative upgrading so that incremental updates can be done all at once.
The page will guide you through the upgrade process.
********************************************************************************************/


// File with necessary functions
require_once dirname(__FILE__) . '/Config/init_functions.php';
// Change initial server value to account for a lot of processing and memory
ini_set('max_execution_time', '3600');
// Get the install version number and set the web path
if (isset($upgrade_to_version)) {
	define("APP_PATH_WEBROOT", "redcap_v" . $upgrade_to_version . "/");
} else {
	if (basename(dirname(__FILE__)) == "codebase") {
		// If this is a developer with 'codebase' folder instead of version folder, then use JavaScript to get version from query string instead
		if (isset($_GET['version'])) {
			$upgrade_to_version = $_GET['version'];
		} else {
			// Redirect via JavaScript
			?>
			<script type="text/javascript">
			var urlChunks = window.location.href.split('/').reverse();
			window.location.href = window.location.href+'?version='+urlChunks[1].substring(8);
			</script>
			<?php
			exit;
		}
	} else {
		// Get version from above directory
		$upgrade_to_version = substr(basename(dirname(__FILE__)), 8);
	}
	define("APP_PATH_WEBROOT", "");	
}
// Set version to standard variable
$redcap_version = $upgrade_to_version;
// Declare current page with full path
define("PAGE_FULL", 			$_SERVER['PHP_SELF']);
// Declare current page
define("PAGE", 					basename(PAGE_FULL));
// Docroot will be used by php includes
define("APP_PATH_DOCROOT", 		dirname(__FILE__) . "/");
// Webtools folder path
define("APP_PATH_WEBTOOLS",		dirname(APP_PATH_DOCROOT) . "/webtools2/");
// Object classes
define("APP_PATH_CLASSES",  	APP_PATH_DOCROOT . "Classes/");
// Image repository
define("APP_PATH_IMAGES",		APP_PATH_WEBROOT . "Resources/images/");
// CSS
define("APP_PATH_CSS",			APP_PATH_WEBROOT . "Resources/css/");
// External Javascript
define("APP_PATH_JS",			APP_PATH_WEBROOT . "Resources/js/");
// Make initial connection to MySQL project
db_connect();

// Get timestamp
$timestamp = date("_Y_m_d_H_i_s");

// Add global $html variable that can be utilized by PHP upgrade files for outputting text, javascript, etc. to page
$html = "";

// Get current version number from redcap_config
$current_version = db_result(db_query("select value from redcap_config where field_name = 'redcap_version' limit 1"), 0);

// Initialize page display object
require_once APP_PATH_CLASSES . 'HtmlPage.php';;
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();

// Page header with logo
print  "<table width=100% cellpadding=0 cellspacing=0>
			<tr>
				<td valign='top' style='padding:20px 0;font-size:20px;font-weight:bold;color:#800000;'>
					REDCap $redcap_version Upgrade Module
				</td>
				<td valign='top' style='text-align:right;padding-top:5px;'>
					<img src='" . APP_PATH_IMAGES . "redcaplogo_small.gif'>
				</td>
			</tr>
		</table>";

// System must be on version 3.0.0 in order to upgrade to this one
if (str_replace(".", "", getLeadZeroVersion($current_version)) < 30000) {
	exit("<p><br><b>Unable to upgrade!</b><br>
		  You are currently on version $current_version. 
		  You must be on REDCap version 3.0.0 or higher in order to upgrade to $redcap_version.<br>
		  Upgrade to 3.0.0 first, then you may upgrade to $redcap_version.<br><br></p>");
}

// If the system has already been upgraded to this version, then stop here and give link back to REDCap.
if ($current_version == $redcap_version) {
	exit("<p><br><b>Already upgraded!</b><br>
		  It appears that you have already upgraded to version $current_version. There is nothing to do here. 
		  <a href='" . APP_PATH_WEBROOT . "index.php' style='text-decoration:underline;font-weight:bold;'>Return to REDCap</a>
		  <br><br></p>");
}

// Do repeated ajax calls every 5 seconds until upgrade is finished via MySQL so that we can remind 
// them to then go to the Configuration Test page.
?>
<script type="text/javascript">
function checkVersionAjax(version) {
	$.get(app_path_webroot+'Test/check_upgrade.php',{ version: version},function(data){
		if (data=='1') {
			$('#goToConfigTest').dialog({ bgiframe: true, modal: true, width: 500, zIndex: 4999, close: function(){ goToConfigTest() }, buttons: {
				'Go to Configuration Test page': function() {
					goToConfigTest();
				}
			} });				
		} else {
			setTimeout("checkVersionAjax('"+version+"')",5000);
		}
	});
}
function goToConfigTest() {
	window.location.href = app_path_webroot+'Test/index.php';
}
$(function(){
	setTimeout("checkVersionAjax('<?php echo $upgrade_to_version ?>')",5000);
});
</script>

<!-- Hidden div to tell user to go to Config Test after the upgrade -->
<p id="goToConfigTest" style="display:none;" title="<img src='<?php echo APP_PATH_IMAGES ?>tick.png' class='imgfix'> <span style='color:green;'>Upgrade Complete!</span>">
	It appears that your upgrade to REDCap <?php echo $upgrade_to_version ?> was successful! As the final step
	of the upgrade process, please navigate to the Configuration Test page to make sure there is nothing
	else that needs to be done.
</p>
<?php

// Get time of auto logout from redcap_config
$autologout_timer = db_result(db_query("select value from redcap_config where field_name = 'autologout_timer' limit 1"), 0);
if ($autologout_timer == "" || $autologout_timer == "0") $autologout_timer = "30";

// Instructions
print  "<p style='margin:25px 0;padding-top:12px;border-top:1px solid #aaa;'>
			<b>1.) PREPARATION:</b><br>
			Approximately $autologout_timer minutes before upgrading, go into the Control Center's 
			<a href='".APP_PATH_WEBROOT."ControlCenter/general_settings.php' target='_blank' style='text-decoration:underline;'>General Configuration page</a> 
			and set the System Status as \"System Offline\", which will take all REDCap projects offline and allow users 
			to save any data before exiting. (When you are done upgrading, REDCap will remind you to bring the system back online.)
		</p>";


// LANGUAGE CHECK: Check if using any non-English languages. Make sure language files exist and remind them to update their language files.
$usesOtherLanguages = false; // Default
// Account for the project_language field changing from INT to VARCHAR in v3.2.0 (0 = English)
$langValNumeric = (getDecVersion($current_version) < 30200);
$englishValue   = ($langValNumeric) ? '0' : 'English';
// Check if using non-English in any projects or as project default
$languagesUsed = array();
$qconfig = db_query("select value from redcap_config where field_name = 'project_language' and value != '$englishValue'");
$configNonEnglish = db_num_rows($qconfig);
$qprojects = db_query("select distinct project_language from redcap_projects where project_language != '$englishValue'");
$projectsNonEnglish = db_num_rows($qprojects);
if (($configNonEnglish + $projectsNonEnglish) > 0)
{
	// Create list of languages used
	while ($row = db_fetch_assoc($qconfig)) 	 $languagesUsed[] = $row['value'];
	while ($row = db_fetch_assoc($qprojects)) $languagesUsed[] = $row['project_language'];
	$languagesUsed = array_unique($languagesUsed);
	// If currently on version before 3.2.0, transform numeric values into varchar equivalents
	if ($langValNumeric)
	{
		foreach ($languagesUsed as $key=>$val)
		{
			$languagesUsed[$key] = ($val == '1') ? 'Spanish' : 'Japanese';
		}
	}
	// Make sure language files exist for languages used
	$languageFiles = getLanguageList();
	unset($languageFiles['English']);
	// Only show section if other languages are actually being utilized
	if (!empty($languagesUsed))
	{
		// Language file directory
		$langDir = dirname(APP_PATH_DOCROOT) . DS . "languages" . DS;
		print  "<b>Language check:</b><br>
				It appears that you are using one or more non-English languages in your installation of REDCap.
				The translation of REDCap's English text into other languages is now supported solely through the REDCap Consortium
				community. For more information on this, please see the  
				<a href='https://iwg.devguard.com/trac/redcap/wiki/Languages' target='_blank' style='text-decoration:underline;'>REDCap wiki Language Center</a>.
				<br><br>
				REDCap stores the language files in 
				the following location on your server: <b>$langDir</b>. A diagnostic of your language files is given below, showing if the file
				can be located, and if it needs to be updated because of any new language variables added in REDCap version $redcap_version.
				<b>If any files are out of date, you can update them using the 
				<a href='".APP_PATH_WEBROOT."LanguageUpdater/' target='_blank' style='text-decoration:underline;'>Language File Creator/Updater</a>
				page</b>, OR you may check the wiki Language Center for any updated versions of these languages. 
				<b>NOTE:</b> It will not harm REDCap if a language file is out of date; it will merely show English text in place of 
				the missing translated text. If you wish, you may translate any new language variables in your language files 
				before performing this upgrade by following the instructions on the Language File Creator/Updater page and then returning
				to this page afterward.<br>";
		// Check if directory exists
		if (!is_dir($langDir))
		{
			print  "<img src='".APP_PATH_IMAGES."cross.png' class='imgfix'> 
					<span style='color:#red;'><b>ERROR!</b> Could not find the \"languages\" folder at the expected path: $langDir</span><br>";
		}
		// Get array of English language
		$English = callLanguageFile('English');
		// Loop through all and check if each INI file exists
		foreach (array_unique(array_merge(array_keys($languageFiles), $languagesUsed)) as $this_lang)
		{
			if (isset($languageFiles[$this_lang])) 
			{
				// Found the file, so now check to see if it's up to date
				$untranslated_strings = count(array_diff_key($English, callLanguageFile($this_lang)));
				if ($untranslated_strings < 1) {
					print  "<img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> 
							<span style='color:green;'><b>$this_lang.ini</b> is up to date.</span><br>";
				} else {
					print  "<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
							<span style='color:#800000;'><b>$this_lang.ini</b> is out of date. 
							Recommendation: $untranslated_strings new language variables 
							need to be added to this file and then translated.</span><br>";
				}
			}
			else
			{
				// Could not find the language file
				print  "<img src='".APP_PATH_IMAGES."cross.png' class='imgfix'> 
						<span style='color:#red;'><b>ERROR!</b> 
						Could not find the following language file: <b>" . $langDir . $this_lang. ".ini</b>.
						Without this file, any projects set with \"$this_lang\" as the language will instead render in English.</span><br>";
			}
		}
	}
}


print  "<p style='margin:25px 0;padding-top:12px;border-top:1px solid #aaa;'>
			<b>2.) PERFORMING THE UPGRADE:</b><br>
			After REDCap has been offline for $autologout_timer minutes, refresh this page. <b>Then copy the SQL statements in the box below and execute them on your 
			MySQL database named '$db'</b>. Once you have done so, move on to the next step below.
		</p>";


// Text box for holding the SQL
print  "<div id='sqlloading' style='margin-bottom:140px;width:90%;height:75px;font-size:14px;font-weight:bold;text-align:center;border:1px solid #ccc;background-color:#eee;padding-top:30px;'>
			<div style='padding-bottom:8px;'>Generating the SQL upgrade script...</div>
			<img src='".APP_PATH_IMAGES."progress_bar.gif'>
		</div>";
print "<div id='sqlscript' style='display:none;'>";
print "<textarea style='font-family:Arial;font-size:11px;width:90%;height:100px;' readonly='readonly' onclick='this.select();'>";
print "-- --- SQL to upgrade REDCap to version $redcap_version from $current_version --- --\n";
getUpgradeSql();
print "\n\n-- Set date of upgrade --\n";
print "UPDATE redcap_config SET value = '" . date("Y-m-d") . "' WHERE field_name = 'redcap_last_install_date' LIMIT 1;\n";
print "-- Set new version number --\n";
print "UPDATE redcap_config SET value = '$redcap_version' WHERE field_name = 'redcap_version' LIMIT 1;\n";
print "</textarea>";

// Link to test page
print  "<p style='margin:35px 0;padding-top:12px;border-top:1px solid #aaa;'>
			<b>3.) AFTER THE UPGRADE - TEST YOUR CONFIGURATION:</b><br>
			Once you have successfully executed the SQL from the box above, you may now navigate to the 
			<a href='" . APP_PATH_WEBROOT . "Test/index.php' style='text-decoration:underline;font-weight:bold;'>REDCap Configuration page</a> 
			to ensure that all necessary REDCap components are correctly in place.
		</p>";
// Any custom HTML added from PHP upgrade files
print $html;
// close div
print "</div>";
?>
<script type="text/javascript">
setTimeout(function(){
	document.getElementById('sqlscript').style.display  = 'block';
	document.getElementById('sqlloading').style.display = 'none';
},1000);
</script>
<?php
// Page footer
$objHtmlPage->PrintFooter();




/**
 * FUNCTIONS
 */
// Add leading zeroes inside version number (keep dots)
function getLeadZeroVersion($dotVersion) {
	list ($one, $two, $three) = explode(".", $dotVersion);
	return $one . "." . sprintf("%02d", $two) . "." . sprintf("%02d", $three);
}
// Remove leading zeroes inside version number (keep dots)
function removeLeadZeroVersion($leadZeroVersion) {
	list ($one, $two, $three) = explode(".", $leadZeroVersion);
	return $one . "." . ($two + 0) . "." . ($three + 0);
}
// Add leading zeroes inside version number (remove dots)
function getDecVersion($dotVersion) {
	list ($one, $two, $three) = explode(".", $dotVersion);
	return $one . sprintf("%02d", $two) . sprintf("%02d", $three);
}
// For each version, run any PHP scripts in /Resources/files first, then run raw SQL files in that folder
function getUpgradeSql() {
	global $current_version, $redcap_version, $db, $timestamp;
	$current_version_dec = getDecVersion($current_version);
	$redcap_version_dec  = getDecVersion($redcap_version);
	// Get listing of all files in directory
	$dh = opendir(APP_PATH_DOCROOT."Resources/sql/");
	$files = array();
	while (false !== ($filename = readdir($dh))) { $files[] = $filename; }
	closedir($dh);
	sort($files);
	// Parse through the files and select the ones we need
	$upgrade_sql = array();
	foreach ($files as $this_file) {
		if (substr($this_file, 0, 8) == "upgrade_" && (substr($this_file, -4) == ".sql" || substr($this_file, -4) == ".php")) {			
			$this_file_version = getDecVersion(substr($this_file, 8, -4));
			if ($this_file_version > $current_version_dec && $this_file_version <= $redcap_version_dec) {
				$upgrade_sql[] = $this_file;
			}
		}
	}
	sort($upgrade_sql);
	// Include all the SQL and PHP files to do cumulative upgrade
	foreach ($upgrade_sql as $this_file) {
		print "\n-- SQL for Version " . removeLeadZeroVersion(substr($this_file, 8, -4)) . " (via " . strtoupper(substr($this_file, -3)) . ") --\n";
		include APP_PATH_DOCROOT . "Resources/sql/" . $this_file;
	}
}
