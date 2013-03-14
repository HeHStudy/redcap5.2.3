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

<h3 style="margin-top: 0;"><?php echo $lang['system_config_214'] ?></h3>
<p><?php echo $lang['system_config_215'] ?></p>

<form enctype='multipart/form-data' target='_self' method='post' name='form' id='form'>
<?php
// Go ahead and manually add the CSRF token even though jQuery will automatically add it after DOM loads.
// (This is done in case the page is very long and user submits form before the DOM has finished loading.)
print "<input type='hidden' name='redcap_csrf_token' value='".getCsrfToken()."'>";
?>
<table style="border: 1px solid #ccc; background-color: #f0f0f0; width: 100%;">

<tr>
	<td colspan="2">
		<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['system_config_218'] ?></h3>
	</td>
</tr>

<!-- Edoc storage option -->
<tr  id="edoc_storage_option-tr" sq_id="edoc_storage_option">
	<td class="cc_label">
		<?php echo $lang['system_config_206'] ?>
	</td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="edoc_storage_option">
			<option value='0' <?php echo ($element_data['edoc_storage_option'] == '0') ? "selected" : "" ?>><?php echo $lang['system_config_208'] ?></option>
			<option value='1' <?php echo ($element_data['edoc_storage_option'] == '1') ? "selected" : "" ?>><?php echo $lang['system_config_209'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_211'] ?> <b>/webtools2/webdav/</b>
		</div>
		<div class="cc_info">
			<?php echo $lang['system_config_207'] ?>
		</div>
	</td>
</tr>

<!-- Edoc local path -->
<tr  id="edoc_path-tr" sq_id="edoc_path">
	<td class="cc_label"><?php echo $lang['system_config_178'] ?> <?php echo $lang['system_config_213'] ?> <span style='color:#800000;'><?php echo $lang['system_config_63'] ?></span></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='edoc_path' value='<?php echo $element_data['edoc_path'] ?>' size="60" />
		<div class="cc_info">
			<?php echo "{$lang['system_config_61']} <b>".dirname(dirname(dirname(__FILE__))).DS."edocs".DS."</b>" ?>
		</div>
		<div class="cc_info">
			<?php echo "{$lang['system_config_64']} ".dirname(dirname(dirname(__FILE__))).DS."my_file_repository".DS ?>
		</div>
	</td>
</tr>

<tr>
	<td colspan="2">
		<hr size=1>
		<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['app_04'] ?></h3>
	</td>
</tr>

<tr id="file_repository_enabled-tr">
	<td class="cc_label"><?php echo $lang['system_config_182'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="file_repository_enabled">
			<option value='0' <?php echo ($element_data['file_repository_enabled'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['file_repository_enabled'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_183'] ?>
		</div>
	</td>
</tr>
<tr  id="file_repository_upload_max-tr">
	<td class="cc_label"><?php echo $lang['system_config_180'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='file_repository_upload_max' value='<?php echo $element_data['file_repository_upload_max'] ?>'
			onblur="redcap_validate(this,'1','<?php echo maxUploadSize() ?>','hard','int')" size='10' />
		<span style="color: #888;"><?php echo "{$lang['system_config_65']} (".maxUploadSize()." MB)" ?></span><br/>
		<div class="cc_info">
			<?php echo "{$lang['system_config_181']} 
				<a href='javascript:;' style='color:#000066;font-size:11px;text-decoration:underline;' onclick=\"openMaxUploadSizePopup()\">{$lang['system_config_68']} ".maxUploadSize()." MB?</a>" ?>
		</div>
	</td>
</tr>

<tr>
	<td colspan="2">
		<hr size=1>
		<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['system_config_219'] ?></h3>
	</td>
</tr>

<tr  id="edoc_field_option_enabled-tr" sq_id="edoc_field_option_enabled">
	<td class="cc_label"><?php echo $lang['system_config_216'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="edoc_field_option_enabled">
			<option value='0' <?php echo ($element_data['edoc_field_option_enabled'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['edoc_field_option_enabled'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_217'] ?>
		</div>
	</td>
</tr>
<tr  id="edoc_upload_max-tr" sq_id="edoc_upload_max">
	<td class="cc_label"><?php echo $lang['system_config_179'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='edoc_upload_max' value='<?php echo $element_data['edoc_upload_max'] ?>'
			onblur="redcap_validate(this,'1','<?php echo maxUploadSize() ?>','hard','int')" size='10' />
		<span style="color: #888;"><?php echo "{$lang['system_config_65']} (".maxUploadSize()." MB)" ?></span><br/>
		<div class="cc_info">
			<?php echo "{$lang['system_config_67']} 
				<a href='javascript:;' style='color:#000066;font-size:11px;text-decoration:underline;' onclick=\"openMaxUploadSizePopup()\">{$lang['system_config_68']} ".maxUploadSize()." MB?</a>" ?>
		</div>
	</td>
</tr>

<tr>
	<td colspan="2">
		<hr size=1>
		<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['form_renderer_25'] ?></h3>
	</td>
</tr>

<tr  id="sendit_enabled-tr" sq_id="sendit_enabled">
	<td class="cc_label"><?php echo $lang['system_config_52'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="sendit_enabled">
			<option value='0' <?php echo ($element_data['sendit_enabled'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['sendit_enabled'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_54'] ?></option>
			<option value='2' <?php echo ($element_data['sendit_enabled'] == 2) ? "selected" : "" ?>><?php echo $lang['system_config_55'] ?></option>
			<option value='3' <?php echo ($element_data['sendit_enabled'] == 3) ? "selected" : "" ?>><?php echo $lang['system_config_56'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_53'] ?>
		</div>
	</td>
</tr>
<tr  id="sendit_upload_max-tr" sq_id="sendit_upload_max">
	<td class="cc_label"><?php echo $lang['system_config_70'] ?></td>
	<td class="cc_data">
		<input class='x-form-text x-form-field '  type='text' name='sendit_upload_max' value='<?php echo $element_data['sendit_upload_max'] ?>'
			onblur="redcap_validate(this,'1','<?php echo maxUploadSize() ?>','hard','int')" size='10' />
		<span style="color: #888;"><?php echo "{$lang['system_config_65']} (".maxUploadSize()." MB)" ?></span><br/>
		<div class="cc_info">
			<?php echo "{$lang['system_config_71']}
				<a href='javascript:;' style='color:#000066;font-size:11px;text-decoration:underline;' onclick=\"openMaxUploadSizePopup()\">{$lang['system_config_68']} ".maxUploadSize()." MB?</a>"
			?>
		</div>
	</td>
</tr>
</table><br/>
<div style="text-align: center;"><input type='submit' name='' value='Save Changes' /></div><br/>
</form>


<!-- Max Upload Size Popup -->
<p id='chUpDef' style='display:none;' title="<?php echo cleanHtml2($lang['system_config_68'])." ".maxUploadSize()." MB?" ?>"><?php echo $lang['system_config_69'] ?></p>

<!-- Javascript Actions -->
<script type="text/javascript">
function openMaxUploadSizePopup() {
	$('#chUpDef').dialog({ bgiframe: true, modal: true, width: 500, buttons: { Close: function() { $(this).dialog('close'); } } });
}
</script>

<?php include 'footer.php'; ?>