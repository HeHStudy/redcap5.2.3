<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

$changesSaved = false;

// If project default values were changed, update redcap_config table with new values
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$changes_log = array();
	$sql_all = array();
	foreach ($_POST as $this_field=>$this_value) {
		// Save this individual field value
		$sql = "UPDATE redcap_config SET value = '".prep($this_value)."' WHERE field_name = '$this_field'";
		$q = db_query($sql);
		
		// Log changes (if change was made)
		if ($q && db_affected_rows() > 0) {
			$sql_all[] = $sql;
			$changes_log[] = "$this_field = '$this_value'";
		}
	}

	// Log any changes in log_event table
	if (count($changes_log) > 0) {
		log_event(implode(";\n",$sql_all),"redcap_config","MANAGE","",implode(",\n",$changes_log),"Modify system configuration");
	}

	$changesSaved = true;
}

// Retrieve data to pre-fill in form
$element_data = array();

$q = db_query("select * from redcap_config");
while ($row = db_fetch_array($q)) {
		$element_data[$row['field_name']] = $row['value'];
}
?>

<?php
if ($changesSaved)
{
	// Show user message that values were changed
	print  "<div class='yellow' style='margin-bottom: 20px; text-align:center'>
			<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'>
			{$lang['control_center_19']}
			</div>";
}
?>

<h3 style="margin-top: 0;"><?php echo $lang['control_center_112'] ?></h3>

<form action='security_settings.php' enctype='multipart/form-data' target='_self' method='post' name='form' id='form'>
<?php
// Go ahead and manually add the CSRF token even though jQuery will automatically add it after DOM loads.
// (This is done in case the page is very long and user submits form before the DOM has finished loading.)
print "<input type='hidden' name='redcap_csrf_token' value='".getCsrfToken()."'>";
?>
<table style="border: 1px solid #ccc; background-color: #f0f0f0;">


<!-- Auth & Login Settings -->
<tr>
	<td colspan="2">
		<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['system_config_157'] ?></h3>
	</td>
</tr>
<tr  id="auth_meth_global-tr" sq_id="auth_meth_global">
	<td class="cc_label">
		<?php echo $lang['system_config_228'] ?>
		<div class="cc_info">
			<?php echo $lang['system_config_229'] ?>
		</div>
	</td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="auth_meth_global">
			<option value='none' <?php echo ($element_data['auth_meth_global'] == "none" ? "selected" : "") ?>><?php echo $lang['system_config_08'] ?></option>
			<option value='table' <?php echo ($element_data['auth_meth_global'] == "table" ? "selected" : "") ?>><?php echo $lang['system_config_09'] ?></option>
			<option value='ldap' <?php echo ($element_data['auth_meth_global'] == "ldap" ? "selected" : "") ?>>LDAP</option>
			<option value='ldap_table' <?php echo ($element_data['auth_meth_global'] == "ldap_table" ? "selected" : "") ?>>LDAP & <?php echo $lang['system_config_09'] ?></option>
			<option value='shibboleth' <?php echo ($element_data['auth_meth_global'] == "shibboleth" ? "selected" : "") ?>>Shibboleth</option>
			<?php if (isDev(true)) { ?>
				<option value='local' <?php echo ($element_data['auth_meth_global'] == "local" ? "selected" : "") ?>>Vanderbilt Local (session-based)</option>
				<option value='c4' <?php echo ($element_data['auth_meth_global'] == "c4" ? "selected" : "") ?>>C4 (cookie-based)</option>
			<?php } ?>
			<option value='rsa' <?php echo ($element_data['auth_meth_global'] == "rsa" ? "selected" : "") ?>>RSA SecurID (two-factor authentication)</option>
		</select>
		<div class="cc_info" style="font-weight:normal;">
			<?php echo $lang['system_config_222'] ?> 
			<a href="https://iwg.devguard.com/trac/redcap/wiki/ChangingAuthenticationMethod" target="_blank" style="text-decoration:underline;"><?php echo $lang['system_config_223'] ?></a><?php echo $lang['system_config_224'] ?>
		</div>
		<div class="cc_info">
			<a href="<?php echo APP_PATH_WEBROOT . "ControlCenter/ldap_troubleshoot.php" ?>" style="color:#800000;text-decoration:underline;"><?php echo $lang['control_center_317'] ?></a>
		</div>
	</td>
</tr>
<tr  id="autologout_timer-tr" sq_id="autologout_timer">
	<td class="cc_label"><?php echo $lang['system_config_160'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='autologout_timer' value='<?php echo $element_data['autologout_timer'] ?>'
			onblur="redcap_validate(this,'0','','hard','float')" size='10' />
		<span style="color: #888;"><?php echo $lang['system_config_22'] ?></span><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_161'] ?>
		</div>
	</td>
</tr>
<!-- Login logo -->
<tr  id="login_logo-tr" sq_id="login_logo">
	<td class="cc_label"><?php echo $lang['system_config_127'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='login_logo' value='<?php echo $element_data['login_logo'] ?>' size="60" /><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_128'] ?>
		</div>
	</td>
</tr>
<!-- Custom login text -->
<tr>
	<td class="cc_label">
		<?php echo $lang['system_config_194'] ?>
		<div class="cc_info" style="font-weight:normal;">
			<?php echo $lang['system_config_196'] ?>
		</div>
	</td>
	<td class="cc_data">
		<textarea class='x-form-field notesbox' id='login_custom_text' name='login_custom_text'><?php echo $element_data['login_custom_text'] ?></textarea><br/>
		<div id='login_custom_text-expand' style='text-align:right;'>
			<a href='javascript:;' style='font-weight:normal;text-decoration:none;color:#999;font-family:tahoma;font-size:10px;'
				onclick="growTextarea('login_custom_text')">Expand</a>&nbsp;
		</div>
		<div class="cc_info">
			<?php echo $lang['system_config_195'] ?>
		</div>
	</td>
</tr>
<tr  id="logout_fail_limit-tr" sq_id="logout_fail_limit">
	<td class="cc_label"><?php echo $lang['system_config_120'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='logout_fail_limit' value='<?php echo $element_data['logout_fail_limit'] ?>'
			onblur="redcap_validate(this,'0','','hard','int')" size='10' />
		<span style="color: #888;"><?php echo $lang['system_config_121'] ?></span><br/>
	</td>
</tr>
<tr  id="logout_fail_window-tr" sq_id="logout_fail_window">
	<td class="cc_label"><?php echo $lang['system_config_122'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='logout_fail_window' value='<?php echo $element_data['logout_fail_window'] ?>'
			onblur="redcap_validate(this,'0','','hard','int')" size='10' />
		<span style="color: #888;"><?php echo $lang['system_config_123'] ?></span><br/>
	</td>
</tr>
<tr  id="login_autocomplete_disable-tr" sq_id="login_autocomplete_disable">
	<td class="cc_label"><?php echo $lang['system_config_32'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="login_autocomplete_disable">
			<option value='0' <?php echo ($element_data['login_autocomplete_disable'] == 0) ? "selected" : "" ?>><?php echo $lang['system_config_35'] ?></option>
			<option value='1' <?php echo ($element_data['login_autocomplete_disable'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_34'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo "{$lang['global_02']}: {$lang['system_config_33']}" ?>
		</div>
	</td>
</tr>


<!-- Additional Tabled-based Authentication Settings -->
<tr>
	<td colspan="2">
		<hr size=1>
		<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['system_config_162'] ?></h3>
	</td>
</tr>
<tr>
	<td class="cc_label"><?php echo $lang['system_config_136'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="password_history_limit">
			<option value='0' <?php echo ($element_data['password_history_limit'] == 0) ? "selected" : "" ?>><?php echo $lang['design_99'] ?></option>
			<option value='1' <?php echo ($element_data['password_history_limit'] == 1) ? "selected" : "" ?>><?php echo $lang['design_100'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_137'] ?>
		</div>
	</td>
</tr>
<tr>
	<td class="cc_label"><?php echo $lang['system_config_138'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='password_reset_duration' value='<?php echo $element_data['password_reset_duration'] ?>'
			onblur="redcap_validate(this,'0','','hard','float')" size='10' />
		<span style="color: #888;"><?php echo $lang['system_config_140'] ?></span><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_139'] ?>
		</div>
</tr>

<!-- Additional Shibboleth Authentication Settings -->
<tr>
	<td colspan="2">
		<hr size=1>
		<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['system_config_158'] ?></h3>
	</td>
</tr>
<tr  id="shibboleth_username_field-tr" sq_id="shibboleth_username_field">
	<td class="cc_label"><?php echo $lang['system_config_44'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="shibboleth_username_field">
			<option value='none' <?php echo ($element_data['shibboleth_username_field'] == "none" ? "selected" : "") ?>><?php echo $lang['system_config_45'] ?></option>
			<option value='REMOTE_USER' <?php echo ($element_data['shibboleth_username_field'] == "REMOTE_USER" ? "selected" : "") ?>>REMOTE_USER</option>
			<option value='HTTP_REMOTE_USER' <?php echo ($element_data['shibboleth_username_field'] == "HTTP_REMOTE_USER" ? "selected" : "") ?>>HTTP_REMOTE_USER</option>
			<option value='HTTP_AUTH_USER' <?php echo ($element_data['shibboleth_username_field'] == "HTTP_AUTH_USER" ? "selected" : "") ?>>HTTP_AUTH_USER</option>
			<option value='HTTP_SHIB_EDUPERSON_PRINCIPAL_NAME' <?php echo ($element_data['shibboleth_username_field'] == "HTTP_SHIB_EDUPERSON_PRINCIPAL_NAME" ? "selected" : "") ?>>HTTP_SHIB_EDUPERSON_PRINCIPAL_NAME</option>
			<option value='Shib-EduPerson-Principal-Name' <?php echo ($element_data['shibboleth_username_field'] == "Shib-EduPerson-Principal-Name" ? "selected" : "") ?>>Shib-EduPerson-Principal-Name</option>
		</select><br/>
		<div class="cc_info">
			<?php echo "{$lang['global_02']}: {$lang['system_config_33']}" ?>
		</div>
	</td>
</tr>
<tr  id="shibboleth_logout-tr" sq_id="shibboleth_logout">
	<td class="cc_label"><?php echo $lang['system_config_46'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='shibboleth_logout' value='<?php echo $element_data['shibboleth_logout'] ?>' size="60" /><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_47'] ?>
		</div>
	</td>
</tr>
</table><br/>
<div style="text-align: center;"><input type='submit' name='' value='Save Changes' /></div><br/>
</form>

<?php include 'footer.php'; ?>