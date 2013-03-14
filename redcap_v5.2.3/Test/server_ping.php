<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Disable authentication
define("NOAUTH", true);
// Call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// Set page to call on consoritium server
$page_to_ping = CONSORTIUM_WEBSITE . 'ping.php';

// Use HTTP Post method
if (isset($_GET['type']) && $_GET['type'] == 'post')
{
	print http_post($page_to_ping);
}

// Use HTTP GET method
else
{
	print http_get($page_to_ping);
}