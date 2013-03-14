<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

//If user is not a super user, go back to Home page
if (!$super_user && $_GET['action'] != "reset_password_as_temp") 
{ 
	redirect(APP_PATH_WEBROOT); 
}


// Do any processing
switch ($_GET['action']) {

	// Designate selected user as a new Super User
	case "add_super_user":
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit('0');
		$sql = "update redcap_user_information set super_user = 1 where username = '" . prep($_GET['username']) . "'";
		$q = db_query($sql);
		// Logging
		if ($q) {
			log_event($sql,"redcap_user_information","MANAGE",$_GET['username'],"username = '" . prep($_GET['username']) . "'","Designate super user");
			exit('1');
		}
		break;

	// Remove Super User status
	case "remove_super_user":
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit('0');
		$sql = "update redcap_user_information set super_user = 0 where username = '" . prep($_GET['username']) . "'";
		$q = db_query($sql);
		// Logging
		if ($q) {
			log_event($sql,"redcap_user_information","MANAGE",$_GET['username'],"username = '" . prep($_GET['username']) . "'","Remove super user");
			exit('1');
		}
		break;

	// Allow/disallow user to create or copy projects
	case "allow_create_db":
		$sql = "update redcap_user_information set allow_create_db = {$_GET['allow_create_db']} where username = '" . prep($_GET['username']) . "'";
		$q = db_query($sql);
		// Logging
		$allowText = $_GET['allow_create_db'] ? "Grant user rights to create or copy projects" : "Remove user rights to create or copy projects";
		if ($q) log_event($sql,"redcap_user_information","MANAGE",$_GET['username'],"username = '" . prep($_GET['username']) . "'",$allowText);
		print $q ? "1" : "0";
		exit;
		break;

	// A table-based user resets their own password and sets as temporary password
	case "reset_password_as_temp":
		$sql = "update redcap_auth set temp_pwd = 1 where username = '" . prep(USERID) . "'";
		$q = db_query($sql);
		// Logging
		if ($q) log_event($sql,"redcap_auth","MANAGE",USERID,"username = '" . prep(USERID) . "'","Reset own password");
		print $q ? "1" : "0";
		exit;
		break;

	// Reset a table-based user's password and set as temporary password, then send user an email with new password
	case "reset_password":
		// Get the user's email address
		$this_user_email = db_result(db_query("select user_email from redcap_user_information where username = '" . prep($_GET['username']) . "'"), 0);
		if ($this_user_email == "") {
			exit("ERROR: The user does not have an email address listed. The password was not reset.");
		}
		// Set password
		$pass = Authentication::resetPassword($_GET['username']);
		if ($pass !== false) {
			// Email user
			$email = new Message();
			$emailSubject = 'REDCap '.$lang['control_center_102'];
			$emailContents = '
				<html>
				<body style="font-family:Arial;font-size:10pt;">
				'.$lang['global_21'].'<br /><br />
				'.$lang['control_center_99'].'<br /><br />
				REDCap - '.APP_PATH_WEBROOT_FULL.' <br /><br />
				'.$lang['control_center_97'].'<br /><br />
				'.$lang['global_11'].$lang['colon'].' '.$_GET['username'].'<br />
				'.$lang['global_32'].$lang['colon'].' '.$pass.'<br /><br />
				'.$lang['control_center_96'].' 
				'.$lang['control_center_98'].' '.$project_contact_name.' '.$lang['global_15'].' '.$project_contact_email.$lang['period'].'
				</body>
				</html>
				';
			$email->setTo($this_user_email);
			$email->setFrom($user_email);
			$email->setSubject($emailSubject);
			$email->setBody($emailContents);
			if ($email->send()) {
				exit("{$lang['control_center_64']} $this_user_email {$lang['control_center_65']}");
			} else {
				exit("{$lang['global_01']}: {$lang['control_center_66']} $this_user_email {$lang['control_center_65']} {$lang['control_center_67']} $pass");
			}
		}
		exit("{$lang['global_01']}: {$lang['control_center_68']}");
		break;
		
}


/**
 * VIEW USER
 */
if ($_GET['user_view'] == "view_user") {

	// Defaults
	$this_user_firstname = "";
	$this_user_lastname  = "";
	$this_user_email     = "";
	$this_user_inst_id   = "";
	$this_ui_id = "";

	// Get user names from redcap_auth, redcap_user_rights, and redcap_user_information (to cover all bases)
	$total_userlist = array();
	$q = db_query("select username from redcap_auth");
	while ($row = db_fetch_assoc($q)) {
		$row['username'] = strtolower(trim($row['username']));
		$total_userlist[$row['username']] = array("username" => $row['username']);
	}
	$q = db_query("select distinct username from redcap_user_rights where username != ''");
	while ($row = db_fetch_assoc($q)) {
		$row['username'] = strtolower(trim($row['username']));
		$total_userlist[$row['username']] = array("username" => $row['username']);
	}
	$q = db_query("select * from redcap_user_information where username != '' order by username");
	while ($row = db_fetch_assoc($q)) {
		$row['username'] = strtolower(trim($row['username']));
		$total_userlist[$row['username']] = array("username" 	   => $row['username'],
												  "user_email"	   => $row['user_email'],
												  "user_firstname" => $row['user_firstname'],
												  "user_lastname"  => $row['user_lastname'],
												  "user_inst_id"  => $row['user_inst_id'],
													"ui_id" => $row['ui_id']);
	}
	ksort($total_userlist, SORT_STRING);

	print  "	<select onchange=\"view_user( $('#select_username').val() );\" id='select_username' class='x-form-text x-form-field' style='padding-right:0;height:22px;'>
					<option value=''>--- {$lang['control_center_22']} ---</option>";
	// Loop through our user list array
	foreach ($total_userlist as $this_user=>$row)
	{
		if (trim($row['username']) == "") continue;
		print  "<option class='notranslate' value='{$row['username']}' " . (($row['username'] == $_GET['username']) ? "selected" : "") . ">" . $row['username'];
		if ($row['user_lastname'] != "" && $row['user_firstname'] != "") {
			print  " (" . $row['user_lastname'] . ", " . $row['user_firstname'] . ")";
		}
		print  "</option>";
	}
	print  "	</select>
				&nbsp;&nbsp;
				<span style='visibility:hidden;' id='view_user_progress'><img src='" . APP_PATH_IMAGES . "progress_circle.gif' class='imgfix'></span>
				<br><br><br>";

	## Display user information table if username has been selected
	if (isset($_GET['username'])) 
	{
		// Get user info
		$thisUserInfo = User::getUserInfo($_GET['username']);
		// Set user info vars
		$first_login = format_ts_mysql($thisUserInfo['user_firstvisit']);
		$first_activity = format_ts_mysql($thisUserInfo['user_firstactivity']);
		$last_activity  = format_ts_mysql($thisUserInfo['user_lastactivity']);
		$last_login  = format_ts_mysql($thisUserInfo['user_lastlogin']);
		$user_suspended_time = format_ts_mysql($thisUserInfo['user_suspended_time']);
		$this_user_lastname  = $thisUserInfo['user_lastname'];
		$this_user_firstname = $thisUserInfo['user_firstname'];
		$this_user_email     = $thisUserInfo['user_email'];
		$this_user_email2    = ($thisUserInfo['user_email2'] != '' && $thisUserInfo['email2_verify_code'] == '') ? $thisUserInfo['user_email2'] : "";
		$this_user_email3    = ($thisUserInfo['user_email3'] != '' && $thisUserInfo['email3_verify_code'] == '') ? $thisUserInfo['user_email3'] : "";
		$this_user_inst_id   = $thisUserInfo['user_inst_id'];
		$this_ui_id = $thisUserInfo['ui_id'];		
		// Check if user is currently logged in
		$logoutWindow = date("Y-m-d H:i:s", mktime(date("H"),date("i")-$autologout_timer,date("s"),date("m"),date("d"),date("Y")));
		$sql = "select 1 from redcap_sessions s, redcap_log_view v 
				where v.user = '".prep($_GET['username'])."' and v.session_id = s.session_id and v.ts >= '$logoutWindow' limit 1";
		$isLoggedIn = (db_num_rows(db_query($sql)) > 0);
		if ($isLoggedIn) {
			$isLoggedInIcon = RCView::img(array('src'=>'tick_circle_frame.png','class'=>'imgfix')) . 
							  RCView::span(array('style'=>'color:green;'), $lang['design_100']);
		} else {
			$isLoggedInIcon = RCView::img(array('src'=>'stop_gray.png','class'=>'imgfix')) . 
							  RCView::span(array('style'=>'color:#800000;'), $lang['design_99']);
		}
		// Set suspended user html (button or link)
		if ($user_suspended_time == "")
		{
			$unsuspend_link = "";
			$user_suspended_time = "<input type='button' value='" . $lang['control_center_142'] . "' style='font-size:11px;' onclick=\"
										if (confirm('" . cleanHtml($lang['control_center_143']) . "')) {
											$.get('suspend_user.php', { suspend: 1, username: '{$_GET['username']}' },function(data) {
												if (data != '0') {
													$.get('user_controls_ajax.php', { user_view: 'view_user', username: '{$_GET['username']}' },function(data) {
														$('#view_user_div').html(data);
														highlightTable('indv_user_info',2500);
													});
													alert('" . cleanHtml($lang['control_center_144']) . "');
												} else {
													alert(woops);
												}
											});
										}
									\">";
		} else {
			$unsuspend_link = "&nbsp;(<a href='javascript:;' style='text-decoration: underline; font-size: 10px; font-family: tahoma;' onclick=\"
										if (confirm('" . cleanHtml($lang['control_center_147']) . "')) {
											$.get('suspend_user.php', { suspend: 0, username: '{$_GET['username']}' },function(data) {
												if (data != '0') {
													$.get('user_controls_ajax.php', { user_view: 'view_user', username: '{$_GET['username']}' },function(data) {
														$('#view_user_div').html(data);
														highlightTable('indv_user_info',2500);
													});
													alert('" . cleanHtml($lang['control_center_146']) . "');
												} else {
													alert(woops);
												}
											});
										}
									\">" . $lang['control_center_145'] . "</a>)";
		}
		// Retrieve project access count
		$proj_access_count = db_result(db_query("select count(1) from redcap_user_rights u, redcap_projects p where u.project_id = p.project_id
			and u.username = '" . prep($_GET['username']) . "'"), 0);
		// Retrieve if user can create/copy new projects
		$allow_create_db = $thisUserInfo['allow_create_db'];
		// collect what user info to display
		$user_info_items = array();
		if (!empty($this_user_lastname) && !empty($this_user_firstname)) $user_info_items[] = "$this_user_firstname $this_user_lastname";
		if (!empty($this_user_email)) $user_info_items[] = $this_user_email;
		if (!empty($this_user_email2)) $user_info_items[] = $this_user_email2;
		if (!empty($this_user_email3)) $user_info_items[] = $this_user_email3;
		if (!empty($this_user_inst_id)) $user_info_items[] = $lang['control_center_238']  . ": $this_user_inst_id";
		// Render table
		print  "<table id='indv_user_info' cellpadding=0 cellspacing=0 style='width:100%;border:0;border-collapse:collapse;'>
					<tr>
						<td class='label' style='background-color:#eee;' colspan='2'>
							{$lang['control_center_71']}
							<span style='padding-left:4px;font-size:11px;color:#800000;font-weight:normal;'>
								<b>{$_GET['username']}</b>
								" . (count(user_info_items) ? "(" . implode(', ', $user_info_items) . ")" : "") . "
							</span>";

		// Button to edit user (but user must FIRST exist in user_information table)
		$inUserInfoTable = db_result(db_query("select count(1) from redcap_user_information where username = '" . prep($_GET['username']) . "' and user_email is not null"), 0);
		?>
		<form method="POST" action="create_user.php" style="display: inline;">
			<input type="hidden" name="redcap_csrf_token" value="<?php echo getCsrfToken() ?>">
			<input type="hidden" name="ui_id" value="<?php echo $this_ui_id; ?>">
			<input type="submit" value="<?php echo $lang['control_center_239'] ?>" style="font-size:11px;<?php if (!$inUserInfoTable) echo "color:gray;" ?>" onclick="
				if (<?php echo $inUserInfoTable ?>==0) {
					simpleDialog('<?php echo cleanHtml($lang['control_center_284']) ?>');
					return false;
				}
				return true;
			">
		</form>
		<?php			
							
		print  "		</td>
					</tr>
					<tr>
						<td class='data2'>
							{$lang['control_center_431']}
						</td>
						<td class='data2' style='width:130px;text-align:center;'>
							$isLoggedInIcon
						</td>
					</tr>
					<tr>
						<td class='data2'>
							{$lang['control_center_76']}
							<span style='padding-left:3px;'>
								(<a style='text-decoration:underline;font-size:10px;font-family:tahoma;'
									href='" . APP_PATH_WEBROOT . "ControlCenter/view_projects.php?userid={$_GET['username']}'>{$lang['control_center_77']}</a>)
							</span>
						</td>
						<td class='data2' style='width:130px;text-align:center;'>
							$proj_access_count
						</td>
					</tr>
					<tr>
						<td class='data2'>
							{$lang['control_center_72']}
						</td>
						<td class='data2' style='width:130px;text-align:center;'>
							" . ($first_login == "" ? "<i>{$lang['database_mods_81']}</i>" : $first_login) . "
						</td>
					</tr>
					<tr>
						<td class='data2'>
							{$lang['control_center_430']}
						</td>
						<td class='data2' style='width:130px;text-align:center;'>
							" . ($last_login == "" ? "<i>{$lang['database_mods_81']}</i>" : $last_login) . "
						</td>
					</tr>
					<tr>
						<td class='data2'>
							{$lang['control_center_73']}
						</td>
						<td class='data2' style='width:130px;text-align:center;'>
							" . ($first_activity == "" ? "<i>{$lang['database_mods_81']}</i>" : $first_activity) . "
						</td>
					</tr>
					<tr>
						<td class='data2'>
							{$lang['control_center_74']}
						</td>
						<td class='data2' style='width:130px;text-align:center;'>
							" . ($last_activity == "" ? "<i>{$lang['database_mods_81']}</i>" : $last_activity) . "
						</td>
					</tr>
					<tr>
						<td class='data2'>
							{$lang['control_center_138']} $unsuspend_link
						</td>
						<td class='data2' style='width:130px;text-align:center;'>
							$user_suspended_time
						</td>
					</tr>
				</table>";
		print  "<div style='text-align:right;padding:5px;'>";

		// Give option to revoke "create db" rights for user (any kind of user)
		print  "	<div style='text-align:left;font-size:11px;padding-bottom:7px;'>
						<input id='allow_create_db' type='checkbox' class='imgfix' ".($allow_create_db ? "checked" : "")." onclick=\"set_allow_create_db('{$_GET['username']}');\">
						{$lang['control_center_79']}
						<span id='progress_allow_create_db2' style='visibility:hidden;padding-left:5px;color:red;font-weight:bold;'>
							{$lang['global_39']}
						</span>";
		// If only super users can create new projects, then add note that the create/copy feature
		if ($superusers_only_create_project) {
			print  "	<br><span style='color:#800000;'>
						({$lang['global_02']}{$lang['colon']} {$lang['control_center_80']})
						</span>";
		}
		print  "	</div>";

		// If user is a table-based user (i.e. in redcap_auth table), then give option to reset password
		$isTableUser = db_result(db_query("select count(1) from redcap_auth where username = '" . prep($_GET['username']) . "'"), 0);
		if ($isTableUser) {
			// Determine last time that their password was reset (if any)
			$lastPasswordReset = '';
			$sql = "select timestamp(ts) from redcap_log_event where pk = '" . prep($_GET['username']) . "' 
					and event = 'MANAGE' and object_type = 'redcap_auth' and description in ('Reset user password', 'Reset own password') 
					order by log_event_id desc limit 1";
			$q = db_query($sql);
			if ($q && db_num_rows($q)) {
				$lastPasswordReset = format_ts_mysql(db_result($q, 0));
			}
			// Button to reset password
			print  "<div style='text-align:left;font-size:11px;color:#666;'>
						<input type='button' value='" . cleanHtml($lang['control_center_140']) . "' style='font-size:11px;' onclick=\"
							if (confirm('" . cleanHtml($lang['control_center_81']) . " \'{$_GET['username']}\'?\\n\\n" . cleanHtml($lang['control_center_82']) . "')) {
								$.get('user_controls_ajax.php', { username: '{$_GET['username']}', action: 'reset_password' }, function(data) {
									simpleDialog(data);
								});
							}
						\"> ";
			print ($lastPasswordReset == '') ? $lang['control_center_383'] : $lang['control_center_384'] . ' ' . $lastPasswordReset;
			print "</div>";
		}
		// Give button to delete user
		print  "<div style='text-align:left;font-size:11px;color:#666;'>
					<input type='button' value='" . $lang['control_center_139'] . "' style='font-size:11px;' onclick=\"
						if (confirm('" . cleanHtml($lang['control_center_83']) . " \'{$_GET['username']}\'?\\n\\n" . cleanHtml($lang['control_center_84']) . " " . ($proj_access_count > 0 ? cleanHtml($lang['control_center_85']) . " $proj_access_count " . cleanHtml($lang['control_center_86']) : "") . "')) {
							$.get('delete_user.php', { username: '{$_GET['username']}' },
								function(data) {
									if (data != '0') {
										$.get('user_controls_ajax.php', { user_view: 'view_user' },
											function(data) {
												$('#view_user_div').html(data);
											}
										);
										simpleDialog('" . cleanHtml($lang['control_center_87']) . " \'{$_GET['username']}\' " . cleanHtml($lang['control_center_88']) . "');
									} else {
										simpleDialog('{$lang['global_01']}{$lang['colon']} " . cleanHtml($lang['control_center_89']) . "');
									}
								}
							);
						}
					\">
				</div>";

		print  "</div>";

	}

}