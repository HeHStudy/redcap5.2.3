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

// Find the highest numbered REDCap version folder in this directory
$files = array();
$dh    = opendir(dirname(__FILE__));
while (($filename = readdir($dh)) !== false) 
{
	if (substr($filename, 0, 8) == "redcap_v") 
	{
		// Store version and numerical represetation of version in array to determine highest
		$this_version = substr($filename, 8);
		list ($v1, $v2, $v3) = explode(".", $this_version, 3);
		$this_version_numerical = sprintf("%02d%02d%02d", $v1, $v2, $v3);
		$files[$this_version_numerical] = $this_version;
	}
}
if (empty($files))
{
	exit("No REDCap directories found. Please install the REDCap software and try again.");
}
// Find the highest numbered key from the array and get its value
ksort($files, SORT_NUMERIC);
$upgrade_to_version = array_pop($files);
// Call the file in the REDCap version directory
include dirname(__FILE__) . DIRECTORY_SEPARATOR . "redcap_v" . $upgrade_to_version . DIRECTORY_SEPARATOR . "upgrade.php";
