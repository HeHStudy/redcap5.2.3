<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Disable authentication
define("NOAUTH", true);
// Call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
// Compare current version to version number passed in query string
print (isset($_GET['version']) && $redcap_version == $_GET['version']) ? '1' : '0';