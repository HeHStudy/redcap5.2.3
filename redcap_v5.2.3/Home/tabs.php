<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * RENDER TABS ON HOME PAGE
 */
print  "<br><br>
		<div id='sub-nav' style='margin:0;'>
		<ul>
			<li"; if (!isset($_GET['action']) && strpos(PAGE_FULL, '/index.php') !== false && strpos(PAGE_FULL, 'ControlCenter/') === false) print " class='active'"; print "><a
				href='".APP_PATH_WEBROOT_PARENT."index.php' style='color:#393733;padding:3px 4px 6px 9px;'><img src='" . APP_PATH_IMAGES . "spacer.gif' 
				style='height:16px;width:1px;'>{$lang['home_21']}</a></li>
			<li"; if ($_GET['action'] == 'myprojects') print " class='active'"; print "><a href='".APP_PATH_WEBROOT_PARENT."index.php?action=myprojects' 
				style='color:#393733;padding:3px 4px 6px 9px;'><img src='" . APP_PATH_IMAGES . "spacer.gif' 
				style='height:16px;width:1px;'>{$lang['home_22']}</a></li>";

// If only super users are allowed to create new projects, then prevent normal users from seeing tab to "create new" page
if ($allow_create_db) 
{
	print  "<li"; if ($_GET['action'] == 'create') 	print " class='active'"; print "><a href='".APP_PATH_WEBROOT_PARENT."index.php?action=create' 
			style='color:#393733;padding:3px 4px 6px 9px;'><img src='" . APP_PATH_IMAGES . "add.png' class='imgfix' style='padding-right:2px;'> <font style='color:green;'>";
	print ($superusers_only_create_project && !$super_user) ? $lang['home_23'] : $lang['home_24'];
	print "</font></a></li>";
}

// Training Resources
print  "<li"; 
if ($_GET['action'] == 'training') print " class='active'"; 
print "><a href='".APP_PATH_WEBROOT_PARENT."index.php?action=training' style='color:#393733;padding:3px 4px 6px 9px;'><img 
	src='" . APP_PATH_IMAGES . "video_small.png' class='imgfix' style='padding-right:2px;'> {$lang['home_25']}</a></li>";

// Help & FAQ
print  "<li"; 
if ($_GET['action'] == 'help') print " class='active'"; 
print "><a href='".APP_PATH_WEBROOT_PARENT."index.php?action=help' style='padding:3px 4px 6px 9px;'><img 
	src='" . APP_PATH_IMAGES . "help.png' class='imgfix' style='padding-right:2px;'> <font style='color:#3E72A8;'>{$lang['bottom_27']}</font></a></li>";

// Send-It
if ($sendit_enabled == 1 || $sendit_enabled == 2) {
	print  "<li"; 
	if (PAGE == 'SendIt/upload.php') print " class='active'"; 
	print  "><a href='".APP_PATH_WEBROOT."SendIt/upload.php' style='color:#393733;padding:3px 4px 6px 9px;'><img src='" . APP_PATH_IMAGES . "mail_arrow.png' 
			class='imgfix' style='padding-right:2px;'> {$lang['home_26']}</a></li>";
}

// Control Center
if ($super_user) {
	print  "<li";
	if (strpos(PAGE, 'ControlCenter/') !== false) print " class='active'";
	print  "><a href='".APP_PATH_WEBROOT."ControlCenter/index.php'
			style='color:#393733;padding:3px 4px 6px 9px;'><img src='" . APP_PATH_IMAGES . "spacer.gif' style='height:16px;width:1px;'>{$lang['global_07']}</a></li>";
}

print  "</ul>
		</div>
		<div class='clear' style='padding:2px;'> </div><br>";
