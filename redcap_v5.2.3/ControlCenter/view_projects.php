<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

// Class to render user's project list
require APP_PATH_CLASSES . "RenderProjectList.php";
?>

<h3 style="margin-top: 0;"><?php echo $lang['control_center_110'] ?></h3>

<p style='margin-top:0;'><?php echo $lang['control_center_20'] ?></p>

<?php
// Give choice to filter project listing by users who have access to them
print  "<p style='padding:10px 0;'>
			<b>{$lang['control_center_21']}</b> &nbsp;
			<select class='x-form-text x-form-field' style='padding-right:0;height:22px;max-width:300px;'
				onchange=\"window.location.href = '".PAGE_FULL."?userid='+this.value\">
				<option value='' " . (($_GET['userid'] == "" && !isset($_GET['view_all'])) ? "selected" : "") . ">--- {$lang['control_center_22']} ---</option>
				<option value='&view_all'" . ((isset($_GET['view_all']) && !isset($_GET['no_counts'])) ? "selected" : "") . ">-- {$lang['control_center_23']} --</option>";

// Get all usernames from the user tables (cover all bases in case they've not yet accessed REDCap, thus no email or name)
$all_users = array();
$sql = "select distinct username from redcap_user_rights order by username";
$q = db_query($sql);
while ($row = db_fetch_assoc($q)) {
	$row['username'] = strtolower(trim($row['username']));
	$all_users[strtolower($row['username'])] = array('username'=>$row['username']);
}
$sql = "select * from redcap_user_information where username != '' order by username, user_lastname, user_firstname";
$q = db_query($sql);
while ($row = db_fetch_assoc($q)) {
	$row['username'] = strtolower(trim($row['username']));
	$all_users[strtolower($row['username'])] = array('username'=>$row['username'], 'user_firstname'=>$row['user_firstname'], 'user_lastname'=>$row['user_lastname'], 'user_email'=>$row['user_email']);
}
// Order array
ksort($all_users, SORT_STRING);
// Loop to display all usernames and their first/last names as an option
foreach ($all_users as $username=>$attr) {
	$attr['username'] = trim($attr['username']);
	if ($attr['username'] != "")
	{
		$disp_name = "";
		if ($attr['user_firstname'] != "" && $attr['user_lastname'] != "") {
			$disp_name = "(" . $attr['user_lastname'] . ", " . $attr['user_firstname'] . ")";
		}
		print  "<option class='notranslate' value='{$attr['username']}' " . (($attr['username'] == $_GET['userid']) ? "selected" : "") . ">{$attr['username']} $disp_name</option>";
	}
}
print  "	</select>";

// If user is selected, then display a link to view their user information
if (isset($_GET['userid']) && $_GET['userid'] != "")
{
	print " &nbsp <a style='font-family:tahoma;color:#800000;text-decoration:underline;font-size:10px;' href='".APP_PATH_WEBROOT."ControlCenter/view_users.php?username={$_GET['userid']}'>{$lang['system_config_145']}</a>";
}
	
print  "</p>";

// Display listing of all existing projects
$projects = new RenderProjectList ();
$projects->renderprojects("control_center");

// Hidden "undelete project" div
print RCView::simpleDialog("", $lang['control_center_378'], 'undelete_project_dialog');

?>

<?php include 'footer.php'; ?>