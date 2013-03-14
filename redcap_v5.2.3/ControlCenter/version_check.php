<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require dirname(dirname(__FILE__)) . "/Config/init_global.php";

//If user is not a super user, go back to Home page
if (!$super_user) { redirect(APP_PATH_WEBROOT); exit; }

//Get current version with no decimals (for comparison)
list ($first, $second, $third) = explode(".",$redcap_version);
if (($second*1) < 10) $second = "0" . $second;
if (($third*1) < 10) $third = "0" . $third;
$redcap_version_5digit = ($first . $second . $third)*1;
//Show Upgrade tab is there exists a server folder that you may upgrade to
$dh  = opendir(dirname(dirname(dirname(__FILE__))));
$files = array();
while (false !== ($filename = readdir($dh))) { $files[] = $filename; }
sort($files);
$version_5digit_old = 0;
foreach ($files as $key => $this_version) {
	if (substr($this_version,0,8) == "redcap_v") {
		list ($first, $second, $third) = explode(".",substr($this_version,8,strlen($this_version)));
		if (($second*1) < 10) $second = "0" . $second;
		if (($third*1) < 10) $third = "0" . $third;
		$version_5digit = ($first . $second . $third)*1;
		if ($version_5digit > $version_5digit_old) $version_5digit_old = $version_5digit;
	}
}
$upgrade_to_version = substr($version_5digit_old,0,1) . "." . (substr($version_5digit_old,1,2)*1) . "." . (substr($version_5digit_old,3,2)*1);

// Yes, we need to upgrade
if ($version_5digit_old > $redcap_version_5digit) {
	print  "<img src='".APP_PATH_IMAGES."star.png' >
			<b>{$lang['control_center_61']} v{$upgrade_to_version}!</b><br>
			<a href='".APP_PATH_WEBROOT_PARENT."redcap_v{$upgrade_to_version}/upgrade.php'
			style='text-decoration:underline;'>{$lang['control_center_62']}</a> {$lang['control_center_63']} $upgrade_to_version";
	exit;
// No, no upgrading needed
} else {
	exit('0');
}
