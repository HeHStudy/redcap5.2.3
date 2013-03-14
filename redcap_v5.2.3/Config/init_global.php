<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Call file containing basic functions for this config file	
require_once dirname(__FILE__) . '/init_functions.php';
// Define all PHP constants used throughout the application
define_constants();
// Enable GZIP compression for webpages (if Zlib extention is enabled). 
enableGzipCompression();
// Check if the URL is pointing to the correct version of REDCap. If not, redirect to correct version.
check_version();
// Language: Call the correct language file for global pages
$lang = getLanguage($language_global);
// Authenticate the user (use global auth value to authenticate global pages witih $auth_meth variable)
$auth_meth = $auth_meth_global;
$userAuthenticated = Authentication::authenticate();
// Prevent CRSF attacks by checking a custom token
checkCsrfToken();
// Clean up any temporary files sitting on the web server (for various reasons)
remove_temp_deleted_files();
// Check if system has been set to Offline
checkSystemStatus();
// Count this page hit
addPageHit();
// Add this page viewing to log_view table
addPageView();
