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

<h3 style="margin-top: 0;"><?php echo $lang['control_center_123'] ?></h3>

<form action='homepage_settings.php' enctype='multipart/form-data' target='_self' method='post' name='form' id='form'>
<?php
// Go ahead and manually add the CSRF token even though jQuery will automatically add it after DOM loads.
// (This is done in case the page is very long and user submits form before the DOM has finished loading.)
print "<input type='hidden' name='redcap_csrf_token' value='".getCsrfToken()."'>";
?>
<table style="border: 1px solid #ccc; background-color: #f0f0f0;">
<tr  id="display_nonauth_projects-tr" sq_id="display_nonauth_projects">
	<td class="cc_label"><?php echo $lang['system_config_73'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="display_nonauth_projects">
			<option value='0' <?php echo ($element_data['display_nonauth_projects'] == 0) ? "selected" : "" ?>><?php echo $lang['system_config_75'] ?></option>
			<option value='1' <?php echo ($element_data['display_nonauth_projects'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_76'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_74'] ?>
		</div>
	</td>
</tr>
<tr  id="homepage_contact-tr" sq_id="homepage_contact">
	<td class="cc_label"><?php echo $lang['system_config_77'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='homepage_contact' value='<?php echo $element_data['homepage_contact'] ?>' size='50' />
	</td>
</tr>
<tr  id="homepage_contact_email-tr" sq_id="homepage_contact_email">
	<td class="cc_label"><?php echo $lang['system_config_78'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='homepage_contact_email' value='<?php echo $element_data['homepage_contact_email'] ?>'
			onblur="redcap_validate(this,'0','','hard','email');" size='50' />
	</td>
</tr>
<tr  id="homepage_grant_cite-tr" sq_id="homepage_grant_cite">
	<td class="cc_label"><?php echo $lang['system_config_79'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='homepage_grant_cite' value='<?php echo $element_data['homepage_grant_cite'] ?>' size='50' /><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_80'] ?>
		</div>
	</td>
</tr>
<tr  id="homepage_custom_text-tr" sq_id="homepage_custom_text">
	<td class="cc_label"><?php echo $lang['system_config_81'] ?></td>
	<td class="cc_data">
		<textarea class='x-form-field notesbox' id='homepage_custom_text' name='homepage_custom_text'><?php echo $element_data['homepage_custom_text'] ?></textarea><br/>
		<div id='homepage_custom_text-expand' style='text-align:right;'>
			<a href='javascript:;' style='font-weight:normal;text-decoration:none;color:#999;font-family:tahoma;font-size:10px;'
				onclick="growTextarea('homepage_custom_text')">Expand</a>&nbsp;
		</div>
		<div class="cc_info">
			<?php echo $lang['system_config_82'] ?>
		</div>
	</td>
</tr>
</table><br/>
<div style="text-align: center;"><input type='submit' name='' value='Save Changes' /></div><br/>
</form>

<?php include 'footer.php'; ?>