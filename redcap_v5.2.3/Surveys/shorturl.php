<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// URL shortening service
$service = "j.mp";

// Set parameters for URL shortener service
$serviceurl = "http://api.bit.ly/v3/shorten?domain=$service&format=txt&login=projectredcap&apiKey=R_6952a44cd93f2c200047bb81cf3dbb71&longUrl=";
$urlbase	= "http://$service/";


if (checkSurveyProject($_GET['survey_id']) && isset($_GET['hash']))
{
	// Set URL to shorten
	$href = APP_PATH_SURVEY_FULL . '?s=' . $_GET['hash'];
	
	// Retrieve shortened URL from URL shortener service
	$shorturl = trim(http_get($serviceurl . urlencode($href)));
	
	// Ensure that we received a link in the expected format
	if (!empty($shorturl) && substr($shorturl, 0, strlen($urlbase)) == $urlbase) 
	{
		// Output
		exit($shorturl);
	}
}

	
// If failed
exit("0");	