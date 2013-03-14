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

<h3 style="margin-top: 0;"><?php echo $lang['control_center_114'] ?></h3>

<form action='modules_settings.php' enctype='multipart/form-data' target='_self' method='post' name='form' id='form'>
<?php
// Go ahead and manually add the CSRF token even though jQuery will automatically add it after DOM loads.
// (This is done in case the page is very long and user submits form before the DOM has finished loading.)
print "<input type='hidden' name='redcap_csrf_token' value='".getCsrfToken()."'>";
?>
<table style="border: 1px solid #ccc; background-color: #f0f0f0; width: 100%;">


<!-- Various modules/services -->
<tr>
	<td colspan="2">
	<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['system_config_150'] ?></h3>
	</td>
</tr>

<!-- Enable/disable the use of surveys in projects -->
<tr>
	<td class="cc_label"><img src="<?php echo APP_PATH_IMAGES ?>send.png" class="imgfix"> <?php echo $lang['system_config_237'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="enable_projecttype_singlesurveyforms">
			<option value='0' <?php echo ($element_data['enable_projecttype_singlesurveyforms'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['enable_projecttype_singlesurveyforms'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select>
	</td>
</tr>

<tr  id="enable_url_shortener-tr" sq_id="enable_url_shortener">
	<td class="cc_label"><img src="<?php echo APP_PATH_IMAGES ?>link.png" class="imgfix"> <?php echo $lang['system_config_132'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="enable_url_shortener">
			<option value='0' <?php echo ($element_data['enable_url_shortener'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['enable_url_shortener'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_238'] ?>
		</div>
	</td>
</tr>

<!-- Randomization -->
<tr>
	<td class="cc_label"><img src="<?php echo APP_PATH_IMAGES ?>arrow_switch.png" class="imgfix"> <?php echo $lang['app_21'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="randomization_global">
			<option value='0' <?php echo ($element_data['randomization_global'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['randomization_global'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_225'] ?>
		</div>
	</td>
</tr>

<!-- Shared Library -->
<tr  id="shared_library_enabled-tr" sq_id="shared_library_enabled">
	<td class="cc_label"><img src="<?php echo APP_PATH_IMAGES ?>blogs_arrow.png" class="imgfix"> REDCap Shared Library</td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="shared_library_enabled">
			<option value='0' <?php echo ($element_data['shared_library_enabled'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['shared_library_enabled'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_110'] ?>
			<a href="<?php echo SHARED_LIB_PATH ?>" style='text-decoration:underline;' target='_blank'>REDCap Shared Library</a>
			<?php echo $lang['system_config_111'] ?>
		</div>
	</td>
</tr>
<tr  id="api_enabled-tr" sq_id="api_enabled">
	<td class="cc_label"><img src="<?php echo APP_PATH_IMAGES ?>computer.png" class="imgfix"> REDCap API</td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="api_enabled">
			<option value='0' <?php echo ($element_data['api_enabled'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['api_enabled'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_114'] ?>
			<a href='<?php echo APP_PATH_WEBROOT_FULL ?>api/help/' style='text-decoration:underline;' target='_blank'>REDCap API help page</a>.
		</div>
	</td>
</tr>
<tr  id="dts_enabled_global-tr" sq_id="dts_enabled_global">
	<td class="cc_label"><img src="<?php echo APP_PATH_IMAGES ?>databases_arrow.png" class="imgfix"> <?php echo $lang['rights_132'] ?></td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="dts_enabled_global">
			<option value='0' <?php echo ($element_data['dts_enabled_global'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['dts_enabled_global'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_124'] ?>
			<a href='https://iwg.devguard.com/trac/redcap/wiki/DTS' style='text-decoration:underline;' target='_blank''>REDCap DTS wiki page</a>.
		</div>
	</td>
</tr>

<tr>
	<td colspan="2">
	<hr size=1>
	<h3 style="font-size:14px;padding:0 10px;color:#800000;"><?php echo $lang['system_config_172'] ?></h3>
	</td>
</tr>
<tr  id="enable_plotting-tr" sq_id="enable_plotting">
	<td class="cc_label">
		<img src="<?php echo APP_PATH_IMAGES ?>chart_curve.png" class="imgfix"> 
		<?php echo $lang['system_config_175'] ?>
		<div class="cc_info" style="font-weight:normal;"><?php echo $lang['system_config_166'] ?></div>
		<div class="cc_info" style="color:#800000;font-weight:normal;"><?php echo $lang['system_config_174'] ?></div>
	</td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="enable_plotting">
			<option value='0' <?php echo ($element_data['enable_plotting'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='2' <?php echo ($element_data['enable_plotting'] == 2) ? "selected" : "" ?>><?php echo $lang['system_config_165'] ?></option>
			<option value='1' <?php echo ($element_data['enable_plotting'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_164'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_167'] ?>
			<a href='https://iwg.devguard.com/trac/redcap/wiki/REDCapPlotService'
			   style='text-decoration:underline;' target='_blank'><?php echo $lang['system_config_25'] ?></a><?php echo $lang['period'] ?><br><br>
			<img src="<?php echo APP_PATH_IMAGES ?>help.png" class="imgfix"> 
			<a href="javascript:;" onclick="$('#diffPlotService').toggle('fast');"
			   style="text-decoration:underline;color:#3E72A8;"><?php echo $lang['system_config_168'] ?></a>
			<div id="diffPlotService" style="display:none;padding:3px;">
				<?php echo $lang['system_config_169'] ?>
			</div>
		</div>
	</td>
</tr>
<tr  id="enable_plotting_survey_results-tr" sq_id="enable_plotting_survey_results">
	<td class="cc_label">
		<img src="<?php echo APP_PATH_IMAGES ?>chart_curve.png" class="imgfix"> 
		<?php echo $lang['system_config_176'] ?>
		<div class="cc_info" style="font-weight:normal;color:#800000;">
			<?php echo $lang['system_config_173'] ?>
		</div>
	</td>
	<td class="cc_data">
		<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="enable_plotting_survey_results">
			<option value='0' <?php echo ($element_data['enable_plotting_survey_results'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
			<option value='1' <?php echo ($element_data['enable_plotting_survey_results'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
		</select><br/>
		<div class="cc_info">
			<?php echo $lang['system_config_171'] ?>
		</div>
	</td>
</tr>
</table><br/>
<div style="text-align: center;"><input type='submit' name='' value='Save Changes' /></div><br/>
</form>

<?php include 'footer.php'; ?>