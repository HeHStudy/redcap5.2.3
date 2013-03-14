<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();


$q = db_query("select * from redcap_auth where username = '$userid'");
$row = db_fetch_array($q);
$display_page = $row['temp_pwd'];
//Redirect to main page if password has already been set
if (!$display_page) redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");


print "
<script type='text/javascript'>
	var mess1 = \"".remBr($lang['pwd_reset_12'])."\"; 
	var mess2 = \"".remBr($lang['pwd_reset_13'])."\";
</script>";


//Catch the new password submitted by user
if (!empty($_POST)) 
{
	// Check password length and complexity
	if (isset($_POST['password']) && $_POST['password'] != "" && isset($_POST['password2']) && $_POST['password'] == $_POST['password2']
		&& preg_match("/\d+/", $_POST['password']) && preg_match("/[a-z]/", $_POST['password']) && preg_match("/[A-Z]/", $_POST['password']) 
		&& preg_match("/^\w+$/", $_POST['password']) && strlen($_POST['password']) >= 10 && strlen($_POST['password']) <= 15) 
	{
		// Set default flag to reset password
		$resetPass = true;
		$sql_all = array();
		// If limit is set on preventing re-use of last 5 passwords, then check auth_history table for past 5 passwords
		if ($password_history_limit)
		{
			// Get last 5 passwords
			$sql_all[] = $sql = "select password from redcap_auth_history where username = '$userid' order by timestamp desc limit 5";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q)) {
				if ($row['password'] == md5($_POST['password'])) {
					// Password is being re-used, so prompt the user again for another password value to set
					$resetPass = false;
				}
			}
		}
		// Check if we can reset the password
		if ($resetPass)
		{
			// Set the new password in redcap_auth
			$sql_all[] = $sql = "UPDATE redcap_auth SET password = '".md5($_POST['password'])."', temp_pwd = 0 WHERE username = '$userid'";
			if (db_query($sql)) 
			{
				// Also add to auth_history table
				$sql_all[] = $sql = "insert into redcap_auth_history values ('$userid', '".md5($_POST['password'])."', '".NOW."')";
				db_query($sql);
				// Give confirmation message to user
				print "<br><br><h3><font color=#800000>{$lang['pwd_reset_11']}</font></h3>";
				print "<p>{$lang['pwd_reset_07']} {$lang['pwd_reset_08']} </p><br>
					   <p style='text-align:center'><input type='button' value=' {$lang['pwd_reset_09']} >> ' 
							onclick='window.location.href=\"".APP_PATH_WEBROOT."index.php?pid=$project_id\"'></p>";
				// Logging
				log_event(implode(";\n", $sql_all),"redcap_auth","MANAGE",$userid,"username = '" . prep($userid) . "'","Change own password");
				exit;
			} else {
				exit("ERROR!");
			}
		}
	}
}


//Check if the user has changed their password from the default value
if ($display_page) 
{
	print "<br><br><h3><font color=#800000>{$lang['pwd_reset_10']}</font></h3>";
	print "<p>{$lang['pwd_reset_01']} {$lang['pwd_reset_12']}</p>";
	// If setting is set to limit on using past 5 passwords and entering one of those five, give error msg to re-enter a new one.
	if ($password_history_limit && isset($resetPass) && !$resetPass)
	{
		print  "<p class='red'>
					<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'>
					<b>{$lang['global_01']}</b><br>{$lang['pwd_reset_15']} {$lang['pwd_reset_18']}
				</p>";
	}
	// Give note that password has expired, if using password
	if (!empty($password_reset_duration) && isset($_GET['msg']) && $_GET['msg'] == 'expired') 
	{
		print  "<p class='red'>
					<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'>
					<b>{$lang['global_03']}</b><br>
					{$lang['pwd_reset_16']} <b>$password_reset_duration {$lang['scheduling_25']}</b>{$lang['period']}
					{$lang['pwd_reset_17']} ";
		if ($password_history_limit) {
			print $lang['pwd_reset_18'];
		}
		print  "</p>";
	}
	print "<center>";
	print "<form method='post' action='{$_SERVER['REQUEST_URI']}'> ";
	print "<table style='font-family:Arial;font-size:12px;' class='blue'><tr><td align='left'>{$lang['global_11']}{$lang['colon']} </td><td> ";
	print "<input type=\"text\" class='x-form-text x-form-field' name=\"username\" value=\"$userid\" readonly=\"\"> </td></tr>";
	print "<tr><td align='left'>{$lang['global_32']}{$lang['colon']} </td><td> <input autocomplete='off' type=\"password\" class='x-form-text x-form-field' name=\"password\" 
			onkeydown='if(event.keyCode == 13) return false;' onBlur='if(this.value.length >0) { if(!chk_cont(this)) return alertbad(this,mess1); 
			if(!chk_len(this,10,15)) return alertbad(this,mess2); }'></td></tr>";
	print "<tr><td align='left'>{$lang['pwd_reset_05']}{$lang['colon']} </td><td> <input autocomplete='off' type=\"password\" class='x-form-text x-form-field' 
			onkeydown='if(event.keyCode == 13) return false;' name=\"password2\" onBlur='if(this.value.length >0) { 
			if(!chk_cont(this)) return alertbad(this,mess1); if(!chk_len(this,10,15)) return alertbad(this,mess2); }'></td></tr></table> ";
	print "<p style='text-align:center;'><input type='submit' value='Submit'
		onclick=\"if(password.value.length < 1) return false; if(trim(password.value) != trim(password2.value) && password.value.length > 1) { 
		alert('{$lang['pwd_reset_14']}'); password.value=''; password2.value=''; setTimeout(function () { password.focus() }, 1); return false; } \"> ";
	print "</form> ";
	print "</center>";
} else {
	print "<h2>{$lang['pwd_reset_06']}</h2>";
}


$objHtmlPage->PrintFooter();
