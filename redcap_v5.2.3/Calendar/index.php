<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

renderPageTitle("<div style='float:left;'>
					<img src='".APP_PATH_IMAGES."date.png' class='imgfix2'> {$lang['app_08']}
				 </div>
				 <div style='float:right;'>
					<img src='" . APP_PATH_IMAGES . "video_small.png' class='imgfix'> 
					<a onclick=\"window.open('".CONSORTIUM_WEBSITE."videoplayer.php?video=calendar_and_cal_data_entry01.flv&referer=".SERVER_NAME."&title=Calendar','myWin','width=1050, height=800, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1');\" href=\"javascript:;\" style=\"font-size:12px;text-decoration:underline;font-weight:normal;\">".$lang['calendar_01']."</a>
				 </div><br><br>");

print  "<p>".$lang['calendar_02'];

//If multiple events exist, explain how participants may be scheduled and added to calendar
if ($longitudinal && $scheduling) {
	print  $lang['calendar_03']."<a href='".APP_PATH_WEBROOT."Calendar/scheduling.php?pid=$project_id' style='text-decoration:underline;'>".$lang['calendar_04']."</a> "
			. $lang['calendar_05'];
}
print  "</p>";

//If user is in DAG, only show calendar events from that DAG and give note of that
if ($user_rights['group_id'] != "") {
	print  "<p style='color:#800000;'>{$lang['global_02']}: {$lang['calendar_06']}</p>";
}

// IE CSS Hack - Render the following CSS if using IE
if ($isIE) {
	print '<style type="text/css">.toprightnumber {width: 107%}</style>';
}


/**
 * TABS FOR CHANGING CALENDAR VIEW
 */
print "<div id='sub-nav' style='margin-bottom:0;max-width:800px;'><ul>";
//Day
print "<li";
if ($_GET['view'] == "day") print " class='active' ";
print "><a style='font-size:12px;color:#393733;padding:5px 5px 0px 11px;' href='".$_SERVER['PHP_SELF']."?pid=$project_id&view=day";
print appendVarTab();
print "'>".$lang['calendar_07']."</a></li>";
//Week
print "<li";
if ($_GET['view'] == "week") print " class='active' ";
print "><a style='font-size:12px;color:#393733;padding:5px 5px 0px 11px;' href='".$_SERVER['PHP_SELF']."?pid=$project_id&view=week";
print appendVarTab();
print "'>".$lang['calendar_08']."</a></li>";
//Month
print "<li";
if (!isset($_GET['view']) || $_GET['view'] == "month" || $_GET['view'] == "") { print " class='active' "; $_GET['view'] = "month"; }
print "><a style='font-size:12px;color:#393733;padding:5px 5px 0px 11px;' href='".$_SERVER['PHP_SELF']."?pid=$project_id&view=month";
print appendVarTab();
print "'>".$lang['calendar_09']."</a></li>";
//Agenda
print "<li";
if ($_GET['view'] == "agenda") print " class='active' ";
print "><a style='font-size:12px;color:#393733;padding:5px 5px 0px 11px;' href='".$_SERVER['PHP_SELF']."?pid=$project_id&view=agenda";
print appendVarTab();
print "'>".$lang['calendar_10']."</a></li>";
print "</ul></div><br><br><br>";


// Render calendar table
include APP_PATH_DOCROOT . "Calendar/calendar_table.php";

print "<br><br>";

include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';


// Function to add URL variables to tab links
function appendVarTab() {
	$val = "";
	if (isset($_GET['month']) && isset($_GET['year'])) {
		$val .= "&month={$_GET['month']}&year={$_GET['year']}";
	}
	if (isset($_GET['day']) && $_GET['view'] != "month") {
		$val .= "&day={$_GET['day']}";
	}
	return $val;
}
