<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Config for non-project pages
require dirname(dirname(__FILE__)) . "/Config/init_global.php";

//If user is not a super user, go back to Home page
if (!$super_user) { redirect(APP_PATH_WEBROOT); exit; }

// determine if we are narrowing our search by latest activity within a given time frame.
// this is delineated by the d $_REQUEST variable
$queryAddendum = NULL;
if (isset($_REQUEST['d']) && $_REQUEST['d']) 
{
	// do a sanity check on the variables to make sure everything is kosher and no URL hacking is going on
	if (is_numeric($_REQUEST['d'])) {
		// Active in...
	   $queryAddendum = " and user_lastactivity is not null and user_lastactivity != '' and user_lastactivity >= '".date('Y-m-d H:i:s',time()-(86400*$_REQUEST['d']))."'";
	} elseif (strpos($_REQUEST['d'], "NA-") !== false) {
		// Not active in...
		list ($nothing, $notactive_days) = explode("-", $_REQUEST['d'], 2);
		if (!is_numeric($notactive_days)) {
			$queryAddendum = NULL;
		} else {
			$queryAddendum = " and (user_lastactivity < '".date('Y-m-d H:i:s',time()-(86400*$notactive_days))."' or user_lastactivity is null or user_lastactivity = '')";
		}
	} elseif ($_REQUEST['d'] == 'I') {
		// Suspended
		$queryAddendum = " and user_suspended_time IS NOT NULL";
	} elseif ($_REQUEST['d'] == 'CL' || $_REQUEST['d'] == 'NCL') {
		// Currently logged in or not
		$logoutWindow = date("Y-m-d H:i:s", mktime(date("H"),date("i")-$autologout_timer,date("s"),date("m"),date("d"),date("Y")));
		$subQueryAddendum = "select distinct v.user from redcap_sessions s, redcap_log_view v 
				where v.user != '[survey respondent]' and v.session_id = s.session_id and v.ts >= '$logoutWindow'";
		if ($_REQUEST['d'] == 'CL') {
			$queryAddendum = " and username in (" . pre_query($subQueryAddendum) . ")";
		} else {
			$queryAddendum = " and username not in (" . pre_query($subQueryAddendum) . ")";
		}
	} elseif (strpos($_REQUEST['d'], "NL-") !== false) {
		// Not logged in within...
		list ($nothing, $notloggedin_days) = explode("-", $_REQUEST['d'], 2);
		if (!is_numeric($notloggedin_days)) {
			$queryAddendum = NULL;
		} else {
			$subQueryAddendum = "select distinct user from redcap_log_view where ts > '".date('Y-m-d H:i:s',time()-(86400*$notloggedin_days))."' and user != 'USERID' and user != '[survey respondent]'";
			$queryAddendum = " and username not in (" . pre_query($subQueryAddendum) . ")";
		}
	} elseif (strpos($_REQUEST['d'], "L-") !== false) {
		// Logged in within...
		list ($nothing, $loggedin_days) = explode("-", $_REQUEST['d'], 2);
		if (!is_numeric($loggedin_days)) {
			$queryAddendum = NULL;
		} else {
			$subQueryAddendum = "select distinct user from redcap_log_view where ts > '".date('Y-m-d H:i:s',time()-(86400*$loggedin_days))."' and user != 'USERID' and user != '[survey respondent]'";
			$queryAddendum = " and username in (" . pre_query($subQueryAddendum) . ")";
		}
	} else {
		$queryAddendum = NULL;
	}
}


// Retrieve list of users
$userList = array();

$dbQuery = "select * from redcap_user_information where username != '' ".$queryAddendum." order by username";
$q = db_query($dbQuery);
$numUsers = db_num_rows($q);
$tickImg = "<img src='" . APP_PATH_IMAGES . "tick_small2.png'>";
while ($row = db_fetch_assoc($q)) 
{
	$row['username'] = strtolower(trim($row['username']));
	$userList[] = array("<a onclick=\"view_user('{$row['username']}')\" href='javascript:;' style='font-size:11px;color:#800000;'>{$row['username']}</a>",
						$row['user_firstname'], 
						$row['user_lastname'], 
						"<a href='mailto:{$row['user_email']}' style='font-size:11px;'>{$row['user_email']}</a>",
						($row['super_user'] ? $tickImg : ""), 
						($row['user_firstactivity'] != "" ? $tickImg : ""),
						substr($row['user_firstactivity'], 0, 16) ,
						substr($row['user_lastactivity'], 0, 16) ,
						substr($row['user_lastlogin'], 0, 16) ,
						($row['user_suspended_time'] == '' ? "<span style='color:#aaa;'>{$lang['control_center_149']}</span>" : substr($row['user_suspended_time'], 0, 16))
						);
}
// If no users are being shown, then render a row to say that no users are displayed
if (empty($userList))
{
	$userList[] = array("<span style='color:red;'>{$lang['control_center_191']}</span>","","","","","","","","");
}

// Set height of table
$height = (count($userList) > 20) ? 500 : "auto";
$col_widths_headers = array(
						array(150, "<b>{$lang['global_11']} &nbsp;&nbsp;&nbsp;<span style='color:#800000;'>($numUsers {$lang['control_center_192']})</span></b>"),
						array(90, "<b>{$lang['global_41']}</b>"),
						array(90, "<b>{$lang['global_42']}</b>"),
						array(152, "<b>{$lang['control_center_56']}</b>"),
						array(35,  RCView::span(array('class'=>'wrap'), "<b>{$lang['control_center_57']}</b>"), "center"),
						array(35,  RCView::span(array('class'=>'wrap'), "<b>{$lang['control_center_58']}</b>"), "center"),
						array(88,  "<b>{$lang['control_center_59']}</b>", "center"),
						array(88,  "<b>{$lang['control_center_148']}</b>", "center"),
						array(88,  "<b>{$lang['control_center_429']}</b>", "center"),
						array(88, RCView::span(array('class'=>'wrap'), "<b>{$lang['control_center_138']}</b>"), "center")
					);



// Add search box for searching table
?>
<p style="text-align:left;margin:0 0 10px 0;font-weight:bold;">
	<?php echo $lang['control_center_60'] ?> &nbsp;
	<input type="text" id="user_list_search" size="30" class="x-form-text x-form-field" style="font-family:arial;">
</p>
<?php

// Render the user list as table
renderGrid("userListTableInner", "", 1025, $height, $col_widths_headers, $userList);
