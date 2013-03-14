<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Turn off error reporting
error_reporting(0); // To turn on error reporting, change to error_reporting(E_ALL);

// Prevent caching
header("Expires: 0");
header("cache-control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");

## SET NECESSARY SERVER-LEVEL SETTINGS
// Set this PHP value, which is used when reading uploaded CSV files
ini_set('auto_detect_line_endings', true);
// Increase memory limit to 512MB in case needed for intensive processing
if (str_replace("M", "", ini_get('memory_limit')) < 512) {
	ini_set('memory_limit', '512M');
}
// Increase initial server value to account for a lot of processing
ini_set('max_execution_time', 1200);
set_time_limit(1200);
// Make sure the character set is UTF-8
ini_set('default_charset', 'UTF-8');

## DEFINE NECESSARY CONSTANTS AND GLOBAL VARIABLES
// Current jQuery UI version
defined("JQUERYUI_VERSION") or define("JQUERYUI_VERSION", "1.8.12");
// Get current date/time to use for all database queries (rather than using MySQL's clock with now())
defined("NOW") 	 or define("NOW", date('Y-m-d H:i:s'));
defined("TODAY") or define("TODAY", date('Y-m-d'));
defined("today") or define("today", TODAY); // The lower-case version of the TODAY constant allows for use in Data Quality rules (e.g. datediff)
// Set time delay (in days) for deleting projects after they have been scheduled for deletion
defined("PROJECT_DELETE_DAY_LAG") or define("PROJECT_DELETE_DAY_LAG", 30);
// Set API key for Google Maps API v3
defined("GOOGLE_MAP_KEY") or define("GOOGLE_MAP_KEY", "AIzaSyCN9Ih8gzAxfPmvijTP8HsE0PAKU8X1Nt0");
// Define DIRECTORY_SEPARATOR as DS for less typing
defined("DS") or define("DS", DIRECTORY_SEPARATOR);
// Detect if a mobile device (don't consider iPad a mobile device)
$isMobileDevice = mobile_device_detect(true, false, true, true, true, true, true, false, false);
// Detect if using iOS (or an iPad specifically)
$isIOS = (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false));
$isIpad = ($isIOS && strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false);
// Detect if the current request is an AJAX call (via $_SERVER['HTTP_X_REQUESTED_WITH'])
$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');	
// Check if using Internet Explorer
$isIE = (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);
// Determine if the web server running PHP is any type of Windows OS (boolean)
$isWindowsServer = ((defined('PHP_OS') && stristr(PHP_OS, 'WIN')) || (stristr(php_uname('s'), 'WIN')));
// Set interval at which REDCap will turn on a listener for any clicking, typing, or mouse movent (used for auto-logout) 
$autologout_resettime = 3; // After X minutes, it will call ProjectGeneral/login_reset.php
// Google Translate anchor text (language abbreviation will be appended to this value)
$googleTransAnchor = '#googtrans/en/';
// Default keywords used for querying identifiers in Identifier Check module
$identifier_keywords_default = "name, street, address, city, county, precinct, zip, postal, date, phone, fax, mail, ssn, "
							 . "social security, mrn, dob, dod, medical, record, id, age";
// Set up array of pages to ignore for logging page views and counting page hits
$noCountPages = array(	
	"DataEntry/auto_complete.php", "DataEntry/search.php", "ControlCenter/report_site_stats.php", "Calendar/calendar_popup_ajax.php", 
	"Reports/report_builder_ajax.php", "Test/index.php", "DataEntry/image_view.php", "ProjectGeneral/project_stats_ajax.php", 
	"SharedLibrary/image_loader.php", "Graphical/plot_gct.php", "Graphical/plot_rapache.php"
);
// List of specific pages where a file is downloaded (rather than a webpage displayed)
$fileDownloadPages = array( "DataExport/data_export_csv.php", "sas_pathway_mapper.php", "spss_pathway_mapper.php",
							"DataImport/import_template.php", "Design/data_dictionary_download.php", "Design/data_dictionary_demo_download.php",
							"FileRepository/file_download.php", "Locking/esign_locking_management.php", "Logging/csv_export.php",
							"Randomization/download_allocation_file.php", "Randomization/download_allocation_file_template.php",
							"Reports/report_export.php", "SendIt/download.php", "Surveys/participant_export.php", "DataEntry/file_download.php", 
							"Graphical/pdf.php/download.pdf", "PDF/index.php"
						  );
// Reserved field names that cannot be used as project field names/variables
$reserved_field_names = array(	
	// These variables are forbidden because they are used internally by REDCap
	"redcap_event_name"=>"Event Name", "redcap_csrf_token"=>"REDCap CSRF Token", 
	"redcap_survey_timestamp"=>"Survey Timestamp", "redcap_survey_identifier"=>"Survey Identifier", 
	"redcap_survey_return_code"=>"Survey Return Code", "redcap_data_access_group"=>"Data Access Group",
	"hidden_edit_flag"=>"hidden_edit_flag",
	// These variables are forbidden because IE throws errors when they are using in branching logic or calculations
	"submit"=>"submit", "new"=>"new", "return"=>"return", "continue"=>"continue", "case"=>"case",
	"class"=>"class", "enum"=>"enum", "catch"=>"catch", "throw"=>"throw", "document"=>"document", "super"=>"super"
);
// Set maximum number of records before the record selection drop-downs disappear on Data Entry Forms
$maxNumRecordsHideDropdowns = 25000;
// Set the HTML tags that are allowed for use in user-defined labels/text (e.g. field labels, survey instructions)
define('ALLOWED_TAGS', '<label><pre><p><a><br><br/><center><font><b><i><u><h3><h2><h1><hr><table><tr><th><td><img><span><div><em><strong><acronym>');

// Set error handlers and session handlers
set_error_handler('myErrorHandler');
register_shutdown_function('session_write_close');
register_shutdown_function('fatalErrorShutdownHandler');
session_set_save_handler("on_session_start", "on_session_end", "on_session_read", "on_session_write", "on_session_destroy", "on_session_gc");

// Make initial database connection
$rc_connection;
db_connect();
// Clean $_GET and $_POST to prevent XSS and SQL injection
cleanGetPost();
// Pull values from redcap_config table and set as global variables
setConfigVals();
